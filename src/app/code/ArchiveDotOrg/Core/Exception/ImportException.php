<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

use Magento\Framework\Phrase;

/**
 * Import Exception
 *
 * Thrown when product/track import operations fail
 */
class ImportException extends ArchiveDotOrgException
{
    private ?string $identifier;
    private ?string $sku;
    private ?string $operation;

    /**
     * @param Phrase $phrase
     * @param \Exception|null $cause
     * @param int $code
     * @param string|null $identifier
     * @param string|null $sku
     * @param string|null $operation
     */
    public function __construct(
        Phrase $phrase,
        ?\Exception $cause = null,
        int $code = 0,
        ?string $identifier = null,
        ?string $sku = null,
        ?string $operation = null
    ) {
        parent::__construct($phrase, $cause, $code);
        $this->identifier = $identifier;
        $this->sku = $sku;
        $this->operation = $operation;
    }

    /**
     * Create exception for product creation failure
     *
     * @param string $sku
     * @param string $error
     * @param \Exception|null $cause
     * @return self
     */
    public static function productCreationFailed(string $sku, string $error, ?\Exception $cause = null): self
    {
        return new self(
            __('Failed to create product %1: %2', $sku, $error),
            $cause,
            0,
            null,
            $sku,
            'create'
        );
    }

    /**
     * Create exception for product update failure
     *
     * @param string $sku
     * @param string $error
     * @param \Exception|null $cause
     * @return self
     */
    public static function productUpdateFailed(string $sku, string $error, ?\Exception $cause = null): self
    {
        return new self(
            __('Failed to update product %1: %2', $sku, $error),
            $cause,
            0,
            null,
            $sku,
            'update'
        );
    }

    /**
     * Create exception for show processing failure
     *
     * @param string $identifier
     * @param string $error
     * @param \Exception|null $cause
     * @return self
     */
    public static function showProcessingFailed(string $identifier, string $error, ?\Exception $cause = null): self
    {
        return new self(
            __('Failed to process show %1: %2', $identifier, $error),
            $cause,
            0,
            $identifier,
            null,
            'process_show'
        );
    }

    /**
     * Create exception for missing required data
     *
     * @param string $field
     * @param string|null $context
     * @return self
     */
    public static function missingRequiredData(string $field, ?string $context = null): self
    {
        $message = $context
            ? __('Missing required field "%1" in %2', $field, $context)
            : __('Missing required field "%1"', $field);

        return new self($message);
    }

    /**
     * Create exception for attribute error
     *
     * @param string $attributeCode
     * @param string $error
     * @return self
     */
    public static function attributeError(string $attributeCode, string $error): self
    {
        return new self(
            __('Attribute error for %1: %2', $attributeCode, $error),
            null,
            0,
            null,
            null,
            'set_attribute'
        );
    }

    /**
     * Get the show identifier
     *
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Get the product SKU
     *
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * Get the operation that failed
     *
     * @return string|null
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * Get exception details as array
     *
     * @return array
     */
    public function getDetails(): array
    {
        return [
            'identifier' => $this->identifier,
            'sku' => $this->sku,
            'operation' => $this->operation,
            'message' => $this->getMessage()
        ];
    }

    /**
     * Create exception for API failure.
     *
     * @param string $endpoint
     * @param int $statusCode
     * @param string $message
     * @return self
     */
    public static function apiError(string $endpoint, int $statusCode, string $message): self
    {
        return new self(__(
            'Archive.org API error for %1: HTTP %2 - %3',
            $endpoint,
            $statusCode,
            $message
        ));
    }

    /**
     * Create exception for rate limiting.
     *
     * @param int $retryAfterSeconds
     * @return self
     */
    public static function rateLimited(int $retryAfterSeconds): self
    {
        return new self(__(
            'Archive.org API rate limited. Retry after %1 seconds.',
            $retryAfterSeconds
        ));
    }

    /**
     * Create exception for corrupted progress file.
     *
     * @param string $file
     * @return self
     */
    public static function corruptedProgress(string $file): self
    {
        return new self(__('Progress file corrupted or unreadable: %1', $file));
    }
}
