@extends($publicLayout ?? 'layouts.app')

@section('title', 'ชำระเงิน - ' . $package->display_name . ' - XMAN Studio')

@php
    // A product package (XMAN MLM, …) sells with add-ons and a monthly/yearly switch.
    $addonCatalogue = $addonCatalogue ?? collect();
    $cycles = $cycles ?? [];
    $selected = $selected ?? [];
    $isProduct = $package->product_key && $package->billingCycle();
    $cycle = $package->billingCycle() ?? 'monthly';
    $packageSatang = \App\Support\Satang::fromBaht((string) $package->price);
    $addonData = $addonCatalogue->map(fn ($addon) => [
        'code' => $addon->code,
        'name' => $addon->display_name,
        'unit' => $addon->cyclePriceSatang($cycle),
        'max' => max(1, (int) $addon->max_quantity),
        'group' => $addon->exclusive_group,
        'qty' => (int) ($selected[$addon->code] ?? 0),
    ])->values();
    $cycleQuery = fn (string $c) => array_filter([
        'cycle' => $c,
        'addons' => $selected ?: null,
        'mlm_reservation' => $reservation ?? null,
    ]);
@endphp

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 px-6 py-8 mb-8">
            <x-page-art art="hero-payment" :opacity="14" :scrim="false" fade="bottom" />
            <div class="relative">
                <!-- Breadcrumb -->
                <nav class="mb-3">
                    <ol class="flex items-center space-x-2 text-sm text-gray-300">
                        <li><a href="{{ route('rental.index') }}" class="hover:text-white"><x-bi th="แพ็กเกจ" en="Packages" /></a></li>
                        <li><span>/</span></li>
                        <li class="text-white"><x-bi th="ชำระเงิน" en="Payment" /></li>
                    </ol>
                </nav>
                <h1 class="text-2xl md:text-3xl font-bold text-white"><x-bi th="ยืนยันการสั่งซื้อ" en="Checkout" /></h1>
                <p class="mt-1 text-gray-300">{{ $package->display_name }}</p>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        @if(!empty($addonNotices))
            <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded">
                <p class="font-semibold"><x-bi th="ตัวเลือกเสริมบางรายการจากลิงก์ใช้ไม่ได้" en="Some add-ons from the link could not be used" /></p>
                <ul class="mt-1 list-disc list-inside text-sm">
                    @foreach($addonNotices as $notice)
                        <li>{{ $notice }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($reservationInvalid))
            <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded">
                <x-bi th="รหัสจองชื่อเว็บจากหน้า MLM ไม่ถูกต้อง — สั่งซื้อต่อได้ แล้วตั้งชื่อเว็บหลังชำระเงิน หรือกลับไปจองชื่อใหม่" en="The site-name reservation from the MLM page is not valid — you can still order and pick a name after paying" />
            </div>
        @endif

        @if($isProduct)
        {{-- ===================== Product package: package + add-ons ===================== --}}
        <form action="{{ route('rental.process') }}" method="POST" id="checkoutForm"
              x-data="rentalCheckout({{ $packageSatang }}, @js($addonData))">
            @csrf
            <input type="hidden" name="package_id" value="{{ $package->id }}">
            <input type="hidden" name="promo_code" id="promoCodeInput">
            @if(!empty($reservation))
                <input type="hidden" name="mlm_reservation" value="{{ $reservation }}">
            @endif

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="md:flex">
                    <!-- Order Summary -->
                    <div class="md:w-1/3 bg-gray-50 p-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi k="common.order_summary" /></h2>

                        @if(count($cycles) > 1)
                            <div class="grid grid-cols-2 gap-2 mb-6">
                                @foreach($cycles as $c => $row)
                                    <a href="{{ route('rental.checkout', ['package' => $package->code] + $cycleQuery($c)) }}"
                                       class="text-center px-3 py-2 rounded-lg text-sm font-semibold border-2 {{ $c === $cycle ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600 hover:border-primary-300' }}">
                                        {{ $c === 'yearly' ? 'รายปี' : 'รายเดือน' }}
                                        <span class="block text-xs font-normal">{{ \App\Services\Rentals\RentalAddons::money(\App\Support\Satang::fromBaht((string) $row->price)) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-gray-900 font-semibold">{{ $package->display_name }}</span>
                                <span class="text-gray-900 whitespace-nowrap">{{ \App\Services\Rentals\RentalAddons::money($packageSatang) }}</span>
                            </div>
                            <template x-for="addon in chosen()" :key="addon.code">
                                <div class="flex justify-between gap-3">
                                    <span class="text-gray-600">
                                        <span x-text="addon.name"></span>
                                        <span x-show="addon.qty > 1" x-text="'× ' + addon.qty"></span>
                                    </span>
                                    <span class="text-gray-900 whitespace-nowrap" x-text="money(addon.unit * addon.qty)"></span>
                                </div>
                            </template>
                        </div>

                        <div class="pt-4 mt-4 border-t border-gray-200">
                            <div class="flex justify-between">
                                <span class="font-semibold text-gray-900"><x-bi th="รวมทั้งสิ้น" en="Total" /></span>
                                <span class="text-2xl font-bold text-primary-600" x-text="money(total())">{{ \App\Services\Rentals\RentalAddons::money($packageSatang) }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 text-right">
                                {{ $cycle === 'yearly' ? 'ต่อปี' : 'ต่อเดือน' }} · <x-bi th="ตัวเลือกเสริมต่ออายุพร้อมแพ็กหลัก" en="Add-ons renew with the package" />
                            </p>
                            @if($package->price_is_proposal)
                                <p class="mt-2 text-xs text-amber-600 text-right"><x-bi th="ราคาเปิดตัวรอบแรก" en="Launch pricing" /></p>
                            @endif
                        </div>

                        <!-- Promo Code -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2"><x-bi th="มีโค้ดส่วนลด?" en="Have a promo code?" /></label>
                            <div class="flex space-x-2">
                                <input type="text" id="promoCode"
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="กรอกโค้ด / Enter code">
                                <button type="button" onclick="applyPromoCode()"
                                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                    <x-bi th="ใช้" en="Apply" />
                                </button>
                            </div>
                            <p id="promoMessage" class="mt-2 text-sm hidden"></p>
                            <p class="mt-1 text-xs text-gray-500"><x-bi th="ส่วนลดจากโค้ดหักให้ในหน้าชำระเงิน" en="The code's discount is applied on the payment page" /></p>
                        </div>
                    </div>

                    <!-- Add-ons + Payment Methods -->
                    <div class="md:w-2/3 p-8">
                        @if($addonCatalogue->isNotEmpty())
                            <h2 class="text-lg font-semibold text-gray-900 mb-1"><x-bi th="ตัวเลือกเสริม" en="Add-ons" /></h2>
                            <p class="text-sm text-gray-500 mb-4"><x-bi th="เลือกตอนนี้หรือเพิ่มทีหลังก็ได้ — เพิ่มทีหลังคิดเงินตามวันที่เหลือของแพ็กหลัก" en="Pick now or later — later picks are charged for the days left" /></p>

                            <div class="space-y-3 mb-8">
                                <template x-for="addon in addons" :key="addon.code">
                                    <div class="flex items-center justify-between gap-4 p-4 border-2 rounded-lg"
                                         :class="addon.qty > 0 ? 'border-primary-500 bg-primary-50' : 'border-gray-200'">
                                        <label class="flex items-center gap-3 cursor-pointer flex-1">
                                            <input type="checkbox" class="w-5 h-5 text-primary-600 rounded"
                                                   :checked="addon.qty > 0" @change="toggle(addon, $event.target.checked)">
                                            <span>
                                                <span class="font-semibold text-gray-900" x-text="addon.name"></span>
                                                <span class="block text-sm text-gray-500" x-text="money(addon.unit) + ' / {{ $cycle === 'yearly' ? 'ปี' : 'เดือน' }}'"></span>
                                            </span>
                                        </label>
                                        <template x-if="addon.max > 1 && addon.qty > 0">
                                            <input type="number" min="1" :max="addon.max" x-model.number="addon.qty"
                                                   class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-center">
                                        </template>
                                        <input type="hidden" :name="'addons[' + addon.code + ']'" :value="addon.qty">
                                    </div>
                                </template>
                            </div>
                        @endif

                        <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi th="เลือกวิธีชำระเงิน" en="Select payment method" /></h2>

                        @include('rental.partials.payment-methods')

                        <div class="mt-8">
                            <button type="submit"
                                    class="w-full py-4 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold text-lg">
                                <x-bi k="common.proceed_to_payment" />
                            </button>
                        </div>

                        <p class="mt-4 text-sm text-gray-500 text-center">
                            <x-bi th="การดำเนินการต่อถือว่าคุณยอมรับ" en="By continuing, you agree to our" />
                            <a href="/terms" class="text-primary-600 hover:underline"><x-bi th="ข้อกำหนดการใช้งาน" en="Terms of Service" /></a>
                            <x-bi th="และ" en="and" />
                            <a href="/privacy" class="text-primary-600 hover:underline"><x-bi th="นโยบายความเป็นส่วนตัว" en="Privacy Policy" /></a>
                        </p>
                    </div>
                </div>
            </div>
        </form>
        @else
        {{-- ===================== Generic package (unchanged) ===================== --}}
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="md:flex">
                <!-- Order Summary -->
                <div class="md:w-1/3 bg-gray-50 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi k="common.order_summary" /></h2>

                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $package->display_name }}</h3>
                            <p class="text-sm text-gray-500">{{ $package->duration_text }}</p>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600"><x-bi th="ราคาปกติ" en="Regular price" /></span>
                                <span class="text-gray-900">฿{{ number_format($package->original_price ?? $package->price) }}</span>
                            </div>

                            @if($package->original_price && $package->original_price > $package->price)
                                <div class="flex justify-between text-sm mt-2">
                                    <span class="text-gray-600"><x-bi k="common.discount" /></span>
                                    <span class="text-red-600">-฿{{ number_format($package->original_price - $package->price) }}</span>
                                </div>
                            @endif

                            <div id="promoDiscount" class="flex justify-between text-sm mt-2 hidden">
                                <span class="text-gray-600"><x-bi th="โค้ดส่วนลด" en="Promo code" /></span>
                                <span class="text-red-600" id="promoDiscountAmount"></span>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex justify-between">
                                <span class="font-semibold text-gray-900"><x-bi th="รวมทั้งสิ้น" en="Total" /></span>
                                <span class="text-2xl font-bold text-primary-600" id="totalAmount">฿{{ number_format($package->price) }}</span>
                            </div>
                            <div class="text-right"><x-tpix-price variant="detail" :thb="$package->price" /></div>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><x-bi th="มีโค้ดส่วนลด?" en="Have a promo code?" /></label>
                        <div class="flex space-x-2">
                            <input type="text" id="promoCode"
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="กรอกโค้ด / Enter code">
                            <button type="button" onclick="applyPromoCode()"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                <x-bi th="ใช้" en="Apply" />
                            </button>
                        </div>
                        <p id="promoMessage" class="mt-2 text-sm hidden"></p>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="md:w-2/3 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi th="เลือกวิธีชำระเงิน" en="Select payment method" /></h2>

                    <form action="{{ route('rental.process') }}" method="POST" id="checkoutForm">
                        @csrf
                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                        <input type="hidden" name="promo_code" id="promoCodeInput">

                        @include('rental.partials.payment-methods')

                        <div class="mt-8">
                            <button type="submit"
                                    class="w-full py-4 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold text-lg">
                                <x-bi k="common.proceed_to_payment" />
                            </button>
                        </div>

                        <p class="mt-4 text-sm text-gray-500 text-center">
                            <x-bi th="การดำเนินการต่อถือว่าคุณยอมรับ" en="By continuing, you agree to our" />
                            <a href="/terms" class="text-primary-600 hover:underline"><x-bi th="ข้อกำหนดการใช้งาน" en="Terms of Service" /></a>
                            <x-bi th="และ" en="and" />
                            <a href="/privacy" class="text-primary-600 hover:underline"><x-bi th="นโยบายความเป็นส่วนตัว" en="Privacy Policy" /></a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    const originalPrice = {{ $package->price }};
    let currentDiscount = 0;

    // Product checkout: package + add-ons, totals in satang (whole numbers — no float money).
    function rentalCheckout(packageSatang, addons) {
        return {
            addons: addons,
            toggle(addon, on) {
                if (on && addon.group) {
                    // Member caps and other grouped add-ons: one per rental.
                    this.addons.forEach(other => { if (other !== addon && other.group === addon.group) other.qty = 0; });
                }
                addon.qty = on ? Math.max(1, addon.qty) : 0;
            },
            chosen() { return this.addons.filter(a => a.qty > 0); },
            total() {
                return this.chosen().reduce((sum, a) => sum + a.unit * Math.min(Math.max(1, a.qty | 0), a.max), packageSatang);
            },
            money(satang) {
                const baht = Math.floor(satang / 100), st = satang % 100;
                return '฿' + baht.toLocaleString('th-TH') + (st ? '.' + String(st).padStart(2, '0') : '');
            },
        };
    }

    function applyPromoCode() {
        const code = document.getElementById('promoCode').value.trim();
        if (!code) return;

        fetch('{{ route("rental.validate-promo") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                code: code,
                package_id: {{ $package->id }}
            })
        })
        .then(response => response.json())
        .then(data => {
            const msgEl = document.getElementById('promoMessage');
            msgEl.classList.remove('hidden', 'text-green-600', 'text-red-600');

            if (data.valid) {
                msgEl.textContent = data.message;
                msgEl.classList.add('text-green-600');
                currentDiscount = Number(data.discount_amount) || 0;
                document.getElementById('promoCodeInput').value = code;
                if (currentDiscount > 0 && document.getElementById('promoDiscount')) {
                    document.getElementById('promoDiscount').classList.remove('hidden');
                    document.getElementById('promoDiscountAmount').textContent = '-฿' + numberFormat(currentDiscount);
                }
            } else {
                msgEl.textContent = data.message;
                msgEl.classList.add('text-red-600');
                currentDiscount = 0;
                document.getElementById('promoCodeInput').value = '';
                document.getElementById('promoDiscount')?.classList.add('hidden');
            }

            updateTotal();
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function updateTotal() {
        const el = document.getElementById('totalAmount');
        if (!el) return;   // product checkout keeps its own total
        const total = Math.max(0, originalPrice - currentDiscount);
        el.textContent = '฿' + numberFormat(total);
    }

    function numberFormat(num) {
        return num.toLocaleString('th-TH');
    }
</script>
@endsection
