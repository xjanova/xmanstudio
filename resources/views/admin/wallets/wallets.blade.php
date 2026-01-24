@extends('layouts.admin')

@section('title', 'กระเป๋าเงินทั้งหมด')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">กระเป๋าเงินทั้งหมด</h1>
            <p class="text-muted mb-0">ดูและจัดการกระเป๋าเงินของผู้ใช้</p>
        </div>
        <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาชื่อหรืออีเมล..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <input type="number" name="min_balance" class="form-control" placeholder="ยอดขั้นต่ำ" value="{{ request('min_balance') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Wallets Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">ผู้ใช้</th>
                            <th class="border-0">ยอดคงเหลือ</th>
                            <th class="border-0">เติมเงินรวม</th>
                            <th class="border-0">ใช้จ่ายรวม</th>
                            <th class="border-0">สถานะ</th>
                            <th class="border-0">อัพเดทล่าสุด</th>
                            <th class="border-0 text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallets as $wallet)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <span class="avatar-title rounded-circle bg-primary text-white">
                                            {{ strtoupper(substr($wallet->user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $wallet->user->name }}</div>
                                        <small class="text-muted">{{ $wallet->user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold {{ $wallet->balance > 0 ? 'text-success' : '' }}">
                                    ฿{{ number_format($wallet->balance, 2) }}
                                </span>
                            </td>
                            <td>฿{{ number_format($wallet->total_deposited, 2) }}</td>
                            <td>฿{{ number_format($wallet->total_spent, 2) }}</td>
                            <td>
                                @if($wallet->is_active)
                                <span class="badge bg-success">ใช้งานได้</span>
                                @else
                                <span class="badge bg-danger">ปิดใช้งาน</span>
                                @endif
                            </td>
                            <td>{{ $wallet->updated_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.wallets.show', $wallet) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-1 d-block mb-2"></i>
                                ไม่พบกระเป๋าเงิน
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($wallets->hasPages())
        <div class="card-footer border-0 bg-transparent">
            {{ $wallets->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
