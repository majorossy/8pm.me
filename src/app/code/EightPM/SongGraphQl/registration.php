<?php
/**
 * EightPM SongGraphQl Module
 * Exposes custom song attributes in GraphQL API
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'EightPM_SongGraphQl',
    __DIR__
);
