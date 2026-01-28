<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Service for fetching studio album artwork
 */
interface AlbumArtworkServiceInterface
{
    /**
     * Get album artwork URL for an artist and album
     *
     * @param string $artistName Artist name (e.g., "Grateful Dead")
     * @param string $albumTitle Album title (e.g., "American Beauty")
     * @param int $size Image size in pixels (250, 500, or 1200)
     * @return string|null Artwork URL or null if not found
     */
    public function getArtworkUrl(string $artistName, string $albumTitle, int $size = 500): ?string;

    /**
     * Get all studio albums for an artist with artwork
     *
     * @param string $artistName Artist name
     * @param int $limit Maximum number of albums to return
     * @return array Array of albums with keys: title, year, artwork_url, musicbrainz_id
     */
    public function getArtistAlbums(string $artistName, int $limit = 50): array;

    /**
     * Download and cache album artwork locally
     *
     * @param string $artistName Artist name
     * @param string $albumTitle Album title
     * @return string|null Local file path or null if download failed
     */
    public function downloadArtwork(string $artistName, string $albumTitle): ?string;

    /**
     * Check if artwork is cached
     *
     * @param string $artistName Artist name
     * @param string $albumTitle Album title
     * @return bool
     */
    public function isCached(string $artistName, string $albumTitle): bool;
}
