@extends('layouts.app')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Balance Card -->
            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white py-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="opacity-75 mb-1">ยอดเงินคงเหลือ</p>
                            <h1 class="display-5 fw-bold mb-0">฿{{ number_format($wallet->balance, 2) }}</h1>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <a href="{{ route('user.wallet.topup') }}" class="btn btn-light btn-lg">
                                <i class="bi bi-plus-lg me-1"></i> เติมเงิน
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Topups -->
            @if($pendingTopups->count() > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning bg-opacity-10 border-0">
                    <h5 class="mb-0 text-warning">
                        <i class="bi bi-clock-history me-2"></i>
                        รายการรอตรวจสอบ
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($pendingTopups as $topup)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $topup->topup_id }}</h6>
                                <small class="text-muted">
                                    {{ $topup->payment_method_label }} •
                                    {{ $topup->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="text-success fw-bold">+฿{{ number_format($topup->total_amount, 2) }}</span>
                                <br>
                                <form action="{{ route('user.wallet.cancel-topup', $topup) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('ยกเลิกรายการนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-1">ยกเลิก</button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Transactions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ประวัติล่าสุด</h5>
                    <a href="{{ route('user.wallet.transactions') }}" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($transactions as $transaction)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="bg-{{ $transaction->type_color }} bg-opacity-10 rounded-circle p-2 me-3">
                                        <i class="bi {{ $transaction->type_icon }} text-{{ $transaction->type_color }}"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $transaction->type_label }}</h6>
                                        <small class="text-muted">{{ $transaction->description }}</small>
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
                        <div class="list-group-item text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            ยังไม่มีประวัติธุรกรรม
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Stats -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">สรุป</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">เติมเงินรวม</span>
                        <span class="text-success fw-bold">฿{{ number_format($wallet->total_deposited, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">ใช้จ่ายรวม</span>
                        <span class="text-danger fw-bold">฿{{ number_format($wallet->total_spent, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">คืนเงินรวม</span>
                        <span class="text-info fw-bold">฿{{ number_format($wallet->total_refunded, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-1"></i> เกี่ยวกับ Wallet</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            ชำระเงินสะดวกรวดเร็ว
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            รับโบนัสเพิ่มเมื่อเติมเงิน
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            ดูประวัติธุรกรรมได้ตลอด
                        </li>
                        <li>
                            <i class="bi bi-check-circle text-success me-2"></i>
                            ปลอดภัย ไม่มีค่าธรรมเนียม
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
