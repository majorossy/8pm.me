<?php
/**
 * Copyright © Archive.org Import. All rights reserved.
 * File locking service to prevent concurrent operations
 */
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\LockServiceInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * LockService - Advisory file locking for concurrent operation prevention
 *
 * Purpose: Prevents two simultaneous downloads/populates for same artist
 *
 * Usage:
 * ```php
 * $lock = $this->lockService->acquire('download', 'Phish');
 * try {
 *     // Do work
 * } finally {
 *     $this->lockService->release($lock);
 * }
 * ```
 */
class LockService implements LockServiceInterface
{
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var string
     */
    private string $lockDir;

    /**
     * @var array<string, resource> Active lock file handles
     */
    private array $locks = [];

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;

        // Lock directory: var/archivedotorg/locks/
        $varDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->lockDir = $varDir->getAbsolutePath('archivedotorg/locks');

        // Create locks directory if it doesn't exist
        if (!is_dir($this->lockDir)) {
            mkdir($this->lockDir, 0755, true);
        }
    }

    /**
     * Acquire exclusive lock for artist operation
     *
     * @param string $operation Operation type (download, populate, etc.)
     * @param string $artistName Artist name (e.g., "Phish")
     * @param int $timeout Maximum seconds to wait for lock (default: 0 = non-blocking)
     * @return string Lock token (pass to release())
     * @throws LockException If lock cannot be acquired
     */
    public function acquire(string $operation, string $artistName, int $timeout = 0): string
    {
        $lockFile = $this->getLockFilePath($operation, $artistName);
        $lockToken = $this->getLockToken($operation, $artistName);

        $this->logger->info("Attempting to acquire lock", [
            'operation' => $operation,
            'artist' => $artistName,
            'lock_file' => $lockFile,
            'timeout' => $timeout
        ]);

        // Open lock file
        $fp = fopen($lockFile, 'c');
        if ($fp === false) {
            throw new LockException("Failed to open lock file: $lockFile");
        }

        // Determine lock mode
        $lockMode = $timeout > 0 ? LOCK_EX : LOCK_EX | LOCK_NB;

        // Try to acquire lock
        $start = time();
        $acquired = false;

        while (true) {
            if (flock($fp, $lockMode)) {
                $acquired = true;
                break;
            }

            // If non-blocking or timeout exceeded, fail
            if ($timeout === 0 || (time() - $start) >= $timeout) {
                fclose($fp);
                throw new LockException(
                    sprintf(
                        "Another '%s' operation is already running for artist '%s'. " .
                        "Wait for it to complete or use --force to override (dangerous).",
                        $operation,
                        $artistName
                    )
                );
            }

            // Wait 100ms before retrying
            usleep(100000);
        }

        // Write lock metadata to file
        ftruncate($fp, 0);
        fwrite($fp, json_encode([
            'operation' => $operation,
            'artist' => $artistName,
            'pid' => getmypid(),
            'hostname' => gethostname(),
            'acquired_at' => date('c'),
            'lock_token' => $lockToken
        ], JSON_PRETTY_PRINT));
        fflush($fp);

        // Store file handle
        $this->locks[$lockToken] = $fp;

        $this->logger->info("Lock acquired successfully", [
            'operation' => $operation,
            'artist' => $artistName,
            'lock_token' => $lockToken,
            'pid' => getmypid()
        ]);

        return $lockToken;
    }

    /**
     * Release lock
     *
     * @param string $lockToken Token from acquire()
     * @return void
     * @throws LockException If lock token invalid
     */
    public function release(string $lockToken): void
    {
        if (!isset($this->locks[$lockToken])) {
            throw new LockException("Invalid lock token: $lockToken");
        }

        $fp = $this->locks[$lockToken];

        // Release lock and close file
        flock($fp, LOCK_UN);
        fclose($fp);

        unset($this->locks[$lockToken]);

        $this->logger->info("Lock released", [
            'lock_token' => $lockToken
        ]);
    }

    /**
     * Check if lock is currently held
     *
     * @param string $operation
     * @param string $artistName
     * @return bool True if locked
     */
    public function isLocked(string $operation, string $artistName): bool
    {
        $lockFile = $this->getLockFilePath($operation, $artistName);

        if (!file_exists($lockFile)) {
            return false;
        }

        // Try non-blocking lock
        $fp = fopen($lockFile, 'r');
        if ($fp === false) {
            return false;
        }

        $locked = !flock($fp, LOCK_EX | LOCK_NB);

        // Release immediately (we were just checking)
        if (!$locked) {
            flock($fp, LOCK_UN);
        }

        fclose($fp);

        return $locked;
    }

    /**
     * Get information about current lock holder
     *
     * @param string $operation
     * @param string $artistName
     * @return array|null Lock metadata or null if not locked
     */
    public function getLockInfo(string $operation, string $artistName): ?array
    {
        $lockFile = $this->getLockFilePath($operation, $artistName);

        if (!file_exists($lockFile)) {
            return null;
        }

        // Read lock file content
        $content = file_get_contents($lockFile);
        if ($content === false) {
            return null;
        }

        $info = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $info;
    }

    /**
     * Force release of stale lock (dangerous!)
     *
     * Only use if you're CERTAIN the process holding the lock has crashed.
     *
     * @param string $operation
     * @param string $artistName
     * @return bool True if lock was removed
     */
    public function forceRelease(string $operation, string $artistName): bool
    {
        $lockFile = $this->getLockFilePath($operation, $artistName);

        if (!file_exists($lockFile)) {
            return false;
        }

        $this->logger->warning("FORCE RELEASING lock (dangerous!)", [
            'operation' => $operation,
            'artist' => $artistName,
            'lock_file' => $lockFile
        ]);

        return unlink($lockFile);
    }

    /**
     * Cleanup stale locks (locks older than X hours with dead PIDs)
     *
     * @param int $maxAgeHours Maximum age in hours
     * @return int Number of stale locks removed
     */
    public function cleanupStaleLocks(int $maxAgeHours = 24): int
    {
        $count = 0;
        $cutoff = time() - ($maxAgeHours * 3600);

        $files = glob($this->lockDir . '/*.lock');
        if ($files === false) {
            return 0;
        }

        foreach ($files as $lockFile) {
            // Check file age
            $mtime = filemtime($lockFile);
            if ($mtime === false || $mtime > $cutoff) {
                continue;
            }

            // Check if PID is still alive
            $info = json_decode(file_get_contents($lockFile), true);
            if ($info === null || !isset($info['pid'])) {
                continue;
            }

            $pid = (int)$info['pid'];

            // Check if process exists (POSIX only)
            if (function_exists('posix_kill')) {
                if (posix_kill($pid, 0)) {
                    // Process still alive, don't remove
                    continue;
                }
            }

            // Remove stale lock
            $this->logger->info("Removing stale lock", [
                'lock_file' => $lockFile,
                'pid' => $pid,
                'age_hours' => round((time() - $mtime) / 3600, 1)
            ]);

            unlink($lockFile);
            $count++;
        }

        return $count;
    }

    /**
     * Release all locks held by this process
     *
     * Called automatically on shutdown, but can be called explicitly
     *
     * @return void
     */
    public function releaseAll(): void
    {
        foreach (array_keys($this->locks) as $lockToken) {
            try {
                $this->release($lockToken);
            } catch (\Exception $e) {
                $this->logger->error("Failed to release lock on shutdown", [
                    'lock_token' => $lockToken,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get lock file path
     *
     * @param string $operation
     * @param string $artistName
     * @return string
     */
    private function getLockFilePath(string $operation, string $artistName): string
    {
        // Sanitize artist name for filename
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $artistName);

        return sprintf('%s/%s_%s.lock', $this->lockDir, $operation, $safeName);
    }

    /**
     * Generate unique lock token
     *
     * @param string $operation
     * @param string $artistName
     * @return string
     */
    private function getLockToken(string $operation, string $artistName): string
    {
        return sprintf('%s:%s:%d', $operation, $artistName, getmypid());
    }

    /**
     * Destructor - release all locks on shutdown
     */
    public function __destruct()
    {
        $this->releaseAll();
    }
}
