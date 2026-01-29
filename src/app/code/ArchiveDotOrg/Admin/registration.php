<?php
/**
 * Archive.org Admin Module Registration
 *
 * Provides admin dashboard, grids, and monitoring for Archive.org imports.
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'ArchiveDotOrg_Admin',
    __DIR__
);
