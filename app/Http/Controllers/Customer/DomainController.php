<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DomainRegistration;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\DomainPricing;
use App\Support\DomainReminders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * The customer's own domains: what they hold, when it expires, and full
 * control of the DNS behind it.
 *
 * "Full control, through us" is the promise on the sales page, so this is
 * where it has to be real — the customer edits records here and they take
 * effect, without a ticket and without anyone at XMAN in the loop. What they
 * never see is which registrar is underneath.
 */
class DomainController extends Controller
{
    /** Record types a customer may edit from the dashboard. */
    public const EDITABLE_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'CAA', 'NS'];

    public function __construct(
        protected HostingerApiService $api,
        protected DomainRegistrarService $registrar,
    ) {}

    public function index(Request $request): View
    {
        $domains = DomainRegistration::where('user_id', $request->user()->id)
            // A renewal is a payment row in this same table. Without this the
            // customer's list grows a second copy of the same name every year
            // they keep it.
            ->registrations()
            ->whereNotIn('status', [DomainRegistration::STATUS_REFUNDED])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('expires_at')
            ->orderByDesc('created_at')
            ->get();

        return view('customer.domains.index', [
            'domains' => $domains,
            'expiringSoon' => $domains->filter->isExpiringSoon(30),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $domain = $this->findOwned($request, $id);

        // Records are read live rather than cached: a customer who just
        // changed one and reloads must see what they changed, not a copy
        // from before it.
        $records = $domain->isUsable()
            ? $this->groupRecords($this->api->getDnsRecords($domain->domain))
            : [];

        return view('customer.domains.show', [
            'domain' => $domain,
            'records' => $records,
            'editableTypes' => self::EDITABLE_TYPES,
            'renewPrice' => $this->renewPriceFor($domain),
            'renewPriceRaw' => $domain->tldRecord?->renewPriceThb() ?? 0.0,
            'renewals' => $domain->renewals()->get(),
            'canRenew' => $domain->canRenew(),
            'dnsUnavailable' => $domain->isUsable() && $records === [],
        ]);
    }

    /**
     * Renew for another year, now, from the wallet.
     *
     * Auto-renew covers the customer who set it and forgot; this is for the one
     * who turned it off, or who wants the year bought before they travel. Same
     * service, same money path, same refund guarantee as the automatic run.
     */
    public function renew(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->canRenew()) {
            return back()->with('error', 'ตอนนี้ยังต่ออายุโดเมนนี้ไม่ได้ — อาจมีรายการต่ออายุค้างอยู่ หรือโดเมนยังไม่พร้อม');
        }

        try {
            $renewal = app(DomainRegistrarService::class)->renew($domain);
        } catch (DomainPurchaseException $e) {
            // Everything this throws is already written for the customer.
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'ต่ออายุไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หรือติดต่อทีมงาน');
        }

        if ($renewal->status === DomainRegistration::STATUS_REFUNDED) {
            return back()->with('error', 'ต่ออายุไม่สำเร็จ เราคืนเงินเข้ากระเป๋าให้เรียบร้อยแล้ว');
        }

        return back()->with('success', sprintf(
            'ต่ออายุ %s เรียบร้อย หมดอายุใหม่ %s',
            $domain->domain,
            $domain->fresh()?->expires_at?->format('d/m/Y') ?? '-',
        ));
    }

    /**
     * Replace the zone with what the customer submitted.
     *
     * Validated upstream first. A zone that fails validation is rejected
     * whole — a half-applied zone is how a customer loses their mail while
     * fixing their website.
     */
    public function updateDns(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->isUsable()) {
            return back()->with('error', 'โดเมนนี้ยังใช้งานไม่ได้ กรุณารอให้จดทะเบียนเสร็จก่อน');
        }

        $validated = $request->validate([
            'records' => ['required', 'array', 'max:200'],
            'records.*.name' => ['required', 'string', 'max:255'],
            'records.*.type' => ['required', 'string', 'in:' . implode(',', self::EDITABLE_TYPES)],
            'records.*.content' => ['required', 'string', 'max:2048'],
            'records.*.ttl' => ['nullable', 'integer', 'min:60', 'max:604800'],
        ], [
            'records.required' => 'ไม่มีรายการ DNS ที่จะบันทึก',
            'records.*.type.in' => 'ประเภทเรคคอร์ดไม่รองรับ',
        ]);

        $zone = $this->buildZone($validated['records']);

        $check = $this->api->validateDnsRecords($domain->domain, $zone);

        if (! $check['ok']) {
            return back()->withInput()->with('error', $this->explainZoneErrors($check['errors']));
        }

        // Deletions have to be sent as deletions.
        //
        // The update call only touches the name/type pairs it is given: with
        // overwrite it replaces those and appends the rest. A pair the
        // customer removed from the form is simply absent, so upstream never
        // hears about it and the record stays live — the row disappears from
        // the table, the customer believes it is gone, and their old mail
        // server keeps receiving mail. So diff against what is there now and
        // delete the pairs that are no longer wanted, before writing.
        $removed = $this->pairsRemoved($domain->domain, $zone);

        if ($removed !== [] && ! $this->api->deleteDnsRecords($domain->domain, $removed)) {
            Log::error('[CustomerDomain] DNS delete failed', [
                'registration_id' => $domain->id,
                'pairs' => count($removed),
            ]);

            return back()->withInput()->with('error', 'ลบเรคคอร์ดที่เอาออกไม่สำเร็จ ยังไม่ได้บันทึกอะไรเลย กรุณาลองใหม่');
        }

        if (! $this->api->updateDnsRecords($domain->domain, $zone, overwrite: true)) {
            Log::error('[CustomerDomain] DNS update failed after passing validation', [
                'registration_id' => $domain->id,
            ]);

            return back()->withInput()->with('error', 'บันทึก DNS ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง หากยังไม่ได้กรุณาแจ้งทีมงาน');
        }

        return back()->with('success', 'บันทึกการตั้งค่า DNS แล้ว การเปลี่ยนแปลงอาจใช้เวลา 5–30 นาทีจึงจะมีผลทั่วโลก');
    }

    /**
     * Point the domain at someone else's nameservers.
     *
     * Allowed deliberately: a customer who wants Cloudflare in front should
     * not have to ask us, and a domain we hold hostage by nameserver is not
     * really theirs. Moving away turns our DNS screen off, which the view
     * explains.
     */
    public function updateNameservers(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->isUsable()) {
            return back()->with('error', 'โดเมนนี้ยังใช้งานไม่ได้');
        }

        $validated = $request->validate([
            'nameservers' => ['required', 'array', 'min:2', 'max:4'],
            'nameservers.*' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i'],
        ], [
            'nameservers.min' => 'ต้องระบุ nameserver อย่างน้อย 2 รายการ',
            'nameservers.*.regex' => 'รูปแบบ nameserver ไม่ถูกต้อง',
        ]);

        $servers = array_values(array_unique(array_map('strtolower', $validated['nameservers'])));

        if (count($servers) < 2) {
            return back()->with('error', 'nameserver ต้องไม่ซ้ำกัน และต้องมีอย่างน้อย 2 รายการ');
        }

        if (! $this->api->updateNameservers($domain->domain, $servers)) {
            return back()->with('error', 'เปลี่ยน nameserver ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $domain->update(['nameservers' => $servers]);

        return back()->with('success', 'เปลี่ยน nameserver แล้ว อาจใช้เวลาถึง 24 ชั่วโมงจึงจะมีผลทั่วโลก');
    }

    /**
     * Hand over the transfer authorisation code.
     *
     * Given on request, always, with no retention offer in the way. The
     * domain belongs to the customer; making them argue for the code would
     * be holding their property.
     */
    public function authCode(Request $request, int $id): JsonResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->isUsable()) {
            return response()->json(['error' => 'โดเมนนี้ยังใช้งานไม่ได้'], 422);
        }

        $code = $this->api->getAuthCode($domain->domain);

        if (! $code) {
            return response()->json([
                'error' => 'ขอรหัสย้ายไม่สำเร็จ กรุณาแจ้งทีมงานเพื่อดำเนินการให้',
            ], 502);
        }

        Log::info('[CustomerDomain] auth code issued', [
            'registration_id' => $domain->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['auth_code' => $code]);
    }

    public function toggleAutoRenew(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        $enabled = $request->boolean('auto_renew');
        $domain->update(['auto_renew' => $enabled]);

        // The day count is an operator setting, so it cannot be written out
        // here — a shop that charges at 15 days would be promising 30.
        return back()->with('success', $enabled
            ? 'เปิดต่ออายุอัตโนมัติแล้ว เราจะตัดจากกระเป๋าเงินก่อนหมดอายุ ' . DomainReminders::chargeDays() . ' วัน'
            : 'ปิดต่ออายุอัตโนมัติแล้ว อย่าลืมต่ออายุเองก่อนหมดอายุ');
    }

    public function togglePrivacy(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->isUsable()) {
            return back()->with('error', 'โดเมนนี้ยังใช้งานไม่ได้');
        }

        $enabled = $request->boolean('privacy');

        $ok = $enabled
            ? $this->api->enablePrivacyProtection($domain->domain)
            : $this->api->disablePrivacyProtection($domain->domain);

        if (! $ok) {
            return back()->with('error', 'เปลี่ยนการตั้งค่าความเป็นส่วนตัวไม่สำเร็จ กรุณาลองใหม่');
        }

        $domain->update(['privacy_protection' => $enabled]);

        return back()->with('success', $enabled
            ? 'เปิดการปกปิดข้อมูลผู้ถือครองแล้ว'
            : 'ปิดการปกปิดข้อมูลผู้ถือครองแล้ว ข้อมูลของคุณจะแสดงใน WHOIS สาธารณะ');
    }

    /**
     * The customer's own row, or a 404.
     *
     * 404 rather than 403 on someone else's id: a 403 confirms the row
     * exists, which is a free enumeration oracle.
     */
    protected function findOwned(Request $request, int $id): DomainRegistration
    {
        return DomainRegistration::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    /**
     * Flatten the upstream zone into rows the table can render.
     *
     * @param  array<int,array<string,mixed>>|null  $zone
     * @return array<int,array<string,mixed>>
     */
    protected function groupRecords(?array $zone): array
    {
        if (! is_array($zone)) {
            return [];
        }

        $rows = [];

        foreach ($zone as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = (string) ($entry['name'] ?? '@');
            $type = strtoupper((string) ($entry['type'] ?? ''));
            $ttl = (int) ($entry['ttl'] ?? 14400);

            foreach (($entry['records'] ?? []) as $record) {
                $content = is_array($record) ? ($record['content'] ?? '') : $record;

                if ($content === '' || $content === null) {
                    continue;
                }

                $rows[] = [
                    'name' => $name,
                    'type' => $type,
                    'content' => (string) $content,
                    'ttl' => $ttl,
                    // SOA and anything else outside our list is shown but
                    // locked: deleting an SOA from a form would break the
                    // zone in a way the customer cannot undo.
                    'editable' => in_array($type, self::EDITABLE_TYPES, true),
                ];
            }
        }

        return $rows;
    }

    /**
     * Which name/type pairs exist upstream but are absent from the zone the
     * customer just submitted?
     *
     * Only editable types are considered. SOA and anything else outside the
     * form is invisible to the customer, was never theirs to remove, and
     * deleting it would break the zone.
     *
     * @param  array<int,array<string,mixed>>  $newZone
     * @return array<int,array{name:string,type:string}>
     */
    protected function pairsRemoved(string $domain, array $newZone): array
    {
        $current = $this->api->getDnsRecords($domain);

        if (! is_array($current)) {
            // We cannot see what is there, so we cannot know what to delete.
            // Returning nothing means the write still happens and the stale
            // record survives — visible in the table on the next load, rather
            // than silently deleted because we guessed.
            return [];
        }

        $wanted = [];
        foreach ($newZone as $entry) {
            $wanted[strtolower($entry['name']) . '|' . strtoupper($entry['type'])] = true;
        }

        $removed = [];
        foreach ($current as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = (string) ($entry['name'] ?? '@');
            $type = strtoupper((string) ($entry['type'] ?? ''));

            if (! in_array($type, self::EDITABLE_TYPES, true)) {
                continue;
            }

            $key = strtolower($name) . '|' . $type;

            if (! isset($wanted[$key]) && ! isset($removed[$key])) {
                $removed[$key] = ['name' => $name, 'type' => $type];
            }
        }

        return array_values($removed);
    }

    /**
     * Turn flat form rows back into the grouped shape the API wants.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,array<string,mixed>>
     */
    protected function buildZone(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $name = trim((string) $row['name']) ?: '@';
            $type = strtoupper(trim((string) $row['type']));
            $ttl = (int) ($row['ttl'] ?? 14400);
            $key = $name . '|' . $type . '|' . $ttl;

            $grouped[$key] ??= [
                'name' => $name,
                'type' => $type,
                'ttl' => $ttl,
                'records' => [],
            ];

            $grouped[$key]['records'][] = ['content' => trim((string) $row['content'])];
        }

        return array_values($grouped);
    }

    /**
     * Upstream validation errors, rewritten so they do not name the
     * registrar and do not dump a JSON blob at the customer.
     *
     * @param  array<int|string,mixed>  $errors
     */
    protected function explainZoneErrors(array $errors): string
    {
        $lines = [];

        array_walk_recursive($errors, function ($value) use (&$lines) {
            if (is_string($value) && $value !== '' && count($lines) < 4) {
                $lines[] = $value;
            }
        });

        if ($lines === []) {
            return 'การตั้งค่า DNS ไม่ถูกต้อง กรุณาตรวจสอบชื่อและค่าของแต่ละเรคคอร์ด';
        }

        return 'การตั้งค่า DNS ไม่ถูกต้อง: ' . implode(' · ', $lines);
    }

    protected function renewPriceFor(DomainRegistration $domain): ?string
    {
        $tld = $domain->tldRecord;

        return $tld ? DomainPricing::format($tld->renewPriceThb()) : null;
    }
}
