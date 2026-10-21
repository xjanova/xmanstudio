<?php

namespace App\Services\TpixPay;

use RuntimeException;

/** No quote could be made; the message is Thai and safe to show the customer. */
class TpixQuoteException extends RuntimeException {}
