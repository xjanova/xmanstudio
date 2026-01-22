<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\YouTubeAPIException;
use Tests\TestCase;

class YouTubeAPIExceptionTest extends TestCase
{
    /** @test */
    public function it_creates_quota_exceeded_exception()
    {
        $exception = YouTubeAPIException::quotaExceeded();

        $this->assertEquals(429, $exception->getCode());
        $this->assertStringContainsString('quota', strtolower($exception->getMessage()));
        $this->assertStringContainsString('exceeded', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_quota_exceeded()
    {
        $exception = YouTubeAPIException::quotaExceeded();

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('daily limit', strtolower($userMessage));
        $this->assertStringContainsString('tomorrow', strtolower($userMessage));
        $this->assertStringNotContainsString('quota', strtolower($userMessage)); // Should use user-friendly language
    }

    /** @test */
    public function it_creates_rate_limit_exceeded_exception()
    {
        $exception = YouTubeAPIException::rateLimitExceeded(90);

        $this->assertEquals(429, $exception->getCode());
        $this->assertEquals(90, $exception->getRetryAfter());
        $this->assertStringContainsString('rate limit', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_rate_limit()
    {
        $exception = YouTubeAPIException::rateLimitExceeded(60);

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('too many requests', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_video_not_found_exception()
    {
        $exception = YouTubeAPIException::videoNotFound('dQw4w9WgXcQ');

        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('video', strtolower($exception->getMessage()));
        $this->assertStringContainsString('dQw4w9WgXcQ', $exception->getMessage());
    }

    /** @test */
    public function it_provides_user_safe_message_for_video_not_found()
    {
        $exception = YouTubeAPIException::videoNotFound('abc123');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('video', strtolower($userMessage));
        $this->assertStringContainsString('not found', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_comments_disabled_exception()
    {
        $exception = YouTubeAPIException::commentsDisabled('dQw4w9WgXcQ');

        $this->assertEquals(403, $exception->getCode());
        $this->assertStringContainsString('comments', strtolower($exception->getMessage()));
        $this->assertStringContainsString('disabled', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_comments_disabled()
    {
        $exception = YouTubeAPIException::commentsDisabled('abc123');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('comments', strtolower($userMessage));
        $this->assertStringContainsString('disabled', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_invalid_api_key_exception()
    {
        $exception = YouTubeAPIException::invalidApiKey();

        $this->assertEquals(401, $exception->getCode());
        $this->assertStringContainsString('api key', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_invalid_api_key()
    {
        $exception = YouTubeAPIException::invalidApiKey();

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('configuration', strtolower($userMessage));
        $this->assertStringNotContainsString('api key', strtolower($userMessage)); // Should not expose configuration details
    }

    /** @test */
    public function it_creates_unauthorized_exception()
    {
        $exception = YouTubeAPIException::unauthorized();

        $this->assertEquals(401, $exception->getCode());
        $this->assertStringContainsString('unauthorized', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_unauthorized()
    {
        $exception = YouTubeAPIException::unauthorized();

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('permission', strtolower($userMessage));
        $this->assertStringContainsString('authenticate', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_forbidden_exception()
    {
        $exception = YouTubeAPIException::forbidden('Cannot access private video');

        $this->assertEquals(403, $exception->getCode());
        $this->assertStringContainsString('forbidden', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_forbidden()
    {
        $exception = YouTubeAPIException::forbidden('Access denied');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('permission', strtolower($userMessage));
        $this->assertStringNotContainsString('access denied', strtolower($userMessage)); // Should use user-friendly language
    }

    /** @test */
    public function it_creates_service_unavailable_exception()
    {
        $exception = YouTubeAPIException::serviceUnavailable();

        $this->assertEquals(503, $exception->getCode());
        $this->assertStringContainsString('unavailable', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_service_unavailable()
    {
        $exception = YouTubeAPIException::serviceUnavailable();

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('unavailable', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_timeout_exception()
    {
        $exception = YouTubeAPIException::timeout(45);

        $this->assertEquals(504, $exception->getCode());
        $this->assertStringContainsString('timeout', strtolower($exception->getMessage()));
        $this->assertStringContainsString('45', $exception->getMessage());
    }

    /** @test */
    public function it_provides_user_safe_message_for_timeout()
    {
        $exception = YouTubeAPIException::timeout(30);

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('taking too long', strtolower($userMessage));
        $this->assertStringNotContainsString('timeout', strtolower($userMessage)); // Should use user-friendly language
    }

    /** @test */
    public function it_creates_invalid_request_exception()
    {
        $exception = YouTubeAPIException::invalidRequest('Missing required parameter: part');

        $this->assertEquals(400, $exception->getCode());
        $this->assertStringContainsString('invalid request', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_invalid_request()
    {
        $exception = YouTubeAPIException::invalidRequest('Bad parameter');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('invalid', strtolower($userMessage));
        $this->assertStringNotContainsString('parameter', strtolower($userMessage)); // Should not expose technical details
    }

    /** @test */
    public function it_creates_network_error_exception()
    {
        $exception = YouTubeAPIException::networkError('Connection timeout');

        $this->assertEquals(0, $exception->getCode()); // Network errors typically have no HTTP code
        $this->assertStringContainsString('network', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_network_error()
    {
        $exception = YouTubeAPIException::networkError('DNS failed');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('connection', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_channel_not_found_exception()
    {
        $exception = YouTubeAPIException::channelNotFound('UC_x5XG1OV2P6uZZ5FSM9Ttw');

        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('channel', strtolower($exception->getMessage()));
        $this->assertStringContainsString('UC_x5XG1OV2P6uZZ5FSM9Ttw', $exception->getMessage());
    }

    /** @test */
    public function it_provides_user_safe_message_for_channel_not_found()
    {
        $exception = YouTubeAPIException::channelNotFound('UCabc123');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('channel', strtolower($userMessage));
        $this->assertStringContainsString('not found', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_playlist_not_found_exception()
    {
        $exception = YouTubeAPIException::playlistNotFound('PLrAXtmErZgOeiKm4sgNOknGvNjby9efdf');

        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('playlist', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_playlist_not_found()
    {
        $exception = YouTubeAPIException::playlistNotFound('PLabc123');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('playlist', strtolower($userMessage));
        $this->assertStringContainsString('not found', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_comment_not_found_exception()
    {
        $exception = YouTubeAPIException::commentNotFound('UgxKREWxIgDkGvCoufZ4AaABAg');

        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('comment', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_comment_not_found()
    {
        $exception = YouTubeAPIException::commentNotFound('Ugxabc123');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('comment', strtolower($userMessage));
        $this->assertStringContainsString('not found', strtolower($userMessage));
    }

    /** @test */
    public function it_stores_retry_after_for_rate_limit()
    {
        $exception = YouTubeAPIException::rateLimitExceeded(120);

        $this->assertEquals(120, $exception->getRetryAfter());
    }

    /** @test */
    public function it_returns_null_retry_after_for_non_rate_limit_exceptions()
    {
        $exception = YouTubeAPIException::videoNotFound('abc123');

        $this->assertNull($exception->getRetryAfter());
    }

    /** @test */
    public function it_includes_context_in_exception()
    {
        $exception = YouTubeAPIException::videoNotFound('dQw4w9WgXcQ');

        $context = $exception->getContext();

        $this->assertIsArray($context);
        $this->assertArrayHasKey('video_id', $context);
        $this->assertEquals('dQw4w9WgXcQ', $context['video_id']);
    }

    /** @test */
    public function it_creates_generic_youtube_api_exception()
    {
        $exception = new YouTubeAPIException('Custom error message', 500);

        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('Custom error message', $exception->getMessage());
    }

    /** @test */
    public function it_provides_default_user_message_for_generic_exception()
    {
        $exception = new YouTubeAPIException('Internal error', 500);

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('error', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function user_messages_do_not_expose_sensitive_information()
    {
        $exceptions = [
            YouTubeAPIException::invalidApiKey(),
            YouTubeAPIException::videoNotFound('sensitive-video-id-123'),
            YouTubeAPIException::forbidden('Private channel access denied'),
            YouTubeAPIException::networkError('Connection to 10.0.0.1:443 failed'),
        ];

        foreach ($exceptions as $exception) {
            $userMessage = $exception->getUserMessage();

            // Should not contain technical details
            $this->assertStringNotContainsString('api key', strtolower($userMessage));
            $this->assertStringNotContainsString('10.0.0.1', $userMessage);
            $this->assertStringNotContainsString('access denied', strtolower($userMessage));
            $this->assertStringNotContainsString('connection', strtolower($exception->getMessage())); // But OK in user message

            // Should be user-friendly
            $this->assertNotEmpty($userMessage);
            $this->assertIsString($userMessage);
        }
    }

    /** @test */
    public function all_factory_methods_return_youtube_api_exception_instance()
    {
        $exceptions = [
            YouTubeAPIException::quotaExceeded(),
            YouTubeAPIException::rateLimitExceeded(60),
            YouTubeAPIException::videoNotFound('abc'),
            YouTubeAPIException::commentsDisabled('abc'),
            YouTubeAPIException::invalidApiKey(),
            YouTubeAPIException::unauthorized(),
            YouTubeAPIException::forbidden('error'),
            YouTubeAPIException::serviceUnavailable(),
            YouTubeAPIException::timeout(30),
            YouTubeAPIException::invalidRequest('error'),
            YouTubeAPIException::networkError('error'),
            YouTubeAPIException::channelNotFound('abc'),
            YouTubeAPIException::playlistNotFound('abc'),
            YouTubeAPIException::commentNotFound('abc'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(YouTubeAPIException::class, $exception);
        }
    }

    /** @test */
    public function it_has_appropriate_http_status_codes()
    {
        $this->assertEquals(429, YouTubeAPIException::quotaExceeded()->getCode());
        $this->assertEquals(429, YouTubeAPIException::rateLimitExceeded(60)->getCode());
        $this->assertEquals(404, YouTubeAPIException::videoNotFound('abc')->getCode());
        $this->assertEquals(403, YouTubeAPIException::commentsDisabled('abc')->getCode());
        $this->assertEquals(401, YouTubeAPIException::invalidApiKey()->getCode());
        $this->assertEquals(401, YouTubeAPIException::unauthorized()->getCode());
        $this->assertEquals(403, YouTubeAPIException::forbidden('error')->getCode());
        $this->assertEquals(503, YouTubeAPIException::serviceUnavailable()->getCode());
        $this->assertEquals(504, YouTubeAPIException::timeout(30)->getCode());
        $this->assertEquals(400, YouTubeAPIException::invalidRequest('error')->getCode());
        $this->assertEquals(0, YouTubeAPIException::networkError('error')->getCode());
        $this->assertEquals(404, YouTubeAPIException::channelNotFound('abc')->getCode());
        $this->assertEquals(404, YouTubeAPIException::playlistNotFound('abc')->getCode());
        $this->assertEquals(404, YouTubeAPIException::commentNotFound('abc')->getCode());
    }

    /** @test */
    public function it_stores_resource_ids_in_context()
    {
        $videoException = YouTubeAPIException::videoNotFound('video123');
        $this->assertEquals('video123', $videoException->getContext()['video_id']);

        $channelException = YouTubeAPIException::channelNotFound('channel456');
        $this->assertEquals('channel456', $channelException->getContext()['channel_id']);

        $playlistException = YouTubeAPIException::playlistNotFound('playlist789');
        $this->assertEquals('playlist789', $playlistException->getContext()['playlist_id']);

        $commentException = YouTubeAPIException::commentNotFound('comment101');
        $this->assertEquals('comment101', $commentException->getContext()['comment_id']);
    }
}
