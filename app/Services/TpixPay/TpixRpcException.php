<?php

namespace App\Services\TpixPay;

use RuntimeException;

/** The chain could not be asked (node down, timeout, bad answer) — not "the payment is wrong". */
class TpixRpcException extends RuntimeException {}
