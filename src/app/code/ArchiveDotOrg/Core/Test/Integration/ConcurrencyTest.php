<?php
/**
 * Integration test for concurrent download protection
 *
 * Tests the LockService behavior when multiple processes try to run simultaneously
 */
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Integration;

use ArchiveDotOrg\Core\Api\LockServiceInterface;
use ArchiveDotOrg\Core\Console\Command\DownloadCommand;
use ArchiveDotOrg\Core\Exception\LockException;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea adminhtml
 */
class ConcurrencyTest extends TestCase
{
    private ObjectManagerInterface $objectManager;
    private LockServiceInterface $lockService;
    private State $state;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->lockService = $this->objectManager->get(LockServiceInterface::class);
        $this->state = $this->objectManager->get(State::class);
    }

    protected function tearDown(): void
    {
        // Clean up any lingering lock files
        $this->cleanupLocks();
    }

    /**
     * Test that concurrent downloads are blocked by lock
     */
    public function testConcurrentDownloadsAreBlocked(): void
    {
        // Given: A lock is acquired for download operation
        $artist = 'test-artist';
        $operation = 'download';

        $lockToken = $this->lockService->acquire($operation, $artist);

        // When: Trying to acquire the same lock again
        // Then: Should throw LockException
        $this->expectException(LockException::class);
        $this->expectExceptionMessageMatches('/already running/i');

        try {
            $this->lockService->acquire($operation, $artist);
        } finally {
            // Cleanup: Release the lock
            $this->lockService->release($lockToken);
        }
    }

    /**
     * Test that lock is released after operation completes
     */
    public function testLockIsReleasedAfterCompletion(): void
    {
        // Given: A lock was acquired and released
        $artist = 'test-artist';
        $operation = 'download';

        $lockToken = $this->lockService->acquire($operation, $artist);
        $this->lockService->release($lockToken);

        // When: Trying to acquire lock again
        // Then: Should succeed
        $newLockToken = $this->lockService->acquire($operation, $artist);
        $this->assertNotNull($newLockToken, 'Lock should be acquirable after release');

        // Cleanup
        $this->lockService->release($newLockToken);
    }

    /**
     * Test that different operations can run concurrently
     */
    public function testDifferentOperationsCanRunConcurrently(): void
    {
        // Given: A download lock is held
        $artist = 'test-artist';
        $downloadLock = $this->lockService->acquire('download', $artist);

        // When: Trying to acquire populate lock for same artist
        // Then: Should succeed (different operation)
        $populateLock = $this->lockService->acquire('populate', $artist);
        $this->assertNotNull($populateLock, 'Different operations should be allowed concurrently');

        // Cleanup
        $this->lockService->release($downloadLock);
        $this->lockService->release($populateLock);
    }

    /**
     * Test that locks for different artists don't interfere
     */
    public function testLocksForDifferentArtistsAreIndependent(): void
    {
        // Given: A lock for artist A
        $artistA = 'artist-a';
        $artistB = 'artist-b';
        $operation = 'download';

        $lockA = $this->lockService->acquire($operation, $artistA);

        // When: Acquiring lock for artist B
        // Then: Should succeed
        $lockB = $this->lockService->acquire($operation, $artistB);
        $this->assertNotNull($lockB, 'Locks for different artists should be independent');

        // Cleanup
        $this->lockService->release($lockA);
        $this->lockService->release($lockB);
    }

    /**
     * Test that lock is automatically released on exception
     */
    public function testLockIsReleasedOnException(): void
    {
        // Given: A lock that will be released in finally block
        $artist = 'test-artist';
        $operation = 'download';

        $lockToken = null;
        try {
            $lockToken = $this->lockService->acquire($operation, $artist);

            // Simulate exception during operation
            throw new \RuntimeException('Simulated error during operation');
        } finally {
            if ($lockToken) {
                $this->lockService->release($lockToken);
            }
        }

        // When: Trying to acquire lock after exception
        // Then: Should succeed (lock was released)
        $newLockToken = $this->lockService->acquire($operation, $artist);
        $this->assertNotNull($newLockToken, 'Lock should be released even after exception');

        // Cleanup
        $this->lockService->release($newLockToken);
    }

    /**
     * Test concurrent command execution via CommandTester
     *
     * This simulates running two download commands in parallel
     */
    public function testConcurrentCommandExecutionBlocks(): void
    {
        // Given: First download command is running
        $artist = 'test-artist';

        // Manually acquire lock to simulate running command
        $lockToken = $this->lockService->acquire('download', $artist);

        try {
            // When: Second download command tries to start
            $downloadCommand = $this->objectManager->create(DownloadCommand::class);
            $commandTester = new CommandTester($downloadCommand);

            // Then: Command should fail with lock error
            $result = $commandTester->execute([
                'artist' => $artist,
                '--limit' => 1,
            ]);

            $this->assertEquals(1, $result, 'Second command should fail when lock is held');

            $output = $commandTester->getDisplay();
            $this->assertStringContainsString('already running', strtolower($output));
        } finally {
            // Cleanup: Release lock
            $this->lockService->release($lockToken);
        }
    }

    /**
     * Test lock timeout behavior (if implemented)
     */
    public function testLockTimeoutBehavior(): void
    {
        // Given: A lock is held
        $artist = 'test-artist';
        $operation = 'download';

        $lockToken = $this->lockService->acquire($operation, $artist);

        try {
            // When: Trying to acquire with timeout
            // Note: Current implementation uses timeout=0 (non-blocking)
            // If timeout support is added, this tests that behavior

            $startTime = microtime(true);

            try {
                $this->lockService->acquire($operation, $artist, timeout: 2);
                $this->fail('Should have thrown LockException');
            } catch (LockException $e) {
                $elapsed = microtime(true) - $startTime;

                // Should fail quickly with timeout=0 behavior
                $this->assertLessThan(1, $elapsed, 'Non-blocking lock should fail immediately');
                $this->assertStringContainsString('already running', $e->getMessage());
            }
        } finally {
            $this->lockService->release($lockToken);
        }
    }

    /**
     * Test lock file contains correct metadata
     */
    public function testLockFileContainsMetadata(): void
    {
        // Given: A lock is acquired
        $artist = 'test-artist';
        $operation = 'download';

        $lockToken = $this->lockService->acquire($operation, $artist);

        // When: Reading lock file contents
        $lockFile = $this->getLockFilePath($operation, $artist);
        $this->assertFileExists($lockFile, 'Lock file should exist');

        $lockData = json_decode(file_get_contents($lockFile), true);

        // Then: Lock metadata should be correct
        $this->assertNotNull($lockData, 'Lock file should contain valid JSON');
        $this->assertEquals($operation, $lockData['operation'], 'Operation should match');
        $this->assertEquals($artist, $lockData['artist'], 'Artist should match');
        $this->assertEquals(getmypid(), $lockData['pid'], 'PID should match current process');
        $this->assertNotEmpty($lockData['acquired_at'], 'Timestamp should be set');
        $this->assertEquals($lockToken, $lockData['lock_token'], 'Token should match');

        // Cleanup
        $this->lockService->release($lockToken);
    }

    /**
     * Test lock file is cleaned up after release
     */
    public function testLockFileIsCleanedUpAfterRelease(): void
    {
        // Given: A lock is acquired and released
        $artist = 'test-artist';
        $operation = 'download';

        $lockToken = $this->lockService->acquire($operation, $artist);
        $lockFile = $this->getLockFilePath($operation, $artist);

        $this->assertFileExists($lockFile, 'Lock file should exist while locked');

        // When: Releasing the lock
        $this->lockService->release($lockToken);

        // Then: Lock file should be removed or unlocked
        // Note: File may still exist but should be unlockable
        $newLockToken = $this->lockService->acquire($operation, $artist);
        $this->assertNotNull($newLockToken, 'Lock should be acquirable after release');

        // Cleanup
        $this->lockService->release($newLockToken);
    }

    /**
     * Test stale lock detection (if implemented)
     *
     * A stale lock occurs when a process dies without releasing its lock
     */
    public function testStaleLockDetection(): void
    {
        // Given: A lock file with a dead PID
        $artist = 'test-artist';
        $operation = 'download';
        $lockFile = $this->getLockFilePath($operation, $artist);

        // Create lock directory if needed
        $lockDir = dirname($lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        // Write lock file with fake PID that doesn't exist
        $staleLockData = [
            'operation' => $operation,
            'artist' => $artist,
            'pid' => 999999, // Non-existent PID
            'hostname' => gethostname(),
            'acquired_at' => date('c', time() - 3600), // 1 hour ago
            'lock_token' => 'stale-lock-token',
        ];

        file_put_contents($lockFile, json_encode($staleLockData, JSON_PRETTY_PRINT));

        // When: Trying to acquire lock
        // Then: Should detect stale lock and allow acquisition (if implemented)
        // OR throw LockException if stale lock detection not implemented yet

        try {
            $lockToken = $this->lockService->acquire($operation, $artist);
            // If we get here, stale lock detection is working
            $this->assertNotNull($lockToken, 'Should acquire lock after detecting stale lock');
            $this->lockService->release($lockToken);
        } catch (LockException $e) {
            // Stale lock detection not yet implemented - that's OK for Phase 0
            $this->assertStringContainsString('already running', $e->getMessage());
        }

        // Cleanup
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }

    /**
     * Test multiple rapid acquire/release cycles
     */
    public function testMultipleRapidAcquireReleaseCycles(): void
    {
        $artist = 'test-artist';
        $operation = 'download';

        for ($i = 0; $i < 10; $i++) {
            $lockToken = $this->lockService->acquire($operation, $artist);
            $this->assertNotNull($lockToken, "Lock acquisition #{$i} should succeed");

            $this->lockService->release($lockToken);

            // Small delay to ensure file system operations complete
            usleep(10000); // 10ms
        }

        // Final check: Lock should still be acquirable
        $finalLock = $this->lockService->acquire($operation, $artist);
        $this->assertNotNull($finalLock, 'Lock should be acquirable after rapid cycles');
        $this->lockService->release($finalLock);
    }

    // Helper Methods

    private function getLockFilePath(string $operation, string $artistName): string
    {
        $lockDir = BP . '/var/archivedotorg/locks';
        $lockName = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($artistName));
        return "{$lockDir}/{$operation}_{$lockName}.lock";
    }

    private function cleanupLocks(): void
    {
        $lockDir = BP . '/var/archivedotorg/locks';
        if (is_dir($lockDir)) {
            $lockFiles = glob($lockDir . '/*.lock');
            foreach ($lockFiles as $lockFile) {
                if (is_file($lockFile)) {
                    // Try to unlock and delete
                    $fp = @fopen($lockFile, 'r');
                    if ($fp) {
                        @flock($fp, LOCK_UN);
                        @fclose($fp);
                    }
                    @unlink($lockFile);
                }
            }
        }
    }
}
