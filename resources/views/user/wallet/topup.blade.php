@extends('layouts.app')

@section('title', 'เติมเงิน Wallet')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">เติมเงิน Wallet</h2>
                    <p class="text-muted mb-0">ยอดคงเหลือ: <span class="fw-bold text-primary">฿{{ number_format($wallet->balance, 2) }}</span></p>
                </div>
                <a href="{{ route('user.wallet.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> กลับ
                </a>
            </div>

            <!-- Bonus Tiers -->
            @if($bonusTiers->count() > 0)
            <div class="card border-0 shadow-sm mb-4 bg-success bg-opacity-10">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-gift text-success me-2"></i>โปรโมชั่นเติมเงิน</h6>
                    <div class="row g-2">
                        @foreach($bonusTiers as $tier)
                        <div class="col-md-4">
                            <div class="bg-white rounded p-2 text-center">
                                <small class="d-block text-muted">{{ $tier->range_label }}</small>
                                <span class="badge bg-success">รับโบนัส {{ $tier->bonus_label }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Topup Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('user.wallet.submit-topup') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Amount -->
                        <div class="mb-4">
                            <label class="form-label">จำนวนเงินที่ต้องการเติม <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">฿</span>
                                <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" min="100" max="100000" step="1" required placeholder="ขั้นต่ำ 100 บาท">
                            </div>
                            @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <!-- Quick amounts -->
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary quick-amount" data-amount="100">฿100</button>
                                <button type="button" class="btn btn-sm btn-outline-primary quick-amount" data-amount="300">฿300</button>
                                <button type="button" class="btn btn-sm btn-outline-primary quick-amount" data-amount="500">฿500</button>
                                <button type="button" class="btn btn-sm btn-outline-primary quick-amount" data-amount="1000">฿1,000</button>
                                <button type="button" class="btn btn-sm btn-outline-primary quick-amount" data-amount="2000">฿2,000</button>
                                <button type="button" class="btn btn-sm btn-outline-primary quick-amount" data-amount="5000">฿5,000</button>
                            </div>
                        </div>

                        <!-- Bonus Preview -->
                        <div id="bonusPreview" class="alert alert-success d-none mb-4">
                            <div class="d-flex justify-content-between">
                                <span>โบนัสที่จะได้รับ:</span>
                                <strong id="bonusAmount">฿0</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>ยอดรวมที่จะได้:</span>
                                <strong id="totalAmount">฿0</strong>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-4">
                            <label class="form-label">ช่องทางชำระเงิน <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="bank_transfer" value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }} required>
                                    <label class="btn btn-outline-primary w-100 py-3" for="bank_transfer">
                                        <i class="bi bi-bank fs-4 d-block mb-1"></i>
                                        โอนเงิน
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="promptpay" value="promptpay" {{ old('payment_method', 'promptpay') === 'promptpay' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-100 py-3" for="promptpay">
                                        <i class="bi bi-phone fs-4 d-block mb-1"></i>
                                        PromptPay
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <input type="radio" class="btn-check" name="payment_method" id="truemoney" value="truemoney" {{ old('payment_method') === 'truemoney' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-100 py-3" for="truemoney">
                                        <i class="bi bi-wallet2 fs-4 d-block mb-1"></i>
                                        TrueMoney
                                    </label>
                                </div>
                            </div>
                            @error('payment_method')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payment Reference -->
                        <div class="mb-4">
                            <label class="form-label">หมายเลขอ้างอิง/หมายเหตุ</label>
                            <input type="text" name="payment_reference" class="form-control @error('payment_reference') is-invalid @enderror"
                                   value="{{ old('payment_reference') }}" placeholder="เช่น เลขที่รายการโอน">
                            @error('payment_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Payment Proof -->
                        <div class="mb-4">
                            <label class="form-label">หลักฐานการชำระเงิน (สลิป)</label>
                            <input type="file" name="payment_proof" class="form-control @error('payment_proof') is-invalid @enderror" accept="image/*">
                            <small class="text-muted">รองรับไฟล์ภาพ ขนาดไม่เกิน 5MB</small>
                            @error('payment_proof')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Submit -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-1"></i> ส่งคำขอเติมเงิน
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">ข้อมูลการชำระเงิน</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">ธนาคารกสิกรไทย</h6>
                            <p class="mb-1"><strong>ชื่อบัญชี:</strong> XmanStudio</p>
                            <p class="mb-0"><strong>เลขบัญชี:</strong> <code>xxx-x-xxxxx-x</code></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">PromptPay</h6>
                            <p class="mb-0"><strong>หมายเลข:</strong> <code>xxx-xxx-xxxx</code></p>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        หลังจากชำระเงินแล้ว กรุณารอการตรวจสอบ 1-24 ชั่วโมง (ในวันทำการ)
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const bonusPreview = document.getElementById('bonusPreview');
    const bonusAmount = document.getElementById('bonusAmount');
    const totalAmount = document.getElementById('totalAmount');

    // Quick amount buttons
    document.querySelectorAll('.quick-amount').forEach(btn => {
        btn.addEventListener('click', function() {
            amountInput.value = this.dataset.amount;
            updateBonus();
        });
    });

    // Update bonus on amount change
    amountInput.addEventListener('input', updateBonus);

    function updateBonus() {
        const amount = parseFloat(amountInput.value) || 0;

        if (amount >= 100) {
            fetch('{{ route("user.wallet.bonus-preview") }}?amount=' + amount)
                .then(r => r.json())
                .then(data => {
                    if (data.bonus > 0) {
                        bonusPreview.classList.remove('d-none');
                        bonusAmount.textContent = '฿' + data.bonus.toLocaleString('th-TH', {minimumFractionDigits: 2});
                        totalAmount.textContent = '฿' + data.total.toLocaleString('th-TH', {minimumFractionDigits: 2});
                    } else {
                        bonusPreview.classList.add('d-none');
                    }
                });
        } else {
            bonusPreview.classList.add('d-none');
        }
    }
});
</script>
@endpush
@endsection
