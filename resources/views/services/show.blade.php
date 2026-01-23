@extends($publicLayout ?? 'layouts.app')

@section('title', $service->name . ' - XMAN Studio')

@section('content')
<!-- Coming Soon Banner -->
@if(method_exists($service, 'isComingSoon') && $service->isComingSoon())
<div class="bg-orange-500 text-white py-3">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="font-semibold">Coming Soon!</span>
            @if($service->coming_soon_until)
                <span>- เปิดให้บริการวันที่ {{ $service->coming_soon_until->format('d/m/Y H:i') }}</span>
            @else
                <span>- บริการนี้จะเปิดให้บริการเร็วๆ นี้</span>
            @endif
        </div>
    </div>
</div>
@endif

<!-- Hero Section -->
<section class="py-16" style="background: linear-gradient(135deg, {{ $service->color ?? '#0ea5e9' }}, {{ $service->color ?? '#0ea5e9' }}99)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mb-8">
            <a href="{{ route('services') }}" class="text-white/80 hover:text-white">&larr; กลับไปรายการบริการ</a>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div class="text-white">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $service->name }}</h1>
                <p class="text-xl text-white/90 mb-6">{{ $service->description }}</p>

                @if($service->price_type === 'fixed')
                    <div class="text-3xl font-bold mb-6">
                        {{ number_format($service->price, 0) }}
                        <span class="text-lg">บาท</span>
                    </div>
                @elseif($service->price_type === 'range')
                    <div class="text-2xl font-bold mb-6">
                        เริ่มต้น {{ number_format($service->price_min, 0) }} - {{ number_format($service->price_max, 0) }}
                        <span class="text-lg">บาท</span>
                    </div>
                @else
                    <div class="text-2xl font-bold mb-6">สอบถามราคา</div>
                @endif

                @if(method_exists($service, 'isComingSoon') && $service->isComingSoon())
                    <div class="inline-block px-8 py-4 bg-orange-400/50 text-white rounded-lg cursor-not-allowed font-semibold text-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Coming Soon
                        @if($service->coming_soon_until)
                            - {{ $service->coming_soon_until->format('d/m/Y') }}
                        @endif
                    </div>
                @else
                    <a href="{{ route('support.index') }}"
                       class="inline-block px-8 py-4 bg-white text-gray-900 rounded-lg hover:bg-gray-100 font-semibold text-lg">
                        ขอใบเสนอราคา
                    </a>
                @endif
            </div>

            <div class="flex justify-center">
                @if($service->icon)
                    <div class="text-9xl text-white/80">
                        {!! $service->icon !!}
                    </div>
                @else
                    <svg class="w-48 h-48 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
@if($service->features && count($service->features) > 0)
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">สิ่งที่คุณจะได้รับ</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($service->features as $feature)
                <div class="flex items-start p-6 bg-white rounded-xl shadow-lg">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center mr-4" style="background-color: {{ $service->color ?? '#0ea5e9' }}20">
                        <svg class="w-6 h-6" style="color: {{ $service->color ?? '#0ea5e9' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $feature }}</h3>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Details Section -->
@if($service->details)
<section class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">รายละเอียดบริการ</h2>
        <div class="prose prose-lg max-w-none">
            {!! nl2br(e($service->details)) !!}
        </div>
    </div>
</section>
@endif

<!-- CTA Section -->
<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">พร้อมเริ่มต้นแล้วหรือยัง?</h2>
        <p class="text-lg text-gray-600 mb-8">
            ติดต่อเราวันนี้เพื่อรับคำปรึกษาฟรี และใบเสนอราคาที่ออกแบบมาเฉพาะสำหรับธุรกิจของคุณ
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('support.index') }}"
               class="inline-block px-8 py-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold text-lg">
                ติดต่อเรา
            </a>
            <a href="{{ route('services') }}"
               class="inline-block px-8 py-4 border-2 border-gray-300 text-gray-700 rounded-lg hover:border-gray-400 font-semibold text-lg">
                ดูบริการอื่นๆ
            </a>
        </div>
    </div>
</section>
@endsection
