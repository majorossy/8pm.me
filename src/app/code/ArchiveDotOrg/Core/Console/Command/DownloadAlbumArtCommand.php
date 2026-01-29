<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\AlbumArtworkServiceInterface;
use ArchiveDotOrg\Core\Model\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to download album artwork from MusicBrainz
 *
 * Usage:
 *   bin/magento archivedotorg:download-album-art "Grateful Dead"
 *   bin/magento archivedotorg:download-album-art "Phish" --limit=20
 *   bin/magento archivedotorg:download-album-art --all
 */
class DownloadAlbumArtCommand extends Command
{
    private const ARTIST_ARGUMENT = 'artist';
    private const OPTION_ALL = 'all';
    private const OPTION_LIMIT = 'limit';

    private AlbumArtworkServiceInterface $artworkService;
    private Config $config;

    public function __construct(
        AlbumArtworkServiceInterface $artworkService,
        Config $config,
        string $name = null
    ) {
        parent::__construct($name);
        $this->artworkService = $artworkService;
        $this->config = $config;
    }

    protected function configure(): void
    {
        $this->setName('archive:artwork:download')
            ->setDescription('Download studio album artwork from MusicBrainz')
            ->addArgument(
                self::ARTIST_ARGUMENT,
                InputArgument::OPTIONAL,
                'Artist name (e.g., "Grateful Dead")'
            )
            ->addOption(
                self::OPTION_ALL,
                null,
                InputOption::VALUE_NONE,
                'Download artwork for all configured artists'
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_REQUIRED,
                'Maximum number of albums per artist',
                '50'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $artistName = $input->getArgument(self::ARTIST_ARGUMENT);
        $downloadAll = $input->getOption(self::OPTION_ALL);
        $limit = (int)$input->getOption(self::OPTION_LIMIT);

        if ($downloadAll) {
            return $this->downloadAllArtists($output, $limit);
        }

        if (empty($artistName)) {
            $output->writeln('<error>Please provide an artist name or use --all</error>');
            return Command::FAILURE;
        }

        return $this->downloadForArtist($artistName, $output, $limit);
    }

    /**
     * Download artwork for all configured artists
     */
    private function downloadAllArtists(OutputInterface $output, int $limit): int
    {
        $mappings = $this->config->getArtistMappings();

        if (empty($mappings)) {
            $output->writeln('<error>No artist mappings configured</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('Downloading artwork for %d artists...', count($mappings)));

        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($mappings as $mapping) {
            $artistName = $mapping['artist_name'] ?? null;
            if ($artistName === null) {
                continue;
            }

            $result = $this->downloadForArtist($artistName, $output, $limit);
            if ($result === Command::SUCCESS) {
                $totalSuccess++;
            } else {
                $totalFailed++;
            }

            $output->writeln('');
        }

        $output->writeln('<info>Summary:</info>');
        $output->writeln("  Artists processed: " . ($totalSuccess + $totalFailed));
        $output->writeln("  Success: $totalSuccess");
        $output->writeln("  Failed: $totalFailed");

        return $totalFailed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Download artwork for a single artist
     */
    private function downloadForArtist(string $artistName, OutputInterface $output, int $limit): int
    {
        $output->writeln("<info>Enriching albums with Wikipedia artwork for: $artistName</info>");

        try {
            $stats = $this->artworkService->enrichAlbumsWithArtwork($artistName, $limit);

            $output->writeln('');
            $output->writeln('<info>Summary:</info>');
            $output->writeln("  Albums processed: " . $stats['processed']);
            $output->writeln("  Artwork found: " . $stats['found']);
            $output->writeln("  Stored in database: " . $stats['stored']);
            $output->writeln("  Errors: " . $stats['errors']);

            return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
