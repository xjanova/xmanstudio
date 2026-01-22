@extends('layouts.admin')

@section('title', 'แก้ไขโครงการ')
@section('page-title', 'แก้ไขโครงการ: ' . $project->project_name)

@section('content')
<form action="{{ route('admin.projects.update', $project) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลโครงการ</h3>

                <div class="mb-4">
                    <span class="text-sm text-gray-500">หมายเลขโครงการ:</span>
                    <span class="ml-2 font-mono font-semibold">{{ $project->project_number }}</span>
                </div>

                @if($project->quotation)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                        <span class="text-blue-700 text-sm">สร้างจากใบเสนอราคา #{{ $project->quotation->quote_number }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อโครงการ <span class="text-red-500">*</span></label>
                        <input type="text" name="project_name" value="{{ old('project_name', $project->project_name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทโครงการ <span class="text-red-500">*</span></label>
                        <select name="project_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @foreach(\App\Models\ProjectOrder::TYPE_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ old('project_type', $project->project_type) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ <span class="text-red-500">*</span></label>
                        <select name="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            @foreach(\App\Models\ProjectOrder::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $project->status) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดโครงการ</label>
                        <textarea name="project_description" rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('project_description', $project->project_description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">กำหนดการ</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">วันเริ่มโครงการ</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">กำหนดส่งมอบ</label>
                        <input type="date" name="expected_end_date" value="{{ old('expected_end_date', $project->expected_end_date?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>

            <!-- Progress -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ความคืบหน้า</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เปอร์เซ็นต์ความคืบหน้า</label>
                    <div class="flex items-center gap-4">
                        <input type="range" name="progress_percent" value="{{ old('progress_percent', $project->progress_percent) }}"
                               min="0" max="100" class="flex-1"
                               oninput="document.getElementById('progress_value').textContent = this.value + '%'">
                        <span id="progress_value" class="font-semibold text-lg w-16 text-right">{{ $project->progress_percent }}%</span>
                    </div>
                </div>
            </div>

            <!-- URLs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ลิงก์ที่เกี่ยวข้อง</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Repository URL</label>
                        <input type="url" name="repository_url" value="{{ old('repository_url', $project->repository_url) }}"
                               placeholder="https://github.com/..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Staging URL</label>
                        <input type="url" name="staging_url" value="{{ old('staging_url', $project->staging_url) }}"
                               placeholder="https://staging.example.com"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Production URL</label>
                        <input type="url" name="production_url" value="{{ old('production_url', $project->production_url) }}"
                               placeholder="https://example.com"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Payment -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">การชำระเงิน</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ราคารวม (บาท)</label>
                        <input type="number" name="total_price" value="{{ old('total_price', $project->total_price) }}" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ยอดชำระแล้ว (บาท)</label>
                        <input type="number" name="paid_amount" value="{{ old('paid_amount', $project->paid_amount) }}" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">สถานะการชำระ</label>
                        <select name="payment_status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="unpaid" {{ old('payment_status', $project->payment_status) == 'unpaid' ? 'selected' : '' }}>ยังไม่ชำระ</option>
                            <option value="partial" {{ old('payment_status', $project->payment_status) == 'partial' ? 'selected' : '' }}>ชำระบางส่วน</option>
                            <option value="paid" {{ old('payment_status', $project->payment_status) == 'paid' ? 'selected' : '' }}>ชำระครบแล้ว</option>
                        </select>
                    </div>

                    @if($project->remaining_amount > 0)
                        <div class="pt-2 border-t">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">ยอดคงเหลือ</span>
                                <span class="font-semibold text-red-600">{{ number_format($project->remaining_amount, 2) }} บาท</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">บันทึก</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">บันทึกสำหรับแอดมิน</label>
                        <textarea name="admin_notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('admin_notes', $project->admin_notes) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">บันทึกสำหรับลูกค้า</label>
                        <textarea name="customer_notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">{{ old('customer_notes', $project->customer_notes) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">ลูกค้าจะเห็นบันทึกนี้ในหน้าติดตามโครงการ</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <button type="submit" class="w-full px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                    บันทึกการเปลี่ยนแปลง
                </button>
                <a href="{{ route('admin.projects.show', $project) }}" class="block text-center mt-3 text-gray-600 hover:text-gray-900">
                    ยกเลิก
                </a>
            </div>

            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">ลิงก์ด่วน</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.projects.show', $project) }}" class="block text-primary-600 hover:underline">
                        ดูหน้าโครงการเต็ม
                    </a>
                    @if($project->user)
                        <a href="{{ route('admin.users.show', $project->user) }}" class="block text-primary-600 hover:underline">
                            ดูข้อมูลลูกค้า
                        </a>
                    @endif
                    @if($project->quotation)
                        <a href="{{ route('admin.quotations.show', $project->quotation) }}" class="block text-primary-600 hover:underline">
                            ดูใบเสนอราคา
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
