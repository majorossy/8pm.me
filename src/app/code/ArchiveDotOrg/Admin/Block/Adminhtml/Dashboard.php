<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Admin\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ResourceConnection;
use ArchiveDotOrg\Admin\Model\Redis\ProgressTracker;

/**
 * Dashboard block for Archive.org imports
 * 
 * Provides stats and data for the dashboard template.
 */
class Dashboard extends Template
{
    protected $_template = 'ArchiveDotOrg_Admin::dashboard.phtml';
    
    public function __construct(
        Context $context,
        private readonly ResourceConnection $resourceConnection,
        private readonly ProgressTracker $progressTracker,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }
    
    /**
     * Get total number of artists
     */
    public function getTotalArtists(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('archivedotorg_artist');
        
        try {
            return (int)$connection->fetchOne(
                $connection->select()->from($tableName, ['COUNT(*)'])
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get total shows downloaded
     */
    public function getTotalShows(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('archivedotorg_artist_status');

        try {
            return (int)$connection->fetchOne(
                $connection->select()
                    ->from($tableName, ['SUM(downloaded_shows)'])
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get total tracks imported
     */
    public function getTotalTracks(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('archivedotorg_artist_status');
        
        try {
            $matched = (int)$connection->fetchOne(
                $connection->select()
                    ->from($tableName, ['SUM(matched_tracks)'])
            );
            $unmatched = (int)$connection->fetchOne(
                $connection->select()
                    ->from($tableName, ['SUM(unmatched_tracks)'])
            );
            return $matched + $unmatched;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get overall match rate
     */
    public function getOverallMatchRate(): float
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('archivedotorg_artist_status');
        
        try {
            $matched = (int)$connection->fetchOne(
                $connection->select()
                    ->from($tableName, ['SUM(matched_tracks)'])
            );
            $unmatched = (int)$connection->fetchOne(
                $connection->select()
                    ->from($tableName, ['SUM(unmatched_tracks)'])
            );

            $total = $matched + $unmatched;
            if ($total === 0) {
                return 0.0;
            }

            return round(($matched / $total) * 100, 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    /**
     * Get count of active imports
     */
    public function getActiveImports(): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('archivedotorg_import_run');
        
        try {
            return (int)$connection->fetchOne(
                $connection->select()
                    ->from($tableName, ['COUNT(*)'])
                    ->where('status = ?', 'running')
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get progress URL for AJAX polling
     */
    public function getProgressUrl(): string
    {
        return $this->getUrl('archivedotorg/progress/status');
    }
}
