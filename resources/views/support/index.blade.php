@extends('layouts.app')

@section('title', 'สั่งงาน & ใบเสนอราคา - XMAN Studio')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 text-white py-20 overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1551434678-e076c223a692?w=1920')] bg-cover bg-center opacity-10"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 to-transparent"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-2 bg-primary-600/30 text-primary-300 text-sm font-semibold rounded-full mb-6 backdrop-blur-sm border border-primary-500/30">
            ระบบสั่งงาน Professional
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6">
            สั่งงาน <span class="text-primary-400">&</span> รับใบเสนอราคา
        </h1>
        <p class="text-xl text-gray-300 max-w-3xl mx-auto">
            เลือกบริการที่ต้องการ กรอกรายละเอียด ดาวน์โหลดใบเสนอราคาทันที หรือสั่งงานพร้อมชำระเงิน
        </p>
    </div>
</section>

<!-- Quotation Form Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-900" x-data="quotationForm()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Form -->
            <div class="lg:col-span-2">
                <form @submit.prevent="handleSubmit" class="space-y-8">
                    <!-- Step 1: Select Service -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold mr-4">1</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">เลือกประเภทบริการ</h2>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($services as $key => $service)
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="service_type" value="{{ $key }}" x-model="formData.service_type" @change="updateServiceOptions()" class="peer sr-only">
                                <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500 group-hover:shadow-lg">
                                    <div class="text-3xl mb-2">{{ $service['icon'] }}</div>
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ $service['name_th'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $service['name'] }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Step 2: Service Options (Categorized) -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="formData.service_type" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold mr-4">2</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">เลือกรายละเอียดบริการ</h2>
                        </div>

                        <!-- Categories -->
                        <div class="space-y-8">
                            <template x-for="(category, catKey) in currentCategories" :key="catKey">
                                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                    <div class="flex items-center mb-4">
                                        <span class="text-2xl mr-3" x-text="category.icon"></span>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="category.name_th"></h3>
                                            <p class="text-sm text-gray-500" x-text="category.name"></p>
                                        </div>
                                    </div>
                                    <div class="grid md:grid-cols-2 gap-3">
                                        <template x-for="(option, optKey) in category.options" :key="optKey">
                                            <label class="relative cursor-pointer">
                                                <input type="checkbox" :value="optKey" x-model="formData.service_options" class="peer sr-only">
                                                <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 hover:border-gray-300 dark:border-gray-600 hover:shadow-md">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <div class="font-semibold text-gray-900 dark:text-white text-sm" x-text="option.name_th"></div>
                                                            <div class="text-xs text-gray-500 mt-1" x-text="option.name"></div>
                                                        </div>
                                                        <div class="text-primary-600 dark:text-primary-400 font-bold text-sm ml-2 whitespace-nowrap" x-text="formatPrice(option.price) + ' ฿'"></div>
                                                    </div>
                                                </div>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 3: Additional Options (Categorized) -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="formData.service_options.length > 0" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold mr-4">3</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ออฟชั่นเพิ่มเติม</h2>
                        </div>

                        <div class="space-y-6">
                            @foreach($additionalOptions as $catKey => $category)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                <div class="flex items-center mb-4">
                                    <span class="text-2xl mr-3">{{ $category['icon'] }}</span>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $category['name_th'] }}</h3>
                                        <p class="text-sm text-gray-500">{{ $category['name'] }}</p>
                                    </div>
                                </div>
                                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($category['options'] as $optKey => $option)
                                    <label class="relative cursor-pointer">
                                        <input type="checkbox" value="{{ $optKey }}" x-model="formData.additional_options" class="peer sr-only">
                                        <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 hover:border-gray-300 dark:border-gray-600 hover:shadow-md">
                                            <div class="flex items-center mb-2">
                                                <span class="text-xl mr-2">{{ $option['icon'] ?? '' }}</span>
                                                <div class="font-semibold text-gray-900 dark:text-white text-sm">{{ $option['name_th'] }}</div>
                                            </div>
                                            <div class="text-xs text-gray-500 mb-2">{{ $option['name'] }}</div>
                                            <div class="text-green-600 font-bold">{{ number_format($option['price']) }} บาท</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Step 4: Project Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="formData.service_options.length > 0" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold mr-4">4</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดโปรเจค</h2>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียดโปรเจค</label>
                                <textarea x-model="formData.project_description" rows="4" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="อธิบายรายละเอียดโปรเจคที่ต้องการ ฟีเจอร์พิเศษ หรือความต้องการเฉพาะ..."></textarea>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระยะเวลาที่ต้องการ</label>
                                    <select x-model="formData.timeline" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                        <option value="flexible">🟢 ยืดหยุ่น (ไม่เร่งด่วน)</option>
                                        <option value="normal">🟡 ปกติ (2-3 เดือน)</option>
                                        <option value="urgent">🔴 เร่งด่วน (+25% ค่าบริการ)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">งบประมาณโดยประมาณ</label>
                                    <select x-model="formData.budget_range" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                        <option value="">กรุณาเลือก</option>
                                        <option value="50000-100000">50,000 - 100,000 บาท</option>
                                        <option value="100000-300000">100,000 - 300,000 บาท</option>
                                        <option value="300000-500000">300,000 - 500,000 บาท</option>
                                        <option value="500000-1000000">500,000 - 1,000,000 บาท</option>
                                        <option value="1000000+">มากกว่า 1,000,000 บาท</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Customer Information -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="formData.service_options.length > 0" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold mr-4">5</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลผู้ติดต่อ</h2>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.customer_name" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="ชื่อจริง นามสกุล">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">บริษัท/องค์กร</label>
                                <input type="text" x-model="formData.customer_company" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="ชื่อบริษัท (ถ้ามี)">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อีเมล <span class="text-red-500">*</span></label>
                                <input type="email" x-model="formData.customer_email" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="email@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เบอร์โทรศัพท์ <span class="text-red-500">*</span></label>
                                <input type="tel" x-model="formData.customer_phone" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="08X-XXX-XXXX">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ที่อยู่</label>
                                <textarea x-model="formData.customer_address" rows="2" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-700 dark:text-white" placeholder="ที่อยู่สำหรับออกใบเสนอราคา..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Action Choice -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="isFormValid()" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-600 text-white rounded-full flex items-center justify-center font-bold mr-4">6</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">เลือกการดำเนินการ</h2>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Download PDF Only -->
                            <button type="button" @click="generateQuotation()" :disabled="isLoading" class="p-6 border-2 border-primary-500 rounded-2xl text-left hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all group">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">ดาวน์โหลดใบเสนอราคา</h3>
                                        <p class="text-sm text-gray-500">รับ PDF ทันที</p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ดาวน์โหลดใบเสนอราคาในรูปแบบ PDF เพื่อศึกษารายละเอียดก่อนตัดสินใจ</p>
                            </button>

                            <!-- Request Quotation -->
                            <button type="button" @click="submitOrder('quotation')" :disabled="isLoading" class="p-6 border-2 border-blue-500 rounded-2xl text-left hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mr-4 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">ขอใบเสนอราคา</h3>
                                        <p class="text-sm text-gray-500">ทีมงานติดต่อกลับ</p>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ส่งคำขอใบเสนอราคา ทีมงานจะติดต่อกลับภายใน 24 ชั่วโมง พร้อมให้คำปรึกษา</p>
                            </button>
                        </div>

                        <!-- Payment Options -->
                        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">หรือสั่งงานพร้อมชำระเงิน</h3>
                            <div class="grid md:grid-cols-3 gap-4">
                                <button type="button" @click="submitOrder('order', 'promptpay')" :disabled="isLoading" class="p-4 border-2 border-green-500 rounded-xl hover:bg-green-50 dark:hover:bg-green-900/20 transition-all group">
                                    <div class="flex items-center justify-center mb-2">
                                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <span class="text-2xl">💳</span>
                                        </div>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-center">PromptPay QR</h4>
                                    <p class="text-xs text-gray-500 text-center">ชำระผ่าน QR Code</p>
                                </button>

                                <button type="button" @click="submitOrder('order', 'bank_transfer')" :disabled="isLoading" class="p-4 border-2 border-yellow-500 rounded-xl hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-all group">
                                    <div class="flex items-center justify-center mb-2">
                                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <span class="text-2xl">🏦</span>
                                        </div>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-center">โอนเงินธนาคาร</h4>
                                    <p class="text-xs text-gray-500 text-center">Bank Transfer</p>
                                </button>

                                <button type="button" @click="submitOrder('order', 'credit_card')" :disabled="isLoading" class="p-4 border-2 border-purple-500 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all group">
                                    <div class="flex items-center justify-center mb-2">
                                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <span class="text-2xl">💎</span>
                                        </div>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-center">บัตรเครดิต</h4>
                                    <p class="text-xs text-gray-500 text-center">Credit Card</p>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 sticky top-24">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        สรุปใบเสนอราคา
                    </h3>

                    <!-- Selected Service -->
                    <div class="mb-6" x-show="formData.service_type">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">ประเภทบริการ</div>
                        <div class="flex items-center">
                            <span class="text-2xl mr-2" x-text="getServiceIcon()"></span>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white" x-text="getServiceName()"></div>
                        </div>
                    </div>

                    <!-- Selected Items -->
                    <div class="border-t dark:border-gray-700 pt-4 mb-4" x-show="formData.service_options.length > 0">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">รายการที่เลือก (<span x-text="getSelectedItems().length"></span> รายการ)</div>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <template x-for="item in getSelectedItems()" :key="item.key">
                                <div class="flex justify-between text-sm py-2 border-b dark:border-gray-700">
                                    <span class="text-gray-700 dark:text-gray-300 flex-1" x-text="item.name"></span>
                                    <span class="font-semibold text-gray-900 dark:text-white ml-2" x-text="formatPrice(item.price)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="border-t dark:border-gray-700 pt-4 space-y-3" x-show="formData.service_options.length > 0">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">รวมก่อนส่วนลด</span>
                            <span class="text-gray-900 dark:text-white" x-text="formatPrice(calculateSubtotal()) + ' ฿'"></span>
                        </div>
                        <div class="flex justify-between text-sm" x-show="getDiscount() > 0">
                            <span class="text-green-600">ส่วนลด (<span x-text="getDiscountPercent()"></span>%)</span>
                            <span class="text-green-600">-<span x-text="formatPrice(getDiscount())"></span> ฿</span>
                        </div>
                        <div class="flex justify-between text-sm" x-show="formData.timeline === 'urgent'">
                            <span class="text-orange-600">ค่าเร่งด่วน (+25%)</span>
                            <span class="text-orange-600">+<span x-text="formatPrice(getRushFee())"></span> ฿</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">VAT (7%)</span>
                            <span class="text-gray-900 dark:text-white" x-text="formatPrice(getVat()) + ' ฿'"></span>
                        </div>
                        <div class="flex justify-between pt-3 border-t dark:border-gray-700">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">รวมทั้งสิ้น</span>
                            <span class="text-2xl font-bold text-primary-600" x-text="formatPrice(getGrandTotal()) + ' ฿'"></span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400" x-show="formData.service_options.length === 0">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p>เลือกบริการเพื่อดูสรุปราคา</p>
                    </div>

                    <!-- Discount Info -->
                    <div class="mt-6 p-4 bg-gradient-to-r from-primary-50 to-purple-50 dark:from-primary-900/20 dark:to-purple-900/20 rounded-xl">
                        <div class="text-sm font-semibold text-primary-700 dark:text-primary-400 mb-2">โปรโมชั่นพิเศษ</div>
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• ยอด 200,000+ รับส่วนลด 5%</li>
                            <li>• ยอด 500,000+ รับส่วนลด 10%</li>
                            <li>• ยอด 1,000,000+ รับส่วนลด 15%</li>
                        </ul>
                    </div>

                    <!-- Loading Overlay -->
                    <div x-show="isLoading" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-2xl flex items-center justify-center">
                        <div class="text-center">
                            <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-sm text-gray-600 dark:text-gray-400">กำลังดำเนินการ...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccessModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showSuccessModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2" x-text="successTitle"></h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4" x-text="successMessage"></p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mb-6">เลขที่อ้างอิง: <span class="font-mono font-bold text-primary-600" x-text="quoteNumber"></span></p>
            <button @click="showSuccessModal = false; resetForm()" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-colors">
                ตกลง
            </button>
        </div>
    </div>
</section>

<!-- Contact Info Section -->
<section class="py-16 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">ติดต่อเราโดยตรง</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">หรือติดต่อเราผ่านช่องทางอื่นๆ</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <a href="mailto:info@xmanstudio.com" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">อีเมล</h3>
                <p class="text-gray-600 dark:text-gray-300">info@xmanstudio.com</p>
            </a>

            <a href="#" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Line OA</h3>
                <p class="text-gray-600 dark:text-gray-300">@xmanstudio</p>
            </a>

            <a href="https://youtube.com/@metal-xproject" target="_blank" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">YouTube</h3>
                <p class="text-gray-600 dark:text-gray-300">Metal-X Project</p>
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function quotationForm() {
    return {
        isLoading: false,
        showSuccessModal: false,
        successTitle: '',
        successMessage: '',
        quoteNumber: '',
        formData: {
            customer_name: '',
            customer_company: '',
            customer_email: '',
            customer_phone: '',
            customer_address: '',
            service_type: '',
            service_options: [],
            additional_options: [],
            project_description: '',
            timeline: 'normal',
            budget_range: '',
        },
        services: @json($services),
        additionalOptions: @json($additionalOptions),
        currentCategories: {},

        updateServiceOptions() {
            if (this.formData.service_type && this.services[this.formData.service_type]) {
                this.currentCategories = this.services[this.formData.service_type].categories;
            } else {
                this.currentCategories = {};
            }
            this.formData.service_options = [];
        },

        getServiceName() {
            if (this.formData.service_type && this.services[this.formData.service_type]) {
                return this.services[this.formData.service_type].name_th;
            }
            return '';
        },

        getServiceIcon() {
            if (this.formData.service_type && this.services[this.formData.service_type]) {
                return this.services[this.formData.service_type].icon;
            }
            return '';
        },

        getAllServiceOptions() {
            let allOptions = {};
            if (this.formData.service_type && this.services[this.formData.service_type]) {
                const categories = this.services[this.formData.service_type].categories;
                for (const catKey in categories) {
                    for (const optKey in categories[catKey].options) {
                        allOptions[optKey] = categories[catKey].options[optKey];
                    }
                }
            }
            return allOptions;
        },

        getAllAdditionalOptions() {
            let allOptions = {};
            for (const catKey in this.additionalOptions) {
                for (const optKey in this.additionalOptions[catKey].options) {
                    allOptions[optKey] = this.additionalOptions[catKey].options[optKey];
                }
            }
            return allOptions;
        },

        getSelectedItems() {
            let items = [];
            const serviceOptions = this.getAllServiceOptions();
            const additionalOptions = this.getAllAdditionalOptions();

            // Service options
            this.formData.service_options.forEach(key => {
                if (serviceOptions[key]) {
                    items.push({
                        key: key,
                        name: serviceOptions[key].name_th,
                        price: serviceOptions[key].price
                    });
                }
            });

            // Additional options
            this.formData.additional_options.forEach(key => {
                if (additionalOptions[key]) {
                    items.push({
                        key: 'add_' + key,
                        name: additionalOptions[key].name_th,
                        price: additionalOptions[key].price
                    });
                }
            });

            return items;
        },

        calculateSubtotal() {
            let total = 0;
            this.getSelectedItems().forEach(item => {
                total += item.price;
            });
            return total;
        },

        getDiscountPercent() {
            const subtotal = this.calculateSubtotal();
            if (subtotal >= 1000000) return 15;
            if (subtotal >= 500000) return 10;
            if (subtotal >= 200000) return 5;
            return 0;
        },

        getDiscount() {
            return this.calculateSubtotal() * (this.getDiscountPercent() / 100);
        },

        getRushFee() {
            if (this.formData.timeline === 'urgent') {
                return (this.calculateSubtotal() - this.getDiscount()) * 0.25;
            }
            return 0;
        },

        getTotalBeforeVat() {
            return this.calculateSubtotal() - this.getDiscount() + this.getRushFee();
        },

        getVat() {
            return this.getTotalBeforeVat() * 0.07;
        },

        getGrandTotal() {
            return this.getTotalBeforeVat() + this.getVat();
        },

        formatPrice(price) {
            return new Intl.NumberFormat('th-TH').format(Math.round(price));
        },

        isFormValid() {
            return this.formData.service_options.length > 0
                && this.formData.customer_name
                && this.formData.customer_email
                && this.formData.customer_phone;
        },

        async generateQuotation() {
            if (!this.isFormValid()) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/quotation/pdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/pdf',
                    },
                    body: JSON.stringify(this.formData),
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'เกิดข้อผิดพลาด');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'XMAN-Quotation.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();

            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.isLoading = false;
            }
        },

        async submitOrder(actionType, paymentMethod = null) {
            if (!this.isFormValid()) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/quotation/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.formData,
                        action_type: actionType,
                        payment_method: paymentMethod,
                    }),
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'เกิดข้อผิดพลาด');
                }

                this.quoteNumber = result.quote_number;

                if (actionType === 'order') {
                    this.successTitle = 'ได้รับคำสั่งซื้อแล้ว!';
                    this.successMessage = result.message;
                } else {
                    this.successTitle = 'ส่งคำขอใบเสนอราคาแล้ว!';
                    this.successMessage = result.message;
                }

                this.showSuccessModal = true;

            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.isLoading = false;
            }
        },

        resetForm() {
            this.formData = {
                customer_name: '',
                customer_company: '',
                customer_email: '',
                customer_phone: '',
                customer_address: '',
                service_type: '',
                service_options: [],
                additional_options: [],
                project_description: '',
                timeline: 'normal',
                budget_range: '',
            };
            this.currentCategories = {};
        },

        handleSubmit() {
            // Prevent default form submission
        }
    }
}
</script>
@endpush
