<?php

declare(strict_types=1);

namespace EightPM\SongGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class SongTitle implements ResolverInterface
{
    private ProductResource $productResource;
    private EavConfig $eavConfig;

    public function __construct(
        ProductResource $productResource,
        EavConfig $eavConfig
    ) {
        $this->productResource = $productResource;
        $this->eavConfig = $eavConfig;
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
        $title = $product->getData('title');
        if ($title !== null) {
            return $title;
        }

        // Load directly from resource if not in model
        $entityId = $product->getId();
        if (!$entityId) {
            return null;
        }

        return $this->productResource->getAttributeRawValue(
            $entityId,
            'title',
            $context->getExtensionAttributes()->getStore()->getId()
        );
    }
}
