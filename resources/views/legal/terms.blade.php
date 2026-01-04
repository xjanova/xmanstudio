@extends('layouts.app')

@section('title', 'ข้อกำหนดการใช้งาน - ' . config('app.name', 'XMAN Studio'))

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold mb-4">ข้อกำหนดการใช้งาน</h1>
            <p class="text-gray-300">Terms of Service</p>
            <p class="text-sm text-gray-400 mt-4">ปรับปรุงล่าสุด: {{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 md:p-12 prose prose-lg dark:prose-invert max-w-none">

            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 mb-8 rounded-r-lg">
                <p class="text-blue-800 dark:text-blue-300 text-sm">
                    กรุณาอ่านข้อกำหนดการใช้งานนี้อย่างละเอียดก่อนใช้บริการของเรา การใช้งานเว็บไซต์และบริการของเราถือว่าท่านยอมรับข้อกำหนดเหล่านี้ทั้งหมด
                </p>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                คำนิยาม
            </h2>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li><strong>"บริษัท"</strong> หมายถึง {{ config('app.name', 'XMAN Studio') }} ผู้ให้บริการตามข้อกำหนดนี้</li>
                <li><strong>"บริการ"</strong> หมายถึง เว็บไซต์ แอปพลิเคชัน ซอฟต์แวร์ และบริการทั้งหมดที่บริษัทจัดให้</li>
                <li><strong>"ผู้ใช้บริการ"</strong> หมายถึง บุคคลหรือนิติบุคคลที่เข้าถึงหรือใช้บริการของบริษัท</li>
                <li><strong>"เนื้อหา"</strong> หมายถึง ข้อความ รูปภาพ วิดีโอ ซอฟต์แวร์ และสื่อทุกประเภทบนบริการ</li>
                <li><strong>"ใบอนุญาตใช้งาน"</strong> หมายถึง สิทธิ์ในการใช้งานซอฟต์แวร์ตามเงื่อนไขที่กำหนด</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                การยอมรับข้อกำหนด
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                การเข้าถึงและใช้บริการของ {{ config('app.name', 'XMAN Studio') }} ถือว่าท่านได้อ่าน เข้าใจ และตกลงยอมรับข้อกำหนดการใช้งานนี้ทั้งหมด รวมถึงนโยบายความเป็นส่วนตัวของเรา หากท่านไม่ยอมรับข้อกำหนดใดๆ กรุณาหยุดใช้บริการของเราทันที
            </p>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                บริการที่ให้
            </h2>
            <p class="text-gray-600 dark:text-gray-300">{{ config('app.name', 'XMAN Studio') }} ให้บริการดังต่อไปนี้:</p>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li>พัฒนาซอฟต์แวร์และแอปพลิเคชันตามความต้องการ (Custom Software Development)</li>
                <li>พัฒนาเว็บไซต์และระบบ E-commerce</li>
                <li>พัฒนาแอปพลิเคชันมือถือ (Mobile Application Development)</li>
                <li>บริการด้าน Blockchain และ Smart Contract</li>
                <li>บริการด้าน AI และ Machine Learning</li>
                <li>บริการด้าน IoT และระบบอัตโนมัติ</li>
                <li>บริการด้านความปลอดภัยเครือข่าย (Network Security)</li>
                <li>จำหน่ายซอฟต์แวร์และใบอนุญาตใช้งาน</li>
                <li>บริการฝึกอบรมและให้คำปรึกษาด้านเทคโนโลยี</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">4</span>
                การลงทะเบียนและบัญชีผู้ใช้
            </h2>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li>ท่านต้องให้ข้อมูลที่ถูกต้อง ครบถ้วน และเป็นปัจจุบันในการลงทะเบียน</li>
                <li>ท่านมีหน้าที่รักษาความปลอดภัยของบัญชีและรหัสผ่านของท่าน</li>
                <li>ท่านต้องรับผิดชอบทุกกิจกรรมที่เกิดขึ้นภายใต้บัญชีของท่าน</li>
                <li>ท่านต้องแจ้งให้เราทราบทันทีหากพบการใช้งานบัญชีโดยไม่ได้รับอนุญาต</li>
                <li>บริษัทสงวนสิทธิ์ในการระงับหรือยกเลิกบัญชีที่ละเมิดข้อกำหนด</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">5</span>
                ใบอนุญาตใช้งานซอฟต์แวร์
            </h2>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 my-4">
                <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2">ประเภทใบอนุญาต:</h4>
                <ul class="list-disc pl-6 text-yellow-700 dark:text-yellow-400 text-sm space-y-1">
                    <li><strong>รายเดือน (Monthly):</strong> สิทธิ์ใช้งาน 30 วัน ต่ออายุอัตโนมัติ</li>
                    <li><strong>รายปี (Yearly):</strong> สิทธิ์ใช้งาน 365 วัน ประหยัดกว่ารายเดือน</li>
                    <li><strong>ตลอดชีพ (Lifetime):</strong> สิทธิ์ใช้งานถาวร อัปเดตฟรี</li>
                    <li><strong>ทดลองใช้ (Demo):</strong> ทดลองใช้งาน 3-7 วัน ฟังก์ชันจำกัด</li>
                </ul>
            </div>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li>ใบอนุญาตเป็นสิทธิ์ส่วนบุคคล ไม่สามารถโอนให้ผู้อื่นได้</li>
                <li>ห้ามแก้ไข ดัดแปลง reverse engineer หรือถอดรหัสซอฟต์แวร์</li>
                <li>ห้ามแจกจ่าย ขายต่อ หรือให้เช่าซอฟต์แวร์โดยไม่ได้รับอนุญาต</li>
                <li>การละเมิดข้อกำหนดจะส่งผลให้ใบอนุญาตถูกยกเลิกทันที</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">6</span>
                การชำระเงินและการคืนเงิน
            </h2>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li>ราคาทั้งหมดแสดงเป็นสกุลเงินบาท (THB) และรวม VAT 7% แล้ว</li>
                <li>การชำระเงินต้องเสร็จสิ้นก่อนเริ่มให้บริการหรือส่งมอบซอฟต์แวร์</li>
                <li>เรารับชำระเงินผ่าน PromptPay, โอนเงินธนาคาร และบัตรเครดิต</li>
                <li>ซอฟต์แวร์ที่ดาวน์โหลดแล้วไม่สามารถขอคืนเงินได้ ยกเว้นมีข้อบกพร่องร้ายแรง</li>
                <li>งานพัฒนาตามสั่งสามารถขอคืนเงินได้ภายใน 7 วันหลังเริ่มงาน หักค่าใช้จ่ายที่เกิดขึ้นจริง</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">7</span>
                ทรัพย์สินทางปัญญา
            </h2>
            <ul class="list-disc pl-6 text-gray-600 dark:text-gray-300 space-y-2">
                <li>เนื้อหาและซอฟต์แวร์ทั้งหมดเป็นทรัพย์สินของ {{ config('app.name', 'XMAN Studio') }}</li>
                <li>เครื่องหมายการค้า โลโก้ และชื่อบริษัทได้รับการคุ้มครองตามกฎหมาย</li>
                <li>ห้ามคัดลอก ทำซ้ำ หรือใช้เนื้อหาใดๆ โดยไม่ได้รับอนุญาตเป็นลายลักษณ์อักษร</li>
                <li>งานที่พัฒนาตามสั่ง ลิขสิทธิ์จะโอนให้ลูกค้าเมื่อชำระเงินครบถ้วน</li>
            </ul>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">8</span>
                ข้อจำกัดความรับผิดชอบ
            </h2>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 my-4">
                <ul class="list-disc pl-6 text-red-700 dark:text-red-400 text-sm space-y-1">
                    <li>บริการให้ "ตามสภาพ" (AS IS) โดยไม่มีการรับประกันใดๆ</li>
                    <li>บริษัทไม่รับผิดชอบต่อความเสียหายทางอ้อม สูญเสียกำไร หรือข้อมูลสูญหาย</li>
                    <li>ความรับผิดชอบสูงสุดจำกัดเท่ากับค่าบริการที่ชำระในช่วง 12 เดือนที่ผ่านมา</li>
                </ul>
            </div>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">9</span>
                การแก้ไขข้อกำหนด
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                บริษัทสงวนสิทธิ์ในการแก้ไขข้อกำหนดเหล่านี้ได้ทุกเมื่อ การเปลี่ยนแปลงจะมีผลทันทีเมื่อประกาศบนเว็บไซต์ การใช้บริการต่อหลังการแก้ไขถือว่าท่านยอมรับข้อกำหนดใหม่
            </p>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">10</span>
                กฎหมายที่ใช้บังคับ
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                ข้อกำหนดนี้อยู่ภายใต้กฎหมายแห่งราชอาณาจักรไทย ข้อพิพาทใดๆ จะอยู่ในเขตอำนาจศาลไทย
            </p>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center mt-8">
                <span class="w-8 h-8 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-lg flex items-center justify-center text-sm font-bold mr-3">11</span>
                ติดต่อเรา
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                หากมีคำถามเกี่ยวกับข้อกำหนดการใช้งาน กรุณาติดต่อ:
            </p>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-4 mt-4">
                <p class="font-semibold text-gray-900 dark:text-white">{{ config('app.name', 'XMAN Studio') }}</p>
                <p class="text-gray-600 dark:text-gray-300">อีเมล: legal@xmanstudio.com</p>
                <p class="text-gray-600 dark:text-gray-300">เว็บไซต์: {{ url('/') }}</p>
            </div>

        </div>
    </div>
</div>
@endsection
