<?php
/**
 * Copyright © Archive.org Import. All rights reserved.
 */
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Exception thrown when lock cannot be acquired
 */
class LockException extends LocalizedException
{
}
