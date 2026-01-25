<?php
/**
 * ArchiveDotOrg Core Module
 *
 * Enterprise-grade module for importing live music recordings from Archive.org
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'ArchiveDotOrg_Core',
    __DIR__
);
