<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * คำขอ/การตัดสินการถอนเงินที่ทำไม่ได้ — ข้อความเป็นภาษาไทยที่แสดงให้คนกดเห็นได้ตรง ๆ
 * (ไม่ใช่ความผิดพลาดของระบบ: ไม่ถูกรายงานเป็น error และไม่มีรายละเอียดภายในปนมา)
 */
class WithdrawalRefused extends RuntimeException {}
