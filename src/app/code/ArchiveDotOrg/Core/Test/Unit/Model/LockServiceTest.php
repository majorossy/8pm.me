<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Model\LockService;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for LockService
 *
 * @covers \ArchiveDotOrg\Core\Model\LockService
 */
class LockServiceTest extends TestCase
{
    private LockService $lockService;
    private Filesystem $filesystem;
    private LoggerInterface $logger;
    private WriteInterface $varDirectory;
    private string $testLockDir;

    protected function setUp(): void
    {
        // Create mock dependencies
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->varDirectory = $this->createMock(WriteInterface::class);

        // Set up temp lock directory for tests
        $this->testLockDir = sys_get_temp_dir() . '/archivedotorg_test_locks_' . uniqid();
        mkdir($this->testLockDir, 0755, true);

        // Configure filesystem mock to return test directory
        $this->varDirectory->method('getAbsolutePath')
            ->with('archivedotorg/locks')
            ->willReturn($this->testLockDir);

        $this->filesystem->method('getDirectoryWrite')
            ->with(DirectoryList::VAR_DIR)
            ->willReturn($this->varDirectory);

        // Instantiate service with mocks
        $this->lockService = new LockService($this->filesystem, $this->logger);
    }

    protected function tearDown(): void
    {
        // Clean up test lock directory
        if (is_dir($this->testLockDir)) {
            array_map('unlink', glob($this->testLockDir . '/*.lock'));
            rmdir($this->testLockDir);
        }
    }

    public function testAcquireLockSucceedsWhenNoLockExists(): void
    {
        $lockToken = $this->lockService->acquire('download', 'Phish');

        $this->assertIsString($lockToken);
        $this->assertStringContainsString('download:Phish:', $lockToken);

        // Cleanup
        $this->lockService->release($lockToken);
    }

    public function testAcquireLockFailsWhenLockAlreadyHeld(): void
    {
        $this->expectException(\Throwable::class);

        // Acquire first lock
        $lockToken1 = $this->lockService->acquire('download', 'Phish');

        try {
            // Try to acquire second lock (should fail)
            $this->lockService->acquire('download', 'Phish');
        } finally {
            // Cleanup
            $this->lockService->release($lockToken1);
        }
    }

    public function testReleaseLockSucceeds(): void
    {
        $lockToken = $this->lockService->acquire('populate', 'GratefulDead');

        // Should not throw
        $this->lockService->release($lockToken);

        // Should be able to acquire again
        $lockToken2 = $this->lockService->acquire('populate', 'GratefulDead');
        $this->assertIsString($lockToken2);

        $this->lockService->release($lockToken2);
    }

    public function testReleaseLockIsIdempotent(): void
    {
        $this->expectException(\Throwable::class);

        $lockToken = $this->lockService->acquire('download', 'STS9');
        $this->lockService->release($lockToken);

        // Releasing again should throw
        $this->lockService->release($lockToken);
    }

    public function testIsLockedReturnsFalseWhenNoLock(): void
    {
        $isLocked = $this->lockService->isLocked('download', 'Phish');

        $this->assertFalse($isLocked);
    }

    public function testIsLockedReturnsTrueWhenLocked(): void
    {
        $lockToken = $this->lockService->acquire('download', 'Phish');

        $isLocked = $this->lockService->isLocked('download', 'Phish');

        $this->assertTrue($isLocked);

        // Cleanup
        $this->lockService->release($lockToken);
    }

    public function testGetLockInfoReturnsNullWhenNoLock(): void
    {
        $info = $this->lockService->getLockInfo('download', 'Phish');

        $this->assertNull($info);
    }

    public function testGetLockInfoReturnsMetadataWhenLocked(): void
    {
        $lockToken = $this->lockService->acquire('populate', 'GratefulDead');

        $info = $this->lockService->getLockInfo('populate', 'GratefulDead');

        $this->assertIsArray($info);
        $this->assertEquals('populate', $info['operation']);
        $this->assertEquals('GratefulDead', $info['artist']);
        $this->assertEquals(getmypid(), $info['pid']);
        $this->assertArrayHasKey('hostname', $info);
        $this->assertArrayHasKey('acquired_at', $info);

        // Cleanup
        $this->lockService->release($lockToken);
    }

    public function testForceReleaseRemovesLock(): void
    {
        $lockToken = $this->lockService->acquire('download', 'STS9');

        // Force release without proper unlock
        $result = $this->lockService->forceRelease('download', 'STS9');

        $this->assertTrue($result);
        $this->assertFalse($this->lockService->isLocked('download', 'STS9'));
    }

    public function testForceReleaseReturnsFalseWhenNoLock(): void
    {
        $result = $this->lockService->forceRelease('download', 'NonExistent');

        $this->assertFalse($result);
    }

    public function testReleaseAllReleasesMultipleLocks(): void
    {
        $lock1 = $this->lockService->acquire('download', 'Phish');
        $lock2 = $this->lockService->acquire('populate', 'GratefulDead');

        $this->lockService->releaseAll();

        // Both should now be free
        $this->assertFalse($this->lockService->isLocked('download', 'Phish'));
        $this->assertFalse($this->lockService->isLocked('populate', 'GratefulDead'));
    }

    public function testLockDirectoryCreation(): void
    {
        // Remove the directory to test auto-creation
        if (is_dir($this->testLockDir)) {
            rmdir($this->testLockDir);
        }

        // Create new service instance (will create directory)
        $newService = new LockService($this->filesystem, $this->logger);

        $this->assertTrue(is_dir($this->testLockDir));
    }

    public function testArtistNameSanitization(): void
    {
        // Test with special characters that should be sanitized
        $lockToken = $this->lockService->acquire('download', 'Artist/With\\Special*Chars');

        $this->assertIsString($lockToken);

        // Verify lock file exists with sanitized name
        $files = glob($this->testLockDir . '/download_*.lock');
        $this->assertCount(1, $files);
        $filename = basename($files[0]);
        $this->assertMatchesRegularExpression('/^download_Artist_With_Special_Chars\.lock$/', $filename);

        // Cleanup
        $this->lockService->release($lockToken);
    }

    public function testAcquireWithTimeoutWaitsForLock(): void
    {
        $lock1 = $this->lockService->acquire('download', 'Phish');

        // Release lock in background after 1 second
        $pid = pcntl_fork();
        if ($pid == 0) {
            // Child process
            sleep(1);
            $this->lockService->release($lock1);
            exit(0);
        }

        // Parent process tries to acquire with 3 second timeout
        $start = microtime(true);

        if ($pid > 0) {
            try {
                $lock2 = $this->lockService->acquire('download', 'Phish', 3);
                $elapsed = microtime(true) - $start;

                // Should succeed after ~1 second
                $this->assertGreaterThanOrEqual(0.9, $elapsed);
                $this->assertLessThan(2, $elapsed);
                $this->assertIsString($lock2);

                pcntl_wait($status);
            } catch (\Exception $e) {
                pcntl_wait($status);
                $this->lockService->release($lock1);
                throw $e;
            }
        }

        $this->markTestSkipped('pcntl_fork not available');
    }

    public function testAcquireWithZeroTimeoutFailsImmediately(): void
    {
        $this->expectException(\Throwable::class);

        $lock1 = $this->lockService->acquire('download', 'Phish');

        try {
            // Should fail immediately with timeout=0
            $this->lockService->acquire('download', 'Phish', 0);
        } finally {
            $this->lockService->release($lock1);
        }
    }

    public function testCleanupStaleLocksRemovesOldLocks(): void
    {
        // Create a lock file manually with old timestamp
        $lockFile = $this->testLockDir . '/download_TestArtist.lock';
        file_put_contents($lockFile, json_encode([
            'operation' => 'download',
            'artist' => 'TestArtist',
            'pid' => 99999, // Non-existent PID
            'hostname' => 'testhost',
            'acquired_at' => date('c', time() - (25 * 3600)) // 25 hours ago
        ]));

        // Set file modification time to 25 hours ago
        touch($lockFile, time() - (25 * 3600));

        // Cleanup stale locks older than 24 hours
        $count = $this->lockService->cleanupStaleLocks(24);

        $this->assertEquals(1, $count);
        $this->assertFileDoesNotExist($lockFile);
    }

    public function testCleanupStaleLocksPreservesRecentLocks(): void
    {
        // Create a recent lock
        $lockToken = $this->lockService->acquire('download', 'Phish');

        $count = $this->lockService->cleanupStaleLocks(24);

        // Should not remove recent lock
        $this->assertEquals(0, $count);
        $this->assertTrue($this->lockService->isLocked('download', 'Phish'));

        // Cleanup
        $this->lockService->release($lockToken);
    }

    public function testDestructorReleasesAllLocks(): void
    {
        $service = new LockService($this->filesystem, $this->logger);
        $service->acquire('download', 'Phish');
        $service->acquire('populate', 'GratefulDead');

        // Trigger destructor
        unset($service);

        // Locks should be released
        $this->assertFalse($this->lockService->isLocked('download', 'Phish'));
        $this->assertFalse($this->lockService->isLocked('populate', 'GratefulDead'));
    }
}
