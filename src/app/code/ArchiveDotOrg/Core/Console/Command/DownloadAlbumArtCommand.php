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
        $this->setName('archivedotorg:download-album-art')
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
        $output->writeln("<info>Fetching albums for: $artistName</info>");

        try {
            $albums = $this->artworkService->getArtistAlbums($artistName, $limit);

            if (empty($albums)) {
                $output->writeln("<comment>No albums with artwork found for $artistName</comment>");
                return Command::SUCCESS;
            }

            $output->writeln(sprintf('Found %d albums with artwork', count($albums)));

            $downloaded = 0;
            $skipped = 0;

            foreach ($albums as $index => $album) {
                $current = $index + 1;
                $title = $album['title'];
                $year = $album['year'] ?? 'Unknown';

                // Check if already cached
                if ($this->artworkService->isCached($artistName, $title)) {
                    $output->writeln("  [$current/" . count($albums) . "] Cached: $title ($year)");
                    $skipped++;
                    continue;
                }

                // Download
                $localPath = $this->artworkService->downloadArtwork($artistName, $title);

                if ($localPath !== null) {
                    $output->writeln("  [$current/" . count($albums) . "] <info>Downloaded:</info> $title ($year)");
                    $downloaded++;
                } else {
                    $output->writeln("  [$current/" . count($albums) . "] <comment>Failed:</comment> $title ($year)");
                }
            }

            $output->writeln('');
            $output->writeln('<info>Summary:</info>');
            $output->writeln("  Total albums: " . count($albums));
            $output->writeln("  Downloaded: $downloaded");
            $output->writeln("  Already cached: $skipped");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
