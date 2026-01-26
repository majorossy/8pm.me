<?php

declare(strict_types=1);

namespace EightPM\SongGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ShowTaper implements ResolverInterface
{
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

        // show_taper is a dropdown attribute, get the label text
        $text = $product->getAttributeText('show_taper');
        if ($text) {
            return is_array($text) ? implode(', ', $text) : $text;
        }

        return null;
    }
}
