@extends('layouts.admin')

@section('title', 'จัดการคูปอง')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">จัดการคูปอง</h1>
            <p class="text-muted mb-0">จัดการโค้ดส่วนลดและคูปอง</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> สร้างคูปองใหม่
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">คูปองทั้งหมด</h6>
                            <h2 class="mb-0">{{ number_format($stats['total']) }}</h2>
                        </div>
                        <i class="bi bi-ticket-perforated fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">ใช้งานได้</h6>
                            <h2 class="mb-0">{{ number_format($stats['active']) }}</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">หมดอายุ</h6>
                            <h2 class="mb-0">{{ number_format($stats['expired']) }}</h2>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">ใช้งานแล้ว</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_usage']) }}</h2>
                        </div>
                        <i class="bi bi-graph-up fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาโค้ดหรือชื่อ..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- สถานะทั้งหมด --</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งานได้</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ/ปิดใช้งาน</option>
                        <option value="used_up" {{ request('status') === 'used_up' ? 'selected' : '' }}>ใช้ครบแล้ว</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
                @if(request()->hasAny(['search', 'status']))
                <div class="col-md-2">
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg me-1"></i> ล้าง
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Coupons Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">โค้ด</th>
                            <th class="border-0">ชื่อ/รายละเอียด</th>
                            <th class="border-0">ส่วนลด</th>
                            <th class="border-0">การใช้งาน</th>
                            <th class="border-0">ระยะเวลา</th>
                            <th class="border-0">สถานะ</th>
                            <th class="border-0 text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded p-2 me-2">
                                        <i class="bi bi-ticket-perforated text-primary"></i>
                                    </div>
                                    <code class="fs-6">{{ $coupon->code }}</code>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $coupon->name }}</div>
                                @if($coupon->description)
                                <small class="text-muted">{{ Str::limit($coupon->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-success fs-6">{{ $coupon->discount_label }}</span>
                                @if($coupon->min_order_amount)
                                <br><small class="text-muted">ขั้นต่ำ ฿{{ number_format($coupon->min_order_amount, 0) }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-semibold">{{ $coupon->used_count }}</span>
                                    @if($coupon->usage_limit)
                                    <span class="text-muted">/{{ $coupon->usage_limit }}</span>
                                    @else
                                    <span class="text-muted">/∞</span>
                                    @endif
                                </div>
                                @if($coupon->usage_limit)
                                <div class="progress mt-1" style="height: 4px; width: 60px;">
                                    <div class="progress-bar" style="width: {{ min(100, ($coupon->used_count / $coupon->usage_limit) * 100) }}%"></div>
                                </div>
                                @endif
                            </td>
                            <td>
                                @if($coupon->starts_at || $coupon->expires_at)
                                <small>
                                    @if($coupon->starts_at)
                                    {{ $coupon->starts_at->format('d/m/y') }}
                                    @else
                                    -
                                    @endif
                                    →
                                    @if($coupon->expires_at)
                                    {{ $coupon->expires_at->format('d/m/y') }}
                                    @else
                                    ∞
                                    @endif
                                </small>
                                @else
                                <span class="text-muted">ไม่จำกัด</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $coupon->status_color }}">{{ $coupon->status_label }}</span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.coupons.show', $coupon) }}" class="btn btn-outline-primary" title="ดูรายละเอียด">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-outline-primary" title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-{{ $coupon->is_active ? 'warning' : 'success' }}" title="{{ $coupon->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                            <i class="bi bi-{{ $coupon->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('ต้องการลบคูปองนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-ticket-perforated fs-1 d-block mb-2"></i>
                                ไม่พบคูปอง
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($coupons->hasPages())
        <div class="card-footer border-0 bg-transparent">
            {{ $coupons->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
