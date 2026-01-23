<?php

namespace Tests\Unit\Services;

use App\Services\CircuitBreakerService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_starts_in_closed_state()
    {
        $breaker = new CircuitBreakerService('test-service');
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $breaker->getState());
        $this->assertTrue($breaker->isAvailable());
    }

    /** @test */
    public function it_executes_callable_successfully_when_closed()
    {
        $breaker = new CircuitBreakerService('test-service');

        $result = $breaker->execute(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    /** @test */
    public function it_records_failures()
    {
        $breaker = new CircuitBreakerService('test-service', 3, 60);

        // First failure
        try {
            $breaker->execute(function () {
                throw new Exception('Test failure');
            });
        } catch (Exception $e) {
            // Expected
        }

        $stats = $breaker->getStats();
        $this->assertEquals(1, $stats['recent_failures']);
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $stats['state']);
    }

    /** @test */
    public function it_opens_circuit_after_threshold_failures()
    {
        $breaker = new CircuitBreakerService('test-service', 3, 60, 10);

        // Trigger 3 failures (threshold = 3)
        for ($i = 0; $i < 3; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Test failure '.$i);
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        // Circuit should now be OPEN
        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $breaker->getState());
        $this->assertFalse($breaker->isAvailable());
    }

    /** @test */
    public function it_fails_fast_when_circuit_is_open()
    {
        $breaker = new CircuitBreakerService('test-service', 2, 60, 10);

        // Open the circuit by causing failures
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Opening circuit');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        // Circuit is now OPEN, next execution should fail fast
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('temporarily unavailable');

        $breaker->execute(function () {
            return 'should not execute';
        });
    }

    /** @test */
    public function it_uses_fallback_when_circuit_is_open()
    {
        $breaker = new CircuitBreakerService('test-service', 2, 60, 10);

        // Open the circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Opening circuit');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        // Use fallback
        $result = $breaker->execute(
            function () {
                return 'primary';
            },
            function () {
                return 'fallback';
            }
        );

        $this->assertEquals('fallback', $result);
    }

    /** @test */
    public function it_transitions_to_half_open_after_recovery_time()
    {
        $breaker = new CircuitBreakerService('test-service', 2, 60, 2); // 2 second recovery

        // Open the circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Opening circuit');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $breaker->getState());

        // Wait for half of recovery time
        sleep(1);

        // Should still be OPEN
        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $breaker->getState());

        // Wait for recovery time to pass
        sleep(2);

        // Should now be HALF_OPEN
        $this->assertEquals(CircuitBreakerService::STATE_HALF_OPEN, $breaker->getState());
    }

    /** @test */
    public function it_closes_circuit_on_successful_execution_in_half_open_state()
    {
        $breaker = new CircuitBreakerService('test-service', 2, 60, 1); // 1 second recovery

        // Open the circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Opening circuit');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        // Wait for half-open state
        sleep(2);
        $this->assertEquals(CircuitBreakerService::STATE_HALF_OPEN, $breaker->getState());

        // Successful execution should close circuit
        $result = $breaker->execute(function () {
            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $breaker->getState());
    }

    /** @test */
    public function it_reopens_circuit_on_failure_in_half_open_state()
    {
        $breaker = new CircuitBreakerService('test-service', 2, 60, 1);

        // Open the circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Opening circuit');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        // Wait for half-open state
        sleep(2);
        $this->assertEquals(CircuitBreakerService::STATE_HALF_OPEN, $breaker->getState());

        // Failed execution should reopen circuit
        try {
            $breaker->execute(function () {
                throw new Exception('Still failing');
            });
        } catch (Exception $e) {
            // Expected
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $breaker->getState());
    }

    /** @test */
    public function it_clears_old_failures_outside_time_window()
    {
        $breaker = new CircuitBreakerService('test-service', 3, 2, 10); // 2 second window

        // Cause 2 failures
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Failure '.$i);
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        $stats = $breaker->getStats();
        $this->assertEquals(2, $stats['recent_failures']);

        // Wait for failures to expire
        sleep(3);

        // These old failures shouldn't count anymore
        $stats = $breaker->getStats();
        $this->assertEquals(0, $stats['recent_failures']);
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $breaker->getState());
    }

    /** @test */
    public function it_resets_manually()
    {
        $breaker = new CircuitBreakerService('test-service', 2, 60, 100);

        // Open the circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Opening circuit');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $breaker->getState());

        // Manual reset
        $breaker->reset();

        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $breaker->getState());
        $this->assertTrue($breaker->isAvailable());
    }

    /** @test */
    public function it_provides_accurate_statistics()
    {
        $breaker = new CircuitBreakerService('test-service', 5, 60, 300);

        $stats = $breaker->getStats();

        $this->assertArrayHasKey('service', $stats);
        $this->assertArrayHasKey('state', $stats);
        $this->assertArrayHasKey('recent_failures', $stats);
        $this->assertArrayHasKey('failure_threshold', $stats);
        $this->assertArrayHasKey('failure_window', $stats);
        $this->assertArrayHasKey('recovery_time', $stats);
        $this->assertArrayHasKey('open_until', $stats);
        $this->assertArrayHasKey('seconds_until_retry', $stats);

        $this->assertEquals('test-service', $stats['service']);
        $this->assertEquals(5, $stats['failure_threshold']);
        $this->assertEquals(60, $stats['failure_window']);
        $this->assertEquals(300, $stats['recovery_time']);
    }

    /** @test */
    public function it_handles_multiple_services_independently()
    {
        $breaker1 = new CircuitBreakerService('service-1', 2, 60, 10);
        $breaker2 = new CircuitBreakerService('service-2', 2, 60, 10);

        // Open service-1 circuit
        for ($i = 0; $i < 2; $i++) {
            try {
                $breaker1->execute(function () {
                    throw new Exception('Service 1 failure');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        // Service 1 should be OPEN
        $this->assertEquals(CircuitBreakerService::STATE_OPEN, $breaker1->getState());

        // Service 2 should still be CLOSED
        $this->assertEquals(CircuitBreakerService::STATE_CLOSED, $breaker2->getState());

        // Service 2 should work normally
        $result = $breaker2->execute(function () {
            return 'service-2 works';
        });
        $this->assertEquals('service-2 works', $result);
    }

    /** @test */
    public function it_creates_breaker_with_config()
    {
        // Set config values
        config([
            'metalx.circuit_breaker.failure_threshold' => 10,
            'metalx.circuit_breaker.failure_window' => 120,
            'metalx.circuit_breaker.recovery_time' => 600,
        ]);

        $breaker = CircuitBreakerService::for('test-service');
        $stats = $breaker->getStats();

        $this->assertEquals(10, $stats['failure_threshold']);
        $this->assertEquals(120, $stats['failure_window']);
        $this->assertEquals(600, $stats['recovery_time']);
    }

    /** @test */
    public function it_uses_default_config_when_not_set()
    {
        $breaker = CircuitBreakerService::for('test-service');
        $stats = $breaker->getStats();

        // Default values from constructor
        $this->assertEquals(5, $stats['failure_threshold']);
        $this->assertEquals(60, $stats['failure_window']);
        $this->assertEquals(300, $stats['recovery_time']);
    }

    /** @test */
    public function it_counts_consecutive_failures_correctly()
    {
        $breaker = new CircuitBreakerService('test-service', 5, 60, 10);

        // 3 failures
        for ($i = 0; $i < 3; $i++) {
            try {
                $breaker->execute(function () {
                    throw new Exception('Failure');
                });
            } catch (Exception $e) {
                // Expected
            }
        }

        $stats = $breaker->getStats();
        $this->assertEquals(3, $stats['recent_failures']);

        // 1 success (clears failures)
        $breaker->execute(function () {
            return 'success';
        });

        $stats = $breaker->getStats();
        $this->assertEquals(0, $stats['recent_failures']);
    }

    /** @test */
    public function it_executes_fallback_on_primary_failure_when_closed()
    {
        $breaker = new CircuitBreakerService('test-service', 10, 60, 10);

        $result = $breaker->execute(
            function () {
                throw new Exception('Primary failed');
            },
            function () {
                return 'fallback executed';
            }
        );

        $this->assertEquals('fallback executed', $result);
    }

    /** @test */
    public function it_throws_exception_when_no_fallback_provided_and_circuit_open()
    {
        $breaker = new CircuitBreakerService('test-service', 1, 60, 10);

        // Open circuit
        try {
            $breaker->execute(function () {
                throw new Exception('Opening');
            });
        } catch (Exception $e) {
            // Expected
        }

        // Should throw when circuit is open and no fallback
        $this->expectException(Exception::class);
        $breaker->execute(function () {
            return 'should not execute';
        });
    }

    /** @test */
    public function it_tracks_seconds_until_retry_accurately()
    {
        $breaker = new CircuitBreakerService('test-service', 1, 60, 10); // 10 second recovery

        // Open circuit
        try {
            $breaker->execute(function () {
                throw new Exception('Opening');
            });
        } catch (Exception $e) {
            // Expected
        }

        $stats = $breaker->getStats();
        $this->assertGreaterThan(0, $stats['seconds_until_retry']);
        $this->assertLessThanOrEqual(10, $stats['seconds_until_retry']);
    }
}
