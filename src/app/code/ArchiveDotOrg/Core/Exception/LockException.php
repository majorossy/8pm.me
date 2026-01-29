<?php
/**
 * ArchiveDotOrg Core Module
 */

declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

/**
 * Exception thrown when lock acquisition fails.
 */
class LockException extends ArchiveDotOrgException
{
    /**
     * Create exception for already-locked resource.
     *
     * @param string $type
     * @param string $resource
     * @param int|null $pid
     * @return self
     */
    public static function alreadyLocked(string $type, string $resource, ?int $pid = null): self
    {
        $message = $pid
            ? __('Cannot acquire %1 lock for %2 - already held by PID %3', $type, $resource, $pid)
            : __('Cannot acquire %1 lock for %2 - already locked', $type, $resource);

        return new self($message);
    }

    /**
     * Create exception for lock timeout.
     *
     * @param string $type
     * @param string $resource
     * @param int $waitedSeconds
     * @return self
     */
    public static function timeout(string $type, string $resource, int $waitedSeconds): self
    {
        return new self(__(
            'Timeout waiting for %1 lock on %2 after %3 seconds',
            $type,
            $resource,
            $waitedSeconds
        ));
    }
}
