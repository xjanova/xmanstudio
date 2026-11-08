<?php

namespace App\Services;

use RuntimeException;

/**
 * Facebook refused or could not be reached. The message is already Thai and already says what to do
 * (reconnect, grant a permission, wait) — show it as it is, never a bare "try again".
 */
class FacebookPageException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = false,
        public readonly ?int $graphCode = null,
        public readonly bool $needsReconnect = false,
    ) {
        parent::__construct($message);
    }
}
