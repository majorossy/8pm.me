<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

/**
 * CLI command to export artist data from PHP patches to YAML.
 *
 * This command analyzes existing PHP data patches and generates YAML
 * configuration files. Due to the complexity of parsing PHP code,
 * exported files may require manual review and completion.
 *
 * Usage:
 *   bin/magento archive:migrate:export --dry-run
 *   bin/magento archive:migrate:export
 */
class MigrateExportCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';
    private const YAML_DIR = 'src/app/code/ArchiveDotOrg/Core/config/artists';

    /**
     * Known artists from data patches
     */
    private const KNOWN_ARTISTS = [
        'STS9' => ['collection_id' => 'STS9', 'url_key' => 'sts9'],
        'Lettuce' => ['collection_id' => 'Lettuce', 'url_key' => 'lettuce'],
        'Phish' => ['collection_id' => 'Phish', 'url_key' => 'phish'],
        'Grateful Dead' => ['collection_id' => 'GratefulDead', 'url_key' => 'grateful-dead'],
        'Widespread Panic' => ['collection_id' => 'WidespreadPanic', 'url_key' => 'widespread-panic'],
        'String Cheese Incident' => ['collection_id' => 'StringCheeseIncident', 'url_key' => 'string-cheese-incident'],
        'Umphrey\'s McGee' => ['collection_id' => 'UmphreysMcGee', 'url_key' => 'umphreys-mcgee'],
    ];

    private int $filesCreated = 0;
    private int $filesSkipped = 0;

    /**
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:migrate:export')
            ->setDescription('Export artist data from PHP patches to YAML')
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Show what would be created without making changes'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);

        if ($dryRun) {
            $output->writeln('<comment>DRY RUN MODE - No files will be created</comment>');
            $output->writeln('');
        }

        $output->writeln('<info>Exporting artist configurations to YAML...</info>');
        $output->writeln('');

        try {
            $rootDir = $this->directoryList->getRoot();
            $yamlDir = $rootDir . '/' . self::YAML_DIR;

            // Ensure YAML directory exists
            if (!$dryRun && !is_dir($yamlDir)) {
                if (!mkdir($yamlDir, 0755, true)) {
                    $output->writeln('<error>Failed to create directory: ' . $yamlDir . '</error>');
                    return Command::FAILURE;
                }
                $output->writeln('<info>Created directory: ' . $yamlDir . '</info>');
            }

            // Export each known artist
            foreach (self::KNOWN_ARTISTS as $artistName => $artistData) {
                $this->exportArtist(
                    $artistName,
                    $artistData['collection_id'],
                    $artistData['url_key'],
                    $yamlDir,
                    $output,
                    $dryRun
                );
            }

            $output->writeln('');
            $output->writeln(str_repeat('=', 60));
            $output->writeln(sprintf(
                '<info>Created: %d files, Skipped: %d files</info>',
                $this->filesCreated,
                $this->filesSkipped
            ));

            if (!$dryRun) {
                $output->writeln('');
                $output->writeln('<comment>IMPORTANT: Generated YAML files are STUBS!</comment>');
                $output->writeln('You must manually add:');
                $output->writeln('  - Album definitions');
                $output->writeln('  - Track definitions with aliases');
                $output->writeln('  - Medley patterns (if applicable)');
                $output->writeln('');
                $output->writeln('Run validation: bin/magento archive:validate --all');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            $this->logger->error('Migration export failed', ['exception' => $e]);
            return Command::FAILURE;
        }
    }

    /**
     * Export a single artist to YAML.
     *
     * @param string $artistName
     * @param string $collectionId
     * @param string $urlKey
     * @param string $yamlDir
     * @param OutputInterface $output
     * @param bool $dryRun
     * @return void
     */
    private function exportArtist(
        string $artistName,
        string $collectionId,
        string $urlKey,
        string $yamlDir,
        OutputInterface $output,
        bool $dryRun
    ): void {
        $filename = $urlKey . '.yaml';
        $filepath = $yamlDir . '/' . $filename;

        // Check if file already exists
        if (file_exists($filepath)) {
            $this->filesSkipped++;
            $output->writeln(sprintf('<comment>⊘ Skipped (exists): %s</comment>', $filename));
            return;
        }

        if ($dryRun) {
            $output->writeln(sprintf('<info>+ Would create: %s</info>', $filename));
            return;
        }

        // Generate YAML content
        $yaml = $this->generateYamlStub($artistName, $collectionId, $urlKey);

        // Write file
        if (file_put_contents($filepath, $yaml) === false) {
            $output->writeln(sprintf('<error>✗ Failed to create: %s</error>', $filename));
            return;
        }

        $this->filesCreated++;
        $output->writeln(sprintf('<info>✓ Created: %s</info>', $filename));
    }

    /**
     * Generate YAML stub for an artist.
     *
     * @param string $artistName
     * @param string $collectionId
     * @param string $urlKey
     * @return string
     */
    private function generateYamlStub(string $artistName, string $collectionId, string $urlKey): string
    {
        $timestamp = date('Y-m-d H:i:s');

        return <<<YAML
# ========================================
# $artistName Configuration
# ========================================
#
# Generated: $timestamp
# Status: STUB - Requires manual completion
#
# TODO:
#   1. Add album definitions
#   2. Add track definitions with aliases
#   3. Add medley patterns (if applicable)
#   4. Run validation: bin/magento archive:validate $urlKey
#
# See template.yaml for structure reference.
# ========================================

# ========================================
# ARTIST SECTION
# ========================================
artist:
  name: "$artistName"
  collection_id: "$collectionId"
  url_key: "$urlKey"

# ========================================
# ALBUMS SECTION
# ========================================
# TODO: Add studio/live album definitions
# Example:
# albums:
#   - key: "album-key"
#     name: "Album Name"
#     url_key: "album-url-key"
#     year: 2002
#     type: "studio"
albums: []

# ========================================
# TRACKS SECTION
# ========================================
# TODO: Add track definitions with aliases
# Example:
# tracks:
#   - key: "track-key"
#     name: "Track Name"
#     url_key: "track-url-key"
#     albums: ["album-key"]
#     canonical_album: "album-key"
#     aliases: ["alternate-spelling"]
#     type: "original"
tracks: []

# ========================================
# MEDLEYS SECTION (Optional)
# ========================================
# TODO: Add common medley patterns if applicable
# Example:
# medleys:
#   - pattern: "Track A > Track B"
#     tracks: ["track-a-key", "track-b-key"]
#     separator: ">"
# medleys: []

YAML;
    }
}
