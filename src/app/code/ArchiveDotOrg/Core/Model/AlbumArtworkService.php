<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\AlbumArtworkServiceInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Album artwork service using MusicBrainz Cover Art Archive
 */
class AlbumArtworkService implements AlbumArtworkServiceInterface
{
    private const CACHE_DIR = 'archivedotorg/artwork';

    private MusicBrainzClient $musicBrainzClient;
    private Logger $logger;
    private DirectoryList $directoryList;
    private string $varDir;

    public function __construct(
        MusicBrainzClient $musicBrainzClient,
        Logger $logger,
        DirectoryList $directoryList
    ) {
        $this->musicBrainzClient = $musicBrainzClient;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->varDir = $directoryList->getPath('var');
    }

    /**
     * @inheritDoc
     */
    public function getArtworkUrl(string $artistName, string $albumTitle, int $size = 500): ?string
    {
        // Search for the album
        $releases = $this->musicBrainzClient->searchReleases($artistName, $albumTitle);

        if (empty($releases)) {
            $this->logger->debug("No MusicBrainz releases found for: $artistName - $albumTitle");
            return null;
        }

        // Try each release until we find artwork
        foreach ($releases as $release) {
            $mbid = $release['id'] ?? null;
            if ($mbid === null) {
                continue;
            }

            $artworkUrl = $this->musicBrainzClient->getArtworkUrl($mbid, $size);
            if ($artworkUrl !== null) {
                $this->logger->debug("Found artwork for $artistName - $albumTitle: $artworkUrl");
                return $artworkUrl;
            }
        }

        $this->logger->debug("No artwork available for: $artistName - $albumTitle");
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getArtistAlbums(string $artistName, int $limit = 50): array
    {
        $releases = $this->musicBrainzClient->getArtistReleases($artistName, $limit);
        $albums = [];

        foreach ($releases as $release) {
            $mbid = $release['id'] ?? null;
            $title = $release['title'] ?? null;
            $date = $release['date'] ?? null;

            if ($mbid === null || $title === null) {
                continue;
            }

            // Extract year from date
            $year = null;
            if ($date !== null && preg_match('/^(\d{4})/', $date, $matches)) {
                $year = (int)$matches[1];
            }

            // Try to get artwork URL
            $artworkUrl = $this->musicBrainzClient->getArtworkUrl($mbid, 500);

            $albums[] = [
                'title' => $title,
                'year' => $year,
                'artwork_url' => $artworkUrl,
                'musicbrainz_id' => $mbid,
                'release_date' => $date
            ];
        }

        // Filter to only albums with artwork
        $albums = array_filter($albums, fn($album) => $album['artwork_url'] !== null);

        // Sort by year descending
        usort($albums, fn($a, $b) => ($b['year'] ?? 0) <=> ($a['year'] ?? 0));

        return $albums;
    }

    /**
     * @inheritDoc
     */
    public function downloadArtwork(string $artistName, string $albumTitle): ?string
    {
        $this->ensureCacheDir();

        // Check if already cached
        $cachedPath = $this->getCacheFilePath($artistName, $albumTitle);
        if (file_exists($cachedPath)) {
            return $cachedPath;
        }

        // Search for the album
        $releases = $this->musicBrainzClient->searchReleases($artistName, $albumTitle);

        if (empty($releases)) {
            return null;
        }

        // Try each release until we download artwork
        foreach ($releases as $release) {
            $mbid = $release['id'] ?? null;
            if ($mbid === null) {
                continue;
            }

            $imageData = $this->musicBrainzClient->downloadArtwork($mbid, 500);
            if ($imageData !== null) {
                file_put_contents($cachedPath, $imageData);
                $this->logger->info("Downloaded artwork for $artistName - $albumTitle to $cachedPath");
                return $cachedPath;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function isCached(string $artistName, string $albumTitle): bool
    {
        return file_exists($this->getCacheFilePath($artistName, $albumTitle));
    }

    /**
     * Get cache file path for an album
     */
    private function getCacheFilePath(string $artistName, string $albumTitle): string
    {
        $filename = $this->sanitizeFilename($artistName . '_' . $albumTitle) . '.jpg';
        return $this->varDir . '/' . self::CACHE_DIR . '/' . $filename;
    }

    /**
     * Sanitize filename for safe filesystem usage
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove special characters, keep alphanumeric, dash, underscore
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Remove consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Trim underscores from edges
        return trim($filename, '_');
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
}
