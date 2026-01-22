<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\AIServiceException;
use Tests\TestCase;

class AIServiceExceptionTest extends TestCase
{
    /** @test */
    public function it_creates_rate_limit_exceeded_exception()
    {
        $exception = AIServiceException::rateLimitExceeded('openai', 120);

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(429, $exception->getCode());
        $this->assertEquals(120, $exception->getRetryAfter());
        $this->assertStringContainsString('rate limit', strtolower($exception->getMessage()));
        $this->assertStringContainsString('openai', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_rate_limit()
    {
        $exception = AIServiceException::rateLimitExceeded('openai', 60);

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('too many requests', strtolower($userMessage));
        $this->assertStringNotContainsString('api', strtolower($userMessage)); // Should not expose technical details
    }

    /** @test */
    public function it_creates_quota_exceeded_exception()
    {
        $exception = AIServiceException::quotaExceeded('claude');

        $this->assertEquals('claude', $exception->getProvider());
        $this->assertEquals(429, $exception->getCode());
        $this->assertStringContainsString('quota', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_quota_exceeded()
    {
        $exception = AIServiceException::quotaExceeded('openai');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('limit', strtolower($userMessage));
        $this->assertIsString($userMessage);
        $this->assertNotEmpty($userMessage);
    }

    /** @test */
    public function it_creates_invalid_api_key_exception()
    {
        $exception = AIServiceException::invalidApiKey('openai');

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(401, $exception->getCode());
        $this->assertStringContainsString('api key', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_invalid_api_key()
    {
        $exception = AIServiceException::invalidApiKey('claude');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('configuration', strtolower($userMessage));
        $this->assertStringNotContainsString('api key', strtolower($userMessage)); // Should not expose configuration details
    }

    /** @test */
    public function it_creates_service_unavailable_exception()
    {
        $exception = AIServiceException::serviceUnavailable('openai');

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(503, $exception->getCode());
        $this->assertStringContainsString('unavailable', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_service_unavailable()
    {
        $exception = AIServiceException::serviceUnavailable('ollama');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('unavailable', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_timeout_exception()
    {
        $exception = AIServiceException::timeout('openai', 30);

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(504, $exception->getCode());
        $this->assertStringContainsString('timeout', strtolower($exception->getMessage()));
        $this->assertStringContainsString('30', $exception->getMessage());
    }

    /** @test */
    public function it_provides_user_safe_message_for_timeout()
    {
        $exception = AIServiceException::timeout('claude', 60);

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('taking too long', strtolower($userMessage));
        $this->assertStringNotContainsString('timeout', strtolower($userMessage)); // Should use user-friendly language
    }

    /** @test */
    public function it_creates_invalid_response_exception()
    {
        $exception = AIServiceException::invalidResponse('openai', 'Expected JSON, got HTML');

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(500, $exception->getCode());
        $this->assertStringContainsString('invalid response', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_invalid_response()
    {
        $exception = AIServiceException::invalidResponse('claude', 'Parse error');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('unexpected response', strtolower($userMessage));
        $this->assertStringNotContainsString('parse', strtolower($userMessage)); // Should not expose technical details
    }

    /** @test */
    public function it_creates_content_policy_violation_exception()
    {
        $exception = AIServiceException::contentPolicyViolation('openai', 'Inappropriate content detected');

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(400, $exception->getCode());
        $this->assertStringContainsString('content policy', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_content_policy_violation()
    {
        $exception = AIServiceException::contentPolicyViolation('claude', 'Violence detected');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('content', strtolower($userMessage));
        $this->assertStringContainsString('policy', strtolower($userMessage));
    }

    /** @test */
    public function it_creates_model_not_found_exception()
    {
        $exception = AIServiceException::modelNotFound('openai', 'gpt-5');

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(404, $exception->getCode());
        $this->assertStringContainsString('model', strtolower($exception->getMessage()));
        $this->assertStringContainsString('gpt-5', $exception->getMessage());
    }

    /** @test */
    public function it_provides_user_safe_message_for_model_not_found()
    {
        $exception = AIServiceException::modelNotFound('ollama', 'llama3:latest');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('configuration', strtolower($userMessage));
        $this->assertStringNotContainsString('model', strtolower($userMessage)); // Should not expose technical details
    }

    /** @test */
    public function it_creates_network_error_exception()
    {
        $exception = AIServiceException::networkError('openai', 'Connection refused');

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(0, $exception->getCode()); // Network errors typically have no HTTP code
        $this->assertStringContainsString('network', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_provides_user_safe_message_for_network_error()
    {
        $exception = AIServiceException::networkError('claude', 'DNS resolution failed');

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('connection', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function it_stores_provider_name()
    {
        $exception = AIServiceException::rateLimitExceeded('test-provider');

        $this->assertEquals('test-provider', $exception->getProvider());
    }

    /** @test */
    public function it_stores_retry_after_for_rate_limit()
    {
        $exception = AIServiceException::rateLimitExceeded('openai', 180);

        $this->assertEquals(180, $exception->getRetryAfter());
    }

    /** @test */
    public function it_returns_null_retry_after_for_non_rate_limit_exceptions()
    {
        $exception = AIServiceException::invalidApiKey('openai');

        $this->assertNull($exception->getRetryAfter());
    }

    /** @test */
    public function it_includes_context_in_exception()
    {
        $exception = AIServiceException::timeout('openai', 30);

        $context = $exception->getContext();

        $this->assertIsArray($context);
        $this->assertArrayHasKey('provider', $context);
        $this->assertEquals('openai', $context['provider']);
    }

    /** @test */
    public function it_creates_generic_ai_service_exception()
    {
        $exception = new AIServiceException('openai', 'Custom error message', 500);

        $this->assertEquals('openai', $exception->getProvider());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('Custom error message', $exception->getMessage());
    }

    /** @test */
    public function it_provides_default_user_message_for_generic_exception()
    {
        $exception = new AIServiceException('openai', 'Internal server error', 500);

        $userMessage = $exception->getUserMessage();

        $this->assertStringContainsString('error', strtolower($userMessage));
        $this->assertStringContainsString('try again', strtolower($userMessage));
    }

    /** @test */
    public function user_messages_do_not_expose_sensitive_information()
    {
        $exceptions = [
            AIServiceException::invalidApiKey('openai'),
            AIServiceException::timeout('claude', 60),
            AIServiceException::invalidResponse('ollama', 'JSON parse error at line 42'),
            AIServiceException::networkError('openai', '127.0.0.1:8080 connection refused'),
        ];

        foreach ($exceptions as $exception) {
            $userMessage = $exception->getUserMessage();

            // Should not contain technical details
            $this->assertStringNotContainsString('api key', strtolower($userMessage));
            $this->assertStringNotContainsString('127.0.0.1', $userMessage);
            $this->assertStringNotContainsString('parse error', strtolower($userMessage));
            $this->assertStringNotContainsString('connection refused', strtolower($userMessage));

            // Should be user-friendly
            $this->assertNotEmpty($userMessage);
            $this->assertIsString($userMessage);
        }
    }

    /** @test */
    public function all_factory_methods_return_ai_service_exception_instance()
    {
        $exceptions = [
            AIServiceException::rateLimitExceeded('openai'),
            AIServiceException::quotaExceeded('openai'),
            AIServiceException::invalidApiKey('openai'),
            AIServiceException::serviceUnavailable('openai'),
            AIServiceException::timeout('openai', 30),
            AIServiceException::invalidResponse('openai', 'error'),
            AIServiceException::contentPolicyViolation('openai', 'violation'),
            AIServiceException::modelNotFound('openai', 'model'),
            AIServiceException::networkError('openai', 'error'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(AIServiceException::class, $exception);
        }
    }

    /** @test */
    public function it_has_appropriate_http_status_codes()
    {
        $this->assertEquals(429, AIServiceException::rateLimitExceeded('openai')->getCode());
        $this->assertEquals(429, AIServiceException::quotaExceeded('openai')->getCode());
        $this->assertEquals(401, AIServiceException::invalidApiKey('openai')->getCode());
        $this->assertEquals(503, AIServiceException::serviceUnavailable('openai')->getCode());
        $this->assertEquals(504, AIServiceException::timeout('openai', 30)->getCode());
        $this->assertEquals(500, AIServiceException::invalidResponse('openai', 'error')->getCode());
        $this->assertEquals(400, AIServiceException::contentPolicyViolation('openai', 'violation')->getCode());
        $this->assertEquals(404, AIServiceException::modelNotFound('openai', 'model')->getCode());
        $this->assertEquals(0, AIServiceException::networkError('openai', 'error')->getCode());
    }
}
