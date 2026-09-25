<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * การเรียกคืนเงิน GPUxMINE ที่ต้องหยุดกลางทรานแซกชัน — ทุกอย่างที่เขียนไปแล้วย้อนกลับ
 * ข้อความเป็นภาษาไทยที่แอดมินเห็นได้ตรง ๆ (GpuxMinePayoutReversalService จับไว้เองเสมอ)
 */
class PayoutReversalRefused extends RuntimeException {}
