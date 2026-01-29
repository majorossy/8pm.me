<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Create Unmatched Track Table
 *
 * Logs tracks that couldn't be matched to categories during import.
 * Enables admin dashboard for resolving unmatched tracks and quality tracking.
 */
class CreateUnmatchedTrackTable implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;

    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    public static function getDependencies(): array
    {
        return [\ArchiveDotOrg\Core\Setup\Patch\Schema\CreateArtistTable::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $this->schemaSetup->startSetup();

        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('archivedotorg_unmatched_track');

        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                // Primary key
                ->addColumn(
                    'unmatched_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Unmatched ID'
                )
                // Artist reference
                ->addColumn(
                    'artist_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Artist ID (FK)'
                )
                // Context (backward compatibility)
                ->addColumn(
                    'artist_name',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Artist Name'
                )
                ->addColumn(
                    'show_identifier',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Show Identifier'
                )
                ->addColumn(
                    'show_date',
                    Table::TYPE_DATE,
                    null,
                    ['nullable' => true],
                    'Show Date'
                )
                // Track details
                ->addColumn(
                    'track_title',
                    Table::TYPE_TEXT,
                    500,
                    ['nullable' => false],
                    'Track Title'
                )
                ->addColumn(
                    'track_number',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Track Number'
                )
                ->addColumn(
                    'track_file',
                    Table::TYPE_TEXT,
                    500,
                    ['nullable' => true],
                    'Track File'
                )
                // Matching suggestions
                ->addColumn(
                    'suggested_match',
                    Table::TYPE_TEXT,
                    500,
                    ['nullable' => true],
                    'Suggested Match'
                )
                ->addColumn(
                    'match_confidence',
                    Table::TYPE_DECIMAL,
                    '5,2',
                    ['nullable' => true],
                    'Match Confidence (0-100)'
                )
                ->addColumn(
                    'match_algorithm',
                    Table::TYPE_TEXT,
                    50,
                    ['nullable' => true],
                    'Match Algorithm (exact, alias, soundex, levenshtein)'
                )
                // Resolution
                ->addColumn(
                    'status',
                    Table::TYPE_TEXT,
                    20,
                    ['nullable' => false, 'default' => 'pending'],
                    'Status (pending, mapped, ignored, new_track)'
                )
                ->addColumn(
                    'mapped_to_category_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Mapped Category ID'
                )
                ->addColumn(
                    'resolution_notes',
                    Table::TYPE_TEXT,
                    '64K',
                    ['nullable' => true],
                    'Resolution Notes'
                )
                // Tracking
                ->addColumn(
                    'run_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => true],
                    'Import Run ID'
                )
                ->addColumn(
                    'occurrence_count',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 1],
                    'Occurrence Count'
                )
                ->addColumn(
                    'first_seen_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false],
                    'First Seen At'
                )
                ->addColumn(
                    'last_seen_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false],
                    'Last Seen At'
                )
                ->addColumn(
                    'resolved_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true],
                    'Resolved At'
                )
                ->addColumn(
                    'resolved_by',
                    Table::TYPE_TEXT,
                    100,
                    ['nullable' => true],
                    'Resolved By'
                )
                // Timestamps
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                // Regular indexes
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id']),
                    ['artist_id']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_name']),
                    ['artist_name']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['status']),
                    ['status']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['match_confidence']),
                    ['match_confidence']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['run_id']),
                    ['run_id']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['show_identifier']),
                    ['show_identifier']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['resolved_at']),
                    ['resolved_at']
                )
                // Composite indexes for dashboard
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id', 'status', 'match_confidence']),
                    ['artist_id', 'status', 'match_confidence']
                )
                ->addIndex(
                    $this->schemaSetup->getIdxName($tableName, ['artist_id', 'status', 'last_seen_at']),
                    ['artist_id', 'status', 'last_seen_at']
                )
                ->setComment('Tracks That Could Not Be Matched to Categories During Import');

            $connection->createTable($table);
        }

        $this->schemaSetup->endSetup();
    }
}
