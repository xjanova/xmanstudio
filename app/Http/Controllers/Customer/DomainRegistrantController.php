<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\WhoisContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The registrant of a domain the customer already holds: see it, change it.
 *
 * The change goes upstream as a new WHOIS profile that all four contact roles
 * are repointed to. It is processed asynchronously, and a change of WHO owns
 * the domain (name, organisation, e-mail) makes the registry ask the
 * registrant to confirm by e-mail and hold transfers for 60 days — the form
 * says so before the customer submits, not after.
 *
 * Same fields, same rules and the same script as the order form
 * (domains/partials/registrant-*), so a contact that passes here is one the
 * registrar accepts.
 */
class DomainRegistrantController extends Controller
{
    public function __construct(
        protected HostingerApiService $api,
        protected DomainRegistrarService $registrar,
    ) {}

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->isUsable()) {
            return redirect()->route('customer.domains.show', $domain->id)
                ->with('error', 'แก้ข้อมูลผู้ถือครองได้เมื่อโดเมนจดเสร็จและใช้งานได้แล้ว');
        }

        $current = $domain->contact;
        $field = fn (string $name, string $default = '') => old($name, $current?->{$name} ?? $default);
        $user = $request->user();
        $nameParts = preg_split('/\s+/u', trim((string) $user->name), 2) ?: [];

        return view('customer.domains.registrant', [
            'domain' => $domain,
            'current' => $current,
            'initial' => [
                'first_name' => $field('first_name'),
                'last_name' => $field('last_name'),
                'organization' => $field('organization'),
                'email' => $field('email', (string) $user->email),
                'phone_country_code' => $field('phone_country_code', '+66'),
                'phone' => $field('phone'),
                'address1' => $field('address1'),
                'address2' => $field('address2'),
                'city' => $field('city'),
                // The form takes the province as typed or picked; the stored
                // value is the registrar's spelling, which the list also knows.
                'state' => $field('state'),
                'zip' => $field('zip'),
                'country' => $field('country', 'TH'),
            ],
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
            'addressMax' => WhoisContact::ADDRESS_MAX,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $domain = $this->findOwned($request, $id);

        if (! $domain->isUsable()) {
            return redirect()->route('customer.domains.show', $domain->id)
                ->with('error', 'แก้ข้อมูลผู้ถือครองได้เมื่อโดเมนจดเสร็จและใช้งานได้แล้ว');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'organization' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone_country_code' => ['required', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'address1' => ['required', 'string', 'max:180'],
            'address2' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'zip' => ['required', 'string', 'max:32'],
            'country' => ['required', 'string', 'size:2'],
            'accept_terms' => ['accepted'],
        ], [
            'accept_terms.accepted' => 'กรุณายืนยันว่าข้อมูลถูกต้อง และรับทราบเรื่องอีเมลยืนยันกับการล็อกย้าย 60 วัน',
            'state.required' => 'กรุณาเลือกจังหวัด',
        ]);

        $problems = WhoisContact::problems($validated);

        if ($problems !== []) {
            throw ValidationException::withMessages($problems);
        }

        $attributes = DomainContact::formAttributes($validated);
        $current = $domain->contact;

        if ($current && ! $current->differsFrom($attributes)) {
            return redirect()->route('customer.domains.show', $domain->id)
                ->with('success', 'ข้อมูลผู้ถือครองเหมือนเดิม ไม่มีอะไรต้องเปลี่ยน');
        }

        $ownerChanges = ! $current || $current->ownerChangesWith($attributes);

        // A new row, never an edit of the old one: the old row is the record of
        // what the registry held until now, and it may be another domain's
        // registrant too.
        $contact = DomainContact::create($attributes + [
            'user_id' => $request->user()->id,
            'hidden_from_picker' => false,
            'is_default' => false,
        ]);

        $whoisId = $this->registrar->ensureWhoisProfile($contact, $domain->tld);

        if (! $whoisId) {
            $refused = $this->api->lastWhoisRejectedFields();
            $contact->delete();

            return back()->withInput()->with('error', 'ทะเบียนไม่รับข้อมูลชุดนี้'
                . ($refused !== [] ? ' (ช่อง: ' . implode(', ', $refused) . ')' : '')
                . ' ยังไม่ได้เปลี่ยนอะไร กรุณาตรวจแล้วลองใหม่');
        }

        if (! $this->api->changeWhoisProfile($domain->domain, $whoisId)) {
            Log::error('[DomainRegistrant] change refused upstream', [
                'registration_id' => $domain->id,
                'status' => $this->api->lastFailure(),
            ]);

            return back()->withInput()->with('error', 'ส่งเรื่องเปลี่ยนข้อมูลผู้ถือครองไม่สำเร็จ ยังใช้ข้อมูลเดิมอยู่ กรุณาลองใหม่อีกครั้ง หรือแจ้งทีมงาน');
        }

        $domain->update(['domain_contact_id' => $contact->id]);
        Cache::forget('domain.details.' . $domain->id);

        Log::info('[DomainRegistrant] change submitted', [
            'registration_id' => $domain->id,
            'user_id' => $request->user()->id,
            'owner_changed' => $ownerChanges,
        ]);

        $message = 'ส่งเรื่องเปลี่ยนข้อมูลผู้ถือครอง ' . $domain->domain . ' แล้ว ทะเบียนจะปรับให้ภายในไม่กี่นาที';

        if ($ownerChanges) {
            $message .= ' — เพราะเปลี่ยนชื่อหรืออีเมลเจ้าของ จะมีอีเมลยืนยันส่งไปที่ ' . $contact->email
                . ' กรุณากดยืนยันในอีเมลนั้น (ไม่ยืนยัน = ไม่เปลี่ยน) และโดเมนจะย้ายออกไปผู้ให้บริการอื่นไม่ได้ 60 วัน';
        }

        return redirect()->route('customer.domains.show', $domain->id)->with('success', $message);
    }

    private function findOwned(Request $request, int $id): DomainRegistration
    {
        return DomainRegistration::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
