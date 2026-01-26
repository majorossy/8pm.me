<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Attribute Option Manager Interface
 *
 * Unified service for managing EAV attribute options.
 * Consolidates the duplicate createOrGetId logic from legacy modules.
 */
interface AttributeOptionManagerInterface
{
    /**
     * Find or create an attribute option and return its ID
     *
     * @param string $attributeCode The attribute code (e.g., 'show_year', 'show_venue')
     * @param string $label The option label to find or create
     * @return int The option ID
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrCreateOptionId(string $attributeCode, string $label): int;

    /**
     * Get option ID for an existing label
     *
     * @param string $attributeCode
     * @param string $label
     * @return int|null Returns null if option doesn't exist
     */
    public function getOptionId(string $attributeCode, string $label): ?int;

    /**
     * Create a new option
     *
     * @param string $attributeCode
     * @param string $label
     * @return int The new option ID
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOption(string $attributeCode, string $label): int;

    /**
     * Check if an option exists
     *
     * @param string $attributeCode
     * @param string $label
     * @return bool
     */
    public function optionExists(string $attributeCode, string $label): bool;

    /**
     * Get all options for an attribute
     *
     * @param string $attributeCode
     * @return array Array of ['value' => id, 'label' => label]
     */
    public function getAllOptions(string $attributeCode): array;

    /**
     * Clear the internal cache for an attribute (or all if null)
     *
     * @param string|null $attributeCode
     * @return void
     */
    public function clearCache(?string $attributeCode = null): void;

    /**
     * Bulk create options
     *
     * @param string $attributeCode
     * @param string[] $labels
     * @return array Map of label => option ID
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function bulkGetOrCreateOptionIds(string $attributeCode, array $labels): array;
}
