@extends('layouts.app')

@section('title', 'บริการของเรา - XMAN Studio')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">บริการของเรา</h1>
        <p class="text-xl text-primary-100 max-w-2xl mx-auto">
            IT Solutions ครบวงจร ตอบโจทย์ทุกความต้องการทางธุรกิจ
        </p>
    </div>
</section>

<!-- Services Grid -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($services->isEmpty())
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700">ยังไม่มีบริการ</h2>
                <p class="text-gray-500 mt-2">กรุณากลับมาใหม่ภายหลัง</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($services as $service)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow group">
                        <div class="h-48 flex items-center justify-center" style="background: linear-gradient(135deg, {{ $service->color ?? '#0ea5e9' }}, {{ $service->color ?? '#0ea5e9' }}99)">
                            @if($service->icon)
                                <div class="text-6xl text-white">
                                    {!! $service->icon !!}
                                </div>
                            @else
                                <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            @endif
                        </div>

                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $service->name }}</h3>
                            <p class="text-gray-600 mb-4 line-clamp-3">{{ $service->description }}</p>

                            @if($service->features)
                                <ul class="text-sm text-gray-500 mb-4 space-y-1">
                                    @foreach(array_slice($service->features, 0, 3) as $feature)
                                        <li class="flex items-center">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="flex items-center justify-between">
                                @if($service->price_type === 'fixed')
                                    <span class="text-2xl font-bold text-gray-900">
                                        {{ number_format($service->price, 0) }}
                                        <span class="text-sm text-gray-500">บาท</span>
                                    </span>
                                @elseif($service->price_type === 'range')
                                    <span class="text-lg font-bold text-gray-900">
                                        {{ number_format($service->price_min, 0) }} - {{ number_format($service->price_max, 0) }}
                                        <span class="text-sm text-gray-500">บาท</span>
                                    </span>
                                @else
                                    <span class="text-lg font-bold text-primary-600">สอบถามราคา</span>
                                @endif

                                <a href="{{ route('services.show', $service->slug) }}"
                                   class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">ต้องการบริการที่ปรึกษา?</h2>
        <p class="text-lg text-gray-600 mb-8">
            ทีมผู้เชี่ยวชาญของเราพร้อมให้คำปรึกษาและวางแผนโซลูชันที่เหมาะสมกับธุรกิจของคุณ
        </p>
        <a href="{{ route('support.index') }}"
           class="inline-block px-8 py-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold text-lg">
            ติดต่อเรา
        </a>
    </div>
</section>
@endsection
