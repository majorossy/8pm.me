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
    private const YAML_DIR = 'app/code/ArchiveDotOrg/Core/config/artists';
    private const ARTIST_PATCH_FILES = [
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php',
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/CreateCategoryStructure.php',
    ];
    private const TRACK_PATCH_FILES = [
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup1.php',
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup2.php',
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup3.php',
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup4.php',
        'app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup5.php',
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
     * Dynamically extract artists and their albums from all artist data patch files.
     *
     * This method parses multiple PHP files to find artist names in CATEGORY_STRUCTURE constants,
     * auto-generates collection IDs and URL keys, and extracts album data.
     *
     * @param string $rootDir Root directory path
     * @return array<string, array{collection_id: string, url_key: string, albums: array}> Artist data
     */
    private function getArtistsFromDataPatch(string $rootDir): array
    {
        $allArtists = [];

        // Parse each artist patch file
        foreach (self::ARTIST_PATCH_FILES as $patchFile) {
            $dataPatchPath = $rootDir . '/' . $patchFile;

            if (!file_exists($dataPatchPath)) {
                continue; // Skip if file doesn't exist
            }

            $content = file_get_contents($dataPatchPath);

            // Extract the entire CATEGORY_STRUCTURE array
            preg_match('/private const CATEGORY_STRUCTURE = \[(.*?)\];/s', $content, $structureMatch);

            if (empty($structureMatch[1])) {
                continue; // Skip if no CATEGORY_STRUCTURE found
            }

            $structure = $structureMatch[1];

            // Split by artist entries (looking for lines like '        'Artist Name' => [')
            preg_match_all("/^        '([^']+)'\s+=>\s+\[(.*?)^        \]/ms", $structure . "\n        ]", $artistMatches, PREG_SET_ORDER);

            foreach ($artistMatches as $match) {
                $artistName = $match[1];
                $artistData = $match[2];

                // Skip if already processed (avoid duplicates)
                if (isset($allArtists[$artistName])) {
                    continue;
                }

                // Generate collection ID and URL key
                $collectionId = $this->generateCollectionId($artistName);
                $urlKey = $this->generateUrlKey($artistName);

                // Extract albums from this artist's data
                $albums = $this->extractAlbumsFromArtistData($artistData);

                $allArtists[$artistName] = [
                    'collection_id' => $collectionId,
                    'url_key' => $urlKey,
                    'albums' => $albums,
                ];
            }
        }

        if (empty($allArtists)) {
            throw new \RuntimeException('No artists found in any data patch files');
        }

        return $allArtists;
    }

    /**
     * Extract album data from an artist's data section.
     *
     * @param string $artistData The artist's data section from the PHP file
     * @return array Array of albums with name, url_key, and type
     */
    private function extractAlbumsFromArtistData(string $artistData): array
    {
        // Match album entries: ['Album Name', 'album-url-key']
        preg_match_all("/\['([^']+)',\s*'([^']+)'\]/", $artistData, $albumMatches, PREG_SET_ORDER);

        $albums = [];
        foreach ($albumMatches as $match) {
            // Strip backslashes from PHP-escaped strings (e.g., "It\'s Ice" → "It's Ice")
            $albumName = stripslashes($match[1]);
            $albumUrlKey = $match[2];

            // Generate a key for the album (kebab-case from name)
            $key = $this->generateUrlKey($albumName);

            $albums[] = [
                'key' => $key,
                'name' => $albumName,
                'url_key' => $albumUrlKey,
                'type' => 'studio', // Default type, can be manually updated
            ];
        }

        return $albums;
    }

    /**
     * Parse track data from all AddTracksGroup data patch files.
     *
     * @param string $rootDir Root directory path
     * @param OutputInterface $output Console output
     * @return array<string, array> Tracks organized by artist name
     */
    private function getTracksFromDataPatches(string $rootDir, OutputInterface $output): array
    {
        $allTracks = [];

        foreach (self::TRACK_PATCH_FILES as $patchFile) {
            $patchPath = $rootDir . '/' . $patchFile;

            if (!file_exists($patchPath)) {
                continue;
            }

            $content = file_get_contents($patchPath);

            // Extract the TRACKS constant: private const TRACKS = [...];
            preg_match('/private const TRACKS = \[(.*?)\];/s', $content, $tracksMatch);

            if (empty($tracksMatch[1])) {
                continue;
            }

            $tracksData = $tracksMatch[1];

            // Extract artist sections: 'Artist Name' => [...] (8 space indentation in file)
            // Pattern matches: '\n        'Artist' => [\n' up to '\n        ],'
            preg_match_all("/\n        '([^']+)'\s*=>\s*\[\s*\n(.*?)\n        \],?/s", $tracksData, $artistMatches, PREG_SET_ORDER);

            foreach ($artistMatches as $artistMatch) {
                $artistName = $artistMatch[1];
                $artistTracksData = $artistMatch[2];

                // Extract album sections within this artist: 'Album Name' => [...] (12 space indentation)
                // Pattern matches: '\n            'Album' => [\n' up to '\n            ],'
                preg_match_all("/\n            '([^']+)'\s*=>\s*\[\s*\n(.*?)\n            \],?/s", $artistTracksData, $albumMatches, PREG_SET_ORDER);

                $artistTracks = [];

                foreach ($albumMatches as $albumMatch) {
                    $albumName = $albumMatch[1];
                    $albumTracksData = $albumMatch[2];

                    // Extract individual tracks: ['Track Name', 'track-url-key'] (12 space indentation)
                    // Need to handle escaped quotes in track names like "I Haven\'t Seen Mary"
                    preg_match_all("/\['([^'\\\\]*(?:\\\\.[^'\\\\]*)*)',\s*'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'\]/", $albumTracksData, $trackMatches, PREG_SET_ORDER);

                    foreach ($trackMatches as $trackMatch) {
                        // Strip backslashes from PHP-escaped strings (e.g., "It\'s Ice" → "It's Ice")
                        $trackName = stripslashes($trackMatch[1]);
                        $trackUrlKey = $trackMatch[2];
                        $trackKey = $this->generateUrlKey($trackName);

                        // Find or create track entry
                        $existingTrackIndex = null;
                        foreach ($artistTracks as $index => $track) {
                            if ($track['key'] === $trackKey) {
                                $existingTrackIndex = $index;
                                break;
                            }
                        }

                        $albumKey = $this->generateUrlKey($albumName);

                        if ($existingTrackIndex !== null) {
                            // Track exists - add album to albums list
                            if (!in_array($albumKey, $artistTracks[$existingTrackIndex]['albums'])) {
                                $artistTracks[$existingTrackIndex]['albums'][] = $albumKey;
                            }
                        } else {
                            // New track
                            $artistTracks[] = [
                                'key' => $trackKey,
                                'name' => $trackName,
                                'url_key' => $trackUrlKey,
                                'albums' => [$albumKey],
                                'canonical_album' => $albumKey, // First album is canonical
                                'type' => 'original',
                            ];
                        }
                    }
                }

                // Merge with existing tracks for this artist
                if (!isset($allTracks[$artistName])) {
                    $allTracks[$artistName] = [];
                }
                $allTracks[$artistName] = array_merge($allTracks[$artistName], $artistTracks);
            }
        }

        return $allTracks;
    }

    /**
     * Generate Archive.org collection ID from artist name.
     *
     * Rules:
     * - Remove spaces
     * - Remove special characters (&, ., ', etc.)
     * - Keep camelCase
     *
     * Examples:
     * - "Grateful Dead" → "GratefulDead"
     * - "Umphrey's McGee" → "UmphreysMcGee"
     * - "moe." → "moe"
     * - "King Gizzard & The Lizard Wizard" → "KingGizzardAndTheLizardWizard"
     *
     * @param string $artistName Artist display name
     * @return string Collection ID
     */
    private function generateCollectionId(string $artistName): string
    {
        // Replace & with And
        $id = str_replace('&', 'And', $artistName);

        // Remove apostrophes, periods, and other punctuation
        $id = preg_replace("/['.]/", '', $id);

        // Remove spaces
        $id = str_replace(' ', '', $id);

        return $id;
    }

    /**
     * Generate URL key from artist name.
     *
     * Rules:
     * - Lowercase
     * - Replace spaces with hyphens
     * - Remove special characters
     *
     * Examples:
     * - "Grateful Dead" → "grateful-dead"
     * - "Umphrey's McGee" → "umphreys-mcgee"
     * - "moe." → "moe"
     * - "King Gizzard & The Lizard Wizard" → "king-gizzard-and-the-lizard-wizard"
     *
     * @param string $artistName Artist display name
     * @return string URL key
     */
    private function generateUrlKey(string $artistName): string
    {
        // Replace & with and
        $key = str_replace('&', 'and', $artistName);

        // Remove apostrophes, periods
        $key = preg_replace("/['.]/", '', $key);

        // Convert to lowercase
        $key = strtolower($key);

        // Replace spaces with hyphens
        $key = str_replace(' ', '-', $key);

        // Remove any remaining special characters
        $key = preg_replace('/[^a-z0-9-]/', '', $key);

        // Remove multiple consecutive hyphens
        $key = preg_replace('/-+/', '-', $key);

        // Trim hyphens from ends
        $key = trim($key, '-');

        return $key;
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

            // Dynamically discover artists from all data patch files
            $artists = $this->getArtistsFromDataPatch($rootDir);

            // Parse track data from track patch files (AddTracksGroup1-5.php)
            $tracksByArtist = $this->getTracksFromDataPatches($rootDir, $output);

            // Merge track data into artist data
            foreach ($artists as $artistName => &$artistData) {
                $artistData['tracks'] = $tracksByArtist[$artistName] ?? [];
            }
            unset($artistData);

            // Export each discovered artist
            foreach ($artists as $artistName => $artistData) {
                $this->exportArtist(
                    $artistName,
                    $artistData['collection_id'],
                    $artistData['url_key'],
                    $artistData['albums'] ?? [],
                    $artistData['tracks'] ?? [], // Tracks from AddTracksGroup files
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
        array $albums,
        array $tracks,
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
        $yaml = $this->generateYamlContent($artistName, $collectionId, $urlKey, $albums, $tracks);

        // Write file
        if (file_put_contents($filepath, $yaml) === false) {
            $output->writeln(sprintf('<error>✗ Failed to create: %s</error>', $filename));
            return;
        }

        $this->filesCreated++;
        $output->writeln(sprintf('<info>✓ Created: %s</info>', $filename));
    }

    /**
     * Generate YAML content for an artist with album and track data.
     *
     * @param string $artistName
     * @param string $collectionId
     * @param string $urlKey
     * @param array $albums Array of albums with key, name, url_key, type
     * @param array $tracks Array of tracks with key, name, url_key, albums, etc.
     * @return string
     */
    private function generateYamlContent(string $artistName, string $collectionId, string $urlKey, array $albums, array $tracks): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $albumCount = count($albums);
        $trackCount = count($tracks);

        // Generate albums YAML section
        $albumsYaml = '';
        if (empty($albums)) {
            $albumsYaml = 'albums: []';
        } else {
            $albumsYaml = "albums:\n";
            foreach ($albums as $album) {
                $albumsYaml .= "  - key: \"{$album['key']}\"\n";
                $albumsYaml .= "    name: \"{$album['name']}\"\n";
                $albumsYaml .= "    url_key: \"{$album['url_key']}\"\n";
                $albumsYaml .= "    type: \"{$album['type']}\"\n";
                $albumsYaml .= "    # TODO: Add year field\n\n";
            }
            $albumsYaml = rtrim($albumsYaml); // Remove trailing newline
        }

        // Generate tracks YAML section
        $tracksYaml = '';
        if (empty($tracks)) {
            $tracksYaml = 'tracks: []';
        } else {
            $tracksYaml = "tracks:\n";
            foreach ($tracks as $track) {
                $tracksYaml .= "  - key: \"{$track['key']}\"\n";
                $tracksYaml .= "    name: \"{$track['name']}\"\n";
                $tracksYaml .= "    url_key: \"{$track['url_key']}\"\n";
                if (!empty($track['albums'])) {
                    $albumsList = '["' . implode('", "', $track['albums']) . '"]';
                    $tracksYaml .= "    albums: {$albumsList}\n";
                }
                if (!empty($track['canonical_album'])) {
                    $tracksYaml .= "    canonical_album: \"{$track['canonical_album']}\"\n";
                }
                $tracksYaml .= "    type: \"{$track['type']}\"\n";
                $tracksYaml .= "    # TODO: Add aliases for better matching\n\n";
            }
            $tracksYaml = rtrim($tracksYaml); // Remove trailing newline
        }

        return <<<YAML
# ========================================
# $artistName Configuration
# ========================================
#
# Generated: $timestamp
# Albums Populated: $albumCount
# Tracks Populated: $trackCount
# Status: Ready for use
#
# TODO:
#   1. Add year to each album
#   2. Add track aliases for better matching
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
$albumsYaml

# ========================================
# TRACKS SECTION
# ========================================
$tracksYaml

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
