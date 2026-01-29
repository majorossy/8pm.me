<?php

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Console\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Lightweight base class for read-only commands
 *
 * Use this for commands that don't modify data and don't need correlation ID tracking:
 * - archive:status
 * - archive:show-unmatched
 * - archive:validate
 *
 * Commands that modify data (download, populate, import) should extend BaseLoggedCommand instead.
 */
abstract class BaseReadCommand extends Command
{
    // No additional functionality needed - just a semantic marker
    // Subclasses implement execute() directly as usual
}
