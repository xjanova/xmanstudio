<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Services\HostingerApiService;
use App\Support\DomainPricing;
use App\Support\DomainReminders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Operator controls for the domain shop: the registrar credential, the
 * margin, the exchange rate, and the TLD catalogue.
 *
 * The margin and rate live here rather than in code because they move — the
 * baht moves weekly and the registrar's prices move whenever they like. An
 * operator who has to open a pull request to reprice the shop will not
 * reprice the shop.
 */
class DomainSettingController extends Controller
{
    public function __construct(
        protected HostingerApiService $api,
    ) {}

    public function index(): View
    {
        $tlds = DomainTld::orderBy('sort_order')->orderBy('tld')->get();

        return view('admin.domains.index', [
            'tlds' => $tlds,
            'hasToken' => (bool) Setting::getValue('hostinger_api_token'),
            'margin' => DomainPricing::defaultMargin(),
            'fxRate' => DomainPricing::fxRate(),
            'rounding' => DomainPricing::rounding(),
            'salesEnabled' => (bool) Setting::getValue('domain_sales_enabled', true),
            'reminders' => [
                'enabled' => DomainReminders::enabled(),
                'notice_days' => DomainReminders::noticeDays(),
                'charge_days' => DomainReminders::chargeDays(),
                'lead_days' => DomainReminders::leadDays(),
                'reminder_days' => DomainReminders::formatDays(DomainReminders::reminderDays()),
                'coherent' => DomainReminders::scheduleIsCoherent(),
            ],
            // Switched on is not the same as sellable: a TLD with no item id
            // cannot actually be bought, whatever the toggle says.
            'tldCounts' => [
                'total' => $tlds->count(),
                'active' => $tlds->where('is_active', true)->count(),
                'sellable' => $tlds->where('is_active', true)->whereNotNull('item_id_register')->count(),
                'no_item' => $tlds->whereNull('item_id_register')->count(),
            ],
            'stats' => $this->stats(),
            'unsettled' => DomainRegistration::unsettled()
                ->with('user:id,name,email')
                ->orderBy('created_at')
                ->limit(20)
                ->get(),
            // A live domain with no subscription link cannot be renewed from
            // the wallet. Shown here so it is fixed before the day it lapses.
            'unlinked' => DomainRegistration::registrations()
                ->where('status', DomainRegistration::STATUS_ACTIVE)
                ->whereNull('remote_subscription_id')
                ->with('user:id,name,email')
                ->orderBy('expires_at')
                ->limit(20)
                ->get(),
            'billing' => VpsSettingController::billingState(),
        ]);
    }

    /**
     * Tie a domain to its billing subscription by hand, when the purchase
     * never said which one it was and the automatic match found none or two.
     */
    public function linkSubscription(Request $request, int $id): RedirectResponse
    {
        $registration = DomainRegistration::registrations()->findOrFail($id);

        $validated = $request->validate([
            'subscription_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $subscriptionId = $validated['subscription_id'];

        $taken = DomainRegistration::where('remote_subscription_id', $subscriptionId)
            ->where('id', '!=', $registration->id)
            ->exists();

        if ($taken) {
            return back()->with('error', 'subscription นี้ผูกกับโดเมนอื่นอยู่แล้ว');
        }

        // Confirm it exists upstream before trusting it with renewals.
        $exists = collect($this->api->getSubscriptions() ?? [])
            ->contains(fn ($row) => is_array($row) && (string) ($row['id'] ?? '') === $subscriptionId);

        if (! $exists) {
            return back()->with('error', 'ไม่พบ subscription นี้ในบัญชีผู้ให้บริการ — ตรวจรหัสจาก hPanel อีกครั้ง');
        }

        $registration->update(['remote_subscription_id' => $subscriptionId]);

        // Ours renews it from the wallet; upstream's own renewal would bill our card too.
        $this->api->setAutoRenewal($subscriptionId, false);

        return back()->with('success', 'ผูก ' . $registration->domain . ' กับ subscription แล้ว ต่ออายุจากกระเป๋าเงินได้ตามปกติ');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hostinger_api_token' => ['nullable', 'string', 'max:500'],
            'domain_margin_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'domain_usd_thb_rate' => ['required', 'numeric', 'min:1', 'max:200'],
            'domain_price_rounding' => ['required', 'integer', 'in:1,5,10,50,100'],
            'domain_sales_enabled' => ['nullable', 'boolean'],
            'domain_reminders_enabled' => ['nullable', 'boolean'],
            'domain_notice_days' => ['required', 'integer', 'min:1', 'max:' . DomainReminders::MAX_DAYS],
            'domain_charge_days' => ['required', 'integer', 'min:1', 'max:' . DomainReminders::MAX_DAYS],
            'domain_notice_lead_days' => ['required', 'integer', 'min:0', 'max:30'],
            'domain_reminder_days' => ['nullable', 'string', 'max:100'],
        ], [
            'domain_margin_percent.min' => 'กำไรติดลบไม่ได้ — จะขายต่ำกว่าทุน',
            'domain_usd_thb_rate.min' => 'อัตราแลกเปลี่ยนต้องมากกว่า 1',
            'domain_notice_days.required' => 'ต้องระบุว่าจะแจ้งเตือนล่วงหน้ากี่วัน',
            'domain_charge_days.required' => 'ต้องระบุว่าจะตัดเงินก่อนหมดอายุกี่วัน',
        ]);

        // The warning has to reach the customer before the money moves. Saved
        // the other way round, the page's promise ("we always warn you first")
        // becomes a lie the same night the scheduler runs.
        $noticeDays = (int) $validated['domain_notice_days'];
        $chargeDays = (int) $validated['domain_charge_days'];
        $leadDays = (int) $validated['domain_notice_lead_days'];

        if ($noticeDays < $chargeDays + $leadDays) {
            return back()
                ->withInput()
                ->withErrors([
                    'domain_notice_days' => sprintf(
                        'แจ้งเตือนต้องมาก่อนตัดเงิน — ตั้งไว้อย่างน้อย %d วัน (ตัดเงิน %d + รออ่าน %d)',
                        $chargeDays + $leadDays,
                        $chargeDays,
                        $leadDays,
                    ),
                ]);
        }

        // Blank means "keep what is stored". The field renders with an empty
        // value and a masked placeholder, so echoing bullets back and saving
        // them would overwrite a working token with the string "••••".
        if (! empty($validated['hostinger_api_token'])) {
            Setting::setValue('hostinger_api_token', trim($validated['hostinger_api_token']), 'string', 'domains');
        }

        Setting::setValue('domain_margin_percent', $validated['domain_margin_percent'], 'string', 'domains');
        Setting::setValue('domain_usd_thb_rate', $validated['domain_usd_thb_rate'], 'string', 'domains');
        Setting::setValue('domain_price_rounding', $validated['domain_price_rounding'], 'integer', 'domains');
        Setting::setValue('domain_sales_enabled', $request->boolean('domain_sales_enabled'), 'boolean', 'domains');

        Setting::setValue('domain_reminders_enabled', $request->boolean('domain_reminders_enabled') ? '1' : '0', 'boolean', 'domains');
        Setting::setValue('domain_notice_days', (string) $noticeDays, 'integer', 'domains');
        Setting::setValue('domain_charge_days', (string) $chargeDays, 'integer', 'domains');
        Setting::setValue('domain_notice_lead_days', (string) $leadDays, 'integer', 'domains');

        // Normalised through the same parser the sender uses, so what is shown
        // back is exactly what will fire — not what was typed.
        Setting::setValue(
            'domain_reminder_days',
            DomainReminders::formatDays(DomainReminders::parseDays((string) ($validated['domain_reminder_days'] ?? ''))),
            'string',
            'domains'
        );

        $this->forgetCatalogueCaches();

        return back()->with('success', 'บันทึกการตั้งค่าโดเมนแล้ว ราคาทุกหน้าอัปเดตทันที');
    }

    /**
     * Edit one TLD: its margin override, whether it is sold, and where it
     * appears.
     */
    public function updateTld(Request $request, int $id): RedirectResponse
    {
        $tld = DomainTld::findOrFail($id);

        $validated = $request->validate([
            'margin_percent' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'search_by_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $tld->update([
            'margin_percent' => $validated['margin_percent'] !== null && $validated['margin_percent'] !== ''
                ? $validated['margin_percent']
                : null,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'search_by_default' => $request->boolean('search_by_default'),
            'sort_order' => $validated['sort_order'] ?? $tld->sort_order,
        ]);

        $this->forgetCatalogueCaches();

        return back()->with('success', 'อัปเดต .' . $tld->tld . ' แล้ว');
    }

    /**
     * Check the stored credential actually works, without spending anything.
     */
    public function testConnection(): RedirectResponse
    {
        if (! $this->api->isConfigured()) {
            return back()->with('error', 'ยังไม่ได้ใส่ API token');
        }

        // A read-only call on an endpoint that is metered separately from the
        // expensive ones.
        $result = $this->api->checkAvailability('xmanstudio-connection-test', ['com']);

        if ($result === null) {
            return back()->with('error', 'เชื่อมต่อไม่สำเร็จ — ตรวจสอบ token และสิทธิ์ของ token (ดูรายละเอียดใน log)');
        }

        return back()->with('success', 'เชื่อมต่อสำเร็จ — token ใช้งานได้');
    }

    /**
     * Pull the registrar's real prices, from the browser.
     *
     * The page used to print two artisan commands and expect the operator to
     * SSH in. Nobody does: the catalogue sat unsynced, every TLD kept a null
     * item id, and the shop could not sell a single domain while looking like
     * it could. The scheduler runs the same command nightly — this is the
     * button for the other twenty-three hours.
     *
     * Runs inline rather than queued because the operator is looking at the
     * page and wants the answer, and the call takes a couple of seconds.
     */
    public function syncCatalogue(Request $request): RedirectResponse
    {
        if (! $this->api->isConfigured()) {
            return back()->with('error', 'ยังไม่ได้ใส่ API token');
        }

        $dry = $request->boolean('dry');

        try {
            $exit = Artisan::call('domains:sync-catalogue', $dry ? ['--dry' => true] : []);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'ดึงราคาไม่สำเร็จ — ดูรายละเอียดใน log');
        }

        $output = trim(Artisan::output());

        if ($exit !== 0) {
            return back()
                ->with('error', 'ดึงราคาไม่สำเร็จ')
                ->with('sync_output', $output);
        }

        return back()
            ->with('success', $dry ? 'ทดลองดึงราคาแล้ว (ยังไม่บันทึก)' : 'ดึงราคาจากผู้ให้บริการเรียบร้อย')
            ->with('sync_output', $output);
    }

    /**
     * @return array<string,mixed>
     */
    protected function stats(): array
    {
        $active = DomainRegistration::where('status', DomainRegistration::STATUS_ACTIVE);

        $revenue = (clone $active)->sum('price_thb');
        $cost = (clone $active)->get()
            ->sum(fn (DomainRegistration $r) => DomainPricing::costThb(
                $r->cost_usd_cents,
                (float) $r->fx_rate,
                (string) ($r->cost_currency ?: 'USD'),
            ));

        return [
            'active' => (clone $active)->count(),
            'unsettled' => DomainRegistration::unsettled()->count(),
            'refunded' => DomainRegistration::where('status', DomainRegistration::STATUS_REFUNDED)->count(),
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
            'expiring30' => DomainRegistration::where('status', DomainRegistration::STATUS_ACTIVE)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(30)])
                ->count(),
        ];
    }

    /**
     * Switch every TLD in the catalogue on, or off, in one press.
     *
     * Eighteen rows each with its own form is fine for tuning one extension
     * and miserable for "open the shop". Switching on does NOT make an
     * unsellable TLD sellable — DomainTld::scopeActive also needs an item id
     * from the registrar — so the message says how many actually became
     * buyable rather than how many rows were touched, which is the number an
     * operator would otherwise be quietly misled by.
     */
    public function bulkTlds(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:enable,disable'],
        ]);

        $enable = $validated['action'] === 'enable';

        $changed = DomainTld::where('is_active', '!=', $enable)->update(['is_active' => $enable]);

        $this->forgetCatalogueCaches();

        if (! $enable) {
            return back()->with('success', "ปิดขายทุกนามสกุลแล้ว ({$changed} รายการ)");
        }

        $sellable = DomainTld::active()->count();
        $blocked = DomainTld::where('is_active', true)->whereNull('item_id_register')->count();

        $message = "เปิดขายทุกนามสกุลแล้ว ({$changed} รายการที่เปลี่ยน) · ขายได้จริง {$sellable} นามสกุล";

        if ($blocked > 0) {
            $message .= " · อีก {$blocked} นามสกุลยังขายไม่ได้เพราะยังไม่มีรหัสสินค้าจากผู้ให้บริการ — ต้องกดปุ่มดึงราคาจริงก่อน";
        }

        return back()->with('success', $message);
    }

    protected function forgetCatalogueCaches(): void
    {
        Cache::forget('domain.catalogue');
        Cache::forget('domain.search_tlds');
    }
}
