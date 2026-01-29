<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\App\ResourceConnection;
use ArchiveDotOrg\Core\Logger\Logger;

/**
 * Service to calculate Archive.org statistics from imported products
 */
class ArchiveStatsService
{
    private ResourceConnection $resourceConnection;
    private Logger $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        Logger $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Get total shows count for an artist
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return int Total number of unique shows
     */
    public function getTotalShows(int $categoryId, string $artistName): int
    {
        $connection = $this->resourceConnection->getConnection();

        // Get attribute IDs
        $identifierAttrId = $this->getAttributeId('identifier');

        if (!$identifierAttrId) {
            $this->logger->error("Identifier attribute not found for stats calculation");
            return 0;
        }

        // Get all category IDs under this artist (artist category + all children)
        $categoryIds = $this->getChildCategoryIds($categoryId);
        $categoryIds[] = $categoryId; // Include parent

        // Count distinct show identifiers for products in these categories
        $select = $connection->select()
            ->from(
                ['p' => $connection->getTableName('catalog_product_entity')],
                ['total_shows' => 'COUNT(DISTINCT ident.value)']
            )
            ->joinInner(
                ['cp' => $connection->getTableName('catalog_category_product')],
                'p.entity_id = cp.product_id',
                []
            )
            ->joinInner(
                ['ident' => $connection->getTableName('catalog_product_entity_varchar')],
                'p.entity_id = ident.entity_id AND ident.attribute_id = ' . $identifierAttrId,
                []
            )
            ->where('cp.category_id IN (?)', $categoryIds);

        $result = $connection->fetchOne($select);

        $this->logger->info("Total shows for $artistName (category $categoryId): $result");

        return (int)$result;
    }

    /**
     * Get most played track for an artist
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return string|null Most frequently played track name
     */
    public function getMostPlayedTrack(int $categoryId, string $artistName): ?string
    {
        $connection = $this->resourceConnection->getConnection();

        // Get attribute IDs - try song_title first, fallback to title
        $songTitleAttrId = $this->getAttributeId('song_title');
        if (!$songTitleAttrId) {
            $songTitleAttrId = $this->getAttributeId('title');
        }

        if (!$songTitleAttrId) {
            $this->logger->error("Neither song_title nor title attribute found for most played track calculation");
            return null;
        }

        // Get all category IDs under this artist (artist category + all children)
        $categoryIds = $this->getChildCategoryIds($categoryId);
        $categoryIds[] = $categoryId; // Include parent

        // Find most frequently played track
        $select = $connection->select()
            ->from(
                ['p' => $connection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['cp' => $connection->getTableName('catalog_category_product')],
                'p.entity_id = cp.product_id',
                []
            )
            ->joinInner(
                ['song' => $connection->getTableName('catalog_product_entity_varchar')],
                'p.entity_id = song.entity_id AND song.attribute_id = ' . $songTitleAttrId,
                ['track_name' => 'song.value', 'play_count' => 'COUNT(*)']
            )
            ->where('cp.category_id IN (?)', $categoryIds)
            ->where('song.value IS NOT NULL')
            ->where('song.value != ?', '')
            ->group('song.value')
            ->order('play_count DESC')
            ->limit(1);

        $result = $connection->fetchRow($select);

        if ($result && isset($result['track_name'])) {
            $trackName = $result['track_name'];
            $playCount = $result['play_count'];
            $this->logger->info("Most played track for $artistName (category $categoryId): $trackName ($playCount times)");
            return $trackName;
        }

        return null;
    }

    /**
     * Get total recordings (tracks) for an artist
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return int Total number of tracks
     */
    public function getTotalRecordings(int $categoryId, string $artistName): int
    {
        $connection = $this->resourceConnection->getConnection();

        // Get all category IDs under this artist (artist category + all children)
        $categoryIds = $this->getChildCategoryIds($categoryId);
        $categoryIds[] = $categoryId; // Include parent

        // Count total products in these categories
        $select = $connection->select()
            ->from(
                ['p' => $connection->getTableName('catalog_product_entity')],
                ['total_recordings' => 'COUNT(*)']
            )
            ->joinInner(
                ['cp' => $connection->getTableName('catalog_category_product')],
                'p.entity_id = cp.product_id',
                []
            )
            ->where('cp.category_id IN (?)', $categoryIds);

        $result = $connection->fetchOne($select);

        $this->logger->info("Total recordings for $artistName (category $categoryId): $result");

        return (int)$result;
    }

    /**
     * Get total hours of audio content for an artist
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return int Total hours (rounded)
     */
    public function getTotalHours(int $categoryId, string $artistName): int
    {
        $connection = $this->resourceConnection->getConnection();

        // Get length attribute ID
        $lengthAttrId = $this->getAttributeId('length');

        if (!$lengthAttrId) {
            $this->logger->error("Length attribute not found for hours calculation");
            return 0;
        }

        // Get all category IDs under this artist (artist category + all children)
        $categoryIds = $this->getChildCategoryIds($categoryId);
        $categoryIds[] = $categoryId; // Include parent

        // Sum all track lengths and convert to hours
        // Length format is typically "MM:SS" or "HH:MM:SS"
        $select = $connection->select()
            ->from(
                ['p' => $connection->getTableName('catalog_product_entity')],
                []
            )
            ->joinInner(
                ['cp' => $connection->getTableName('catalog_category_product')],
                'p.entity_id = cp.product_id',
                []
            )
            ->joinInner(
                ['length' => $connection->getTableName('catalog_product_entity_varchar')],
                'p.entity_id = length.entity_id AND length.attribute_id = ' . $lengthAttrId,
                ['length_value' => 'length.value']
            )
            ->where('cp.category_id IN (?)', $categoryIds)
            ->where('length.value IS NOT NULL')
            ->where('length.value != ?', '');

        $rows = $connection->fetchAll($select);

        $totalSeconds = 0;
        foreach ($rows as $row) {
            $lengthStr = $row['length_value'];

            // Parse MM:SS or HH:MM:SS format
            $parts = explode(':', $lengthStr);
            $seconds = 0;

            if (count($parts) === 2) {
                // MM:SS format
                $seconds = ((int)$parts[0] * 60) + (int)$parts[1];
            } elseif (count($parts) === 3) {
                // HH:MM:SS format
                $seconds = ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
            }

            $totalSeconds += $seconds;
        }

        $totalHours = (int)round($totalSeconds / 3600);

        $this->logger->info("Total hours for $artistName (category $categoryId): $totalHours hours ($totalSeconds seconds)");

        return $totalHours;
    }

    /**
     * Get total unique venues for an artist
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return int Total number of unique venues
     */
    public function getTotalVenues(int $categoryId, string $artistName): int
    {
        $connection = $this->resourceConnection->getConnection();

        // Get show_venue attribute ID
        $venueAttrId = $this->getAttributeId('show_venue');

        if (!$venueAttrId) {
            $this->logger->error("show_venue attribute not found for venues calculation");
            return 0;
        }

        // Get all category IDs under this artist (artist category + all children)
        $categoryIds = $this->getChildCategoryIds($categoryId);
        $categoryIds[] = $categoryId; // Include parent

        // Count distinct venues
        $select = $connection->select()
            ->from(
                ['p' => $connection->getTableName('catalog_product_entity')],
                ['total_venues' => 'COUNT(DISTINCT venue.value)']
            )
            ->joinInner(
                ['cp' => $connection->getTableName('catalog_category_product')],
                'p.entity_id = cp.product_id',
                []
            )
            ->joinInner(
                ['venue' => $connection->getTableName('catalog_product_entity_int')],
                'p.entity_id = venue.entity_id AND venue.attribute_id = ' . $venueAttrId,
                []
            )
            ->where('cp.category_id IN (?)', $categoryIds)
            ->where('venue.value IS NOT NULL');

        $result = $connection->fetchOne($select);

        $this->logger->info("Total unique venues for $artistName (category $categoryId): $result");

        return (int)$result;
    }

    /**
     * Get both stats at once for efficiency
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return array ['total_shows' => int, 'most_played_track' => string|null]
     */
    public function getArtistStats(int $categoryId, string $artistName): array
    {
        return [
            'total_shows' => $this->getTotalShows($categoryId, $artistName),
            'most_played_track' => $this->getMostPlayedTrack($categoryId, $artistName),
        ];
    }

    /**
     * Get all extended stats at once (includes recordings, hours, venues)
     *
     * @param int $categoryId Artist category ID
     * @param string $artistName Artist name (for logging)
     * @return array ['total_shows', 'most_played_track', 'total_recordings', 'total_hours', 'total_venues']
     */
    public function getExtendedArtistStats(int $categoryId, string $artistName): array
    {
        return [
            'total_shows' => $this->getTotalShows($categoryId, $artistName),
            'most_played_track' => $this->getMostPlayedTrack($categoryId, $artistName),
            'total_recordings' => $this->getTotalRecordings($categoryId, $artistName),
            'total_hours' => $this->getTotalHours($categoryId, $artistName),
            'total_venues' => $this->getTotalVenues($categoryId, $artistName),
        ];
    }

    /**
     * Get all child category IDs for a given category
     *
     * @param int $categoryId Parent category ID
     * @return array Child category IDs
     */
    private function getChildCategoryIds(int $categoryId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                $connection->getTableName('catalog_category_entity'),
                ['entity_id']
            )
            ->where('path LIKE ?', "%/$categoryId/%");

        return $connection->fetchCol($select);
    }

    /**
     * Get attribute ID by code
     *
     * @param string $attributeCode Attribute code
     * @return int|null Attribute ID or null
     */
    private function getAttributeId(string $attributeCode): ?int
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($connection->getTableName('eav_attribute'), ['attribute_id'])
            ->where('attribute_code = ?', $attributeCode)
            ->where('entity_type_id = ?', 4); // Product entity type

        $result = $connection->fetchOne($select);

        return $result ? (int)$result : null;
    }
}
