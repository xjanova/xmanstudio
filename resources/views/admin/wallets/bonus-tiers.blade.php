@extends('layouts.admin')

@section('title', 'โบนัสเติมเงิน')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">โบนัสเติมเงิน</h1>
            <p class="text-muted mb-0">ตั้งค่าโบนัสเมื่อเติมเงินตามยอด</p>
        </div>
        <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> กลับ
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Existing Tiers -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">รายการโบนัส</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">ช่วงยอดเติมเงิน</th>
                                    <th class="border-0">โบนัส</th>
                                    <th class="border-0">สถานะ</th>
                                    <th class="border-0 text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tiers as $tier)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $tier->range_label }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6">{{ $tier->bonus_label }}</span>
                                    </td>
                                    <td>
                                        @if($tier->is_active)
                                        <span class="badge bg-success">เปิดใช้งาน</span>
                                        @else
                                        <span class="badge bg-secondary">ปิดใช้งาน</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $tier->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.wallets.bonus-tiers.destroy', $tier) }}" method="POST" class="d-inline" onsubmit="return confirm('ต้องการลบ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal{{ $tier->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.wallets.bonus-tiers.update', $tier) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">แก้ไขโบนัส</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-6">
                                                            <label class="form-label">ยอดขั้นต่ำ <span class="text-danger">*</span></label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">฿</span>
                                                                <input type="number" name="min_amount" class="form-control" value="{{ $tier->min_amount }}" required min="0">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label">ยอดสูงสุด</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">฿</span>
                                                                <input type="number" name="max_amount" class="form-control" value="{{ $tier->max_amount }}" min="0" placeholder="ไม่จำกัด">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                                                            <select name="bonus_type" class="form-select">
                                                                <option value="percentage" {{ $tier->bonus_type === 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์</option>
                                                                <option value="fixed" {{ $tier->bonus_type === 'fixed' ? 'selected' : '' }}>จำนวนเงิน</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label">ค่าโบนัส <span class="text-danger">*</span></label>
                                                            <input type="number" name="bonus_value" class="form-control" value="{{ $tier->bonus_value }}" required min="0" step="0.01">
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $tier->is_active ? 'checked' : '' }}>
                                                                <label class="form-check-label">เปิดใช้งาน</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                                    <button type="submit" class="btn btn-primary">บันทึก</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-gift fs-1 d-block mb-2"></i>
                                        ยังไม่มีโบนัสที่ตั้งค่า
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Add New Tier -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">เพิ่มโบนัสใหม่</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.wallets.bonus-tiers.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">ยอดขั้นต่ำ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">฿</span>
                                <input type="number" name="min_amount" class="form-control" required min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ยอดสูงสุด</label>
                            <div class="input-group">
                                <span class="input-group-text">฿</span>
                                <input type="number" name="max_amount" class="form-control" min="0" placeholder="ไม่จำกัด">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ประเภทโบนัส <span class="text-danger">*</span></label>
                            <select name="bonus_type" class="form-select">
                                <option value="percentage">เปอร์เซ็นต์ (%)</option>
                                <option value="fixed">จำนวนเงิน (฿)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ค่าโบนัส <span class="text-danger">*</span></label>
                            <input type="number" name="bonus_value" class="form-control" required min="0" step="0.01" placeholder="เช่น 5 หรือ 100">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">เปิดใช้งาน</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มโบนัส
                        </button>
                    </form>
                </div>
            </div>

            <!-- Example -->
            <div class="card border-0 shadow-sm mt-4 bg-light">
                <div class="card-body">
                    <h6 class="text-muted mb-3">ตัวอย่างโบนัส</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            เติม ฿500-999 รับโบนัส 5%
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            เติม ฿1,000-2,999 รับโบนัส 10%
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            เติม ฿3,000+ รับโบนัส 15%
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
