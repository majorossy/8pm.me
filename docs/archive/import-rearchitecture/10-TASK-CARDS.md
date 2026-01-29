# Task Cards for Agent Swarming

**Usage:** Copy a task card and paste it as the initial prompt for a new Claude session/agent.

**Important:** Each agent should work on ONE card at a time. Cards are designed to be independent within the same phase.

---

# Phase -1: Standalone Fixes (Day 1)

**Prerequisites:** None - start here
**Parallelism:** 3 agents can work simultaneously

---

## Card -1.A: Interfaces & SKU Documentation

**Assigned to:** Agent A
**Time estimate:** 1.5 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`

Read the full task details:
- `docs/import-rearchitecture/00a-PHASE-MINUS-1-STANDALONE.md` (Tasks -1.1 and -1.2)

### Goal

1. Document the SKU generation format in TrackImporter
2. Create 5 service interfaces for planned services

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Api/
├── TrackMatcherServiceInterface.php
├── ArtistConfigLoaderInterface.php
├── ArtistConfigValidatorInterface.php
├── StringNormalizerInterface.php
└── Data/
    └── MatchResultInterface.php
```

### File to Modify

```
src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php
```
- Add docblock to SKU generation method explaining format: `{artist_code}-{show_identifier}-{track_num}`

### Implementation Details

**TrackMatcherServiceInterface.php:**
```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api;

use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;

interface TrackMatcherServiceInterface
{
    public function match(string $trackName, string $artistKey): ?MatchResultInterface;
    public function buildIndexes(string $artistKey): void;
    public function clearIndexes(?string $artistKey = null): void;
}
```

**MatchResultInterface.php:**
```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Api\Data;

interface MatchResultInterface
{
    public const MATCH_EXACT = 'exact';
    public const MATCH_ALIAS = 'alias';
    public const MATCH_METAPHONE = 'metaphone';
    public const MATCH_FUZZY = 'fuzzy';

    public function getTrackKey(): string;
    public function getMatchType(): string;
    public function getConfidence(): int;
}
```

See the phase doc for full interface definitions.

### Success Criteria

```bash
# All files exist
ls src/app/code/ArchiveDotOrg/Core/Api/*.php
ls src/app/code/ArchiveDotOrg/Core/Api/Data/*.php

# DI compiles without errors
bin/magento setup:di:compile
```

### Do NOT

- Create implementations (just interfaces)
- Work on exception classes (that's Agent B)
- Work on feature flags (that's Agent B)
- Modify di.xml (not needed yet)

---

## Card -1.B: Exceptions & Feature Flags

**Assigned to:** Agent B
**Time estimate:** 1 hour
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`

Read the full task details:
- `docs/import-rearchitecture/00a-PHASE-MINUS-1-STANDALONE.md` (Tasks -1.3 and -1.4)

### Goal

1. Create exception class hierarchy
2. Add feature flags for gradual rollout

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Exception/
├── ArchiveDotOrgException.php      (base exception)
├── LockException.php               (check if exists first - may need merge)
├── ConfigurationException.php
└── ImportException.php

src/app/code/ArchiveDotOrg/Core/etc/adminhtml/
└── system.xml

src/app/code/ArchiveDotOrg/Core/Model/
└── Config.php                      (create or update)
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/etc/config.xml    (add feature flags)
src/app/code/ArchiveDotOrg/Core/etc/acl.xml       (add config resource)
```

### Implementation Details

**Exception base class:**
```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Exception;

use Magento\Framework\Exception\LocalizedException;

class ArchiveDotOrgException extends LocalizedException
{
}
```

**LockException with factory methods:**
```php
public static function alreadyLocked(string $type, string $resource, ?int $pid = null): self
public static function timeout(string $type, string $resource, int $waitedSeconds): self
```

**Feature flags in config.xml:**
```xml
<archivedotorg>
    <migration>
        <use_organized_folders>0</use_organized_folders>
        <use_yaml_config>0</use_yaml_config>
        <use_new_commands>0</use_new_commands>
        <dashboard_enabled>0</dashboard_enabled>
    </migration>
</archivedotorg>
```

**Config helper methods:**
```php
public function useOrganizedFolders(): bool
public function useYamlConfig(): bool
public function useNewCommands(): bool
public function isDashboardEnabled(): bool
```

See the phase doc for full code.

### Success Criteria

```bash
# Exception classes exist
ls src/app/code/ArchiveDotOrg/Core/Exception/*.php

# Config compiles
bin/magento setup:upgrade
bin/magento cache:flush

# Feature flags accessible
bin/magento config:show archivedotorg/migration/use_organized_folders
# Should return: 0

# Admin config section exists
# Navigate to: Admin > Stores > Configuration > Archive.org
```

### Do NOT

- Create service interfaces (that's Agent A)
- Modify any CLI commands
- Enable any feature flags (leave at 0)

---

## Card -1.C: Test Plan Alignment

**Assigned to:** Agent C
**Time estimate:** 1 hour
**Worktree:** `agent-c`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-c/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`

Read the full task details:
- `docs/import-rearchitecture/00a-PHASE-MINUS-1-STANDALONE.md` (Task -1.5)
- `docs/import-rearchitecture/07-PHASE-6-TESTING.md`

### Goal

Review and update the test plan (Phase 6 doc) to:
1. Target actual existing classes OR planned interfaces
2. Ensure test file paths are correct
3. Add missing test cases for error handling

### Files to Modify

```
docs/import-rearchitecture/07-PHASE-6-TESTING.md
```

### Analysis Required

First, explore the codebase to understand what exists:

```bash
# Find existing test files
find src -name "*Test.php" -type f

# Find existing service classes
ls src/app/code/ArchiveDotOrg/Core/Model/*.php

# Check what TrackPopulatorService has
grep -n "function" src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php
```

### Updates Needed

The test plan references classes that may not exist:
- `TrackMatcherService` → May need to test `TrackPopulatorService.normalizeTitle()` instead
- `ArtistConfigValidator` → May need to test `Config.getArtistMappings()` instead
- `StringNormalizer` → May be inline in `TrackPopulatorService`

Update the test plan to:
1. Reference actual class names
2. Reference planned interfaces (from Card -1.A)
3. Note which tests target existing code vs. planned code

### Success Criteria

- Phase 6 doc accurately reflects what will be tested
- Test file paths match actual Magento test directory structure
- Each test has clear class/method targets

### Do NOT

- Write actual test code (that's Phase 6)
- Modify PHP files
- Create new documentation files

---

# Phase 0: Critical Fixes (Week 1-2)

**Prerequisites:** Phase -1 complete, DI compiles
**Parallelism:** 4 agents can work simultaneously

---

## Card 0.A: Database Foundation

**Assigned to:** Agent A (Database specialist)
**Time estimate:** 6-8 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/01-PHASE-0-CRITICAL.md` (Tasks 0.1-0.4)
- `docs/import-rearchitecture/FIXES.md` (Fixes #1, #7, #18, #19, #34-37)

### Goal

Create database infrastructure:
1. Artist normalization table
2. Dashboard performance indexes
3. Convert TEXT columns to JSON type
4. Extract large JSON from EAV to separate table

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/
├── CreateArtistTable.php
├── AddDashboardIndexes.php
├── ConvertJsonColumns.php
└── CreateShowMetadataTable.php

migrations/
├── 001_create_artist_table.sql
├── 002_add_dashboard_indexes.sql
├── 003_convert_json_columns.sql
├── 004_create_show_metadata_table.sql
└── rollback/
    ├── 001_drop_artist_table.sql
    ├── 002_drop_dashboard_indexes.sql
    ├── 003_revert_json_columns.sql
    └── 004_drop_show_metadata_table.sql
```

### Implementation Details

**Artist table (001):**
```sql
CREATE TABLE archivedotorg_artist (
    artist_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artist_name VARCHAR(255) NOT NULL UNIQUE,
    collection_id VARCHAR(255) NOT NULL UNIQUE,
    url_key VARCHAR(255) NOT NULL,
    yaml_file_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_collection (collection_id),
    INDEX idx_url_key (url_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Dashboard indexes (002):**
```sql
CREATE INDEX idx_created_at ON catalog_product_entity (created_at);
CREATE INDEX idx_artist_status_started ON archivedotorg_import_run (artist_id, status, started_at DESC);
CREATE INDEX idx_correlation_id ON archivedotorg_import_run (correlation_id);
```

**JSON columns (003):**
```sql
ALTER TABLE archivedotorg_import_run
  MODIFY options_json JSON NULL,
  MODIFY command_args JSON NULL;
```

**Show metadata table (004):**
```sql
CREATE TABLE archivedotorg_show_metadata (
    metadata_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    show_identifier VARCHAR(255) NOT NULL UNIQUE,
    artist_id INT UNSIGNED NOT NULL,
    reviews_json JSON,
    workable_servers JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Success Criteria

```bash
# Schema patches compile
bin/magento setup:di:compile

# Run migrations (in dev)
bin/magento setup:upgrade

# Verify tables
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';"

# Verify indexes
bin/mysql -e "SHOW INDEX FROM catalog_product_entity WHERE Key_name = 'idx_created_at';"

# Verify JSON type
bin/mysql -e "SELECT DATA_TYPE FROM information_schema.columns WHERE table_name='archivedotorg_import_run' AND column_name='options_json';"
# Should return: json
```

### Do NOT

- Modify existing model/service files
- Create repository classes (that's Phase 5)
- Run migrations on production

---

## Card 0.B: Concurrency & Safety

**Assigned to:** Agent B (PHP specialist)
**Time estimate:** 4-6 hours
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/01-PHASE-0-CRITICAL.md` (Tasks 0.5-0.7)
- `docs/import-rearchitecture/FIXES.md` (Fixes #3, #4, #10, #23, #24, #39)

### Goal

1. Integrate LockService into DI (already exists, needs registration)
2. Add atomic progress file writes
3. Add progress file validation with fallback

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/etc/di.xml                    (add preference)
src/app/code/ArchiveDotOrg/Core/Model/MetadataDownloader.php  (atomic writes)
src/app/code/ArchiveDotOrg/Core/Model/ProgressTracker.php     (atomic writes)
src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadMetadataCommand.php (validation)
```

### Files to Check First

```bash
# LockService should already exist
cat src/app/code/ArchiveDotOrg/Core/Model/LockService.php

# Check for LockServiceInterface
cat src/app/code/ArchiveDotOrg/Core/Api/LockServiceInterface.php

# If interface doesn't exist, check if Model/LockException.php exists
ls src/app/code/ArchiveDotOrg/Core/Model/LockException.php
```

### Implementation Details

**di.xml preference:**
```xml
<preference for="ArchiveDotOrg\Core\Api\LockServiceInterface"
            type="ArchiveDotOrg\Core\Model\LockService"/>
```

**Atomic write pattern:**
```php
private function atomicWrite(string $filePath, string $content): void
{
    $tmpFile = $filePath . '.tmp.' . getmypid();

    if (file_put_contents($tmpFile, $content) === false) {
        throw new \RuntimeException("Failed to write temp file: $tmpFile");
    }

    // Sync to disk before rename
    $fp = fopen($tmpFile, 'r');
    if ($fp) {
        fsync($fp);
        fclose($fp);
    }

    // Atomic rename
    if (!rename($tmpFile, $filePath)) {
        @unlink($tmpFile);
        throw new \RuntimeException("Failed to rename: $tmpFile -> $filePath");
    }
}
```

**Progress validation with fallback:**
```php
private function loadProgress(string $progressFile): array
{
    if (!file_exists($progressFile)) {
        return ['completed' => [], 'version' => 2];
    }

    $content = file_get_contents($progressFile);
    $progress = json_decode($content, true);

    if ($progress === null || !isset($progress['completed'])) {
        $this->logger->warning('Progress file corrupted, scanning filesystem');
        return $this->scanFilesystemForProgress();
    }

    return $progress;
}
```

### Success Criteria

```bash
# DI compiles with LockService preference
bin/magento setup:di:compile

# Test atomic write survives kill
php -r "
  file_put_contents('/tmp/test.json.tmp.123', 'test');
  rename('/tmp/test.json.tmp.123', '/tmp/test.json');
  echo file_get_contents('/tmp/test.json');
"

# Test lock acquisition
bin/magento archivedotorg:download-metadata "TestArtist" --limit=1 &
bin/magento archivedotorg:download-metadata "TestArtist" --limit=1
# Second should fail with lock error
```

### Do NOT

- Rewrite LockService from scratch (it exists)
- Add Redis locking yet (test flock first per Phase 0 instructions)
- Modify CLI command signatures

---

## Card 0.C: Data Integrity

**Assigned to:** Agent C
**Time estimate:** 3-4 hours
**Worktree:** `agent-c`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-c/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/01-PHASE-0-CRITICAL.md` (Tasks 0.8-0.10)
- `docs/import-rearchitecture/FIXES.md` (Fixes #6, #21)

### Goal

1. Document SKU generation format (if not done in Phase -1)
2. Add category duplication check
3. Enforce fuzzy matching disabled by default

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Model/CategoryService.php    (or modify existing)
src/app/code/ArchiveDotOrg/Core/Model/ArtistConfigValidator.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php     (SKU docblock if needed)
```

### Implementation Details

**CategoryService - findByUrlKeyAndParent:**
```php
public function findByUrlKeyAndParent(string $urlKey, int $parentId): ?CategoryInterface
{
    $collection = $this->categoryCollectionFactory->create();
    $collection->addAttributeToFilter('url_key', $urlKey)
               ->addAttributeToFilter('parent_id', $parentId)
               ->setPageSize(1);

    return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
}

public function createIfNotExists(string $name, string $urlKey, int $parentId): CategoryInterface
{
    $existing = $this->findByUrlKeyAndParent($urlKey, $parentId);
    if ($existing) {
        return $existing;
    }

    // Create new category...
}
```

**ArtistConfigValidator - fuzzy check:**
```php
public function validate(array $config): array
{
    $errors = [];
    $warnings = [];

    // Check for fuzzy matching (should be disabled)
    if (!empty($config['matching']['fuzzy_enabled'])) {
        $errors[] = 'fuzzy_enabled is true - this is disabled by default. Use --enable-fuzzy CLI flag if needed.';
    }

    // Required fields
    if (empty($config['artist']['name'])) {
        $errors[] = 'artist.name is required';
    }

    if (empty($config['artist']['collection_id'])) {
        $errors[] = 'artist.collection_id is required';
    }

    // ... more validation

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}
```

### Success Criteria

```bash
# Category service works
bin/magento dev:console
>>> $service = \Magento\Framework\App\ObjectManager::getInstance()->get(\ArchiveDotOrg\Core\Model\CategoryService::class);
>>> $service->findByUrlKeyAndParent('lettuce', 2);

# Setup command is idempotent
bin/magento archivedotorg:setup lettuce
bin/magento archivedotorg:setup lettuce
# Second run should say "0 new categories created"
```

### Do NOT

- Implement the full YAML loader (that's Phase 2)
- Modify category structure
- Create new CLI commands

---

## Card 0.D: Soundex Matching Service

**Assigned to:** Agent D (Algorithm specialist)
**Time estimate:** 4-6 hours
**Worktree:** `agent-d`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-d/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/01-PHASE-0-CRITICAL.md` (Task 0.11)
- `docs/import-rearchitecture/FIXES.md` (Fix #41 - hybrid matching algorithm)

### Goal

Create TrackMatcherService with hybrid matching:
1. Exact match (hash lookup) - O(1)
2. Alias match (from config) - O(n)
3. Metaphone phonetic match - O(1)
4. Limited fuzzy on top 5 candidates only

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Model/TrackMatcherService.php
src/app/code/ArchiveDotOrg/Core/Model/Data/MatchResult.php
src/app/code/ArchiveDotOrg/Core/Model/StringNormalizer.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/etc/di.xml    (add preferences)
```

### Implementation Details

**TrackMatcherService:**
```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface;
use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;
use ArchiveDotOrg\Core\Model\Data\MatchResult;

class TrackMatcherService implements TrackMatcherServiceInterface
{
    private array $exactIndex = [];
    private array $aliasIndex = [];
    private array $metaphoneIndex = [];
    private array $allTracks = [];

    public function __construct(
        private readonly StringNormalizer $normalizer,
        private readonly Config $config
    ) {}

    public function match(string $trackName, string $artistKey): ?MatchResultInterface
    {
        $this->ensureIndexed($artistKey);
        $normalized = $this->normalizer->normalize($trackName);

        // 1. Exact match - O(1)
        if (isset($this->exactIndex[$artistKey][$normalized])) {
            return new MatchResult(
                $this->exactIndex[$artistKey][$normalized],
                MatchResultInterface::MATCH_EXACT,
                100
            );
        }

        // 2. Alias match - O(1) lookup
        if (isset($this->aliasIndex[$artistKey][$normalized])) {
            return new MatchResult(
                $this->aliasIndex[$artistKey][$normalized],
                MatchResultInterface::MATCH_ALIAS,
                95
            );
        }

        // 3. Metaphone match - O(1)
        $metaphone = metaphone($normalized);
        if (isset($this->metaphoneIndex[$artistKey][$metaphone])) {
            return new MatchResult(
                $this->metaphoneIndex[$artistKey][$metaphone],
                MatchResultInterface::MATCH_METAPHONE,
                85
            );
        }

        // 4. Limited fuzzy - top 5 candidates only
        $candidate = $this->fuzzyMatchTopCandidates($normalized, $artistKey, 5);
        if ($candidate && $candidate['score'] >= 80) {
            return new MatchResult(
                $candidate['track'],
                MatchResultInterface::MATCH_FUZZY,
                $candidate['score']
            );
        }

        return null;
    }

    public function buildIndexes(string $artistKey): void
    {
        // Load tracks from config/YAML
        $tracks = $this->config->getTracksForArtist($artistKey);

        foreach ($tracks as $track) {
            $normalized = $this->normalizer->normalize($track['name']);

            // Exact index
            $this->exactIndex[$artistKey][$normalized] = $track['key'];

            // Alias index
            foreach ($track['aliases'] ?? [] as $alias) {
                $normalizedAlias = $this->normalizer->normalize($alias);
                $this->aliasIndex[$artistKey][$normalizedAlias] = $track['key'];
            }

            // Metaphone index
            $this->metaphoneIndex[$artistKey][metaphone($normalized)] = $track['key'];

            // Keep all for fuzzy fallback
            $this->allTracks[$artistKey][$track['key']] = $normalized;
        }
    }

    public function clearIndexes(?string $artistKey = null): void
    {
        if ($artistKey === null) {
            $this->exactIndex = [];
            $this->aliasIndex = [];
            $this->metaphoneIndex = [];
            $this->allTracks = [];
        } else {
            unset(
                $this->exactIndex[$artistKey],
                $this->aliasIndex[$artistKey],
                $this->metaphoneIndex[$artistKey],
                $this->allTracks[$artistKey]
            );
        }
    }

    private function fuzzyMatchTopCandidates(string $input, string $artistKey, int $limit): ?array
    {
        $candidates = [];
        $inputMetaphone = metaphone($input);

        foreach ($this->allTracks[$artistKey] ?? [] as $key => $normalized) {
            // Only consider tracks with similar metaphone
            if (levenshtein($inputMetaphone, metaphone($normalized)) <= 2) {
                $candidates[$key] = $normalized;
                if (count($candidates) >= $limit) {
                    break;
                }
            }
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $key => $normalized) {
            similar_text($input, $normalized, $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = ['track' => $key, 'score' => (int) $score];
            }
        }

        return $bestMatch;
    }

    private function ensureIndexed(string $artistKey): void
    {
        if (!isset($this->exactIndex[$artistKey])) {
            $this->buildIndexes($artistKey);
        }
    }
}
```

**StringNormalizer:**
```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model;

use ArchiveDotOrg\Core\Api\StringNormalizerInterface;

class StringNormalizer implements StringNormalizerInterface
{
    public function normalize(string $input): string
    {
        // 1. NFD decomposition + remove accents
        if (class_exists('Normalizer')) {
            $input = \Normalizer::normalize($input, \Normalizer::NFD);
            $input = preg_replace('/[\x{0300}-\x{036f}]/u', '', $input);
        }

        // 2. Convert unicode dashes to ASCII
        $input = str_replace(['—', '–', '→', '−'], ['-', '-', '>', '-'], $input);

        // 3. Normalize whitespace
        $input = preg_replace('/\s+/', ' ', trim($input));

        // 4. Lowercase
        return mb_strtolower($input, 'UTF-8');
    }
}
```

**MatchResult DTO:**
```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Model\Data;

use ArchiveDotOrg\Core\Api\Data\MatchResultInterface;

class MatchResult implements MatchResultInterface
{
    public function __construct(
        private readonly string $trackKey,
        private readonly string $matchType,
        private readonly int $confidence
    ) {}

    public function getTrackKey(): string
    {
        return $this->trackKey;
    }

    public function getMatchType(): string
    {
        return $this->matchType;
    }

    public function getConfidence(): int
    {
        return $this->confidence;
    }
}
```

### Success Criteria

```bash
# DI compiles
bin/magento setup:di:compile

# Unit test matching
bin/magento dev:console
>>> $matcher = \Magento\Framework\App\ObjectManager::getInstance()->get(\ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface::class);
>>> $result = $matcher->match('Tweezer', 'phish');
>>> echo $result?->getMatchType();  // Should be 'exact' or 'metaphone'

# Test soundex collision handling
>>> $matcher->match('Twezer', 'phish');  // Misspelling should match via metaphone
```

### Do NOT

- Implement full Levenshtein on entire catalog (too slow)
- Create CLI commands (that's Phase 3)
- Load from YAML files yet (use Config class)

---

# Phase 1, 2, 4: Parallel Execution (Week 3-5)

These three phases can run simultaneously after Phase 0 completes.

---

## Card 1.A: Folder Migration Command

**Assigned to:** Agent A
**Time estimate:** 6-8 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/02-PHASE-1-FOLDERS.md` (All tasks)
- `docs/import-rearchitecture/FIXES.md` (Fixes #13, #22, #26)

### Goal

1. Create folder migration command
2. Update MetadataDownloader for subfolders
3. Create file manifest service
4. Create cache cleanup command

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Console/Command/MigrateOrganizeFoldersCommand.php
src/app/code/ArchiveDotOrg/Core/Console/Command/CleanupCacheCommand.php
src/app/code/ArchiveDotOrg/Core/Model/FileManifestService.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Model/MetadataDownloader.php
src/app/code/ArchiveDotOrg/Core/etc/di.xml
```

### Key Implementation Points

**Folder structure change:**
- Current: `var/archivedotorg/metadata/*.json` (flat)
- Target: `var/archivedotorg/metadata/{Artist}/*.json` (organized)

**Migration command features:**
- Backup to `metadata.backup/` first
- Crash-safe with progress tracking
- Dry-run mode
- Quarantine unmappable files to `/unmapped/`

**MetadataDownloader update:**
```php
// Before
return $this->basePath . '/' . $identifier . '.json';

// After
return $this->basePath . '/' . $collectionId . '/' . $identifier . '.json';
```

### Success Criteria

```bash
# Commands registered
bin/magento list | grep archive:migrate
bin/magento list | grep archive:cleanup

# Dry run works
bin/magento archive:migrate:organize-folders --dry-run

# Migration preserves file count
find var/archivedotorg/metadata -name "*.json" | wc -l
# Before and after should match (minus any quarantined)
```

### Do NOT

- Delete original files without backup
- Modify database schemas
- Work on YAML system (that's Phase 2)

---

## Card 2.A: YAML Infrastructure

**Assigned to:** Agent B
**Time estimate:** 10-12 hours
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/03-PHASE-2-YAML.md` (All tasks)
- `docs/import-rearchitecture/FIXES.md` (Fixes #17, #29, #45-47)

### Goal

1. Create YAML schema validator
2. Create YAML loader with caching
3. Define YAML structure template
4. Create validate command
5. Create export command
6. Create setup command

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Model/ArtistConfigLoader.php
src/app/code/ArchiveDotOrg/Core/Model/ArtistConfigValidator.php  (extend from Phase 0)
src/app/code/ArchiveDotOrg/Core/Console/Command/ValidateArtistCommand.php
src/app/code/ArchiveDotOrg/Core/Console/Command/MigrateExportCommand.php
src/app/code/ArchiveDotOrg/Core/Console/Command/SetupArtistCommand.php
src/app/code/ArchiveDotOrg/Core/config/artists/template.yaml
```

### Key Implementation Points

**YAML structure (FINAL - from FIXES.md):**
```yaml
artist:
  name: "Lettuce"
  collection_id: "Lettuce"
  url_key: "lettuce"

albums:
  - key: "outta-here"
    name: "Outta Here"
    url_key: "outta-here"
    year: 2002
    type: "studio"
  - key: "live-only"
    name: "Live Repertoire"
    type: "virtual"

tracks:
  - key: "phyllis"
    name: "Phyllis"
    url_key: "phyllis"
    albums: ["outta-here"]
    canonical_album: "outta-here"
    aliases: ["phillis", "philis"]
    type: "original"

medleys:
  - pattern: "Phyllis > Sam Huff"
    tracks: ["phyllis", "sam-huff"]
    separator: ">"
```

**Validation rules:**
- Required: `artist.name`, `artist.collection_id`
- `url_key` format: lowercase alphanumeric + hyphens
- No duplicate track keys
- All `canonical_album` must exist in `albums` array
- No empty aliases arrays

### Success Criteria

```bash
# Commands registered
bin/magento list | grep archive:validate
bin/magento list | grep archive:setup

# Template exists
cat src/app/code/ArchiveDotOrg/Core/config/artists/template.yaml

# Validation works
bin/magento archive:validate template
# Should pass with 0 errors

# Export works (after data patches analyzed)
bin/magento archive:migrate:export --dry-run
```

### Do NOT

- Delete existing data patches yet
- Modify TrackMatcherService (that's Phase 0/3)
- Create admin UI (that's Phase 5)

---

## Card 4.A: Extended Attributes

**Assigned to:** Agent C
**Time estimate:** 8-10 hours
**Worktree:** `agent-c`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-c/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/05-PHASE-4-ATTRIBUTES.md` (All tasks)

### Goal

1. Create extended EAV attributes
2. Update Track and Show DTOs
3. Update JSON parser to extract new fields
4. Update importers to save new attributes
5. Create ShowMetadataRepository

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddExtendedArchiveAttributes.php
src/app/code/ArchiveDotOrg/Core/Model/ShowMetadataRepository.php
src/app/code/ArchiveDotOrg/Core/Model/ShowMetadata.php
src/app/code/ArchiveDotOrg/Core/Model/ResourceModel/ShowMetadata.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Model/Data/Track.php
src/app/code/ArchiveDotOrg/Core/Model/Data/Show.php
src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php
src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php
src/app/code/ArchiveDotOrg/Core/Model/BulkProductImporter.php
src/app/code/ArchiveDotOrg/Core/etc/di.xml
```

### New Attributes

**Track-level:**
- `track_file_size` (int)
- `track_md5` (varchar)
- `track_acoustid` (varchar)
- `track_bitrate` (int)

**Show-level:**
- `show_files_count` (int)
- `show_total_size` (int)
- `show_uploader` (varchar)
- `show_created_date` (datetime)
- `show_last_updated` (datetime)

### Success Criteria

```bash
# Attributes created
bin/magento setup:upgrade
bin/magento catalog:attributes:list | grep track_
bin/magento catalog:attributes:list | grep show_

# Import populates new attributes
bin/magento archive:populate lettuce --limit=5
# Check product in admin - new attributes should have values
```

### Do NOT

- Move show_reviews_json to EAV (it goes to separate table)
- Modify CLI commands (that's Phase 3)
- Create admin grids (that's Phase 5)

---

# Phase 3: Commands & Matching (Week 6-7)

**Prerequisites:** Phase 0-2 complete
**Parallelism:** 3 agents can work simultaneously

---

## Card 3.A: Command Infrastructure

**Assigned to:** Agent A
**Time estimate:** 6-8 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/04-PHASE-3-COMMANDS.md` (Tasks 3.1-3.5)

### Goal

1. Create BaseLoggedCommand for correlation ID tracking
2. Create BaseReadCommand for read-only commands
3. Create new Download command
4. Add deprecation warnings to old commands

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Console/Command/
├── BaseLoggedCommand.php
├── BaseReadCommand.php
└── DownloadCommand.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadMetadataCommand.php
```

### Implementation Details

**BaseLoggedCommand template:**
```php
abstract class BaseLoggedCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $correlationId = Uuid::uuid4()->toString();
        $this->logStart($correlationId, $input);

        try {
            $result = $this->doExecute($input, $output, $correlationId);
            $this->logEnd($correlationId, 'completed');
            return $result;
        } catch (\Exception $e) {
            $this->logEnd($correlationId, 'failed', $e->getMessage());
            throw $e;
        }
    }

    abstract protected function doExecute(
        InputInterface $input,
        OutputInterface $output,
        string $correlationId
    ): int;

    private function logStart(string $correlationId, InputInterface $input): void
    {
        // Insert into archivedotorg_import_run table
    }

    private function logEnd(string $correlationId, string $status, ?string $error = null): void
    {
        // Update archivedotorg_import_run table
    }
}
```

**DownloadCommand features:**
- Extends BaseLoggedCommand
- Uses LockService (from Phase 0)
- Downloads to folder structure (from Phase 1)
- Progress bar with ETA
- Logs to database with correlation ID

**Deprecation warning:**
```
DEPRECATED: archive:download-metadata is deprecated.
Use archive:download instead for improved logging and safety.
This command will be removed in version 2.0.
```

### Success Criteria

```bash
# New command works
bin/magento archive:download lettuce --limit=5
# Should log to DB, create files in folder structure

# Old command shows deprecation warning
bin/magento archive:download-metadata lettuce --limit=1
# Should show warning but still work

# Check DB logging
bin/mysql -e "SELECT * FROM archivedotorg_import_run ORDER BY started_at DESC LIMIT 5;"
```

### Do NOT

- Create TrackMatcherService (that's Agent B)
- Create Populate command (that's Agent B)
- Create Show-Unmatched command (that's Agent C)

---

## Card 3.B: Track Matching Service

**Assigned to:** Agent B
**Time estimate:** 8-10 hours
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/04-PHASE-3-COMMANDS.md` (Tasks 3.6-3.10)
- `docs/import-rearchitecture/FIXES.md` (Fix #41 - hybrid matching algorithm)

### Goal

1. Create TrackMatcherService with hybrid algorithm
2. Create StringNormalizer for unicode handling
3. Create Populate command
4. Add deprecation to old Populate command

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Model/
├── TrackMatcherService.php
├── StringNormalizer.php
└── Data/MatchResult.php

src/app/code/ArchiveDotOrg/Core/Console/Command/PopulateCommand.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php
src/app/code/ArchiveDotOrg/Core/Console/Command/PopulateTracksCommand.php
src/app/code/ArchiveDotOrg/Core/etc/di.xml
```

### Implementation Details

**Matching algorithm (DECIDED - see FIXES.md #41):**
1. Exact match - Hash lookup, O(1)
2. Alias match - Check YAML aliases, O(n)
3. Metaphone phonetic match - O(1) with pre-built index
4. Limited fuzzy - Levenshtein on top 5 metaphone candidates only
5. Log unmatched - Admin resolution required

See Card 0.D from Phase 0 for full TrackMatcherService implementation.

**StringNormalizer transformations:**
- NFD decomposition + remove accents
- Convert unicode dashes to ASCII hyphen
- Strip extra whitespace
- Lowercase

**PopulateCommand features:**
```bash
bin/magento archive:populate lettuce
bin/magento archive:populate lettuce --dry-run
bin/magento archive:populate lettuce --limit=100
bin/magento archive:populate lettuce --export-unmatched=unmatched.txt
```

### Success Criteria

```bash
# TrackMatcherService works
bin/magento dev:console
>>> $matcher = \Magento\Framework\App\ObjectManager::getInstance()->get(\ArchiveDotOrg\Core\Api\TrackMatcherServiceInterface::class);
>>> $result = $matcher->match('Twezer', 'phish');
>>> echo $result?->getMatchType();  // Should be 'metaphone'

# Populate command works
bin/magento archive:populate lettuce --dry-run --limit=10
# Should show match types and confidence scores

# Old command deprecated
bin/magento archive:populate-tracks lettuce
# Should show deprecation warning but work
```

### Do NOT

- Implement full Levenshtein on entire catalog (too slow)
- Create Show-Unmatched command (that's Agent C)
- Create admin UI (that's Phase 5)

---

## Card 3.C: Visibility & Status Commands

**Assigned to:** Agent C
**Time estimate:** 4-6 hours
**Worktree:** `agent-c`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-c/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/04-PHASE-3-COMMANDS.md` (Tasks 3.11-3.13)

### Goal

1. Deprecate ImportShowsCommand (not delete)
2. Create Show-Unmatched command
3. Enhance Status command with comprehensive info

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Console/Command/ShowUnmatchedCommand.php
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Console/Command/ImportShowsCommand.php
src/app/code/ArchiveDotOrg/Core/Console/Command/StatusCommand.php
```

### Implementation Details

**ImportShowsCommand warning:**
```
WARNING: archive:import-shows bypasses permanent storage.
Downloaded metadata is not saved to disk for future use.

Recommended workflow:
  1. bin/magento archive:download {artist}
  2. bin/magento archive:populate {artist}

Continue anyway? [y/N]
```
- Add `--yes` flag to skip confirmation
- Keep full functionality

**ShowUnmatchedCommand output:**
```
Unmatched tracks for Lettuce (15 total):

  Track Name          | Shows  | Suggested Match
  --------------------|--------|------------------
  Twezer              | 12     | Tweezer (metaphone)
  The Flue            | 5      | The Flu (metaphone)
  Phillis             | 3      | Phyllis (metaphone)
  Unknown Track       | 2      | No suggestion

Add aliases to config/artists/lettuce.yaml to fix.
```

**Enhanced StatusCommand output:**
```
Archive.org Import Status
=========================

Artist: Lettuce
  Downloaded shows:   523
  Processed shows:    510
  Unprocessed:        13
  Unmatched tracks:   15 (2.9%)
  Match rate:         97.1%
  Last download:      2026-01-27 14:30:00
  Last populate:      2026-01-27 15:45:00

Overall:
  Total artists:      35
  Total shows:        12,450
  Total tracks:       186,000
```

### Success Criteria

```bash
# ImportShowsCommand shows warning
bin/magento archive:import-shows lettuce
# Should prompt for confirmation

# Show-Unmatched works
bin/magento archive:show-unmatched lettuce
# Should list unmatched tracks with metaphone suggestions

# Status shows comprehensive info
bin/magento archive:status lettuce
# Should show all stats
```

### Do NOT

- Delete ImportShowsCommand (deprecate only)
- Create admin UI (that's Phase 5)
- Implement YAML alias auto-add (future feature)

---

# Phase 5a/5b: Admin Dashboard (Week 8-9)

**Prerequisites:** Phase 0-3 complete, Phase 4 recommended
**Parallelism:** 4 agents can work simultaneously

---

## Card 5.A: Database Tables & Models

**Assigned to:** Agent A (Database specialist)
**Time estimate:** 8-10 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/06-PHASE-5-DASHBOARD.md` (Tasks 5.1-5.8)

### Goal

1. Create dashboard database tables (4 tables)
2. Create model classes and repositories
3. Set up Doctrine mappings

### Files to Create

```
src/app/code/ArchiveDotOrg/Admin/Setup/Patch/Schema/
├── CreateImportRunTable.php
├── CreateArtistStatusTable.php
├── CreateUnmatchedTrackTable.php
└── CreateDailyMetricsTable.php

migrations/
├── 005_create_import_run_table.sql
├── 006_create_artist_status_table.sql
├── 007_create_unmatched_track_table.sql
└── 008_create_daily_metrics_table.sql

src/app/code/ArchiveDotOrg/Admin/Model/
├── ImportRun.php
├── ArtistStatus.php
├── UnmatchedTrack.php
├── DailyMetrics.php
└── ResourceModel/
    ├── ImportRun.php
    ├── ImportRun/Collection.php
    ├── ArtistStatus.php
    ├── ArtistStatus/Collection.php
    ├── UnmatchedTrack.php
    ├── UnmatchedTrack/Collection.php
    ├── DailyMetrics.php
    └── DailyMetrics/Collection.php

src/app/code/ArchiveDotOrg/Admin/Api/ImportRunRepositoryInterface.php
src/app/code/ArchiveDotOrg/Admin/Model/ImportRunRepository.php
```

### Implementation Details

**import_run table:**
```sql
CREATE TABLE archivedotorg_import_run (
    run_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    correlation_id VARCHAR(36) NOT NULL UNIQUE,
    artist_id INT UNSIGNED NOT NULL,
    command VARCHAR(100) NOT NULL,
    status ENUM('running', 'completed', 'failed') NOT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    options_json JSON,
    error_message TEXT,
    shows_processed INT DEFAULT 0,
    tracks_processed INT DEFAULT 0,
    FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id) ON DELETE CASCADE,
    INDEX idx_correlation_id (correlation_id),
    INDEX idx_artist_status_started (artist_id, status, started_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**artist_status table:**
```sql
CREATE TABLE archivedotorg_artist_status (
    status_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    artist_id INT UNSIGNED NOT NULL UNIQUE,
    shows_downloaded INT DEFAULT 0,
    shows_processed INT DEFAULT 0,
    tracks_matched INT DEFAULT 0,
    tracks_unmatched INT DEFAULT 0,
    match_rate DECIMAL(5,2) DEFAULT 0,
    last_download_at TIMESTAMP NULL,
    last_populate_at TIMESTAMP NULL,
    FOREIGN KEY (artist_id) REFERENCES archivedotorg_artist(artist_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

See Phase 5 doc for unmatched_track and daily_metrics tables.

### Success Criteria

```bash
# Schema patches compile
bin/magento setup:di:compile

# Run migrations
bin/magento setup:upgrade

# Verify tables
bin/mysql -e "SHOW TABLES LIKE 'archivedotorg_%';"
# Should show 9 tables total (5 from Phase 0 + 4 new)

# Test models
bin/magento dev:console
>>> $repo = \Magento\Framework\App\ObjectManager::getInstance()->get(\ArchiveDotOrg\Admin\Api\ImportRunRepositoryInterface::class);
>>> echo get_class($repo);
```

### Do NOT

- Create admin controllers yet (that's Agent B)
- Create UI components (that's Agent C)
- Create charts (that's Agent D)

---

## Card 5.B: Admin Controllers & Module

**Assigned to:** Agent B
**Time estimate:** 8-10 hours
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/06-PHASE-5-DASHBOARD.md` (Tasks 5.9-5.12, 5.16)

### Goal

1. Create Admin module structure
2. Create Redis progress tracking
3. Create dashboard controller
4. Create progress AJAX endpoint

### Files to Create

```
src/app/code/ArchiveDotOrg/Admin/
├── registration.php
├── etc/
│   ├── module.xml
│   └── adminhtml/
│       ├── menu.xml
│       └── routes.xml
├── Model/Redis/ProgressTracker.php
├── Controller/Adminhtml/
│   ├── Dashboard/Index.php
│   └── Progress/Status.php
├── Block/Adminhtml/Dashboard.php
└── view/adminhtml/
    ├── layout/archivedotorg_dashboard_index.xml
    └── templates/dashboard.phtml
```

### Files to Modify

```
src/app/code/ArchiveDotOrg/Core/Console/Command/BaseLoggedCommand.php
```

### Implementation Details

**Admin menu structure:**
```xml
<config>
    <menu>
        <add id="ArchiveDotOrg_Admin::dashboard"
             title="Archive.org Import"
             module="ArchiveDotOrg_Admin"
             resource="ArchiveDotOrg_Admin::dashboard"
             parent="Magento_Backend::content">
            <add id="ArchiveDotOrg_Admin::dashboard_index"
                 title="Dashboard"
                 action="archivedotorg/dashboard/index"
                 resource="ArchiveDotOrg_Admin::dashboard"/>
            <add id="ArchiveDotOrg_Admin::artists"
                 title="Artists"
                 action="archivedotorg/artist/index"
                 resource="ArchiveDotOrg_Admin::artists"/>
            <add id="ArchiveDotOrg_Admin::history"
                 title="Import History"
                 action="archivedotorg/history/index"
                 resource="ArchiveDotOrg_Admin::history"/>
            <add id="ArchiveDotOrg_Admin::unmatched"
                 title="Unmatched Tracks"
                 action="archivedotorg/unmatched/index"
                 resource="ArchiveDotOrg_Admin::unmatched"/>
        </add>
    </menu>
</config>
```

**Redis progress keys:**
```
archivedotorg:progress:{artist}:current
archivedotorg:progress:{artist}:total
archivedotorg:progress:{artist}:processed
archivedotorg:progress:{artist}:eta
archivedotorg:progress:{artist}:status
archivedotorg:progress:{artist}:correlation_id
```

**Progress AJAX endpoint response:**
```json
{
  "artist": "lettuce",
  "status": "running",
  "current": 150,
  "total": 523,
  "processed": 145,
  "eta": "2026-01-28T15:30:00Z",
  "correlation_id": "abc-123"
}
```

### Success Criteria

```bash
# Module enabled
bin/magento module:status ArchiveDotOrg_Admin
# Should be enabled

# Menu appears
# Navigate to admin, check Content menu

# Dashboard loads
curl http://localhost/admin/archivedotorg/dashboard/index
# Should return HTML

# Progress endpoint works
curl http://localhost/admin/archivedotorg/progress/status?artist=lettuce
# Should return JSON
```

### Do NOT

- Create UI grids yet (that's Agent C)
- Create charts (that's Agent D)
- Create cron jobs (that's Agent D)

---

## Card 5.C: Admin UI Grids

**Assigned to:** Agent C
**Time estimate:** 10-12 hours
**Worktree:** `agent-c`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-c/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/06-PHASE-5-DASHBOARD.md` (Tasks 5.13-5.15)

### Goal

1. Create Artist grid
2. Create Import History grid
3. Create Unmatched Tracks grid

### Files to Create

```
src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/
├── Artist/Index.php
├── History/Index.php
└── Unmatched/Index.php

src/app/code/ArchiveDotOrg/Admin/Ui/Component/Listing/
├── ArtistDataProvider.php
├── HistoryDataProvider.php
└── UnmatchedDataProvider.php

src/app/code/ArchiveDotOrg/Admin/view/adminhtml/ui_component/
├── archivedotorg_artist_listing.xml
├── archivedotorg_history_listing.xml
└── archivedotorg_unmatched_listing.xml
```

### Implementation Details

**Artist grid columns:**
- Artist name
- Shows downloaded
- Shows processed
- Match rate
- Last download
- Last populate
- Actions (Download, Populate, View Unmatched)

**Import History grid columns:**
- Correlation ID
- Artist
- Command
- Status
- Started at
- Duration
- Shows/tracks processed

**Filters:**
- Artist
- Command type
- Status
- Date range

**Unmatched Tracks grid columns:**
- Track name
- Artist
- Occurrences
- Suggested match
- First seen
- Resolved (checkbox)

### Success Criteria

```bash
# Grids load
# Navigate to each grid in admin

# Artist grid shows data
# Should list all 35 artists with status

# History grid filterable
# Test filters: artist, command, status, date

# Unmatched grid shows suggestions
# Should show metaphone suggestions
```

### Do NOT

- Implement mass actions yet (future feature)
- Add inline editing (future feature)
- Create charts (that's Agent D)

---

## Card 5.D: Charts & Real-Time Features

**Assigned to:** Agent D
**Time estimate:** 8-10 hours
**Worktree:** `agent-d`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-d/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/06-PHASE-5-DASHBOARD.md` (Tasks 5.17-5.21)

### Goal

1. Add ApexCharts library
2. Create Imports Per Day chart
3. Create Match Rate gauge
4. Create real-time progress widget
5. Create daily metrics aggregation cron

### Files to Create

```
src/app/code/ArchiveDotOrg/Admin/view/adminhtml/
├── requirejs-config.js
└── web/js/
    ├── lib/apexcharts.min.js
    ├── dashboard-charts.js
    └── progress-poller.js

src/app/code/ArchiveDotOrg/Admin/Cron/AggregateDailyMetrics.php
src/app/code/ArchiveDotOrg/Admin/etc/crontab.xml
```

### Implementation Details

**ApexCharts config:**
```javascript
require.config({
    paths: {
        'apexcharts': 'ArchiveDotOrg_Admin/js/lib/apexcharts.min'
    }
});
```

**Bar chart (Imports Per Day):**
```javascript
var options = {
    chart: { type: 'bar', height: 350 },
    series: [{
        name: 'Shows Imported',
        data: [45, 52, 38, 67, 55, 41, 60]
    }],
    xaxis: {
        categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
    }
};
```

**Radial gauge (Match Rate):**
```javascript
var options = {
    chart: { type: 'radialBar' },
    series: [97.1],
    colors: ['#00E396'],
    plotOptions: {
        radialBar: {
            hollow: { size: '70%' },
            dataLabels: {
                name: { offsetY: -10, show: true, color: '#888', fontSize: '13px' },
                value: { color: '#111', fontSize: '30px', show: true }
            }
        }
    },
    labels: ['Match Rate']
};
```

**Progress poller:**
```javascript
setInterval(function() {
    fetch('/admin/archivedotorg/progress/status?artist=lettuce')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'running') {
                updateProgressBar(data.current, data.total);
                updateETA(data.eta);
            } else {
                stopPolling();
            }
        });
}, 2000);
```

**Cron job (daily 4 AM):**
```xml
<config>
    <group id="archivedotorg">
        <job name="aggregate_daily_metrics"
             instance="ArchiveDotOrg\Admin\Cron\AggregateDailyMetrics"
             method="execute">
            <schedule>0 4 * * *</schedule>
        </job>
    </group>
</config>
```

### Success Criteria

```bash
# Charts render on dashboard
# Navigate to dashboard, verify bar chart and gauge display

# Real-time progress works
bin/magento archive:download lettuce --limit=50 &
# Watch dashboard for live updates

# Cron job registered
bin/magento cron:run --group=archivedotorg
# Should aggregate metrics

# Verify metrics
bin/mysql -e "SELECT * FROM archivedotorg_daily_metrics ORDER BY date DESC LIMIT 7;"
```

### Do NOT

- Create complex chart interactions (zoom, pan) yet
- Add export to CSV (future feature)
- Create email alerts (future feature)

---

# Phase 6: Testing & Documentation (Week 9-10)

**Prerequisites:** Phase 0-5 complete
**Parallelism:** 4 agents can work simultaneously

---

## Card 6.A: Unit Tests - Services

**Assigned to:** Agent A
**Time estimate:** 8-10 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/07-PHASE-6-TESTING.md` (Tasks 6.1-6.4)

### Goal

Create comprehensive unit tests for core services:
1. LockService
2. TrackMatcherService
3. ArtistConfigValidator
4. StringNormalizer

**Target:** 100% test coverage on critical services

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Test/Unit/Model/
├── LockServiceTest.php
├── TrackMatcherServiceTest.php
├── ArtistConfigValidatorTest.php
└── StringNormalizerTest.php
```

### Implementation Details

**LockService test cases:**
- Acquire lock succeeds when no lock exists
- Acquire lock fails when lock already held
- Release lock succeeds
- Release lock is idempotent
- Stale lock detection (lock from dead process)
- Lock directory creation

**TrackMatcherService test cases:**
- Exact match found
- Alias match found
- Metaphone match found
- No match returns null
- Case insensitive matching
- Unicode normalization applied
- Empty string handling
- Multiple metaphone candidates (picks best)

**ArtistConfigValidator test cases:**
- Valid YAML passes
- Missing required field fails
- Invalid URL key format fails
- Duplicate track names in same album fails
- Invalid fuzzy_threshold fails
- Empty aliases array triggers warning
- Album context required for tracks

**StringNormalizer test cases:**
- Accent removal: "Tweezér" → "tweezer"
- Unicode dash conversion: "Free—form" → "free-form"
- Whitespace normalization: "  The   Flu  " → "the flu"
- Lowercase conversion
- Combined transformations
- Empty string handling

### Success Criteria

```bash
# All tests pass
bin/magento dev:tests:run unit --filter=ArchiveDotOrg
# Should report: 0 failures, 30+ assertions

# Or with phpunit directly
cd src
../vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  --filter="ArchiveDotOrg" --testdox
```

### Do NOT

- Write integration tests (that's Agent B)
- Write performance tests (that's Agent C)
- Write documentation (that's Agent D)

---

## Card 6.B: Integration & Concurrency Tests

**Assigned to:** Agent B
**Time estimate:** 6-8 hours
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/07-PHASE-6-TESTING.md` (Tasks 6.5-6.6)

### Goal

Create end-to-end integration tests:
1. Full download → populate flow
2. Concurrent download protection

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Test/Integration/
├── DownloadPopulateTest.php
└── ConcurrencyTest.php
```

### Implementation Details

**Download → Populate flow:**
1. Create test YAML config
2. Run `archive:setup {test-artist}`
3. Mock Archive.org API (or use test fixtures)
4. Run `archive:download --limit=5`
5. Run `archive:populate`
6. Verify products created with correct attributes
7. Verify unmatched tracks logged

**Concurrency test:**
1. Start download process A in background
2. Try to start download process B (same artist)
3. Verify B fails with lock error
4. Wait for A to complete
5. Start download C - should succeed

### Success Criteria

```bash
# Integration tests pass
bin/magento dev:tests:run integration --filter=ArchiveDotOrg
# Should report: 0 failures

# Manual concurrency test
bin/magento archive:download lettuce --limit=50 &
bin/magento archive:download lettuce --limit=50
# Second should fail with lock error
```

### Do NOT

- Write unit tests (that's Agent A)
- Write performance tests (that's Agent C)
- Write documentation (that's Agent D)

---

## Card 6.C: Performance Benchmarks

**Assigned to:** Agent C
**Time estimate:** 6-8 hours
**Worktree:** `agent-c`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-c/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Run Magento CLI via:** `/Users/chris.majorossy/Education/8pm/bin/magento`

Read the full task details:
- `docs/import-rearchitecture/07-PHASE-6-TESTING.md` (Tasks 6.7-6.9)

### Goal

Create performance benchmarks:
1. Matching algorithms (exact, metaphone, fuzzy)
2. BulkProductImporter vs TrackImporter
3. Dashboard query performance

### Files to Create

```
src/app/code/ArchiveDotOrg/Core/Test/Performance/
├── MatchingBenchmark.php
├── ImportBenchmark.php
└── DashboardBenchmark.php

src/app/code/ArchiveDotOrg/Core/Console/Command/
├── BenchmarkMatchingCommand.php
└── BenchmarkImportCommand.php
```

### Implementation Details

**Matching benchmark targets:**

| Method | Tracks | Target Time | Target Memory |
|--------|--------|-------------|---------------|
| Exact match | 10,000 | <100ms | <10MB |
| Metaphone match | 10,000 | <500ms | <50MB |
| Levenshtein (DON'T USE) | 10,000 | ~2-10 min | 50-100MB |

**Import benchmark:**
- Import 1,000 products via TrackImporter (ORM)
- Import 1,000 products via BulkProductImporter (direct SQL)
- Expected: BulkProductImporter ~10x faster

**Dashboard benchmark:**
- Artist grid query - target <100ms
- Imports per day chart query - target <50ms
- Unmatched tracks query - target <100ms

### Success Criteria

```bash
# Run matching benchmark
bin/magento archive:benchmark-matching --tracks=10000
# Should show: Exact: <100ms, Metaphone: <500ms

# Run import benchmark
bin/magento archive:benchmark-import --products=1000
# Should show speedup factor

# Query benchmarks
bin/mysql -e "EXPLAIN SELECT * FROM archivedotorg_artist_status;"
# Should show indexes used
```

### Do NOT

- Optimize code to meet benchmarks (that's implementation)
- Write unit tests (that's Agent A)
- Write documentation (that's Agent D)

---

## Card 6.D: Documentation

**Assigned to:** Agent D
**Time estimate:** 8-10 hours
**Worktree:** `agent-d`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-d/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`

Read the full task details:
- `docs/import-rearchitecture/07-PHASE-6-TESTING.md` (Tasks 6.10-6.13)

### Goal

Create comprehensive documentation:
1. Update main plan document
2. Create developer guide
3. Create admin user guide
4. Document API endpoints (if created)

### Files to Create

```
docs/
├── DEVELOPER_GUIDE.md
├── ADMIN_GUIDE.md
└── API.md (optional)
```

### Files to Modify

```
docs/import-rearchitecture/00-OVERVIEW.md
```

### Implementation Details

**Developer Guide sections:**
1. Architecture overview
   - System components diagram
   - Data flow (API → JSON → Products)
   - File structure

2. Adding a new artist
   - Create YAML config
   - Run `archive:setup`
   - Run `archive:download`
   - Run `archive:populate`
   - Resolve unmatched tracks

3. Extending matching logic
   - TrackMatcherService interface
   - Adding new match strategies
   - Custom normalizers

4. Troubleshooting
   - Common errors and solutions
   - Log locations
   - Debug commands

**Admin User Guide sections:**
1. Dashboard overview
   - Stats cards explained
   - Charts interpretation

2. Managing artists
   - Viewing artist status
   - Triggering imports
   - Monitoring progress

3. Resolving unmatched tracks
   - Finding unmatched tracks
   - Adding aliases to YAML
   - Re-running populate

4. Performance tuning
   - Batch size recommendations
   - Cron scheduling
   - Cache management

### Success Criteria

```bash
# Documentation exists
ls docs/
# Should show: DEVELOPER_GUIDE.md, ADMIN_GUIDE.md

# Documentation is complete
wc -l docs/DEVELOPER_GUIDE.md docs/ADMIN_GUIDE.md
# Should be 200+ lines each
```

### Do NOT

- Write tests (that's Agents A-C)
- Implement features (that's previous phases)
- Create video tutorials (future work)

---

# Phase 7: Rollout & Verification (Week 10)

**Prerequisites:** ALL previous phases complete
**Parallelism:** 2 agents (staging validation + production prep)

---

## Card 7.A: Staging Validation & Load Testing

**Assigned to:** Agent A
**Time estimate:** 12-16 hours
**Worktree:** `agent-a`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-a/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Environment:** Staging (production clone)

Read the full task details:
- `docs/import-rearchitecture/08-PHASE-7-ROLLOUT.md` (Tasks 7.1-7.4)

### Goal

1. Verify database migrations in staging
2. Test with production data clone
3. Load test with 100k+ products
4. Test admin dashboard performance

### Tasks

**Database verification:**
```bash
# Run all schema patches
bin/magento setup:upgrade

# Verify tables created
mysql magento -e "SHOW TABLES LIKE 'archivedotorg_%';"
# Expected: 9 tables

# Verify indexes
mysql magento -e "SHOW INDEX FROM catalog_product_entity WHERE Key_name LIKE 'idx_%';"

# Verify foreign keys
SELECT * FROM information_schema.key_column_usage
WHERE referenced_table_name LIKE 'archivedotorg_%';
```

**Production data clone test:**
```bash
# Clone database
mysqldump production_magento | mysql staging_magento

# Run folder migration
bin/magento archive:migrate:organize-folders

# Verify file counts match
find var/archivedotorg/metadata -name "*.json" | wc -l

# Export to YAML
bin/magento archive:migrate:export

# Validate all YAMLs
bin/magento archive:validate --all
# Should report: 35 artists validated, 0 errors
```

**Load test:**
```bash
# Monitor resources during import
bin/magento archive:download GratefulDead --limit=500 &
watch -n 1 "ps aux | grep magento | grep -v grep"

# Check memory usage (should stay <512MB)
# Check CPU usage (should stay <80%)
```

**Dashboard performance:**
```bash
# Load dashboard
curl -w "@curl-format.txt" -o /dev/null -s https://staging-admin.example.com/archivedotorg/dashboard
# Target: <100ms total time
```

### Success Criteria

- [ ] All migrations run without error
- [ ] All 35 artists exported to YAML
- [ ] Import flow works with new commands
- [ ] Memory usage stable (no leaks)
- [ ] Dashboard loads <100ms with 186k products
- [ ] All tests pass in staging

### Do NOT

- Deploy to production yet (that's Agent B after validation complete)
- Make code changes (this is testing only)
- Skip any verification steps

---

## Card 7.B: Production Deployment & Monitoring

**Assigned to:** Agent B
**Time estimate:** 16-20 hours (includes 7-day monitoring)
**Worktree:** `agent-b`

### Context

You are working on the 8PM Archive.org import rearchitecture project.

**Your working directory:** `/Users/chris.majorossy/Education/8pm-worktrees/agent-b/`
**Main repo (for reference):** `/Users/chris.majorossy/Education/8pm/`
**Environment:** Production

Read the full task details:
- `docs/import-rearchitecture/08-PHASE-7-ROLLOUT.md` (Tasks 7.5-7.12)

### Goal

1. Execute phased production deployment (5 phases)
2. Monitor system for 7 days
3. Gather user feedback
4. Create runbook

### Tasks

**Phase 1 - Database:**
```bash
# Backup
mysqldump magento > backup_$(date +%Y%m%d_%H%M%S).sql

# Maintenance mode
bin/magento maintenance:enable

# Run migrations
bin/magento setup:upgrade

# Verify
mysql magento -e "SHOW TABLES LIKE 'archivedotorg_%';"

# End maintenance (target: <5 min downtime)
bin/magento maintenance:disable
```

**Phase 2 - Code:**
```bash
# Deploy
git pull origin main

# Clear caches
bin/magento cache:flush
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f

# Test old commands
bin/magento archive:download-metadata --help
# Should show deprecation warning but work
```

**Phase 3 - Data Migration:**
```bash
# Backup
cp -r var/archivedotorg/metadata var/archivedotorg/metadata.backup.$(date +%Y%m%d)

# Folder migration
bin/magento archive:migrate:organize-folders

# YAML export
bin/magento archive:migrate:export

# Verify
bin/magento archive:validate --all
```

**Phase 4 - Admin Dashboard:**
```bash
# Enable module
bin/magento module:enable ArchiveDotOrg_Admin
bin/magento cache:flush

# Test dashboard
# Navigate to Admin > Content > Archive.org Import > Dashboard
```

**Phase 5 - Cleanup (wait 30 days):**
```bash
# Delete old data patches
rm src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/CreateCategoryStructure.php
# ... etc
```

**7-Day Monitoring:**
```bash
# Day 1: Error logs
tail -f var/log/exception.log | grep -i archivedotorg

# Day 2: Dashboard performance
curl -w "%{time_total}\n" -o /dev/null -s https://admin.example.com/archivedotorg/dashboard

# Day 3-7: Import success, match rates, disk usage
# See Phase 7 doc for full monitoring checklist
```

**Runbook creation:**
Create `docs/RUNBOOK.md` with:
1. Common errors and solutions
2. How to restart failed imports
3. Emergency rollback procedures
4. Performance tuning guide

### Success Criteria

- [ ] All deployment phases complete
- [ ] Zero data loss during migration
- [ ] Dashboard performance <100ms
- [ ] 7-day monitoring passed with no critical bugs
- [ ] Runbook created and reviewed
- [ ] User feedback collected

### Do NOT

- Rush deployment (wait for Agent A staging validation)
- Skip backup steps
- Delete old code without 30-day grace period

---

# Quick Start Guide

## One-Time Setup: Create Worktrees

Git worktrees let each agent work in a completely isolated directory. No branch switching, no conflicts.

```bash
# From main repo
cd /Users/chris.majorossy/Education/8pm

# Ensure main is up to date
git checkout main
git pull

# Create worktrees directory
mkdir -p ../8pm-worktrees

# Create 4 agent worktrees (reusable across phases)
git worktree add ../8pm-worktrees/agent-a -b feature/agent-a
git worktree add ../8pm-worktrees/agent-b -b feature/agent-b
git worktree add ../8pm-worktrees/agent-c -b feature/agent-c
git worktree add ../8pm-worktrees/agent-d -b feature/agent-d
```

**Result:**
```
/Users/chris.majorossy/Education/
├── 8pm/                    ← Main repo (keep on main)
└── 8pm-worktrees/
    ├── agent-a/            ← Agent A works here
    ├── agent-b/            ← Agent B works here
    ├── agent-c/            ← Agent C works here
    └── agent-d/            ← Agent D works here
```

---

## To Start Swarming Phase -1:

Open 3 terminal windows:

**Terminal 1 (Agent A):**
```bash
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-a
claude
# Paste Card -1.A prompt
```

**Terminal 2 (Agent B):**
```bash
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-b
claude
# Paste Card -1.B prompt
```

**Terminal 3 (Agent C):**
```bash
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-c
claude
# Paste Card -1.C prompt
```

---

## After All Phase -1 Agents Complete:

```bash
# Go to main repo
cd /Users/chris.majorossy/Education/8pm

# Merge each agent's work
git merge feature/agent-a --no-ff -m "Phase -1.A: Service interfaces and SKU docs"
git merge feature/agent-b --no-ff -m "Phase -1.B: Exceptions and feature flags"
git merge feature/agent-c --no-ff -m "Phase -1.C: Test plan alignment"

# Verify integration
bin/magento setup:di:compile
bin/magento setup:upgrade
bin/magento cache:flush

# If all passes, reset worktrees for Phase 0
```

---

## Reset Worktrees for Next Phase:

```bash
# Update each worktree to latest main
cd /Users/chris.majorossy/Education/8pm-worktrees/agent-a
git fetch origin
git reset --hard origin/main

# Repeat for agent-b, agent-c, agent-d
# Or use a loop:
for agent in agent-a agent-b agent-c agent-d; do
  cd /Users/chris.majorossy/Education/8pm-worktrees/$agent
  git fetch origin && git reset --hard origin/main
done
```

---

## Cleanup When Done:

```bash
cd /Users/chris.majorossy/Education/8pm

# Remove worktrees
git worktree remove ../8pm-worktrees/agent-a
git worktree remove ../8pm-worktrees/agent-b
git worktree remove ../8pm-worktrees/agent-c
git worktree remove ../8pm-worktrees/agent-d

# Delete feature branches
git branch -D feature/agent-a feature/agent-b feature/agent-c feature/agent-d

# Remove directory
rm -rf ../8pm-worktrees
```

---

## Docker Note

All worktrees can share the same Docker containers since they mount to `src/`:

```bash
# From main repo, start Docker once
cd /Users/chris.majorossy/Education/8pm
bin/start

# Agents work in worktrees but test via main repo's Docker
# Just run bin/magento from main repo after merging
```

If agents need to run CLI commands during development:
```bash
# Option 1: Symlink bin/ to worktree
ln -s /Users/chris.majorossy/Education/8pm/bin ../8pm-worktrees/agent-a/bin

# Option 2: Use absolute path
/Users/chris.majorossy/Education/8pm/bin/magento setup:di:compile
```
