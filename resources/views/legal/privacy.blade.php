@extends('layouts.app')

@section('title', 'นโยบายความเป็นส่วนตัว - ' . config('app.name', 'XMAN Studio'))

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold mb-4">นโยบายความเป็นส่วนตัว</h1>
            <p class="text-gray-300">Privacy Policy</p>
            <p class="text-sm text-gray-400 mt-4">ปรับปรุงล่าสุด: {{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 md:p-12 prose prose-lg dark:prose-invert max-w-none">

            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 mb-8 rounded-r-lg">
                <p class="text-green-800 dark:text-green-300 text-sm">
                    {{ config('app.name', 'XMAN Studio') }} ให้ความสำคัญกับความเป็นส่วนตัวของท่าน นโยบายนี้อธิบายวิธีที่เราเก็บรวบรวม ใช้ และปกป้องข้อมูลส่วนบุคคลของท่าน ตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
                </p>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                ข้อมูลที่เราเก็บรวบรวม
            </h2>

            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mt-4">1.1 ข้อมูลที่ท่านให้โดยตรง</h3>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>ข้อมูลบัญชี:</strong> ชื่อ-นามสกุล, อีเมล, เบอร์โทรศัพท์, รหัสผ่าน (เข้ารหัส)</li>
                <li><strong>ข้อมูลการติดต่อ:</strong> ที่อยู่, ชื่อบริษัท, ตำแหน่งงาน</li>
                <li><strong>ข้อมูลการชำระเงิน:</strong> ข้อมูลบัตรเครดิต (ผ่านระบบ Payment Gateway ที่ปลอดภัย)</li>
                <li><strong>ข้อมูลคำสั่งซื้อ:</strong> รายละเอียดสินค้า/บริการที่สั่งซื้อ</li>
            </ul>

            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mt-4">1.2 ข้อมูลที่เก็บโดยอัตโนมัติ</h3>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>ข้อมูลอุปกรณ์:</strong> IP Address, ประเภทเบราว์เซอร์, ระบบปฏิบัติการ</li>
                <li><strong>ข้อมูลการใช้งาน:</strong> หน้าที่เข้าชม, เวลาที่ใช้, การคลิก</li>
                <li><strong>คุกกี้:</strong> เพื่อจดจำการตั้งค่าและปรับปรุงประสบการณ์</li>
                <li><strong>ข้อมูล License:</strong> Machine ID สำหรับยืนยันสิทธิ์ใช้งานซอฟต์แวร์</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                วัตถุประสงค์ในการใช้ข้อมูล
            </h2>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>ให้บริการ:</strong> ดำเนินการตามคำสั่งซื้อ, ส่งมอบซอฟต์แวร์, ให้การสนับสนุน</li>
                <li><strong>การยืนยันตัวตน:</strong> ยืนยันสิทธิ์การเข้าถึงบัญชีและ License</li>
                <li><strong>การติดต่อสื่อสาร:</strong> แจ้งสถานะคำสั่งซื้อ, ตอบคำถาม, แจ้งข่าวสาร</li>
                <li><strong>การปรับปรุงบริการ:</strong> วิเคราะห์การใช้งานเพื่อพัฒนาบริการ</li>
                <li><strong>การตลาด:</strong> ส่งโปรโมชั่น (เฉพาะผู้ที่ยินยอม)</li>
                <li><strong>ความปลอดภัย:</strong> ป้องกันการฉ้อโกงและการใช้งานที่ไม่ได้รับอนุญาต</li>
                <li><strong>กฎหมาย:</strong> ปฏิบัติตามข้อกำหนดทางกฎหมาย</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                การเปิดเผยข้อมูล
            </h2>
            <p class="text-gray-600 dark:text-gray-300">เราจะไม่ขายหรือให้เช่าข้อมูลส่วนบุคคลของท่าน แต่อาจเปิดเผยข้อมูลในกรณี:</p>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>ผู้ให้บริการ:</strong> บริษัทรับชำระเงิน, ผู้ให้บริการ Cloud, ผู้ให้บริการจัดส่ง</li>
                <li><strong>ความจำเป็นทางกฎหมาย:</strong> คำสั่งศาล, หน่วยงานรัฐที่มีอำนาจ</li>
                <li><strong>การโอนกิจการ:</strong> หากมีการควบรวมหรือขายกิจการ</li>
                <li><strong>ความยินยอม:</strong> เมื่อท่านอนุญาตให้เปิดเผยโดยเฉพาะ</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">4</span>
                การรักษาความปลอดภัย
            </h2>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">SSL/TLS Encryption</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">การเข้ารหัสข้อมูลระหว่างส่ง</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">Password Hashing</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">รหัสผ่านถูกเข้ารหัสด้วย Bcrypt</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">Secure Servers</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เซิร์ฟเวอร์ที่ได้มาตรฐาน ISO 27001</p>
                </div>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <span class="font-semibold text-gray-900 dark:text-white">Access Control</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จำกัดการเข้าถึงข้อมูลตามหน้าที่</p>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">5</span>
                สิทธิ์ของเจ้าของข้อมูล
            </h2>
            <p class="text-gray-600 dark:text-gray-300">ตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล (PDPA) ท่านมีสิทธิ์:</p>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>สิทธิ์ในการเข้าถึง:</strong> ขอสำเนาข้อมูลส่วนบุคคลของท่าน</li>
                <li><strong>สิทธิ์ในการแก้ไข:</strong> แก้ไขข้อมูลที่ไม่ถูกต้องหรือไม่สมบูรณ์</li>
                <li><strong>สิทธิ์ในการลบ:</strong> ขอให้ลบข้อมูลในบางกรณี</li>
                <li><strong>สิทธิ์ในการโอนย้าย:</strong> ขอรับข้อมูลในรูปแบบที่อ่านได้โดยเครื่อง</li>
                <li><strong>สิทธิ์ในการคัดค้าน:</strong> คัดค้านการประมวลผลข้อมูลบางประเภท</li>
                <li><strong>สิทธิ์ในการถอนความยินยอม:</strong> ถอนความยินยอมได้ทุกเมื่อ</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">6</span>
                คุกกี้และเทคโนโลยีที่คล้ายกัน
            </h2>
            <div class="overflow-x-auto mt-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">ประเภท</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">วัตถุประสงค์</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">ระยะเวลา</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">จำเป็น</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">การทำงานพื้นฐานของเว็บไซต์</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">Session</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">การทำงาน</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">จดจำการตั้งค่า (เช่น Dark Mode)</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">1 ปี</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">วิเคราะห์</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">วิเคราะห์การใช้งาน</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">2 ปี</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">7</span>
                ระยะเวลาเก็บรักษาข้อมูล
            </h2>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>ข้อมูลบัญชี:</strong> ตลอดระยะเวลาที่มีบัญชี + 5 ปี หลังปิดบัญชี</li>
                <li><strong>ข้อมูลการชำระเงิน:</strong> 7 ปี ตามกฎหมายบัญชี</li>
                <li><strong>ข้อมูล License:</strong> ตลอดอายุ License + 2 ปี</li>
                <li><strong>ข้อมูล Log:</strong> 90 วัน</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">8</span>
                การแก้ไขนโยบาย
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                เราอาจปรับปรุงนโยบายนี้เป็นครั้งคราว การเปลี่ยนแปลงที่สำคัญจะแจ้งให้ทราบผ่านอีเมลหรือประกาศบนเว็บไซต์
            </p>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">9</span>
                ติดต่อเจ้าหน้าที่คุ้มครองข้อมูล
            </h2>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-6 mt-4">
                <p class="font-semibold text-gray-900 dark:text-white text-lg mb-2">{{ config('app.name', 'XMAN Studio') }}</p>
                <p class="text-gray-600 dark:text-gray-300">เจ้าหน้าที่คุ้มครองข้อมูลส่วนบุคคล (DPO)</p>
                <p class="text-gray-600 dark:text-gray-300 mt-2">อีเมล: <a href="mailto:privacy@xmanstudio.com" class="text-primary-600 dark:text-primary-400 hover:underline">privacy@xmanstudio.com</a></p>
                <p class="text-gray-600 dark:text-gray-300">เว็บไซต์: <a href="{{ url('/') }}" class="text-primary-600 dark:text-primary-400 hover:underline">{{ url('/') }}</a></p>
            </div>

        </div>
    </div>
</div>
@endsection
