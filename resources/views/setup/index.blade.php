<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ตั้งค่าระบบ - XMAN Studio</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4" x-data="{ step: 1 }">
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-primary-600 rounded-2xl mb-4 shadow-lg shadow-primary-600/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">ตั้งค่าระบบ XMAN Studio</h1>
                <p class="text-gray-400">ยินดีต้อนรับ! กรุณากรอกข้อมูลเพื่อเริ่มใช้งานระบบ</p>
            </div>

            <!-- Progress Steps -->
            <div class="flex justify-center mb-8">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div :class="step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-700 text-gray-400'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">1</div>
                        <span class="ml-2 text-sm text-gray-300 hidden sm:inline">ผู้ดูแลระบบ</span>
                    </div>
                    <div class="w-8 h-0.5 bg-gray-700"></div>
                    <div class="flex items-center">
                        <div :class="step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-700 text-gray-400'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">2</div>
                        <span class="ml-2 text-sm text-gray-300 hidden sm:inline">ข้อมูลบริษัท</span>
                    </div>
                    <div class="w-8 h-0.5 bg-gray-700"></div>
                    <div class="flex items-center">
                        <div :class="step >= 3 ? 'bg-primary-600 text-white' : 'bg-gray-700 text-gray-400'" class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm transition-colors">3</div>
                        <span class="ml-2 text-sm text-gray-300 hidden sm:inline">Line OA</span>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 overflow-hidden">
                <form method="POST" action="{{ route('setup.store') }}" class="p-8">
                    @csrf

                    <!-- Step 1: Admin Info -->
                    <div x-show="step === 1" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-primary-600/20 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">สร้างบัญชีผู้ดูแลระบบ</h2>
                                <p class="text-gray-400 text-sm">ผู้ดูแลระบบสูงสุด (Super Admin)</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">ชื่อ-นามสกุล <span class="text-red-400">*</span></label>
                                <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                    placeholder="ชื่อผู้ดูแลระบบ">
                                @error('admin_name')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">อีเมล <span class="text-red-400">*</span></label>
                                <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                    placeholder="admin@example.com">
                                @error('admin_email')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">รหัสผ่าน <span class="text-red-400">*</span></label>
                                <input type="password" name="admin_password" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                    placeholder="อย่างน้อย 8 ตัวอักษร ตัวพิมพ์เล็ก-ใหญ่ และตัวเลข">
                                @error('admin_password')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-gray-500 text-xs mt-1">ต้องมีอย่างน้อย 8 ตัวอักษร, ตัวพิมพ์เล็ก-ใหญ่, และตัวเลข</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">ยืนยันรหัสผ่าน <span class="text-red-400">*</span></label>
                                <input type="password" name="admin_password_confirmation" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                    placeholder="กรอกรหัสผ่านอีกครั้ง">
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="button" @click="step = 2"
                                class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-colors flex items-center">
                                ถัดไป
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Company Info -->
                    <div x-show="step === 2" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">ข้อมูลบริษัท</h2>
                                <p class="text-gray-400 text-sm">ข้อมูลสำหรับใบเสนอราคาและการติดต่อ</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">ชื่อบริษัท/ร้านค้า <span class="text-red-400">*</span></label>
                                <input type="text" name="company_name" value="{{ old('company_name', 'XMAN STUDIO') }}" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                    placeholder="ชื่อบริษัท">
                                @error('company_name')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">อีเมลบริษัท <span class="text-red-400">*</span></label>
                                <input type="email" name="company_email" value="{{ old('company_email') }}" required
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                    placeholder="info@company.com">
                                @error('company_email')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">เบอร์โทรศัพท์</label>
                                    <input type="text" name="company_phone" value="{{ old('company_phone') }}"
                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                        placeholder="080-6038278">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Line OA ID</label>
                                    <input type="text" name="company_line" value="{{ old('company_line') }}"
                                        class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                                        placeholder="@yourline">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button type="button" @click="step = 1"
                                class="px-6 py-3 bg-gray-700 text-white font-semibold rounded-xl hover:bg-gray-600 transition-colors flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                ย้อนกลับ
                            </button>
                            <button type="button" @click="step = 3"
                                class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-colors flex items-center">
                                ถัดไป
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Line Messaging API -->
                    <div x-show="step === 3" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-green-600/20 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">Line Messaging API</h2>
                                <p class="text-gray-400 text-sm">สำหรับรับการแจ้งเตือนคำสั่งซื้อ (ไม่บังคับ)</p>
                            </div>
                        </div>

                        <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-4 mb-6">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm">
                                    <p class="text-yellow-300 font-medium">วิธีการตั้งค่า Line Messaging API:</p>
                                    <ol class="text-yellow-200/80 mt-2 space-y-1 list-decimal list-inside">
                                        <li>ไปที่ <a href="https://developers.line.biz/console/" target="_blank" class="underline hover:text-yellow-100">Line Developers Console</a></li>
                                        <li>สร้าง Messaging API Channel</li>
                                        <li>ไปที่ tab Messaging API → Issue Channel Access Token</li>
                                        <li>เพิ่ม Bot เป็นเพื่อนเพื่อรับ User ID</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Channel Access Token</label>
                                <input type="text" name="line_channel_access_token" value="{{ old('line_channel_access_token') }}"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition font-mono text-sm"
                                    placeholder="Long-lived Channel Access Token">
                                @error('line_channel_access_token')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Admin User ID</label>
                                <input type="text" name="line_admin_user_id" value="{{ old('line_admin_user_id') }}"
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition font-mono text-sm"
                                    placeholder="Uxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                @error('line_admin_user_id')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-gray-500 text-xs mt-1">User ID ของคุณสำหรับรับการแจ้งเตือน (เริ่มต้นด้วย U)</p>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-between">
                            <button type="button" @click="step = 2"
                                class="px-6 py-3 bg-gray-700 text-white font-semibold rounded-xl hover:bg-gray-600 transition-colors flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                ย้อนกลับ
                            </button>
                            <button type="submit"
                                class="px-8 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors flex items-center shadow-lg shadow-green-600/30">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                เริ่มใช้งานระบบ
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-500 text-sm mt-8">
                XMAN Studio &copy; {{ date('Y') }} - IT Solutions & Software Development
            </p>
        </div>
    </div>
</body>
</html>
