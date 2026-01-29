<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\App\ResourceConnection;

/**
 * Resolver for band_total_recordings field
 */
class BandTotalRecordings implements ResolverInterface
{
    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['name'])) {
            return null;
        }

        $artistName = $value['name'];
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('archivedotorg_artist_status');

        $select = $connection->select()
            ->from($tableName, ['imported_tracks'])
            ->where('artist_name = ?', $artistName)
            ->limit(1);

        $result = $connection->fetchOne($select);

        return $result ? (int)$result : null;
    }
}
