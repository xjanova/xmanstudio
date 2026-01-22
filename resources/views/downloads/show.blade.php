@extends('layouts.app')

@section('title', 'ดาวน์โหลด ' . $product->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 py-20">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Link -->
        <nav class="mb-8">
            <a href="{{ route('products.show', $product->slug) }}" class="text-primary-400 hover:text-primary-300 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับไปหน้าผลิตภัณฑ์
            </a>
        </nav>

        <!-- Download Card -->
        <div class="bg-gray-800/50 rounded-2xl border border-gray-700 overflow-hidden backdrop-blur-sm">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-600 to-blue-600 p-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $product->name }}</h1>
                        <p class="text-white/80">เวอร์ชัน {{ $productVersion->version }}</p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Version Info -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-700/50 rounded-lg p-4">
                        <p class="text-sm text-gray-400">ชื่อไฟล์</p>
                        <p class="text-white font-semibold">{{ $productVersion->download_filename ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-700/50 rounded-lg p-4">
                        <p class="text-sm text-gray-400">ขนาดไฟล์</p>
                        <p class="text-white font-semibold">{{ $productVersion->file_size_formatted }}</p>
                    </div>
                </div>

                @if($productVersion->changelog)
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-400 mb-2">Changelog</h3>
                        <div class="bg-gray-700/50 rounded-lg p-4 text-gray-300 text-sm whitespace-pre-wrap">{{ $productVersion->changelog }}</div>
                    </div>
                @endif

                <!-- Download Section -->
                @if($product->requires_license)
                    @auth
                        @if($hasValidLicense)
                            <!-- Has valid license - show download button -->
                            <div class="text-center">
                                <a href="{{ route('download.product', ['slug' => $product->slug, 'version' => $productVersion->version]) }}"
                                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    ดาวน์โหลดเลย
                                </a>
                                <p class="text-green-400 text-sm mt-2">คุณมี License ที่ใช้งานได้</p>
                            </div>
                        @else
                            <!-- No valid license -->
                            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-6 text-center">
                                <svg class="w-12 h-12 text-yellow-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-yellow-400 mb-2">ต้องมี License</h3>
                                <p class="text-gray-400 mb-4">คุณต้องซื้อ License เพื่อดาวน์โหลดผลิตภัณฑ์นี้</p>
                                <a href="{{ route('products.show', $product->slug) }}"
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-blue-600 hover:from-primary-700 hover:to-blue-700 text-white font-semibold rounded-lg transition-all">
                                    ดูราคาและซื้อ License
                                </a>
                            </div>
                        @endif
                    @else
                        <!-- Not logged in -->
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-6 text-center">
                            <svg class="w-12 h-12 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-blue-400 mb-2">กรุณาเข้าสู่ระบบ</h3>
                            <p class="text-gray-400 mb-4">เข้าสู่ระบบเพื่อดาวน์โหลดผลิตภัณฑ์</p>
                            <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg transition-all">
                                เข้าสู่ระบบ
                            </a>
                        </div>
                    @endauth
                @else
                    <!-- Product doesn't require license - direct download -->
                    @auth
                        <div class="text-center">
                            <a href="{{ route('download.product', ['slug' => $product->slug, 'version' => $productVersion->version]) }}"
                               class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                ดาวน์โหลดฟรี
                            </a>
                        </div>
                    @else
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-6 text-center">
                            <svg class="w-12 h-12 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-blue-400 mb-2">กรุณาเข้าสู่ระบบ</h3>
                            <p class="text-gray-400 mb-4">เข้าสู่ระบบเพื่อดาวน์โหลดผลิตภัณฑ์</p>
                            <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg transition-all">
                                เข้าสู่ระบบ
                            </a>
                        </div>
                    @endauth
                @endif
            </div>

            <!-- Footer -->
            <div class="bg-gray-700/30 px-6 py-4 border-t border-gray-700">
                <div class="flex items-center justify-between text-sm text-gray-400">
                    <span>อัปเดตเมื่อ: {{ $productVersion->synced_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
                    <span>เวอร์ชัน {{ $productVersion->version }}</span>
                </div>
            </div>
        </div>

        <!-- License Key Download Section -->
        @if($product->requires_license)
            <div class="mt-8 bg-gray-800/50 rounded-2xl border border-gray-700 p-6 backdrop-blur-sm">
                <h3 class="text-lg font-semibold text-white mb-4">ดาวน์โหลดด้วย License Key</h3>
                <p class="text-gray-400 text-sm mb-4">หากคุณมี License Key อยู่แล้ว สามารถใช้ดาวน์โหลดได้โดยตรง:</p>

                <form id="licenseDownloadForm" class="flex gap-3">
                    <input type="text" id="license_key" name="license_key" required
                           class="flex-1 px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="กรอก License Key ของคุณ">
                    <button type="submit"
                            class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors">
                        ดาวน์โหลด
                    </button>
                </form>

                <div id="licenseError" class="hidden mt-3 text-red-400 text-sm"></div>
            </div>

            <script>
                document.getElementById('licenseDownloadForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const errorDiv = document.getElementById('licenseError');
                    errorDiv.classList.add('hidden');

                    const licenseKey = document.getElementById('license_key').value;

                    try {
                        const response = await fetch('{{ route("download.api", ["slug" => $product->slug, "version" => $productVersion->version]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ license_key: licenseKey }),
                        });

                        if (response.ok) {
                            // Get the blob and create download
                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = '{{ $productVersion->download_filename ?? "download" }}';
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            a.remove();
                        } else {
                            const data = await response.json();
                            errorDiv.textContent = data.error || 'License Key ไม่ถูกต้องหรือหมดอายุ';
                            errorDiv.classList.remove('hidden');
                        }
                    } catch (error) {
                        errorDiv.textContent = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
                        errorDiv.classList.remove('hidden');
                    }
                });
            </script>
        @endif
    </div>
</div>
@endsection
