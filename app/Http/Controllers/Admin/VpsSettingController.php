<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Models\VpsPlan;
use App\Services\HostingerApiService;
use App\Services\VpsOrderException;
use App\Services\VpsProvisioningService;
use App\Support\AdminAlerts;
use App\Support\DomainPricing;
use App\Support\UpstreamBilling;
use App\Support\VpsCatalog;
use App\Support\VpsPricing;
use App\Support\VpsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * Operator controls for the VPS shop: margin, plans, the renewal schedule,
 * every customer server — and the one thing both shops depend on, whether
 * our card at the supplier still pays.
 */
class VpsSettingController extends Controller
{
    public function __construct(
        protected HostingerApiService $api,
        protected VpsProvisioningService $provisioning,
    ) {}

    public function index(): View
    {
        $plans = VpsPlan::ordered()->get();
        $instances = VpsInstance::with('user:id,name,email')->latest()->limit(100)->get();

        $paid = VpsPayment::where('status', VpsPayment::STATUS_PAID);
        $revenue = (float) (clone $paid)->sum('amount_thb');
        $cost = (clone $paid)->get()->sum(fn (VpsPayment $p) => DomainPricing::costThb((int) $p->cost_cents, null, (string) $p->cost_currency));

        return view('admin.vps.index', [
            'plans' => $plans,
            'instances' => $instances,
            'attention' => VpsInstance::with('user:id,name,email')
                ->whereIn('status', [VpsInstance::STATUS_PENDING, VpsInstance::STATUS_PROVISIONING, VpsInstance::STATUS_FAILED])
                ->orderBy('created_at')
                ->get(),
            'hasToken' => $this->api->isConfigured(),
            'settings' => [
                'sales_enabled' => VpsSettings::salesEnabled(),
                'margin' => VpsPricing::defaultMargin(),
                'rounding' => VpsPricing::rounding(),
                'notice_days' => VpsSettings::noticeDays(),
                'charge_days' => VpsSettings::chargeDays(),
                'lead_days' => VpsSettings::leadDays(),
                'coherent' => VpsSettings::scheduleIsCoherent(),
            ],
            'stats' => [
                'active' => VpsInstance::where('status', VpsInstance::STATUS_ACTIVE)->count(),
                'provisioning' => VpsInstance::whereIn('status', [VpsInstance::STATUS_PENDING, VpsInstance::STATUS_PROVISIONING])->count(),
                'failed' => VpsInstance::where('status', VpsInstance::STATUS_FAILED)->count(),
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $revenue - $cost,
            ],
            'billing' => $this->billingState(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vps_margin_percent' => ['required', 'numeric', 'min:0', 'max:500'],
            'vps_price_rounding' => ['required', 'integer', 'in:1,5,10,50,100'],
            'vps_notice_days' => ['required', 'integer', 'min:1', 'max:' . VpsSettings::MAX_DAYS],
            'vps_charge_days' => ['required', 'integer', 'min:1', 'max:' . VpsSettings::MAX_DAYS],
            'vps_notice_lead_days' => ['required', 'integer', 'min:0', 'max:30'],
        ], [
            'vps_margin_percent.min' => 'กำไรติดลบไม่ได้ — จะให้เช่าต่ำกว่าทุน',
        ]);

        // A warning that arrives after the charge is not a warning.
        if ((int) $validated['vps_notice_days'] < (int) $validated['vps_charge_days'] + (int) $validated['vps_notice_lead_days']) {
            return back()->withInput()->withErrors([
                'vps_notice_days' => sprintf(
                    'แจ้งเตือนต้องมาก่อนตัดเงิน — ตั้งไว้อย่างน้อย %d วัน (ตัดเงิน %d + รออ่าน %d)',
                    (int) $validated['vps_charge_days'] + (int) $validated['vps_notice_lead_days'],
                    (int) $validated['vps_charge_days'],
                    (int) $validated['vps_notice_lead_days'],
                ),
            ]);
        }

        Setting::setValue('vps_sales_enabled', $request->boolean('vps_sales_enabled') ? '1' : '0', 'boolean', 'vps');
        Setting::setValue('vps_margin_percent', (string) $validated['vps_margin_percent'], 'string', 'vps');
        Setting::setValue('vps_price_rounding', (string) $validated['vps_price_rounding'], 'integer', 'vps');
        Setting::setValue('vps_notice_days', (string) $validated['vps_notice_days'], 'integer', 'vps');
        Setting::setValue('vps_charge_days', (string) $validated['vps_charge_days'], 'integer', 'vps');
        Setting::setValue('vps_notice_lead_days', (string) $validated['vps_notice_lead_days'], 'integer', 'vps');

        return back()->with('success', 'บันทึกการตั้งค่า VPS แล้ว ราคาทุกหน้าอัปเดตทันที');
    }

    /** Pull plans and prices from the supplier, from the browser — nobody SSHes in to run artisan. */
    public function syncCatalogue(): RedirectResponse
    {
        if (! $this->api->isConfigured()) {
            return back()->with('error', 'ยังไม่ได้ใส่ API token (ตั้งที่หน้าระบบขายโดเมน)');
        }

        try {
            $exit = Artisan::call('vps:sync-catalogue');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'ดึงแพ็กเกจไม่สำเร็จ — ดูรายละเอียดใน log');
        }

        app(VpsCatalog::class)->forget();

        return back()
            ->with($exit === 0 ? 'success' : 'error', $exit === 0 ? 'ดึงแพ็กเกจและราคาจริงเรียบร้อย' : 'ดึงแพ็กเกจไม่สำเร็จ')
            ->with('sync_output', trim(Artisan::output()));
    }

    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $plan = VpsPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'margin_percent' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'description_th' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
        ]);

        $plan->update([
            'name' => trim($validated['name']),
            'margin_percent' => ($validated['margin_percent'] ?? '') !== '' ? $validated['margin_percent'] : null,
            'sort_order' => $validated['sort_order'] ?? $plan->sort_order,
            'description_th' => $validated['description_th'] ?? null,
            'description_en' => $validated['description_en'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return back()->with('success', 'อัปเดตแพ็กเกจ ' . $plan->name . ' แล้ว');
    }

    /** Run one order through the reconciliation step now, instead of waiting for the scheduler. */
    public function retry(int $id): RedirectResponse
    {
        $instance = VpsInstance::findOrFail($id);

        if ($instance->status === VpsInstance::STATUS_FAILED) {
            // A person has decided it is worth another go.
            $instance->update(['status' => VpsInstance::STATUS_PROVISIONING, 'setup_attempts' => 0]);
        }

        if ($instance->isSettled() && $instance->status !== VpsInstance::STATUS_PROVISIONING) {
            return back()->with('error', 'รายการนี้ไม่ได้ค้างอยู่');
        }

        $outcome = $this->provisioning->reconcile($instance->fresh());

        return back()->with('success', 'ตรวจสอบกับผู้ให้บริการแล้ว: ' . $outcome);
    }

    public function refund(Request $request, int $id): RedirectResponse
    {
        $instance = VpsInstance::findOrFail($id);

        $validated = $request->validate([
            'confirm' => ['required', 'in:REFUND'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'confirm.in' => 'พิมพ์ REFUND ให้ตรงเพื่อยืนยันการคืนเงิน',
        ]);

        try {
            $this->provisioning->adminRefund($instance, trim(($validated['note'] ?? '') . ' by ' . $request->user()->name));
        } catch (VpsOrderException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'คืนเงินลูกค้าแล้ว และปิดการต่ออายุฝั่งผู้ให้บริการแล้ว — ตรวจใน hPanel ว่าเครื่องที่ซื้อไว้ต้องการใช้ต่อหรือไม่');
    }

    /** Read the supplier's payment methods now, not at the next scheduled check. */
    public function checkBilling(): RedirectResponse
    {
        Artisan::call('hostinger:billing-check');

        $health = UpstreamBilling::lastCheck();

        return back()->with(
            ($health['level'] ?? '') === 'ok' ? 'success' : 'error',
            ($health['level'] ?? '') === 'ok'
                ? 'ตรวจแล้ว: วิธีชำระเงินในบัญชีผู้ให้บริการปกติ'
                : 'ตรวจแล้ว: ' . implode(' / ', (array) ($health['problems'] ?? ['อ่านข้อมูลไม่ได้'])),
        );
    }

    /** The admin fixed the card — open the shops again without waiting for the pause to expire. */
    public function resumeSales(): RedirectResponse
    {
        UpstreamBilling::resume();

        return back()->with('success', 'เปิดรับคำสั่งซื้อโดเมนและ VPS ต่อแล้ว — ถ้าบัตรยังจ่ายไม่ผ่าน ระบบจะหยุดเองอีกครั้งพร้อมแจ้งเตือน');
    }

    /**
     * Everything the shared "can we pay the supplier" panel shows.
     *
     * @return array<string,mixed>
     */
    public static function billingState(): array
    {
        return [
            'paused' => UpstreamBilling::paused(),
            'health' => UpstreamBilling::lastCheck(),
            'last_failure' => UpstreamBilling::lastFailure(),
            'telegram' => AdminAlerts::enabled(),
            'orders_alerts' => AdminAlerts::wants('orders'),
        ];
    }
}
