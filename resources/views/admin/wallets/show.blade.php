@extends('layouts.admin')

@section('title', 'กระเป๋าของ ' . $wallet->user->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">กระเป๋าเงิน</h1>
            <p class="text-muted mb-0">{{ $wallet->user->name }} - {{ $wallet->user->email }}</p>
        </div>
        <a href="{{ route('admin.wallets.wallets') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Balance Card -->
            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white py-4">
                    <div class="row">
                        <div class="col-md-4 text-center border-end border-white border-opacity-25">
                            <h6 class="opacity-75 mb-1">ยอดคงเหลือ</h6>
                            <h2 class="mb-0">฿{{ number_format($wallet->balance, 2) }}</h2>
                        </div>
                        <div class="col-md-4 text-center border-end border-white border-opacity-25">
                            <h6 class="opacity-75 mb-1">เติมเงินรวม</h6>
                            <h2 class="mb-0">฿{{ number_format($wallet->total_deposited, 2) }}</h2>
                        </div>
                        <div class="col-md-4 text-center">
                            <h6 class="opacity-75 mb-1">ใช้จ่ายรวม</h6>
                            <h2 class="mb-0">฿{{ number_format($wallet->total_spent, 2) }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">ประวัติธุรกรรม</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">รหัส</th>
                                    <th class="border-0">ประเภท</th>
                                    <th class="border-0">รายละเอียด</th>
                                    <th class="border-0">จำนวน</th>
                                    <th class="border-0">ยอดคงเหลือ</th>
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
                                        <span class="badge bg-{{ $transaction->type_color }}">
                                            <i class="bi {{ $transaction->type_icon }} me-1"></i>
                                            {{ $transaction->type_label }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->description }}</td>
                                    <td>
                                        <span class="fw-bold text-{{ $transaction->isCredit() ? 'success' : 'danger' }}">
                                            {{ $transaction->isCredit() ? '+' : '' }}฿{{ number_format($transaction->amount, 2) }}
                                        </span>
                                    </td>
                                    <td>฿{{ number_format($transaction->balance_after, 2) }}</td>
                                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        ยังไม่มีธุรกรรม
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
                                {{ strtoupper(substr($wallet->user->name, 0, 1)) }}
                            </span>
                        </div>
                        <h5 class="mb-1">{{ $wallet->user->name }}</h5>
                        <p class="text-muted mb-0">{{ $wallet->user->email }}</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">สมาชิกตั้งแต่</span>
                        <span>{{ $wallet->user->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">สถานะกระเป๋า</span>
                        @if($wallet->is_active)
                        <span class="badge bg-success">ใช้งานได้</span>
                        @else
                        <span class="badge bg-danger">ปิดใช้งาน</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Adjust Balance -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">ปรับยอดเงิน</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.wallets.adjust', $wallet) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">จำนวนเงิน <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">฿</span>
                                <input type="number" name="amount" class="form-control" required step="0.01" placeholder="ใส่ค่าลบเพื่อหัก">
                            </div>
                            <small class="text-muted">ใส่ค่าบวกเพื่อเพิ่ม, ค่าลบเพื่อหัก</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">รายละเอียด <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control" required placeholder="เช่น แก้ไขยอดเงิน">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หมายเหตุ (เฉพาะแอดมิน)</label>
                            <textarea name="admin_note" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('ยืนยันการปรับยอดเงิน?')">
                            <i class="bi bi-sliders me-1"></i> ปรับยอด
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">การจัดการ</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <form action="{{ route('admin.wallets.adjust', $wallet) }}" method="POST">
                            @csrf
                            <input type="hidden" name="amount" value="0">
                            <input type="hidden" name="description" value="{{ $wallet->is_active ? 'ปิดใช้งานกระเป๋า' : 'เปิดใช้งานกระเป๋า' }}">
                            <button type="submit" class="btn btn-{{ $wallet->is_active ? 'warning' : 'success' }} w-100">
                                <i class="bi bi-{{ $wallet->is_active ? 'pause' : 'play' }} me-1"></i>
                                {{ $wallet->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
