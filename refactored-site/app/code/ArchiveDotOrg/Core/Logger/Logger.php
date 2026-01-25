<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Logger;

use Monolog\Logger as MonologLogger;

/**
 * Custom logger for Archive.org import operations
 */
class Logger extends MonologLogger
{
    /**
     * Log an import start event
     *
     * @param string $artistName
     * @param string $collectionId
     * @param int|null $limit
     * @param int|null $offset
     * @return void
     */
    public function logImportStart(
        string $artistName,
        string $collectionId,
        ?int $limit = null,
        ?int $offset = null
    ): void {
        $this->info('Import started', [
            'artist' => $artistName,
            'collection' => $collectionId,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Log an import completion event
     *
     * @param array $stats
     * @return void
     */
    public function logImportComplete(array $stats): void
    {
        $this->info('Import completed', $stats);
    }

    /**
     * Log a show processing event
     *
     * @param string $identifier
     * @param string $title
     * @param int $trackCount
     * @return void
     */
    public function logShowProcessed(string $identifier, string $title, int $trackCount): void
    {
        $this->debug('Show processed', [
            'identifier' => $identifier,
            'title' => $title,
            'tracks' => $trackCount
        ]);
    }

    /**
     * Log a track creation event
     *
     * @param string $sku
     * @param string $title
     * @return void
     */
    public function logTrackCreated(string $sku, string $title): void
    {
        $this->debug('Track created', [
            'sku' => $sku,
            'title' => $title
        ]);
    }

    /**
     * Log an API error
     *
     * @param string $url
     * @param string $error
     * @param int|null $statusCode
     * @return void
     */
    public function logApiError(string $url, string $error, ?int $statusCode = null): void
    {
        $this->error('API error', [
            'url' => $url,
            'error' => $error,
            'status_code' => $statusCode
        ]);
    }

    /**
     * Log an import error
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logImportError(string $message, array $context = []): void
    {
        $this->error('Import error: ' . $message, $context);
    }
}
