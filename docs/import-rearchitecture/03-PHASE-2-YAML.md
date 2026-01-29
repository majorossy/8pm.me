# Phase 2: YAML Configuration System

**Timeline:** Week 4-5
**Status:** ⏸️ Blocked by Phase 0
**Prerequisites:** Phase 0 complete, Phase 1 recommended

---

## Overview

Replace hardcoded PHP data patches with YAML-driven artist configuration. This makes adding/modifying artists a config change instead of code deployment.

**Current:** Artist data in 7 PHP data patch files
**Target:** 35 YAML files in `config/artists/`

**Completion Criteria:**
- [ ] YAML schema validator catches all invalid configs
- [ ] All 35 artists exported to YAML files
- [ ] `archive:setup {artist}` creates categories from YAML
- [ ] `archive:validate {artist}` shows config errors
- [ ] Old data patches deleted (after verification)

---

## 🟧 P1 - YAML Infrastructure

### Task 2.1: Create YAML Schema Validator
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/ArtistConfigValidator.php`

**Validation rules:**
- [ ] Required fields: `artist.name`, `artist.collection_id`
- [ ] URL key format: lowercase alphanumeric + hyphens only
- [ ] No duplicate track names within same album
- [ ] `fuzzy_threshold` range: 0-100 (if present)
- [ ] Album context required for tracks (see Task 2.3)
- [ ] No empty aliases array
- [ ] Valid date formats

**Return format:**
```php
[
    'valid' => false,
    'errors' => [
        'artist.name is required',
        'tracks[5].url_key contains invalid characters',
    ],
    'warnings' => [
        'tracks[12] has no aliases - matching may be less accurate',
    ]
]
```

---

### Task 2.2: Create YAML Loader
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/ArtistConfigLoader.php`

**Features:**
- [ ] Load from `app/code/ArchiveDotOrg/Core/config/artists/{artist}.yaml`
- [ ] Validate before returning (using validator from 2.1)
- [ ] Cache parsed YAML in memory (avoid re-parsing)
- [ ] Throw descriptive exception on invalid YAML

**Usage:**
```php
$config = $this->configLoader->load('lettuce');
// Returns validated array structure
```

---

### Task 2.3: Fix YAML Structure (Multi-Album with Stable Keys) ✅ DECIDED
**Create:** `app/code/ArchiveDotOrg/Core/config/artists/template.yaml`

**Decision:** Use stable keys for albums and tracks, support multi-album tracks (see FIXES.md #29, #45-47)

**Problem:** Current structure doesn't specify which album a track belongs to, and tracks can appear on multiple albums.

**Solution (FINAL):** Use stable keys and array for albums:

```yaml
artist:
  name: "Lettuce"
  collection_id: "Lettuce"
  url_key: "lettuce"

albums:
  - key: "outta-here"        # Stable key (won't break if name changes)
    name: "Outta Here"
    url_key: "outta-here"
    year: 2002
    type: "studio"
  - key: "rage"
    name: "Rage!"
    url_key: "rage"
    year: 2008
    type: "studio"
  - key: "live-only"         # Virtual album for live-only tracks
    name: "Live Repertoire"
    url_key: "live-repertoire"
    type: "virtual"          # Not a real album

tracks:
  - key: "phyllis"           # Stable key
    name: "Phyllis"
    url_key: "phyllis"
    albums: ["outta-here"]   # Array - supports multi-album
    canonical_album: "outta-here"  # For display purposes
    aliases: ["phillis", "philis"]
    type: "original"
  - key: "sam-huff"
    name: "Sam Huff"
    url_key: "sam-huff"
    albums: ["outta-here", "live-at-bonnaroo"]  # On multiple albums
    canonical_album: "outta-here"
    aliases: []
    type: "original"
  - key: "bowzers-jungle-romp"
    name: "Bowzer's Jungle Romp"
    url_key: "bowzers-jungle-romp"
    albums: ["live-only"]    # Never on studio album
    canonical_album: "live-only"
    aliases: ["bowzers", "jungle romp"]
    type: "original"

# Optional: Handle medleys/segues (Fix #46)
medleys:
  - pattern: "Phyllis > Sam Huff"
    tracks: ["phyllis", "sam-huff"]
    separator: ">"
  - pattern: "Funk Medley"
    tracks: ["phyllis", "sam-huff", "the-flu"]
    type: "named_medley"
```

- [ ] Create template file with documentation
- [ ] Document key stability requirement
- [ ] Add validator rules for new structure

---

### Task 2.4: Create Validate Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/ValidateArtistCommand.php`

**Usage:**
```bash
bin/magento archive:validate lettuce
bin/magento archive:validate --all
```

**Output example:**
```
Validating Lettuce configuration...

Errors (2):
  - tracks[5].url_key "the flu!" contains invalid characters
  - tracks[12] missing required album field

Warnings (1):
  - tracks[8] has no aliases

Validation FAILED. Fix errors before proceeding.
```

- [ ] Color-coded output (red errors, yellow warnings)
- [ ] Exit code 1 on errors, 0 on success
- [ ] `--all` flag validates all artist YAMLs

---

## 🟧 P1 - Data Migration

### Task 2.5: Export Hardcoded Data to YAML
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/MigrateExportCommand.php`

**Source files to extract from:**
- `Setup/Patch/Data/CreateCategoryStructure.php` (7 artists)
- `Setup/Patch/Data/AddAdditionalArtists.php` (28 artists)
- `Setup/Patch/Data/AddTracksGroup{1-5}.php` (track lists)

**Usage:**
```bash
bin/magento archive:migrate:export --dry-run
bin/magento archive:migrate:export
```

**Output:** 35 YAML files in `config/artists/`

- [ ] Parse existing PHP arrays
- [ ] Convert to YAML structure (matching template)
- [ ] Validate all exported files pass validation
- [ ] Report any manual fixes needed

---

### Task 2.6: Create Setup Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/SetupArtistCommand.php`

**Usage:**
```bash
bin/magento archive:setup lettuce
bin/magento archive:setup lettuce --dry-run
```

**What it does:**
1. Load YAML config
2. Validate config
3. Create artist category (under root)
4. Create album categories (under artist)
5. Create track categories (under albums) - if using categories for tracks

**Features:**
- [ ] Extends `BaseLoggedCommand` (from Phase 3)
- [ ] Idempotent - checks existing categories before creating
- [ ] Dry-run mode shows what would be created
- [ ] Reports: "Created 3 new categories, skipped 12 existing"

---

### Task 2.7: Delete Old Data Patches
**Remove files:**
- `Setup/Patch/Data/CreateCategoryStructure.php`
- `Setup/Patch/Data/AddAdditionalArtists.php`
- `Setup/Patch/Data/AddTracksGroup1.php`
- `Setup/Patch/Data/AddTracksGroup2.php`
- `Setup/Patch/Data/AddTracksGroup3.php`
- `Setup/Patch/Data/AddTracksGroup4.php`
- `Setup/Patch/Data/AddTracksGroup5.php`

**Clean database:**
```sql
DELETE FROM patch_list WHERE patch_name LIKE '%CreateCategoryStructure%';
DELETE FROM patch_list WHERE patch_name LIKE '%AddAdditionalArtists%';
DELETE FROM patch_list WHERE patch_name LIKE '%AddTracksGroup%';
```

**Checklist:**
- [ ] Verify ALL YAML files exported and validated
- [ ] Verify `archive:setup --all` creates identical categories
- [ ] Delete PHP files
- [ ] Clean patch_list table
- [ ] Test: `bin/magento setup:upgrade` completes without errors

---

## Verification Checklist

Before moving to Phase 3:

```bash
# 1. Validate all YAMLs
bin/magento archive:validate --all
# Should report: "35 artists validated, 0 errors"

# 2. List exported YAMLs
ls app/code/ArchiveDotOrg/Core/config/artists/
# Should show 35 .yaml files

# 3. Test setup idempotency
bin/magento archive:setup lettuce
bin/magento archive:setup lettuce
# Second run should say "0 new categories created"

# 4. Verify categories match
# Compare category tree before/after migration
```

---

## Rollback Plan

If YAML migration fails:
```bash
# Re-run old data patches (they're in git history)
git checkout HEAD~1 -- src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/

# Re-register patches
bin/magento setup:upgrade
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 3: Commands & Matching](./04-PHASE-3-COMMANDS.md)
