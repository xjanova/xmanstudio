<?php

namespace App\Services;

use RuntimeException;

/**
 * A wallet renewal of a license that did not go through. The message is Thai and is shown to the
 * customer as it is; $reason lets the auto-renew run tell a short wallet (tried again tomorrow)
 * from everything else.
 */
class LicenseRenewalException extends RuntimeException
{
    public const NOT_RENEWABLE = 'not_renewable';

    public const STALE = 'stale';

    public const BUSY = 'busy';

    public const SHORT = 'short';

    public function __construct(string $message, public readonly string $reason)
    {
        parent::__construct($message);
    }
}
