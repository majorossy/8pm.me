<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use ArchiveDotOrg\Core\Logger\Logger;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Brave Search API client for finding artist social media links
 *
 * Free tier: 2,000 queries/month
 * Paid tier: $3 per 1,000 queries
 * API Docs: https://api.search.brave.com/app/documentation
 */
class BraveSearchClient
{
    private const BRAVE_API_BASE = 'https://api.search.brave.com/res/v1/web/search';
    private const USER_AGENT = 'ArchiveDotOrg-Magento/2.0';
    private const CACHE_TTL = 2592000; // 30 days

    private ScopeConfigInterface $scopeConfig;
    private Json $jsonSerializer;
    private Logger $logger;
    private GuzzleClient $httpClient;
    private array $cache = [];

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $jsonSerializer,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->httpClient = new GuzzleClient([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => self::USER_AGENT
            ]
        ]);
    }

    /**
     * Search for artist social media links
     *
     * @param string $artistName Artist name
     * @return array Associative array of social media URLs
     */
    public function findSocialLinks(string $artistName): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            $this->logger->debug("Brave Search API key not configured, skipping social media search");
            return [];
        }

        // Check cache first
        $cacheKey = 'brave_social_' . md5($artistName);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // Search for artist's official website and social media
            $searchQuery = sprintf('%s official website facebook instagram twitter', $artistName);
            $results = $this->performSearch($searchQuery);

            $links = $this->extractSocialLinks($results);

            // Cache results
            $this->cache[$cacheKey] = $links;

            return $links;

        } catch (\Exception $e) {
            $this->logger->error("Brave Search API error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search for artist's official website
     *
     * @param string $artistName Artist name
     * @return string|null Website URL or null
     */
    public function findOfficialWebsite(string $artistName): ?string
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            return null;
        }

        try {
            $searchQuery = sprintf('%s official website', $artistName);
            $results = $this->performSearch($searchQuery);

            // Look for first result that's not a social media site
            foreach ($results as $result) {
                $url = $result['url'] ?? null;
                if ($url && !$this->isSocialMediaUrl($url)) {
                    return $url;
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error("Brave Search website lookup error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Perform Brave Search API query
     *
     * @param string $query Search query
     * @return array Search results
     * @throws LocalizedException
     */
    private function performSearch(string $query): array
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            return [];
        }

        try {
            // Rate limiting: sleep 1 second between requests (free tier limit)
            usleep(1000000);

            $url = sprintf(
                '%s?q=%s&count=10',
                self::BRAVE_API_BASE,
                urlencode($query)
            );

            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Subscription-Token' => $apiKey
                ]
            ]);

            $data = $this->jsonSerializer->unserialize($response->getBody()->getContents());

            return $data['web']['results'] ?? [];

        } catch (GuzzleException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Brave Search API request failed: %1', $e->getMessage())
            );
        }
    }

    /**
     * Extract social media links from search results
     *
     * @param array $results Search results
     * @return array Associative array of social media URLs
     */
    private function extractSocialLinks(array $results): array
    {
        $links = [
            'website' => null,
            'facebook' => null,
            'instagram' => null,
            'twitter' => null,
        ];

        foreach ($results as $result) {
            $url = $result['url'] ?? null;
            $title = $result['title'] ?? '';
            $description = $result['description'] ?? '';

            if (!$url) {
                continue;
            }

            // Extract Facebook
            if (preg_match('/facebook\.com\/([^\/\?]+)/i', $url, $matches)) {
                if (!isset($links['facebook'])) {
                    $links['facebook'] = $url;
                }
            }

            // Extract Instagram
            if (preg_match('/instagram\.com\/([^\/\?]+)/i', $url, $matches)) {
                if (!isset($links['instagram'])) {
                    $links['instagram'] = $url;
                }
            }

            // Extract Twitter/X
            if (preg_match('/(twitter\.com|x\.com)\/([^\/\?]+)/i', $url, $matches)) {
                if (!isset($links['twitter'])) {
                    $links['twitter'] = $url;
                }
            }

            // Extract official website (first non-social URL)
            if (!$links['website'] && !$this->isSocialMediaUrl($url)) {
                // Verify it's likely an official site by checking title/description
                if (stripos($title, 'official') !== false ||
                    stripos($description, 'official') !== false) {
                    $links['website'] = $url;
                }
            }
        }

        return array_filter($links); // Remove nulls
    }

    /**
     * Check if URL is a social media site
     *
     * @param string $url URL to check
     * @return bool True if social media URL
     */
    private function isSocialMediaUrl(string $url): bool
    {
        $socialDomains = [
            'facebook.com',
            'instagram.com',
            'twitter.com',
            'x.com',
            'youtube.com',
            'tiktok.com',
            'linkedin.com',
            'wikipedia.org',
            'allmusic.com',
            'discogs.com',
            'spotify.com',
            'bandcamp.com',
        ];

        foreach ($socialDomains as $domain) {
            if (stripos($url, $domain) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Brave Search API key from configuration
     *
     * @return string|null API key or null
     */
    private function getApiKey(): ?string
    {
        // Try environment variable first
        $apiKey = getenv('BRAVE_SEARCH_API_KEY');
        if ($apiKey !== false && !empty($apiKey)) {
            return $apiKey;
        }

        // Fallback to Magento config (can be added later)
        // $apiKey = $this->scopeConfig->getValue('archivedotorg/api/brave_search_key');

        return null;
    }
}
