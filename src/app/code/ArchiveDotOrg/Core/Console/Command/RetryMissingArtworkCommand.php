<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ArchiveDotOrg\Core\Model\AlbumArtworkService;

/**
 * Retry enrichment for albums with missing artwork using improved matching
 */
class RetryMissingArtworkCommand extends Command
{
    private const OPTION_LIMIT = 'limit';
    private const OPTION_DRY_RUN = 'dry-run';

    private AlbumArtworkService $artworkService;

    public function __construct(
        AlbumArtworkService $artworkService
    ) {
        $this->artworkService = $artworkService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('archivedotorg:retry-missing-artwork')
            ->setDescription('Retry enrichment for albums missing artwork with improved matching')
            ->addOption(
                self::OPTION_LIMIT,
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of albums to process',
                0
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Preview what would be processed without making changes'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int)$input->getOption(self::OPTION_LIMIT);
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);

        if ($dryRun) {
            $output->writeln('<comment>DRY RUN MODE - No changes will be made</comment>');
            $output->writeln('');
        }

        $output->writeln('<info>Re-enriching albums with missing artwork using improved matching...</info>');
        $output->writeln('');

        try {
            // Get albums that don't have artwork yet
            $albums = $this->artworkService->getAlbumsWithoutArtwork($limit);

            if (empty($albums)) {
                $output->writeln('<info>All albums have artwork! 🎉</info>');
                return Command::SUCCESS;
            }

            $output->writeln(sprintf('Found %d albums without artwork', count($albums)));
            $output->writeln('');

            if ($dryRun) {
                foreach ($albums as $index => $album) {
                    $output->writeln(sprintf(
                        '[%d/%d] %s - %s (ID: %d)',
                        $index + 1,
                        count($albums),
                        $album['artist_name'],
                        $album['album_name'],
                        $album['entity_id']
                    ));
                }
                $output->writeln('');
                $output->writeln('<comment>Run without --dry-run to actually enrich these albums</comment>');
                return Command::SUCCESS;
            }

            // Process each album
            $stats = [
                'processed' => 0,
                'found' => 0,
                'stored' => 0,
                'errors' => 0
            ];

            foreach ($albums as $index => $album) {
                $current = $index + 1;
                $total = count($albums);
                $artist = $album['artist_name'];
                $title = $album['album_name'];
                $categoryId = $album['entity_id'];

                $output->write(sprintf(
                    '[%d/%d] Processing: %s - %s...',
                    $current,
                    $total,
                    $artist,
                    $title
                ));

                $stats['processed']++;

                try {
                    $result = $this->artworkService->enrichSingleAlbum(
                        $artist,
                        $title,
                        (int)$categoryId
                    );

                    if ($result['found']) {
                        $stats['found']++;
                        $stats['stored']++;
                        $output->writeln(' <info>✓ Found!</info>');
                    } else {
                        $output->writeln(' <comment>✗ Not found</comment>');
                    }

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $output->writeln(' <error>✗ Error: ' . $e->getMessage() . '</error>');
                }
            }

            $output->writeln('');
            $output->writeln('<info>Summary:</info>');
            $output->writeln("  Albums processed: " . $stats['processed']);
            $output->writeln("  Artwork found: " . $stats['found']);
            $output->writeln("  Stored in database: " . $stats['stored']);
            $output->writeln("  Errors: " . $stats['errors']);

            $improvement = $stats['found'];
            $remaining = count($albums) - $stats['found'];
            $output->writeln('');
            $output->writeln(sprintf(
                '<info>Improvement: %d albums now have artwork, %d still missing</info>',
                $improvement,
                $remaining
            ));

            return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
