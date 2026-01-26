<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use ArchiveDotOrg\Core\Api\ArchiveApiClientInterface;
use ArchiveDotOrg\Core\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Status Command
 *
 * CLI command to check Archive.org module status and connectivity
 * Usage: bin/magento archive:status
 */
class StatusCommand extends Command
{
    private const OPTION_TEST_COLLECTION = 'test-collection';

    private Config $config;
    private ArchiveApiClientInterface $apiClient;
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @param Config $config
     * @param ArchiveApiClientInterface $apiClient
     * @param ProductCollectionFactory $productCollectionFactory
     * @param string|null $name
     */
    public function __construct(
        Config $config,
        ArchiveApiClientInterface $apiClient,
        ProductCollectionFactory $productCollectionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->config = $config;
        $this->apiClient = $apiClient;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('archive:status')
            ->setDescription('Check Archive.org module status and API connectivity')
            ->addOption(
                self::OPTION_TEST_COLLECTION,
                'c',
                InputOption::VALUE_OPTIONAL,
                'Test connectivity with a specific collection'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Archive.org Module Status');

        // Configuration status
        $io->section('Configuration');
        $io->table(['Setting', 'Value'], [
            ['Module Enabled', $this->config->isEnabled() ? 'Yes' : 'No'],
            ['Debug Mode', $this->config->isDebugEnabled() ? 'Yes' : 'No'],
            ['Base URL', $this->config->getBaseUrl()],
            ['Timeout', $this->config->getTimeout() . ' seconds'],
            ['Retry Attempts', $this->config->getRetryAttempts()],
            ['Batch Size', $this->config->getBatchSize()],
            ['Audio Format', $this->config->getAudioFormat()],
            ['Cron Enabled', $this->config->isCronEnabled() ? 'Yes' : 'No'],
            ['Cron Schedule', $this->config->getCronSchedule()]
        ]);

        // Artist mappings
        $mappings = $this->config->getArtistMappings();
        if (!empty($mappings)) {
            $io->section('Configured Artist Mappings');
            $io->write('Fetching collection sizes from Archive.org... ');

            $mappingTable = [];
            $totalAvailable = 0;

            foreach ($mappings as $mapping) {
                $artistName = $mapping['artist_name'] ?? 'Unknown';
                $collectionId = $mapping['collection_id'] ?? 'Unknown';

                // Fetch collection count from Archive.org
                try {
                    $count = $this->apiClient->getCollectionCount($collectionId);
                    $totalAvailable += $count;
                    $countDisplay = number_format($count);
                } catch (\Exception $e) {
                    $countDisplay = 'Error';
                }

                $mappingTable[] = [
                    $artistName,
                    $collectionId,
                    $countDisplay
                ];
            }

            $io->writeln('<info>Done</info>');
            $io->table(['Artist', 'Collection ID', 'Available Shows'], $mappingTable);
            $io->writeln(sprintf('<info>Total available shows: %s</info>', number_format($totalAvailable)));
        } else {
            $io->note('No artist mappings configured. Use Admin > Stores > Configuration > Archive.org Import.');
        }

        // API connectivity test
        $io->section('API Connectivity');
        $io->write('Testing connection to Archive.org... ');

        $connected = $this->apiClient->testConnection();

        if ($connected) {
            $io->writeln('<info>OK</info>');
        } else {
            $io->writeln('<error>FAILED</error>');
            $io->error('Cannot connect to Archive.org. Check network connectivity and API URL.');
            return Command::FAILURE;
        }

        // Test specific collection if requested
        $testCollection = $input->getOption(self::OPTION_TEST_COLLECTION);
        if ($testCollection !== null) {
            $io->section('Collection Test: ' . $testCollection);

            try {
                $count = $this->apiClient->getCollectionCount($testCollection);
                $io->writeln(sprintf('<info>Collection "%s" contains %d items</info>', $testCollection, $count));
            } catch (\Exception $e) {
                $io->error('Failed to query collection: ' . $e->getMessage());
            }
        }

        // Database statistics
        $io->section('Database Statistics');

        // Get product count with identifier attribute
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('identifier', ['notnull' => true]);
        $totalProducts = $productCollection->getSize();

        $io->table(['Metric', 'Value'], [
            ['Imported Products', number_format($totalProducts)]
        ]);

        $io->success('Status check completed.');

        return Command::SUCCESS;
    }
}
