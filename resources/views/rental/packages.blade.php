@extends($publicLayout ?? 'layouts.app')

@section('title', 'แพ็กเกจเช่าใช้งาน - XMAN Studio')
@section('meta_description', 'เลือกแพ็กเกจเช่าใช้งานซอฟต์แวร์ที่เหมาะกับคุณ ราคาคุ้มค่า พร้อมบริการสนับสนุน')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-primary-600 to-primary-800 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-extrabold text-white sm:text-5xl">
            แพ็กเกจเช่าใช้งาน
        </h1>
        <p class="mt-4 text-xl text-primary-100">
            เลือกแพ็กเกจที่เหมาะกับความต้องการของคุณ
        </p>
    </div>
</div>

<!-- Packages Section -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('error'))
            <div class="mb-8 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            @forelse($packages as $package)
                <div class="relative bg-white rounded-2xl shadow-xl {{ $package->is_featured ? 'ring-2 ring-primary-500' : '' }}">
                    @if($package->is_featured)
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                            <span class="inline-flex px-4 py-1 rounded-full text-sm font-semibold bg-primary-500 text-white">
                                แนะนำ
                            </span>
                        </div>
                    @endif

                    @if($package->is_popular)
                        <div class="absolute -top-4 right-4">
                            <span class="inline-flex px-4 py-1 rounded-full text-sm font-semibold bg-orange-500 text-white">
                                ยอดนิยม
                            </span>
                        </div>
                    @endif

                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900">{{ $package->display_name }}</h3>
                        <p class="mt-2 text-gray-500">{{ $package->display_description }}</p>

                        <div class="mt-6">
                            @if($package->original_price && $package->original_price > $package->price)
                                <span class="text-lg text-gray-400 line-through">
                                    ฿{{ number_format($package->original_price) }}
                                </span>
                                <span class="ml-2 inline-flex px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-700">
                                    ลด {{ $package->discount_percent }}%
                                </span>
                            @endif
                            <div class="flex items-baseline">
                                <span class="text-4xl font-extrabold text-gray-900">฿{{ number_format($package->price) }}</span>
                                <span class="ml-2 text-gray-500">/ {{ $package->duration_text }}</span>
                            </div>
                        </div>

                        <!-- Features -->
                        <ul class="mt-8 space-y-4">
                            @foreach($package->features ?? [] as $feature)
                                <li class="flex items-start">
                                    <svg class="flex-shrink-0 w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-3 text-gray-600">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <!-- Limits -->
                        @if($package->limits)
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">โควต้าการใช้งาน</h4>
                                <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                    @if(isset($package->limits['max_posts']))
                                        <li>โพสต์: {{ $package->limits['max_posts'] }} ครั้ง/เดือน</li>
                                    @endif
                                    @if(isset($package->limits['max_accounts']))
                                        <li>บัญชี: {{ $package->limits['max_accounts'] }} บัญชี</li>
                                    @endif
                                    @if(isset($package->limits['ai_credits']))
                                        <li>AI Credits: {{ $package->limits['ai_credits'] }} credits</li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        <div class="mt-8">
                            @auth
                                @if($activeRental && $activeRental->rental_package_id === $package->id)
                                    <button disabled class="w-full py-3 px-6 rounded-lg text-white bg-gray-400 cursor-not-allowed">
                                        กำลังใช้งานอยู่
                                    </button>
                                @else
                                    <a href="{{ route('rental.checkout', $package) }}"
                                       class="block w-full text-center py-3 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold">
                                        เลือกแพ็กเกจนี้
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('login') }}"
                                   class="block w-full text-center py-3 px-6 rounded-lg text-white bg-primary-600 hover:bg-primary-700 transition-colors font-semibold">
                                    เข้าสู่ระบบเพื่อสมัคร
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500">ยังไม่มีแพ็กเกจในขณะนี้</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-12">คำถามที่พบบ่อย</h2>

        <div class="space-y-6">
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">สามารถเปลี่ยนแพ็กเกจได้หรือไม่?</h3>
                <p class="mt-2 text-gray-600">ได้ครับ สามารถอัพเกรดแพ็กเกจได้ตลอดเวลา โดยจะคิดค่าใช้จ่ายส่วนต่างตามสัดส่วนเวลาที่เหลือ</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">วิธีการชำระเงินมีอะไรบ้าง?</h3>
                <p class="mt-2 text-gray-600">รองรับการชำระผ่านพร้อมเพย์ (PromptPay QR Code), โอนเงินธนาคาร และบัตรเครดิต/เดบิต</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">มีการต่ออายุอัตโนมัติหรือไม่?</h3>
                <p class="mt-2 text-gray-600">ในขณะนี้ยังไม่มีการต่ออายุอัตโนมัติ ระบบจะแจ้งเตือนก่อนหมดอายุ 7 วัน</p>
            </div>
        </div>
    </div>
</div>
@endsection
