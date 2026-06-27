@extends($customerLayout ?? 'layouts.customer')

@section('title', 'Affiliate Dashboard')
@section('page-title')<x-bi th="พันธมิตร (Affiliate)" en="Affiliate" />@endsection
@section('page-description')<x-bi th="แนะนำเพื่อนรับค่าคอมมิชชั่นเข้า Wallet" en="Refer friends and earn commission into your Wallet" />@endsection

@section('content')
@if(!$affiliate)
    {{-- ยังไม่ได้สมัคร Affiliate --}}
    <div class="max-w-2xl mx-auto text-center py-16">
        <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-pink-500 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg shadow-pink-500/30">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-3"><x-bi th="เข้าร่วมโปรแกรม Affiliate" en="Join the Affiliate Program" /></h2>
        <p class="text-gray-600 mb-8 max-w-md mx-auto"><x-bi th="แนะนำเพื่อนมาใช้บริการ เมื่อเพื่อนซื้อสินค้าหรือบริการ คุณจะได้รับค่าแนะนำ" en="Refer friends to our services. When they buy a product or service, you instantly earn a" /> <span class="font-bold text-pink-600">10%</span> <x-bi th="เข้า Wallet ทันที" en="referral reward into your Wallet" /></p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="text-3xl mb-2">1️⃣</div>
                <h3 class="font-semibold text-gray-900"><x-bi th="สมัคร Affiliate" en="Sign up as an Affiliate" /></h3>
                <p class="text-sm text-gray-500 mt-1"><x-bi th="รับลิงก์แนะนำส่วนตัว" en="Get your personal referral link" /></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="text-3xl mb-2">2️⃣</div>
                <h3 class="font-semibold text-gray-900"><x-bi th="แชร์ลิงก์" en="Share your link" /></h3>
                <p class="text-sm text-gray-500 mt-1"><x-bi th="ส่งให้เพื่อนหรือโพสต์" en="Send it to friends or post it online" /></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <div class="text-3xl mb-2">3️⃣</div>
                <h3 class="font-semibold text-gray-900"><x-bi th="รับเงิน" en="Get paid" /></h3>
                <p class="text-sm text-gray-500 mt-1"><x-bi th="ค่าแนะนำเข้า Wallet" en="Referral rewards land in your Wallet" /></p>
            </div>
        </div>

        <form action="{{ route('customer.affiliate.register') }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-pink-500 to-rose-500 text-white font-semibold rounded-xl shadow-lg shadow-pink-500/30 hover:shadow-xl hover:shadow-pink-500/40 transition-all hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <x-bi th="สมัครเป็น Affiliate ฟรี" en="Sign up as an Affiliate for free" />
            </button>
        </form>
    </div>
@else
    {{-- Affiliate Dashboard --}}
    {{-- Header Banner --}}
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-red-500 rounded-2xl p-6 mb-6 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -right-8 -top-8 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute right-20 bottom-0 w-24 h-24 bg-white rounded-full"></div>
        </div>
        <div class="relative">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        Affiliate Dashboard
                        <span class="text-xs px-2 py-1 rounded-full bg-white/20">{{ $affiliate->status_label }}</span>
                    </h2>
                    <p class="text-pink-100 text-sm mt-1"><x-bi th="ค่าคอมมิชชั่น" en="Commission" /> {{ number_format($affiliate->commission_rate) }}% <x-bi th="ของยอดขาย" en="of sales" /></p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="bg-white/10 backdrop-blur rounded-xl px-4 py-2 flex items-center gap-3">
                        <span class="text-sm text-pink-100"><x-bi th="ลิงก์แนะนำ" en="Referral link" />:</span>
                        <code class="text-sm font-mono bg-white/20 px-2 py-1 rounded" id="refLink">{{ $affiliate->referral_url }}</code>
                        <button onclick="copyToClipboard('{{ $affiliate->referral_url }}', 'คัดลอกลิงก์แล้ว! / Link copied!')" class="p-1 hover:bg-white/20 rounded transition-colors" title="คัดลอก / Copy">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        {{-- Total Earned --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500"><x-bi th="รายได้ทั้งหมด" en="Total earned" /></span>
            </div>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($affiliate->total_earned) }}</p>
        </div>

        {{-- Total Paid --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500"><x-bi th="จ่ายเข้า Wallet แล้ว" en="Paid to Wallet" /></span>
            </div>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($affiliate->total_paid) }}</p>
        </div>

        {{-- Pending --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500"><x-bi th="รอตรวจสอบ" en="Pending review" /></span>
            </div>
            <p class="text-2xl font-bold text-gray-900">฿{{ number_format($affiliate->total_pending) }}</p>
        </div>

        {{-- Clicks / Conversions --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500"><x-bi th="คลิก / ขาย" en="Clicks / Sales" /></span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($affiliate->total_clicks) }} / {{ number_format($affiliate->total_conversions) }}</p>
            <p class="text-xs text-gray-400 mt-1">Conversion {{ $affiliate->conversion_rate }}%</p>
        </div>

        {{-- Downline Count --}}
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500"><x-bi th="ลูกทีม" en="Downline" /></span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($downlineCount) }}</p>
            @if($downlineCount > 0)
                <a href="{{ route('customer.affiliate.downline') }}" class="text-xs text-indigo-600 hover:text-indigo-700 mt-1 inline-block"><x-bi th="ดูทั้งหมด" en="View all" /> &rarr;</a>
            @endif
        </div>
    </div>

    {{-- Referral Code Card --}}
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 mb-6">
        <h3 class="font-semibold text-gray-900 mb-4"><x-bi th="ลิงก์แนะนำของคุณ" en="Your referral link" /></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-gray-500 mb-1 block">Referral Code</label>
                <div class="flex items-center gap-2">
                    <input type="text" value="{{ $affiliate->referral_code }}" readonly class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg font-mono text-lg tracking-wider">
                    <button onclick="copyToClipboard('{{ $affiliate->referral_code }}', 'คัดลอกโค้ดแล้ว! / Code copied!')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="text-sm text-gray-500 mb-1 block"><x-bi th="ลิงก์แนะนำ (URL)" en="Referral link (URL)" /></label>
                <div class="flex items-center gap-2">
                    <input type="text" value="{{ $affiliate->referral_url }}" readonly class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm truncate">
                    <button onclick="copyToClipboard('{{ $affiliate->referral_url }}', 'คัดลอกลิงก์แล้ว! / Link copied!')" class="px-3 py-2 bg-pink-500 hover:bg-pink-600 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3"><x-bi th="เมื่อเพื่อนเปิดลิงก์นี้แล้วซื้อสินค้าหรือบริการภายใน 30 วัน คุณจะได้รับค่าแนะนำ" en="When a friend opens this link and buys a product or service within 30 days, you automatically earn a" /> {{ number_format($affiliate->commission_rate) }}% <x-bi th="เข้า Wallet อัตโนมัติ" en="reward into your Wallet" /></p>
    </div>

    {{-- Recent Commissions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900"><x-bi th="ค่าคอมมิชชั่นล่าสุด" en="Recent commissions" /></h3>
            @if($recentCommissions->count() > 0)
                <a href="{{ route('customer.affiliate.commissions') }}" class="text-sm text-pink-600 hover:text-pink-700 font-medium"><x-bi th="ดูทั้งหมด" en="View all" /></a>
            @endif
        </div>

        @if($recentCommissions->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="วันที่" en="Date" /></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="ประเภท" en="Type" /></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="รายละเอียด" en="Details" /></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="ผู้ซื้อ" en="Buyer" /></th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><x-bi th="ยอด" en="Amount" /></th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><x-bi th="คอมมิชชั่น" en="Commission" /></th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><x-bi th="สถานะ" en="Status" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentCommissions as $commission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $commission->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3">
                                    @php
                                        $typeColors = [
                                            'tping' => 'bg-purple-100 text-purple-800',
                                            'order' => 'bg-blue-100 text-blue-800',
                                            'rental_payment' => 'bg-orange-100 text-orange-800',
                                            'autotradex' => 'bg-cyan-100 text-cyan-800',
                                        ];
                                        $typeColor = $typeColors[$commission->source_type ?? ''] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColor }}">{{ $commission->source_label }}</span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $commission->source_description ?: ($commission->order->order_number ?? '-') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $commission->referredUser->name ?? '-' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700 text-right">฿{{ number_format($commission->order_amount) }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-green-600 text-right">+฿{{ number_format($commission->commission_amount) }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $commission->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $commission->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                    ">{{ $commission->status_label }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p><x-bi th="ยังไม่มีค่าคอมมิชชั่น" en="No commissions yet" /></p>
                <p class="text-sm mt-1"><x-bi th="แชร์ลิงก์แนะนำของคุณเพื่อเริ่มรับรายได้" en="Share your referral link to start earning" /></p>
            </div>
        @endif
    </div>

    {{-- Monthly Stats --}}
    @if($monthlyStats->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mt-6 p-6">
        <h3 class="font-semibold text-gray-900 mb-4"><x-bi th="สรุปรายเดือน (6 เดือนล่าสุด)" en="Monthly summary (last 6 months)" /></h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500"><x-bi th="เดือน" en="Month" /></th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500"><x-bi th="ออเดอร์" en="Orders" /></th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500"><x-bi th="คอมมิชชั่นรวม" en="Total commission" /></th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500"><x-bi th="จ่ายแล้ว" en="Paid" /></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($monthlyStats as $stat)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $stat->month }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 text-right">{{ $stat->total_orders }}</td>
                            <td class="px-4 py-2 text-sm font-semibold text-gray-900 text-right">฿{{ number_format($stat->total_commission) }}</td>
                            <td class="px-4 py-2 text-sm text-green-600 text-right">฿{{ number_format($stat->paid_amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endif
@endsection
