<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

/**
 * Show Unmatched Tracks Command
 *
 * CLI command to display tracks that don't match canonical song names
 * Usage: bin/magento archive:show-unmatched lettuce
 *        bin/magento archive:show-unmatched --all --limit=50
 */
class ShowUnmatchedCommand extends Command
{
    private const ARGUMENT_ARTIST = 'artist';
    private const OPTION_ALL = 'all';
    private const OPTION_LIMIT = 'limit';

    /**
     * @param TrackMatcherServiceInterface $trackMatcher
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Config $config
     * @param string|null $name
     */
    public function __construct(
        private readonly TrackMatcherServiceInterface $trackMatcher,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly Config $config,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:show-unmatched')
            ->setDescription('Show unmatched tracks for an artist or all artists')
            ->addArgument(
                self::ARGUMENT_ARTIST,
                InputArgument::OPTIONAL,
                'Artist name (e.g., "Lettuce", "Phish")'
            )
            ->addOption(
                self::OPTION_ALL,
                'a',
                InputOption::VALUE_NONE,
                'Show unmatched tracks for all configured artists'
            )
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of unmatched tracks to display',
                '50'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $artistName = $input->getArgument(self::ARGUMENT_ARTIST);
        $showAll = $input->getOption(self::OPTION_ALL);
        $limit = (int) $input->getOption(self::OPTION_LIMIT);

        // Validate inputs
        if (!$showAll && empty($artistName)) {
            $io->error('Please specify an artist name or use --all flag.');
            return Command::FAILURE;
        }

        if ($showAll && !empty($artistName)) {
            $io->error('Cannot use both artist name and --all flag together.');
            return Command::FAILURE;
        }

        // Get artists to process
        $artists = $showAll ? $this->getAllArtists() : [$artistName];

        if (empty($artists)) {
            $io->warning('No configured artists found.');
            return Command::SUCCESS;
        }

        $io->title('Unmatched Tracks Report');

        $totalUnmatched = 0;

        foreach ($artists as $artist) {
            $unmatched = $this->findUnmatchedTracks($artist);

            if (empty($unmatched)) {
                if (!$showAll) {
                    $io->success("No unmatched tracks found for {$artist}!");
                }
                continue;
            }

            $artistKey = $this->getArtistKey($artist);
            $displayCount = min($limit, count($unmatched));
            $totalCount = count($unmatched);
            $totalUnmatched += $totalCount;

            $io->section("{$artist} ({$totalCount} unmatched tracks)");

            if ($displayCount < $totalCount) {
                $io->note("Showing first {$displayCount} of {$totalCount} unmatched tracks");
            }

            // Build table data
            $tableData = [];
            $displayed = 0;

            foreach ($unmatched as $trackName => $showCount) {
                if ($displayed >= $displayCount) {
                    break;
                }

                $suggestion = $this->getSuggestedMatch($trackName, $artistKey);
                $tableData[] = [
                    $trackName,
                    $showCount,
                    $suggestion
                ];

                $displayed++;
            }

            // Display table
            $table = new Table($output);
            $table->setHeaders(['Track Name', 'Shows', 'Suggested Match']);
            $table->setRows($tableData);
            $table->render();

            $io->writeln('');
            $io->writeln("Add aliases to config/artists/{$artistKey}.yaml to fix.");
            $io->writeln('');
        }

        if ($showAll && $totalUnmatched === 0) {
            $io->success('No unmatched tracks found across all artists!');
        } elseif ($showAll) {
            $io->note("Total unmatched tracks across all artists: {$totalUnmatched}");
        }

        return Command::SUCCESS;
    }

    /**
     * Get all configured artists from config
     *
     * @return string[]
     */
    private function getAllArtists(): array
    {
        $mappings = $this->config->getArtistMappings();
        $artists = [];

        foreach ($mappings as $mapping) {
            if (!empty($mapping['artist_name'])) {
                $artists[] = $mapping['artist_name'];
            }
        }

        return $artists;
    }

    /**
     * Find unmatched tracks for an artist
     *
     * @param string $artistName
     * @return array<string, int> Track name => show count
     */
    private function findUnmatchedTracks(string $artistName): array
    {
        $artistKey = $this->getArtistKey($artistName);

        // Get all products for this artist
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['title', 'song_title', 'archive_collection']);
        $collection->addAttributeToFilter('archive_collection', $artistName);

        $unmatchedCounts = [];

        foreach ($collection as $product) {
            $trackTitle = $product->getData('song_title') ?: $product->getData('title');

            if (empty($trackTitle)) {
                continue;
            }

            // Try to match this track
            $matchResult = $this->trackMatcher->match($trackTitle, $artistKey);

            // If no match, record it
            if ($matchResult === null) {
                if (!isset($unmatchedCounts[$trackTitle])) {
                    $unmatchedCounts[$trackTitle] = 0;
                }
                $unmatchedCounts[$trackTitle]++;
            }
        }

        // Sort by frequency (most common first)
        arsort($unmatchedCounts);

        return $unmatchedCounts;
    }

    /**
     * Get suggested match for a track name
     *
     * @param string $trackName
     * @param string $artistKey
     * @return string
     */
    private function getSuggestedMatch(string $trackName, string $artistKey): string
    {
        // Try matching to get a suggested track (this will use metaphone/fuzzy)
        $matchResult = $this->trackMatcher->match($trackName, $artistKey);

        if ($matchResult === null) {
            return 'No suggestion';
        }

        $matchType = $matchResult->getMatchType();
        $trackKey = $matchResult->getTrackKey();

        // Format: "Track Name (metaphone)"
        return "{$trackKey} ({$matchType})";
    }

    /**
     * Convert artist name to URL key format
     *
     * @param string $artistName
     * @return string
     */
    private function getArtistKey(string $artistName): string
    {
        return strtolower(str_replace(' ', '-', $artistName));
    }
}
