<?php

declare(strict_types=1);

namespace EightPM\SongGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class SongDuration implements ResolverInterface
{
    private ProductResource $productResource;

    public function __construct(ProductResource $productResource)
    {
        $this->productResource = $productResource;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            return null;
        }

        $product = $value['model'];

        // Try to get from model first
        $length = $product->getData('length');

        // Load directly from resource if not in model
        if ($length === null) {
            $entityId = $product->getId();
            if (!$entityId) {
                return null;
            }
            $length = $this->productResource->getAttributeRawValue(
                $entityId,
                'length',
                $context->getExtensionAttributes()->getStore()->getId()
            );
        }

        if ($length === null) {
            return null;
        }

        // Parse mm:ss format to seconds, or return as-is if already numeric
        return $this->parseToSeconds($length);
    }

    /**
     * Parse duration string to seconds
     * Handles formats: "6:34" (mm:ss), "1:23:45" (h:mm:ss), or numeric seconds
     */
    private function parseToSeconds($length): float
    {
        $length = (string) $length;

        // If already numeric (seconds), return as float
        if (is_numeric($length)) {
            return (float) $length;
        }

        // Parse time format (mm:ss or h:mm:ss)
        $parts = explode(':', $length);
        $count = count($parts);

        if ($count === 2) {
            // mm:ss format
            return ((int) $parts[0] * 60) + (float) $parts[1];
        } elseif ($count === 3) {
            // h:mm:ss format
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (float) $parts[2];
        }

        // Fallback: try to parse as float
        return (float) $length;
    }
}
