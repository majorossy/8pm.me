# ⚠️ BREAKING CHANGE: Command Names Standardized

**Date:** 2026-01-29
**Type:** Breaking Change
**Impact:** CLI Commands

---

## What Changed

Old command names with `archivedotorg:` prefix have been **removed**. Only the new `archive:` prefix works now.

---

## Commands Affected

| ✗ Old Command (Removed) | ✓ New Command (Use This) |
|------------------------|--------------------------|
| `archivedotorg:download-album-art` | `archive:artwork:download` |
| `archivedotorg:update-category-artwork` | `archive:artwork:update` |
| `archivedotorg:set-artwork-url` | `archive:artwork:set-url` |
| `archivedotorg:retry-missing-artwork` | `archive:artwork:retry` |
| `archivedotorg:benchmark-matching` | `archive:benchmark:matching` |
| `archivedotorg:benchmark-import` | `archive:benchmark:import` |
| `archivedotorg:benchmark-dashboard` | `archive:benchmark:dashboard` |

---

## Why This Change?

1. **Consistency** - All 22 commands now use `archive:` prefix
2. **Clean CLI** - No duplicate names in `bin/magento list`
3. **Better Organization** - Logical namespaces (`artwork:*`, `benchmark:*`)
4. **Magento Standards** - Follows framework conventions

---

## Action Required

### 1. Find Old Command Usage

Search your codebase for old command names:

```bash
cd /Users/chris.majorossy/Education/8pm

# Search all files
grep -r "archivedotorg:download-album-art" .
grep -r "archivedotorg:update-category-artwork" .
grep -r "archivedotorg:set-artwork-url" .
grep -r "archivedotorg:retry-missing-artwork" .
grep -r "archivedotorg:benchmark" .

# Check cron jobs
crontab -l | grep archivedotorg

# Check shell scripts
find . -name "*.sh" -exec grep -l "archivedotorg:" {} \;
```

### 2. Update Scripts

Replace old names with new names in:
- Shell scripts (`.sh` files)
- Cron jobs (`crontab -e`)
- Documentation (`.md` files)
- CI/CD pipelines
- Deployment scripts

**Example:**
```bash
# Before (fails now):
bin/magento archivedotorg:download-album-art "Phish" --limit=20

# After (works):
bin/magento archive:artwork:download "Phish" --limit=20
```

### 3. Update Global Instructions

Your global Claude instructions (`~/.claude/CLAUDE.md`) reference old commands:

```bash
# Edit global instructions
vim ~/.claude/CLAUDE.md

# Find and replace:
# archivedotorg:download-album-art → archive:artwork:download
```

---

## Testing

Verify all scripts work with new names:

```bash
# Test new names work
bin/magento archive:artwork:download --help
bin/magento archive:benchmark:matching --help

# Verify old names fail (expected)
bin/magento archivedotorg:download-album-art --help
# Should error: Command "archivedotorg:download-album-art" is not defined
```

---

## Benefits

After migration:

✅ **Clean command list** - No aliases cluttering `bin/magento list`
✅ **Consistent naming** - All commands use `archive:` prefix
✅ **Better organization** - Logical namespacing
✅ **Less confusion** - Single command name per function

---

## Rollback (If Needed)

If you need to temporarily restore old command names, revert these 7 files:

```bash
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/DownloadAlbumArtCommand.php
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/UpdateCategoryArtworkCommand.php
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/SetArtworkUrlCommand.php
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/RetryMissingArtworkCommand.php
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/BenchmarkMatchingCommand.php
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/BenchmarkImportCommand.php
git diff src/app/code/ArchiveDotOrg/Core/Console/Command/BenchmarkDashboardCommand.php

# To rollback
git checkout HEAD -- src/app/code/ArchiveDotOrg/Core/Console/Command/*.php
```

---

## Support

If you encounter issues:

1. Check logs: `tail -f var/log/system.log`
2. Clear cache: `bin/magento cache:flush`
3. Verify command list: `bin/magento list | grep archive:`

---

**Status:** ✅ Complete - Old commands removed, new commands active
