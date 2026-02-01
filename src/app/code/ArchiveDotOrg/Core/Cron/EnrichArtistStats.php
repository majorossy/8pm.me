<?php
namespace ArchiveDotOrg\Core\Cron;

use ArchiveDotOrg\Core\Model\ArtistEnrichmentService;
use ArchiveDotOrg\Core\Model\Config;
use Psr\Log\LoggerInterface;

class EnrichArtistStats
{
    private $enrichmentService;
    private $config;
    private $logger;

    public function __construct(
        ArtistEnrichmentService $enrichmentService,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->enrichmentService = $enrichmentService;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->logger->info('Starting monthly artist stats enrichment');

        try {
            // Get all configured artists from Config
            $artistMappings = $this->config->getArtistMappings();
            $artists = array_column($artistMappings, 'artist_name');
            $fields = ['stats_extended']; // Only update stats, not bio/social

            $results = $this->enrichmentService->enrichBatch(
                $artists,
                $fields,
                function($progress) {
                    $this->logger->info("Progress: {$progress['current']}/{$progress['total']} - {$progress['artist']}");
                }
            );

            $this->logger->info('Artist stats enrichment completed', [
                'total' => count($results),
                'successful' => count(array_filter($results, fn($r) => $r['success'])),
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Artist stats enrichment failed: ' . $e->getMessage());
            return false;
        }
    }
}
