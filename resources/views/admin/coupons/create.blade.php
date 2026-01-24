@extends('layouts.admin')

@section('title', 'สร้างคูปองใหม่')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">สร้างคูปองใหม่</h1>
            <p class="text-muted mb-0">สร้างโค้ดส่วนลดสำหรับลูกค้า</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <form action="{{ route('admin.coupons.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">ข้อมูลคูปอง</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">โค้ดคูปอง</label>
                                <div class="input-group">
                                    <input type="text" name="code" id="couponCode" class="form-control text-uppercase @error('code') is-invalid @enderror"
                                           value="{{ old('code') }}" placeholder="ปล่อยว่างเพื่อสร้างอัตโนมัติ">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                        <i class="bi bi-shuffle"></i>
                                    </button>
                                </div>
                                @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ชื่อคูปอง <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" required placeholder="เช่น ส่วนลดปีใหม่ 2026">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="รายละเอียดเพิ่มเติม...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Discount -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">ส่วนลด</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">ประเภทส่วนลด <span class="text-danger">*</span></label>
                                <select name="discount_type" id="discountType" class="form-select" onchange="toggleMaxDiscount()">
                                    <option value="percentage" {{ old('discount_type', 'percentage') === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์ (%)</option>
                                    <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>จำนวนเงิน (฿)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">มูลค่าส่วนลด <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="discount_value" class="form-control @error('discount_value') is-invalid @enderror"
                                           value="{{ old('discount_value') }}" required min="0" step="0.01">
                                    <span class="input-group-text" id="discountUnit">%</span>
                                </div>
                                @error('discount_value')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4" id="maxDiscountGroup">
                                <label class="form-label">ส่วนลดสูงสุด</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" name="max_discount" class="form-control"
                                           value="{{ old('max_discount') }}" min="0" step="0.01" placeholder="ไม่จำกัด">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ยอดสั่งซื้อขั้นต่ำ</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" name="min_order_amount" class="form-control"
                                           value="{{ old('min_order_amount') }}" min="0" step="0.01" placeholder="ไม่จำกัด">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restrictions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">เงื่อนไขการใช้</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">สินค้าที่ใช้ได้</label>
                                <select name="applicable_products[]" class="form-select" multiple size="4">
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ in_array($product->id, old('applicable_products', [])) ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">กด Ctrl+Click เพื่อเลือกหลายรายการ (ว่างไว้ = ใช้ได้ทุกสินค้า)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ประเภท License ที่ใช้ได้</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_license_types[]" value="monthly"
                                           {{ in_array('monthly', old('applicable_license_types', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label">รายเดือน</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_license_types[]" value="yearly"
                                           {{ in_array('yearly', old('applicable_license_types', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label">รายปี</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_license_types[]" value="lifetime"
                                           {{ in_array('lifetime', old('applicable_license_types', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label">ตลอดชีพ</label>
                                </div>
                                <small class="text-muted">ไม่เลือก = ใช้ได้ทุกประเภท</small>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="first_order_only" value="1"
                                           {{ old('first_order_only') ? 'checked' : '' }}>
                                    <label class="form-check-label">สำหรับการสั่งซื้อครั้งแรกเท่านั้น</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Usage Limits -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">จำนวนการใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">จำนวนที่ใช้ได้ทั้งหมด</label>
                            <input type="number" name="usage_limit" class="form-control"
                                   value="{{ old('usage_limit') }}" min="1" placeholder="ไม่จำกัด">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">จำนวนที่ใช้ได้ต่อคน <span class="text-danger">*</span></label>
                            <input type="number" name="usage_limit_per_user" class="form-control"
                                   value="{{ old('usage_limit_per_user', 1) }}" min="1" required>
                        </div>
                    </div>
                </div>

                <!-- Validity -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">ระยะเวลา</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">วันที่เริ่มใช้ได้</label>
                            <input type="datetime-local" name="starts_at" class="form-control"
                                   value="{{ old('starts_at') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">วันหมดอายุ</label>
                            <input type="datetime-local" name="expires_at" class="form-control"
                                   value="{{ old('expires_at') }}">
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <label class="form-check-label">เปิดใช้งานทันที</label>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-1"></i> สร้างคูปอง
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function generateCode() {
    fetch('{{ route("admin.coupons.generate-code") }}')
        .then(r => r.json())
        .then(data => {
            document.getElementById('couponCode').value = data.code;
        });
}

function toggleMaxDiscount() {
    const type = document.getElementById('discountType').value;
    const unit = document.getElementById('discountUnit');
    const maxGroup = document.getElementById('maxDiscountGroup');

    if (type === 'percentage') {
        unit.textContent = '%';
        maxGroup.style.display = 'block';
    } else {
        unit.textContent = '฿';
        maxGroup.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', toggleMaxDiscount);
</script>
@endpush
@endsection
