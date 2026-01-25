<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Cron;

use ArchiveDotOrg\Core\Api\ShowImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\Config;

/**
 * Import Shows Cron Job
 *
 * Scheduled import of shows from configured artist collections
 */
class ImportShows
{
    private ShowImporterInterface $showImporter;
    private Config $config;
    private Logger $logger;

    /**
     * @param ShowImporterInterface $showImporter
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        ShowImporterInterface $showImporter,
        Config $config,
        Logger $logger
    ) {
        $this->showImporter = $showImporter;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            $this->logger->debug('ImportShows cron: Module is disabled');
            return;
        }

        if (!$this->config->isCronEnabled()) {
            $this->logger->debug('ImportShows cron: Cron is disabled');
            return;
        }

        $mappings = $this->config->getArtistMappings();

        if (empty($mappings)) {
            $this->logger->warning('ImportShows cron: No artist mappings configured');
            return;
        }

        $this->logger->info('ImportShows cron: Starting scheduled import', [
            'artists' => count($mappings)
        ]);

        foreach ($mappings as $mapping) {
            $artistName = $mapping['artist_name'] ?? null;
            $collectionId = $mapping['collection_id'] ?? null;

            if ($artistName === null || $collectionId === null) {
                continue;
            }

            try {
                $this->logger->info('ImportShows cron: Processing artist', [
                    'artist' => $artistName,
                    'collection' => $collectionId
                ]);

                $result = $this->showImporter->importByCollection(
                    $artistName,
                    $collectionId,
                    null, // No limit
                    null  // No offset
                );

                $this->logger->info('ImportShows cron: Artist import completed', [
                    'artist' => $artistName,
                    'shows' => $result->getShowsProcessed(),
                    'tracks_created' => $result->getTracksCreated(),
                    'tracks_updated' => $result->getTracksUpdated(),
                    'errors' => $result->getErrorCount()
                ]);
            } catch (\Exception $e) {
                $this->logger->error('ImportShows cron: Artist import failed', [
                    'artist' => $artistName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('ImportShows cron: Scheduled import completed');
    }
}
