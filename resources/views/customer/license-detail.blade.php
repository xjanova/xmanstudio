@extends($customerLayout ?? 'layouts.customer')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('autotradex'); @endphp

@section('title', 'License Details - ' . ($license->product?->name ?? 'License'))
@section('page-title', 'License Details')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.licenses') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center font-medium transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Licenses
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main License Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- License Card with Premium Header -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-violet-600 to-pink-600 p-6">
                <x-page-art art="hero-licenses" :opacity="14" :scrim="false" />
                <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob"></div>
                <div class="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob animation-delay-2000"></div>
                <div class="absolute -bottom-8 left-20 w-72 h-72 bg-violet-400 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob animation-delay-4000"></div>

                <div class="relative flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ $license->product?->name ?? 'Software License' }}</h2>
                        <p class="text-purple-200 mt-1">License Key</p>
                    </div>
                    @php
                        $statusGradients = [
                            'active' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                            'expired' => 'bg-gradient-to-r from-red-400 to-rose-500 text-white',
                            'revoked' => 'bg-white/20 text-white',
                        ];
                    @endphp
                    {{-- What the key can do now: an "active" row past its expiry is expired to the app --}}
                    @php $shownStatus = $license->status === 'active' && $license->isExpired() ? 'expired' : $license->status; @endphp
                    <span class="px-3 py-1 text-sm font-semibold rounded-full shadow {{ $statusGradients[$shownStatus] ?? 'bg-white/20 text-white' }}">
                        {{ ucfirst($shownStatus) }}
                    </span>
                </div>
            </div>

            <div class="p-6">
                <!-- License Key Display -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-4 mb-6 border border-gray-200 dark:border-gray-600">
                    <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">License Key</label>
                    <div class="flex items-center">
                        <code class="flex-1 text-lg font-mono bg-white dark:bg-gray-800 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 break-all text-gray-900 dark:text-white">{{ $license->license_key }}</code>
                        <button onclick="copyLicenseKey()" class="ml-3 px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl hover:from-purple-600 hover:to-pink-600 transition-all shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- License Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">License Type</label>
                        @php
                            $typeGradients = [
                                'lifetime' => 'bg-gradient-to-r from-purple-400 to-violet-500 text-white',
                                'yearly' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                'monthly' => 'bg-gradient-to-r from-gray-400 to-gray-500 text-white',
                                'demo' => 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white',
                            ];
                        @endphp
                        <span class="px-3 py-1 text-sm font-semibold rounded-full shadow {{ $typeGradients[$license->license_type] ?? 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($license->license_type) }}
                        </span>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Created Date</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $license->created_at->format('d M Y H:i') }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Expires</label>
                        @if($license->expires_at)
                            <span class="{{ $license->expires_at->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} font-medium">
                                {{ $license->expires_at->format('d M Y') }}
                            </span>
                            @if(!$license->expires_at->isPast())
                                <span class="text-sm text-gray-500 dark:text-gray-400 block">({{ $license->expires_at->diffForHumans() }})</span>
                            @else
                                <span class="text-sm text-red-500 dark:text-red-400 block">(Expired)</span>
                            @endif
                        @else
                            <span class="text-green-600 dark:text-green-400 font-semibold">Never (Lifetime)</span>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Activations</label>
                        <p class="text-gray-900 dark:text-white font-medium">
                            {{ $license->activation_count }} / {{ $license->max_activations ?? '∞' }}
                        </p>
                    </div>

                    @if($cloudStorage ?? null)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Cloud space · พื้นที่คลาวด์</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $cloudStorage['total'] }} GB</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" data-cloud-breakdown>
                            <x-bi th="แพ็กเกจ" en="Plan" /> {{ $cloudStorage['base'] }} GB
                            @if($cloudStorage['purchased'] > 0) + <x-bi th="ซื้อเพิ่ม" en="add-ons" /> {{ $cloudStorage['purchased'] }} GB @endif
                            @if($cloudStorage['bonus'] > 0) + <x-bi th="จากทีมงาน" en="from our team" /> {{ $cloudStorage['bonus'] }} GB @endif
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Machine Activation -->
        @if($license->machine_id)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                Activated Machine
            </h3>
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Machine ID</p>
                        <code class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $license->machine_id }}</code>
                    </div>
                    <span class="px-3 py-1 bg-gradient-to-r from-green-400 to-emerald-500 text-white text-xs font-semibold rounded-full shadow">Active</span>
                </div>
                @if($license->activated_at)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Activated: {{ $license->activated_at->format('d M Y H:i') }}
                </p>
                @endif
            </div>

            <div class="mt-4 p-4 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200 dark:border-yellow-700">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Need to change machine?</strong> Contact support to reset your license activation.
                </p>
            </div>
        </div>
        @endif

        <!-- Activation History -->
        @if($license->activations()->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                Activation History
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Machine ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($license->activations()->get() as $activation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-400">{{ Str::limit($activation->machine_id, 20) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $activation->ip_address ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $activation->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $activation->is_active ? 'bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow' : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300' }}">
                                    {{ $activation->is_active ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        @if($renewal)
        {{-- Renew this key: one more term on the SAME key, so nothing is re-entered in the app.
             Wallet = one click; other methods go through the cart; auto-renew charges the wallet
             before expiry (licenses:renew). --}}
        @php
            $termLabels = ['daily' => '1 วัน', 'weekly' => '1 สัปดาห์', 'monthly' => '1 เดือน', 'yearly' => '1 ปี'];
            $walletShort = $walletBalance < $renewal['total'];
            $renewConfirm = 'ยืนยันต่ออายุ ' . ($termLabels[$renewal['term']] ?? '') . ' — ตัดเงินจาก Wallet ฿' . number_format($renewal['total'], 2) . ' ?';
        @endphp
        <div id="renew" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border-2 border-violet-200 dark:border-violet-700/60 scroll-mt-24">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <x-bi th="ต่ออายุคีย์นี้" en="Renew this key" />
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                <x-bi th="ใช้คีย์เดิม เวลาบวกต่อจากวันหมดอายุ ไม่ต้องกรอกคีย์ใหม่ในโปรแกรม" en="Same key, time adds on from its expiry — nothing to re-enter in the app" />
            </p>

            <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-4 space-y-2 text-sm mb-4">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="ระยะเวลา" en="Term" /></span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $termLabels[$renewal['term']] ?? $renewal['term'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="ราคา (รวม VAT)" en="Price (VAT incl.)" /></span>
                    <span class="font-bold text-violet-600 dark:text-violet-400">฿{{ number_format($renewal['total'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="หมดอายุใหม่" en="New expiry" /></span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $renewal['new_expires_at']->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                    <span class="text-gray-500 dark:text-gray-400"><x-bi th="ยอดใน Wallet" en="Wallet balance" /></span>
                    <span class="font-semibold {{ $walletShort ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">฿{{ number_format($walletBalance, 2) }}</span>
                </div>
            </div>

            <div class="space-y-3">
                @if($walletShort)
                    <a href="{{ route('user.wallet.topup') }}"
                       class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl hover:from-violet-700 hover:to-purple-700 font-semibold shadow-lg transition-all">
                        <x-bi th="เติมเงินเข้า Wallet" en="Top up wallet" class="text-white" />
                    </a>
                    <p class="text-xs text-center text-red-600 dark:text-red-400">
                        ขาดอีก ฿{{ number_format($renewal['total'] - $walletBalance, 2) }}
                    </p>
                @else
                    <form action="{{ route('customer.licenses.renew', $license) }}" method="POST"
                          x-data="{ busy: false }"
                          x-on:submit="if (busy || !confirm({{ \Illuminate\Support\Js::from($renewConfirm) }})) { $event.preventDefault(); return; } busy = true">
                        @csrf
                        <input type="hidden" name="expected_expiry" value="{{ $expiryStamp }}">
                        <button type="submit" x-bind:disabled="busy"
                                class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl hover:from-violet-700 hover:to-purple-700 font-semibold shadow-lg transition-all disabled:opacity-60 disabled:cursor-wait">
                            <span x-show="!busy"><x-bi th="ต่ออายุด้วย Wallet" en="Renew with wallet" class="text-white" /></span>
                            <span x-show="busy" x-cloak><x-bi th="กำลังต่ออายุ…" en="Renewing…" class="text-white" /></span>
                        </button>
                    </form>
                @endif

                {{-- Any other payment method (transfer, PromptPay, card): the cart, naming this key --}}
                <form action="{{ route('cart.add', $license->product) }}" method="POST">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="license_type" value="{{ $renewal['term'] }}">
                    <input type="hidden" name="renew_license" value="{{ $license->id }}">
                    <input type="hidden" name="buy_now" value="1">
                    <button type="submit"
                            class="w-full flex items-center justify-center px-4 py-2.5 border border-violet-300 dark:border-violet-600 text-violet-700 dark:text-violet-300 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 font-medium transition-all text-sm">
                        <x-bi th="ชำระช่องทางอื่น (โอน / พร้อมเพย์ / บัตร)" en="Other payment (transfer / PromptPay / card)" />
                    </button>
                </form>
            </div>

            {{-- Auto-renew switch --}}
            <form action="{{ route('customer.licenses.auto-renew', $license) }}" method="POST"
                  class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                @csrf
                <input type="hidden" name="auto_renew" value="{{ $license->auto_renew ? 0 : 1 }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm"><x-bi th="ต่ออายุอัตโนมัติ" en="Auto-renew" /></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ตัดเงินจาก Wallet เมื่อเหลือ {{ \App\Services\LicenseRenewalService::CHARGE_DAYS }} วันก่อนหมดอายุ
                            · แจ้งทางอีเมลล่วงหน้า {{ \App\Services\LicenseRenewalService::NOTICE_DAYS }} วัน · ปิดได้ทุกเมื่อ
                        </p>
                    </div>
                    <button type="submit" role="switch" aria-checked="{{ $license->auto_renew ? 'true' : 'false' }}"
                            aria-label="{{ $license->auto_renew ? 'ปิดต่ออายุอัตโนมัติ' : 'เปิดต่ออายุอัตโนมัติ' }}"
                            class="relative inline-flex h-7 w-12 flex-shrink-0 items-center rounded-full transition-colors {{ $license->auto_renew ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform {{ $license->auto_renew ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                @if($license->auto_renew)
                    <p class="mt-2 text-xs font-medium text-green-600 dark:text-green-400">
                        <x-bi th="เปิดอยู่ — ไม่ต้องทำอะไรเพิ่ม" en="On — nothing else to do" />
                    </p>
                @endif
            </form>
        </div>
        @elseif(in_array($license->license_type, ['daily', 'weekly', 'monthly', 'yearly'], true) && $license->status !== 'revoked')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-5 border border-gray-100 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300">
            <x-bi th="คีย์นี้ยังต่ออายุออนไลน์ไม่ได้ (สินค้าปิดขายหรือไม่มีแพ็กเกจต่ออายุ) — ติดต่อทีมงานเพื่อต่ออายุ" en="This key cannot be renewed online yet — contact support to renew it" />
        </div>
        @endif

        <!-- QR Code for App Scan -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center justify-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900/30 dark:to-purple-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                </div>
                Scan to Activate
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"><x-bi th="สแกน QR Code ในแอพเพื่อกรอก License Key อัตโนมัติ" en="Scan the QR code in the app to fill the License Key automatically" /></p>
            <div class="bg-white p-3 rounded-xl inline-block shadow-inner border border-gray-200">
                <div id="license-qrcode"></div>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-3"><x-bi th="เปิดแอพ Tping → กดสแกน QR Code" en="Open the Tping app → tap Scan QR Code" /></p>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                Quick Actions
            </h3>
            <div class="space-y-3">

                @if(! empty($cloudStorage['packs']))
                {{-- Cloud space packs for THIS key (config/licenses.php → cloud_storage). Paid once, the
                     space stays with the key through every renewal. The price comes from the cart. --}}
                <div class="rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 p-4" data-cloud-packs>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        <x-bi th="เพิ่มพื้นที่คลาวด์" en="Add cloud space" />
                    </p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($cloudStorage['packs'] as $gb => $price)
                        <form action="{{ route('cart.add', $cloudStorage['addonProduct']) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="storage_gb" value="{{ $gb }}">
                            <input type="hidden" name="license" value="{{ $license->id }}">
                            <input type="hidden" name="buy_now" value="1">
                            <button type="submit"
                                    class="w-full px-2 py-2 rounded-lg bg-white dark:bg-gray-800 border border-sky-300 dark:border-sky-700 hover:bg-sky-100 dark:hover:bg-sky-900/40 text-center transition">
                                <span class="block font-bold text-sky-700 dark:text-sky-300">+{{ $gb }} GB</span>
                                <span class="block text-xs text-gray-600 dark:text-gray-300">฿{{ number_format($price) }}</span>
                            </button>
                        </form>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                        <x-bi th="จ่ายครั้งเดียว พื้นที่อยู่กับคีย์นี้ตลอด ต่ออายุกี่ครั้งก็ยังอยู่" en="Paid once — the space stays with this key through every renewal" />
                    </p>
                </div>
                @endif

                @if($license->product && $license->product->slug === 'autotradex')
                {{-- ไฟล์ส่งจาก xman4289.com เอง ห้ามลิงก์ไป GitHub (กฎเจ้าของ 2026-09-24) --}}
                @if($csDownload)
                    <span class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-500 via-violet-500 to-pink-500 text-white rounded-xl hover:from-purple-600 hover:via-violet-600 hover:to-pink-600 font-semibold shadow-lg transition-all" style="cursor: not-allowed; opacity: .8;" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
                @else
                <a href="{{ route('autotradex.download') }}"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-500 via-violet-500 to-pink-500 text-white rounded-xl hover:from-purple-600 hover:via-violet-600 hover:to-pink-600 font-semibold shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Software
                </a>
                @endif
                @endif

                @if($license->product && in_array($license->product->slug, \App\Models\Product::BRAINX_SLUGS, true))
                {{-- The holder of a BrainX key gets the installer even while BrainX is Coming Soon (owner, 2026-10-19) --}}
                <a href="{{ route('brainx.download') }}" rel="nofollow"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-xl hover:from-violet-700 hover:to-indigo-700 font-semibold shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <x-bi th="ดาวน์โหลด BrainX สำหรับ Windows" en="Download BrainX for Windows" class="text-white" />
                </a>
                @endif

                @if($license->product && $license->product->slug === 'anti-x')
                {{-- ไฟล์ส่งจาก xman4289.com เอง ห้ามลิงก์ไป GitHub (กฎเจ้าของ 2026-09-24) · Coming Soon = ไม่มีลิงก์ --}}
                @if($antiXDownload = $license->product->downloadUrl())
                <a href="{{ $antiXDownload }}" data-antix-download
                   class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 font-semibold shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <x-bi th="ดาวน์โหลด Anti X (Windows)" en="Download Anti X (Windows)" class="text-white" />
                </a>
                @endif
                @if(! empty($mobileOffer))
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 p-4" data-mobile-offer>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        <x-bi th="แอปมือถือ Anti X (Android) — ฟรี" en="Anti X mobile app (Android) — free" />
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        <x-bi th="ดูภาพรวม บล็อก/ปลดบล็อก และรับแจ้งเตือนบนมือถือ — ไลเซนส์ฟรีออกให้มือถือเองตอนเปิดครั้งแรก" en="Overview, block/unblock and alerts on your phone — its free licence is issued on first start" />
                        @if($mobileOffer['version']) · v{{ $mobileOffer['version']->version }}@if($mobileOffer['version']->file_size) · {{ $mobileOffer['version']->file_size_formatted }}@endif @endif
                    </p>
                    @if($mobileOffer['url'])
                    <a href="{{ $mobileOffer['url'] }}" data-mobile-download
                       class="mt-3 w-full flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl hover:from-emerald-600 hover:to-green-700 font-semibold transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <x-bi th="ดาวน์โหลดแอปมือถือ" en="Download the mobile app" class="text-white" />
                    </a>
                    @else
                    <span class="mt-3 block text-center text-sm text-gray-500 dark:text-gray-400" data-coming-soon>เร็วๆ นี้</span>
                    @endif
                </div>
                @endif
                @endif

                <a href="{{ route('customer.downloads') }}"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Center
                </a>

                <a href="{{ route('customer.support.create') }}"
                   class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 font-medium transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>

        <!-- License Features -->
        @if($license->product && $license->product->slug === 'autotradex')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                License Features
            </h3>
            <ul class="space-y-3">
                @php
                    $features = [
                        'demo' => ['Simulation Mode', '1 Exchange', 'Basic Alerts'],
                        'monthly' => ['Live Trading', '3 Exchanges', 'P&L Tracking', 'Basic Arbitrage'],
                        'yearly' => ['Live Trading', '5 Exchanges', 'Advanced Arbitrage', 'Auto Rebalance', 'Priority Support'],
                        'lifetime' => ['All Features', '6 Exchanges', 'Triangular Arbitrage', 'API Access', 'Lifetime Updates'],
                    ];
                    $licenseFeatures = $features[$license->license_type] ?? $features['monthly'];
                @endphp
                @foreach($licenseFeatures as $feature)
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 flex items-center justify-center mr-3 shadow">
                        <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            @if($license->license_type !== 'lifetime' && $license->status === 'active')
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('autotradex.pricing') }}" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium flex items-center transition-colors">
                    Upgrade to Lifetime
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            @endif
        </div>
        @endif

        <!-- Help Card -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-700">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mb-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Need Help?</h3>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                If you have any issues with your license, our support team is here to help.
            </p>
            <a href="{{ route('customer.support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold flex items-center transition-colors">
                Open a Support Ticket
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function copyLicenseKey() {
    const licenseKey = '{{ $license->license_key }}';
    navigator.clipboard.writeText(licenseKey).then(function() {
        alert('License key copied to clipboard!');
    }).catch(function(err) {
        console.error('Failed to copy:', err);
    });
}

// Generate QR Code
document.addEventListener('DOMContentLoaded', function() {
    const qrEl = document.getElementById('license-qrcode');
    if (qrEl) {
        new QRCode(qrEl, {
            text: 'tping://activate?key={{ $license->license_key }}',
            width: 180,
            height: 180,
            colorDark: '#4c1d95',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
@endpush
@endsection
