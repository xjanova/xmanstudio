<?php

/*
|--------------------------------------------------------------------------
| Password Broker Messages — bilingual
|--------------------------------------------------------------------------
|
| Overrides the framework's English-only defaults. The site shows Thai and
| English together everywhere else (see lang/bi/), and these strings surface on
| the public /forgot-password and /reset-password screens, so they follow the
| same rule.
|
| 'sent' is deliberately vague about whether the address is registered — see
| PasswordResetLinkController::store(), which returns it for a missing account
| too so the form cannot be used to enumerate customers.
|
*/

return [

    'reset' => 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว / Your password has been reset.',
    'sent' => 'ถ้าอีเมลนี้มีบัญชีอยู่ เราได้ส่งลิงก์ตั้งรหัสผ่านใหม่ไปให้แล้ว กรุณาตรวจสอบกล่องจดหมายและโฟลเดอร์สแปม / If an account exists for this email, we have sent a password reset link. Please check your inbox and spam folder.',
    'throttled' => 'กรุณารอสักครู่ก่อนลองใหม่ / Please wait before retrying.',
    'token' => 'ลิงก์ตั้งรหัสผ่านนี้ไม่ถูกต้องหรือหมดอายุแล้ว กรุณาขอลิงก์ใหม่ / This password reset link is invalid or has expired. Please request a new one.',
    'user' => 'ไม่พบบัญชีที่ใช้อีเมลนี้ / We can\'t find a user with that email address.',

];
