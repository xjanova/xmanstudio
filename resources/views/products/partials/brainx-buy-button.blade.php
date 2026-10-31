{{-- ปุ่มซื้อ / ต่ออายุ BrainX (Pro + Cloud) — ใช้ในหน้า products/brainx เท่านั้น
     ราคาจริงมาจาก config/licenses.php 'plans' (ตะกร้าคิดตามนั้น) ไม่ใช่จากฟอร์มนี้
     มีคีย์อยู่แล้ว = ส่ง renew_license ไปด้วย ให้การชำระเงินต่ออายุคีย์นั้น (คีย์เดิม ไม่ใช่คีย์ใหม่)
     ถึงไม่ส่ง ระบบก็ต่ออายุคีย์ของลูกค้าให้เองอยู่แล้ว — ตัวนี้แค่ระบุว่าใบไหนเมื่อมีหลายใบ
     รับ: $key (LicenseKey|null), $label, $term (ค่าเริ่มต้น monthly), $block (ปุ่มเต็มความกว้าง) --}}
<form action="{{ route('cart.add', $product) }}" method="POST" @if($block ?? false) class="w-full" @endif>
    @csrf
    <input type="hidden" name="quantity" value="1">
    <input type="hidden" name="license_type" value="{{ $term ?? 'monthly' }}">
    <input type="hidden" name="buy_now" value="1">
    @if($key)
        <input type="hidden" name="renew_license" value="{{ $key->id }}">
    @endif
    <button type="submit"
            class="xp-btn-pro {{ ($block ?? false) ? 'block w-full py-3.5 text-center' : 'inline-flex items-center px-6 py-4' }} rounded-xl font-extrabold transition cursor-pointer">
        {{ $label }}
    </button>
</form>
