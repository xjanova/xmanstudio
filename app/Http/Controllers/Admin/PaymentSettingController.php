<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class PaymentSettingController extends Controller
{
    /**
     * Display payment settings
     */
    public function index()
    {
        $settings = PaymentSetting::all()->groupBy('group');
        $bankAccounts = BankAccount::ordered()->get();

        return view('admin.payment-settings.index', compact('settings', 'bankAccounts'));
    }

    /**
     * Update payment settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'promptpay_number' => 'nullable|string|max:20',
            'promptpay_enabled' => 'boolean',
            'bank_transfer_enabled' => 'boolean',
            'card_payment_enabled' => 'boolean',
            'payment_timeout_hours' => 'nullable|integer|min:1|max:168',
            'auto_cancel_pending_after_hours' => 'nullable|integer|min:1|max:168',
        ]);

        // PromptPay settings
        PaymentSetting::set('promptpay_number', $validated['promptpay_number'] ?? '', [
            'group' => 'promptpay',
            'type' => 'string',
            'label' => 'เบอร์พร้อมเพย์',
            'description' => 'เบอร์โทรศัพท์หรือเลขบัตรประชาชนสำหรับพร้อมเพย์',
        ]);

        PaymentSetting::set('promptpay_enabled', $request->boolean('promptpay_enabled'), [
            'group' => 'promptpay',
            'type' => 'boolean',
            'label' => 'เปิดใช้งานพร้อมเพย์',
        ]);

        // Bank transfer settings
        PaymentSetting::set('bank_transfer_enabled', $request->boolean('bank_transfer_enabled'), [
            'group' => 'bank_transfer',
            'type' => 'boolean',
            'label' => 'เปิดใช้งานโอนเงินธนาคาร',
        ]);

        // Card payment settings
        PaymentSetting::set('card_payment_enabled', $request->boolean('card_payment_enabled'), [
            'group' => 'card',
            'type' => 'boolean',
            'label' => 'เปิดใช้งานบัตรเครดิต/เดบิต',
        ]);

        // General payment settings
        if (isset($validated['payment_timeout_hours'])) {
            PaymentSetting::set('payment_timeout_hours', $validated['payment_timeout_hours'], [
                'group' => 'general',
                'type' => 'integer',
                'label' => 'ระยะเวลารอชำระเงิน (ชั่วโมง)',
            ]);
        }

        if (isset($validated['auto_cancel_pending_after_hours'])) {
            PaymentSetting::set('auto_cancel_pending_after_hours', $validated['auto_cancel_pending_after_hours'], [
                'group' => 'general',
                'type' => 'integer',
                'label' => 'ยกเลิกอัตโนมัติหลัง (ชั่วโมง)',
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Store a new bank account
     */
    public function storeBank(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'required|string|max:20',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:20',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $bankAccount = BankAccount::create($validated);

        return redirect()
            ->back()
            ->with('success', "บัญชี '{$bankAccount->bank_name}' ถูกเพิ่มเรียบร้อยแล้ว");
    }

    /**
     * Update a bank account
     */
    public function updateBank(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'required|string|max:20',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:20',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $bankAccount->update($validated);

        return redirect()
            ->back()
            ->with('success', "บัญชี '{$bankAccount->bank_name}' ถูกอัพเดทเรียบร้อยแล้ว");
    }

    /**
     * Toggle bank account status
     */
    public function toggleBank(BankAccount $bankAccount)
    {
        $bankAccount->update(['is_active' => ! $bankAccount->is_active]);

        $status = $bankAccount->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "บัญชี '{$bankAccount->bank_name}' ถูก{$status}แล้ว");
    }

    /**
     * Delete a bank account
     */
    public function destroyBank(BankAccount $bankAccount)
    {
        $name = $bankAccount->bank_name;
        $bankAccount->delete();

        return redirect()
            ->back()
            ->with('success', "บัญชี '{$name}' ถูกลบเรียบร้อยแล้ว");
    }
}
