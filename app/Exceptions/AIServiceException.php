<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception for AI service errors with user-friendly messages
 */
class AIServiceException extends Exception
{
    protected $aiProvider;

    protected $aiModel;

    protected $retryAfter;

    protected $context = [];

    protected $rateLimitExceeded = false;

    protected $quotaExceeded = false;

    public function __construct(
        string $provider,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->aiProvider = $provider;
        $this->context['provider'] = $provider;
    }

    /**
     * Create exception for rate limit error
     */
    public static function rateLimitExceeded(string $provider, int $retryAfter = 60): self
    {
        $exception = new static(
            $provider,
            "AI rate limit exceeded for {$provider}. Please try again in {$retryAfter} seconds.",
            429
        );
        $exception->rateLimitExceeded = true;
        $exception->retryAfter = $retryAfter;
        $exception->context['retry_after'] = $retryAfter;

        return $exception;
    }

    /**
     * Create exception for quota exceeded
     */
    public static function quotaExceeded(string $provider): self
    {
        $exception = new static(
            $provider,
            "AI quota exceeded for {$provider}. Please check your billing or upgrade your plan.",
            429
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
            $provider,
            "Invalid API key for {$provider}. Please check your AI settings.",
            401
        );
    }

    /**
     * Create exception for service unavailable
     */
    public static function serviceUnavailable(string $provider): self
    {
        return new static(
            $provider,
            "AI service {$provider} is currently unavailable.",
            503
        );
    }

    /**
     * Create exception for API timeout
     */
    public static function timeout(string $provider, int $timeout): self
    {
        $exception = new static(
            $provider,
            "AI request timeout after {$timeout} seconds for {$provider}.",
            504
        );
        $exception->context['timeout'] = $timeout;

        return $exception;
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

        $exception = new static($provider, $message, 500);
        if ($reason) {
            $exception->context['reason'] = $reason;
        }

        return $exception;
    }

    /**
     * Create exception for content policy violation
     */
    public static function contentPolicyViolation(string $provider, string $reason = ''): self
    {
        $message = "Content policy violation for {$provider}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        $exception = new static($provider, $message, 400);
        if ($reason) {
            $exception->context['reason'] = $reason;
        }

        return $exception;
    }

    /**
     * Create exception for model not found
     */
    public static function modelNotFound(string $provider, string $model): self
    {
        $exception = new static(
            $provider,
            "Model '{$model}' not found for provider {$provider}.",
            404
        );
        $exception->aiModel = $model;
        $exception->context['model'] = $model;

        return $exception;
    }

    /**
     * Create exception for network error
     */
    public static function networkError(string $provider, string $details = ''): self
    {
        $message = "Network error connecting to {$provider}";
        if ($details) {
            $message .= ": {$details}";
        }

        $exception = new static($provider, $message, 0);
        if ($details) {
            $exception->context['details'] = $details;
        }

        return $exception;
    }

    /**
     * Create exception for unsupported provider
     */
    public static function unsupportedProvider(string $provider): self
    {
        return new static(
            $provider,
            "AI provider '{$provider}' is not supported. Supported providers: openai, gemini, claude, ollama.",
            400
        );
    }

    /**
     * Get user-friendly error message (safe to display)
     */
    public function getUserMessage(): string
    {
        if ($this->rateLimitExceeded) {
            return 'The AI service has received too many requests. Please try again in a few moments.';
        }

        if ($this->quotaExceeded) {
            return 'AI service usage limit has been reached. Please contact the administrator.';
        }

        if ($this->code === 401) {
            return 'AI service configuration issue detected. Please contact the administrator.';
        }

        if ($this->code === 503) {
            return 'AI service is currently unavailable. Please try again later.';
        }

        if ($this->code === 504) {
            return 'The request is taking too long. Please try again.';
        }

        if ($this->code === 500) {
            return 'An unexpected response error occurred. Please try again later.';
        }

        if ($this->code === 400) {
            return 'The content violates the AI service content policy. Please modify your request.';
        }

        if ($this->code === 404) {
            return 'AI service configuration issue detected. Please contact the administrator.';
        }

        if ($this->code === 0) {
            return 'Unable to connect to AI service. Please check your connection and try again.';
        }

        return 'AI service encountered an error. Please try again later.';
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

    /**
     * Get retry after seconds (for rate limit errors)
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get exception context
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
