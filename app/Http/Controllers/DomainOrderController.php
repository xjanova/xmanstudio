<?php

namespace App\Http\Controllers;

use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\DomainSearchService;
use App\Services\HostingerApiService;
use App\Support\DomainPricing;
use App\Support\WhoisContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Ordering a domain: the registrant form, and the purchase itself.
 *
 * Auth lives on the route group, not in a constructor — Laravel 11's base
 * Controller has no middleware() method and calling it is a fatal error.
 */
class DomainOrderController extends Controller
{
    public function __construct(
        protected DomainSearchService $search,
        protected DomainRegistrarService $registrar,
        protected HostingerApiService $api,
    ) {}

    /**
     * The registrant form for one domain.
     */
    public function create(Request $request, string $domain): View|RedirectResponse
    {
        [$label, $tld] = $this->search->splitDomain($domain);

        if ($label === '' || ! $tld) {
            return redirect()->route('domains.index')
                ->with('error', 'ชื่อโดเมนไม่ถูกต้อง กรุณาค้นหาใหม่');
        }

        $domain = $label . '.' . $tld;
        $record = DomainTld::active()->where('tld', $tld)->first();

        if (! $record || ! $record->item_id_register) {
            return redirect()->route('domains.index')
                ->with('error', 'ขออภัย ขณะนี้เรายังไม่เปิดให้จดนามสกุล .' . $tld);
        }

        $user = $request->user();
        $wallet = Wallet::getOrCreateForUser($user->id);
        $price = $record->registerPriceThb();

        // Contacts the customer asked us not to offer again are still on
        // file against their domains; they just do not clutter the picker.
        $contacts = DomainContact::where('user_id', $user->id)
            ->where('hidden_from_picker', false)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        // A saved contact from before the registrar's rules were known (no
        // province, a 60-character address …) is shown, but flagged, and the
        // form starts on "enter new" instead of pre-selecting it.
        $contactProblems = $contacts->mapWithKeys(fn (DomainContact $c) => [
            $c->id => ($c->isSynced() && $c->remote_whois_id) ? [] : array_values(WhoisContact::problems($c)),
        ]);

        // "Use my account details": only what the account really holds.
        // Social sign-ups often have a display name, so the split is a guess
        // the customer sees and can correct before paying.
        $nameParts = preg_split('/\s+/u', trim((string) $user->name), 2) ?: [];

        return view('domains.register', [
            'whoisCountries' => WhoisContact::countries(),
            'thPostcodes' => [
                'prefixes' => config('domain_whois.th_postcode_prefixes', []),
                'exceptions' => config('domain_whois.th_postcode_exceptions', []),
            ],
            'accountPrefill' => [
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'email' => (string) $user->email,
                'phone' => (string) ($user->phone ?? ''),
            ],
            'contactProblems' => $contactProblems,
            'addressMax' => WhoisContact::ADDRESS_MAX,
            'domain' => $domain,
            'label' => $label,
            'tld' => $record,
            'price' => $price,
            'priceDisplay' => DomainPricing::format($price),
            'renewDisplay' => DomainPricing::format($record->renewPriceThb()),
            'renewalIsDearer' => $record->renewalIsDearer(),
            'wallet' => $wallet,
            'balanceDisplay' => DomainPricing::format((float) $wallet->balance),
            'sufficient' => $wallet->hasSufficientBalance($price),
            'shortfall' => max(0, $price - (float) $wallet->balance),
            'contacts' => $contacts,
            'extraFields' => $record->requiredExtraFields(),
        ]);
    }

    /**
     * Take the money and register the domain.
     */
    public function store(Request $request, string $domain): RedirectResponse
    {
        $user = $request->user();

        [$label, $tld] = $this->search->splitDomain($domain);
        $domain = $label . '.' . $tld;

        $validated = $request->validate([
            'contact_id' => ['nullable', 'integer'],
            'first_name' => ['required_without:contact_id', 'nullable', 'string', 'max:80'],
            'last_name' => ['required_without:contact_id', 'nullable', 'string', 'max:80'],
            'organization' => ['nullable', 'string', 'max:120'],
            'email' => ['required_without:contact_id', 'nullable', 'email', 'max:180'],
            'phone_country_code' => ['required_without:contact_id', 'nullable', 'string', 'max:8'],
            'phone' => ['required_without:contact_id', 'nullable', 'string', 'max:32'],
            'address1' => ['required_without:contact_id', 'nullable', 'string', 'max:180'],
            'address2' => ['nullable', 'string', 'max:180'],
            'city' => ['required_without:contact_id', 'nullable', 'string', 'max:80'],
            'state' => ['required_without:contact_id', 'nullable', 'string', 'max:80'],
            'zip' => ['required_without:contact_id', 'nullable', 'string', 'max:32'],
            'country' => ['required_without:contact_id', 'nullable', 'string', 'size:2'],
            'save_contact' => ['nullable', 'boolean'],
            'privacy' => ['nullable', 'boolean'],
            'auto_renew' => ['nullable', 'boolean'],
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'กรุณายอมรับเงื่อนไขการจดทะเบียนโดเมนก่อนดำเนินการต่อ',
            'state.required_without' => 'กรุณาเลือกจังหวัด',
        ]);

        // The registrar's own rules (one 50-character address line, its list
        // of provinces, digits-only phone …), field by field, before any
        // money moves. Without this the customer finds out from a refund.
        if (empty($validated['contact_id'])) {
            $problems = WhoisContact::problems($validated);

            if ($problems !== []) {
                throw ValidationException::withMessages($problems);
            }
        }

        try {
            $contact = $this->resolveContact($user->id, $validated);
        } catch (DomainPurchaseException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        try {
            $registration = $this->registrar->register($user->id, $domain, $contact, [
                'privacy' => (bool) ($validated['privacy'] ?? true),
                'auto_renew' => (bool) ($validated['auto_renew'] ?? false),
            ]);
        } catch (DomainPurchaseException $e) {
            // Customer-safe by construction — see the exception's docblock.
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[DomainOrder] unexpected failure', [
                'user_id' => $user->id,
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'เกิดข้อผิดพลาดที่ไม่คาดคิด ทีมงานได้รับแจ้งแล้ว กรุณาลองใหม่อีกครั้ง');
        }

        $redirect = redirect()->route('customer.domains.show', $registration->id);

        // A refused order is not a success: the domain page shows it in orange,
        // with the refund and a "try again" button, so no green flash on top.
        return $registration->status === DomainRegistration::STATUS_REFUNDED
            ? $redirect
            : $redirect->with('success', $this->outcomeMessage($registration));
    }

    /**
     * Use the saved registrant the customer picked, or create one from the
     * form. Either way the row must belong to them.
     *
     * @param  array<string,mixed>  $data
     *
     * @throws DomainPurchaseException
     */
    protected function resolveContact(int $userId, array $data): DomainContact
    {
        if (! empty($data['contact_id'])) {
            $contact = DomainContact::where('id', $data['contact_id'])
                ->where('user_id', $userId)
                ->first();

            // 404-shaped rather than 403: confirming the row exists would
            // tell someone probing ids that it does.
            if (! $contact) {
                throw new DomainPurchaseException('ไม่พบข้อมูลผู้ถือครองที่เลือก กรุณาเลือกใหม่');
            }

            return $contact;
        }

        // The row is always written, whatever the "save for next time" box
        // says — the registration points at it, and a domain whose registrant
        // record we did not keep is one we cannot show the customer, prove
        // ownership from, or re-push upstream when the profile is rebuilt.
        //
        // What the box actually controls is whether it shows up as a saved
        // option on the next order. Unticked means kept, but not offered.
        $keepVisible = (bool) ($data['save_contact'] ?? false);
        $first = DomainContact::where('user_id', $userId)->doesntExist();

        return DomainContact::create(DomainContact::formAttributes($data) + [
            'hidden_from_picker' => ! $keepVisible,
            'user_id' => $userId,
            'is_default' => $first,
        ]);
    }

    protected function outcomeMessage(DomainRegistration $registration): string
    {
        return match ($registration->status) {
            DomainRegistration::STATUS_ACTIVE => 'จดโดเมน ' . $registration->domain . ' สำเร็จแล้ว ตั้งค่า DNS ได้เลย',
            DomainRegistration::STATUS_REGISTERING => 'รับคำสั่งจดโดเมน ' . $registration->domain . ' แล้ว กำลังดำเนินการ ปกติใช้เวลาไม่เกิน 15 นาที เราจะแจ้งเมื่อเสร็จ',
            // Deliberately vague about WHY. The usual cause is our own card
            // or credit at the registrar, which is not the customer's
            // business and not something they can act on — and the honest
            // action for them either way is the same: the money is back, try
            // again shortly. The admin gets the real reason by Telegram.
            DomainRegistration::STATUS_REFUNDED => 'จดโดเมนไม่สำเร็จ คืนเงินเข้ากระเป๋าของคุณเรียบร้อยแล้ว '
                . 'ทีมงานได้รับแจ้งแล้วและกำลังตรวจสอบ — กรุณาลองใหม่อีกครั้งในภายหลัง',
            default => 'รับคำสั่งจดโดเมน ' . $registration->domain . ' แล้ว กำลังตรวจสอบสถานะ',
        };
    }
}
