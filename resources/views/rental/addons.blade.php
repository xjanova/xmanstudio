@extends($publicLayout ?? 'layouts.app')

@section('title', 'เพิ่มตัวเลือกเสริม - ' . $package->display_name . ' - XMAN Studio')

@php
    $money = fn (int $satang) => \App\Services\Rentals\RentalAddons::money($satang);
    $per = $cycle === 'yearly' ? 'ปี' : 'เดือน';
    // Each add-on: price for one unit for the days left today, and whether it can still be bought.
    $addonData = $catalogue->map(function ($addon) use ($cycle, $daysLeft, $cycleDays, $held, $heldGroups, $selected) {
        $unitCycle = $addon->cyclePriceSatang($cycle);
        $has = (int) ($held[$addon->code] ?? 0);
        $max = max(1, (int) $addon->max_quantity);
        $blockedByGroup = $addon->exclusive_group && isset($heldGroups[$addon->exclusive_group]) && $heldGroups[$addon->exclusive_group] !== $addon->code;

        return [
            'code' => $addon->code,
            'name' => $addon->display_name,
            'unitCycle' => $unitCycle,
            // Per-unit prorated price — the total is computed the same way as the server: floor(price × qty × days ÷ cycle)
            'max' => max(0, $max - $has),
            'has' => $has,
            'group' => $addon->exclusive_group,
            'blocked' => $blockedByGroup || $has >= $max,
            'qty' => $blockedByGroup || $has >= $max ? 0 : min((int) ($selected[$addon->code] ?? 0), $max - $has),
        ];
    })->values();
@endphp

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 px-6 py-8 mb-8">
            <x-page-art art="hero-payment" :opacity="14" :scrim="false" fade="bottom" />
            <div class="relative">
                <nav class="mb-3">
                    <ol class="flex items-center space-x-2 text-sm text-gray-300">
                        <li><a href="{{ route('rental.status') }}" class="hover:text-white"><x-bi th="สถานะการเช่า" en="Rental status" /></a></li>
                        <li><span>/</span></li>
                        <li class="text-white"><x-bi th="ตัวเลือกเสริม" en="Add-ons" /></li>
                    </ol>
                </nav>
                <h1 class="text-2xl md:text-3xl font-bold text-white"><x-bi th="เพิ่มตัวเลือกเสริม" en="Add add-ons" /></h1>
                <p class="mt-1 text-gray-300">{{ $package->display_name }}</p>
            </div>
        </div>

        @foreach(['error' => 'bg-red-100 border-red-400 text-red-700', 'warning' => 'bg-amber-50 border-amber-300 text-amber-800'] as $key => $classes)
            @if(session($key))
                <div class="mb-6 border px-4 py-3 rounded {{ $classes }}">{{ session($key) }}</div>
            @endif
        @endforeach

        <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded text-sm">
            <x-bi th="คิดเงินตามวันที่เหลือของแพ็กหลัก" en="Charged for the days left on your package" />:
            <strong>{{ $daysLeft }}</strong> / {{ $cycleDays }} <x-bi th="วัน" en="days" />
            (<x-bi th="หมดอายุ" en="expires" /> {{ $rental->expires_at->copy()->timezone('Asia/Bangkok')->format('d/m/Y') }}).
            <x-bi th="ราคานี้ใช้ได้ถึงสิ้นวันนี้ ถ้าชำระวันอื่นระบบคิดใหม่ตามวันที่เหลือของวันนั้น จากนั้นต่ออายุพร้อมแพ็กหลักในราคาเต็ม" en="This price holds until the end of today; after that it renews with your package at full price" />
        </div>

        <form action="{{ route('rental.addons.process', $rental) }}" method="POST"
              class="bg-white rounded-2xl shadow-xl overflow-hidden"
              x-data="addonPurchase(@js($addonData), {{ $daysLeft }}, {{ $cycleDays }})">
            @csrf
            <div class="md:flex">
                <div class="md:w-1/3 bg-gray-50 p-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi k="common.order_summary" /></h2>
                    <div class="space-y-3 text-sm">
                        <template x-for="addon in chosen()" :key="addon.code">
                            <div class="flex justify-between gap-3">
                                <span class="text-gray-600"><span x-text="addon.name"></span><span x-show="addon.qty > 1" x-text="' × ' + addon.qty"></span></span>
                                <span class="text-gray-900 whitespace-nowrap" x-text="money(prorate(addon))"></span>
                            </div>
                        </template>
                        <p x-show="chosen().length === 0" class="text-gray-500"><x-bi th="ยังไม่ได้เลือก" en="Nothing picked yet" /></p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-gray-200">
                        <div class="flex justify-between">
                            <span class="font-semibold text-gray-900"><x-bi th="ชำระวันนี้" en="Due today" /></span>
                            <span class="text-2xl font-bold text-primary-600" x-text="money(total())"></span>
                        </div>
                    </div>
                </div>

                <div class="md:w-2/3 p-8">
                    <div class="space-y-3 mb-8">
                        <template x-for="addon in addons" :key="addon.code">
                            <div class="flex items-center justify-between gap-4 p-4 border-2 rounded-lg"
                                 :class="addon.blocked ? 'border-gray-100 bg-gray-50 opacity-70' : (addon.qty > 0 ? 'border-primary-500 bg-primary-50' : 'border-gray-200')">
                                <label class="flex items-center gap-3 flex-1" :class="addon.blocked ? '' : 'cursor-pointer'">
                                    <input type="checkbox" class="w-5 h-5 text-primary-600 rounded" :disabled="addon.blocked"
                                           :checked="addon.qty > 0" @change="toggle(addon, $event.target.checked)">
                                    <span>
                                        <span class="font-semibold text-gray-900" x-text="addon.name"></span>
                                        <span class="block text-sm text-gray-500">
                                            <span x-text="money(addon.unitCycle) + ' / {{ $per }}'"></span>
                                            · <x-bi th="วันนี้" en="today" /> <span x-text="money(prorate({ unitCycle: addon.unitCycle, qty: 1 }))"></span>
                                        </span>
                                        <span x-show="addon.has > 0" class="block text-xs text-green-700"><x-bi th="มีอยู่แล้ว" en="Already have" /> <span x-text="addon.has"></span></span>
                                        <span x-show="addon.blocked && addon.has === 0" class="block text-xs text-gray-500"><x-bi th="ใช้ระดับอื่นของกลุ่มนี้อยู่ — ติดต่อทีมงานเพื่อเปลี่ยนระดับ" en="Another tier of this group is in use — contact us to switch" /></span>
                                    </span>
                                </label>
                                <template x-if="addon.max > 1 && addon.qty > 0">
                                    <input type="number" min="1" :max="addon.max" x-model.number="addon.qty"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-center">
                                </template>
                                <input type="hidden" :name="'addons[' + addon.code + ']'" :value="addon.blocked ? 0 : addon.qty">
                            </div>
                        </template>
                    </div>

                    <h2 class="text-lg font-semibold text-gray-900 mb-6"><x-bi th="เลือกวิธีชำระเงิน" en="Select payment method" /></h2>
                    @include('rental.partials.payment-methods')

                    <div class="mt-8">
                        <button type="submit" :disabled="chosen().length === 0"
                                class="w-full py-4 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors font-semibold text-lg">
                            <x-bi k="common.proceed_to_payment" />
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Same arithmetic as RentalAddonPricing::prorate — integers in satang, floor.
    function addonPurchase(addons, daysLeft, cycleDays) {
        return {
            addons: addons,
            toggle(addon, on) {
                if (on && addon.group) {
                    this.addons.forEach(other => { if (other !== addon && other.group === addon.group) other.qty = 0; });
                }
                addon.qty = on ? Math.max(1, addon.qty) : 0;
            },
            chosen() { return this.addons.filter(a => !a.blocked && a.qty > 0); },
            prorate(addon) {
                const qty = Math.max(1, addon.qty | 0);
                const days = Math.min(daysLeft, cycleDays);
                return cycleDays > 0 ? Math.floor(addon.unitCycle * qty * days / cycleDays) : 0;
            },
            total() { return this.chosen().reduce((sum, a) => sum + this.prorate(a), 0); },
            money(satang) {
                const baht = Math.floor(satang / 100), st = satang % 100;
                return '฿' + baht.toLocaleString('th-TH') + (st ? '.' + String(st).padStart(2, '0') : '');
            },
        };
    }
</script>
@endsection
