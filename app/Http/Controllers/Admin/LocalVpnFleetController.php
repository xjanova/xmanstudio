<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\VpnGameProfile;
use App\Models\VpnNode;
use App\Models\VpnNodeUsageDaily;
use App\Models\VpnUserUsageDaily;
use App\Models\WireguardServer;
use App\Services\LocalVpnFleet\FleetBudget;
use App\Support\LocalVpnFleet\FleetClock;
use App\Support\LocalVpnFleet\FleetPolicy;
use App\Support\LocalVpnFleet\Ipv4Cidr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * LocalVPN → Fleet: the relay/exit nodes, the tier policy and what it all
 * costs (docs/RELAY_FLEET.md in the app repo).
 *
 * A node's secret is generated here, stored encrypted, and shown exactly once
 * — on the page that answers the create or rotate request, never in a list,
 * never flashed into the session.
 */
class LocalVpnFleetController extends Controller
{
    public function __construct(private readonly FleetBudget $budget) {}

    // ==================== Nodes ====================

    public function nodes(): View
    {
        $nodes = VpnNode::with(['wireguardServer' => fn ($q) => $q->withCount([
            'clients as connected_clients_count' => fn ($c) => $c->where('is_connected', true),
        ])])->orderBy('code')->get();

        $snapshot = $this->budget->fresh();

        return view('admin.localvpn.fleet-nodes', compact('nodes', 'snapshot'));
    }

    public function createNode(): View
    {
        return view('admin.localvpn.fleet-node-form', ['node' => new VpnNode([
            'relay_port' => 51821,
            'wg_port' => 51820,
            'roles' => [VpnNode::ROLE_RELAY],
            'status' => VpnNode::STATUS_ACTIVE,
            'country' => 'TH',
            'included_gb' => 1000,
            'billing_day' => 1,
            'billing_mode' => 'out',
            'max_clients' => 2000,
            'weight' => 100,
        ]), 'server' => null]);
    }

    public function storeNode(Request $request): Response
    {
        $data = $this->validateNode($request);

        $secret = VpnNode::generateSecret();
        $node = DB::transaction(function () use ($data, $secret) {
            $node = new VpnNode($this->nodeAttributes($data));
            $node->secret = $secret;
            $node->save();
            $this->syncExitServer($node, $data);

            return $node;
        });
        FleetBudget::forget();

        return $this->showSecret($node, $secret, "เพิ่มโหนด {$node->code} แล้ว");
    }

    public function editNode(VpnNode $node): View
    {
        return view('admin.localvpn.fleet-node-form', ['node' => $node, 'server' => $node->wireguardServer]);
    }

    public function updateNode(Request $request, VpnNode $node): RedirectResponse
    {
        $data = $this->validateNode($request, $node);

        DB::transaction(function () use ($node, $data) {
            $node->fill($this->nodeAttributes($data))->save();
            $this->syncExitServer($node, $data);
        });
        FleetBudget::forget();

        return redirect()->route('admin.localvpn.fleet.nodes')->with('success', "บันทึกโหนด {$node->code} แล้ว");
    }

    public function rotateSecret(VpnNode $node): Response
    {
        $secret = VpnNode::generateSecret();
        $node->secret = $secret;
        $node->save();

        return $this->showSecret($node, $secret, "สร้างรหัสลับใหม่ให้โหนด {$node->code} แล้ว — รหัสเดิมใช้ไม่ได้อีก");
    }

    public function toggleNode(VpnNode $node): RedirectResponse
    {
        $node->status = $node->status === VpnNode::STATUS_DISABLED ? VpnNode::STATUS_ACTIVE : VpnNode::STATUS_DISABLED;
        $node->save();
        FleetBudget::forget();

        $label = $node->status === VpnNode::STATUS_DISABLED ? 'ปิดใช้งาน' : 'เปิดใช้งาน';

        return back()->with('success', "โหนด {$node->code} ถูก{$label}แล้ว");
    }

    public function destroyNode(VpnNode $node): RedirectResponse
    {
        $code = $node->code;
        // The node's WireGuard server, its clients and usage rows go with it (FK cascade).
        $node->delete();
        FleetBudget::forget();

        return redirect()->route('admin.localvpn.fleet.nodes')->with('success', "ลบโหนด {$code} แล้ว");
    }

    // ==================== Policy & game profiles ====================

    public function policy(): View
    {
        return view('admin.localvpn.fleet-policy', [
            'policy' => FleetPolicy::current(),
            'profiles' => VpnGameProfile::orderBy('sort')->orderBy('name')->get(),
        ]);
    }

    public function updatePolicy(Request $request): RedirectResponse
    {
        $request->validate(FleetPolicy::rules(), FleetPolicy::messages());
        FleetPolicy::save($request->only(['tiers', 'budget']));
        FleetBudget::forget();

        return back()->with('success', 'บันทึกนโยบาย Fleet แล้ว');
    }

    public function storeGameProfile(Request $request): RedirectResponse
    {
        $data = $this->validateProfile($request, null, 'profileCreate');
        VpnGameProfile::create($data);

        return back()->with('success', "เพิ่มโปรไฟล์เกม {$data['name']} แล้ว");
    }

    public function updateGameProfile(Request $request, VpnGameProfile $profile): RedirectResponse
    {
        $data = $this->validateProfile($request, $profile, 'profileUpdate');
        $profile->update($data);

        return back()->with('success', "บันทึกโปรไฟล์เกม {$profile->name} แล้ว");
    }

    public function destroyGameProfile(VpnGameProfile $profile): RedirectResponse
    {
        $name = $profile->name;
        $profile->delete();

        return back()->with('success', "ลบโปรไฟล์เกม {$name} แล้ว");
    }

    // ==================== Cost ====================

    public function cost(): View
    {
        $snapshot = $this->budget->fresh();
        $nodes = VpnNode::orderBy('code')->get();

        $since = FleetClock::now()->subDays(29)->toDateString();
        $byDay = VpnNodeUsageDaily::query()
            ->where('date', '>=', $since)
            ->groupBy('date')
            ->selectRaw('date, SUM(nic_rx) as nic_rx, SUM(nic_tx) as nic_tx, SUM(relay_bytes) as relay_bytes, SUM(exit_bytes) as exit_bytes')
            ->toBase()
            ->get()
            ->keyBy(fn ($row) => substr((string) $row->date, 0, 10));

        $days = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = FleetClock::now()->subDays($i)->toDateString();
            $row = $byDay[$date] ?? null;
            $days[] = [
                'date' => $date,
                'out_gb' => round(((int) ($row->nic_tx ?? 0)) / FleetBudget::BYTES_PER_GB, 3),
                'in_gb' => round(((int) ($row->nic_rx ?? 0)) / FleetBudget::BYTES_PER_GB, 3),
                'relay_gb' => round(((int) ($row->relay_bytes ?? 0)) / FleetBudget::BYTES_PER_GB, 3),
                'exit_gb' => round(((int) ($row->exit_bytes ?? 0)) / FleetBudget::BYTES_PER_GB, 3),
            ];
        }

        $monthStart = FleetClock::now()->startOfMonth()->toDateString();
        $topRelay = $this->topUsers(VpnUserUsageDaily::KIND_RELAY, $monthStart);
        $topExit = $this->topUsers(VpnUserUsageDaily::KIND_EXIT, $monthStart);

        return view('admin.localvpn.fleet-cost', compact('snapshot', 'nodes', 'days', 'topRelay', 'topExit', 'monthStart'));
    }

    // ==================== Helpers ====================

    /** @return \Illuminate\Support\Collection<int, object> */
    private function topUsers(string $kind, string $since)
    {
        $rows = VpnUserUsageDaily::query()
            ->where('kind', $kind)
            ->where('date', '>=', $since)
            ->groupBy('machine_id')
            ->selectRaw('machine_id, SUM(bytes) as total_bytes, MAX(license_key_id) as license_key_id')
            ->orderByDesc('total_bytes')
            ->limit(20)
            ->toBase()
            ->get();

        $licenses = LicenseKey::with('user:id,name,email')
            ->whereIn('id', $rows->pluck('license_key_id')->filter()->all())
            ->get(['id', 'user_id', 'license_type', 'status', 'expires_at'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($licenses) {
            $row->license = $licenses[$row->license_key_id] ?? null;

            return $row;
        });
    }

    private function showSecret(VpnNode $node, string $secret, string $message): Response
    {
        return response()
            ->view('admin.localvpn.fleet-node-secret', [
                'node' => $node,
                'secret' => $secret,
                'command' => $node->installCommand($secret),
                'message' => $message,
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    private function validateNode(Request $request, ?VpnNode $node = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('vpn_nodes', 'code')->ignore($node?->id)],
            'country' => ['required', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255'],
            'relay_port' => ['required', 'integer', 'between:1,65535'],
            'wg_port' => ['required', 'integer', 'between:1,65535'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(VpnNode::ROLES)],
            'status' => ['required', Rule::in(VpnNode::STATUSES)],
            'monthly_cost_thb' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'included_gb' => ['required', 'integer', 'min:1', 'max:10000000'],
            'overage_thb_per_gb' => ['required', 'numeric', 'min:0', 'max:100000'],
            'billing_day' => ['required', 'integer', 'between:1,28'],
            'billing_mode' => ['required', Rule::in(VpnNode::BILLING_MODES)],
            'allow_overage' => ['nullable', 'boolean'],
            'max_clients' => ['required', 'integer', 'min:1', 'max:1000000'],
            'weight' => ['required', 'integer', 'min:1', 'max:10000'],
            'wg_address' => ['nullable', 'string', 'max:18'],
            'wg_dns' => ['nullable', 'string', 'max:255'],
            'wg_max_clients' => ['nullable', 'integer', 'min:1', 'max:65000'],
        ];

        $validator = validator($request->all(), $rules, [
            'code.regex' => 'รหัสโหนดใช้ได้เฉพาะ a-z 0-9 และขีด เช่น th-bkk-1',
            'country.regex' => 'รหัสประเทศ 2 ตัวอักษร เช่น TH, SG, JP',
        ]);

        $validator->after(function (Validator $v) use ($request) {
            $host = (string) $request->input('host');
            $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
            $isName = str_contains($host, '.') && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
            if ($host !== '' && ! $isIp && ! $isName) {
                $v->errors()->add('host', 'Host ต้องเป็น IP หรือชื่อโดเมน เช่น 203.0.113.10 หรือ th1.example.com');
            }

            $roles = (array) $request->input('roles', []);
            $wantsExit = in_array(VpnNode::ROLE_GAME, $roles, true) || in_array(VpnNode::ROLE_VPN, $roles, true);
            if ($wantsExit) {
                if ($error = self::wgAddressError((string) $request->input('wg_address'))) {
                    $v->errors()->add('wg_address', $error);
                }
                foreach (array_filter(array_map('trim', explode(',', (string) $request->input('wg_dns')))) as $dns) {
                    if (filter_var($dns, FILTER_VALIDATE_IP) === false) {
                        $v->errors()->add('wg_dns', "DNS ไม่ถูกต้อง: {$dns}");
                    }
                }
            }
        });

        return $validator->validate();
    }

    /** The exit's own address and subnet, e.g. 10.66.0.1/24 (private IPv4, /16–/29). */
    private static function wgAddressError(string $address): ?string
    {
        if (! preg_match('/^(\d{1,3}(?:\.\d{1,3}){3})\/(\d{1,2})$/', $address, $m)
            || filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return 'ต้องระบุที่อยู่ของ exit แบบ IP/prefix เช่น 10.66.0.1/24';
        }
        $prefix = (int) $m[2];
        if ($prefix < 16 || $prefix > 29) {
            return 'prefix ต้องอยู่ระหว่าง /16 ถึง /29';
        }
        if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false) {
            return 'ต้องเป็นช่วง IP ส่วนตัว (10.x, 172.16–31.x หรือ 192.168.x)';
        }
        $ip = ip2long($m[1]);
        $mask = ~((1 << (32 - $prefix)) - 1) & 0xFFFFFFFF;
        if ($ip === ($ip & $mask) || $ip === (($ip & $mask) | (~$mask & 0xFFFFFFFF))) {
            return 'ใช้ที่อยู่ของ exit เอง เช่น .1 ไม่ใช่ที่อยู่เครือข่ายหรือ broadcast';
        }

        return null;
    }

    private function nodeAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'code' => $data['code'],
            'country' => strtoupper($data['country']),
            'city' => $data['city'] ?? null,
            'provider' => $data['provider'] ?? null,
            'host' => $data['host'],
            'relay_port' => (int) $data['relay_port'],
            'wg_port' => (int) $data['wg_port'],
            'roles' => array_values(array_intersect(VpnNode::ROLES, $data['roles'])),
            'status' => $data['status'],
            'monthly_cost_thb' => $data['monthly_cost_thb'],
            'included_gb' => (int) $data['included_gb'],
            'overage_thb_per_gb' => $data['overage_thb_per_gb'],
            'billing_day' => (int) $data['billing_day'],
            'billing_mode' => $data['billing_mode'],
            'allow_overage' => (bool) ($data['allow_overage'] ?? false),
            'max_clients' => (int) $data['max_clients'],
            'weight' => (int) $data['weight'],
        ];
    }

    /**
     * Keep the node's WireGuard server row in step with its roles. The row
     * stores no private key — the node keeps its own and reports the public
     * key on sync, which also makes the row healthy.
     */
    private function syncExitServer(VpnNode $node, array $data): void
    {
        $server = $node->wireguardServer()->first();

        if ($node->exitMode() === null) {
            $server?->update(['is_active' => false]);

            return;
        }

        $attributes = [
            'name' => trim($node->name . ' (' . $node->code . ')'),
            'country_code' => $node->country,
            'country_name' => self::countryName($node->country),
            'endpoint' => $node->endpoint((int) $node->wg_port),
            'address' => $data['wg_address'],
            'dns' => trim((string) ($data['wg_dns'] ?? '')) !== '' ? trim($data['wg_dns']) : '1.1.1.1, 8.8.8.8',
            'listen_port' => (int) $node->wg_port,
            'max_clients' => (int) ($data['wg_max_clients'] ?? 250),
            'is_active' => true,
        ];

        if ($server === null) {
            WireguardServer::create($attributes + [
                'vpn_node_id' => $node->id,
                'public_key' => (string) $node->wg_public_key,
                'private_key' => null,
                'is_healthy' => (string) $node->wg_public_key !== '',
            ]);

            return;
        }

        // A new subnet strands every assigned address; those devices simply register again.
        if ($server->address !== $attributes['address']) {
            $server->clients()->delete();
        }
        $server->update($attributes);
    }

    private static function countryName(string $code): string
    {
        $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-' . $code, 'en') : '';

        return $name !== '' && $name !== $code ? $name : $code;
    }

    private function validateProfile(Request $request, ?VpnGameProfile $profile, string $bag): array
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('vpn_game_profiles', 'slug')->ignore($profile?->id)],
            'region' => ['nullable', 'string', 'max:64'],
            'cidrs' => ['required', 'string', 'max:60000'],
            'enabled' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:-100000', 'max:100000'],
        ], [
            'slug.regex' => 'slug ใช้ได้เฉพาะ a-z 0-9 และขีด เช่น rov-sea',
        ]);

        $validator->after(function (Validator $v) use ($request) {
            [$cidrs, $errors] = Ipv4Cidr::parseList((string) $request->input('cidrs'));
            foreach (array_slice($errors, 0, 20) as $error) {
                $v->errors()->add('cidrs', $error);
            }
            if ($errors === [] && $cidrs === []) {
                $v->errors()->add('cidrs', 'ต้องมี CIDR อย่างน้อยหนึ่งบรรทัด');
            }
            if (count($cidrs) > 2000) {
                $v->errors()->add('cidrs', 'มี CIDR ได้ไม่เกิน 2000 บรรทัดต่อโปรไฟล์');
            }
        });

        $data = $validator->validateWithBag($bag);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'region' => $data['region'] ?? null,
            'cidrs' => str_replace("\r\n", "\n", trim($data['cidrs'])),
            'enabled' => (bool) ($data['enabled'] ?? false),
            'sort' => (int) ($data['sort'] ?? 0),
        ];
    }
}
