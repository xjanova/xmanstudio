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
use App\Support\VpsFirewall;
use App\Support\VpsLabels;
use App\Support\VpsSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    /** The panel's sections. Each one fetches only what it shows — the supplier allows 90 calls a minute for the whole site. */
    public const TABS = ['overview', 'network', 'backups', 'system', 'activity', 'billing'];

    public function show(Request $request, int $id): View
    {
        $server = $this->findOwned($request, $id);

        $vm = $server->isManageable() ? $this->liveVm($server) : null;
        $manageable = $server->isManageable();

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'overview';

        // A server that cannot be managed has nothing behind the other tabs.
        if (! $manageable && ! in_array($tab, ['overview', 'billing'], true)) {
            $tab = 'overview';
        }

        $range = $request->query('range') === '7d' ? '7d' : '24h';

        $data = [
            'server' => $server,
            'manageable' => $manageable,
            'tab' => $tab,
            'range' => $range,
            'vm' => $vm,
            'snapshot' => null,
            'backups' => [],
            'metrics' => null,
            'actions' => [],
            'templates' => [],
            'domains' => collect(),
            'firewall' => null,
            'sshKeys' => null,
            'malware' => null,
            'network' => $this->networkFrom($vm, $server),
            'panel' => VpsLabels::panel($server->template_name, $server->ipv4),
            'payments' => $server->payments()->get(),
            'canRenew' => $server->canRenew(),
            'renewPrice' => $server->plan?->renewPriceThb((string) $server->period) ?? 0.0,
            'chargeDays' => VpsSettings::chargeDays(),
        ];

        if ($manageable) {
            match ($tab) {
                'overview' => $data = array_merge($data, [
                    'metrics' => $this->metricsFor($server, $range),
                    'actions' => array_slice($this->actionsFor($server), 0, 5),
                ]),
                'network' => $data = array_merge($data, [
                    'firewall' => $this->firewallFor($server, $vm),
                    'sshKeys' => $this->sshKeysFor($server),
                    'malware' => $this->malwareFor($server),
                    'domains' => DomainRegistration::where('user_id', $server->user_id)->registrations()->active()->orderBy('domain')->get(['id', 'domain']),
                ]),
                'backups' => $data = array_merge($data, [
                    'snapshot' => $this->snapshotFor($server),
                    'backups' => $this->backupsFor($server),
                ]),
                'system' => $data = array_merge($data, [
                    'templates' => $this->catalog->groupedTemplates(),
                ]),
                'activity' => $data = array_merge($data, [
                    'actions' => $this->actionsFor($server),
                ]),
                default => null,
            };
        }

        return view('customer.vps.show', $data);
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

        $action = $this->text($request, 'action');

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
        $action = $this->text($request, 'action');
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

    // ======================================================= network & security

    /**
     * The server's own firewall: switch it on or off, apply a ready-made rule
     * set, or save the customer's rules.
     *
     * One firewall per rental, created on first use and remembered on the row
     * — the only firewall this customer's requests ever reach. Everything no
     * rule accepts is dropped, so a rule set without SSH is refused unless the
     * customer confirms they mean to lock SSH out.
     */
    public function firewall(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);
        $action = $this->text($request, 'action');

        if (! in_array($action, ['enable', 'disable', 'save', 'preset'], true)) {
            return $this->backTo($server, 'network')->with('error', 'คำสั่งไม่ถูกต้อง');
        }

        $rules = null;

        if ($action === 'save') {
            $request->validate([
                'rules' => ['nullable', 'array', 'max:' . (VpsFirewall::MAX_RULES * 2)],
                'rules.*' => ['array'],
            ], [
                'rules.max' => 'กฎได้สูงสุด ' . VpsFirewall::MAX_RULES . ' ข้อ',
            ]);

            [$rules, $errors] = VpsFirewall::normalise((array) $request->input('rules', []));

            if ($errors !== []) {
                return $this->backTo($server, 'network')->withInput()->with('error', implode(' · ', $errors));
            }
        } elseif ($action === 'preset') {
            $preset = $this->text($request, 'preset');

            if (! array_key_exists($preset, VpsFirewall::PRESETS)) {
                return $this->backTo($server, 'network')->with('error', 'ไม่พบชุดกฎที่เลือก');
            }

            $rules = VpsFirewall::preset($preset);
        }

        if ($rules !== null && ! VpsFirewall::allowsSsh($rules) && ! $request->boolean('confirm_no_ssh')) {
            return $this->backTo($server, 'network')->withInput()->with('error',
                'กฎชุดนี้ไม่เปิด SSH (พอร์ต 22) — ถ้าเปิดไฟร์วอลล์ไว้ คุณจะเข้าเครื่องด้วย SSH ไม่ได้ ติ๊กยืนยันก่อนหากตั้งใจปิด SSH จริง');
        }

        // Up to four upstream calls in a row, each allowed 30 seconds: the lock
        // has to outlive all of them or a second click slips in behind it.
        $lock = Cache::lock('vps:firewall:' . $server->id, 150);

        if (! $lock->get()) {
            return $this->backTo($server, 'network')->with('error', 'กำลังบันทึกไฟร์วอลล์ของเครื่องนี้อยู่ กรุณารอสักครู่แล้วลองใหม่');
        }

        try {
            // Read under the lock: the request that held it may have just
            // created this server's firewall.
            $server->refresh();

            $message = match ($action) {
                'disable' => $this->firewallOff($server),
                'enable' => $this->firewallOn($server, $request->boolean('confirm_no_ssh')),
                default => $this->firewallSave($server, $rules ?? []),
            };
        } finally {
            $lock->release();
        }

        Cache::forget('vps.firewall.' . $server->id);
        $this->forgetLive($server);

        if ($message[0] === 'error') {
            return $this->backTo($server, 'network')->withInput()->with('error', $message[1]);
        }

        // Upstream applies an (de)activation a moment later than it answers;
        // until the machine reports it, the page shows what was asked for.
        if (in_array($action, ['enable', 'disable'], true)) {
            Cache::put('vps.firewall.pending.' . $server->id, $action === 'enable' ? 'on' : 'off', 90);
        }

        $this->audit($request, $server, 'firewall:' . $action);

        return $this->backTo($server, 'network')->with('success', $message[1]);
    }

    /**
     * Put an SSH public key on the server. Keys cannot be taken off through
     * the API — removing one is done on the server, in authorized_keys.
     */
    public function sshKey(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'key_name' => ['nullable', 'string', 'max:40', 'regex:/^[\pL\pN _.@-]+$/u'],
            'public_key' => ['required', 'string', 'max:4096', 'regex:/^(ssh-(rsa|ed25519|dss)|ecdsa-sha2-nistp(256|384|521)|sk-ssh-ed25519@openssh\.com|sk-ecdsa-sha2-nistp256@openssh\.com) [A-Za-z0-9+\/=]+( [^\r\n]{0,200})?$/'],
        ], [
            'public_key.required' => 'วาง SSH public key ก่อน',
            'public_key.regex' => 'SSH public key ไม่ถูกต้อง (ต้องขึ้นต้นด้วย ssh-ed25519, ssh-rsa หรือ ecdsa-… และอยู่ในบรรทัดเดียว)',
            'key_name.regex' => 'ชื่อคีย์ใช้ได้เฉพาะตัวอักษร ตัวเลข เว้นวรรค และ . _ - @',
        ]);

        $key = trim($validated['public_key']);
        $blob = explode(' ', $key)[1] ?? '';

        foreach ($this->sshKeysFor($server) ?? [] as $existing) {
            if (($existing['blob'] ?? null) === $blob) {
                return $this->backTo($server, 'network')->with('success', 'คีย์นี้อยู่บนเครื่องแล้ว ใช้เข้าเครื่องได้เลย');
            }
        }

        // Keys live on the supplier account next to every other customer's;
        // the prefix keeps ours recognisable there, and is stripped on display.
        $name = 'vps' . $server->id . '-' . ($validated['key_name'] ?: 'key-' . now()->format('Ymd-His'));
        $created = $this->api->createPublicKey($name, $key);

        if ($created === null || $created['status_code'] >= 300 || empty($created['body']['id'])) {
            return $this->backTo($server, 'network')->with('error', VpsProvisioningService::explain($created, 'เพิ่ม SSH key ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }

        $attached = $this->api->attachPublicKeys((int) $server->remote_vm_id, [(int) $created['body']['id']]);

        if ($attached === null || $attached['status_code'] >= 300) {
            return $this->backTo($server, 'network')->with('error', VpsProvisioningService::explain($attached, 'ติดตั้ง SSH key บนเครื่องไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }

        Cache::forget('vps.keys.' . $server->id);
        $this->audit($request, $server, 'ssh-key');

        return $this->backTo($server, 'network')->with('success', 'เพิ่ม SSH key แล้ว ใช้เข้าเครื่องได้ภายใน 1–2 นาที');
    }

    /** Reverse DNS (PTR) for the server's IPv4 or IPv6 address — blank removes it. */
    public function reverseDns(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'ip' => ['required', 'in:v4,v6'],
            'ptr' => ['nullable', 'string', 'max:253', 'regex:' . self::HOSTNAME_REGEX],
        ], [
            'ptr.regex' => 'Reverse DNS ต้องเป็นชื่อโดเมน เช่น mail.example.com',
        ]);

        $network = $this->networkFrom($this->liveVm($server, fresh: true), $server);
        $ipId = $network[$validated['ip'] === 'v6' ? 'ipv6' : 'ipv4']['id'] ?? null;

        if (! $ipId) {
            return $this->backTo($server, 'network')->with('error', 'ไม่พบ IP นี้บนเครื่อง กรุณารีเฟรชหน้าแล้วลองใหม่');
        }

        $ptr = strtolower(trim((string) ($validated['ptr'] ?? '')));
        $result = $ptr === ''
            ? $this->api->deleteVirtualMachinePtr((int) $server->remote_vm_id, (int) $ipId)
            : $this->api->setVirtualMachinePtr((int) $server->remote_vm_id, (int) $ipId, $ptr);

        if ($result === null || $result['status_code'] >= 300) {
            return $this->backTo($server, 'network')->with('error', VpsProvisioningService::explain($result, 'ตั้ง Reverse DNS ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'));
        }

        $this->forgetLive($server);
        $this->audit($request, $server, 'ptr:' . $validated['ip']);

        return $this->backTo($server, 'network')->with('success', $ptr === ''
            ? 'ลบ Reverse DNS แล้ว'
            : 'ตั้ง Reverse DNS เป็น ' . $ptr . ' แล้ว มีผลภายในไม่กี่ชั่วโมง');
    }

    /** The resolvers the server itself uses to look names up. */
    public function resolvers(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'ns1' => ['required', 'ip'],
            'ns2' => ['nullable', 'ip', 'different:ns1'],
        ], [
            'ns1.required' => 'ใส่ DNS resolver ตัวแรก เช่น 1.1.1.1',
            'ns1.ip' => 'DNS resolver ต้องเป็น IP เช่น 1.1.1.1',
            'ns2.ip' => 'DNS resolver ตัวที่สองต้องเป็น IP เช่น 8.8.8.8',
            'ns2.different' => 'ตัวที่สองต้องไม่ซ้ำกับตัวแรก',
        ]);

        $result = $this->api->setVirtualMachineNameservers((int) $server->remote_vm_id, $validated['ns1'], $validated['ns2'] ?? null);

        if ($result === null || $result['status_code'] >= 300) {
            return $this->backTo($server, 'network')->with('error', VpsProvisioningService::explain($result));
        }

        $this->forgetLive($server);
        $this->audit($request, $server, 'resolvers');

        return $this->backTo($server, 'network')->with('success', 'ตั้ง DNS resolver ของเครื่องแล้ว');
    }

    /** Install or remove the free malware scanner. */
    public function malware(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);
        $action = $this->text($request, 'action');
        $vmId = (int) $server->remote_vm_id;

        $result = match ($action) {
            'install' => $this->api->installMalwareScanner($vmId),
            'uninstall' => $this->api->uninstallMalwareScanner($vmId),
            default => false,
        };

        if ($result === false) {
            return $this->backTo($server, 'network')->with('error', 'คำสั่งไม่ถูกต้อง');
        }

        if ($result === null || $result['status_code'] >= 300) {
            return $this->backTo($server, 'network')->with('error', VpsProvisioningService::explain($result));
        }

        Cache::forget('vps.malware.' . $server->id);
        $this->audit($request, $server, 'malware:' . $action);

        return $this->backTo($server, 'network')->with('success', $action === 'install'
            ? 'กำลังติดตั้งตัวสแกนมัลแวร์ ผลสแกนแรกจะขึ้นภายในไม่กี่ชั่วโมง'
            : 'ถอนตัวสแกนมัลแวร์ออกแล้ว');
    }

    // ================================================================= system

    /**
     * Rescue mode: the server boots a rescue system with its own disk mounted
     * at /mnt, reachable by SSH with a temporary root password — for a server
     * that no longer boots, or whose password nobody remembers.
     */
    public function recovery(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);
        $action = $this->text($request, 'action');

        if ($action === 'stop') {
            $result = $this->api->stopVirtualMachineRecovery((int) $server->remote_vm_id);

            if ($result === null || $result['status_code'] >= 300) {
                return $this->backTo($server, 'system')->with('error', VpsProvisioningService::explain($result));
            }

            $this->forgetLive($server);
            $this->audit($request, $server, 'recovery:stop');

            return $this->backTo($server, 'system')->with('success', 'สั่งออกจากโหมดกู้ระบบแล้ว เครื่องจะบูตระบบปกติภายในไม่กี่นาที');
        }

        if ($action !== 'start') {
            return $this->backTo($server, 'system')->with('error', 'คำสั่งไม่ถูกต้อง');
        }

        $validated = $request->validate([
            'recovery_password' => ['required', 'string', 'max:128', 'confirmed', Password::min(12)->mixedCase()->numbers()->uncompromised()],
        ], $this->passwordMessages('recovery_password'));

        $result = $this->api->startVirtualMachineRecovery((int) $server->remote_vm_id, $validated['recovery_password']);

        if ($result === null || $result['status_code'] >= 300) {
            return $this->backTo($server, 'system')->with('error', VpsProvisioningService::explain($result));
        }

        $this->forgetLive($server);
        $this->audit($request, $server, 'recovery:start');

        return $this->backTo($server, 'system')->with('success', 'กำลังเข้าโหมดกู้ระบบ — อีกสักครู่ SSH เข้า root@' . ($server->ipv4 ?: 'IP ของเครื่อง') . ' ด้วยรหัสชั่วคราวที่เพิ่งตั้ง ดิสก์เดิมอยู่ที่ /mnt');
    }

    /** Password of the control panel that a panel template installed. */
    public function panelPassword(Request $request, int $id): RedirectResponse
    {
        $server = $this->manageable($request, $id);

        $validated = $request->validate([
            'panel_password' => ['required', 'string', 'max:128', 'confirmed', Password::min(12)->mixedCase()->numbers()->uncompromised()],
        ], $this->passwordMessages('panel_password'));

        $result = $this->api->setVirtualMachinePanelPassword((int) $server->remote_vm_id, $validated['panel_password']);

        if ($result === null || $result['status_code'] >= 300) {
            return $this->backTo($server, 'system')->with('error', VpsProvisioningService::explain($result));
        }

        $this->audit($request, $server, 'panel-password');

        return $this->backTo($server, 'system')->with('success', 'ตั้งรหัสผ่านแผงควบคุมใหม่แล้ว ใช้ได้ภายใน 1–2 นาที');
    }

    // ================================================================ helpers

    /** A hostname: labels of a-z 0-9 and hyphens, at least one dot. */
    protected const HOSTNAME_REGEX = '/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i';

    /** A form field as text — an array smuggled in under the same name counts as empty, not as a 500. */
    protected function text(Request $request, string $key): string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : '';
    }

    /** Back to the server's page, on the tab the form was on. */
    protected function backTo(VpsInstance $server, string $tab): RedirectResponse
    {
        return redirect()->route('customer.vps.show', ['id' => $server->id, 'tab' => $tab]);
    }

    /**
     * @return array{0:'success'|'error',1:string}
     */
    protected function firewallOn(VpsInstance $server, bool $confirmedNoSsh): array
    {
        $firewallId = $this->ensureFirewall($server);

        if (! $firewallId) {
            return ['error', 'สร้างไฟร์วอลล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'];
        }

        $current = $this->api->getFirewall($firewallId);

        if (! is_array($current) && $this->api->lastWasNotFound()) {
            // Deleted upstream (the account is shared with the owner, and a
            // firewall named xman-vps-N looks like a leftover): start again.
            $server->update(['remote_firewall_id' => null]);
            $firewallId = $this->ensureFirewall($server);
            $current = $firewallId ? ['rules' => []] : null;
        }

        if (! $firewallId || ! is_array($current)) {
            return ['error', 'อ่านกฎไฟร์วอลล์ไม่ได้ในขณะนี้ จึงยังไม่เปิด — กรุณาลองใหม่อีกครั้ง'];
        }

        $rules = (array) ($current['rules'] ?? []);

        // An empty firewall drops everything — including the owner's SSH and
        // whatever the installed template listens on.
        if ($rules === []) {
            $seed = VpsFirewall::seedFor($server->template_name);
            $seeded = $this->api->replaceFirewallRules($firewallId, $seed, sync: true);

            if ($seeded === null || $seeded['status_code'] >= 300) {
                return ['error', 'ตั้งกฎเริ่มต้นไม่สำเร็จ จึงยังไม่เปิดไฟร์วอลล์ — กรุณาลองใหม่อีกครั้ง'];
            }

            $rules = $seed;
        }

        if (! VpsFirewall::allowsSsh($rules) && ! $confirmedNoSsh) {
            return ['error', 'กฎปัจจุบันไม่เปิด SSH (พอร์ต 22) — เพิ่มกฎ SSH ก่อน หรือติ๊กยืนยันหากตั้งใจปิด SSH จริง'];
        }

        $result = $this->api->activateFirewall($firewallId, (int) $server->remote_vm_id);

        if ($result === null || $result['status_code'] >= 300) {
            return ['error', VpsProvisioningService::explain($result, 'เปิดไฟร์วอลล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')];
        }

        return ['success', 'เปิดไฟร์วอลล์แล้ว — เฉพาะพอร์ตที่อยู่ในกฎเท่านั้นที่เข้าถึงเครื่องได้'];
    }

    /**
     * @return array{0:'success'|'error',1:string}
     */
    protected function firewallOff(VpsInstance $server): array
    {
        if (! $server->remote_firewall_id) {
            return ['error', 'เครื่องนี้ยังไม่มีไฟร์วอลล์'];
        }

        $result = $this->api->deactivateFirewall((int) $server->remote_firewall_id, (int) $server->remote_vm_id);

        // Gone upstream: nothing is active any more, and the row must stop
        // pointing at it.
        if ($result !== null && (int) $result['status_code'] === 404) {
            $server->update(['remote_firewall_id' => null]);

            return ['success', 'ปิดไฟร์วอลล์แล้ว'];
        }

        if ($result === null || $result['status_code'] >= 300) {
            return ['error', VpsProvisioningService::explain($result, 'ปิดไฟร์วอลล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')];
        }

        return ['success', 'ปิดไฟร์วอลล์แล้ว — ทุกพอร์ตเข้าถึงเครื่องได้ตามการตั้งค่าในเครื่อง'];
    }

    /**
     * @param  array<int,array{protocol:string,port:string,source:string,source_detail:string}>  $rules
     * @return array{0:'success'|'error',1:string}
     */
    protected function firewallSave(VpsInstance $server, array $rules): array
    {
        $firewallId = $this->ensureFirewall($server);

        if (! $firewallId) {
            return ['error', 'สร้างไฟร์วอลล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'];
        }

        $result = $this->api->replaceFirewallRules($firewallId, $rules, sync: true);

        // The recorded firewall was deleted upstream: make a new one once.
        if ($result !== null && (int) $result['status_code'] === 404) {
            $server->update(['remote_firewall_id' => null]);
            $firewallId = $this->ensureFirewall($server);
            $result = $firewallId ? $this->api->replaceFirewallRules($firewallId, $rules, sync: true) : null;
        }

        if ($result === null || $result['status_code'] >= 300) {
            return ['error', VpsProvisioningService::explain($result, 'บันทึกกฎไฟร์วอลล์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')];
        }

        $active = (int) ($this->liveVm($server)['firewall_group_id'] ?? 0) === (int) $firewallId;

        return ['success', 'บันทึกกฎไฟร์วอลล์แล้ว (' . count($rules) . ' ข้อ) — ' . ($active
            ? 'กำลังนำไปใช้กับเครื่อง ใช้เวลาไม่เกิน 1–2 นาที'
            : 'จะมีผลเมื่อเปิดไฟร์วอลล์')];
    }

    /**
     * This rental's firewall, created on first use. Null when upstream would
     * not create one.
     *
     * Looked up by name before creating: a create that timed out may have
     * gone through, and a second click must adopt that firewall rather than
     * leave it orphaned beside a new one.
     */
    protected function ensureFirewall(VpsInstance $server): ?int
    {
        if ($server->remote_firewall_id) {
            return (int) $server->remote_firewall_id;
        }

        $name = 'xman-vps-' . $server->id;

        for ($page = 1; $page <= 10; $page++) {
            $rows = $this->api->listFirewalls($page);

            if (! is_array($rows) || $rows === []) {
                break;
            }

            foreach ($rows as $row) {
                if (is_array($row) && ($row['name'] ?? null) === $name && (int) ($row['id'] ?? 0) > 0) {
                    $server->update(['remote_firewall_id' => (int) $row['id']]);

                    return (int) $row['id'];
                }
            }
        }

        $created = $this->api->createFirewall($name);
        $firewallId = (int) ($created['body']['id'] ?? 0);

        if ($created === null || $created['status_code'] >= 300 || $firewallId <= 0) {
            return null;
        }

        $server->update(['remote_firewall_id' => $firewallId]);

        return $firewallId;
    }

    /**
     * @param  array<string,mixed>|null  $vm
     * @return array{exists:bool,loaded:bool,active:bool,foreign:bool,synced:bool,applying:bool,rows:array<int,array{protocol:string,port:string,source_detail:string}>,saved_rows:array<int,array{protocol:string,port:string,source_detail:string}>}
     */
    protected function firewallFor(VpsInstance $server, ?array $vm): array
    {
        $activeId = (int) ($vm['firewall_group_id'] ?? 0);
        $ours = (int) $server->remote_firewall_id;

        $state = [
            'exists' => $ours > 0,
            'loaded' => false,
            'active' => $ours > 0 && $activeId === $ours,
            // Something else is active on the machine — not ours to show or edit.
            'foreign' => $activeId > 0 && $activeId !== $ours,
            'synced' => true,
            'applying' => false,
            'rows' => [],
            // What switching on would open, for the confirmation.
            'saved_rows' => [],
        ];

        // Just switched on or off: the machine reports it a moment later.
        $pending = Cache::get('vps.firewall.pending.' . $server->id);

        if ($ours > 0 && ($pending === 'on' && ! $state['active'] || $pending === 'off' && $state['active'])) {
            $state['active'] = $pending === 'on';
            $state['applying'] = true;
        }

        if ($ours > 0) {
            $raw = Cache::get('vps.firewall.' . $server->id);

            if (! is_array($raw)) {
                $raw = $this->api->getFirewall($ours);

                if (! is_array($raw) && $this->api->lastWasNotFound()) {
                    // Deleted upstream: show a fresh start, not a page stuck on
                    // "couldn't read" until somebody edits the database.
                    $server->update(['remote_firewall_id' => null]);
                    $ours = 0;
                    $state['exists'] = $state['active'] = $state['applying'] = false;
                } else {
                    Cache::put('vps.firewall.' . $server->id, $raw ?? ['__failed' => true], is_array($raw) ? 60 : 20);
                }
            }

            if ($ours > 0 && is_array($raw) && ! isset($raw['__failed'])) {
                $state['loaded'] = true;
                $state['synced'] = (bool) ($raw['is_synced'] ?? true);
                $state['rows'] = VpsFirewall::rows((array) ($raw['rules'] ?? []));
                $state['saved_rows'] = $state['rows'];
            }
        }

        if ($ours <= 0) {
            $seed = VpsFirewall::rows(VpsFirewall::seedFor($server->template_name));
            $state['loaded'] = true;
            $state['rows'] = $seed;
            $state['saved_rows'] = $seed;
        }

        return $state;
    }

    /**
     * @return array<int,array{name:string,type:string,fingerprint:string,comment:string,blob:string}>|null null when upstream would not say
     */
    protected function sshKeysFor(VpsInstance $server): ?array
    {
        // Short-lived: a key added a moment ago is attached asynchronously, and
        // a list cached for minutes would say "no keys" right after "added".
        $raw = Cache::get('vps.keys.' . $server->id);

        if (! is_array($raw)) {
            $raw = $this->api->getVirtualMachinePublicKeys((int) $server->remote_vm_id) ?? ['__failed' => true];
            Cache::put('vps.keys.' . $server->id, $raw, isset($raw['__failed']) ? 15 : 60);
        }

        if (isset($raw['__failed'])) {
            return null;
        }

        $keys = [];

        foreach ($raw as $row) {
            if (! is_array($row) || empty($row['key'])) {
                continue;
            }

            $parts = preg_split('/\s+/', trim((string) $row['key']), 3);
            $blob = $parts[1] ?? '';
            $binary = base64_decode($blob, true);

            $keys[] = [
                'name' => (string) preg_replace('/^vps\d+-/', '', (string) ($row['name'] ?? '')),
                'type' => (string) ($parts[0] ?? ''),
                'fingerprint' => $binary !== false && $binary !== '' ? 'SHA256:' . rtrim(base64_encode(hash('sha256', $binary, true)), '=') : '',
                'comment' => (string) ($parts[2] ?? ''),
                'blob' => $blob,
            ];
        }

        return $keys;
    }

    /**
     * @return array{installed:bool,scanned:int,malicious:int,compromised:int,started_at:?string,ended_at:?string}|null null when not installed, or unknown
     */
    protected function malwareFor(VpsInstance $server): ?array
    {
        $raw = Cache::remember('vps.malware.' . $server->id, 600, fn () => $this->api->getMalwareScan((int) $server->remote_vm_id) ?? []);

        if (! is_array($raw) || ! array_key_exists('scanned_files', $raw)) {
            return null;
        }

        return [
            'installed' => true,
            'scanned' => (int) ($raw['scanned_files'] ?? 0),
            'malicious' => (int) ($raw['malicious'] ?? 0),
            'compromised' => (int) ($raw['compromised'] ?? 0),
            'started_at' => $raw['scan_started_at'] ?? null,
            'ended_at' => $raw['scan_ended_at'] ?? null,
        ];
    }

    /**
     * The machine's recent history, newest first.
     *
     * @return array<int,array{label:array{th:string,en:string},state:array{th:string,en:string,classes:string},created_at:?string}>
     */
    protected function actionsFor(VpsInstance $server): array
    {
        $raw = Cache::remember('vps.actions.' . $server->id, 60, fn () => $this->api->getVirtualMachineActions((int) $server->remote_vm_id) ?? []);
        $rows = [];

        foreach ((array) $raw as $row) {
            if (! is_array($row) || empty($row['name'])) {
                continue;
            }

            $rows[] = [
                'label' => VpsLabels::action((string) $row['name']),
                'state' => VpsLabels::actionState((string) ($row['state'] ?? '')),
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));

        return array_slice($rows, 0, 15);
    }

    /**
     * The addresses, their reverse DNS, and the resolvers — from the live
     * machine when we have it, from our row when we do not.
     *
     * @param  array<string,mixed>|null  $vm
     * @return array{ipv4:array{id:?int,address:?string,ptr:?string},ipv6:array{id:?int,address:?string,ptr:?string},ns1:?string,ns2:?string}
     */
    protected function networkFrom(?array $vm, VpsInstance $server): array
    {
        $address = function (string $family) use ($vm, $server): array {
            $row = is_array($vm[$family][0] ?? null) ? $vm[$family][0] : [];

            return [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'address' => $row['address'] ?? $server->{$family},
                'ptr' => isset($row['ptr']) && $row['ptr'] !== '' ? (string) $row['ptr'] : null,
            ];
        };

        return [
            'ipv4' => $address('ipv4'),
            'ipv6' => $address('ipv6'),
            'ns1' => isset($vm['ns1']) ? (string) $vm['ns1'] : null,
            'ns2' => isset($vm['ns2']) ? (string) $vm['ns2'] : null,
        ];
    }

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
        $this->liveVm($server);
    }

    /**
     * The machine as upstream sees it — state, addresses, reverse DNS,
     * resolvers, which firewall is on — stored into our row as it goes and
     * kept for 20 seconds, so a page and its poller share one call.
     *
     * @return array<string,mixed>|null
     */
    protected function liveVm(VpsInstance $server, bool $fresh = false): ?array
    {
        $key = 'vps.vm.' . $server->id;

        if (! $fresh && is_array($cached = Cache::get($key))) {
            return isset($cached['__failed']) ? null : $cached;
        }

        $vm = $this->api->getVirtualMachine((int) $server->remote_vm_id);

        if (! is_array($vm) || $vm === []) {
            // Remember the failure too: a deleted machine, or upstream having a
            // bad minute, must not turn every page view and status poll into
            // another call against the site's 90-a-minute budget.
            Cache::put($key, ['__failed' => true], 20);

            return null;
        }

        $this->provisioning->applyVmDetails($server, $vm);

        if ($server->isDirty()) {
            $server->save();
        }

        Cache::put($key, $vm, 20);

        return $vm;
    }

    protected function forgetLive(VpsInstance $server): void
    {
        Cache::forget('vps.vm.' . $server->id);
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
     * The last 24 hours (or 7 days), boiled down to what a dashboard shows:
     * the latest CPU and RAM with a chart each, disk used, traffic, uptime.
     *
     * @param  '24h'|'7d'  $range
     * @return array<string,mixed>|null
     */
    protected function metricsFor(VpsInstance $server, string $range = '24h'): ?array
    {
        $raw = Cache::remember('vps.metrics.' . $server->id . '.' . $range, 300, fn () => $this->api->getVirtualMachineMetrics(
            (int) $server->remote_vm_id,
            ($range === '7d' ? now()->subDays(7) : now()->subDay())->utc(),
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
        $ramPercent = array_map(fn ($v) => $v / $memoryBytes * 100, $ram);

        $stamps = array_keys($cpu ?: $ram);
        $from = $stamps ? (int) min($stamps) : null;
        $to = $stamps ? (int) max($stamps) : null;

        return [
            'cpu' => $cpu ? round((float) end($cpu), 1) : null,
            'cpu_chart' => $this->chart($cpu),
            'ram_percent' => $ram ? round(((float) end($ram)) / $memoryBytes * 100, 1) : null,
            'ram_used' => $ram ? round(((float) end($ram)) / 1024 / 1024 / 1024, 2) : null,
            'ram_chart' => $this->chart($ramPercent),
            'disk_used' => $disk ? round(((float) end($disk)) / 1024 / 1024 / 1024, 1) : null,
            'disk_percent' => $disk ? round(((float) end($disk)) / $diskBytes * 100, 1) : null,
            'traffic_out_gb' => round(array_sum($out) / 1024 / 1024 / 1024, 2),
            'traffic_in_gb' => round(array_sum($in) / 1024 / 1024 / 1024, 2),
            'uptime_hours' => $uptime ? round(((float) end($uptime)) / 1000 / 3600, 1) : null,
            'from' => $from ? Carbon::createFromTimestamp($from)->timezone('Asia/Bangkok') : null,
            'to' => $to ? Carbon::createFromTimestamp($to)->timezone('Asia/Bangkok') : null,
        ];
    }

    /**
     * An area chart of a 0–100 series in a 600×160 box, averaged into at most
     * 96 buckets — drawn on the server so the page needs no chart library.
     *
     * @param  array<int|string,float>  $values
     * @return array{line:string,area:string,avg:float,peak:float}|null
     */
    protected function chart(array $values, float $max = 100): ?array
    {
        $values = array_values(array_map(fn ($v) => min($max, max(0.0, (float) $v)), $values));
        $n = count($values);

        if ($n < 2) {
            return null;
        }

        $buckets = min(96, $n);
        $points = [];

        for ($b = 0; $b < $buckets; $b++) {
            $start = (int) floor($b * $n / $buckets);
            $end = max($start + 1, (int) floor(($b + 1) * $n / $buckets));
            $slice = array_slice($values, $start, $end - $start);
            $points[] = array_sum($slice) / count($slice);
        }

        $last = count($points) - 1;
        $coords = [];

        foreach ($points as $i => $v) {
            $coords[] = round($i / max(1, $last) * 600, 1) . ',' . round(160 - ($v / $max) * 150 - 5, 1);
        }

        return [
            'line' => implode(' ', $coords),
            'area' => '0,160 ' . implode(' ', $coords) . ' 600,160',
            'avg' => round(array_sum($values) / $n, 1),
            'peak' => round(max($values), 1),
        ];
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
    protected function passwordMessages(string $field = 'root_password'): array
    {
        return [
            $field . '.required' => $field === 'root_password' ? 'กรุณาตั้งรหัสผ่าน root' : 'กรุณาตั้งรหัสผ่าน',
            $field . '.min' => 'รหัสผ่านต้องยาวอย่างน้อย 12 ตัวอักษร',
            $field . '.mixed' => 'รหัสผ่านต้องมีทั้งตัวพิมพ์ใหญ่และตัวพิมพ์เล็ก',
            $field . '.numbers' => 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว',
            $field . '.uncompromised' => 'รหัสผ่านนี้เคยหลุดสู่สาธารณะ กรุณาตั้งรหัสใหม่',
            $field . '.confirmed' => 'ยืนยันรหัสผ่านไม่ตรงกัน',
        ];
    }
}
