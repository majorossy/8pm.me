<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Resilience;

use ArchiveDotOrg\Core\Model\Config;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Circuit Breaker Implementation
 *
 * Prevents hammering a failing API by tracking failures and temporarily
 * rejecting requests when the failure threshold is exceeded.
 *
 * States:
 * - CLOSED: Normal operation, requests pass through
 * - OPEN: Circuit is tripped, requests fail fast without calling the API
 * - HALF_OPEN: Testing if the API has recovered
 */
class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    private const CACHE_KEY_STATE = 'archivedotorg_circuit_state';
    private const CACHE_KEY_FAILURES = 'archivedotorg_circuit_failures';
    private const CACHE_KEY_LAST_FAILURE = 'archivedotorg_circuit_last_failure';

    private CacheInterface $cache;
    private Config $config;

    /**
     * @param CacheInterface $cache
     * @param Config $config
     */
    public function __construct(
        CacheInterface $cache,
        Config $config
    ) {
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Execute an operation with circuit breaker protection
     *
     * @param callable $operation The operation to execute
     * @return mixed The result of the operation
     * @throws CircuitOpenException If the circuit is open
     * @throws \Exception If the operation fails
     */
    public function call(callable $operation)
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            // Check if reset timeout has elapsed
            $lastFailure = $this->getLastFailureTime();
            $resetTimeout = $this->config->getCircuitResetSeconds();

            if (time() - $lastFailure >= $resetTimeout) {
                $this->setState(self::STATE_HALF_OPEN);
            } else {
                throw CircuitOpenException::fromString(
                    'Circuit breaker is open. Archive.org API appears to be unavailable. ' .
                    'Will retry in ' . ($resetTimeout - (time() - $lastFailure)) . ' seconds.'
                );
            }
        }

        try {
            $result = $operation();
            $this->onSuccess();
            return $result;
        } catch (\Exception $e) {
            $this->onFailure();
            throw $e;
        }
    }

    /**
     * Record a successful operation
     *
     * @return void
     */
    public function onSuccess(): void
    {
        $this->setFailureCount(0);
        $this->setState(self::STATE_CLOSED);
    }

    /**
     * Record a failed operation
     *
     * @return void
     */
    public function onFailure(): void
    {
        $failures = $this->getFailureCount() + 1;
        $this->setFailureCount($failures);
        $this->setLastFailureTime(time());

        $threshold = $this->config->getCircuitThreshold();

        if ($failures >= $threshold) {
            $this->setState(self::STATE_OPEN);
        }
    }

    /**
     * Get current circuit state
     *
     * @return string
     */
    public function getState(): string
    {
        $state = $this->cache->load(self::CACHE_KEY_STATE);
        return $state ?: self::STATE_CLOSED;
    }

    /**
     * Set circuit state
     *
     * @param string $state
     * @return void
     */
    private function setState(string $state): void
    {
        $this->cache->save($state, self::CACHE_KEY_STATE, [], 3600);
    }

    /**
     * Get failure count
     *
     * @return int
     */
    public function getFailureCount(): int
    {
        $count = $this->cache->load(self::CACHE_KEY_FAILURES);
        return $count !== false ? (int) $count : 0;
    }

    /**
     * Set failure count
     *
     * @param int $count
     * @return void
     */
    private function setFailureCount(int $count): void
    {
        $this->cache->save((string) $count, self::CACHE_KEY_FAILURES, [], 3600);
    }

    /**
     * Get last failure timestamp
     *
     * @return int
     */
    public function getLastFailureTime(): int
    {
        $time = $this->cache->load(self::CACHE_KEY_LAST_FAILURE);
        return $time !== false ? (int) $time : 0;
    }

    /**
     * Set last failure timestamp
     *
     * @param int $time
     * @return void
     */
    private function setLastFailureTime(int $time): void
    {
        $this->cache->save((string) $time, self::CACHE_KEY_LAST_FAILURE, [], 3600);
    }

    /**
     * Reset the circuit breaker to closed state
     *
     * Useful for manual reset from admin or CLI
     *
     * @return void
     */
    public function reset(): void
    {
        $this->setFailureCount(0);
        $this->setState(self::STATE_CLOSED);
    }

    /**
     * Check if the circuit is open
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->getState() === self::STATE_OPEN;
    }

    /**
     * Check if the circuit is closed (normal operation)
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->getState() === self::STATE_CLOSED;
    }

    /**
     * Get circuit status for monitoring
     *
     * @return array{state: string, failures: int, last_failure: int, threshold: int, reset_seconds: int}
     */
    public function getStatus(): array
    {
        return [
            'state' => $this->getState(),
            'failures' => $this->getFailureCount(),
            'last_failure' => $this->getLastFailureTime(),
            'threshold' => $this->config->getCircuitThreshold(),
            'reset_seconds' => $this->config->getCircuitResetSeconds(),
        ];
    }
}
