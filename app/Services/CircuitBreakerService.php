<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Circuit Breaker Service
 *
 * Implements the Circuit Breaker pattern to prevent cascading failures
 * from external APIs (YouTube, OpenAI, Claude, etc.)
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Too many failures, all requests fail fast
 * - HALF_OPEN: Testing if service recovered, limited requests allowed
 */
class CircuitBreakerService
{
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';

    protected $serviceName;
    protected $failureThreshold;
    protected $failureWindow;
    protected $recoveryTime;
    protected $halfOpenMaxAttempts;

    public function __construct(
        string $serviceName,
        int $failureThreshold = 5,
        int $failureWindow = 60,
        int $recoveryTime = 300,
        int $halfOpenMaxAttempts = 3
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->failureWindow = $failureWindow;
        $this->recoveryTime = $recoveryTime;
        $this->halfOpenMaxAttempts = $halfOpenMaxAttempts;
    }

    /**
     * Execute a callable with circuit breaker protection
     *
     * @param callable $callable The function to execute
     * @param callable|null $fallback Fallback function if circuit is open
     * @return mixed
     * @throws Exception
     */
    public function execute(callable $callable, ?callable $fallback = null)
    {
        $state = $this->getState();

        // If circuit is open, fail fast
        if ($state === self::STATE_OPEN) {
            Log::warning("Circuit breaker OPEN for {$this->serviceName}. Failing fast.");

            if ($fallback) {
                return $fallback();
            }

            throw new Exception("Service {$this->serviceName} is temporarily unavailable (circuit breaker OPEN).");
        }

        // Try to execute the callable
        try {
            $result = $callable();

            // Success! Record it
            $this->recordSuccess();

            return $result;
        } catch (Exception $e) {
            // Failure! Record it
            $this->recordFailure();

            // Check if we should open the circuit
            if ($this->shouldOpenCircuit()) {
                $this->openCircuit();
                Log::error("Circuit breaker opened for {$this->serviceName} due to repeated failures.");
            }

            // If we have a fallback, use it
            if ($fallback) {
                return $fallback();
            }

            // Otherwise, rethrow the exception
            throw $e;
        }
    }

    /**
     * Get current circuit state
     */
    public function getState(): string
    {
        $openUntil = Cache::get($this->getOpenKey());

        if ($openUntil && time() < $openUntil) {
            // Check if we should try half-open
            if (time() >= ($openUntil - $this->recoveryTime / 2)) {
                return self::STATE_HALF_OPEN;
            }
            return self::STATE_OPEN;
        }

        return self::STATE_CLOSED;
    }

    /**
     * Record a successful execution
     */
    protected function recordSuccess(): void
    {
        // If we were in half-open state, close the circuit
        if ($this->getState() === self::STATE_HALF_OPEN) {
            $this->closeCircuit();
            Log::info("Circuit breaker closed for {$this->serviceName} after successful recovery.");
        }

        // Clear failure count
        $this->clearFailures();
    }

    /**
     * Record a failed execution
     */
    protected function recordFailure(): void
    {
        $key = $this->getFailureKey();
        $failures = Cache::get($key, []);

        // Add current timestamp
        $failures[] = time();

        // Store with expiration
        Cache::put($key, $failures, $this->failureWindow);
    }

    /**
     * Check if circuit should be opened
     */
    protected function shouldOpenCircuit(): bool
    {
        $failures = Cache::get($this->getFailureKey(), []);

        // Filter failures within the time window
        $recentFailures = array_filter($failures, function ($timestamp) {
            return time() - $timestamp <= $this->failureWindow;
        });

        return count($recentFailures) >= $this->failureThreshold;
    }

    /**
     * Open the circuit
     */
    protected function openCircuit(): void
    {
        $openUntil = time() + $this->recoveryTime;
        Cache::put($this->getOpenKey(), $openUntil, $this->recoveryTime);

        Log::warning("Circuit breaker OPENED for {$this->serviceName}. Will retry after {$this->recoveryTime} seconds.");
    }

    /**
     * Close the circuit
     */
    protected function closeCircuit(): void
    {
        Cache::forget($this->getOpenKey());
        $this->clearFailures();
    }

    /**
     * Clear failure records
     */
    protected function clearFailures(): void
    {
        Cache::forget($this->getFailureKey());
    }

    /**
     * Get cache key for failure tracking
     */
    protected function getFailureKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:failures";
    }

    /**
     * Get cache key for open state
     */
    protected function getOpenKey(): string
    {
        return "circuit_breaker:{$this->serviceName}:open_until";
    }

    /**
     * Get circuit breaker statistics
     */
    public function getStats(): array
    {
        $state = $this->getState();
        $failures = Cache::get($this->getFailureKey(), []);
        $openUntil = Cache::get($this->getOpenKey());

        $recentFailures = array_filter($failures, function ($timestamp) {
            return time() - $timestamp <= $this->failureWindow;
        });

        return [
            'service' => $this->serviceName,
            'state' => $state,
            'recent_failures' => count($recentFailures),
            'failure_threshold' => $this->failureThreshold,
            'failure_window' => $this->failureWindow,
            'recovery_time' => $this->recoveryTime,
            'open_until' => $openUntil ? date('Y-m-d H:i:s', $openUntil) : null,
            'seconds_until_retry' => $openUntil ? max(0, $openUntil - time()) : 0,
        ];
    }

    /**
     * Manually reset the circuit breaker
     */
    public function reset(): void
    {
        $this->closeCircuit();
        Log::info("Circuit breaker manually reset for {$this->serviceName}.");
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return $this->getState() !== self::STATE_OPEN;
    }

    /**
     * Create a circuit breaker for a specific service
     */
    public static function for(string $serviceName): self
    {
        $config = config('metalx.circuit_breaker', []);

        return new self(
            $serviceName,
            $config['failure_threshold'] ?? 5,
            $config['failure_window'] ?? 60,
            $config['recovery_time'] ?? 300,
            3
        );
    }
}
