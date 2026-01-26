<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Api\CategoryAssignmentServiceInterface;
use ArchiveDotOrg\Core\Api\Data\ImportResultInterface;
use ArchiveDotOrg\Core\Api\Data\ImportResultInterfaceFactory;
use ArchiveDotOrg\Core\Api\Data\ShowInterface;
use ArchiveDotOrg\Core\Api\ShowImporterInterface;
use ArchiveDotOrg\Core\Api\TrackImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use DateTime;
use Magento\Framework\Exception\LocalizedException;

/**
 * Show Importer Implementation
 *
 * Orchestrates the import of shows from Archive.org with batch processing,
 * memory management, and progress tracking.
 */
class ShowImporter implements ShowImporterInterface
{
    private ArchiveApiClientInterface $apiClient;
    private ConcurrentApiClient $concurrentApiClient;
    private TrackImporterInterface $trackImporter;
    private AttributeOptionManagerInterface $attributeOptionManager;
    private CategoryAssignmentServiceInterface $categoryAssignmentService;
    private ImportResultInterfaceFactory $resultFactory;
    private Config $config;
    private Logger $logger;

    /**
     * Current collection ID for category assignment
     */
    private ?string $currentCollectionId = null;

    /**
     * Cached artist category ID
     */
    private ?int $artistCategoryId = null;

    /**
     * @param ArchiveApiClientInterface $apiClient
     * @param ConcurrentApiClient $concurrentApiClient
     * @param TrackImporterInterface $trackImporter
     * @param AttributeOptionManagerInterface $attributeOptionManager
     * @param CategoryAssignmentServiceInterface $categoryAssignmentService
     * @param ImportResultInterfaceFactory $resultFactory
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        ArchiveApiClientInterface $apiClient,
        ConcurrentApiClient $concurrentApiClient,
        TrackImporterInterface $trackImporter,
        AttributeOptionManagerInterface $attributeOptionManager,
        CategoryAssignmentServiceInterface $categoryAssignmentService,
        ImportResultInterfaceFactory $resultFactory,
        Config $config,
        Logger $logger
    ) {
        $this->apiClient = $apiClient;
        $this->concurrentApiClient = $concurrentApiClient;
        $this->trackImporter = $trackImporter;
        $this->attributeOptionManager = $attributeOptionManager;
        $this->categoryAssignmentService = $categoryAssignmentService;
        $this->resultFactory = $resultFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function importByCollection(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null,
        ?callable $progressCallback = null
    ): ImportResultInterface {
        /** @var ImportResultInterface $result */
        $result = $this->resultFactory->create();
        $result->setArtistName($artistName);
        $result->setCollectionId($collectionId);
        $result->setStartTime(new DateTime());

        $this->logger->logImportStart($artistName, $collectionId, $limit, $offset);

        // Set up collection context for category assignment
        $this->currentCollectionId = $collectionId;
        $this->artistCategoryId = $this->categoryAssignmentService->getOrCreateArtistCategory(
            $artistName,
            $collectionId
        );

        try {
            // Fetch all identifiers from the collection
            $identifiers = $this->apiClient->fetchCollectionIdentifiers(
                $collectionId,
                $limit,
                $offset
            );

            $total = count($identifiers);
            $current = 0;
            $batchSize = $this->config->getBatchSize();

            $this->notifyProgress($progressCallback, $total, 0, "Starting import of $total shows");

            // Process in batches with concurrent metadata fetching
            $batchIndex = 0;
            foreach (array_chunk($identifiers, $batchSize) as $batch) {
                // Add delay between batches to avoid overwhelming Archive.org API
                if ($batchIndex > 0) {
                    $this->logger->debug('Pausing between import batches', [
                        'delay_sec' => 2,
                        'batch_index' => $batchIndex
                    ]);
                    sleep(2);
                }
                $batchIndex++;

                // Fetch all show metadata for this batch in parallel (with internal rate limiting)
                $shows = $this->concurrentApiClient->fetchShowMetadataBatch($batch);

                foreach ($batch as $identifier) {
                    $current++;

                    try {
                        $show = $shows[$identifier] ?? null;
                        if ($show === null) {
                            throw new \RuntimeException("Failed to fetch metadata for $identifier");
                        }

                        $this->processShowFromData($show, $artistName, $result);
                        $this->notifyProgress(
                            $progressCallback,
                            $total,
                            $current,
                            "Processed: $identifier"
                        );
                    } catch (\Exception $e) {
                        $result->addError($e->getMessage(), $identifier);
                        $this->logger->logImportError('Show processing failed', [
                            'identifier' => $identifier,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Clear caches between batches to manage memory
                $this->attributeOptionManager->clearCache();
                $this->categoryAssignmentService->clearCache();
                gc_collect_cycles();
            }

        } catch (\Exception $e) {
            $result->addError('Collection import failed: ' . $e->getMessage());
            $this->logger->logImportError('Collection import failed', [
                'collection' => $collectionId,
                'error' => $e->getMessage()
            ]);
        }

        $result->setEndTime(new DateTime());
        $this->logger->logImportComplete($result->toArray());

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function importShow(string $identifier, string $artistName): ImportResultInterface
    {
        /** @var ImportResultInterface $result */
        $result = $this->resultFactory->create();
        $result->setArtistName($artistName);
        $result->setStartTime(new DateTime());

        try {
            $this->processShow($identifier, $artistName, $result);
        } catch (\Exception $e) {
            $result->addError($e->getMessage(), $identifier);
        }

        $result->setEndTime(new DateTime());
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function dryRun(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null
    ): ImportResultInterface {
        /** @var ImportResultInterface $result */
        $result = $this->resultFactory->create();
        $result->setArtistName($artistName);
        $result->setCollectionId($collectionId);
        $result->setStartTime(new DateTime());

        try {
            $identifiers = $this->apiClient->fetchCollectionIdentifiers(
                $collectionId,
                $limit,
                $offset
            );

            foreach ($identifiers as $identifier) {
                try {
                    $show = $this->apiClient->fetchShowMetadata($identifier);
                    $tracks = $show->getTracks();

                    $result->incrementShowsProcessed();

                    foreach ($tracks as $track) {
                        $sku = $track->generateSku();

                        if ($this->trackImporter->productExists($sku)) {
                            $result->incrementTracksUpdated();
                        } else {
                            $result->incrementTracksCreated();
                        }
                    }
                } catch (\Exception $e) {
                    $result->addError($e->getMessage(), $identifier);
                }
            }
        } catch (\Exception $e) {
            $result->addError('Dry run failed: ' . $e->getMessage());
        }

        $result->setEndTime(new DateTime());
        return $result;
    }

    /**
     * Process a single show (fetches metadata)
     *
     * @param string $identifier
     * @param string $artistName
     * @param ImportResultInterface $result
     * @return void
     * @throws LocalizedException
     */
    private function processShow(
        string $identifier,
        string $artistName,
        ImportResultInterface $result
    ): void {
        $show = $this->apiClient->fetchShowMetadata($identifier);
        $this->processShowFromData($show, $artistName, $result);
    }

    /**
     * Process a show from pre-fetched data (used by batch processing)
     *
     * @param ShowInterface $show
     * @param string $artistName
     * @param ImportResultInterface $result
     * @return void
     */
    private function processShowFromData(
        ShowInterface $show,
        string $artistName,
        ImportResultInterface $result
    ): void {
        $trackResult = $this->trackImporter->importShowTracks($show, $artistName);

        $result->incrementShowsProcessed();

        for ($i = 0; $i < $trackResult['created']; $i++) {
            $result->incrementTracksCreated();
        }

        for ($i = 0; $i < $trackResult['updated']; $i++) {
            $result->incrementTracksUpdated();
        }

        for ($i = 0; $i < $trackResult['skipped']; $i++) {
            $result->incrementTracksSkipped();
        }

        // Assign products to artist category
        $this->assignProductsToCategories(
            $trackResult['product_ids'] ?? [],
            null
        );

        $this->logger->logShowProcessed(
            $show->getIdentifier(),
            $show->getTitle(),
            count($show->getTracks())
        );
    }

    /**
     * Assign products to appropriate categories
     *
     * @param int[] $productIds
     * @param int|null $showCategoryId The show/album category ID (already verified to exist)
     * @return void
     */
    private function assignProductsToCategories(
        array $productIds,
        ?int $showCategoryId
    ): void {
        if (empty($productIds)) {
            return;
        }

        // Assign to artist category if available
        if ($this->artistCategoryId !== null) {
            $this->categoryAssignmentService->bulkAssignToCategory(
                $productIds,
                $this->artistCategoryId
            );
        }

        // Assign to show category (verified to exist in processShow before importing)
        if ($showCategoryId !== null) {
            $this->categoryAssignmentService->bulkAssignToCategory(
                $productIds,
                $showCategoryId
            );
        }
    }

    /**
     * Notify progress callback
     *
     * @param callable|null $callback
     * @param int $total
     * @param int $current
     * @param string $message
     * @return void
     */
    private function notifyProgress(
        ?callable $callback,
        int $total,
        int $current,
        string $message
    ): void {
        if ($callback !== null) {
            $callback($total, $current, $message);
        }
    }
}
