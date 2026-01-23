@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่าการชำระเงิน')
@section('page-title', 'ตั้งค่าการชำระเงิน')

@section('content')
<div class="space-y-6">
    <!-- Payment Methods Settings -->
    <form action="{{ route('admin.payment-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ช่องทางการชำระเงิน</h3>

            <div class="space-y-6">
                <!-- PromptPay Settings -->
                <div class="border-b pb-6">
                    <h4 class="font-medium text-gray-900 mb-4">พร้อมเพย์ (PromptPay)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์พร้อมเพย์</label>
                            <input type="text" name="promptpay_number"
                                   value="{{ $settings['promptpay']['promptpay_number'] ?? '' }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="0812345678">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center">
                                <input type="checkbox" name="promptpay_enabled" value="1"
                                       {{ ($settings['promptpay']['promptpay_enabled'] ?? true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">เปิดใช้งานพร้อมเพย์</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Bank Transfer Settings -->
                <div class="border-b pb-6">
                    <h4 class="font-medium text-gray-900 mb-4">โอนเงินธนาคาร</h4>
                    <label class="flex items-center">
                        <input type="checkbox" name="bank_transfer_enabled" value="1"
                               {{ ($settings['bank_transfer']['bank_transfer_enabled'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานโอนเงินธนาคาร</span>
                    </label>
                </div>

                <!-- Card Payment Settings -->
                <div class="border-b pb-6">
                    <h4 class="font-medium text-gray-900 mb-4">บัตรเครดิต/เดบิต</h4>
                    <label class="flex items-center">
                        <input type="checkbox" name="card_payment_enabled" value="1"
                               {{ ($settings['card']['card_payment_enabled'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานบัตรเครดิต/เดบิต</span>
                    </label>
                </div>

                <!-- General Payment Settings -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-4">ตั้งค่าทั่วไป</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ระยะเวลารอชำระเงิน (ชั่วโมง)</label>
                            <input type="number" name="payment_timeout_hours"
                                   value="{{ $settings['general']['payment_timeout_hours'] ?? 24 }}"
                                   min="1" max="168"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ยกเลิกอัตโนมัติหลัง (ชั่วโมง)</label>
                            <input type="number" name="auto_cancel_pending_after_hours"
                                   value="{{ $settings['general']['auto_cancel_pending_after_hours'] ?? 48 }}"
                                   min="1" max="168"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </form>

    <!-- Bank Accounts -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">บัญชีธนาคาร</h3>
            <button type="button" onclick="showAddBankModal()"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                + เพิ่มบัญชี
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ธนาคาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขบัญชี</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อบัญชี</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สาขา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($bankAccounts as $account)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-medium text-gray-900">{{ $account->bank_name }}</span>
                                    <span class="ml-2 text-xs text-gray-500">({{ $account->bank_code }})</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $account->account_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $account->account_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $account->branch ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $account->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button type="button" onclick="showEditBankModal({{ json_encode($account) }})"
                                        class="text-primary-600 hover:underline mr-3">แก้ไข</button>
                                <form action="{{ route('admin.payment-settings.bank.toggle', $account) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="{{ $account->is_active ? 'text-orange-600' : 'text-green-600' }} hover:underline mr-3">
                                        {{ $account->is_active ? 'ปิด' : 'เปิด' }}
                                    </button>
                                </form>
                                <button type="button" onclick="confirmDeleteBank({{ $account->id }}, '{{ $account->bank_name }}')"
                                        class="text-red-600 hover:underline">ลบ</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                ไม่พบบัญชีธนาคาร
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Bank Modal -->
<div id="addBankModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <form action="{{ route('admin.payment-settings.bank.store') }}" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">เพิ่มบัญชีธนาคาร</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อธนาคาร</label>
                            <input type="text" name="bank_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="ธนาคารกสิกรไทย">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">รหัสธนาคาร</label>
                            <input type="text" name="bank_code" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="KBANK">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เลขบัญชี</label>
                        <input type="text" name="account_number" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="xxx-x-xxxxx-x">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อบัญชี</label>
                        <input type="text" name="account_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                               placeholder="XMAN Studio Co., Ltd.">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สาขา</label>
                            <input type="text" name="branch"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                            <input type="number" name="order" value="0" min="0"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideAddBankModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">เพิ่ม</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Bank Modal -->
<div id="editBankModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <form id="editBankForm" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">แก้ไขบัญชีธนาคาร</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อธนาคาร</label>
                            <input type="text" name="bank_name" id="edit_bank_name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">รหัสธนาคาร</label>
                            <input type="text" name="bank_code" id="edit_bank_code" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เลขบัญชี</label>
                        <input type="text" name="account_number" id="edit_account_number" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อบัญชี</label>
                        <input type="text" name="account_name" id="edit_account_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สาขา</label>
                            <input type="text" name="branch" id="edit_branch"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ลำดับ</label>
                            <input type="number" name="order" id="edit_order" min="0"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideEditBankModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Bank Modal -->
<div id="deleteBankModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="deleteBankForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ยืนยันการลบ</h3>
                <p class="text-gray-600">คุณต้องการลบบัญชี "<span id="deleteBankName"></span>" หรือไม่?</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideDeleteBankModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ลบ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showAddBankModal() {
        document.getElementById('addBankModal').classList.remove('hidden');
        document.getElementById('addBankModal').classList.add('flex');
    }

    function hideAddBankModal() {
        document.getElementById('addBankModal').classList.add('hidden');
        document.getElementById('addBankModal').classList.remove('flex');
    }

    function showEditBankModal(account) {
        document.getElementById('editBankForm').action = `/admin/payment-settings/bank/${account.id}`;
        document.getElementById('edit_bank_name').value = account.bank_name;
        document.getElementById('edit_bank_code').value = account.bank_code;
        document.getElementById('edit_account_number').value = account.account_number;
        document.getElementById('edit_account_name').value = account.account_name;
        document.getElementById('edit_branch').value = account.branch || '';
        document.getElementById('edit_order').value = account.order || 0;
        document.getElementById('edit_is_active').checked = account.is_active;
        document.getElementById('editBankModal').classList.remove('hidden');
        document.getElementById('editBankModal').classList.add('flex');
    }

    function hideEditBankModal() {
        document.getElementById('editBankModal').classList.add('hidden');
        document.getElementById('editBankModal').classList.remove('flex');
    }

    function confirmDeleteBank(accountId, bankName) {
        document.getElementById('deleteBankForm').action = `/admin/payment-settings/bank/${accountId}`;
        document.getElementById('deleteBankName').textContent = bankName;
        document.getElementById('deleteBankModal').classList.remove('hidden');
        document.getElementById('deleteBankModal').classList.add('flex');
    }

    function hideDeleteBankModal() {
        document.getElementById('deleteBankModal').classList.add('hidden');
        document.getElementById('deleteBankModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
