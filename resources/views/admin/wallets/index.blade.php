@extends('layouts.admin')

@section('title', 'ระบบ Wallet')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">ระบบ Wallet</h1>
            <p class="text-muted mb-0">ภาพรวมกระเป๋าเงินและธุรกรรม</p>
        </div>
        <div>
            <a href="{{ route('admin.wallets.topups') }}" class="btn btn-warning position-relative">
                <i class="bi bi-clock-history me-1"></i> รอตรวจสอบ
                @if($stats['pending_topups'] > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $stats['pending_topups'] }}
                </span>
                @endif
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">ยอดรวมในระบบ</h6>
                            <h2 class="mb-0">฿{{ number_format($stats['total_balance'], 2) }}</h2>
                        </div>
                        <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">เติมเงินทั้งหมด</h6>
                            <h2 class="mb-0">฿{{ number_format($stats['total_deposited'], 2) }}</h2>
                        </div>
                        <i class="bi bi-plus-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">ใช้จ่ายทั้งหมด</h6>
                            <h2 class="mb-0">฿{{ number_format($stats['total_spent'], 2) }}</h2>
                        </div>
                        <i class="bi bi-cart fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 opacity-75">กระเป๋าทั้งหมด</h6>
                            <h2 class="mb-0">{{ number_format($stats['total_wallets']) }}</h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="{{ route('admin.wallets.wallets') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-wallet2 fs-1 text-primary mb-2"></i>
                    <h5 class="mb-1">กระเป๋าเงินทั้งหมด</h5>
                    <p class="text-muted mb-0">ดูและจัดการกระเป๋า</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.wallets.topups') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-credit-card fs-1 text-success mb-2"></i>
                    <h5 class="mb-1">รายการเติมเงิน</h5>
                    <p class="text-muted mb-0">อนุมัติการเติมเงิน</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.wallets.transactions') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-arrow-left-right fs-1 text-info mb-2"></i>
                    <h5 class="mb-1">ธุรกรรมทั้งหมด</h5>
                    <p class="text-muted mb-0">ดูประวัติทั้งหมด</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.wallets.bonus-tiers') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-gift fs-1 text-warning mb-2"></i>
                    <h5 class="mb-1">โบนัสเติมเงิน</h5>
                    <p class="text-muted mb-0">ตั้งค่าโบนัส</p>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Pending Topups -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock text-warning me-2"></i>
                        รอตรวจสอบ
                    </h5>
                    <a href="{{ route('admin.wallets.topups', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($pendingTopups as $topup)
                        <a href="{{ route('admin.wallets.topups.show', $topup) }}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $topup->user->name }}</h6>
                                    <small class="text-muted">{{ $topup->topup_id }} • {{ $topup->payment_method_label }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="text-success fw-bold">+฿{{ number_format($topup->total_amount, 2) }}</span>
                                    <br>
                                    <small class="text-muted">{{ $topup->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </a>
                        @empty
                        <div class="list-group-item text-center py-4 text-muted">
                            <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                            ไม่มีรายการรอตรวจสอบ
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-left-right text-info me-2"></i>
                        ธุรกรรมล่าสุด
                    </h5>
                    <a href="{{ route('admin.wallets.transactions') }}" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentTransactions as $transaction)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="bg-{{ $transaction->type_color }} bg-opacity-10 rounded p-2 me-3">
                                        <i class="bi {{ $transaction->type_icon }} text-{{ $transaction->type_color }}"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $transaction->user->name }}</h6>
                                        <small class="text-muted">{{ $transaction->type_label }} • {{ $transaction->description }}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-{{ $transaction->isCredit() ? 'success' : 'danger' }}">
                                        {{ $transaction->isCredit() ? '+' : '' }}฿{{ number_format($transaction->amount, 2) }}
                                    </span>
                                    <br>
                                    <small class="text-muted">{{ $transaction->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="list-group-item text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            ยังไม่มีธุรกรรม
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
