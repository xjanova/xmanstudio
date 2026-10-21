<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A GigGok AI message could not be paid for. The code says why, so the app can
 * show the right thing (top up / you hit today's cap / account suspended).
 */
class AppAiBillingException extends RuntimeException
{
    public const INSUFFICIENT = 'insufficient_credit';

    public const DAILY_CAP = 'daily_cap';

    public const WALLET_INACTIVE = 'wallet_inactive';

    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
