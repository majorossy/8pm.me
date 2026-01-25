<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\AttributeOptionManagerInterface;
use ArchiveDotOrg\Core\Logger\Logger;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\Source\TableFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Attribute Option Manager Implementation
 *
 * Single source of truth for EAV attribute option management.
 * Replaces the duplicate createOrGetId methods in legacy commands.
 */
class AttributeOptionManager implements AttributeOptionManagerInterface
{
    private ProductAttributeRepositoryInterface $attributeRepository;
    private TableFactory $tableFactory;
    private AttributeOptionManagementInterface $attributeOptionManagement;
    private AttributeOptionLabelInterfaceFactory $optionLabelFactory;
    private AttributeOptionInterfaceFactory $optionFactory;
    private Logger $logger;

    /**
     * Internal cache of attribute options
     * Structure: [attributeId => [label => optionId]]
     *
     * @var array
     */
    private array $optionCache = [];

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param TableFactory $tableFactory
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param AttributeOptionLabelInterfaceFactory $optionLabelFactory
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param Logger $logger
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        TableFactory $tableFactory,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        AttributeOptionInterfaceFactory $optionFactory,
        Logger $logger
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->tableFactory = $tableFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->optionFactory = $optionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function getOrCreateOptionId(string $attributeCode, string $label): int
    {
        $label = trim($label);

        if ($label === '') {
            throw new LocalizedException(
                __('Label for attribute %1 must not be empty.', $attributeCode)
            );
        }

        // Check cache first
        $optionId = $this->getOptionId($attributeCode, $label);

        if ($optionId !== null) {
            return $optionId;
        }

        // Create new option
        return $this->createOption($attributeCode, $label);
    }

    /**
     * @inheritDoc
     */
    public function getOptionId(string $attributeCode, string $label): ?int
    {
        $attribute = $this->attributeRepository->get($attributeCode);
        $attributeId = (int) $attribute->getAttributeId();

        // Build cache if needed
        if (!isset($this->optionCache[$attributeId])) {
            $this->buildOptionCache($attributeCode, $attributeId);
        }

        return $this->optionCache[$attributeId][$label] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function createOption(string $attributeCode, string $label): int
    {
        $attribute = $this->attributeRepository->get($attributeCode);
        $attributeId = (int) $attribute->getAttributeId();

        // Create option label
        $optionLabel = $this->optionLabelFactory->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($label);

        // Create option
        $option = $this->optionFactory->create();
        $option->setLabel($label);
        $option->setStoreLabels([$optionLabel]);
        $option->setSortOrder(0);
        $option->setIsDefault(false);

        // Add to attribute
        $this->attributeOptionManagement->add(
            Product::ENTITY,
            $attributeId,
            $option
        );

        // Refresh cache and get new ID
        $this->buildOptionCache($attributeCode, $attributeId, true);

        $optionId = $this->optionCache[$attributeId][$label] ?? null;

        if ($optionId === null) {
            throw new LocalizedException(
                __('Failed to create option "%1" for attribute %2', $label, $attributeCode)
            );
        }

        $this->logger->debug('Created attribute option', [
            'attribute' => $attributeCode,
            'label' => $label,
            'option_id' => $optionId
        ]);

        return $optionId;
    }

    /**
     * @inheritDoc
     */
    public function optionExists(string $attributeCode, string $label): bool
    {
        return $this->getOptionId($attributeCode, $label) !== null;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions(string $attributeCode): array
    {
        $attribute = $this->attributeRepository->get($attributeCode);
        $attributeId = (int) $attribute->getAttributeId();

        if (!isset($this->optionCache[$attributeId])) {
            $this->buildOptionCache($attributeCode, $attributeId);
        }

        $options = [];
        foreach ($this->optionCache[$attributeId] as $label => $value) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function clearCache(?string $attributeCode = null): void
    {
        if ($attributeCode === null) {
            $this->optionCache = [];
            return;
        }

        try {
            $attribute = $this->attributeRepository->get($attributeCode);
            $attributeId = (int) $attribute->getAttributeId();
            unset($this->optionCache[$attributeId]);
        } catch (\Exception $e) {
            // Attribute doesn't exist, nothing to clear
        }
    }

    /**
     * @inheritDoc
     */
    public function bulkGetOrCreateOptionIds(string $attributeCode, array $labels): array
    {
        $result = [];
        $labelsToCreate = [];

        // First pass: check what exists
        foreach ($labels as $label) {
            $label = trim($label);
            if ($label === '') {
                continue;
            }

            $optionId = $this->getOptionId($attributeCode, $label);
            if ($optionId !== null) {
                $result[$label] = $optionId;
            } else {
                $labelsToCreate[] = $label;
            }
        }

        // Second pass: create missing options
        foreach ($labelsToCreate as $label) {
            $result[$label] = $this->createOption($attributeCode, $label);
        }

        return $result;
    }

    /**
     * Build the options cache for an attribute
     *
     * @param string $attributeCode
     * @param int $attributeId
     * @param bool $force
     * @return void
     */
    private function buildOptionCache(string $attributeCode, int $attributeId, bool $force = false): void
    {
        if (!$force && isset($this->optionCache[$attributeId])) {
            return;
        }

        $this->optionCache[$attributeId] = [];

        $attribute = $this->attributeRepository->get($attributeCode);

        // Create a fresh source model to avoid stale cache
        $sourceModel = $this->tableFactory->create();
        $sourceModel->setAttribute($attribute);

        foreach ($sourceModel->getAllOptions() as $option) {
            $label = $option['label'] ?? '';
            $value = $option['value'] ?? '';

            if ($label !== '' && $value !== '') {
                $this->optionCache[$attributeId][$label] = (int) $value;
            }
        }
    }
}
