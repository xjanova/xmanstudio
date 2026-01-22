<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception for YouTube API errors
 */
class YouTubeAPIException extends Exception
{
    protected $quotaExceeded = false;
    protected $rateLimitExceeded = false;
    protected $videoId;
    protected $commentId;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $videoId = null,
        ?string $commentId = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->videoId = $videoId;
        $this->commentId = $commentId;
    }

    /**
     * Create exception for quota exceeded
     */
    public static function quotaExceeded(): self
    {
        $exception = new static(
            'YouTube API quota exceeded. The quota will reset at midnight Pacific Time (PT). Please try again later.',
            403
        );
        $exception->quotaExceeded = true;
        return $exception;
    }

    /**
     * Create exception for rate limit
     */
    public static function rateLimitExceeded(int $retryAfter = 60): self
    {
        $exception = new static(
            "YouTube API rate limit exceeded. Please try again in {$retryAfter} seconds.",
            429
        );
        $exception->rateLimitExceeded = true;
        return $exception;
    }

    /**
     * Create exception for invalid API key
     */
    public static function invalidApiKey(): self
    {
        return new static(
            'Invalid YouTube API key. Please check your Metal-X settings.',
            401
        );
    }

    /**
     * Create exception for forbidden access
     */
    public static function forbidden(string $reason = ''): self
    {
        $message = 'Access forbidden to YouTube resource';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new static($message, 403);
    }

    /**
     * Create exception for video not found
     */
    public static function videoNotFound(string $videoId): self
    {
        return new static(
            "YouTube video '{$videoId}' not found or is private/deleted.",
            404,
            null,
            $videoId
        );
    }

    /**
     * Create exception for comment not found
     */
    public static function commentNotFound(string $commentId): self
    {
        return new static(
            "YouTube comment '{$commentId}' not found or has been deleted.",
            404,
            null,
            null,
            $commentId
        );
    }

    /**
     * Create exception for comments disabled
     */
    public static function commentsDisabled(string $videoId): self
    {
        return new static(
            "Comments are disabled for video '{$videoId}'.",
            403,
            null,
            $videoId
        );
    }

    /**
     * Create exception for insufficient permissions
     */
    public static function insufficientPermissions(string $action): self
    {
        return new static(
            "Insufficient permissions to {$action}. Please check YouTube channel authorization.",
            403
        );
    }

    /**
     * Create exception for invalid response
     */
    public static function invalidResponse(string $reason = ''): self
    {
        $message = 'Invalid response from YouTube API';
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new static($message, 502);
    }

    /**
     * Create exception for network error
     */
    public static function networkError(string $details = ''): self
    {
        $message = 'Failed to connect to YouTube API';
        if ($details) {
            $message .= ": {$details}";
        }
        return new static($message, 503);
    }

    /**
     * Create exception for timeout
     */
    public static function timeout(int $seconds): self
    {
        return new static(
            "YouTube API request timed out after {$seconds} seconds.",
            408
        );
    }

    /**
     * Create exception for duplicate action
     */
    public static function duplicate(string $action): self
    {
        return new static(
            "Cannot {$action}: Action has already been performed.",
            409
        );
    }

    /**
     * Get user-friendly error message (safe to display)
     */
    public function getUserMessage(): string
    {
        if ($this->quotaExceeded) {
            return 'YouTube API quota exceeded for today. Service will resume tomorrow.';
        }

        if ($this->rateLimitExceeded) {
            return 'YouTube API is temporarily busy. Please try again in a few moments.';
        }

        if ($this->code === 401) {
            return 'YouTube authentication failed. Please contact administrator.';
        }

        if ($this->code === 403) {
            return 'Access to YouTube resource is forbidden. Please check permissions.';
        }

        if ($this->code === 404) {
            return 'YouTube resource not found or has been deleted.';
        }

        if ($this->code === 408) {
            return 'YouTube API request timed out. Please try again.';
        }

        if ($this->code === 503) {
            return 'YouTube API is temporarily unavailable. Please try again later.';
        }

        return 'YouTube API error occurred. Please try again later.';
    }

    /**
     * Check if error is quota related
     */
    public function isQuotaError(): bool
    {
        return $this->quotaExceeded;
    }

    /**
     * Check if error is rate limit related
     */
    public function isRateLimitError(): bool
    {
        return $this->rateLimitExceeded;
    }

    /**
     * Check if error is retryable
     */
    public function isRetryable(): bool
    {
        // Retry on rate limits, timeouts, and network errors
        return in_array($this->code, [408, 429, 503]);
    }

    /**
     * Get video ID if available
     */
    public function getVideoId(): ?string
    {
        return $this->videoId;
    }

    /**
     * Get comment ID if available
     */
    public function getCommentId(): ?string
    {
        return $this->commentId;
    }
}
