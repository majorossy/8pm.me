<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Ui\Component\Listing\Column;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Options provider for show_year filter
 *
 * Fetches all option values from the show_year dropdown attribute
 */
class YearOptions implements OptionSourceInterface
{
    private ProductAttributeRepositoryInterface $attributeRepository;
    private ?array $options = null;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        if ($this->options === null) {
            $this->options = $this->getAttributeOptions('show_year');
        }

        return $this->options;
    }

    /**
     * Get attribute options as array
     *
     * @param string $attributeCode
     * @return array
     */
    private function getAttributeOptions(string $attributeCode): array
    {
        $options = [];

        try {
            $attribute = $this->attributeRepository->get($attributeCode);
            $source = $attribute->getSource();

            if ($source instanceof AbstractSource) {
                $allOptions = $source->getAllOptions(true, true);

                foreach ($allOptions as $option) {
                    if (isset($option['value']) && $option['value'] !== '') {
                        $options[] = [
                            'value' => $option['value'],
                            'label' => $option['label'],
                        ];
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            // Attribute doesn't exist, return empty options
        }

        // Sort by year descending for better UX
        usort($options, function ($a, $b) {
            return (int) $b['label'] <=> (int) $a['label'];
        });

        return $options;
    }
}
