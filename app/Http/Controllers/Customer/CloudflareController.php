<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CloudflareConnection;
use App\Models\DomainRegistration;
use App\Models\VpsInstance;
use App\Services\CloudflareApiService;
use App\Services\HostingerApiService;
use App\Support\CloudflareTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * The customer's own Cloudflare, driven from the domain page.
 *
 * Connecting is a token the customer made on Cloudflare (our link pre-ticks
 * the permissions). With it, "move to Cloudflare" is one click with nothing
 * to copy: the site is added to their account, our current records are
 * copied across, and the domain is pointed at the pair Cloudflare assigned.
 * Quick-setup templates then lay down a ready configuration.
 *
 * Everything acts on the customer's account with the customer's token. A
 * domain is only ever touched when it is theirs here, and only the zone of
 * exactly that name.
 */
class CloudflareController extends Controller
{
    /** Types copied from our zone; the rest are listed back for the customer to add by hand. */
    private const COPYABLE = ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS'];

    public function __construct(protected HostingerApiService $registrar) {}

    public function connect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cloudflare_token' => ['required', 'string', 'min:20', 'max:200', 'regex:/^[A-Za-z0-9_\-]+$/'],
        ], [
            'cloudflare_token.required' => 'วาง API token จาก Cloudflare ก่อน',
            'cloudflare_token.min' => 'token สั้นเกินไป — คัดลอกมาทั้งหมดจากหน้าที่ Cloudflare แสดงหลังสร้าง',
            'cloudflare_token.regex' => 'token มีอักขระที่ไม่ควรมี — คัดลอกมาเฉพาะตัว token ไม่มีช่องว่าง',
        ]);

        $token = $validated['cloudflare_token'];
        $api = new CloudflareApiService($token);

        if (! $api->verifyToken()) {
            return back()->with('cf_open', true)->with('error', $api->lastError() ?? 'token นี้ใช้ไม่ได้ กรุณาสร้างใหม่จากลิงก์ในหน้านี้');
        }

        $accounts = $api->accounts();

        if (! $accounts) {
            return back()->with('cf_open', true)->with('error', $accounts === null
                ? ($api->lastError() ?? 'อ่านบัญชี Cloudflare ไม่สำเร็จ')
                : 'token นี้มองไม่เห็นบัญชี Cloudflare ใดเลย — ตอนสร้าง token ให้เลือก All accounts');
        }

        $account = $accounts[0];

        CloudflareConnection::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'api_token' => $token,
                'token_hint' => substr($token, -4),
                'account_id' => $account['id'],
                'account_name' => mb_substr($account['name'], 0, 190),
                'verified_at' => now(),
            ],
        );

        $this->forgetZones($request->user()->id);

        Log::info('[Cloudflare] connected', ['user_id' => $request->user()->id, 'accounts' => count($accounts)]);

        return back()->with('cf_open', true)->with('success', 'เชื่อมกับ Cloudflare แล้ว (บัญชี ' . $account['name'] . ')'
            . (count($accounts) > 1 ? ' — token นี้เห็น ' . count($accounts) . ' บัญชี เราใช้บัญชีแรก' : ''));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        CloudflareConnection::where('user_id', $request->user()->id)->delete();
        $this->forgetZones($request->user()->id);

        return back()->with('success', 'ยกเลิกการเชื่อม Cloudflare แล้ว และลบ token ออกจากระบบเราแล้ว '
            . '— เพื่อความปลอดภัย ลบ token นั้นที่ Cloudflare ด้วย (My Profile → API Tokens)');
    }

    /**
     * One click, nothing to copy: add the site to the customer's Cloudflare,
     * bring our records across, and point the domain at the assigned pair.
     */
    public function move(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);
        $connection = $this->connection($request);

        if (! $connection) {
            return back()->with('cf_open', true)->with('error', 'เชื่อมกับ Cloudflare ก่อน');
        }

        if (! $domain->isUsable()) {
            return back()->with('cf_open', true)->with('error', 'โดเมนนี้ยังใช้งานไม่ได้');
        }

        $cf = $connection->api();
        $zone = $cf->findZone($domain->domain);

        if (! $zone && $cf->lastError()) {
            return back()->with('cf_open', true)->with('error', $cf->lastError());
        }

        $created = false;

        if (! $zone) {
            $zone = $cf->createZone($domain->domain, (string) $connection->account_id);

            if (! $zone) {
                return back()->with('cf_open', true)->with('error', 'เพิ่มเว็บใน Cloudflare ไม่สำเร็จ — ' . ($cf->lastError() ?? 'กรุณาลองใหม่'));
            }

            $created = true;
        }

        $assigned = DomainRegistration::normaliseNameservers($zone['name_servers'] ?? []);

        if (count($assigned) < 2 || collect($assigned)->contains(fn ($ns) => ! str_ends_with($ns, '.ns.cloudflare.com'))) {
            return back()->with('cf_open', true)->with('error', 'Cloudflare ไม่ได้ให้ nameserver มาครบ ยังไม่ได้เปลี่ยนอะไร กรุณาลองใหม่');
        }

        // Bring our records across before the switch, so nothing goes dark —
        // but only into an empty zone: one with records is the customer's own
        // setup, and copying on top would double it.
        $copied = 0;
        $skipped = [];

        if ($domain->usesOwnDns()) {
            $current = $cf->dnsRecords((string) $zone['id']);

            if ($current === null) {
                return back()->with('cf_open', true)->with('error', 'อ่าน DNS ที่ Cloudflare ไม่สำเร็จ ยังไม่ได้เปลี่ยน nameserver — ' . $cf->lastError());
            }

            $theirs = array_filter($current, fn ($r) => ! in_array(strtoupper((string) ($r['type'] ?? '')), ['NS', 'SOA'], true));

            if ($theirs === []) {
                $ours = $this->registrar->getDnsRecords($domain->domain);

                if ($ours === null) {
                    return back()->with('cf_open', true)->with('error', 'อ่าน DNS ปัจจุบันของโดเมนไม่สำเร็จ ยังไม่ได้เปลี่ยนอะไร กรุณาลองใหม่');
                }

                [$records, $skipped] = $this->translate($ours, $domain->domain);

                foreach ($records as $record) {
                    if ($cf->createDnsRecord((string) $zone['id'], $record)) {
                        $copied++;
                    } else {
                        $skipped[] = $record['type'] . ' ' . $record['name'];
                    }
                }
            }
        }

        if (DomainRegistration::normaliseNameservers($domain->nameservers) !== $assigned) {
            if (! $this->registrar->updateNameservers($domain->domain, $assigned)) {
                return back()->with('cf_open', true)->with('error', 'เพิ่มเว็บใน Cloudflare แล้ว แต่เปลี่ยน nameserver ไม่สำเร็จ กรุณากดอีกครั้ง');
            }

            $domain->update(['nameservers' => $assigned]);
        }

        $cf->activationCheck((string) $zone['id']);
        $this->forgetZones($request->user()->id);

        Log::info('[Cloudflare] domain moved', [
            'registration_id' => $domain->id,
            'user_id' => $request->user()->id,
            'zone_created' => $created,
            'copied' => $copied,
            'skipped' => count($skipped),
        ]);

        $message = 'ย้าย ' . $domain->domain . ' ไป Cloudflare แล้ว (' . implode(', ', $assigned) . ')';
        $message .= $copied > 0 ? ' · คัดลอกเรคคอร์ด DNS ไป ' . $copied . ' รายการ' : '';
        $message .= $skipped !== [] ? ' · ต้องเพิ่มเองที่ Cloudflare: ' . implode(', ', array_slice($skipped, 0, 8)) : '';
        $message .= ' — Cloudflare จะเปิดใช้งานภายในไม่กี่นาทีถึง 24 ชั่วโมง';

        return back()->with('success', $message);
    }

    public function applyTemplate(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);
        $connection = $this->connection($request);

        if (! $connection) {
            return back()->with('error', 'เชื่อมกับ Cloudflare ก่อน');
        }

        $catalogue = CloudflareTemplates::all();
        $key = (string) $request->input('template');

        if (! isset($catalogue[$key])) {
            return back()->with('error', 'ไม่รู้จักเทมเพลตนี้');
        }

        $request->validate([
            'vps_id' => ['nullable', 'integer'],
            'ssl' => ['nullable', 'in:full,flexible'],
            'ipv4' => ['nullable', 'string', 'max:64'],
            'ipv6' => ['nullable', 'string', 'max:64'],
            'site' => ['nullable', 'string', 'max:80'],
            'user' => ['nullable', 'string', 'max:60'],
            'verification' => ['nullable', 'string', 'max:300'],
        ]);

        // Only the customer's own machines can be named.
        $vpsIps = VpsInstance::where('user_id', $request->user()->id)
            ->whereNotNull('ipv4')
            ->get(['id', 'ipv4', 'ipv6'])
            ->mapWithKeys(fn ($v) => [$v->id => ['ipv4' => $v->ipv4, 'ipv6' => $v->ipv6]])
            ->all();

        try {
            $built = CloudflareTemplates::build($key, $request->all(), $domain->domain, $vpsIps);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())
                ->with('error', collect($e->errors())->flatten()->first());
        }

        $cf = $connection->api();
        $zone = $cf->findZone($domain->domain);

        if (! $zone) {
            return back()->withInput()->with('error', $cf->lastError()
                ?? 'ยังไม่มี ' . $domain->domain . ' ใน Cloudflare ของคุณ — กด "ย้ายไป Cloudflare อัตโนมัติ" ก่อน');
        }

        $zoneId = (string) $zone['id'];
        $failed = [];
        $removed = 0;
        $added = 0;

        if ($built['records'] !== []) {
            $existing = $cf->dnsRecords($zoneId);

            if ($existing === null) {
                return back()->withInput()->with('error', 'อ่าน DNS ที่ Cloudflare ไม่สำเร็จ ยังไม่ได้เปลี่ยนอะไร — ' . $cf->lastError());
            }

            $plan = CloudflareTemplates::plan($built['records'], $existing);

            foreach ($plan['delete'] as $row) {
                if ($cf->deleteDnsRecord($zoneId, (string) $row['id'])) {
                    $removed++;
                } else {
                    $failed[] = 'ลบ ' . $row['type'] . ' ' . $row['name'];
                }
            }

            foreach ($plan['create'] as $record) {
                if ($cf->createDnsRecord($zoneId, CloudflareTemplates::payload($record))) {
                    $added++;
                } else {
                    $failed[] = 'เพิ่ม ' . $record['type'] . ' ' . $record['name'] . ' (' . $cf->lastError() . ')';
                }
            }
        }

        foreach ($built['settings'] as $setting => $value) {
            if (! $cf->setSetting($zoneId, $setting, $value)) {
                $failed[] = 'ตั้งค่า ' . $setting . ($cf->lastWasAuth() ? ' (token ไม่มีสิทธิ์ Zone Settings)' : '');
            }
        }

        Log::info('[Cloudflare] template applied', [
            'registration_id' => $domain->id,
            'user_id' => $request->user()->id,
            'template' => $key,
            'added' => $added,
            'removed' => $removed,
            'failed' => count($failed),
        ]);

        $label = $catalogue[$key]['label_th'];

        if ($failed !== []) {
            return back()->withInput()->with('error', 'ตั้งค่า "' . $label . '" ได้บางส่วน — ไม่สำเร็จ: '
                . implode(' · ', array_slice($failed, 0, 6)) . ' กรุณาตรวจที่ Cloudflare');
        }

        return back()->with('success', 'ตั้งค่า "' . $label . '" ที่ Cloudflare แล้ว'
            . ($added || $removed ? ' (เพิ่ม ' . $added . ' · แทนที่ของเดิม ' . $removed . ' รายการ)' : '')
            . ' มีผลภายในไม่กี่นาที');
    }

    /**
     * Our zone's rows as Cloudflare create-record bodies, plus the ones that
     * cannot be copied as-is (SRV, CAA and the like).
     *
     * @param  array<int,array<string,mixed>>  $zone
     * @return array{0:array<int,array<string,mixed>>,1:array<int,string>}
     */
    private function translate(array $zone, string $domain): array
    {
        $records = [];
        $skipped = [];

        foreach ($zone as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $type = strtoupper((string) ($entry['type'] ?? ''));
            $name = trim((string) ($entry['name'] ?? '@')) ?: '@';
            $fqdn = $name === '@' ? $domain : (str_ends_with($name, '.' . $domain) ? $name : $name . '.' . $domain);
            $ttl = (int) ($entry['ttl'] ?? 0);
            // Cloudflare takes 60 s and up, or 1 for "automatic".
            $ttl = $ttl < 60 ? 1 : min($ttl, 86400);

            foreach (($entry['records'] ?? []) as $row) {
                $content = trim((string) (is_array($row) ? ($row['content'] ?? '') : $row));

                if ($content === '' || (is_array($row) && ! empty($row['is_disabled']))) {
                    continue;
                }

                // The apex NS set is the zone's own; Cloudflare brings its own.
                if (! in_array($type, self::COPYABLE, true) || ($type === 'NS' && $name === '@')) {
                    if ($type !== 'NS' && $type !== 'SOA') {
                        $skipped[] = $type . ' ' . $fqdn;
                    }

                    continue;
                }

                $body = ['type' => $type, 'name' => $fqdn, 'ttl' => $ttl, 'comment' => 'copied by XMAN Studio'];

                if ($type === 'MX') {
                    if (preg_match('/^(\d+)\s+(\S+)$/', $content, $m)) {
                        $body['priority'] = (int) $m[1];
                        $content = $m[2];
                    } else {
                        $body['priority'] = 10;
                    }
                }

                if ($type === 'TXT') {
                    $content = trim($content, '"');
                } else {
                    $content = rtrim($content, '.');
                }

                if (in_array($type, ['A', 'AAAA', 'CNAME'], true)) {
                    // Copied exactly as it worked here: DNS only, no proxy.
                    $body['proxied'] = false;
                }

                $body['content'] = $content;
                $records[] = $body;
            }
        }

        return [$records, $skipped];
    }

    private function connection(Request $request): ?CloudflareConnection
    {
        return CloudflareConnection::forUser($request->user()->id);
    }

    private function findOwned(Request $request, int $id): DomainRegistration
    {
        return DomainRegistration::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    private function forgetZones(int $userId): void
    {
        DomainRegistration::where('user_id', $userId)->pluck('domain')
            ->each(fn ($name) => Cache::forget(self::zoneCacheKey($userId, $name)));
    }

    public static function zoneCacheKey(int $userId, string $domain): string
    {
        return 'cf.zone.' . $userId . '.' . strtolower($domain);
    }
}
