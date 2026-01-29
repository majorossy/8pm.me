# Phase 1: Folder Migration & Cleanup

**Timeline:** Week 3
**Status:** ⏸️ Blocked by Phase 0
**Prerequisites:** Phase 0 100% complete

---

## Overview

Reorganize the flat file structure into artist-based folders for better organization and performance.

**Current:** `var/archivedotorg/metadata/*.json` (2,130 files in one directory)
**Target:** `var/archivedotorg/metadata/{Artist}/*.json`

**Completion Criteria:**
- [ ] All 2,130 existing files migrated to new structure
- [ ] No orphaned files (all files mapped to an artist)
- [ ] MetadataDownloader creates files in new structure
- [ ] Backup of original structure preserved
- [ ] Manifest service provides fast directory scanning

---

## 🟧 P1 - File Organization

### Task 1.1: Implement Folder Migration Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/MigrateOrganizeFoldersCommand.php`

**Features:**
- [ ] Backup flat structure first (to `metadata.backup/`)
- [ ] Crash-safe (tracks progress in migration state file)
- [ ] Logs unmapped files for manual review
- [ ] Dry-run mode to preview changes

**Usage:**
```bash
bin/magento archive:migrate:organize-folders --dry-run
bin/magento archive:migrate:organize-folders
```

**Implementation notes:**
- Map files to artists using identifier prefix or existing DB records
- Create `var/archivedotorg/metadata/{Artist}/` directories
- Move files: `{identifier}.json` → `{Artist}/{identifier}.json`
- Track progress in `var/archivedotorg/migration_state.json`

---

### Task 1.2: Update MetadataDownloader for Subfolders
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/MetadataDownloader.php`

**Changes:**
- [ ] Update `getCacheFilePath()` to use `{collectionId}/{identifier}.json`
- [ ] Create artist directory if it doesn't exist
- [ ] Update all read/write operations
- [ ] Ensure progress file is also per-artist

**Before:**
```php
return $this->basePath . '/' . $identifier . '.json';
```

**After:**
```php
return $this->basePath . '/' . $collectionId . '/' . $identifier . '.json';
```

- [ ] Test: Download to new folder structure
- [ ] Test: Existing code reading files still works

---

### Task 1.3: Add File Manifest (Optional Optimization)
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/FileManifestService.php`

**Purpose:** Track files in `metadata/{Artist}/manifest.json` for 100x faster directory scanning.

**Features:**
- [ ] Update manifest after each download
- [ ] Manifest format:
```json
{
  "updated_at": "2026-01-28T10:00:00Z",
  "file_count": 523,
  "files": {
    "phish2024-01-01": {"size": 45000, "downloaded_at": "2026-01-27"},
    "phish2024-01-02": {"size": 52000, "downloaded_at": "2026-01-27"}
  }
}
```
- [ ] Rebuild manifest on demand: `bin/magento archive:rebuild-manifest phish`

**Benchmark:**
- `scandir()` on 10k files: ~500ms
- Manifest JSON read: ~5ms

---

### Task 1.4: Implement Cache Cleanup Command
**Create:** `src/app/code/ArchiveDotOrg/Core/Console/Command/CleanupCacheCommand.php`

**Usage:**
```bash
bin/magento archive:cleanup --older-than=90 --dry-run
bin/magento archive:cleanup --older-than=90
bin/magento archive:cleanup --keep-latest=1000 --artist=phish
```

**Flags:**
- [ ] `--older-than=N` - Delete files older than N days
- [ ] `--keep-latest=N` - Keep N most recent files per artist
- [ ] `--artist=X` - Only cleanup specific artist
- [ ] `--dry-run` - Preview what would be deleted

**Safety:**
- [ ] Never delete files that have associated products (check DB first)
- [ ] Always confirm before actual deletion
- [ ] Log all deletions

---

## Verification Checklist

Before moving to Phase 2:

```bash
# 1. Check folder structure
ls -la var/archivedotorg/metadata/
# Should show artist directories: Phish/, Lettuce/, GratefulDead/, etc.

# 2. Verify file counts
find var/archivedotorg/metadata -name "*.json" | wc -l
# Should be ~2130

# 3. Verify backup exists
ls -la var/archivedotorg/metadata.backup/
# Should contain original flat structure

# 4. Test download to new structure
bin/magento archive:download lettuce --limit=1
ls var/archivedotorg/metadata/Lettuce/
# Should show new file
```

---

## Rollback Plan

If migration fails:
```bash
# Restore from backup
rm -rf var/archivedotorg/metadata
mv var/archivedotorg/metadata.backup var/archivedotorg/metadata

# Revert MetadataDownloader changes
git checkout -- src/app/code/ArchiveDotOrg/Core/Model/MetadataDownloader.php
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 2: YAML Configuration](./03-PHASE-2-YAML.md)
