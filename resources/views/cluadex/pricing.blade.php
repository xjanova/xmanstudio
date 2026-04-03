@extends('layouts.app')

@section('title', 'CluadeX Pro - แพ็กเกจราคา | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-indigo-950 to-gray-900 py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <a href="{{ route('cluadex.detail') }}" class="text-indigo-400 hover:text-indigo-300 text-sm mb-6 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                กลับหน้า CluadeX
            </a>
            <h1 class="text-4xl md:text-5xl font-black text-white mt-4">เลือกแพ็กเกจ <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">Pro</span></h1>
            <p class="text-gray-400 mt-3 text-lg">ปลดล็อกฟีเจอร์ขั้นสูงทั้งหมด</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            @foreach($pricing as $planKey => $plan)
            <div class="bg-gray-800/40 rounded-2xl p-8 border {{ $planKey === 'yearly' ? 'border-indigo-500/50 relative md:scale-105' : ($planKey === 'lifetime' ? 'border-yellow-500/30' : 'border-gray-700/50') }} hover:border-indigo-500/40 transition-all">

                @if($planKey === 'yearly')
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs font-bold rounded-full shadow-lg">ประหยัด 63%</div>
                @endif

                <div class="text-center mb-6">
                    <h3 class="text-lg font-bold {{ $planKey === 'yearly' ? 'text-indigo-400' : ($planKey === 'lifetime' ? 'text-yellow-400' : 'text-gray-400') }}">{{ $plan['name_th'] }}</h3>
                    <div class="text-5xl font-black text-white mt-3">฿{{ number_format($plan['price']) }}</div>
                    <div class="text-gray-500 mt-1">{{ $planKey === 'lifetime' ? 'จ่ายครั้งเดียว' : '/' . ($planKey === 'monthly' ? 'เดือน' : 'ปี') }}</div>
                    @if($planKey === 'yearly')
                    <div class="text-green-400 text-sm mt-1">≈ ฿{{ number_format(round($plan['price']/12)) }}/เดือน</div>
                    @endif
                </div>

                <ul class="space-y-3 text-gray-300 text-sm mb-8">
                    @foreach($plan['features'] as $feature)
                    <li class="flex items-start gap-2">
                        <span class="{{ $planKey === 'yearly' ? 'text-indigo-400' : ($planKey === 'lifetime' ? 'text-yellow-400' : 'text-green-400') }} mt-0.5">✓</span>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                @auth
                <a href="{{ route('products.index') }}"
                   class="block w-full text-center px-6 py-3 {{ $planKey === 'yearly' ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 shadow-lg shadow-indigo-500/25' : ($planKey === 'lifetime' ? 'bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 shadow-lg shadow-yellow-500/25' : 'bg-gray-700 hover:bg-gray-600') }} text-white font-bold rounded-xl transition-all transform hover:scale-105">
                    เลือกแพ็กเกจ
                </a>
                @else
                <a href="{{ route('login') }}"
                   class="block w-full text-center px-6 py-3 {{ $planKey === 'yearly' ? 'bg-gradient-to-r from-indigo-600 to-purple-600' : ($planKey === 'lifetime' ? 'bg-gradient-to-r from-yellow-600 to-amber-600' : 'bg-gray-700 hover:bg-gray-600') }} text-white font-bold rounded-xl transition-all">
                    เข้าสู่ระบบเพื่อซื้อ
                </a>
                @endauth
            </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">ทุกแพ็กเกจรวม: อัพเดทอัตโนมัติ, Bug Report, ซัพพอร์ตจาก XMAN Studio</p>
            <a href="https://github.com/xjanova/cluadeX/releases/latest" class="text-green-400 hover:text-green-300 text-sm mt-3 inline-flex items-center gap-1">
                หรือ ดาวน์โหลดเวอร์ชันฟรี →
            </a>
        </div>
    </div>
</div>
@endsection
