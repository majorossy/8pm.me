<?php
/**
 * Copyright © Archive.org Import. All rights reserved.
 */
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Lock service interface for preventing concurrent operations
 *
 * @api
 */
interface LockServiceInterface
{
    /**
     * Acquire exclusive lock for artist operation
     *
     * @param string $operation Operation type (download, populate, etc.)
     * @param string $artistName Artist name (e.g., "Phish")
     * @param int $timeout Maximum seconds to wait for lock (default: 0 = non-blocking)
     * @return string Lock token (pass to release())
     * @throws \ArchiveDotOrg\Core\Model\LockException If lock cannot be acquired
     */
    public function acquire(string $operation, string $artistName, int $timeout = 0): string;

    /**
     * Release lock
     *
     * @param string $lockToken Token from acquire()
     * @return void
     * @throws \ArchiveDotOrg\Core\Model\LockException If lock token invalid
     */
    public function release(string $lockToken): void;

    /**
     * Check if lock is currently held
     *
     * @param string $operation
     * @param string $artistName
     * @return bool True if locked
     */
    public function isLocked(string $operation, string $artistName): bool;

    /**
     * Get information about current lock holder
     *
     * @param string $operation
     * @param string $artistName
     * @return array|null Lock metadata or null if not locked
     */
    public function getLockInfo(string $operation, string $artistName): ?array;

    /**
     * Force release of stale lock (dangerous!)
     *
     * @param string $operation
     * @param string $artistName
     * @return bool True if lock was removed
     */
    public function forceRelease(string $operation, string $artistName): bool;

    /**
     * Cleanup stale locks (locks older than X hours with dead PIDs)
     *
     * @param int $maxAgeHours Maximum age in hours
     * @return int Number of stale locks removed
     */
    public function cleanupStaleLocks(int $maxAgeHours = 24): int;

    /**
     * Release all locks held by this process
     *
     * @return void
     */
    public function releaseAll(): void;
}
