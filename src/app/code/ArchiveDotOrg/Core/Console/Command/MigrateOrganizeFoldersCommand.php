<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\LockServiceInterface;
use ArchiveDotOrg\Core\Exception\LockException;
use ArchiveDotOrg\Core\Logger\Logger;
use ArchiveDotOrg\Core\Model\FileManifestService;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Migrates flat metadata structure to organized artist-based folders
 *
 * Current: var/archivedotorg/metadata/*.json (flat)
 * Target:  var/archivedotorg/metadata/{Artist}/*.json (organized)
 *
 * Features:
 * - Automatic backup to metadata.backup/
 * - Crash-safe with progress tracking
 * - Quarantine unmappable files to /unmapped/
 * - Dry-run mode for preview
 */
class MigrateOrganizeFoldersCommand extends Command
{
    private const BACKUP_DIR = 'metadata.backup';
    private const UNMAPPED_DIR = 'unmapped';
    private const MIGRATION_STATE_FILE = 'archivedotorg/migration_state.json';

    /**
     * Known Archive.org identifier patterns
     * Maps lowercase prefix to artist collection ID
     */
    private const IDENTIFIER_PATTERNS = [
        'gd' => 'GratefulDead',
        'phish' => 'Phish',
        'sts9' => 'STS9',
        'sci' => 'StringCheeseIncident',
        'db' => 'DiscoBiscuits',
        'tdb' => 'DiscoBiscuits',
        'rre' => 'RailroadEarth',
        'um' => 'UmphreysMcGee',
        'wsp' => 'WidespreadPanic',
        'billystrings' => 'BillyStrings',
        'goose' => 'Goose',
        'moe' => 'moe',
        'jrad' => 'JRAD',
        'furthur' => 'Furthur',
        'kw' => 'KellerWilliams',
        'keller' => 'KellerWilliams',
        'los' => 'LeftoverSalmon',
        'ymsb' => 'YonderMountainStringBand',
        'yonder' => 'YonderMountainStringBand',
        'ttb' => 'TedeschiTrucksBand',
        'tlg' => 'TeaLeafGreen',
        'mmj' => 'MyMorningJacket',
        'twiddle' => 'Twiddle',
        'ratdog' => 'Ratdog',
        'plf' => 'PhilLeshandFriends',
    ];

    private DirectoryList $directoryList;
    private File $file;
    private Json $jsonSerializer;
    private Logger $logger;
    private FileManifestService $manifestService;
    private LockServiceInterface $lockService;
    private string $varDir;

    public function __construct(
        DirectoryList $directoryList,
        File $file,
        Json $jsonSerializer,
        Logger $logger,
        FileManifestService $manifestService,
        LockServiceInterface $lockService,
        string $name = null
    ) {
        parent::__construct($name);
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->manifestService = $manifestService;
        $this->lockService = $lockService;
        $this->varDir = $directoryList->getPath('var');
    }

    protected function configure(): void
    {
        $this->setName('archive:migrate:organize-folders');
        $this->setDescription('Migrate flat metadata structure to artist-based folders');
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Preview changes without actually moving files'
        );
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Skip backup and confirmation prompts'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $output->writeln('<info>Archive.org Metadata Folder Migration</info>');
        $output->writeln('');

        $metadataDir = $this->varDir . '/archivedotorg/metadata';
        $backupDir = $this->varDir . '/archivedotorg/' . self::BACKUP_DIR;
        $unmappedDir = $metadataDir . '/' . self::UNMAPPED_DIR;

        // Acquire lock (use 'global' as resource since this operates on all collections)
        try {
            $lockToken = $this->lockService->acquire('migrate', 'metadata', 300);
        } catch (LockException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        try {
                // Check if metadata directory exists
            if (!$this->file->isDirectory($metadataDir)) {
                $output->writeln('<comment>No metadata directory found. Nothing to migrate.</comment>');
                return Command::SUCCESS;
            }

            // Find all JSON files
            $files = $this->findJsonFiles($metadataDir);
            $totalFiles = count($files);

            if ($totalFiles === 0) {
                $output->writeln('<comment>No JSON files found in metadata directory.</comment>');
                return Command::SUCCESS;
            }

            $output->writeln("Found <info>$totalFiles</info> metadata files");

            // Analyze files for mapping
            $analysis = $this->analyzeFiles($files);
            $mappable = $analysis['mappable'];
            $unmappable = $analysis['unmappable'];

            $output->writeln('');
            $output->writeln('<info>Migration Plan:</info>');
            $output->writeln("  Mappable files:   {$mappable['count']}");
            $output->writeln("  Unmappable files: {$unmappable['count']}");

            if (!empty($mappable['by_artist'])) {
                $output->writeln('');
                $output->writeln('<info>Files by Artist:</info>');
                foreach ($mappable['by_artist'] as $artist => $count) {
                    $output->writeln("  $artist: $count");
                }
            }

            if ($unmappable['count'] > 0) {
                $output->writeln('');
                $output->writeln('<comment>Unmappable files will be quarantined to: ' . self::UNMAPPED_DIR . '/</comment>');
                if (count($unmappable['examples']) > 0) {
                    $output->writeln('  Examples:');
                    foreach (array_slice($unmappable['examples'], 0, 5) as $example) {
                        $output->writeln("    - $example");
                    }
                }
            }

            if ($dryRun) {
                $output->writeln('');
                $output->writeln('<comment>DRY RUN - No files will be moved</comment>');
                return Command::SUCCESS;
            }

            // Confirm migration
            if (!$force) {
                $output->writeln('');
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Proceed with migration? [y/N] ',
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln('<comment>Migration cancelled</comment>');
                    return Command::SUCCESS;
                }
            }

            // Create backup
            if (!$force) {
                $output->writeln('');
                $output->writeln('Creating backup...');
                $this->createBackup($metadataDir, $backupDir);
                $output->writeln("  Backup created at: $backupDir");
            }

            // Load migration state
            $state = $this->loadMigrationState();

            // Migrate files
            $output->writeln('');
            $output->writeln('Migrating files...');

            $migrated = 0;
            $quarantined = 0;
            $skipped = 0;

            foreach ($files as $file) {
                $basename = basename($file);

                // Skip if already processed
                if (isset($state['processed'][$basename])) {
                    $skipped++;
                    continue;
                }

                $artist = $this->mapFileToArtist($basename);

                try {
                    if ($artist !== null) {
                        // Move to artist folder
                        $targetDir = $metadataDir . '/' . $artist;
                        $this->ensureDirectory($targetDir);
                        $targetPath = $targetDir . '/' . $basename;
                        $this->file->rename($file, $targetPath);
                        $migrated++;

                        // Update manifest
                        $this->manifestService->addFile($artist, $basename, filesize($targetPath));
                    } else {
                        // Quarantine unmappable file
                        $this->ensureDirectory($unmappedDir);
                        $targetPath = $unmappedDir . '/' . $basename;
                        $this->file->rename($file, $targetPath);
                        $quarantined++;
                    }

                    // Update state
                    $state['processed'][$basename] = [
                        'timestamp' => date('c'),
                        'artist' => $artist,
                        'quarantined' => $artist === null,
                    ];
                    $this->saveMigrationState($state);

                    // Progress output
                    $total = $migrated + $quarantined + $skipped;
                    if ($total % 50 === 0) {
                        $output->writeln("  Processed: $total / $totalFiles");
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Failed to migrate file: $basename", [
                        'error' => $e->getMessage(),
                    ]);
                    $output->writeln("  <error>Failed: $basename - {$e->getMessage()}</error>");
                }
            }

            $output->writeln('');
            $output->writeln('<info>Migration Complete!</info>');
            $output->writeln("  Migrated:     $migrated");
            $output->writeln("  Quarantined:  $quarantined");
            $output->writeln("  Skipped:      $skipped");

            if ($quarantined > 0) {
                $output->writeln('');
                $output->writeln("<comment>Review quarantined files in: $unmappedDir</comment>");
            }

            // Clean up migration state
            $this->file->deleteFile($this->varDir . '/' . self::MIGRATION_STATE_FILE);

            $this->logger->info('Folder migration complete', [
                'migrated' => $migrated,
                'quarantined' => $quarantined,
                'skipped' => $skipped,
            ]);

            return Command::SUCCESS;

        } finally {
            // Always release lock
            try {
                $this->lockService->release($lockToken);
            } catch (\Exception $e) {
                $this->logger->error('Failed to release lock', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Find all JSON files recursively (excluding subdirectories)
     */
    private function findJsonFiles(string $directory): array
    {
        $files = [];
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            // Only include JSON files in the root metadata directory (flat structure)
            if ($this->file->isFile($path) && pathinfo($path, PATHINFO_EXTENSION) === 'json') {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Analyze files for artist mapping
     */
    private function analyzeFiles(array $files): array
    {
        $mappable = ['count' => 0, 'by_artist' => []];
        $unmappable = ['count' => 0, 'examples' => []];

        foreach ($files as $file) {
            $basename = basename($file);
            $artist = $this->mapFileToArtist($basename);

            if ($artist !== null) {
                $mappable['count']++;
                $mappable['by_artist'][$artist] = ($mappable['by_artist'][$artist] ?? 0) + 1;
            } else {
                $unmappable['count']++;
                $unmappable['examples'][] = $basename;
            }
        }

        return [
            'mappable' => $mappable,
            'unmappable' => $unmappable,
        ];
    }

    /**
     * Map filename to artist collection ID
     *
     * Uses identifier patterns to determine which artist a file belongs to.
     * Returns null if file cannot be mapped (will be quarantined).
     */
    private function mapFileToArtist(string $filename): ?string
    {
        $filename = basename($filename, '.json');
        $filenameLower = strtolower($filename);

        // Try pattern matching
        foreach (self::IDENTIFIER_PATTERNS as $pattern => $artist) {
            if (strpos($filenameLower, $pattern) === 0) {
                return $artist;
            }
        }

        return null;
    }

    /**
     * Create backup of flat structure
     */
    private function createBackup(string $source, string $destination): void
    {
        if ($this->file->isDirectory($destination)) {
            // Backup already exists, create timestamped version
            $timestamp = date('Ymd_His');
            $destination .= '_' . $timestamp;
        }

        $this->ensureDirectory($destination);

        // Copy all JSON files
        $files = $this->findJsonFiles($source);
        foreach ($files as $file) {
            $basename = basename($file);
            $this->file->copy($file, $destination . '/' . $basename);
        }
    }

    /**
     * Load migration state from file
     */
    private function loadMigrationState(): array
    {
        $statePath = $this->varDir . '/' . self::MIGRATION_STATE_FILE;

        if (!$this->file->isFile($statePath)) {
            return [
                'started_at' => date('c'),
                'processed' => [],
            ];
        }

        try {
            $content = $this->file->fileGetContents($statePath);
            return $this->jsonSerializer->unserialize($content);
        } catch (\Exception $e) {
            $this->logger->warning('Migration state file corrupted, starting fresh');
            return [
                'started_at' => date('c'),
                'processed' => [],
            ];
        }
    }

    /**
     * Save migration state to file
     */
    private function saveMigrationState(array $state): void
    {
        $statePath = $this->varDir . '/' . self::MIGRATION_STATE_FILE;
        $dir = dirname($statePath);

        if (!$this->file->isDirectory($dir)) {
            $this->file->createDirectory($dir, 0755);
        }

        $content = $this->jsonSerializer->serialize($state);
        $this->file->filePutContents($statePath, $content);
    }

    /**
     * Ensure directory exists
     */
    private function ensureDirectory(string $path): void
    {
        if (!$this->file->isDirectory($path)) {
            $this->file->createDirectory($path, 0755);
        }
    }
}
