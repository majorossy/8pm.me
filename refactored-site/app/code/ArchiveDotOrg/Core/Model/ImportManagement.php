<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Api\Data\CollectionInfoInterface;
use ArchiveDotOrg\Core\Api\Data\CollectionInfoInterfaceFactory;
use ArchiveDotOrg\Core\Api\Data\ImportJobInterface;
use ArchiveDotOrg\Core\Api\Data\ImportJobInterfaceFactory;
use ArchiveDotOrg\Core\Api\ImportManagementInterface;
use ArchiveDotOrg\Core\Api\ShowImporterInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Import Management Implementation
 *
 * Provides REST API functionality for managing Archive.org imports
 */
class ImportManagement implements ImportManagementInterface
{
    private const JOBS_DIR = 'archivedotorg/jobs';

    private ShowImporterInterface $showImporter;
    private ArchiveApiClientInterface $apiClient;
    private ProductRepositoryInterface $productRepository;
    private ProductCollectionFactory $productCollectionFactory;
    private Config $config;
    private Logger $logger;
    private DirectoryList $directoryList;
    private Json $jsonSerializer;
    private ImportJobInterfaceFactory $importJobFactory;
    private CollectionInfoInterfaceFactory $collectionInfoFactory;

    /**
     * @param ShowImporterInterface $showImporter
     * @param ArchiveApiClientInterface $apiClient
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Config $config
     * @param Logger $logger
     * @param DirectoryList $directoryList
     * @param Json $jsonSerializer
     * @param ImportJobInterfaceFactory $importJobFactory
     * @param CollectionInfoInterfaceFactory $collectionInfoFactory
     */
    public function __construct(
        ShowImporterInterface $showImporter,
        ArchiveApiClientInterface $apiClient,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollectionFactory,
        Config $config,
        Logger $logger,
        DirectoryList $directoryList,
        Json $jsonSerializer,
        ImportJobInterfaceFactory $importJobFactory,
        CollectionInfoInterfaceFactory $collectionInfoFactory
    ) {
        $this->showImporter = $showImporter;
        $this->apiClient = $apiClient;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->jsonSerializer = $jsonSerializer;
        $this->importJobFactory = $importJobFactory;
        $this->collectionInfoFactory = $collectionInfoFactory;
    }

    /**
     * @inheritDoc
     */
    public function startImport(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null,
        bool $dryRun = false
    ): ImportJobInterface {
        // Validate inputs
        $this->validateArtistName($artistName);
        $this->validateCollectionId($collectionId);

        if ($limit !== null && $limit <= 0) {
            throw new LocalizedException(__('Limit must be a positive integer.'));
        }

        if ($offset !== null && $offset < 0) {
            throw new LocalizedException(__('Offset must be a non-negative integer.'));
        }

        // Generate job ID
        $jobId = $this->generateJobId();

        // Create initial job status
        /** @var ImportJobInterface $job */
        $job = $this->importJobFactory->create();
        $job->setJobId($jobId)
            ->setStatus(ImportJobInterface::STATUS_RUNNING)
            ->setArtistName($artistName)
            ->setCollectionId($collectionId)
            ->setStartedAt(date('Y-m-d H:i:s'))
            ->setMessage($dryRun ? 'Starting dry run...' : 'Starting import...');

        $this->saveJobStatus($job);

        $this->logger->info('REST API import started', [
            'job_id' => $jobId,
            'artist' => $artistName,
            'collection' => $collectionId,
            'limit' => $limit,
            'offset' => $offset,
            'dry_run' => $dryRun
        ]);

        try {
            // Execute import with progress tracking
            $progressCallback = function (int $total, int $current, string $message) use ($job) {
                $job->setTotalShows($total)
                    ->setProcessedShows($current)
                    ->setMessage($message);
                $this->saveJobStatus($job);
            };

            if ($dryRun) {
                $result = $this->showImporter->dryRun($artistName, $collectionId, $limit, $offset);
            } else {
                $result = $this->showImporter->importByCollection(
                    $artistName,
                    $collectionId,
                    $limit,
                    $offset,
                    $progressCallback
                );
            }

            // Update final status
            $resultArray = $result->toArray();

            $job->setStatus(
                $result->hasErrors()
                    ? ImportJobInterface::STATUS_FAILED
                    : ImportJobInterface::STATUS_COMPLETED
            )
                ->setTotalShows($resultArray['shows_processed'])
                ->setProcessedShows($resultArray['shows_processed'])
                ->setTracksCreated($resultArray['tracks_created'])
                ->setTracksUpdated($resultArray['tracks_updated'])
                ->setErrorCount($resultArray['error_count'])
                ->setErrors(array_map(
                    fn($e) => ($e['context'] ?? '') . ': ' . $e['message'],
                    array_slice($resultArray['errors'], 0, 10)
                ))
                ->setCompletedAt(date('Y-m-d H:i:s'))
                ->setMessage(
                    $result->hasErrors()
                        ? 'Import completed with errors'
                        : ($dryRun ? 'Dry run completed successfully' : 'Import completed successfully')
                );

            $this->saveJobStatus($job);

        } catch (\Exception $e) {
            $job->setStatus(ImportJobInterface::STATUS_FAILED)
                ->setErrorCount(1)
                ->setErrors([$e->getMessage()])
                ->setCompletedAt(date('Y-m-d H:i:s'))
                ->setMessage('Import failed: ' . $e->getMessage());

            $this->saveJobStatus($job);

            $this->logger->error('REST API import failed', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            throw new LocalizedException(
                __('Import failed: %1', $e->getMessage()),
                $e
            );
        }

        return $job;
    }

    /**
     * @inheritDoc
     */
    public function getJobStatus(string $jobId): ImportJobInterface
    {
        $jobFile = $this->getJobFilePath($jobId);

        if (!file_exists($jobFile)) {
            throw new NoSuchEntityException(
                __('Import job with ID "%1" does not exist.', $jobId)
            );
        }

        $data = $this->jsonSerializer->unserialize(file_get_contents($jobFile));

        /** @var ImportJobInterface $job */
        $job = $this->importJobFactory->create();
        $job->setJobId($data['job_id'] ?? $jobId)
            ->setStatus($data['status'] ?? ImportJobInterface::STATUS_PENDING)
            ->setArtistName($data['artist_name'] ?? null)
            ->setCollectionId($data['collection_id'] ?? null)
            ->setTotalShows($data['total_shows'] ?? 0)
            ->setProcessedShows($data['processed_shows'] ?? 0)
            ->setTracksCreated($data['tracks_created'] ?? 0)
            ->setTracksUpdated($data['tracks_updated'] ?? 0)
            ->setErrorCount($data['error_count'] ?? 0)
            ->setErrors($data['errors'] ?? [])
            ->setMessage($data['message'] ?? null)
            ->setStartedAt($data['started_at'] ?? null)
            ->setCompletedAt($data['completed_at'] ?? null);

        return $job;
    }

    /**
     * @inheritDoc
     */
    public function deleteProduct(string $sku): bool
    {
        if (empty($sku)) {
            throw new LocalizedException(__('SKU cannot be empty.'));
        }

        try {
            $product = $this->productRepository->get($sku);

            // Verify this is an Archive.org product
            $identifier = $product->getData('identifier');
            if (empty($identifier)) {
                throw new LocalizedException(
                    __('Product "%1" is not an Archive.org product.', $sku)
                );
            }

            $this->productRepository->delete($product);

            $this->logger->info('Product deleted via REST API', ['sku' => $sku]);

            return true;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __('Product with SKU "%1" does not exist.', $sku)
            );
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete product "%1": %2', $sku, $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function listCollections(bool $includeStats = false): array
    {
        $mappings = $this->config->getArtistMappings();
        $collections = [];

        foreach ($mappings as $mapping) {
            if (empty($mapping['artist_name']) || empty($mapping['collection_id'])) {
                continue;
            }

            /** @var CollectionInfoInterface $collection */
            $collection = $this->collectionInfoFactory->create();
            $collection->setArtistName($mapping['artist_name'])
                ->setCollectionId($mapping['collection_id'])
                ->setCategoryId(
                    isset($mapping['category_id']) && $mapping['category_id'] !== ''
                        ? (int) $mapping['category_id']
                        : null
                )
                ->setEnabled(true);

            if ($includeStats) {
                // Get imported product count
                $importedCount = $this->getImportedCountForCollection($mapping['artist_name']);
                $collection->setImportedCount($importedCount);

                // Get total items from Archive.org (optional, can be slow)
                try {
                    $totalItems = $this->apiClient->getCollectionCount($mapping['collection_id']);
                    $collection->setTotalItems($totalItems);
                } catch (\Exception $e) {
                    // API call failed, leave as null
                    $this->logger->debug('Failed to get collection count', [
                        'collection' => $mapping['collection_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * @inheritDoc
     */
    public function getCollection(string $collectionId): CollectionInfoInterface
    {
        $this->validateCollectionId($collectionId);

        $mappings = $this->config->getArtistMappings();

        foreach ($mappings as $mapping) {
            if (($mapping['collection_id'] ?? '') === $collectionId) {
                /** @var CollectionInfoInterface $collection */
                $collection = $this->collectionInfoFactory->create();
                $collection->setArtistName($mapping['artist_name'] ?? '')
                    ->setCollectionId($collectionId)
                    ->setCategoryId(
                        isset($mapping['category_id']) && $mapping['category_id'] !== ''
                            ? (int) $mapping['category_id']
                            : null
                    )
                    ->setEnabled(true)
                    ->setImportedCount($this->getImportedCountForCollection($mapping['artist_name'] ?? ''));

                try {
                    $totalItems = $this->apiClient->getCollectionCount($collectionId);
                    $collection->setTotalItems($totalItems);
                } catch (\Exception $e) {
                    // API call failed
                }

                return $collection;
            }
        }

        throw new NoSuchEntityException(
            __('Collection with ID "%1" is not configured.', $collectionId)
        );
    }

    /**
     * @inheritDoc
     */
    public function cancelJob(string $jobId): bool
    {
        $job = $this->getJobStatus($jobId);

        if ($job->getStatus() !== ImportJobInterface::STATUS_RUNNING) {
            throw new LocalizedException(
                __('Cannot cancel job "%1" - status is "%2".', $jobId, $job->getStatus())
            );
        }

        // Mark job as cancelled
        $job->setStatus(ImportJobInterface::STATUS_FAILED)
            ->setMessage('Job cancelled by user')
            ->setCompletedAt(date('Y-m-d H:i:s'));

        $this->saveJobStatus($job);

        $this->logger->info('Import job cancelled', ['job_id' => $jobId]);

        return true;
    }

    /**
     * Validate artist name
     *
     * @param string $artistName
     * @throws LocalizedException
     */
    private function validateArtistName(string $artistName): void
    {
        if (trim($artistName) === '') {
            throw new LocalizedException(__('Artist name cannot be empty.'));
        }
    }

    /**
     * Validate collection ID
     *
     * @param string $collectionId
     * @throws LocalizedException
     */
    private function validateCollectionId(string $collectionId): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $collectionId)) {
            throw new LocalizedException(
                __('Collection ID must contain only alphanumeric characters, underscores, and hyphens.')
            );
        }
    }

    /**
     * Generate unique job ID
     *
     * @return string
     */
    private function generateJobId(): string
    {
        return sprintf(
            'import_%s_%s',
            date('YmdHis'),
            substr(md5(uniqid((string) mt_rand(), true)), 0, 8)
        );
    }

    /**
     * Get job file path
     *
     * @param string $jobId
     * @return string
     */
    private function getJobFilePath(string $jobId): string
    {
        $varDir = $this->directoryList->getPath('var');
        return $varDir . '/' . self::JOBS_DIR . '/' . $jobId . '.json';
    }

    /**
     * Save job status to file
     *
     * @param ImportJobInterface $job
     * @return void
     */
    private function saveJobStatus(ImportJobInterface $job): void
    {
        $jobFile = $this->getJobFilePath($job->getJobId());
        $jobDir = dirname($jobFile);

        if (!is_dir($jobDir)) {
            mkdir($jobDir, 0755, true);
        }

        $data = [
            'job_id' => $job->getJobId(),
            'status' => $job->getStatus(),
            'artist_name' => $job->getArtistName(),
            'collection_id' => $job->getCollectionId(),
            'total_shows' => $job->getTotalShows(),
            'processed_shows' => $job->getProcessedShows(),
            'tracks_created' => $job->getTracksCreated(),
            'tracks_updated' => $job->getTracksUpdated(),
            'error_count' => $job->getErrorCount(),
            'errors' => $job->getErrors(),
            'message' => $job->getMessage(),
            'started_at' => $job->getStartedAt(),
            'completed_at' => $job->getCompletedAt(),
            'progress' => $job->getProgress()
        ];

        file_put_contents($jobFile, $this->jsonSerializer->serialize($data));
    }

    /**
     * Get imported product count for a collection/artist
     *
     * @param string $artistName
     * @return int
     */
    private function getImportedCountForCollection(string $artistName): int
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToFilter('archive_collection', ['like' => '%' . $artistName . '%']);
            return $collection->getSize();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
