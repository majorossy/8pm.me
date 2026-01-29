# Phase 3: New Commands & Matching

**Timeline:** Week 6-7
**Status:** ⏸️ Blocked by Phase 2
**Prerequisites:** Phase 0-2 complete

---

## Overview

Create the new command structure with proper logging, and implement the improved track matching algorithm.

**New commands:**
- `archive:download` - Download with logging (replaces `archive:download-metadata`)
- `archive:populate` - Populate with matching (replaces `archive:populate-tracks`)
- `archive:show-unmatched` - View unmatched tracks

**Completion Criteria:**
- [ ] BaseLoggedCommand provides correlation ID + DB logging
- [ ] TrackMatcherService uses Soundex (not fuzzy)
- [ ] Unmatched tracks logged to database
- [ ] Old commands show deprecation warning but still work
- [ ] Status command shows comprehensive information

---

## 🟧 P1 - Command Structure

### Task 3.1: Create BaseLoggedCommand
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/BaseLoggedCommand.php`

**Features:**
- [ ] Auto-generates correlation ID (UUID)
- [ ] Logs command start/end to `archivedotorg_import_run` table
- [ ] Updates Redis progress keys (see Phase 5)
- [ ] Catches exceptions, logs failures
- [ ] Abstract `doExecute()` method for subclasses

**Template:**
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
}
```

---

### Task 3.2: Create BaseReadCommand
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/BaseReadCommand.php`

**Purpose:** Lightweight base for read-only commands (no logging overhead).

**Use for:**
- `archive:status`
- `archive:show-unmatched`
- `archive:validate`

---

### Task 3.3: Unify --resume and --incremental Flags
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadMetadataCommand.php`

**Problem:** Two flags that do similar things is confusing.

**Solution:**
- [ ] Remove `--resume` flag
- [ ] Update `--incremental` to:
  1. Check progress file first
  2. Fall back to filesystem scan if corrupted
  3. Skip files that already exist

---

### Task 3.4: Create New Download Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadCommand.php`

**Usage:**
```bash
bin/magento archive:download lettuce
bin/magento archive:download lettuce --incremental
bin/magento archive:download lettuce --limit=100
```

**Features:**
- [ ] Extends `BaseLoggedCommand`
- [ ] Uses LockService (from Phase 0)
- [ ] Downloads to folder structure (from Phase 1)
- [ ] Logs to database with correlation ID
- [ ] Progress bar with ETA

---

### Task 3.5: Add Deprecation to Old Download Command
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadMetadataCommand.php`

- [ ] Add warning at start of execution:
```
DEPRECATED: archive:download-metadata is deprecated.
Use archive:download instead for improved logging and safety.
This command will be removed in version 2.0.
```
- [ ] Keep full functionality (backward compatible)
- [ ] Test: Shows warning but still works

---

## 🟧 P1 - Track Matching

### Task 3.6: Create TrackMatcherService
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/TrackMatcherService.php`
**Create:** `src/app/code/ArchiveDotOrg/Core/Api/TrackMatcherServiceInterface.php`

**Algorithm (DECIDED - see FIXES.md #41):**
1. **Exact match** - Hash lookup, O(1)
2. **Alias match** - Check YAML aliases, O(n)
3. **Metaphone phonetic match** - O(1) with pre-built index (better than Soundex)
4. **Limited fuzzy** - Levenshtein on top 5 metaphone candidates only
5. **Log unmatched** - Admin resolution required

**Implementation:**
```php
public function match(string $trackName, string $artistId): ?MatchResult
{
    $normalized = $this->normalizer->normalize($trackName);

    // 1. Exact match
    if (isset($this->exactIndex[$artistId][$normalized])) {
        return new MatchResult($this->exactIndex[$artistId][$normalized], 'exact', 100);
    }

    // 2. Alias match
    if (isset($this->aliasIndex[$artistId][$normalized])) {
        return new MatchResult($this->aliasIndex[$artistId][$normalized], 'alias', 95);
    }

    // 3. Metaphone match (better than Soundex for English)
    $metaphone = metaphone($normalized);
    if (isset($this->metaphoneIndex[$artistId][$metaphone])) {
        return new MatchResult($this->metaphoneIndex[$artistId][$metaphone], 'metaphone', 85);
    }

    // 4. Limited Levenshtein - top 5 candidates only
    $candidate = $this->fuzzyMatchTopCandidates($normalized, $artistId, limit: 5);
    if ($candidate && $candidate['score'] >= 80) {
        return new MatchResult($candidate['track'], 'fuzzy', $candidate['score']);
    }

    // 5. No match - will be logged for admin resolution
    return null;
}
```

- [ ] Pre-build indexes on service construction
- [ ] Return match type + confidence score
- [ ] Limited fuzzy on top 5 candidates only (not full catalog)

---

### Task 3.7: Add Album-Context Matching
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php`

**Problem:** Same track name can appear on multiple albums.

**Solution:**
- [ ] Use YAML album context for matching
- [ ] If track appears in multiple albums, check show metadata for album hints
- [ ] Log ambiguous matches for admin resolution

**Example:**
```
Track "Fly" found in albums: "Outta Here", "Rage!"
Show metadata suggests album: "Outta Here" (2002 tour)
Matched to: Lettuce/Outta Here/Fly
```

---

### Task 3.8: Add Unicode Normalization
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/StringNormalizer.php`

**Transformations:**
- [ ] NFD decomposition + remove accents
- [ ] Convert unicode dashes to ASCII hyphen
- [ ] Strip extra whitespace
- [ ] Lowercase

**Examples:**
- "Tweezér" → "tweezer"
- "Free—form" → "free-form"
- "  The   Flu  " → "the flu"

---

### Task 3.9: Create Populate Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/PopulateCommand.php`

**Usage:**
```bash
bin/magento archive:populate lettuce
bin/magento archive:populate lettuce --dry-run
bin/magento archive:populate lettuce --limit=100
bin/magento archive:populate lettuce --export-unmatched=unmatched.txt
```

**Features:**
- [ ] Extends `BaseLoggedCommand`
- [ ] Uses TrackMatcherService
- [ ] Logs unmatched tracks to database
- [ ] Dry-run mode shows what would be created
- [ ] Progress bar with stats

---

### Task 3.10: Add Deprecation to Old Populate Command
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/PopulateTracksCommand.php`

- [ ] Add deprecation warning
- [ ] Keep full functionality
- [ ] Test: Shows warning but works

---

### Task 3.11: Deprecate ImportShowsCommand (NOT delete)
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/ImportShowsCommand.php`

**Add warning:**
```
WARNING: archive:import-shows bypasses permanent storage.
Downloaded metadata is not saved to disk for future use.

Recommended workflow:
  1. bin/magento archive:download {artist}
  2. bin/magento archive:populate {artist}

Continue anyway? [y/N]
```

- [ ] Keep functionality for users who need single-command import
- [ ] Add `--yes` flag to skip confirmation
- [ ] Plan removal in version 2.0

---

## 🟧 P1 - Visibility & Monitoring

### Task 3.12: Create Show-Unmatched Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/ShowUnmatchedCommand.php`

**Usage:**
```bash
bin/magento archive:show-unmatched lettuce
bin/magento archive:show-unmatched --all --limit=50
```

**Output:**
```
Unmatched tracks for Lettuce (15 total):

  Track Name          | Shows  | Suggested Match
  --------------------|--------|------------------
  Twezer              | 12     | Tweezer (soundex)
  The Flue            | 5      | The Flu (soundex)
  Phillis             | 3      | Phyllis (soundex)
  Unknown Track       | 2      | No suggestion

Add aliases to config/artists/lettuce.yaml to fix.
```

---

### Task 3.13: Enhance Status Command
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/StatusCommand.php`

**Add information:**
- [ ] Downloaded shows count (from filesystem/manifest)
- [ ] Processed shows count (from DB query)
- [ ] Unprocessed shows (downloaded but not populated)
- [ ] Unmatched tracks count
- [ ] Last import date/time
- [ ] Match rate percentage

**Output:**
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

---

## Verification Checklist

Before moving to Phase 4:

```bash
# 1. Test new download command
bin/magento archive:download lettuce --limit=5
# Should log to DB, create files in folder structure

# 2. Test track matching
bin/magento archive:populate lettuce --dry-run
# Should show match types (exact, alias, soundex)

# 3. Test deprecation warnings
bin/magento archive:download-metadata lettuce --limit=1
# Should show deprecation warning

# 4. Test unmatched tracking
bin/magento archive:show-unmatched lettuce
# Should list any unmatched tracks

# 5. Test status command
bin/magento archive:status
# Should show comprehensive stats
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 4: Extended Attributes](./05-PHASE-4-ATTRIBUTES.md)
