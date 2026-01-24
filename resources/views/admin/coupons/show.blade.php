@extends('layouts.admin')

@section('title', 'คูปอง: ' . $coupon->code)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">รายละเอียดคูปอง</h1>
            <p class="text-muted mb-0">โค้ด: <code class="fs-5">{{ $coupon->code }}</code></p>
        </div>
        <div>
            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> แก้ไข
            </a>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> กลับ
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Coupon Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ข้อมูลคูปอง</h5>
                    <span class="badge bg-{{ $coupon->status_color }} fs-6">{{ $coupon->status_label }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">ชื่อคูปอง</p>
                            <h5>{{ $coupon->name }}</h5>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">ส่วนลด</p>
                            <h5 class="text-success">{{ $coupon->discount_label }}</h5>
                        </div>
                        @if($coupon->description)
                        <div class="col-12">
                            <p class="text-muted mb-1">รายละเอียด</p>
                            <p>{{ $coupon->description }}</p>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <p class="text-muted mb-1">ยอดขั้นต่ำ</p>
                            <p class="mb-0">{{ $coupon->min_order_amount ? '฿' . number_format($coupon->min_order_amount, 0) : 'ไม่จำกัด' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">ระยะเวลา</p>
                            <p class="mb-0">
                                @if($coupon->starts_at || $coupon->expires_at)
                                {{ $coupon->starts_at?->format('d/m/Y') ?? '-' }} →
                                {{ $coupon->expires_at?->format('d/m/Y') ?? '∞' }}
                                @else
                                ไม่จำกัด
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">สร้างเมื่อ</p>
                            <p class="mb-0">{{ $coupon->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">ประวัติการใช้งาน</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">ผู้ใช้</th>
                                    <th class="border-0">ออเดอร์</th>
                                    <th class="border-0">ยอดสั่งซื้อ</th>
                                    <th class="border-0">ส่วนลด</th>
                                    <th class="border-0">วันที่</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coupon->usages as $usage)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <span class="avatar-title rounded-circle bg-primary text-white">
                                                    {{ strtoupper(substr($usage->user->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $usage->user->name ?? 'Unknown' }}</div>
                                                <small class="text-muted">{{ $usage->user->email ?? '' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($usage->order)
                                        <a href="{{ route('admin.orders.show', $usage->order) }}">#{{ $usage->order->order_number }}</a>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>฿{{ number_format($usage->order_amount, 2) }}</td>
                                    <td class="text-success">-฿{{ number_format($usage->discount_amount, 2) }}</td>
                                    <td>{{ $usage->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        ยังไม่มีการใช้งาน
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Usage Stats -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">สถิติการใช้งาน</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>ใช้งานแล้ว</span>
                        <strong>{{ $coupon->used_count }}</strong>
                    </div>
                    @if($coupon->usage_limit)
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar bg-success" style="width: {{ min(100, ($coupon->used_count / $coupon->usage_limit) * 100) }}%">
                            {{ number_format(($coupon->used_count / $coupon->usage_limit) * 100, 0) }}%
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        เหลืออีก {{ $coupon->usage_limit - $coupon->used_count }} ครั้ง
                    </p>
                    @else
                    <p class="text-muted mb-0">ไม่จำกัดจำนวน</p>
                    @endif
                </div>
            </div>

            <!-- Restrictions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">เงื่อนไข</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            ใช้ได้ {{ $coupon->usage_limit_per_user }} ครั้ง/คน
                        </li>
                        @if($coupon->first_order_only)
                        <li class="mb-2">
                            <i class="bi bi-star text-warning me-2"></i>
                            สำหรับออเดอร์แรกเท่านั้น
                        </li>
                        @endif
                        @if($coupon->applicable_products)
                        <li class="mb-2">
                            <i class="bi bi-box text-info me-2"></i>
                            จำกัดสินค้า ({{ count($coupon->applicable_products) }} รายการ)
                        </li>
                        @endif
                        @if($coupon->applicable_license_types)
                        <li class="mb-2">
                            <i class="bi bi-key text-primary me-2"></i>
                            License: {{ implode(', ', $coupon->applicable_license_types) }}
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">จัดการ</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-{{ $coupon->is_active ? 'warning' : 'success' }} w-100">
                                <i class="bi bi-{{ $coupon->is_active ? 'pause' : 'play' }} me-1"></i>
                                {{ $coupon->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" onsubmit="return confirm('ต้องการลบคูปองนี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash me-1"></i> ลบคูปอง
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
