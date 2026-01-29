<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Model\Redis;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Redis-based progress tracking for long-running imports
 * 
 * Stores progress data in Redis with 1-hour TTL for real-time dashboard updates.
 */
class ProgressTracker
{
    private const KEY_PREFIX = 'archivedotorg:progress:';
    private const TTL = 3600; // 1 hour
    
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {}
    
    /**
     * Update progress for an import operation
     */
    public function updateProgress(
        string $artist,
        string $correlationId,
        int $current,
        int $total,
        int $processed,
        string $status = 'running'
    ): void {
        try {
            $eta = $this->calculateEta($current, $total, $processed);
            
            $this->setKey($artist, 'current', (string)$current);
            $this->setKey($artist, 'total', (string)$total);
            $this->setKey($artist, 'processed', (string)$processed);
            $this->setKey($artist, 'status', $status);
            $this->setKey($artist, 'correlation_id', $correlationId);
            $this->setKey($artist, 'eta', $eta);
        } catch (\Exception $e) {
            // Don't fail the import if Redis is down
            $this->logger->warning('Failed to update Redis progress: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark import as completed
     */
    public function complete(string $artist): void
    {
        try {
            $this->setKey($artist, 'status', 'completed');
            // Keep keys for 5 minutes after completion so dashboard can show final state
            $this->setKeyWithTtl($artist, 'completed_at', date('c'), 300);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to mark Redis progress as complete: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark import as failed
     */
    public function fail(string $artist, string $errorMessage): void
    {
        try {
            $this->setKey($artist, 'status', 'failed');
            $this->setKey($artist, 'error', $errorMessage);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to mark Redis progress as failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get progress data for an artist
     * 
     * @return array{artist: string, status: string, current: int, total: int, processed: int, eta: string, correlation_id: string}|null
     */
    public function getProgress(string $artist): ?array
    {
        try {
            $status = $this->getKey($artist, 'status');
            if (!$status) {
                return null;
            }
            
            return [
                'artist' => $artist,
                'status' => $status,
                'current' => (int)$this->getKey($artist, 'current'),
                'total' => (int)$this->getKey($artist, 'total'),
                'processed' => (int)$this->getKey($artist, 'processed'),
                'eta' => $this->getKey($artist, 'eta') ?? '',
                'correlation_id' => $this->getKey($artist, 'correlation_id') ?? '',
                'error' => $this->getKey($artist, 'error') ?? ''
            ];
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get Redis progress: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Clear all progress keys for an artist
     */
    public function clear(string $artist): void
    {
        try {
            $keys = ['current', 'total', 'processed', 'status', 'correlation_id', 'eta', 'error', 'completed_at'];
            foreach ($keys as $key) {
                $this->cache->remove($this->buildKey($artist, $key));
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to clear Redis progress: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate estimated time of completion
     */
    private function calculateEta(int $current, int $total, int $processed): string
    {
        if ($current === 0 || $total === 0) {
            return '';
        }
        
        // Simple linear estimation based on current progress
        $percentComplete = $current / $total;
        if ($percentComplete === 0) {
            return '';
        }
        
        $startTime = $this->getKey('_meta', 'start_time');
        if (!$startTime) {
            // First update - store start time
            $startTime = time();
            $this->setKey('_meta', 'start_time', (string)$startTime);
            return '';
        }
        
        $elapsed = time() - (int)$startTime;
        $totalEstimated = (int)($elapsed / $percentComplete);
        $remaining = $totalEstimated - $elapsed;
        
        $eta = new \DateTime();
        $eta->add(new \DateInterval('PT' . $remaining . 'S'));
        
        return $eta->format('c');
    }
    
    /**
     * Build Redis key
     */
    private function buildKey(string $artist, string $suffix): string
    {
        return self::KEY_PREFIX . strtolower($artist) . ':' . $suffix;
    }
    
    /**
     * Set Redis key with default TTL
     */
    private function setKey(string $artist, string $suffix, string $value): void
    {
        $this->setKeyWithTtl($artist, $suffix, $value, self::TTL);
    }
    
    /**
     * Set Redis key with custom TTL
     */
    private function setKeyWithTtl(string $artist, string $suffix, string $value, int $ttl): void
    {
        $this->cache->save($value, $this->buildKey($artist, $suffix), [], $ttl);
    }
    
    /**
     * Get Redis key value
     */
    private function getKey(string $artist, string $suffix): ?string
    {
        $value = $this->cache->load($this->buildKey($artist, $suffix));
        return $value !== false ? $value : null;
    }
}
