<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model\Resilience;

use ArchiveDotOrg\Core\Model\Config;
use ArchiveDotOrg\Core\Model\Resilience\CircuitBreaker;
use ArchiveDotOrg\Core\Model\Resilience\CircuitOpenException;
use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CircuitBreaker
 *
 * @covers \ArchiveDotOrg\Core\Model\Resilience\CircuitBreaker
 */
class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;
    private CacheInterface|MockObject $cacheMock;
    private Config|MockObject $configMock;

    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->configMock = $this->createMock(Config::class);

        $this->configMock->method('getCircuitThreshold')->willReturn(3);
        $this->configMock->method('getCircuitResetSeconds')->willReturn(30);

        $this->circuitBreaker = new CircuitBreaker(
            $this->cacheMock,
            $this->configMock
        );
    }

    /**
     * @test
     */
    public function callExecutesOperationWhenClosed(): void
    {
        $this->cacheMock->method('load')->willReturn(false);

        $result = $this->circuitBreaker->call(fn() => 'success');

        $this->assertEquals('success', $result);
    }

    /**
     * @test
     */
    public function callThrowsWhenCircuitIsOpen(): void
    {
        // Simulate open circuit with recent failure
        $this->cacheMock->method('load')
            ->willReturnMap([
                ['archivedotorg_circuit_state', CircuitBreaker::STATE_OPEN],
                ['archivedotorg_circuit_last_failure', (string)(time() - 10)], // 10 seconds ago
            ]);

        $this->expectException(CircuitOpenException::class);
        $this->expectExceptionMessage('Circuit breaker is open');

        $this->circuitBreaker->call(fn() => 'should not execute');
    }

    /**
     * @test
     */
    public function callTransitionsToHalfOpenAfterResetTimeout(): void
    {
        // Simulate open circuit with old failure (past reset timeout)
        $this->cacheMock->method('load')
            ->willReturnCallback(function ($key) {
                if ($key === 'archivedotorg_circuit_state') {
                    return CircuitBreaker::STATE_OPEN;
                }
                if ($key === 'archivedotorg_circuit_last_failure') {
                    return (string)(time() - 60); // 60 seconds ago (past 30s reset)
                }
                return false;
            });

        // Expect saves: half-open state, failure count reset, closed state
        $this->cacheMock->expects($this->exactly(3))
            ->method('save');

        $result = $this->circuitBreaker->call(fn() => 'success after reset');

        $this->assertEquals('success after reset', $result);
    }

    /**
     * @test
     */
    public function onSuccessResetsBreakerToClosed(): void
    {
        $this->cacheMock->method('load')->willReturn(false);

        // Expect failure count reset to 0 and state set to closed
        $this->cacheMock->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($value, $key) {
                if ($key === 'archivedotorg_circuit_failures') {
                    $this->assertEquals('0', $value);
                }
                if ($key === 'archivedotorg_circuit_state') {
                    $this->assertEquals(CircuitBreaker::STATE_CLOSED, $value);
                }
            });

        $this->circuitBreaker->call(fn() => 'success');
    }

    /**
     * @test
     */
    public function onFailureIncrementsFailureCount(): void
    {
        $this->cacheMock->method('load')
            ->willReturnCallback(function ($key) {
                if ($key === 'archivedotorg_circuit_state') {
                    return CircuitBreaker::STATE_CLOSED;
                }
                if ($key === 'archivedotorg_circuit_failures') {
                    return '0';
                }
                return false;
            });

        $this->cacheMock->expects($this->atLeast(1))
            ->method('save')
            ->willReturnCallback(function ($value, $key) {
                if ($key === 'archivedotorg_circuit_failures') {
                    $this->assertEquals('1', $value);
                }
            });

        $this->expectException(\RuntimeException::class);

        $this->circuitBreaker->call(fn() => throw new \RuntimeException('API error'));
    }

    /**
     * @test
     */
    public function circuitOpensAfterThresholdFailures(): void
    {
        $failureCount = 2; // Already at 2 failures, next will hit threshold of 3

        $this->cacheMock->method('load')
            ->willReturnCallback(function ($key) use ($failureCount) {
                if ($key === 'archivedotorg_circuit_state') {
                    return CircuitBreaker::STATE_CLOSED;
                }
                if ($key === 'archivedotorg_circuit_failures') {
                    return (string)$failureCount;
                }
                return false;
            });

        $openCalled = false;
        $this->cacheMock->method('save')
            ->willReturnCallback(function ($value, $key) use (&$openCalled) {
                if ($key === 'archivedotorg_circuit_state' && $value === CircuitBreaker::STATE_OPEN) {
                    $openCalled = true;
                }
            });

        try {
            $this->circuitBreaker->call(fn() => throw new \RuntimeException('API error'));
        } catch (\RuntimeException $e) {
            // Expected
        }

        $this->assertTrue($openCalled, 'Circuit should be opened after reaching threshold');
    }

    /**
     * @test
     */
    public function resetSetsCircuitToClosed(): void
    {
        $this->cacheMock->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($value, $key) {
                if ($key === 'archivedotorg_circuit_failures') {
                    $this->assertEquals('0', $value);
                }
                if ($key === 'archivedotorg_circuit_state') {
                    $this->assertEquals(CircuitBreaker::STATE_CLOSED, $value);
                }
            });

        $this->circuitBreaker->reset();
    }

    /**
     * @test
     */
    public function getStatusReturnsCorrectData(): void
    {
        $this->cacheMock->method('load')
            ->willReturnCallback(function ($key) {
                if ($key === 'archivedotorg_circuit_state') {
                    return CircuitBreaker::STATE_OPEN;
                }
                if ($key === 'archivedotorg_circuit_failures') {
                    return '3';
                }
                if ($key === 'archivedotorg_circuit_last_failure') {
                    return '1700000000';
                }
                return false;
            });

        $status = $this->circuitBreaker->getStatus();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $status['state']);
        $this->assertEquals(3, $status['failures']);
        $this->assertEquals(1700000000, $status['last_failure']);
        $this->assertEquals(3, $status['threshold']);
        $this->assertEquals(30, $status['reset_seconds']);
    }

    /**
     * @test
     */
    public function isOpenReturnsTrueWhenOpen(): void
    {
        $this->cacheMock->method('load')
            ->with('archivedotorg_circuit_state')
            ->willReturn(CircuitBreaker::STATE_OPEN);

        $this->assertTrue($this->circuitBreaker->isOpen());
    }

    /**
     * @test
     */
    public function isClosedReturnsTrueWhenClosed(): void
    {
        $this->cacheMock->method('load')
            ->with('archivedotorg_circuit_state')
            ->willReturn(CircuitBreaker::STATE_CLOSED);

        $this->assertTrue($this->circuitBreaker->isClosed());
    }
}
