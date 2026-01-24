@extends('layouts.admin')

@section('title', 'รายการเติมเงิน')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">รายการเติมเงิน</h1>
            <p class="text-muted mb-0">อนุมัติและจัดการรายการเติมเงิน</p>
        </div>
        <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">รอตรวจสอบ</h6>
                            <h2 class="mb-0">{{ $stats['pending'] }}</h2>
                        </div>
                        <i class="bi bi-clock-history fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">อนุมัติแล้ว</h6>
                            <h2 class="mb-0">{{ $stats['approved'] }}</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">ยอดอนุมัติรวม</h6>
                            <h2 class="mb-0">฿{{ number_format($stats['total_approved'], 2) }}</h2>
                        </div>
                        <i class="bi bi-cash-stack fs-1 opacity-50"></i>
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
                    <input type="text" name="search" class="form-control" placeholder="ค้นหา ID หรือชื่อ..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- สถานะทั้งหมด --</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>อนุมัติแล้ว</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
                @if(request()->hasAny(['search', 'status']))
                <div class="col-md-2">
                    <a href="{{ route('admin.wallets.topups') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg me-1"></i> ล้าง
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Topups Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">รหัส</th>
                            <th class="border-0">ผู้ใช้</th>
                            <th class="border-0">จำนวน</th>
                            <th class="border-0">โบนัส</th>
                            <th class="border-0">รวม</th>
                            <th class="border-0">ช่องทาง</th>
                            <th class="border-0">สถานะ</th>
                            <th class="border-0">วันที่</th>
                            <th class="border-0 text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topups as $topup)
                        <tr>
                            <td>
                                <code>{{ $topup->topup_id }}</code>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-semibold">{{ $topup->user->name }}</div>
                                    <small class="text-muted">{{ $topup->user->email }}</small>
                                </div>
                            </td>
                            <td>฿{{ number_format($topup->amount, 2) }}</td>
                            <td>
                                @if($topup->bonus_amount > 0)
                                <span class="text-success">+฿{{ number_format($topup->bonus_amount, 2) }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td class="fw-bold text-success">฿{{ number_format($topup->total_amount, 2) }}</td>
                            <td>{{ $topup->payment_method_label }}</td>
                            <td>
                                <span class="badge bg-{{ $topup->status_color }}">{{ $topup->status_label }}</span>
                            </td>
                            <td>
                                {{ $topup->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-end">
                                @if($topup->status === 'pending')
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.wallets.topups.show', $topup) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('admin.wallets.topups.approve', $topup) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('ยืนยันอนุมัติรายการนี้?')">
                                        @csrf
                                        <button type="submit" class="btn btn-success" title="อนุมัติ">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $topup->id }}" title="ปฏิเสธ">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal{{ $topup->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.wallets.topups.reject', $topup) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">ปฏิเสธรายการ</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">เหตุผล <span class="text-danger">*</span></label>
                                                        <textarea name="reason" class="form-control" rows="3" required placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                                    <button type="submit" class="btn btn-danger">ปฏิเสธ</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <a href="{{ route('admin.wallets.topups.show', $topup) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-credit-card fs-1 d-block mb-2"></i>
                                ไม่พบรายการ
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($topups->hasPages())
        <div class="card-footer border-0 bg-transparent">
            {{ $topups->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
