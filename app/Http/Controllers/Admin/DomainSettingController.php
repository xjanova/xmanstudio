<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Services\HostingerApiService;
use App\Support\DomainPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'stats' => $this->stats(),
            'unsettled' => DomainRegistration::unsettled()
                ->with('user:id,name,email')
                ->orderBy('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hostinger_api_token' => ['nullable', 'string', 'max:500'],
            'domain_margin_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'domain_usd_thb_rate' => ['required', 'numeric', 'min:1', 'max:200'],
            'domain_price_rounding' => ['required', 'integer', 'in:1,5,10,50,100'],
            'domain_sales_enabled' => ['nullable', 'boolean'],
        ], [
            'domain_margin_percent.min' => 'กำไรติดลบไม่ได้ — จะขายต่ำกว่าทุน',
            'domain_usd_thb_rate.min' => 'อัตราแลกเปลี่ยนต้องมากกว่า 1',
        ]);

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
     * @return array<string,mixed>
     */
    protected function stats(): array
    {
        $active = DomainRegistration::where('status', DomainRegistration::STATUS_ACTIVE);

        $revenue = (clone $active)->sum('price_thb');
        $cost = (clone $active)->get()
            ->sum(fn (DomainRegistration $r) => DomainPricing::costThb($r->cost_usd_cents, (float) $r->fx_rate));

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

    protected function forgetCatalogueCaches(): void
    {
        Cache::forget('domain.catalogue');
        Cache::forget('domain.search_tlds');
    }
}
