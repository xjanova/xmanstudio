<?php

/*
|--------------------------------------------------------------------------
| Shared bilingual strings (TH + EN shown together)
|--------------------------------------------------------------------------
| Reusable labels that appear across many pages. Reference with the <x-bi>
| component (<x-bi k="common.save" />) or the bi('common.save') helper.
|
| Each leaf MUST be an array with 'th' and 'en' keys.
| Page-specific copy stays inline in the Blade files via <x-bi th en />.
*/

return [
    // Actions / buttons
    'save' => ['th' => 'บันทึก', 'en' => 'Save'],
    'cancel' => ['th' => 'ยกเลิก', 'en' => 'Cancel'],
    'confirm' => ['th' => 'ยืนยัน', 'en' => 'Confirm'],
    'submit' => ['th' => 'ส่งข้อมูล', 'en' => 'Submit'],
    'close' => ['th' => 'ปิด', 'en' => 'Close'],
    'back' => ['th' => 'ย้อนกลับ', 'en' => 'Back'],
    'next' => ['th' => 'ถัดไป', 'en' => 'Next'],
    'continue' => ['th' => 'ดำเนินการต่อ', 'en' => 'Continue'],
    'edit' => ['th' => 'แก้ไข', 'en' => 'Edit'],
    'update' => ['th' => 'อัปเดต', 'en' => 'Update'],
    'delete' => ['th' => 'ลบ', 'en' => 'Delete'],
    'remove' => ['th' => 'นำออก', 'en' => 'Remove'],
    'search' => ['th' => 'ค้นหา', 'en' => 'Search'],
    'view' => ['th' => 'ดู', 'en' => 'View'],
    'view_details' => ['th' => 'ดูรายละเอียด', 'en' => 'View Details'],
    'download' => ['th' => 'ดาวน์โหลด', 'en' => 'Download'],
    'print' => ['th' => 'พิมพ์', 'en' => 'Print'],
    'copy' => ['th' => 'คัดลอก', 'en' => 'Copy'],
    'copied' => ['th' => 'คัดลอกแล้ว', 'en' => 'Copied'],
    'send' => ['th' => 'ส่ง', 'en' => 'Send'],
    'retry' => ['th' => 'ลองใหม่', 'en' => 'Try Again'],
    'add' => ['th' => 'เพิ่ม', 'en' => 'Add'],

    // Auth
    'login' => ['th' => 'เข้าสู่ระบบ', 'en' => 'Log In'],
    'logout' => ['th' => 'ออกจากระบบ', 'en' => 'Log Out'],
    'register' => ['th' => 'สมัครสมาชิก', 'en' => 'Register'],
    'email' => ['th' => 'อีเมล', 'en' => 'Email'],
    'password' => ['th' => 'รหัสผ่าน', 'en' => 'Password'],
    'confirm_password' => ['th' => 'ยืนยันรหัสผ่าน', 'en' => 'Confirm Password'],
    'current_password' => ['th' => 'รหัสผ่านปัจจุบัน', 'en' => 'Current Password'],
    'new_password' => ['th' => 'รหัสผ่านใหม่', 'en' => 'New Password'],
    'name' => ['th' => 'ชื่อ', 'en' => 'Name'],
    'full_name' => ['th' => 'ชื่อ-นามสกุล', 'en' => 'Full Name'],
    'phone' => ['th' => 'เบอร์โทรศัพท์', 'en' => 'Phone'],
    'remember_me' => ['th' => 'จดจำฉันไว้', 'en' => 'Remember me'],
    'forgot_password' => ['th' => 'ลืมรหัสผ่าน?', 'en' => 'Forgot password?'],
    'already_registered' => ['th' => 'มีบัญชีอยู่แล้ว?', 'en' => 'Already have an account?'],
    'no_account' => ['th' => 'ยังไม่มีบัญชี?', 'en' => "Don't have an account?"],

    // Account / member area
    'home' => ['th' => 'หน้าหลัก', 'en' => 'Home'],
    'dashboard' => ['th' => 'แดชบอร์ด', 'en' => 'Dashboard'],
    'profile' => ['th' => 'โปรไฟล์', 'en' => 'Profile'],
    'account' => ['th' => 'บัญชีของฉัน', 'en' => 'My Account'],
    'settings' => ['th' => 'ตั้งค่า', 'en' => 'Settings'],
    'my_orders' => ['th' => 'คำสั่งซื้อของฉัน', 'en' => 'My Orders'],
    'wallet' => ['th' => 'กระเป๋าเงิน', 'en' => 'Wallet'],

    // Checkout / payment
    'cart' => ['th' => 'ตะกร้าสินค้า', 'en' => 'Cart'],
    'checkout' => ['th' => 'ชำระเงิน', 'en' => 'Checkout'],
    'payment' => ['th' => 'การชำระเงิน', 'en' => 'Payment'],
    'order_summary' => ['th' => 'สรุปคำสั่งซื้อ', 'en' => 'Order Summary'],
    'order_number' => ['th' => 'เลขที่คำสั่งซื้อ', 'en' => 'Order No.'],
    'payment_method' => ['th' => 'ช่องทางการชำระเงิน', 'en' => 'Payment Method'],
    'proceed_to_payment' => ['th' => 'ดำเนินการชำระเงิน', 'en' => 'Proceed to Payment'],
    'upload_slip' => ['th' => 'อัปโหลดสลิป', 'en' => 'Upload Slip'],
    'subtotal' => ['th' => 'ยอดรวม', 'en' => 'Subtotal'],
    'total' => ['th' => 'ยอดรวมทั้งสิ้น', 'en' => 'Total'],
    'amount' => ['th' => 'จำนวนเงิน', 'en' => 'Amount'],
    'quantity' => ['th' => 'จำนวน', 'en' => 'Quantity'],
    'price' => ['th' => 'ราคา', 'en' => 'Price'],
    'discount' => ['th' => 'ส่วนลด', 'en' => 'Discount'],
    'vat' => ['th' => 'ภาษีมูลค่าเพิ่ม', 'en' => 'VAT'],

    // Generic labels / table headers
    'status' => ['th' => 'สถานะ', 'en' => 'Status'],
    'date' => ['th' => 'วันที่', 'en' => 'Date'],
    'actions' => ['th' => 'จัดการ', 'en' => 'Actions'],
    'details' => ['th' => 'รายละเอียด', 'en' => 'Details'],
    'description' => ['th' => 'คำอธิบาย', 'en' => 'Description'],
    'all' => ['th' => 'ทั้งหมด', 'en' => 'All'],
    'yes' => ['th' => 'ใช่', 'en' => 'Yes'],
    'no' => ['th' => 'ไม่', 'en' => 'No'],
    'required' => ['th' => 'จำเป็น', 'en' => 'Required'],
    'optional' => ['th' => 'ไม่บังคับ', 'en' => 'Optional'],
    'loading' => ['th' => 'กำลังโหลด...', 'en' => 'Loading...'],
    'no_data' => ['th' => 'ไม่มีข้อมูล', 'en' => 'No data'],

    // Statuses
    'status_pending' => ['th' => 'รอดำเนินการ', 'en' => 'Pending'],
    'status_processing' => ['th' => 'กำลังดำเนินการ', 'en' => 'Processing'],
    'status_completed' => ['th' => 'เสร็จสิ้น', 'en' => 'Completed'],
    'status_cancelled' => ['th' => 'ยกเลิก', 'en' => 'Cancelled'],
    'status_failed' => ['th' => 'ล้มเหลว', 'en' => 'Failed'],
    'status_paid' => ['th' => 'ชำระแล้ว', 'en' => 'Paid'],
    'status_unpaid' => ['th' => 'ยังไม่ชำระ', 'en' => 'Unpaid'],
    'status_expired' => ['th' => 'หมดอายุ', 'en' => 'Expired'],
    'status_active' => ['th' => 'ใช้งานอยู่', 'en' => 'Active'],
    'status_inactive' => ['th' => 'ปิดใช้งาน', 'en' => 'Inactive'],
];
