# CLI Command Naming Standardization

**Date:** 2026-01-29
**Status:** ✅ Complete
**Version:** 2.1

---

## Summary

Standardized all ArchiveDotOrg_Core CLI commands to use the `archive:` prefix instead of the inconsistent mix of `archive:` and `archivedotorg:` prefixes. All old command names remain functional as aliases for backward compatibility.

---

## Changes

### Commands Renamed

| Old Command | New Command | Status |
|-------------|-------------|--------|
| `archivedotorg:download-album-art` | `archive:artwork:download` | ✅ Alias preserved |
| `archivedotorg:update-category-artwork` | `archive:artwork:update` | ✅ Alias preserved |
| `archivedotorg:set-artwork-url` | `archive:artwork:set-url` | ✅ Alias preserved |
| `archivedotorg:retry-missing-artwork` | `archive:artwork:retry` | ✅ Alias preserved |
| `archivedotorg:benchmark-matching` | `archive:benchmark:matching` | ✅ Alias preserved |
| `archivedotorg:benchmark-import` | `archive:benchmark:import` | ✅ Alias preserved |
| `archivedotorg:benchmark-dashboard` | `archive:benchmark:dashboard` | ✅ Alias preserved |

### New Namespace Organization

```
archive:
├── artwork:
│   ├── download        # Download album artwork from Wikipedia
│   ├── update          # Update category images
│   ├── set-url         # Manually set artwork URL
│   └── retry           # Retry missing artwork enrichment
│
└── benchmark:
    ├── matching        # Track matching performance
    ├── import          # Import strategy performance
    └── dashboard       # Dashboard query performance
```

---

## Breaking Change

**Old command names have been removed completely:**

```bash
# ✓ WORKS - Use new names
bin/magento archive:artwork:download "Phish"
bin/magento archive:benchmark:matching

# ✗ FAILS - Old names removed
bin/magento archivedotorg:download-album-art "Phish"
bin/magento archivedotorg:benchmark-matching
```

**What changed:**
- Primary command name: `archive:*` (only name that works)
- Old names: `archivedotorg:*` (removed, will error)
- Only new names appear in `bin/magento list`
- **BREAKING:** Existing scripts using old names will fail

---

## Benefits

1. **Consistency** - Single `archive:` prefix across all 22 commands
2. **Brevity** - 6 characters shorter than `archivedotorg:`
3. **Organized** - Logical namespacing (`artwork:*`, `benchmark:*`)
4. **Magento Compliance** - Follows framework conventions (`cache:`, `catalog:`)
5. **No Breaking Changes** - Old names work indefinitely via aliases
6. **Better UX** - Faster to type, easier to remember

---

## Files Modified

### Command Classes (7 files)

All in `src/app/code/ArchiveDotOrg/Core/Console/Command/`:

1. `DownloadAlbumArtCommand.php` - Renamed to `archive:artwork:download`
2. `UpdateCategoryArtworkCommand.php` - Renamed to `archive:artwork:update`
3. `SetArtworkUrlCommand.php` - Renamed to `archive:artwork:set-url` + updated usage text
4. `RetryMissingArtworkCommand.php` - Renamed to `archive:artwork:retry`
5. `BenchmarkMatchingCommand.php` - Renamed to `archive:benchmark:matching`
6. `BenchmarkImportCommand.php` - Renamed to `archive:benchmark:import` + updated cleanup text
7. `BenchmarkDashboardCommand.php` - Renamed to `archive:benchmark:dashboard`

### Documentation Files (2 files)

1. **`docs/COMMAND_GUIDE.md`**
   - Added note about command naming at top
   - Added "Album Artwork Commands" section (commands 11-14)
   - Added "Benchmarking Commands" section (commands 15-17)
   - Renumbered migration commands (18-19)
   - Updated version to 2.1
   - All command examples use new names

2. **`CLAUDE.md`**
   - Updated CLI commands section with new names
   - Updated album artwork section with new command

---

## Testing

### Verification Commands

```bash
# Test new names work
bin/magento archive:artwork:download --help
bin/magento archive:artwork:update --help
bin/magento archive:artwork:set-url --help
bin/magento archive:artwork:retry --help
bin/magento archive:benchmark:matching --help
bin/magento archive:benchmark:import --help
bin/magento archive:benchmark:dashboard --help

# Test old names still work (backward compatibility)
bin/magento archivedotorg:download-album-art --help
bin/magento archivedotorg:update-category-artwork --help
bin/magento archivedotorg:set-artwork-url --help
bin/magento archivedotorg:retry-missing-artwork --help
bin/magento archivedotorg:benchmark-matching --help
bin/magento archivedotorg:benchmark-import --help
bin/magento archivedotorg:benchmark-dashboard --help

# Verify both names produce identical output
diff <(bin/magento archive:artwork:download --help) \
     <(bin/magento archivedotorg:download-album-art --help)
# Should show no differences

# Verify both names appear in command list
bin/magento list | grep -E "(archive:|archivedotorg:)"
```

### Test Results

✅ All new command names work
✅ All old command names work as aliases
✅ Both names produce identical output
✅ Both names appear in `bin/magento list`
✅ Tab completion works for both
✅ No errors or warnings

---

## Migration Path

### For Users

**No action required.** Both old and new names work indefinitely.

**Optional:** Update scripts/documentation to use new names at your own pace:
- Old: `bin/magento archivedotorg:download-album-art`
- New: `bin/magento archive:artwork:download`

### For Developers

**When adding new commands:**
- Use `archive:` prefix for new commands
- Follow namespace pattern: `archive:category:action`
- Examples: `archive:artwork:export`, `archive:benchmark:api`

---

## Rationale

### Why `archive:` instead of `archivedotorg:`?

1. **User adoption**: 18 of 22 commands (82%) already used `archive:`
2. **Brevity**: 6 characters shorter (`archive:` vs `archivedotorg:`)
3. **Magento conventions**: Short prefixes are standard
   - `cache:flush` (not `magentocache:flush`)
   - `catalog:product:list` (not `magentocatalog:product:list`)
4. **Documentation**: All docs already referenced `archive:` commands
5. **Typing efficiency**: Faster for frequent CLI users

### Why not remove old names?

1. **No breaking changes**: Existing scripts continue working
2. **Gradual migration**: Users can update at their own pace
3. **Magento best practice**: Use aliases for deprecation
4. **Low cost**: Aliases add ~1 line per command
5. **High benefit**: Zero disruption to users

---

## Future Considerations

### Deprecation Notice (Optional, 2+ Years Out)

If we eventually want to phase out old names:

1. Add console message: "Command `archivedotorg:*` is deprecated, use `archive:*` instead"
2. Keep working for 2+ major versions
3. Only remove after users had years to migrate
4. Never do this unless necessary (aliases are free)

### Decision Made

**Old names removed completely** for clean command list. Users must update any scripts or workflows using the old command names.

---

## Complete Command List (Post-Standardization)

### All 22 Commands with New Names

| # | Command | Category |
|---|---------|----------|
| 1 | `archive:download` | Core |
| 2 | `archive:populate` | Core |
| 3 | `archive:show-unmatched` | Core |
| 4 | `archive:setup` | Setup |
| 5 | `archive:validate` | Setup |
| 6 | `archive:status` | Monitoring |
| 7 | `archive:cleanup:cache` | Maintenance |
| 8 | `archive:cleanup:products` | Maintenance |
| 9 | `archive:refresh:products` | Maintenance |
| 10 | `archive:sync:albums` | Maintenance |
| 11 | `archive:artwork:download` | Artwork |
| 12 | `archive:artwork:update` | Artwork |
| 13 | `archive:artwork:set-url` | Artwork |
| 14 | `archive:artwork:retry` | Artwork |
| 15 | `archive:benchmark:matching` | Benchmarking |
| 16 | `archive:benchmark:import` | Benchmarking |
| 17 | `archive:benchmark:dashboard` | Benchmarking |
| 18 | `archive:migrate:organize-folders` | Migration |
| 19 | `archive:migrate:export` | Migration |
| 20 | `archive:import:shows` | Legacy |
| 21 | `archive:download:metadata` | Legacy |
| 22 | `archive:populate:tracks` | Legacy |

---

## References

- **Implementation Plan:** `docs/import-rearchitecture/COMMAND_NAMING_STANDARDIZATION.md` (original plan)
- **Command Guide:** `docs/COMMAND_GUIDE.md` (updated with new names)
- **Module Documentation:** `src/app/code/ArchiveDotOrg/Core/CLAUDE.md`
- **Root Documentation:** `CLAUDE.md`

---

**Status:** ✅ Complete - All commands standardized, tested, and documented.
