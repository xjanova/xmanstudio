@extends('layouts.admin')

@section('title', 'รายการเติมเงิน: ' . $topup->topup_id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">รายละเอียดการเติมเงิน</h1>
            <p class="text-muted mb-0">รหัส: <code>{{ $topup->topup_id }}</code></p>
        </div>
        <a href="{{ route('admin.wallets.topups') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Topup Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ข้อมูลรายการ</h5>
                    <span class="badge bg-{{ $topup->status_color }} fs-6">{{ $topup->status_label }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <p class="text-muted mb-1">ยอดเติมเงิน</p>
                            <h4>฿{{ number_format($topup->amount, 2) }}</h4>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">โบนัส</p>
                            <h4 class="text-success">+฿{{ number_format($topup->bonus_amount, 2) }}</h4>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted mb-1">ยอดรวม</p>
                            <h4 class="text-primary">฿{{ number_format($topup->total_amount, 2) }}</h4>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">ช่องทางชำระเงิน</p>
                            <p class="mb-0 fs-5">{{ $topup->payment_method_label }}</p>
                        </div>
                        @if($topup->payment_reference)
                        <div class="col-md-6">
                            <p class="text-muted mb-1">หมายเลขอ้างอิง</p>
                            <p class="mb-0"><code>{{ $topup->payment_reference }}</code></p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <p class="text-muted mb-1">วันที่สร้าง</p>
                            <p class="mb-0">{{ $topup->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        @if($topup->approved_at)
                        <div class="col-md-6">
                            <p class="text-muted mb-1">วันที่{{ $topup->status === 'approved' ? 'อนุมัติ' : 'ปฏิเสธ' }}</p>
                            <p class="mb-0">{{ $topup->approved_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payment Proof -->
            @if($topup->payment_proof)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">หลักฐานการชำระเงิน</h5>
                </div>
                <div class="card-body text-center">
                    <a href="{{ asset('storage/' . $topup->payment_proof) }}" target="_blank">
                        <img src="{{ asset('storage/' . $topup->payment_proof) }}" class="img-fluid rounded" style="max-height: 400px;">
                    </a>
                    <p class="text-muted mt-2 mb-0">คลิกเพื่อดูขนาดเต็ม</p>
                </div>
            </div>
            @endif

            <!-- Reject Reason -->
            @if($topup->status === 'rejected' && $topup->reject_reason)
            <div class="card border-0 shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-x-circle me-2"></i>เหตุผลที่ปฏิเสธ</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $topup->reject_reason }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- User Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">ข้อมูลผู้ใช้</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-lg mx-auto mb-2">
                            <span class="avatar-title rounded-circle bg-primary text-white fs-1">
                                {{ strtoupper(substr($topup->user->name, 0, 1)) }}
                            </span>
                        </div>
                        <h5 class="mb-1">{{ $topup->user->name }}</h5>
                        <p class="text-muted mb-0">{{ $topup->user->email }}</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">ยอดคงเหลือปัจจุบัน</span>
                        <span class="fw-bold">฿{{ number_format($topup->wallet->balance ?? 0, 2) }}</span>
                    </div>
                    <a href="{{ route('admin.wallets.show', $topup->wallet) }}" class="btn btn-outline-primary w-100 mt-2">
                        <i class="bi bi-wallet2 me-1"></i> ดูกระเป๋าเงิน
                    </a>
                </div>
            </div>

            <!-- Actions -->
            @if($topup->status === 'pending')
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">ดำเนินการ</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.wallets.topups.approve', $topup) }}" method="POST" onsubmit="return confirm('ยืนยันอนุมัติรายการนี้?')">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-check-lg me-1"></i> อนุมัติ
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-lg me-1"></i> ปฏิเสธ
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reject Modal -->
            <div class="modal fade" id="rejectModal" tabindex="-1">
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
            @endif

            <!-- Approver Info -->
            @if($topup->approvedBy)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">{{ $topup->status === 'approved' ? 'อนุมัติโดย' : 'ปฏิเสธโดย' }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <span class="avatar-title rounded-circle bg-secondary text-white">
                                {{ strtoupper(substr($topup->approvedBy->name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $topup->approvedBy->name }}</div>
                            <small class="text-muted">{{ $topup->approved_at->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
