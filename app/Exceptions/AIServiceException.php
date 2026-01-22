<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception for AI service errors
 */
class AIServiceException extends Exception
{
    protected $aiProvider;
    protected $aiModel;
    protected $rateLimitExceeded = false;
    protected $quotaExceeded = false;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $aiProvider = null,
        ?string $aiModel = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->aiProvider = $aiProvider;
        $this->aiModel = $aiModel;
    }

    /**
     * Create exception for rate limit error
     */
    public static function rateLimitExceeded(string $provider, int $retryAfter = 60): self
    {
        $exception = new static(
            "AI rate limit exceeded for {$provider}. Please try again in {$retryAfter} seconds.",
            429,
            null,
            $provider
        );
        $exception->rateLimitExceeded = true;
        return $exception;
    }

    /**
     * Create exception for quota exceeded
     */
    public static function quotaExceeded(string $provider): self
    {
        $exception = new static(
            "AI quota exceeded for {$provider}. Please check your billing or upgrade your plan.",
            402,
            null,
            $provider
        );
        $exception->quotaExceeded = true;
        return $exception;
    }

    /**
     * Create exception for invalid API key
     */
    public static function invalidApiKey(string $provider): self
    {
        return new static(
            "Invalid API key for {$provider}. Please check your AI settings.",
            401,
            null,
            $provider
        );
    }

    /**
     * Create exception for API timeout
     */
    public static function timeout(string $provider, int $timeout): self
    {
        return new static(
            "AI request timed out after {$timeout} seconds for {$provider}.",
            408,
            null,
            $provider
        );
    }

    /**
     * Create exception for invalid response
     */
    public static function invalidResponse(string $provider, string $reason = ''): self
    {
        $message = "Invalid response from {$provider}";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new static($message, 502, null, $provider);
    }

    /**
     * Create exception for model not found
     */
    public static function modelNotFound(string $provider, string $model): self
    {
        return new static(
            "Model '{$model}' not found for provider {$provider}.",
            404,
            null,
            $provider,
            $model
        );
    }

    /**
     * Create exception for unsupported provider
     */
    public static function unsupportedProvider(string $provider): self
    {
        return new static(
            "AI provider '{$provider}' is not supported. Supported providers: openai, claude, ollama.",
            400,
            null,
            $provider
        );
    }

    /**
     * Get user-friendly error message (safe to display)
     */
    public function getUserMessage(): string
    {
        if ($this->rateLimitExceeded) {
            return 'AI service is temporarily busy. Please try again in a few moments.';
        }

        if ($this->quotaExceeded) {
            return 'AI service quota exceeded. Please contact administrator.';
        }

        if ($this->code === 401) {
            return 'AI service authentication failed. Please contact administrator.';
        }

        if ($this->code === 408) {
            return 'AI service request timed out. Please try again.';
        }

        return 'AI service temporarily unavailable. Please try again later.';
    }

    /**
     * Check if error is rate limit related
     */
    public function isRateLimitError(): bool
    {
        return $this->rateLimitExceeded;
    }

    /**
     * Check if error is quota related
     */
    public function isQuotaError(): bool
    {
        return $this->quotaExceeded;
    }

    /**
     * Get AI provider
     */
    public function getProvider(): ?string
    {
        return $this->aiProvider;
    }

    /**
     * Get AI model
     */
    public function getModel(): ?string
    {
        return $this->aiModel;
    }
}
