<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use ArchiveDotOrg\Core\Logger\Logger;

/**
 * Artist data enrichment service with multi-tier fallback
 *
 * Tier 1: Wikipedia REST API (bio, thumbnail)
 * Tier 2: Wikipedia Parse API + HTML parsing (origin, genres, years_active, website)
 * Tier 3: Brave Search API (social media links)
 * Tier 4: Archive.org Stats (total shows, most played track from local database)
 */
class ArtistEnrichmentService
{
    private WikipediaClient $wikipediaClient;
    private BraveSearchClient $braveSearchClient;
    private ArchiveStatsService $archiveStatsService;
    private CategoryRepositoryInterface $categoryRepository;
    private Logger $logger;
    private array $negativeCache = [];

    public function __construct(
        WikipediaClient $wikipediaClient,
        BraveSearchClient $braveSearchClient,
        ArchiveStatsService $archiveStatsService,
        CategoryRepositoryInterface $categoryRepository,
        Logger $logger
    ) {
        $this->wikipediaClient = $wikipediaClient;
        $this->braveSearchClient = $braveSearchClient;
        $this->archiveStatsService = $archiveStatsService;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * Enrich artist category with Wikipedia and web search data
     *
     * @param int $categoryId Category ID
     * @param string $artistName Artist name
     * @param array $fields Fields to enrich (default: all)
     * @return array Result with success/failure counts and confidence scores
     */
    public function enrichArtist(int $categoryId, string $artistName, array $fields = []): array
    {
        // Default: enrich all fields
        if (empty($fields)) {
            $fields = [
                'bio', 'origin', 'years_active', 'genres',
                'website', 'facebook', 'instagram', 'twitter', 'stats'
            ];
        }

        $result = [
            'category_id' => $categoryId,
            'artist_name' => $artistName,
            'fields_updated' => [],
            'fields_failed' => [],
            'confidence' => [],
            'data_sources' => [],
        ];

        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error("Category not found: $categoryId");
            return $result;
        }

        // Tier 1: Wikipedia REST API (bio, thumbnail)
        if (in_array('bio', $fields)) {
            $summary = $this->wikipediaClient->getArtistSummary($artistName);
            if ($summary && !empty($summary['bio'])) {
                $category->setData('band_extended_bio', $summary['bio']);
                $result['fields_updated'][] = 'band_extended_bio';
                $result['confidence']['band_extended_bio'] = 'high';
                $result['data_sources']['band_extended_bio'] = 'Wikipedia REST API';
                $this->logger->info("Enriched bio for $artistName via Wikipedia REST API");

                // Save thumbnail image URL (hotlinked from Wikimedia)
                if (!empty($summary['thumbnail'])) {
                    $category->setData('band_image_url', $summary['thumbnail']);
                    $result['fields_updated'][] = 'band_image_url';
                    $result['confidence']['band_image_url'] = 'high';
                    $result['data_sources']['band_image_url'] = 'Wikipedia/Wikimedia (hotlinked)';
                    $this->logger->info("Saved image URL for $artistName: " . $summary['thumbnail']);
                }
            } else {
                $result['fields_failed'][] = 'band_extended_bio';
                $this->negativeCache[$artistName . '_bio'] = true;
            }
        }

        // Tier 2: Wikipedia Parse API + HTML parsing (infobox data)
        $needsInfobox = array_intersect($fields, ['origin', 'years_active', 'genres', 'website']);
        if (!empty($needsInfobox)) {
            $infobox = $this->wikipediaClient->getArtistInfobox($artistName);

            if (!empty($infobox['origin'])) {
                $category->setData('band_origin_location', $infobox['origin']);
                $result['fields_updated'][] = 'band_origin_location';
                $result['confidence']['band_origin_location'] = 'medium';
                $result['data_sources']['band_origin_location'] = 'Wikipedia Infobox';
            }

            if (!empty($infobox['years_active'])) {
                $category->setData('band_years_active', $infobox['years_active']);
                $result['fields_updated'][] = 'band_years_active';
                $result['confidence']['band_years_active'] = 'medium';
                $result['data_sources']['band_years_active'] = 'Wikipedia Infobox';

                // Extract formation year from years_active
                if (preg_match('/(\d{4})/', $infobox['years_active'], $matches)) {
                    $category->setData('band_formation_date', $matches[1]);
                    $result['fields_updated'][] = 'band_formation_date';
                    $result['confidence']['band_formation_date'] = 'medium';
                }
            }

            if (!empty($infobox['genres'])) {
                $category->setData('band_genres', $infobox['genres']);
                $result['fields_updated'][] = 'band_genres';
                $result['confidence']['band_genres'] = 'medium';
                $result['data_sources']['band_genres'] = 'Wikipedia Infobox';
            }

            if (!empty($infobox['website'])) {
                $category->setData('band_official_website', $infobox['website']);
                $result['fields_updated'][] = 'band_official_website';
                $result['confidence']['band_official_website'] = 'high';
                $result['data_sources']['band_official_website'] = 'Wikipedia Infobox';
            }
        }

        // Tier 3: Brave Search API (social media)
        $needsSocial = array_intersect($fields, ['website', 'facebook', 'instagram', 'twitter']);
        if (!empty($needsSocial)) {
            // Skip if already found website in Wikipedia
            if (in_array('website', $needsSocial) && !empty($category->getData('band_official_website'))) {
                $needsSocial = array_diff($needsSocial, ['website']);
            }

            if (!empty($needsSocial)) {
                $socialLinks = $this->braveSearchClient->findSocialLinks($artistName);

                if (!empty($socialLinks['website']) && !$category->getData('band_official_website')) {
                    $category->setData('band_official_website', $socialLinks['website']);
                    $result['fields_updated'][] = 'band_official_website';
                    $result['confidence']['band_official_website'] = 'medium';
                    $result['data_sources']['band_official_website'] = 'Brave Search';
                }

                if (!empty($socialLinks['facebook'])) {
                    $category->setData('band_facebook', $socialLinks['facebook']);
                    $result['fields_updated'][] = 'band_facebook';
                    $result['confidence']['band_facebook'] = 'medium';
                    $result['data_sources']['band_facebook'] = 'Brave Search';
                }

                if (!empty($socialLinks['instagram'])) {
                    $category->setData('band_instagram', $socialLinks['instagram']);
                    $result['fields_updated'][] = 'band_instagram';
                    $result['confidence']['band_instagram'] = 'medium';
                    $result['data_sources']['band_instagram'] = 'Brave Search';
                }

                if (!empty($socialLinks['twitter'])) {
                    $category->setData('band_twitter', $socialLinks['twitter']);
                    $result['fields_updated'][] = 'band_twitter';
                    $result['confidence']['band_twitter'] = 'medium';
                    $result['data_sources']['band_twitter'] = 'Brave Search';
                }
            }
        }

        // Tier 4: Archive.org Stats (from local database)
        if (in_array('stats', $fields)) {
            $stats = $this->archiveStatsService->getArtistStats($categoryId, $artistName);

            if ($stats['total_shows'] > 0) {
                $category->setData('band_total_shows', $stats['total_shows']);
                $result['fields_updated'][] = 'band_total_shows';
                $result['confidence']['band_total_shows'] = 'high';
                $result['data_sources']['band_total_shows'] = 'Archive.org (local database)';
                $this->logger->info("Total shows for $artistName: " . $stats['total_shows']);
            }

            if ($stats['most_played_track']) {
                $category->setData('band_most_played_track', $stats['most_played_track']);
                $result['fields_updated'][] = 'band_most_played_track';
                $result['confidence']['band_most_played_track'] = 'high';
                $result['data_sources']['band_most_played_track'] = 'Archive.org (local database)';
                $this->logger->info("Most played track for $artistName: " . $stats['most_played_track']);
            }
        }

        // Save category
        try {
            $this->categoryRepository->save($category);
            $this->logger->info(sprintf(
                "Enriched artist %s: %d fields updated, %d failed",
                $artistName,
                count($result['fields_updated']),
                count($result['fields_failed'])
            ));
        } catch (\Exception $e) {
            $this->logger->error("Failed to save enriched category data: " . $e->getMessage());
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Batch enrich multiple artists
     *
     * @param array $artists Array of ['category_id' => int, 'artist_name' => string]
     * @param array $fields Fields to enrich
     * @param callable|null $progressCallback Progress callback
     * @return array Overall results
     */
    public function enrichBatch(array $artists, array $fields = [], ?callable $progressCallback = null): array
    {
        $results = [
            'total' => count($artists),
            'processed' => 0,
            'updated' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($artists as $index => $artist) {
            $categoryId = $artist['category_id'];
            $artistName = $artist['artist_name'];

            if ($progressCallback) {
                $progressCallback($results['total'], $index + 1, "Enriching $artistName...");
            }

            $result = $this->enrichArtist($categoryId, $artistName, $fields);
            $results['details'][$artistName] = $result;
            $results['processed']++;

            if (!empty($result['fields_updated'])) {
                $results['updated']++;
            } elseif (!empty($result['fields_failed'])) {
                $results['failed']++;
            }

            // Rate limiting: 1 second between artists to avoid API throttling
            if ($index < count($artists) - 1) {
                sleep(1);
            }
        }

        return $results;
    }
}
