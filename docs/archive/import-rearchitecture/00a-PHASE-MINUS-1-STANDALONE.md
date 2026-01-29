# Phase -1: Standalone Fixes

**Timeline:** 1 day (4-6 hours)
**Status:** 🔴 Not Started
**Blockers:** None - do this FIRST
**Dependencies:** None - these fixes have no prerequisites

---

## Overview

These fixes can be done **independently** before starting Phase 0. They have no dependencies on other work and won't be affected by later phases.

Knock these out in a single focused session to clear the deck.

**Completion Criteria:**
- [ ] All 6 tasks complete
- [ ] All new files pass `bin/magento setup:di:compile`
- [ ] Feature flags accessible via admin config

---

## Task -1.1: Document SKU Format (Fix #6)

**Time:** 15 minutes
**File:** `src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php`

Add docblock to the SKU generation method:

```php
/**
 * Generate unique product SKU for a track.
 *
 * Format: {artist_code}-{show_identifier}-{track_num}
 *
 * Components:
 * - artist_code: Lowercase collection ID (e.g., "phish", "lettuce")
 * - show_identifier: Full Archive.org identifier (e.g., "phish2023-07-14")
 * - track_num: 2-digit zero-padded track number (e.g., "01", "12")
 *
 * Example: phish-phish2023-07-14-01
 *
 * Uniqueness is guaranteed by:
 * - Archive.org identifiers are globally unique
 * - Track numbers are unique within a show
 *
 * @param string $artistCode Lowercase artist/collection identifier
 * @param string $showIdentifier Archive.org item identifier
 * @param int $trackNumber Track position in show (1-based)
 * @return string Unique SKU
 */
private function generateSku(string $artistCode, string $showIdentifier, int $trackNumber): string
{
    return sprintf('%s-%s-%02d', $artistCode, $showIdentifier, $trackNumber);
}
```

- [ ] Add docblock to existing method
- [ ] Verify format matches actual implementation
- [ ] Add unit test for SKU uniqueness (optional)

---

## Task -1.2: Create Service Interfaces (Fix #8)

**Time:** 1 hour
**Location:** `src/app/code/ArchiveDotOrg/Core/Api/`

Create interfaces for planned services. These are empty shells now - implementations come in later phases.

### -1.2a: TrackMatcherServiceInterface

**File:** `src/app/code/ArchiveDotOrg/Core/Api/TrackMatcherServiceInterface.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;

/**
 * Service for matching track names from Archive.org to canonical track definitions.
 *
 * Uses hybrid matching: exact → alias → metaphone → limited fuzzy
 */
interface TrackMatcherServiceInterface
{
    /**
     * Match a track name to a canonical track for an artist.
     *
     * @param string $trackName Raw track name from Archive.org
     * @param string $artistKey Artist URL key (e.g., "lettuce")
     * @return MatchResultInterface|null Match result or null if no match
     */
    public function match(string $trackName, string $artistKey): ?MatchResultInterface;

    /**
     * Build matching indexes for an artist.
     *
     * @param string $artistKey Artist URL key
     * @return void
     */
    public function buildIndexes(string $artistKey): void;

    /**
     * Clear matching indexes to free memory.
     *
     * @param string|null $artistKey Specific artist or null for all
     * @return void
     */
    public function clearIndexes(?string $artistKey = null): void;
}
```

### -1.2b: MatchResultInterface

**File:** `src/app/code/ArchiveDotOrg/Core/Api/Data/MatchResultInterface.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

/**
 * Result of a track matching operation.
 */
interface MatchResultInterface
{
    public const MATCH_EXACT = 'exact';
    public const MATCH_ALIAS = 'alias';
    public const MATCH_METAPHONE = 'metaphone';
    public const MATCH_FUZZY = 'fuzzy';

    /**
     * Get the matched canonical track key.
     *
     * @return string
     */
    public function getTrackKey(): string;

    /**
     * Get the match type (exact, alias, metaphone, fuzzy).
     *
     * @return string
     */
    public function getMatchType(): string;

    /**
     * Get the confidence score (0-100).
     *
     * @return int
     */
    public function getConfidence(): int;
}
```

### -1.2c: ArtistConfigLoaderInterface

**File:** `src/app/code/ArchiveDotOrg/Core/Api/ArtistConfigLoaderInterface.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Loads and caches artist YAML configuration.
 */
interface ArtistConfigLoaderInterface
{
    /**
     * Load artist configuration from YAML file.
     *
     * @param string $artistKey Artist URL key (e.g., "lettuce")
     * @return array Parsed and validated configuration
     * @throws \ArchiveDotOrg\Core\Exception\ConfigurationException If YAML invalid
     */
    public function load(string $artistKey): array;

    /**
     * Get list of all available artist keys.
     *
     * @return string[]
     */
    public function getAvailableArtists(): array;

    /**
     * Clear cached configuration.
     *
     * @param string|null $artistKey Specific artist or null for all
     * @return void
     */
    public function clearCache(?string $artistKey = null): void;
}
```

### -1.2d: ArtistConfigValidatorInterface

**File:** `src/app/code/ArchiveDotOrg/Core/Api/ArtistConfigValidatorInterface.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Validates artist YAML configuration.
 */
interface ArtistConfigValidatorInterface
{
    /**
     * Validate artist configuration array.
     *
     * @param array $config Parsed YAML configuration
     * @return array{valid: bool, errors: string[], warnings: string[]}
     */
    public function validate(array $config): array;
}
```

### -1.2e: StringNormalizerInterface

**File:** `src/app/code/ArchiveDotOrg/Core/Api/StringNormalizerInterface.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

/**
 * Normalizes strings for matching (Unicode, whitespace, case).
 */
interface StringNormalizerInterface
{
    /**
     * Normalize a string for matching.
     *
     * Operations:
     * - NFD decomposition + accent removal
     * - Unicode dash → ASCII hyphen
     * - Whitespace normalization
     * - Lowercase
     *
     * @param string $input Raw input string
     * @return string Normalized string
     */
    public function normalize(string $input): string;
}
```

**Checklist:**
- [ ] Create Api/ directory if needed
- [ ] Create Api/Data/ directory
- [ ] Create all 5 interface files
- [ ] Run `bin/magento setup:di:compile` to verify syntax

---

## Task -1.3: Create Exception Hierarchy (Fix #14)

**Time:** 30 minutes
**Location:** `src/app/code/ArchiveDotOrg/Core/Exception/`

### -1.3a: Base Exception

**File:** `src/app/code/ArchiveDotOrg/Core/Exception/ArchiveDotOrgException.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

use Magento\Framework\Exception\LocalizedException;

/**
 * Base exception for all Archive.org module exceptions.
 */
class ArchiveDotOrgException extends LocalizedException
{
}
```

### -1.3b: Lock Exception (may already exist - verify)

**File:** `src/app/code/ArchiveDotOrg/Core/Exception/LockException.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

/**
 * Exception thrown when lock acquisition fails.
 */
class LockException extends ArchiveDotOrgException
{
    /**
     * Create exception for already-locked resource.
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
```

### -1.3c: Configuration Exception

**File:** `src/app/code/ArchiveDotOrg/Core/Exception/ConfigurationException.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

/**
 * Exception thrown when YAML or configuration is invalid.
 */
class ConfigurationException extends ArchiveDotOrgException
{
    /**
     * Create exception for missing required field.
     */
    public static function missingField(string $field, string $context = ''): self
    {
        $message = $context
            ? __('Missing required field "%1" in %2', $field, $context)
            : __('Missing required field "%1"', $field);

        return new self($message);
    }

    /**
     * Create exception for invalid field value.
     */
    public static function invalidValue(string $field, string $reason): self
    {
        return new self(__('Invalid value for "%1": %2', $field, $reason));
    }

    /**
     * Create exception for YAML parse error.
     */
    public static function yamlParseError(string $file, string $error): self
    {
        return new self(__('Failed to parse YAML file %1: %2', $file, $error));
    }
}
```

### -1.3d: Import Exception

**File:** `src/app/code/ArchiveDotOrg/Core/Exception/ImportException.php`

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

/**
 * Exception thrown during import operations.
 */
class ImportException extends ArchiveDotOrgException
{
    /**
     * Create exception for API failure.
     */
    public static function apiError(string $endpoint, int $statusCode, string $message): self
    {
        return new self(__(
            'Archive.org API error for %1: HTTP %2 - %3',
            $endpoint,
            $statusCode,
            $message
        ));
    }

    /**
     * Create exception for rate limiting.
     */
    public static function rateLimited(int $retryAfterSeconds): self
    {
        return new self(__(
            'Archive.org API rate limited. Retry after %1 seconds.',
            $retryAfterSeconds
        ));
    }

    /**
     * Create exception for corrupted progress file.
     */
    public static function corruptedProgress(string $file): self
    {
        return new self(__('Progress file corrupted or unreadable: %1', $file));
    }
}
```

**Checklist:**
- [ ] Create Exception/ directory if needed
- [ ] Create all 4 exception classes
- [ ] Check if LockException already exists (merge if so)
- [ ] Run `bin/magento setup:di:compile`

---

## Task -1.4: Add Feature Flags (Fix #12)

**Time:** 30 minutes

### -1.4a: Add to config.xml

**File:** `src/app/code/ArchiveDotOrg/Core/etc/config.xml`

Add inside `<default>`:

```xml
<default>
    <!-- Existing config... -->

    <archivedotorg>
        <general>
            <enabled>1</enabled>
        </general>
        <migration>
            <!-- Feature flags for gradual rollout -->
            <use_organized_folders>0</use_organized_folders>
            <use_yaml_config>0</use_yaml_config>
            <use_new_commands>0</use_new_commands>
            <dashboard_enabled>0</dashboard_enabled>
        </migration>
        <matching>
            <!-- Matching algorithm settings -->
            <use_hybrid_matching>1</use_hybrid_matching>
            <fuzzy_candidate_limit>5</fuzzy_candidate_limit>
            <min_fuzzy_score>80</min_fuzzy_score>
        </matching>
        <performance>
            <!-- Import settings -->
            <download_batch_size>100</download_batch_size>
            <populate_batch_size>500</populate_batch_size>
            <api_delay_ms>750</api_delay_ms>
            <progress_save_interval>10</progress_save_interval>
        </performance>
    </archivedotorg>
</default>
```

### -1.4b: Add system.xml for admin UI

**File:** `src/app/code/ArchiveDotOrg/Core/etc/adminhtml/system.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <tab id="archivedotorg" translate="label" sortOrder="500">
            <label>Archive.org</label>
        </tab>
        <section id="archivedotorg" translate="label" sortOrder="10"
                 showInDefault="1" showInWebsite="0" showInStore="0">
            <label>Import Settings</label>
            <tab>archivedotorg</tab>
            <resource>ArchiveDotOrg_Core::config</resource>

            <group id="general" translate="label" sortOrder="10" showInDefault="1">
                <label>General</label>
                <field id="enabled" translate="label" type="select" sortOrder="10" showInDefault="1">
                    <label>Enable Module</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
            </group>

            <group id="migration" translate="label" sortOrder="20" showInDefault="1">
                <label>Migration Feature Flags</label>
                <comment>Enable new features gradually during migration.</comment>
                <field id="use_organized_folders" translate="label comment" type="select" sortOrder="10" showInDefault="1">
                    <label>Use Organized Folders</label>
                    <comment>Store JSON files in artist subfolders</comment>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="use_yaml_config" translate="label comment" type="select" sortOrder="20" showInDefault="1">
                    <label>Use YAML Configuration</label>
                    <comment>Load artist config from YAML files instead of data patches</comment>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="use_new_commands" translate="label comment" type="select" sortOrder="30" showInDefault="1">
                    <label>Use New Commands</label>
                    <comment>Use archive:download/populate instead of legacy commands</comment>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="dashboard_enabled" translate="label comment" type="select" sortOrder="40" showInDefault="1">
                    <label>Enable Admin Dashboard</label>
                    <comment>Show Archive.org import dashboard in admin</comment>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
            </group>

            <group id="performance" translate="label" sortOrder="30" showInDefault="1">
                <label>Performance Settings</label>
                <field id="download_batch_size" translate="label comment" type="text" sortOrder="10" showInDefault="1">
                    <label>Download Batch Size</label>
                    <comment>Number of shows to download per batch (default: 100)</comment>
                    <validate>validate-digits</validate>
                </field>
                <field id="api_delay_ms" translate="label comment" type="text" sortOrder="20" showInDefault="1">
                    <label>API Delay (ms)</label>
                    <comment>Delay between API calls to avoid rate limiting (default: 750)</comment>
                    <validate>validate-digits</validate>
                </field>
            </group>
        </section>
    </system>
</config>
```

### -1.4c: Create Config Helper

**File:** `src/app/code/ArchiveDotOrg/Core/Model/Config.php` (add methods if file exists)

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Configuration helper for Archive.org module.
 */
class Config
{
    private const XML_PATH_PREFIX = 'archivedotorg/';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    // Feature flags
    public function useOrganizedFolders(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PREFIX . 'migration/use_organized_folders');
    }

    public function useYamlConfig(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PREFIX . 'migration/use_yaml_config');
    }

    public function useNewCommands(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PREFIX . 'migration/use_new_commands');
    }

    public function isDashboardEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PREFIX . 'migration/dashboard_enabled');
    }

    // Performance settings
    public function getDownloadBatchSize(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_PREFIX . 'performance/download_batch_size') ?: 100);
    }

    public function getPopulateBatchSize(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_PREFIX . 'performance/populate_batch_size') ?: 500);
    }

    public function getApiDelayMs(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_PREFIX . 'performance/api_delay_ms') ?: 750);
    }

    public function getProgressSaveInterval(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_PREFIX . 'performance/progress_save_interval') ?: 10);
    }

    // Matching settings
    public function useHybridMatching(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PREFIX . 'matching/use_hybrid_matching');
    }

    public function getFuzzyCandidateLimit(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_PREFIX . 'matching/fuzzy_candidate_limit') ?: 5);
    }

    public function getMinFuzzyScore(): int
    {
        return (int) ($this->scopeConfig->getValue(self::XML_PATH_PREFIX . 'matching/min_fuzzy_score') ?: 80);
    }
}
```

### -1.4d: Add ACL

**File:** `src/app/code/ArchiveDotOrg/Core/etc/acl.xml`

Add inside `<resources>`:

```xml
<resource id="ArchiveDotOrg_Core::config" title="Archive.org Configuration" sortOrder="10" />
```

**Checklist:**
- [ ] Update config.xml with feature flags
- [ ] Create system.xml for admin UI
- [ ] Create/update Config helper class
- [ ] Add ACL resource
- [ ] Run `bin/magento setup:upgrade`
- [ ] Run `bin/magento cache:flush`
- [ ] Verify: Admin > Stores > Configuration > Archive.org

---

## Task -1.5: Align Test Plan with Codebase (Fix #11)

**Time:** 1 hour
**File:** `docs/import-rearchitecture/07-PHASE-6-TESTING.md`

The test plan references classes that don't exist. Update to target actual code OR planned interfaces.

### Option A: Test Planned Interfaces (Recommended)

Update test targets to use interfaces created in Task -1.2:

| Original Target | New Target |
|-----------------|------------|
| `TrackMatcherServiceTest` | `TrackMatcherServiceInterface` implementation |
| `ArtistConfigValidatorTest` | `ArtistConfigValidatorInterface` implementation |
| `StringNormalizerTest` | `StringNormalizerInterface` implementation |

### Option B: Test Existing Code

If testing existing code before refactoring:

| Test | Actual Class |
|------|--------------|
| Track matching | `TrackPopulatorService::normalizeTitle()` |
| Config validation | `Config::getArtistMappings()` |
| String normalization | Inline in `TrackPopulatorService` |

**Update in Phase 6 doc:**
- [ ] Change test file paths to match interfaces
- [ ] Note that tests are written against interfaces
- [ ] Implementations tested when created in Phase 3

---

## Task -1.6: Performance Documentation Already Done (Fix #2)

**Status:** ✅ Already completed in FIXES.md

The incorrect performance claims (43 hours → 2-10 minutes) have been corrected in:
- FIXES.md - Fix #2 updated
- Phase 0 doc - Architecture decisions table

No action needed.

---

## Verification Checklist

Before moving to Phase 0:

```bash
# 1. Compile DI to verify all PHP files
bin/magento setup:di:compile

# 2. Check for syntax errors
bin/magento setup:upgrade

# 3. Clear caches
bin/magento cache:flush

# 4. Verify admin config section exists
# Navigate to: Admin > Stores > Configuration > Archive.org

# 5. Verify feature flags default to OFF
bin/magento config:show archivedotorg/migration/use_organized_folders
# Should return: 0

# 6. Check exception classes load
bin/magento dev:console
>>> new \ArchiveDotOrg\Core\Exception\LockException(__('test'));
```

---

## Files Created/Modified Summary

| File | Action |
|------|--------|
| `Model/TrackImporter.php` | Add docblock |
| `Api/TrackMatcherServiceInterface.php` | Create |
| `Api/Data/MatchResultInterface.php` | Create |
| `Api/ArtistConfigLoaderInterface.php` | Create |
| `Api/ArtistConfigValidatorInterface.php` | Create |
| `Api/StringNormalizerInterface.php` | Create |
| `Exception/ArchiveDotOrgException.php` | Create |
| `Exception/LockException.php` | Create or update |
| `Exception/ConfigurationException.php` | Create |
| `Exception/ImportException.php` | Create |
| `etc/config.xml` | Update |
| `etc/adminhtml/system.xml` | Create |
| `etc/acl.xml` | Update |
| `Model/Config.php` | Create or update |

---

## Next Phase

Once all tasks complete → [Phase 0: Critical Fixes](./01-PHASE-0-CRITICAL.md)
