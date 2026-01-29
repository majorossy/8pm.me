<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\FileManifestService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Cleanup old metadata cache files
 *
 * Strategies:
 * - Delete files older than N days
 * - Keep N most recent files per artist
 * - Only cleanup specific artist
 *
 * Safety:
 * - Never deletes files that have associated products
 * - Always requires confirmation unless --force is used
 * - Dry-run mode for preview
 */
class CleanupCacheCommand extends Command
{
    private const METADATA_DIR = 'archivedotorg/metadata';

    private DirectoryList $directoryList;
    private File $file;
    private Logger $logger;
    private FileManifestService $manifestService;
    private ProductRepositoryInterface $productRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private string $varDir;

    public function __construct(
        DirectoryList $directoryList,
        File $file,
        Logger $logger,
        FileManifestService $manifestService,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        string $name = null
    ) {
        parent::__construct($name);
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->logger = $logger;
        $this->manifestService = $manifestService;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->varDir = $directoryList->getPath('var');
    }

    protected function configure(): void
    {
        $this->setName('archive:cleanup:cache');
        $this->setDescription('Cleanup old metadata cache files');
        $this->addOption(
            'older-than',
            null,
            InputOption::VALUE_REQUIRED,
            'Delete files older than N days'
        );
        $this->addOption(
            'keep-latest',
            null,
            InputOption::VALUE_REQUIRED,
            'Keep N most recent files per artist'
        );
        $this->addOption(
            'artist',
            null,
            InputOption::VALUE_REQUIRED,
            'Only cleanup specific artist'
        );
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview what would be deleted without actually deleting'
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Skip confirmation prompt'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $olderThan = $input->getOption('older-than');
        $keepLatest = $input->getOption('keep-latest');
        $artist = $input->getOption('artist');
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $output->writeln('<info>Archive.org Cache Cleanup</info>');
        $output->writeln('');

        // Validate options
        if ($olderThan === null && $keepLatest === null) {
            $output->writeln('<error>Must specify either --older-than or --keep-latest</error>');
            return Command::FAILURE;
        }

        if ($olderThan !== null && !is_numeric($olderThan)) {
            $output->writeln('<error>--older-than must be a number</error>');
            return Command::FAILURE;
        }

        if ($keepLatest !== null && !is_numeric($keepLatest)) {
            $output->writeln('<error>--keep-latest must be a number</error>');
            return Command::FAILURE;
        }

        $metadataDir = $this->varDir . '/' . self::METADATA_DIR;

        if (!$this->file->isDirectory($metadataDir)) {
            $output->writeln('<comment>No metadata directory found. Nothing to clean up.</comment>');
            return Command::SUCCESS;
        }

        // Get artists to process
        $artists = $this->getArtists($metadataDir, $artist);

        if (empty($artists)) {
            $output->writeln('<comment>No artists found to process.</comment>');
            return Command::SUCCESS;
        }

        $output->writeln("Processing <info>" . count($artists) . "</info> artist(s)");
        $output->writeln('');

        // Build list of files to delete
        $toDelete = [];
        $protected = [];

        foreach ($artists as $artistId) {
            $output->writeln("Analyzing: <info>$artistId</info>");

            $files = $this->getFilesForArtist($metadataDir, $artistId);
            $filesCount = count($files);

            if ($filesCount === 0) {
                $output->writeln("  No files found");
                continue;
            }

            // Get products for this artist to protect associated files
            $productIdentifiers = $this->getProductIdentifiers($artistId);

            // Filter files based on strategy
            $candidates = $this->filterFiles($files, $olderThan, $keepLatest);

            foreach ($candidates as $file) {
                $basename = basename($file, '.json');

                // Check if file has associated product
                if (in_array($basename, $productIdentifiers, true)) {
                    $protected[] = $file;
                } else {
                    $toDelete[] = $file;
                }
            }

            $candidatesCount = count($candidates);
            $protectedCount = count(array_filter($candidates, function ($file) use ($productIdentifiers) {
                return in_array(basename($file, '.json'), $productIdentifiers, true);
            }));
            $deletableCount = $candidatesCount - $protectedCount;

            $output->writeln("  Total files:      $filesCount");
            $output->writeln("  Candidates:       $candidatesCount");
            $output->writeln("  Protected:        $protectedCount");
            $output->writeln("  To delete:        $deletableCount");
            $output->writeln('');
        }

        // Summary
        $totalToDelete = count($toDelete);
        $totalProtected = count($protected);

        $output->writeln('<info>Summary:</info>');
        $output->writeln("  Files to delete:  $totalToDelete");
        $output->writeln("  Protected files:  $totalProtected");
        $output->writeln('');

        if ($totalToDelete === 0) {
            $output->writeln('<comment>No files to delete</comment>');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $output->writeln('<comment>DRY RUN - No files will be deleted</comment>');
            $output->writeln('');
            $output->writeln('Files that would be deleted:');
            foreach (array_slice($toDelete, 0, 10) as $file) {
                $output->writeln("  - " . basename($file));
            }
            if ($totalToDelete > 10) {
                $remaining = $totalToDelete - 10;
                $output->writeln("  ... and $remaining more");
            }
            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$force) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "Delete $totalToDelete files? [y/N] ",
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Cleanup cancelled</comment>');
                return Command::SUCCESS;
            }
        }

        // Delete files
        $output->writeln('');
        $output->writeln('Deleting files...');

        $deleted = 0;
        $failed = 0;

        foreach ($toDelete as $file) {
            try {
                $basename = basename($file);
                $artistId = basename(dirname($file));

                $this->file->deleteFile($file);
                $deleted++;

                // Update manifest
                $this->manifestService->removeFile($artistId, $basename);

                // Progress output
                if ($deleted % 50 === 0) {
                    $output->writeln("  Deleted: $deleted / $totalToDelete");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->logger->error("Failed to delete file: " . basename($file), [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $output->writeln('');
        $output->writeln('<info>Cleanup Complete!</info>');
        $output->writeln("  Deleted:  $deleted");
        $output->writeln("  Failed:   $failed");

        $this->logger->info('Cache cleanup complete', [
            'deleted' => $deleted,
            'failed' => $failed,
            'protected' => $totalProtected,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Get list of artists to process
     */
    private function getArtists(string $metadataDir, ?string $specificArtist): array
    {
        if ($specificArtist !== null) {
            $artistDir = $metadataDir . '/' . $specificArtist;
            if ($this->file->isDirectory($artistDir)) {
                return [$specificArtist];
            }
            return [];
        }

        // Get all artist directories
        $artists = [];
        $items = $this->file->readDirectory($metadataDir);

        foreach ($items as $item) {
            $basename = basename($item);
            if ($this->file->isDirectory($item) && $basename !== 'unmapped' && !str_starts_with($basename, '.')) {
                $artists[] = $basename;
            }
        }

        return $artists;
    }

    /**
     * Get files for an artist
     */
    private function getFilesForArtist(string $metadataDir, string $artist): array
    {
        $artistDir = $metadataDir . '/' . $artist;
        $files = [];

        if (!$this->file->isDirectory($artistDir)) {
            return [];
        }

        $items = $this->file->readDirectory($artistDir);

        foreach ($items as $item) {
            $basename = basename($item);
            if ($this->file->isFile($item) && str_ends_with($basename, '.json') && $basename !== 'manifest.json') {
                $files[] = $item;
            }
        }

        return $files;
    }

    /**
     * Filter files based on cleanup strategy
     */
    private function filterFiles(array $files, ?string $olderThan, ?string $keepLatest): array
    {
        $candidates = [];

        // Strategy 1: Older than N days
        if ($olderThan !== null) {
            $cutoffTime = time() - ((int) $olderThan * 86400);

            foreach ($files as $file) {
                try {
                    $mtime = $this->file->stat($file)['mtime'] ?? 0;
                    if ($mtime < $cutoffTime) {
                        $candidates[] = $file;
                    }
                } catch (\Exception $e) {
                    // Skip files that can't be stat'd
                }
            }
        }

        // Strategy 2: Keep N latest
        if ($keepLatest !== null) {
            $keepCount = (int) $keepLatest;

            // Sort by modification time (newest first)
            usort($files, function ($a, $b) {
                try {
                    $mtimeA = $this->file->stat($a)['mtime'] ?? 0;
                    $mtimeB = $this->file->stat($b)['mtime'] ?? 0;
                    return $mtimeB <=> $mtimeA;
                } catch (\Exception $e) {
                    return 0;
                }
            });

            // Files beyond the keep count are candidates for deletion
            if (count($files) > $keepCount) {
                $candidates = array_merge($candidates, array_slice($files, $keepCount));
            }
        }

        return array_unique($candidates);
    }

    /**
     * Get product identifiers for an artist (to protect associated files)
     */
    private function getProductIdentifiers(string $artist): array
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('archive_collection', $artist, 'eq')
                ->create();

            $products = $this->productRepository->getList($searchCriteria);
            $identifiers = [];

            foreach ($products->getItems() as $product) {
                $identifier = $product->getData('identifier');
                if ($identifier) {
                    $identifiers[] = $identifier;
                }
            }

            return $identifiers;
        } catch (\Exception $e) {
            $this->logger->warning("Failed to load products for $artist", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
