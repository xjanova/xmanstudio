@extends('layouts.admin')

@section('title', 'ธุรกรรมทั้งหมด')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">ธุรกรรมทั้งหมด</h1>
            <p class="text-muted mb-0">ดูประวัติธุรกรรมทั้งหมดในระบบ</p>
        </div>
        <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหา ID หรือชื่อ..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">-- ประเภททั้งหมด --</option>
                        <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>เติมเงิน</option>
                        <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>ชำระเงิน</option>
                        <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>คืนเงิน</option>
                        <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>โบนัส</option>
                        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>ปรับยอด</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">-- สถานะทั้งหมด --</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
                @if(request()->hasAny(['search', 'type', 'status']))
                <div class="col-md-2">
                    <a href="{{ route('admin.wallets.transactions') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-lg me-1"></i> ล้าง
                    </a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">รหัส</th>
                            <th class="border-0">ผู้ใช้</th>
                            <th class="border-0">ประเภท</th>
                            <th class="border-0">รายละเอียด</th>
                            <th class="border-0">จำนวน</th>
                            <th class="border-0">สถานะ</th>
                            <th class="border-0">วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <code class="small">{{ $transaction->transaction_id }}</code>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <span class="avatar-title rounded-circle bg-primary text-white small">
                                            {{ strtoupper(substr($transaction->user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $transaction->user->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $transaction->type_color }}">
                                    <i class="bi {{ $transaction->type_icon }} me-1"></i>
                                    {{ $transaction->type_label }}
                                </span>
                            </td>
                            <td>{{ Str::limit($transaction->description, 30) }}</td>
                            <td>
                                <span class="fw-bold text-{{ $transaction->isCredit() ? 'success' : 'danger' }}">
                                    {{ $transaction->isCredit() ? '+' : '' }}฿{{ number_format($transaction->amount, 2) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $transaction->status_color }}">{{ $transaction->status_label }}</span>
                            </td>
                            <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                ไม่พบธุรกรรม
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer border-0 bg-transparent">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
