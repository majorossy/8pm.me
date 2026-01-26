<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Resilience;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Exception thrown when the circuit breaker is open
 *
 * This indicates the Archive.org API has been failing repeatedly
 * and requests are being rejected to avoid hammering the failing service.
 */
class CircuitOpenException extends LocalizedException
{
    /**
     * Create exception from string message
     *
     * @param string $message Error message
     * @param \Exception|null $cause Previous exception
     * @param int $code Exception code
     * @return self
     */
    public static function fromString(string $message, \Exception $cause = null, int $code = 0): self
    {
        return new self(new Phrase($message), $cause, $code);
    }
}
