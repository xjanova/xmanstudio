<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DomainRegistration;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Services\HostingerApiService;
use App\Services\VpsOrderException;
use App\Services\VpsProvisioningService;
use App\Support\VpsCatalog;
use App\Support\VpsSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The customer's own servers: what they rent, and every button a VPS panel
 * needs — power, root password, reinstall, snapshots, backups — without a
 * ticket and without anyone at XMAN in the loop. What they never see is who
 * the machine is rented from.
 *
 * Every read of a row goes through findOwned(): someone else's id is a 404,
 * never a 403, so the ids cannot be probed.
 */
class VpsController extends Controller
{
    public function __construct(
        protected HostingerApiService $api,
        protected VpsProvisioningService $provisioning,
        protected VpsCatalog $catalog,
    ) {}

    public function index(Request $request): View
    {
        $servers = VpsInstance::where('user_id', $request->user()->id)
            ->visible()
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 WHEN status IN ('pending','provisioning') THEN 1 ELSE 2 END")
            ->orderBy('expires_at')
            ->orderByDesc('created_at')
            ->get();

        return view('customer.vps.index', [
            'servers' => $servers,
            'expiringSoon' => $servers->filter(fn (VpsInstance $s) => $s->isActive() && $s->isExpiringSoon(VpsSettings::noticeDays())),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $server = $this->findOwned($request, $id);

        if ($server->isManageable()) {
            $this->refreshLive($server);
        }

        $manageable = $server->isManageable();

        return view('customer.vps.show', [
            'server' => $server,
            'manageable' => $manageable,
            'snapshot' => $manageable ? $this->snapshotFor($server) : null,
            'backups' => $manageable ? $this->backupsFor($server) : [],
            'metrics' => $manageable ? $this->metricsFor($server) : null,
            'templates' => $manageable ? $this->catalog->groupedTemplates() : [],
            'domains' => $manageable
                ? DomainRegistration::where('user_id', $server->user_id)->registrations()->active()->orderBy('domain')->get(['id', 'domain'])
                : collect(),
            'payments' => $server->payments()->get(),
            'canRenew' => $server->canRenew(),
            'renewPrice' => $server->plan?->renewPriceThb((string) $server->period) ?? 0.0,
            'chargeDays' => VpsSettings::chargeDays(),
        ]);
    }

    /**
     * Polled by the page while a server is being built, so the customer sees
     * it come up without refreshing. Moves the order along itself when the
     * scheduler has not looked at it for a minute — the customer is watching.
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $server = $this->findOwned($request, $id);

        if (! $server->isSettled() && (! $server->last_polled_at || $server->last_polled_at->lt(now()->subMinute()))) {
            try {
                $this->provisioning->reconcile($server);
                $server->refresh();
            } catch (\Throwable $e) {
                Log::warning('[CustomerVps] inline reconcile failed', ['instance' => $server->id, 'error' => $e->getMessage()]);
            }
        } elseif ($server->isManageable()) {
            $this->refreshLive($server);
        }

        $badge = $server->statusBadge();
        $state = $server->stateLabel();

        return response()->json([
            'status' => $server->status,
            'settled' => $server->isSettled(),
            'badge' => $badge['label_th'] . ' / ' . $badge['label_en'],
            'state' => $server->state,
            'state_label' => $state['th'] . ' / ' . $state['en'],
            'busy' => $server->isBusy(),
            'ipv4' => $server->ipv4,
        ]);
    }

    public function power(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $action = (string) $request->input('action');

        if (! in_array($action, ['start', 'stop', 'restart'], true)) {
            return back()->with('error', 'คำสั่งไม่ถูกต้อง');
        }

        $result = $this->api->powerVirtualMachine((int) $server->remote_vm_id, $action);

        if ($result === null || $result['status_code'] >= 300) {
            return back()->with('error', VpsProvisioningService::explain($result));
        }

        $this->forgetLive($server);
        $this->audit($request, $server, 'power:' . $action);

        return back()->with('success', match ($action) {
            'start' => 'สั่งเปิดเครื่องแล้ว ใช้เวลาประมาณ 1 นาที',
            'stop' => 'สั่งปิดเครื่องแล้ว — การปิดเครื่องไม่หยุดรอบบิล เครื่องยังเป็นของคุณจนหมดอายุ',
            default => 'สั่งรีสตาร์ทแล้ว ใช้เวลาประมาณ 1–2 นาที',
        });
    }

    public function password(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'root_password' => ['required', 'string', 'max:128', 'confirmed', Password::min(12)->mixedCase()->numbers()->uncompromised()],
        ], $this->passwordMessages());

        $result = $this->api->setVirtualMachineRootPassword((int) $server->remote_vm_id, $validated['root_password']);

        if ($result === null || $result['status_code'] >= 300) {
            return back()->with('error', VpsProvisioningService::explain($result));
        }

        $server->update(['needs_password_reset' => false]);
        $this->audit($request, $server, 'root-password');

        return back()->with('success', 'ตั้งรหัสผ่าน root ใหม่แล้ว ใช้รหัสใหม่เข้าระบบได้ภายใน 1–2 นาที');
    }

    public function hostname(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'hostname' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i'],
        ], [
            'hostname.regex' => 'ชื่อโฮสต์ต้องเป็นรูปแบบโดเมน เช่น server.example.com',
        ]);

        $hostname = strtolower($validated['hostname']);
        $result = $this->api->setVirtualMachineHostname((int) $server->remote_vm_id, $hostname);

        if ($result === null || $result['status_code'] >= 300) {
            return back()->with('error', VpsProvisioningService::explain($result));
        }

        $server->update(['hostname' => $hostname]);
        $this->forgetLive($server);

        return back()->with('success', 'เปลี่ยนชื่อโฮสต์เป็น ' . $hostname . ' แล้ว');
    }

    /**
     * Wipe the disk and install an operating system again.
     *
     * The most destructive button on the page, so it asks for the hostname
     * typed back — a confirm() dialog is clicked through without reading.
     */
    public function reinstall(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'min:1'],
            'root_password' => ['required', 'string', 'max:128', Password::min(12)->mixedCase()->numbers()->uncompromised()],
            'confirm_hostname' => ['required', 'string'],
        ], $this->passwordMessages());

        if (strtolower(trim($validated['confirm_hostname'])) !== strtolower($server->hostname)) {
            return back()->with('error', 'พิมพ์ชื่อโฮสต์ไม่ตรง จึงยังไม่ได้ติดตั้งใหม่ — ข้อมูลในเครื่องยังอยู่ครบ');
        }

        $template = $this->catalog->template((int) $validated['template_id']);

        if (! $template) {
            return back()->with('error', 'ไม่พบระบบปฏิบัติการที่เลือก');
        }

        $result = $this->api->recreateVirtualMachine((int) $server->remote_vm_id, $template['id'], $validated['root_password']);

        if ($result === null || $result['status_code'] >= 300) {
            return back()->with('error', VpsProvisioningService::explain($result));
        }

        $server->update([
            'template_id' => $template['id'],
            'template_name' => $template['name'],
            'state' => 'recreating',
            'needs_password_reset' => false,
        ]);
        $this->forgetLive($server);
        $this->audit($request, $server, 'reinstall:' . $template['id']);

        return back()->with('success', 'เริ่มติดตั้ง ' . $template['name'] . ' ใหม่แล้ว ใช้เวลาประมาณ 5–10 นาที');
    }

    /**
     * create — overwrites the one snapshot a server can hold.
     * restore — puts the whole disk back to it.
     * delete.
     */
    public function snapshot(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);
        $action = (string) $request->input('action');
        $vm = (int) $server->remote_vm_id;

        $result = match ($action) {
            'create' => $this->api->createVirtualMachineSnapshot($vm),
            'restore' => $this->api->restoreVirtualMachineSnapshot($vm),
            'delete' => $this->api->deleteVirtualMachineSnapshot($vm),
            default => false,
        };

        if ($result === false) {
            return back()->with('error', 'คำสั่งไม่ถูกต้อง');
        }

        if ($result === null || $result['status_code'] >= 300) {
            return back()->with('error', VpsProvisioningService::explain($result, 'ทำรายการสแนปช็อตไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }

        Cache::forget('vps.snapshot.' . $server->id);
        $this->forgetLive($server);
        $this->audit($request, $server, 'snapshot:' . $action);

        return back()->with('success', match ($action) {
            'create' => 'กำลังสร้างสแนปช็อต ใช้เวลาไม่กี่นาที (สแนปช็อตเดิมถูกแทนที่)',
            'restore' => 'กำลังกู้คืนจากสแนปช็อต เครื่องจะรีสตาร์ทเมื่อเสร็จ',
            default => 'ลบสแนปช็อตแล้ว',
        });
    }

    public function restoreBackup(Request $request, int $id, int $backup): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        // Only a backup of THIS machine — the id arrives from the form.
        $known = collect($this->backupsFor($server))->pluck('id')->map(fn ($v) => (int) $v)->all();

        if (! in_array($backup, $known, true)) {
            return back()->with('error', 'ไม่พบแบ็กอัปนี้ กรุณารีเฟรชหน้าแล้วลองใหม่');
        }

        $result = $this->api->restoreVirtualMachineBackup((int) $server->remote_vm_id, $backup);

        if ($result === null || $result['status_code'] >= 300) {
            return back()->with('error', VpsProvisioningService::explain($result));
        }

        $this->forgetLive($server);
        $this->audit($request, $server, 'backup-restore:' . $backup);

        return back()->with('success', 'เริ่มกู้คืนจากแบ็กอัปแล้ว ข้อมูลในเครื่องจะถูกแทนที่ด้วยข้อมูลในแบ็กอัป ใช้เวลาสักครู่');
    }

    /**
     * Point one of the customer's own domains at this server: A for the bare
     * name, CNAME for www. Only those two name/type pairs are written — mail
     * and every other record on the domain stay exactly as they were.
     */
    public function pointDomain(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate(['domain_id' => ['required', 'integer']]);

        $domain = DomainRegistration::where('id', $validated['domain_id'])
            ->where('user_id', $request->user()->id)
            ->registrations()
            ->active()
            ->first();

        if (! $domain) {
            return back()->with('error', 'ไม่พบโดเมนนี้ในบัญชีของคุณ');
        }

        if (! $server->ipv4) {
            return back()->with('error', 'เซิร์ฟเวอร์ยังไม่มี IP กรุณารอให้ติดตั้งเสร็จก่อน');
        }

        $zone = [
            ['name' => '@', 'type' => 'A', 'ttl' => 3600, 'records' => [['content' => $server->ipv4]]],
            ['name' => 'www', 'type' => 'CNAME', 'ttl' => 3600, 'records' => [['content' => $domain->domain]]],
        ];

        $check = $this->api->validateDnsRecords($domain->domain, $zone);

        if (! $check['ok'] || ! $this->api->updateDnsRecords($domain->domain, $zone, overwrite: true)) {
            return back()->with('error', 'ชี้โดเมนไม่สำเร็จ — ถ้าโดเมนใช้ nameserver ที่อื่นอยู่ ให้ตั้ง A record ไปที่ ' . $server->ipv4 . ' เองที่ผู้ให้บริการ DNS นั้น');
        }

        Cache::forget('domain.snapshots.' . $domain->id);
        $this->audit($request, $server, 'point-domain:' . $domain->domain);

        return back()->with('success', 'ชี้ ' . $domain->domain . ' และ www.' . $domain->domain . ' มาที่เซิร์ฟเวอร์นี้แล้ว (' . $server->ipv4 . ') มีผลใน 5–30 นาที');
    }

    public function toggleAutoRenew(Request $request, int $id): RedirectResponse
    {
        $server = $this->findOwned($request, $id);

        $enabled = $request->boolean('auto_renew');
        $server->update(['auto_renew' => $enabled]);

        return back()->with('success', $enabled
            ? 'เปิดต่ออายุอัตโนมัติแล้ว เราจะตัดจากกระเป๋าเงินก่อนหมดอายุ ' . VpsSettings::chargeDays() . ' วัน และแจ้งล่วงหน้าทุกครั้ง'
            : 'ปิดต่ออายุอัตโนมัติแล้ว — ถ้าไม่ต่ออายุเอง เครื่องจะถูกระงับเมื่อหมดอายุ และข้อมูลจะถูกลบในที่สุด');
    }

    public function renew(Request $request, int $id): RedirectResponse
    {
        $server = $this->findOwned($request, $id);

        try {
            $payment = $this->provisioning->renew($server);
        } catch (VpsOrderException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'ต่ออายุไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือติดต่อทีมงาน');
        }

        if ($payment->status === VpsPayment::STATUS_REFUNDED) {
            return back()->with('error', 'ต่ออายุไม่สำเร็จ คืนเงินเข้ากระเป๋าของคุณเรียบร้อยแล้ว ทีมงานได้รับแจ้งแล้ว — กรุณาลองใหม่อีกครั้งในภายหลัง');
        }

        if ($payment->status === VpsPayment::STATUS_PENDING) {
            return back()->with('success', 'รับคำสั่งต่ออายุแล้ว กำลังยืนยันกับระบบ ภายในไม่กี่นาทีวันหมดอายุจะอัปเดต');
        }

        return back()->with('success', sprintf(
            'ต่ออายุ %s เรียบร้อย ใช้งานได้ถึง %s',
            $server->hostname,
            $server->fresh()?->expires_at?->format('d/m/Y') ?? '-',
        ));
    }

    // ================================================================ helpers

    protected function findOwned(Request $request, int $id): VpsInstance
    {
        return VpsInstance::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    /** The customer's own server, and only while it is running and has a machine behind it. */
    protected function manageable(Request $request, int $id): VpsInstance
    {
        $server = $this->findOwned($request, $id);

        if (! $server->isManageable()) {
            abort(redirect()->route('customer.vps.show', $server->id)
                ->with('error', 'เซิร์ฟเวอร์นี้ยังจัดการไม่ได้ — ยังติดตั้งไม่เสร็จหรือหมดอายุแล้ว'));
        }

        return $server;
    }

    /**
     * Pull state and addresses, at most every 20 seconds per server. The page
     * reloads after every button and the supplier allows 90 calls a minute
     * for the whole site.
     */
    protected function refreshLive(VpsInstance $server): void
    {
        if (Cache::add('vps.live.' . $server->id, 1, 20)) {
            $this->provisioning->refreshVm($server);
        }
    }

    protected function forgetLive(VpsInstance $server): void
    {
        Cache::forget('vps.live.' . $server->id);
    }

    /**
     * @return array{id:int,created_at:?string,expires_at:?string,restore_minutes:int}|null
     */
    protected function snapshotFor(VpsInstance $server): ?array
    {
        $raw = Cache::remember('vps.snapshot.' . $server->id, 60, fn () => $this->api->getVirtualMachineSnapshot((int) $server->remote_vm_id) ?? []);

        if (empty($raw['id'])) {
            return null;
        }

        return [
            'id' => (int) $raw['id'],
            'created_at' => $raw['created_at'] ?? null,
            'expires_at' => $raw['expires_at'] ?? null,
            'restore_minutes' => (int) ceil(((int) ($raw['restore_time'] ?? 0)) / 60),
        ];
    }

    /**
     * @return array<int,array{id:int,created_at:?string,size_gb:float}>
     */
    protected function backupsFor(VpsInstance $server): array
    {
        $raw = Cache::remember('vps.backups.' . $server->id, 300, fn () => $this->api->getVirtualMachineBackups((int) $server->remote_vm_id) ?? []);
        $out = [];

        foreach ($raw as $row) {
            if (is_array($row) && isset($row['id'])) {
                $out[] = [
                    'id' => (int) $row['id'],
                    'created_at' => $row['created_at'] ?? null,
                    'size_gb' => round(((int) ($row['size'] ?? 0)) / 1024 / 1024, 1),
                ];
            }
        }

        usort($out, fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));

        return array_slice($out, 0, 8);
    }

    /**
     * The last 24 hours, boiled down to what a dashboard shows: the latest
     * CPU and RAM with a sparkline each, disk used, traffic, uptime.
     *
     * @return array<string,mixed>|null
     */
    protected function metricsFor(VpsInstance $server): ?array
    {
        $raw = Cache::remember('vps.metrics.' . $server->id, 300, fn () => $this->api->getVirtualMachineMetrics(
            (int) $server->remote_vm_id,
            now()->subDay()->utc(),
            now()->utc(),
        ) ?? []);

        if (! is_array($raw) || $raw === []) {
            return null;
        }

        $series = function (string $key) use ($raw): array {
            $usage = $raw[$key]['usage'] ?? [];

            if (! is_array($usage)) {
                return [];
            }

            ksort($usage);

            return array_map('floatval', $usage);
        };

        $cpu = $series('cpu_usage');
        $ram = $series('ram_usage');
        $disk = $series('disk_space');
        $out = $series('outgoing_traffic');
        $in = $series('incoming_traffic');
        $uptime = $series('uptime');

        $memoryBytes = max(1, $server->spec('memory_mb')) * 1024 * 1024;
        $diskBytes = max(1, $server->spec('disk_mb')) * 1024 * 1024;

        return [
            'cpu' => $cpu ? round((float) end($cpu), 1) : null,
            'cpu_line' => $this->sparkline($cpu, 100),
            'ram_percent' => $ram ? round(((float) end($ram)) / $memoryBytes * 100, 1) : null,
            'ram_used' => $ram ? round(((float) end($ram)) / 1024 / 1024 / 1024, 2) : null,
            'ram_line' => $this->sparkline(array_map(fn ($v) => $v / $memoryBytes * 100, $ram), 100),
            'disk_used' => $disk ? round(((float) end($disk)) / 1024 / 1024 / 1024, 1) : null,
            'disk_percent' => $disk ? round(((float) end($disk)) / $diskBytes * 100, 1) : null,
            'traffic_out_gb' => round(array_sum($out) / 1024 / 1024 / 1024, 2),
            'traffic_in_gb' => round(array_sum($in) / 1024 / 1024 / 1024, 2),
            'uptime_hours' => $uptime ? round(((float) end($uptime)) / 1000 / 3600, 1) : null,
        ];
    }

    /**
     * SVG polyline points for a 0..$max series in a 120×32 box. Drawn on the
     * server so the page needs no charting library.
     *
     * @param  array<int|string,float>  $values
     */
    protected function sparkline(array $values, float $max): string
    {
        $values = array_values($values);
        $n = count($values);

        if ($n < 2) {
            return '';
        }

        // At most 60 points: a 24-hour series at one-minute resolution is
        // 1,440 vertices nobody can see at 120 pixels wide.
        $step = max(1, (int) ceil($n / 60));
        $points = [];

        for ($i = 0; $i < $n; $i += $step) {
            $x = round($i / ($n - 1) * 120, 1);
            $y = round(32 - min($max, max(0, $values[$i])) / $max * 30 - 1, 1);
            $points[] = $x . ',' . $y;
        }

        return implode(' ', $points);
    }

    protected function audit(Request $request, VpsInstance $server, string $what): void
    {
        Log::info('[CustomerVps] ' . $what, [
            'instance' => $server->id,
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);
    }

    /** @return array<string,string> */
    protected function passwordMessages(): array
    {
        return [
            'root_password.required' => 'กรุณาตั้งรหัสผ่าน root',
            'root_password.min' => 'รหัสผ่านต้องยาวอย่างน้อย 12 ตัวอักษร',
            'root_password.mixed' => 'รหัสผ่านต้องมีทั้งตัวพิมพ์ใหญ่และตัวพิมพ์เล็ก',
            'root_password.numbers' => 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว',
            'root_password.uncompromised' => 'รหัสผ่านนี้เคยหลุดสู่สาธารณะ กรุณาตั้งรหัสใหม่',
            'root_password.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ];
    }
}
