<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Cron;

use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Process Import Queue Cron
 *
 * Runs the import queue consumer via cron to process queued import jobs
 */
class ProcessImportQueue
{
    private const CONSUMER_NAME = 'archivedotorg.import.job.consumer';
    private const MAX_MESSAGES = 10;

    private ConsumerFactory $consumerFactory;
    private Logger $logger;

    /**
     * @param ConsumerFactory $consumerFactory
     * @param Logger $logger
     */
    public function __construct(
        ConsumerFactory $consumerFactory,
        Logger $logger
    ) {
        $this->consumerFactory = $consumerFactory;
        $this->logger = $logger;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $consumer = $this->consumerFactory->get(self::CONSUMER_NAME);
            $consumer->process(self::MAX_MESSAGES);

            $this->logger->debug('Import queue consumer processed', [
                'max_messages' => self::MAX_MESSAGES
            ]);
        } catch (LocalizedException $e) {
            $this->logger->error('Failed to process import queue', [
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error processing import queue', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
