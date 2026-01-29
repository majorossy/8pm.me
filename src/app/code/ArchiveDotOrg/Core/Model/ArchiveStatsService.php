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
