@extends($publicLayout ?? 'layouts.app')

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
                                            <div class="relative">
                                                <label class="cursor-pointer block">
                                                    <input type="checkbox" :value="optKey" x-model="formData.service_options" class="peer sr-only">
                                                    <div class="p-4 border-2 rounded-xl transition-all peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/20 hover:border-gray-300 dark:border-gray-600 hover:shadow-md">
                                                        <div class="flex justify-between items-start mb-2">
                                                            <div class="flex-1">
                                                                <div class="font-semibold text-gray-900 dark:text-white text-sm" x-text="option.name_th"></div>
                                                                <div class="text-xs text-gray-500 mt-1" x-text="option.name"></div>
                                                            </div>
                                                            <div class="text-primary-600 dark:text-primary-400 font-bold text-sm ml-2 whitespace-nowrap" x-text="formatPrice(option.price) + ' ฿'"></div>
                                                        </div>
                                                    </div>
                                                </label>
                                                <a :href="`/services/${formData.service_type}/${optKey}`"
                                                   class="absolute bottom-2 right-2 px-3 py-1 text-xs bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors z-10"
                                                   @click.stop>
                                                    ดูรายละเอียด
                                                </a>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Step 2.5: Service Option Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="hasServiceOptionDetails()" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ระบุรายละเอียดบริการ</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">กรอกรายละเอียดเพิ่มเติมสำหรับบริการที่เลือก</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <template x-for="optKey in formData.service_options" :key="'svc_' + optKey">
                                <div x-show="serviceOptionDetailConfig[optKey]" class="border border-indigo-200 dark:border-indigo-700 rounded-xl p-5">
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                                        <span class="inline-block w-2 h-2 bg-indigo-500 rounded-full mr-2"></span>
                                        <span x-text="getServiceOptionName(optKey)"></span>
                                    </h4>

                                    <div class="space-y-4">
                                        <template x-for="(fieldConfig, fieldKey) in (serviceOptionDetailConfig[optKey] || {})" :key="'svc_' + optKey + '_' + fieldKey">
                                            <div>
                                                <template x-if="fieldConfig.type === 'select'">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="fieldConfig.label"></label>
                                                        <select x-model="formData.option_details[optKey + '.' + fieldKey]"
                                                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                                                            <option value="">-- เลือก --</option>
                                                            <template x-for="opt in fieldConfig.options" :key="opt">
                                                                <option :value="opt" x-text="opt"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>
                                                <template x-if="fieldConfig.type === 'text'">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="fieldConfig.label"></label>
                                                        <input type="text"
                                                               x-model="formData.option_details[optKey + '.' + fieldKey]"
                                                               :placeholder="fieldConfig.placeholder || ''"
                                                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                                                    </div>
                                                </template>
                                                <template x-if="fieldConfig.type === 'checkbox_group'">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" x-text="fieldConfig.label"></label>
                                                        <div class="flex flex-wrap gap-2">
                                                            <template x-for="opt in fieldConfig.options" :key="opt">
                                                                <label class="cursor-pointer">
                                                                    <input type="checkbox" :value="opt"
                                                                           x-model="formData.option_details[optKey + '.' + fieldKey]"
                                                                           class="peer sr-only"
                                                                           x-init="if (!Array.isArray(formData.option_details[optKey + '.' + fieldKey])) formData.option_details[optKey + '.' + fieldKey] = []">
                                                                    <span class="inline-block px-3 py-1.5 text-sm border-2 rounded-lg transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 peer-checked:text-indigo-700 dark:peer-checked:text-indigo-400 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400"
                                                                           x-text="opt"></span>
                                                                </label>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
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

                    <!-- Step 3.5: Additional Option Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8" x-show="hasOptionDetails()" x-transition>
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียดเพิ่มเติม</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">กรอกรายละเอียดสำหรับออฟชั่นที่เลือก</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <template x-for="optKey in formData.additional_options" :key="optKey">
                                <div x-show="optionDetailConfig[optKey]" class="border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        <span x-text="getAdditionalOptionName(optKey)"></span>
                                    </h4>

                                    <div class="space-y-4">
                                        <template x-for="(fieldConfig, fieldKey) in (optionDetailConfig[optKey] || {})" :key="optKey + '_' + fieldKey">
                                            <div>
                                                <!-- Select -->
                                                <template x-if="fieldConfig.type === 'select'">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="fieldConfig.label"></label>
                                                        <select x-model="formData.option_details[optKey + '.' + fieldKey]"
                                                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                                                            <option value="">-- เลือก --</option>
                                                            <template x-for="opt in fieldConfig.options" :key="opt">
                                                                <option :value="opt" x-text="opt"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>

                                                <!-- Text -->
                                                <template x-if="fieldConfig.type === 'text'">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                            <span x-text="fieldConfig.label"></span>
                                                            <span x-show="fieldConfig.required" class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text"
                                                               x-model="formData.option_details[optKey + '.' + fieldKey]"
                                                               :placeholder="fieldConfig.placeholder || ''"
                                                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm">
                                                    </div>
                                                </template>

                                                <!-- Checkbox Group -->
                                                <template x-if="fieldConfig.type === 'checkbox_group'">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" x-text="fieldConfig.label"></label>
                                                        <div class="flex flex-wrap gap-2">
                                                            <template x-for="opt in fieldConfig.options" :key="opt">
                                                                <label class="cursor-pointer">
                                                                    <input type="checkbox" :value="opt"
                                                                           x-model="formData.option_details[optKey + '.' + fieldKey]"
                                                                           class="peer sr-only"
                                                                           x-init="if (!Array.isArray(formData.option_details[optKey + '.' + fieldKey])) formData.option_details[optKey + '.' + fieldKey] = []">
                                                                    <span class="inline-block px-3 py-1.5 text-sm border-2 rounded-lg transition-all peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 peer-checked:text-green-700 dark:peer-checked:text-green-400 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400"
                                                                           x-text="opt"></span>
                                                                </label>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
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
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full p-8 max-h-[90vh] overflow-y-auto">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2" x-text="successTitle"></h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4" x-text="successMessage"></p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mb-2">เลขที่อ้างอิง: <span class="font-mono font-bold text-primary-600" x-text="quoteNumber"></span></p>
            </div>

            <!-- Payment Amount -->
            <template x-if="paymentMethod && grandTotal > 0">
                <div class="mt-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-xl text-center border border-primary-200 dark:border-primary-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ยอดที่ต้องชำระ</p>
                    <p class="text-3xl font-black text-primary-600" x-text="formatPrice(Math.round(grandTotal)) + ' บาท'"></p>
                </div>
            </template>

            <!-- Bank Transfer Info -->
            <template x-if="paymentMethod === 'bank_transfer' && bankAccounts.length > 0">
                <div class="mt-6">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-3 text-center">บัญชีธนาคารสำหรับโอนเงิน</h4>
                    <div class="space-y-3">
                        <template x-for="(bank, index) in bankAccounts" :key="index">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-12 h-12 bg-white dark:bg-gray-600 rounded-lg flex items-center justify-center border border-gray-200 dark:border-gray-500">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-200" x-text="bank.bank_code || 'BANK'"></span>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="bank.bank_name"></p>
                                        <p class="text-lg font-mono text-primary-600 dark:text-primary-400 tracking-wider" x-text="bank.account_number"></p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="bank.account_name"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300 text-center">
                            กรุณาโอนเงินตามยอดด้านบน แล้วแจ้งสลิปกลับมาที่ทีมงาน
                        </p>
                    </div>
                </div>
            </template>

            <!-- PromptPay Info -->
            <template x-if="paymentMethod === 'promptpay' && promptpayInfo">
                <div class="mt-6 text-center">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-3">สแกน QR Code เพื่อชำระเงิน</h4>
                    <div class="inline-block p-4 bg-white rounded-xl shadow-sm border border-gray-200">
                        <img :src="promptpayInfo.qr_image_url" alt="PromptPay QR Code" class="w-56 h-56 mx-auto object-contain">
                    </div>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="promptpayInfo.promptpay_type_label"></span>: <span x-text="promptpayInfo.promptpay_number"></span>
                    </p>
                    <template x-if="promptpayInfo.promptpay_name">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            ชื่อบัญชี: <span x-text="promptpayInfo.promptpay_name"></span>
                        </p>
                    </template>
                    <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                            สแกน QR Code แล้วแจ้งสลิปกลับมาที่ทีมงาน
                        </p>
                    </div>
                </div>
            </template>

            <div class="mt-6 text-center">
                <button @click="showSuccessModal = false; resetForm()" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-colors">
                    ตกลง
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Bottom Bar -->
    <div x-show="formData.service_options.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 z-50 lg:hidden bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-2xl"
         style="padding-bottom: env(safe-area-inset-bottom, 0);">
        <div class="px-4 py-3">
            <!-- Price Summary -->
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">รายการที่เลือก (<span x-text="getSelectedItems().length"></span>)</div>
                    <div class="text-xl font-bold text-primary-600" x-text="formatPrice(getGrandTotal()) + ' ฿'"></div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500 dark:text-gray-400" x-show="getDiscount() > 0">
                        ส่วนลด <span class="text-green-600" x-text="getDiscountPercent() + '%'"></span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">รวม VAT 7%</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
                <button type="button" @click="generateQuotation()" :disabled="!isFormValid() || isLoading"
                        class="flex-1 py-3 px-4 bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm">
                    <span class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        PDF
                    </span>
                </button>
                <button type="button" @click="submitOrder('quotation')" :disabled="!isFormValid() || isLoading"
                        class="flex-1 py-3 px-4 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm">
                    <span x-show="!isLoading">ขอใบเสนอราคา</span>
                    <span x-show="isLoading" class="flex items-center justify-center">
                        <svg class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        กำลังดำเนินการ
                    </span>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Contact Info Section -->
<section class="py-16 bg-white dark:bg-gray-800 pb-32 lg:pb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">ติดต่อเราโดยตรง</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">หรือติดต่อเราผ่านช่องทางอื่นๆ</p>
        </div>

        @php
            $ctPhone = \App\Models\Setting::getValue('contact_phone', '080-6038278');
            $ctPhoneName = \App\Models\Setting::getValue('contact_phone_name', 'คุณกรณิภา');
            $ctEmail = \App\Models\Setting::getValue('contact_email', 'xjanovax@gmail.com');
            $ctFbName = \App\Models\Setting::getValue('contact_facebook_name', 'XMAN Enterprise');
            $ctFbUrl = \App\Models\Setting::getValue('contact_facebook_url', 'https://www.facebook.com/xmanenterprise/');
            $ctYtName = \App\Models\Setting::getValue('contact_youtube_name', 'Metal-X Project');
            $ctYtUrl = \App\Models\Setting::getValue('contact_youtube_url', 'https://youtube.com/@metal-xproject');
        @endphp

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            @if($ctPhone)
            <a href="tel:{{ $ctPhone }}" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">โทรศัพท์</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ $ctPhone }}</p>
                @if($ctPhoneName)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $ctPhoneName }}</p>
                @endif
            </a>
            @endif

            @if($ctEmail)
            <a href="mailto:{{ $ctEmail }}" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">อีเมล</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ $ctEmail }}</p>
            </a>
            @endif

            @if($ctFbName)
            <a href="{{ $ctFbUrl ?: '#' }}" target="_blank" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Facebook</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ $ctFbName }}</p>
            </a>
            @endif

            @if($ctYtName)
            <a href="{{ $ctYtUrl ?: '#' }}" target="_blank" class="group p-6 bg-gray-50 dark:bg-gray-700 rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-center">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">YouTube</h3>
                <p class="text-gray-600 dark:text-gray-300">{{ $ctYtName }}</p>
            </a>
            @endif
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
        paymentMethod: null,
        grandTotal: 0,
        bankAccounts: [],
        promptpayInfo: null,
        formData: {
            customer_name: '',
            customer_company: '',
            customer_email: '',
            customer_phone: '',
            customer_address: '',
            service_type: '',
            service_options: [],
            additional_options: [],
            option_details: {},
            project_description: '',
            timeline: 'normal',
            budget_range: '',
        },
        services: @json($services),
        additionalOptions: @json($additionalOptions),
        optionDetailConfig: @json($optionDetailConfig),
        serviceOptionDetailConfig: @json($serviceOptionDetailConfig),
        currentCategories: {},

        init() {
            // Load saved selections from localStorage
            this.loadSavedSelections();

            // Watch for changes and save to localStorage
            this.$watch('formData.service_type', (newValue, oldValue) => {
                // If service type changed manually (not during init), clear selections
                if (oldValue !== undefined && oldValue !== newValue) {
                    this.formData.service_options = [];
                }
                this.saveSelections();
            });
            this.$watch('formData.service_options', () => this.saveSelections());
            this.$watch('formData.additional_options', () => this.saveSelections());
        },

        saveSelections() {
            // Save current selections to localStorage
            const selectionsToSave = {
                service_type: this.formData.service_type,
                service_options: this.formData.service_options,
                additional_options: this.formData.additional_options,
                timestamp: Date.now()
            };

            try {
                localStorage.setItem('quotation_selections', JSON.stringify(selectionsToSave));
            } catch (error) {
                console.warn('Failed to save selections to localStorage:', error);
            }
        },

        loadSavedSelections() {
            try {
                const saved = localStorage.getItem('quotation_selections');
                if (saved) {
                    const data = JSON.parse(saved);

                    // Check if saved data is not too old (24 hours)
                    const hoursSinceLastSave = (Date.now() - data.timestamp) / (1000 * 60 * 60);
                    if (hoursSinceLastSave < 24) {
                        this.formData.service_type = data.service_type || '';
                        this.formData.service_options = data.service_options || [];
                        this.formData.additional_options = data.additional_options || [];

                        // Update categories if service type was loaded
                        if (this.formData.service_type) {
                            this.updateServiceOptions();
                        }
                    } else {
                        // Clear old data
                        localStorage.removeItem('quotation_selections');
                    }
                }
            } catch (error) {
                console.warn('Failed to load selections from localStorage:', error);
            }
        },

        clearSavedSelections() {
            try {
                localStorage.removeItem('quotation_selections');
            } catch (error) {
                console.warn('Failed to clear selections from localStorage:', error);
            }
        },

        updateServiceOptions() {
            if (this.formData.service_type && this.services[this.formData.service_type]) {
                this.currentCategories = this.services[this.formData.service_type].categories;
                // Don't clear selections if they were loaded from localStorage
                // Only clear if changing service type manually
            } else {
                this.currentCategories = {};
                this.formData.service_options = [];
            }
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

        hasServiceOptionDetails() {
            return this.formData.service_options.some(key => this.serviceOptionDetailConfig[key]);
        },

        getServiceOptionName(optKey) {
            const allOptions = this.getAllServiceOptions();
            return allOptions[optKey] ? allOptions[optKey].name_th : optKey;
        },

        hasOptionDetails() {
            return this.formData.additional_options.some(key => this.optionDetailConfig[key]);
        },

        getAdditionalOptionName(optKey) {
            const allOptions = this.getAllAdditionalOptions();
            return allOptions[optKey] ? allOptions[optKey].name_th : optKey;
        },

        isDomainDetailValid() {
            if (!this.formData.additional_options.includes('domain')) return true;
            const d = this.formData.option_details;
            return d['domain.domain_name_1'] && d['domain.domain_name_2'] && d['domain.domain_name_3'];
        },

        isFormValid() {
            return this.formData.service_options.length > 0
                && this.formData.customer_name
                && this.formData.customer_email
                && this.formData.customer_phone
                && this.isDomainDetailValid();
        },

        async generateQuotation() {
            if (!this.isFormValid()) {
                if (!this.isDomainDetailValid()) {
                    alert('กรุณากรอกชื่อโดเมนที่ต้องการอย่างน้อย 3 ชื่อ');
                } else {
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
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
                if (!this.isDomainDetailValid()) {
                    alert('กรุณากรอกชื่อโดเมนที่ต้องการอย่างน้อย 3 ชื่อ');
                } else {
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
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
                this.paymentMethod = result.payment_method || null;
                this.grandTotal = result.grand_total || 0;
                this.bankAccounts = result.bank_accounts || [];
                this.promptpayInfo = result.promptpay || null;

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
                option_details: {},
                project_description: '',
                timeline: 'normal',
                budget_range: '',
            };
            this.currentCategories = {};
            this.paymentMethod = null;
            this.grandTotal = 0;
            this.bankAccounts = [];
            this.promptpayInfo = null;
            // Clear saved selections from localStorage
            this.clearSavedSelections();
        },

        handleSubmit() {
            // Prevent default form submission
        }
    }
}
</script>
@endpush
