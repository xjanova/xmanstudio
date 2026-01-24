@extends('layouts.app')

@section('title', 'ประวัติธุรกรรม')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">ประวัติธุรกรรม</h2>
            <p class="text-muted mb-0">ยอดคงเหลือ: <span class="fw-bold text-primary">฿{{ number_format($wallet->balance, 2) }}</span></p>
        </div>
        <a href="{{ route('user.wallet.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <!-- Transactions -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($transactions as $transaction)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-{{ $transaction->type_color }} bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi {{ $transaction->type_icon }} text-{{ $transaction->type_color }} fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $transaction->type_label }}</h6>
                                <p class="text-muted mb-0 small">{{ $transaction->description }}</p>
                                <small class="text-muted">
                                    <code>{{ $transaction->transaction_id }}</code>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold fs-5 text-{{ $transaction->isCredit() ? 'success' : 'danger' }}">
                                {{ $transaction->isCredit() ? '+' : '' }}฿{{ number_format($transaction->amount, 2) }}
                            </span>
                            <br>
                            <small class="text-muted">ยอดคงเหลือ: ฿{{ number_format($transaction->balance_after, 2) }}</small>
                            <br>
                            <small class="text-muted">{{ $transaction->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="list-group-item text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                    <p class="text-muted mb-0">ยังไม่มีประวัติธุรกรรม</p>
                </div>
                @endforelse
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer bg-transparent border-0">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
