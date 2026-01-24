@extends('layouts.admin')

@section('title', 'แก้ไขคูปอง')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">แก้ไขคูปอง</h1>
            <p class="text-muted mb-0">แก้ไขโค้ด: <code>{{ $coupon->code }}</code></p>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST">
        @csrf
        @method('PUT')

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
                                <label class="form-label">โค้ดคูปอง <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror"
                                       value="{{ old('code', $coupon->code) }}" required>
                                @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ชื่อคูปอง <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $coupon->name) }}" required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">รายละเอียด</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $coupon->description) }}</textarea>
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
                                    <option value="percentage" {{ old('discount_type', $coupon->discount_type) === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์ (%)</option>
                                    <option value="fixed" {{ old('discount_type', $coupon->discount_type) === 'fixed' ? 'selected' : '' }}>จำนวนเงิน (฿)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">มูลค่าส่วนลด <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="discount_value" class="form-control @error('discount_value') is-invalid @enderror"
                                           value="{{ old('discount_value', $coupon->discount_value) }}" required min="0" step="0.01">
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
                                           value="{{ old('max_discount', $coupon->max_discount) }}" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ยอดสั่งซื้อขั้นต่ำ</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" name="min_order_amount" class="form-control"
                                           value="{{ old('min_order_amount', $coupon->min_order_amount) }}" min="0" step="0.01">
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
                                    <option value="{{ $product->id }}" {{ in_array($product->id, old('applicable_products', $coupon->applicable_products ?? [])) ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">กด Ctrl+Click เพื่อเลือกหลายรายการ</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ประเภท License ที่ใช้ได้</label>
                                @php $licenseTypes = old('applicable_license_types', $coupon->applicable_license_types ?? []); @endphp
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_license_types[]" value="monthly"
                                           {{ in_array('monthly', $licenseTypes) ? 'checked' : '' }}>
                                    <label class="form-check-label">รายเดือน</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_license_types[]" value="yearly"
                                           {{ in_array('yearly', $licenseTypes) ? 'checked' : '' }}>
                                    <label class="form-check-label">รายปี</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applicable_license_types[]" value="lifetime"
                                           {{ in_array('lifetime', $licenseTypes) ? 'checked' : '' }}>
                                    <label class="form-check-label">ตลอดชีพ</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="first_order_only" value="1"
                                           {{ old('first_order_only', $coupon->first_order_only) ? 'checked' : '' }}>
                                    <label class="form-check-label">สำหรับการสั่งซื้อครั้งแรกเท่านั้น</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Usage Stats -->
                <div class="card border-0 shadow-sm mb-4 bg-light">
                    <div class="card-body">
                        <h6 class="text-muted mb-3">สถิติการใช้งาน</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>ใช้งานแล้ว</span>
                            <strong>{{ $coupon->used_count }} ครั้ง</strong>
                        </div>
                        @if($coupon->usage_limit)
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar" style="width: {{ min(100, ($coupon->used_count / $coupon->usage_limit) * 100) }}%"></div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Usage Limits -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">จำนวนการใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">จำนวนที่ใช้ได้ทั้งหมด</label>
                            <input type="number" name="usage_limit" class="form-control"
                                   value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" placeholder="ไม่จำกัด">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">จำนวนที่ใช้ได้ต่อคน <span class="text-danger">*</span></label>
                            <input type="number" name="usage_limit_per_user" class="form-control"
                                   value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" min="1" required>
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
                                   value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">วันหมดอายุ</label>
                            <input type="datetime-local" name="expires_at" class="form-control"
                                   value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label">เปิดใช้งาน</label>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-lg me-1"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
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
