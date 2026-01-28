<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\MetadataDownloaderInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Downloads and caches show metadata from Archive.org
 *
 * Features:
 * - Best version selection (SBD > rating > reviews > downloads)
 * - Local JSON caching for offline processing
 * - Progress tracking and resumability
 * - Incremental updates
 */
class MetadataDownloader implements MetadataDownloaderInterface
{
    private const CACHE_DIR = 'archivedotorg/metadata';
    private const PROGRESS_FILE = 'archivedotorg/download_progress.json';
    private const DELAY_MS = 750;
    private const BACKOFF_SEC = 60;

    /**
     * Default collection mappings (used when admin config is empty)
     */
    private const DEFAULT_COLLECTIONS = [
        'BillyStrings' => ['artist_name' => 'Billy Strings', 'identifier_pattern' => 'billystrings'],
        'DiscoBiscuits' => ['artist_name' => 'Disco Biscuits', 'identifier_pattern' => 'db'],
        'Furthur' => ['artist_name' => 'Furthur', 'identifier_pattern' => 'furthur'],
        'Goose' => ['artist_name' => 'Goose', 'identifier_pattern' => 'goose'],
        'GratefulDead' => ['artist_name' => 'Grateful Dead', 'identifier_pattern' => 'gd'],
        'JRAD' => ['artist_name' => "Joe Russo's Almost Dead", 'identifier_pattern' => 'jrad'],
        'KellerWilliams' => ['artist_name' => 'Keller Williams', 'identifier_pattern' => 'kw'],
        'LeftoverSalmon' => ['artist_name' => 'Leftover Salmon', 'identifier_pattern' => 'los'],
        'moe' => ['artist_name' => 'moe.', 'identifier_pattern' => 'moe'],
        'MyMorningJacket' => ['artist_name' => 'My Morning Jacket', 'identifier_pattern' => 'mmj'],
        'PhilLeshandFriends' => ['artist_name' => 'Phil Lesh & Friends', 'identifier_pattern' => 'plf'],
        'Phish' => ['artist_name' => 'Phish', 'identifier_pattern' => 'phish'],
        'RailroadEarth' => ['artist_name' => 'Railroad Earth', 'identifier_pattern' => 'rre'],
        'Ratdog' => ['artist_name' => 'Ratdog', 'identifier_pattern' => 'ratdog'],
        'StringCheeseIncident' => ['artist_name' => 'String Cheese Incident', 'identifier_pattern' => 'sci'],
        'STS9' => ['artist_name' => 'STS9', 'identifier_pattern' => 'sts9'],
        'TeaLeafGreen' => ['artist_name' => 'Tea Leaf Green', 'identifier_pattern' => 'tlg'],
        'TedeschiTrucksBand' => ['artist_name' => 'Tedeschi Trucks Band', 'identifier_pattern' => 'ttb'],
        'Twiddle' => ['artist_name' => 'Twiddle', 'identifier_pattern' => 'twiddle'],
        'UmphreysMcGee' => ['artist_name' => "Umphrey's McGee", 'identifier_pattern' => 'um'],
        'WidespreadPanic' => ['artist_name' => 'Widespread Panic', 'identifier_pattern' => 'wsp'],
        'YonderMountainStringBand' => ['artist_name' => 'Yonder Mountain String Band', 'identifier_pattern' => 'ymsb'],
    ];

    private Config $config;
    private Curl $httpClient;
    private Json $jsonSerializer;
    private Logger $logger;
    private DirectoryList $directoryList;
    private string $varDir;

    public function __construct(
        Config $config,
        Curl $httpClient,
        Json $jsonSerializer,
        Logger $logger,
        DirectoryList $directoryList
    ) {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->varDir = $directoryList->getPath('var');
    }

    /**
     * @inheritDoc
     */
    public function download(
        string $collectionId,
        ?int $limit = null,
        bool $force = false,
        bool $incremental = false,
        ?string $since = null,
        ?callable $progressCallback = null
    ): array {
        $this->ensureCacheDir();
        $progress = $this->loadProgress();

        // Check if already completed and not forcing
        if (!$force && !$incremental && isset($progress[$collectionId]['status'])
            && $progress[$collectionId]['status'] === 'completed') {
            $this->log($progressCallback, "Collection $collectionId already completed. Use --force to re-download.");
            return $progress[$collectionId];
        }

        $this->log($progressCallback, "Fetching show list from Archive.org for collection: $collectionId");

        // Fetch all recordings with quality fields
        $searchResults = $this->fetchCollectionWithQualityFields($collectionId, $incremental, $since);
        $totalRecordings = count($searchResults);

        $this->log($progressCallback, "Found $totalRecordings total recordings");

        // Apply best version selection
        $winners = $this->selectBestRecordings($searchResults);
        $uniqueShows = count($winners);

        $this->log($progressCallback, "Selected $uniqueShows unique shows (best version per date)");

        // Get already cached identifiers
        $cached = $force ? [] : $this->getDownloadedIdentifiers($collectionId);
        $cachedCount = count($cached);

        // Filter to only download what's needed
        $toDownload = array_diff($winners, $cached);
        $toDownloadCount = count($toDownload);

        if ($limit !== null && $limit > 0) {
            $toDownload = array_slice($toDownload, 0, $limit);
            $toDownloadCount = count($toDownload);
        }

        $this->log($progressCallback, "Already cached: $cachedCount | To download: $toDownloadCount");

        // Update progress to in_progress
        $progress[$collectionId] = [
            'status' => 'in_progress',
            'started_at' => $progress[$collectionId]['started_at'] ?? date('c'),
            'last_updated' => date('c'),
            'total_recordings' => $totalRecordings,
            'unique_shows' => $uniqueShows,
            'downloaded' => $cachedCount,
            'failed' => 0,
            'failed_identifiers' => [],
        ];
        $this->saveProgress($progress);

        // Download metadata for each winner
        $downloaded = 0;
        $failed = 0;
        $failedIdentifiers = [];

        foreach ($toDownload as $index => $identifier) {
            $current = $index + 1;

            try {
                $metadata = $this->fetchShowMetadata($identifier);
                $this->saveMetadataToCache($identifier, $metadata);
                $downloaded++;

                // Update progress after each successful download (crash-safe)
                $progress[$collectionId]['downloaded'] = $cachedCount + $downloaded;
                $progress[$collectionId]['last_updated'] = date('c');
                $progress[$collectionId]['last_identifier'] = $identifier;
                $this->saveProgress($progress);

                $this->log($progressCallback, "[$current/$toDownloadCount] Downloaded: $identifier");

                // Rate limiting
                usleep(self::DELAY_MS * 1000);

            } catch (LocalizedException $e) {
                $failed++;
                $failedIdentifiers[] = $identifier;
                $progress[$collectionId]['failed'] = $failed;
                $progress[$collectionId]['failed_identifiers'] = $failedIdentifiers;
                $this->saveProgress($progress);

                $this->logger->logApiError($identifier, $e->getMessage());
                $this->log($progressCallback, "[$current/$toDownloadCount] FAILED: $identifier - " . $e->getMessage());

                // Check if we hit rate limit
                if (strpos($e->getMessage(), '429') !== false) {
                    $this->log($progressCallback, "Rate limited! Waiting " . self::BACKOFF_SEC . " seconds...");
                    sleep(self::BACKOFF_SEC);
                }
            }
        }

        // Mark as completed
        $progress[$collectionId]['status'] = $failed > 0 && $downloaded === 0 ? 'failed' : 'completed';
        $progress[$collectionId]['completed_at'] = date('c');
        $progress[$collectionId]['last_full_sync'] = $incremental
            ? ($progress[$collectionId]['last_full_sync'] ?? date('c'))
            : date('c');
        if ($incremental) {
            $progress[$collectionId]['last_incremental'] = date('c');
        }
        $this->saveProgress($progress);

        $this->log($progressCallback, "Download complete: $downloaded downloaded, $failed failed");

        return [
            'total_recordings' => $totalRecordings,
            'unique_shows' => $uniqueShows,
            'downloaded' => $downloaded,
            'cached' => $cachedCount,
            'failed' => $failed,
            'failed_identifiers' => $failedIdentifiers,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getProgress(string $collectionId): ?array
    {
        $progress = $this->loadProgress();
        return $progress[$collectionId] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getCachedMetadata(string $identifier): ?array
    {
        $path = $this->getCacheFilePath($identifier);

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        try {
            return $this->jsonSerializer->unserialize($content);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to parse cached metadata: $identifier", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function isCached(string $identifier): bool
    {
        return file_exists($this->getCacheFilePath($identifier));
    }

    /**
     * @inheritDoc
     */
    public function getDownloadedIdentifiers(string $collectionId): array
    {
        $cacheDir = $this->varDir . '/' . self::CACHE_DIR;

        if (!is_dir($cacheDir)) {
            return [];
        }

        // First, try to get identifiers from the progress file (most reliable)
        $progress = $this->getProgress($collectionId);
        if ($progress !== null && isset($progress['downloaded_identifiers']) && !empty($progress['downloaded_identifiers'])) {
            return $progress['downloaded_identifiers'];
        }

        // Fall back to scanning directory with Archive.org identifier patterns
        // Archive.org identifiers typically start with shorthand: gd, sts9, phish, etc.
        // Try multiple patterns based on common naming conventions
        $searchPatterns = $this->getSearchPatterns($collectionId);

        $identifiers = [];
        foreach ($searchPatterns as $pattern) {
            $files = glob($cacheDir . '/' . $pattern . '*.json');
            if ($files !== false) {
                foreach ($files as $file) {
                    $identifiers[] = basename($file, '.json');
                }
            }
        }

        return array_unique($identifiers);
    }

    /**
     * Get search patterns for finding cached files
     * Archive.org uses different naming conventions than collection names
     * Returns patterns for glob - case insensitive via [Aa][Bb] style
     */
    private function getSearchPatterns(string $collectionId): array
    {
        // Map collection IDs to their Archive.org identifier prefixes
        // These need to match the start of filenames (case-insensitive)
        $knownPrefixes = [
            'SoundTribeSector9' => ['sts9', 'STS9'],
            'StringCheeseIncident' => ['sci', 'SCI', 'stringcheese'],
            'DiscoBiscuits' => ['db', 'tdb', 'discobiscuits'],
            'RailroadEarth' => ['rre', 'RRE', 'railroadearth'],
            'TeaLeafGreen' => ['tlg', 'TLG', 'tealeafgreen'],
            'GratefulDead' => ['gd', 'GD'],
            'Phish' => ['phish', 'Phish'],
            'UmphreysMcGee' => ['um', 'UM'],
            'WidespreadPanic' => ['wsp', 'WSP'],
            'BillyStrings' => ['billystrings', 'BillyStrings'],
            'Goose' => ['goose', 'Goose'],
            'moe' => ['moe', 'MOE'],
            'JRAD' => ['jrad', 'JRAD'],
            'Furthur' => ['furthur', 'Furthur'],
            'KellerWilliams' => ['kw', 'KW', 'keller'],
            'LeftoverSalmon' => ['los', 'LOS', 'leftover'],
            'YonderMountainStringBand' => ['ymsb', 'YMSB', 'yonder'],
            'TedeschiTrucksBand' => ['ttb', 'TTB', 'tedeschi'],
            'GracePotterandtheNocturnals' => ['gp', 'GP', 'gpn', 'GPN', 'gptn', 'GPTN', 'grace', 'Grace'],
            'OfARevolution' => ['oar', 'OAR', 'ofar'],
            'MyMorningJacket' => ['mmj', 'MMJ', 'mymorningjacket', 'MyMorningJacket', 'JimJames'],
            'Twiddle' => ['twiddle', 'Twiddle', 'tw', 'TW'],
        ];

        if (isset($knownPrefixes[$collectionId])) {
            return $knownPrefixes[$collectionId];
        }

        // Fall back to lowercase collection name and capitalized version
        $lower = strtolower($collectionId);
        return [$lower, ucfirst($lower)];
    }

    /**
     * @inheritDoc
     */
    public function selectBestRecordings(array $searchResults): array
    {
        // Group by date (YYYY-MM-DD)
        $byDate = [];
        foreach ($searchResults as $item) {
            $date = $this->extractDate($item);
            if ($date) {
                $byDate[$date][] = $item;
            }
        }

        // Pick best per date
        $winners = [];
        foreach ($byDate as $date => $recordings) {
            usort($recordings, [$this, 'compareRecordingQuality']);
            $winners[] = $recordings[0]['identifier'];
        }

        return $winners;
    }

    /**
     * @inheritDoc
     */
    public function retryFailed(string $collectionId, ?callable $progressCallback = null): array
    {
        $progress = $this->loadProgress();
        $failedIdentifiers = $progress[$collectionId]['failed_identifiers'] ?? [];

        if (empty($failedIdentifiers)) {
            $this->log($progressCallback, "No failed identifiers to retry for $collectionId");
            return ['downloaded' => 0, 'still_failed' => 0];
        }

        $this->log($progressCallback, "Retrying " . count($failedIdentifiers) . " failed downloads...");

        $downloaded = 0;
        $stillFailed = [];

        foreach ($failedIdentifiers as $index => $identifier) {
            $current = $index + 1;
            $total = count($failedIdentifiers);

            try {
                $metadata = $this->fetchShowMetadata($identifier);
                $this->saveMetadataToCache($identifier, $metadata);
                $downloaded++;

                $this->log($progressCallback, "[$current/$total] Retry succeeded: $identifier");
                usleep(self::DELAY_MS * 1000);

            } catch (LocalizedException $e) {
                $stillFailed[] = $identifier;
                $this->log($progressCallback, "[$current/$total] Retry failed: $identifier");
            }
        }

        // Update progress
        $progress[$collectionId]['failed'] = count($stillFailed);
        $progress[$collectionId]['failed_identifiers'] = $stillFailed;
        $progress[$collectionId]['downloaded'] = ($progress[$collectionId]['downloaded'] ?? 0) + $downloaded;
        $this->saveProgress($progress);

        return ['downloaded' => $downloaded, 'still_failed' => count($stillFailed)];
    }

    /**
     * @inheritDoc
     */
    public function getAllCollections(): array
    {
        // First try admin config
        $mappings = $this->config->getArtistMappings();

        if (!empty($mappings)) {
            $collections = [];
            foreach ($mappings as $mapping) {
                if (isset($mapping['collection_id'])) {
                    $collections[$mapping['collection_id']] = [
                        'artist_name' => $mapping['artist_name'] ?? $mapping['collection_id'],
                        'identifier_pattern' => $mapping['identifier_pattern']
                            ?? strtolower($mapping['collection_id']),
                    ];
                }
            }
            return $collections;
        }

        // Fall back to defaults
        return self::DEFAULT_COLLECTIONS;
    }

    /**
     * Fetch collection with quality fields for best version selection
     */
    private function fetchCollectionWithQualityFields(
        string $collectionId,
        bool $incremental = false,
        ?string $since = null
    ): array {
        $query = "collection:$collectionId";

        // Add date filter for incremental updates
        if ($incremental || $since !== null) {
            $sinceDate = $since;
            if ($sinceDate === null) {
                $progress = $this->getProgress($collectionId);
                $sinceDate = $progress['last_full_sync'] ?? $progress['last_incremental'] ?? null;
            }
            if ($sinceDate !== null) {
                // Extract just the date portion
                $sinceDate = substr($sinceDate, 0, 10);
                $query .= " AND publicdate:[$sinceDate TO *]";
            }
        }

        $allResults = [];
        $page = 1;
        $rows = 1000;

        do {
            $url = sprintf(
                '%s/advancedsearch.php?q=%s&fl[]=identifier&fl[]=date&fl[]=avg_rating&fl[]=num_reviews&fl[]=downloads&rows=%d&page=%d&output=json',
                $this->config->getBaseUrl(),
                urlencode($query),
                $rows,
                $page
            );

            $response = $this->executeRequest($url);
            $data = $this->jsonSerializer->unserialize($response);

            $docs = $data['response']['docs'] ?? [];
            $numFound = $data['response']['numFound'] ?? 0;

            $allResults = array_merge($allResults, $docs);
            $page++;

            // Small delay between pagination requests
            if (!empty($docs) && count($allResults) < $numFound) {
                usleep(100000); // 100ms
            }

        } while (count($allResults) < $numFound && !empty($docs));

        return $allResults;
    }

    /**
     * Fetch full metadata for a single show
     */
    private function fetchShowMetadata(string $identifier): array
    {
        $url = $this->config->buildMetadataUrl($identifier);
        $response = $this->executeRequest($url);
        return $this->jsonSerializer->unserialize($response);
    }

    /**
     * Execute HTTP request with retry logic
     */
    private function executeRequest(string $url): string
    {
        $attempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $this->httpClient->setTimeout($this->config->getTimeout());
                $this->httpClient->setOption(CURLOPT_FOLLOWLOCATION, true);
                $this->httpClient->setHeaders([
                    'User-Agent' => 'ArchiveDotOrg-Magento/2.0',
                    'Accept' => 'application/json'
                ]);

                $this->httpClient->get($url);
                $status = $this->httpClient->getStatus();

                if ($status === 200) {
                    return $this->httpClient->getBody();
                }

                if ($status === 429) {
                    throw new LocalizedException(__('Rate limited (HTTP 429)'));
                }

                if ($status >= 500) {
                    throw new LocalizedException(__('Server error: HTTP %1', $status));
                }

                throw new LocalizedException(__('API error: HTTP %1', $status));

            } catch (\Exception $e) {
                $lastException = $e instanceof LocalizedException
                    ? $e
                    : new LocalizedException(__('Request failed: %1', $e->getMessage()), $e);

                if ($attempt < $attempts) {
                    usleep(1000000 * $attempt); // Exponential backoff
                }
            }
        }

        throw $lastException;
    }

    /**
     * Compare two recordings by quality (for usort)
     * Returns negative if $a is better, positive if $b is better
     */
    private function compareRecordingQuality(array $a, array $b): int
    {
        // SBD recordings win (soundboard = highest quality)
        $aIsSbd = stripos($a['identifier'] ?? '', 'sbd') !== false;
        $bIsSbd = stripos($b['identifier'] ?? '', 'sbd') !== false;
        if ($aIsSbd !== $bIsSbd) {
            return $bIsSbd <=> $aIsSbd;
        }

        // Then by average rating (higher is better)
        $aRating = (float)($a['avg_rating'] ?? 0);
        $bRating = (float)($b['avg_rating'] ?? 0);
        if ($aRating != $bRating) {
            return $bRating <=> $aRating;
        }

        // Then by number of reviews (more = more trusted)
        $aReviews = (int)($a['num_reviews'] ?? 0);
        $bReviews = (int)($b['num_reviews'] ?? 0);
        if ($aReviews != $bReviews) {
            return $bReviews <=> $aReviews;
        }

        // Finally by downloads (popularity fallback)
        return (int)($b['downloads'] ?? 0) <=> (int)($a['downloads'] ?? 0);
    }

    /**
     * Extract date from search result item
     */
    private function extractDate(array $item): ?string
    {
        $date = $item['date'] ?? null;
        if ($date === null) {
            return null;
        }

        // Handle array format
        if (is_array($date)) {
            $date = $date[0] ?? null;
        }

        if ($date === null) {
            return null;
        }

        // Extract YYYY-MM-DD
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$date, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get identifier pattern for a collection
     */
    private function getIdentifierPattern(string $collectionId): string
    {
        $collections = $this->getAllCollections();
        return $collections[$collectionId]['identifier_pattern'] ?? strtolower($collectionId);
    }

    /**
     * Get cache file path for an identifier
     */
    private function getCacheFilePath(string $identifier): string
    {
        return $this->varDir . '/' . self::CACHE_DIR . '/' . $identifier . '.json';
    }

    /**
     * Save metadata to cache file
     */
    private function saveMetadataToCache(string $identifier, array $metadata): void
    {
        $path = $this->getCacheFilePath($identifier);
        $content = $this->jsonSerializer->serialize($metadata);
        file_put_contents($path, $content);
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDir(): void
    {
        $cacheDir = $this->varDir . '/' . self::CACHE_DIR;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    /**
     * Load progress file
     */
    private function loadProgress(): array
    {
        $path = $this->varDir . '/' . self::PROGRESS_FILE;

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        try {
            return $this->jsonSerializer->unserialize($content) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Save progress file
     */
    private function saveProgress(array $progress): void
    {
        $dir = dirname($this->varDir . '/' . self::PROGRESS_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->jsonSerializer->serialize($progress);
        file_put_contents($this->varDir . '/' . self::PROGRESS_FILE, $content);
    }

    /**
     * Log message via callback
     */
    private function log(?callable $callback, string $message): void
    {
        if ($callback !== null) {
            $callback($message);
        }
        $this->logger->debug($message);
    }
}
