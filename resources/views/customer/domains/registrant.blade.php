@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ผู้ถือครอง ' . $domain->domain . ' · โดเมนของฉัน')
@section('page-title'){{ $domain->domain }}@endsection
@section('page-description')<x-bi th="ข้อมูลผู้ถือครองโดเมน (WHOIS)" en="Domain registrant (WHOIS)" />@endsection

@section('content')
{{--
    แก้ข้อมูลผู้ถือครองโดเมนที่จดแล้ว — ฟอร์มและกฎชุดเดียวกับหน้าจด
    (domains/partials/registrant-*) บอกผลข้างเคียงของทะเบียนก่อนกดส่ง:
    เปลี่ยนชื่อ/อีเมลเจ้าของ = มีอีเมลยืนยัน + ย้ายออกไม่ได้ 60 วัน
--}}
@php
    $serverErrors = collect(['first_name', 'last_name', 'organization', 'email', 'phone_country_code', 'phone', 'address1', 'address2', 'city', 'state', 'zip', 'country'])
        ->mapWithKeys(fn ($f) => [$f => $errors->first($f)])
        ->filter()
        ->all();
    $card = 'rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700';
@endphp

@include('domains.partials.registrant-script')

<div class="space-y-6">
    <a href="{{ route('customer.domains.show', $domain->id) }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <x-bi th="กลับไปหน้าโดเมน" en="Back to the domain" />
    </a>

    <div class="{{ $card }} p-6 space-y-4">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">
                <x-bi th="ผู้ถือครองตอนนี้" en="Current registrant" />
            </h2>
            @if($current)
                <p class="text-sm text-slate-800 dark:text-slate-200 font-semibold">{{ $current->fullName() }}@if($current->organization) · {{ $current->organization }}@endif</p>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ $current->email }} · {{ $current->phoneE164() }}</p>
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ trim($current->address1 . ' ' . $current->address2) }}, {{ $current->city }}, {{ $current->state }} {{ $current->zip }}, {{ $current->country }}</p>
            @else
                <p class="text-sm text-slate-600 dark:text-slate-400"><x-bi th="ไม่มีข้อมูลผู้ถือครองในระบบเรา" en="We hold no registrant record for this domain" /></p>
            @endif
        </div>
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200 space-y-1.5">
            <p class="font-semibold"><x-bi th="ก่อนแก้ไข ควรรู้" en="Before you change it" /></p>
            <ul class="list-disc pl-5 space-y-1">
                <li><x-bi th="แก้ที่อยู่ เบอร์โทร หรือรหัสไปรษณีย์: ทะเบียนปรับให้ภายในไม่กี่นาที ไม่มีขั้นตอนเพิ่ม"
                          en="Address, phone or postcode: the registry updates within minutes, nothing else to do." /></li>
                <li><x-bi th="เปลี่ยนชื่อ องค์กร หรืออีเมลเจ้าของ: ทะเบียนส่งอีเมลให้กดยืนยัน (ไม่กด = ไม่เปลี่ยน) และโดเมนจะย้ายไปผู้ให้บริการอื่นไม่ได้ 60 วัน ตามกฎสากล"
                          en="Owner name, organisation or e-mail: the registry e-mails a confirmation link (no click, no change) and holds transfers to another provider for 60 days, per international rules." /></li>
                <li><x-bi th="ข้อมูลใน WHOIS สาธารณะยังถูกปกปิดตามเดิมถ้าเปิดการปกปิดไว้"
                          en="Public WHOIS stays hidden if privacy is on." /></li>
            </ul>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.domains.registrant.update', $domain->id) }}" novalidate
          x-data="domainRegistrant(@js([
              'mode' => 'new',
              'contactId' => null,
              'initial' => $initial,
              'countries' => $whoisCountries,
              'postcodes' => $thPostcodes,
              'account' => $accountPrefill,
              'addressMax' => $addressMax,
              'serverErrors' => (object) $serverErrors,
              'csrfUrl' => route('csrf.refresh'),
          ]))"
          @submit="onSubmit($event)"
          class="space-y-6">
        @csrf

        <div class="{{ $card }} p-5 sm:p-7">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">
                        <x-bi th="ข้อมูลผู้ถือครองใหม่" en="New registrant details" />
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="กรอกให้ตรงกับบัตรประชาชนหรือหนังสือรับรองบริษัท — ใช้ยืนยันสิทธิ์ตอนย้ายโดเมน"
                              en="Match your ID card or company certificate — it proves the domain is yours when you move it."
                              layout="stack" />
                    </p>
                </div>
                <button type="button" @click="fillFromAccount()"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-indigo-200 dark:border-indigo-500/40 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 text-sm font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition">
                    <x-bi th="ใช้ข้อมูลจากบัญชีของฉัน" en="Use my account" />
                </button>
            </div>

            <p x-show="note.account" x-text="note.account" x-cloak
               class="mb-5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-200 text-sm px-4 py-3"></p>

            @include('domains.partials.registrant-fields')
        </div>

        <div class="{{ $card }} p-5 sm:p-7 space-y-5">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="accept_terms" value="1" required x-ref="terms"
                       @change="termsMissing = false" class="mt-1">
                <span class="text-sm text-slate-700 dark:text-slate-300">
                    <x-bi th="ข้าพเจ้ายืนยันว่าข้อมูลเป็นความจริง และรับทราบว่าถ้าเปลี่ยนชื่อ องค์กร หรืออีเมลเจ้าของ จะต้องกดยืนยันทางอีเมล และโดเมนจะย้ายออกไม่ได้ 60 วัน"
                          en="I confirm these details are accurate, and understand that changing the owner's name, organisation or e-mail needs e-mail confirmation and holds transfers for 60 days."
                          layout="stack" />
                    <span x-show="termsMissing" x-cloak class="field-error">
                        <x-bi th="กรุณาติ๊กยืนยันก่อนบันทึก" en="Please tick to confirm first" />
                    </span>
                </span>
            </label>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 pt-5 border-t border-slate-200 dark:border-slate-700">
                <a href="{{ route('customer.domains.show', $domain->id) }}"
                   class="px-5 py-3 rounded-xl text-center text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                    <x-bi th="ยกเลิก" en="Cancel" />
                </a>
                <button type="submit" x-bind:disabled="submitting"
                        class="px-7 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-base font-bold shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!submitting"><x-bi th="บันทึกข้อมูลผู้ถือครอง" en="Save registrant" /></span>
                    <span x-show="submitting" x-cloak><x-bi th="กำลังส่งไปที่ทะเบียน…" en="Sending to the registry…" /></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
