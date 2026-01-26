<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Cache;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * API Response Cache
 *
 * Caches Archive.org API responses to reduce API calls.
 * Supports different TTLs for different operation types.
 */
class ApiResponseCache
{
    /**
     * Cache tag for all Archive.org cached data
     */
    public const CACHE_TAG = 'ARCHIVEDOTORG_API';

    /**
     * Default TTL for import operations (24 hours)
     */
    public const TTL_IMPORT = 86400;

    /**
     * TTL for refresh operations (1 week)
     */
    public const TTL_REFRESH = 604800;

    /**
     * Cache type identifier
     */
    private const CACHE_TYPE = 'archivedotorg_api';

    private CacheInterface $cache;
    private Json $jsonSerializer;

    /**
     * @param CacheInterface $cache
     * @param Json $jsonSerializer
     */
    public function __construct(
        CacheInterface $cache,
        Json $jsonSerializer
    ) {
        $this->cache = $cache;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get cached show metadata
     *
     * @param string $identifier
     * @return array|null
     */
    public function get(string $identifier): ?array
    {
        $cacheKey = $this->getCacheKey($identifier);
        $cachedData = $this->cache->load($cacheKey);

        if ($cachedData === false) {
            return null;
        }

        try {
            $data = $this->jsonSerializer->unserialize($cachedData);
            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save show metadata to cache
     *
     * @param string $identifier
     * @param array $data
     * @param int $ttl Time to live in seconds (default: TTL_IMPORT)
     * @return bool
     */
    public function save(string $identifier, array $data, int $ttl = self::TTL_IMPORT): bool
    {
        $cacheKey = $this->getCacheKey($identifier);

        try {
            $serialized = $this->jsonSerializer->serialize($data);
            return $this->cache->save(
                $serialized,
                $cacheKey,
                [self::CACHE_TAG],
                $ttl
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save with refresh TTL (1 week)
     *
     * @param string $identifier
     * @param array $data
     * @return bool
     */
    public function saveForRefresh(string $identifier, array $data): bool
    {
        return $this->save($identifier, $data, self::TTL_REFRESH);
    }

    /**
     * Check if data is cached for an identifier
     *
     * @param string $identifier
     * @return bool
     */
    public function has(string $identifier): bool
    {
        return $this->get($identifier) !== null;
    }

    /**
     * Remove cached data for an identifier
     *
     * @param string $identifier
     * @return bool
     */
    public function remove(string $identifier): bool
    {
        $cacheKey = $this->getCacheKey($identifier);
        return $this->cache->remove($cacheKey);
    }

    /**
     * Clear all Archive.org API cache
     *
     * @return bool
     */
    public function clear(): bool
    {
        return $this->cache->clean([self::CACHE_TAG]);
    }

    /**
     * Get cache key for an identifier
     *
     * @param string $identifier
     * @return string
     */
    private function getCacheKey(string $identifier): string
    {
        return self::CACHE_TYPE . '_' . sha1($identifier);
    }
}
