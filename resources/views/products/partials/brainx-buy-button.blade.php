{{-- ปุ่มซื้อ / ต่ออายุ BrainX Cloud
     ราคาจริงมาจาก config/licenses.php 'plans' (ตะกร้าคิดตามนั้น) ไม่ใช่จากฟอร์มนี้
     มีคีย์อยู่แล้ว = ส่ง renew_license ไปด้วย ให้การชำระเงินต่ออายุคีย์นั้น (คีย์เดิม ไม่ใช่คีย์ใหม่)
     ถึงไม่ส่ง ระบบก็ต่ออายุคีย์ของลูกค้าให้เองอยู่แล้ว — ตัวนี้แค่ระบุว่าใบไหนเมื่อมีหลายใบ --}}
<form action="{{ route('cart.add', $product) }}" method="POST">
    @csrf
    <input type="hidden" name="quantity" value="1">
    <input type="hidden" name="license_type" value="monthly">
    <input type="hidden" name="buy_now" value="1">
    @if($key)
        <input type="hidden" name="renew_license" value="{{ $key->id }}">
    @endif
    <button type="submit"
            class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-violet-500/25 cursor-pointer">
        @if($key)
            <x-bi :th="'ต่ออายุ +30 วัน — ฿' . $monthlyPrice" en="Renew +30 days" class="text-white" />
        @else
            <x-bi :th="'สมัคร — ฿' . $monthlyPrice . '/เดือน'" en="Subscribe" class="text-white" />
        @endif
    </button>
</form>
