<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use ArchiveDotOrg\Core\Logger\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Client for MusicBrainz API and Cover Art Archive
 *
 * MusicBrainz API: https://musicbrainz.org/doc/MusicBrainz_API
 * Cover Art Archive: https://coverartarchive.org/
 */
class MusicBrainzClient
{
    private const MUSICBRAINZ_API_BASE = 'https://musicbrainz.org/ws/2';
    private const COVERART_API_BASE = 'https://coverartarchive.org';
    private const RATE_LIMIT_DELAY_MS = 1000; // MusicBrainz requires 1 req/sec
    private const USER_AGENT = 'ArchiveDotOrg-Magento/2.0 (chris.majorossy@example.com)';

    private Json $jsonSerializer;
    private Logger $logger;
    private int $lastRequestTime = 0;
    private GuzzleClient $httpClient;

    public function __construct(
        Json $jsonSerializer,
        Logger $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->httpClient = new GuzzleClient([
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for dev (Docker SSL issue)
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => self::USER_AGENT
            ]
        ]);
    }

    /**
     * Search for album releases by artist and title
     *
     * @param string $artistName Artist name
     * @param string $albumTitle Album title
     * @return array Array of release results with MBID, title, date, etc.
     * @throws LocalizedException
     */
    public function searchReleases(string $artistName, string $albumTitle): array
    {
        $this->respectRateLimit();

        $query = sprintf('artist:"%s" AND release:"%s"', $artistName, $albumTitle);
        $url = sprintf(
            '%s/release/?query=%s&fmt=json&limit=10',
            self::MUSICBRAINZ_API_BASE,
            urlencode($query)
        );

        $response = $this->executeRequest($url);
        $data = $this->jsonSerializer->unserialize($response);

        return $data['releases'] ?? [];
    }

    /**
     * Get all releases for an artist
     *
     * @param string $artistName Artist name
     * @param int $limit Maximum results
     * @return array Array of releases
     * @throws LocalizedException
     */
    public function getArtistReleases(string $artistName, int $limit = 50): array
    {
        $this->respectRateLimit();

        $query = sprintf('artist:"%s" AND type:album AND status:official', $artistName);
        $url = sprintf(
            '%s/release/?query=%s&fmt=json&limit=%d',
            self::MUSICBRAINZ_API_BASE,
            urlencode($query),
            $limit
        );

        $response = $this->executeRequest($url);
        $data = $this->jsonSerializer->unserialize($response);

        return $data['releases'] ?? [];
    }

    /**
     * Get artwork URL for a release by MBID
     *
     * @param string $mbid MusicBrainz Release ID
     * @param int $size Image size (250, 500, 1200, or 0 for full-res)
     * @return string|null Artwork URL or null if not found
     */
    public function getArtworkUrl(string $mbid, int $size = 500): ?string
    {
        $this->respectRateLimit();

        $sizeParam = match($size) {
            250 => '-250',
            500 => '-500',
            1200 => '-1200',
            default => ''
        };

        $url = sprintf('%s/release/%s/front%s', self::COVERART_API_BASE, $mbid, $sizeParam);

        // Cover Art Archive returns 404 if no artwork exists
        // Don't treat this as an error
        try {
            $response = $this->httpClient->head($url, [
                'headers' => ['Accept' => 'image/*']
            ]);

            $status = $response->getStatusCode();

            if ($status === 200 || $status === 307) {
                return $url;
            }

            if ($status === 404) {
                $this->logger->debug("No artwork found for MBID: $mbid");
                return null;
            }

            $this->logger->warning("Unexpected status $status for artwork: $mbid");
            return null;

        } catch (GuzzleException $e) {
            // 404 exceptions are expected for missing artwork
            if (strpos($e->getMessage(), '404') !== false) {
                $this->logger->debug("No artwork found for MBID: $mbid");
                return null;
            }
            $this->logger->error("Failed to fetch artwork for $mbid: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch artwork for $mbid: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download artwork binary data
     *
     * @param string $mbid MusicBrainz Release ID
     * @param int $size Image size
     * @return string|null Binary image data or null if not found
     */
    public function downloadArtwork(string $mbid, int $size = 500): ?string
    {
        $sizeParam = match($size) {
            250 => '-250',
            500 => '-500',
            1200 => '-1200',
            default => ''
        };

        $url = sprintf('%s/release/%s/front%s', self::COVERART_API_BASE, $mbid, $sizeParam);

        try {
            $response = $this->httpClient->get($url, [
                'headers' => ['Accept' => 'image/*']
            ]);

            if ($response->getStatusCode() === 200) {
                return (string)$response->getBody();
            }

            return null;

        } catch (GuzzleException $e) {
            // 404 is expected for missing artwork
            if (strpos($e->getMessage(), '404') !== false) {
                $this->logger->debug("No artwork found for MBID: $mbid");
                return null;
            }
            $this->logger->error("Failed to download artwork from $url: " . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logger->error("Failed to download artwork from $url: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Execute HTTP request with error handling
     */
    private function executeRequest(string $url): string
    {
        try {
            $response = $this->httpClient->get($url, [
                'headers' => ['Accept' => 'application/json']
            ]);

            $status = $response->getStatusCode();

            if ($status === 200) {
                return (string)$response->getBody();
            }

            if ($status === 503) {
                throw new LocalizedException(__('MusicBrainz service unavailable (503)'));
            }

            throw new LocalizedException(__('MusicBrainz API error: HTTP %1', $status));

        } catch (GuzzleException $e) {
            throw new LocalizedException(__('MusicBrainz request failed: %1', $e->getMessage()), $e);
        } catch (\Exception $e) {
            if ($e instanceof LocalizedException) {
                throw $e;
            }
            throw new LocalizedException(__('MusicBrainz request failed: %1', $e->getMessage()), $e);
        }
    }

    /**
     * Respect MusicBrainz rate limit (1 req/sec)
     */
    private function respectRateLimit(): void
    {
        $now = intval(microtime(true) * 1000);
        $elapsed = $now - $this->lastRequestTime;

        if ($elapsed < self::RATE_LIMIT_DELAY_MS) {
            usleep((self::RATE_LIMIT_DELAY_MS - $elapsed) * 1000);
        }

        $this->lastRequestTime = intval(microtime(true) * 1000);
    }
}
