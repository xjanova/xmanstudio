<?php

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception for YouTube API errors with user-friendly messages
 */
class YouTubeAPIException extends Exception
{
    protected $retryAfter;

    protected $context = [];

    protected $quotaExceeded = false;

    protected $rateLimitExceeded = false;

    protected $videoId;

    protected $commentId;

    protected $channelId;

    protected $playlistId;

    protected $isInvalidApiKey = false;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for quota exceeded
     */
    public static function quotaExceeded(): self
    {
        $exception = new static(
            'YouTube API quota exceeded. The quota will reset at midnight Pacific Time (PT). Please try again later.',
            429
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
        $exception->retryAfter = $retryAfter;
        $exception->context['retry_after'] = $retryAfter;

        return $exception;
    }

    /**
     * Create exception for invalid API key
     */
    public static function invalidApiKey(): self
    {
        $exception = new static(
            'Invalid YouTube API key. Please check your Metal-X settings.',
            401
        );
        $exception->isInvalidApiKey = true;

        return $exception;
    }

    /**
     * Create exception for unauthorized access
     */
    public static function unauthorized(string $reason = ''): self
    {
        $message = 'Unauthorized access to YouTube API';
        if ($reason) {
            $message .= ": {$reason}";
        }

        $exception = new static($message, 401);
        if ($reason) {
            $exception->context['reason'] = $reason;
        }

        return $exception;
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

        $exception = new static($message, 403);
        if ($reason) {
            $exception->context['reason'] = $reason;
        }

        return $exception;
    }

    /**
     * Create exception for service unavailable
     */
    public static function serviceUnavailable(): self
    {
        return new static(
            'YouTube API is currently unavailable. Please try again later.',
            503
        );
    }

    /**
     * Create exception for timeout
     */
    public static function timeout(int $seconds): self
    {
        $exception = new static(
            "YouTube API request timeout after {$seconds} seconds.",
            504
        );
        $exception->context['timeout'] = $seconds;

        return $exception;
    }

    /**
     * Create exception for invalid request
     */
    public static function invalidRequest(string $reason = ''): self
    {
        $message = 'Invalid request to YouTube API';
        if ($reason) {
            $message .= ": {$reason}";
        }

        $exception = new static($message, 400);
        if ($reason) {
            $exception->context['reason'] = $reason;
        }

        return $exception;
    }

    /**
     * Create exception for network error
     */
    public static function networkError(string $details = ''): self
    {
        $message = 'Network error when accessing YouTube API';

        $exception = new static($message, 0);
        if ($details) {
            $exception->context['details'] = $details;
        }

        return $exception;
    }

    /**
     * Create exception for video not found
     */
    public static function videoNotFound(string $videoId): self
    {
        $exception = new static(
            "YouTube video '{$videoId}' not found or is private/deleted.",
            404
        );
        $exception->videoId = $videoId;
        $exception->context['video_id'] = $videoId;

        return $exception;
    }

    /**
     * Create exception for channel not found
     */
    public static function channelNotFound(string $channelId): self
    {
        $exception = new static(
            "YouTube channel '{$channelId}' not found or is unavailable.",
            404
        );
        $exception->channelId = $channelId;
        $exception->context['channel_id'] = $channelId;

        return $exception;
    }

    /**
     * Create exception for playlist not found
     */
    public static function playlistNotFound(string $playlistId): self
    {
        $exception = new static(
            "YouTube playlist '{$playlistId}' not found or is private/deleted.",
            404
        );
        $exception->playlistId = $playlistId;
        $exception->context['playlist_id'] = $playlistId;

        return $exception;
    }

    /**
     * Create exception for comment not found
     */
    public static function commentNotFound(string $commentId): self
    {
        $exception = new static(
            "YouTube comment '{$commentId}' not found or has been deleted.",
            404
        );
        $exception->commentId = $commentId;
        $exception->context['comment_id'] = $commentId;

        return $exception;
    }

    /**
     * Create exception for comments disabled
     */
    public static function commentsDisabled(string $videoId): self
    {
        $exception = new static(
            "Comments are disabled for video '{$videoId}'.",
            403
        );
        $exception->videoId = $videoId;
        $exception->context['video_id'] = $videoId;

        return $exception;
    }

    /**
     * Create exception for insufficient permissions
     */
    public static function insufficientPermissions(string $action): self
    {
        $exception = new static(
            "Insufficient permissions to {$action}. Please check YouTube channel authorization.",
            403
        );
        $exception->context['action'] = $action;

        return $exception;
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

        $exception = new static($message, 500);
        if ($reason) {
            $exception->context['reason'] = $reason;
        }

        return $exception;
    }

    /**
     * Create exception for duplicate action
     */
    public static function duplicate(string $action): self
    {
        $exception = new static(
            "Cannot {$action}: Action has already been performed.",
            409
        );
        $exception->context['action'] = $action;

        return $exception;
    }

    /**
     * Get user-friendly error message (safe to display)
     */
    public function getUserMessage(): string
    {
        if ($this->quotaExceeded) {
            return 'YouTube API daily limit has been reached. Please try again tomorrow.';
        }

        if ($this->rateLimitExceeded) {
            return 'Too many requests to YouTube API. Please try again in a few moments.';
        }

        if ($this->code === 401) {
            if ($this->isInvalidApiKey) {
                return 'YouTube API configuration error detected. Please contact the administrator.';
            }

            return 'Permission denied. Please re-authenticate your YouTube account.';
        }

        if ($this->code === 403) {
            if (str_contains($this->message, 'Comments are disabled')) {
                return 'Comments are disabled on this video.';
            }

            return 'Access to YouTube resource is forbidden. Please check permissions.';
        }

        if ($this->code === 404) {
            if ($this->videoId) {
                return 'The video was not found or is no longer available.';
            }
            if ($this->channelId) {
                return 'The channel was not found or is unavailable.';
            }
            if ($this->playlistId) {
                return 'The playlist was not found or is no longer available.';
            }
            if ($this->commentId) {
                return 'The comment was not found or has been deleted.';
            }

            return 'YouTube resource not found or has been deleted.';
        }

        if ($this->code === 400) {
            return 'Invalid request to YouTube API. Please try again.';
        }

        if ($this->code === 503) {
            return 'YouTube API is currently unavailable. Please try again later.';
        }

        if ($this->code === 504) {
            return 'The request is taking too long. Please try again.';
        }

        if ($this->code === 500) {
            return 'An unexpected error occurred. Please try again.';
        }

        if ($this->code === 0) {
            return 'Unable to connect to YouTube API. Please check your connection and try again.';
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
        return in_array($this->code, [429, 503, 504, 0]);
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

    /**
     * Get channel ID if available
     */
    public function getChannelId(): ?string
    {
        return $this->channelId;
    }

    /**
     * Get playlist ID if available
     */
    public function getPlaylistId(): ?string
    {
        return $this->playlistId;
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
