<?php

namespace App\Http\Controllers;

use App\Models\DomainRegistration;
use App\Models\VpsInstance;
use App\Models\VpsPlan;
use App\Models\Wallet;
use App\Services\VpsOrderException;
use App\Services\VpsProvisioningService;
use App\Support\UpstreamBilling;
use App\Support\VpsCatalog;
use App\Support\VpsPricing;
use App\Support\VpsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The public VPS shop: the plans, and ordering one.
 *
 * Nothing here names the supplier. The customer is renting a server from
 * XMAN Studio; plan names are ours, and the catalogue's own names ("KVM 2")
 * stay on the admin page.
 */
class VpsController extends Controller
{
    public function __construct(
        protected VpsCatalog $catalog,
        protected VpsProvisioningService $provisioning,
    ) {}

    public function index(): View
    {
        $plans = VpsPlan::active()->ordered()->get()->filter->isSellable()->values();

        return view('vps.index', [
            'plans' => $plans,
            'cards' => $plans->map(fn (VpsPlan $p) => $this->card($p))->values(),
            'salesOpen' => VpsSettings::salesEnabled(),
        ]);
    }

    public function create(Request $request, VpsPlan $plan): View|RedirectResponse
    {
        if (! $plan->isSellable() || ! VpsSettings::salesEnabled()) {
            return redirect()->route('vps.index')->with('error', 'แพ็กเกจนี้ยังไม่เปิดให้เช่า');
        }

        $user = $request->user();
        $wallet = Wallet::getOrCreateForUser($user->id);

        $periods = collect($plan->periods())->map(fn (string $p) => [
            'key' => $p,
            'label_th' => VpsPricing::periodLabel($p, 'th'),
            'label_en' => VpsPricing::periodLabel($p, 'en'),
            'unit_th' => VpsPricing::unitLabel($p, 'th'),
            'months' => VpsPricing::months($p),
            'first' => $plan->firstPriceThb($p),
            'renew' => $plan->renewPriceThb($p),
            'first_display' => VpsPricing::format($plan->firstPriceThb($p)),
            'renew_display' => VpsPricing::format($plan->renewPriceThb($p)),
        ])->values();

        $defaultPeriod = old('period', $request->query('period', $periods->first()['key'] ?? '1m'));

        return view('vps.order', [
            'plan' => $plan,
            'periods' => $periods,
            'defaultPeriod' => $periods->contains('key', $defaultPeriod) ? $defaultPeriod : ($periods->first()['key'] ?? '1m'),
            'templates' => $this->catalog->groupedTemplates(),
            'defaultTemplate' => (int) old('template_id', $this->catalog->defaultTemplateId()),
            'dataCenters' => $this->catalog->dataCenters(),
            'defaultDataCenter' => (int) old('data_center_id', $this->catalog->defaultDataCenterId()),
            'wallet' => $wallet,
            'balance' => (float) $wallet->balance,
            'balanceDisplay' => VpsPricing::format((float) $wallet->balance),
            'suggestedHostname' => $this->suggestHostname($user->id),
            // One token per rendered form: a double submit shares it, a second
            // deliberate order (a new page load) does not.
            'orderToken' => (string) Str::uuid(),
            'paused' => UpstreamBilling::paused(),
        ]);
    }

    public function store(Request $request, VpsPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'order_token' => ['required', 'uuid'],
            'period' => ['required', 'string', 'in:' . implode(',', array_keys(VpsPricing::PERIODS))],
            'template_id' => ['required', 'integer', 'min:1'],
            'data_center_id' => ['required', 'integer', 'min:1'],
            'hostname' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i'],
            // The supplier's rules, checked here so a weak password is a form
            // error — not a paid order that gets refused and refunded.
            'root_password' => ['required', 'string', 'max:128', Password::min(12)->mixedCase()->numbers()->uncompromised()],
            'public_key' => ['nullable', 'string', 'max:4096', 'regex:/^(ssh-(rsa|ed25519|dss)|ecdsa-sha2-nistp(256|384|521)|sk-ssh-ed25519@openssh\.com|sk-ecdsa-sha2-nistp256@openssh\.com) [A-Za-z0-9+\/=]+( [^\r\n]{0,200})?$/'],
            'auto_renew' => ['nullable', 'boolean'],
            'expected_amount' => ['required', 'numeric', 'min:0'],
            'accept_terms' => ['accepted'],
        ], [
            'hostname.regex' => 'ชื่อโฮสต์ต้องเป็นรูปแบบโดเมน เช่น server.example.com (a-z 0-9 ขีดกลาง และจุด)',
            'root_password.required' => 'กรุณาตั้งรหัสผ่าน root',
            'root_password.min' => 'รหัสผ่านต้องยาวอย่างน้อย 12 ตัวอักษร',
            'root_password.mixed' => 'รหัสผ่านต้องมีทั้งตัวพิมพ์ใหญ่และตัวพิมพ์เล็ก',
            'root_password.numbers' => 'รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว',
            'root_password.uncompromised' => 'รหัสผ่านนี้เคยหลุดสู่สาธารณะ กรุณาตั้งรหัสใหม่',
            'public_key.regex' => 'SSH public key ไม่ถูกต้อง (ต้องขึ้นต้นด้วย ssh-ed25519, ssh-rsa หรือ ecdsa-…)',
            'accept_terms.accepted' => 'กรุณายอมรับเงื่อนไขการใช้งานก่อนสั่งเช่า',
        ]);

        // Never flash the password back into the form, whatever happens next.
        $safeInput = $request->except(['root_password', '_token']);

        try {
            $instance = $this->provisioning->order($request->user(), $plan, $validated['period'], [
                'template_id' => (int) $validated['template_id'],
                'data_center_id' => (int) $validated['data_center_id'],
                'hostname' => strtolower($validated['hostname']),
                'password' => $validated['root_password'],
                'public_key' => $validated['public_key'] ?? null,
                // An unticked checkbox sends nothing at all, so the default
                // here must be false — "true when missing" would switch
                // auto-renew ON for exactly the customers who turned it off.
                'auto_renew' => $request->boolean('auto_renew'),
                'expected_amount' => (float) $validated['expected_amount'],
            ], $validated['order_token']);
        } catch (VpsOrderException $e) {
            return back()->withInput($safeInput)->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[VpsOrder] unexpected failure', [
                'user_id' => $request->user()->id,
                'plan' => $plan->slug,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput($safeInput)->with('error', 'เกิดข้อผิดพลาดที่ไม่คาดคิด ทีมงานได้รับแจ้งแล้ว กรุณาตรวจสอบรายการ VPS ของคุณก่อนลองใหม่');
        }

        return redirect()->route('customer.vps.show', $instance->id)
            ->with($instance->status === VpsInstance::STATUS_REFUNDED ? 'error' : 'success', $this->outcomeMessage($instance));
    }

    /**
     * A hostname the customer can accept as it is: under their own domain
     * when they hold one with us, otherwise a random name under ours. It is
     * only a label on the machine — nothing has to resolve for it to work.
     */
    protected function suggestHostname(int $userId): string
    {
        $own = DomainRegistration::where('user_id', $userId)
            ->registrations()
            ->active()
            ->orderBy('created_at')
            ->value('domain');

        $suffix = Str::lower(Str::random(4));

        if ($own) {
            return 'vps-' . $suffix . '.' . $own;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'xman4289.com';

        return 'vps-' . Str::lower(Str::random(6)) . '.' . preg_replace('/^www\./', '', $host);
    }

    protected function outcomeMessage(VpsInstance $instance): string
    {
        return match ($instance->status) {
            VpsInstance::STATUS_ACTIVE => 'เซิร์ฟเวอร์ ' . $instance->hostname . ' พร้อมใช้งานแล้ว เข้าใช้งานด้วย SSH ได้ทันที',
            VpsInstance::STATUS_REFUNDED => 'สั่งเช่าไม่สำเร็จ คืนเงินเข้ากระเป๋าของคุณเรียบร้อยแล้ว ทีมงานได้รับแจ้งแล้ว — กรุณาลองใหม่อีกครั้งในภายหลัง',
            default => 'รับคำสั่งเช่าแล้ว กำลังติดตั้งเซิร์ฟเวอร์ ปกติใช้เวลา 5–15 นาที เราจะส่งอีเมลแจ้งเมื่อพร้อม',
        };
    }

    /**
     * What the plan card needs, pre-formatted, per period — the page switches
     * period with Alpine and must not do money arithmetic in JavaScript.
     *
     * @return array<string,mixed>
     */
    protected function card(VpsPlan $plan): array
    {
        $prices = [];

        foreach ($plan->periods() as $period) {
            $months = VpsPricing::months($period);
            $first = $plan->firstPriceThb($period);
            $renew = $plan->renewPriceThb($period);

            $prices[$period] = [
                'first' => VpsPricing::format($first),
                'renew' => VpsPricing::format($renew),
                'first_monthly' => VpsPricing::format(ceil($first / $months)),
                'renew_monthly' => VpsPricing::format(ceil($renew / $months)),
                'dearer' => $renew > $first,
                'months' => $months,
            ];
        }

        return [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'category' => $plan->category,
            'featured' => $plan->is_featured,
            'cpus' => $plan->cpus,
            'memory' => VpsPlan::sizeLabel($plan->memory_mb),
            'disk' => VpsPlan::sizeLabel($plan->disk_mb),
            'bandwidth' => VpsPlan::sizeLabel($plan->bandwidth_mb),
            'network' => $plan->network_mbps,
            'description_th' => $plan->description_th,
            'description_en' => $plan->description_en,
            'prices' => $prices,
            'from_monthly' => VpsPricing::format($plan->fromMonthlyThb()),
            'order_url' => route('vps.order', $plan->slug),
        ];
    }
}
