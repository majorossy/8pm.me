# Archive.org Import System - Admin User Guide

**Version:** 2.0
**Last Updated:** 2026-01-28
**For:** Store Administrators & Content Managers

---

## Table of Contents

1. [Dashboard Overview](#dashboard-overview)
2. [Managing Artists](#managing-artists)
3. [Import History](#import-history)
4. [Resolving Unmatched Tracks](#resolving-unmatched-tracks)
5. [Performance Tuning](#performance-tuning)
6. [Common Tasks](#common-tasks)
7. [Troubleshooting](#troubleshooting)

---

## Dashboard Overview

Access the Archive.org Import Dashboard from:

**Admin Panel → Content → Archive.org Import**

The dashboard provides three main sections:
- **Artists**: Manage all configured artists, view statistics, trigger imports
- **Import History**: Track all download and populate operations
- **Unmatched Tracks**: Review and resolve tracks that couldn't be auto-matched

---

## Managing Artists

### Viewing Artist Status

Navigate to **Content → Archive.org Import → Artists**

The Artist Grid shows:

| Column | Description |
|--------|-------------|
| **Artist Name** | Full artist name (e.g., "Grateful Dead", "Phish") |
| **Collection ID** | Archive.org collection identifier |
| **Shows Downloaded** | Number of shows with JSON metadata downloaded |
| **Shows Processed** | Number of shows converted to products |
| **Tracks Matched** | Successfully matched tracks |
| **Tracks Unmatched** | Tracks needing manual resolution |
| **Match Rate** | Percentage of tracks successfully matched (target: >95%) |
| **Last Download** | Timestamp of most recent download operation |
| **Last Populate** | Timestamp of most recent populate operation |

### Artist Status Indicators

**Match Rate Colors:**
- 🟢 **Green (95-100%)**: Excellent - no action needed
- 🟡 **Yellow (90-95%)**: Good - minor unmatched tracks
- 🟠 **Orange (80-90%)**: Fair - review unmatched tracks
- 🔴 **Red (<80%)**: Poor - requires immediate attention

**Shows Processed:**
- If **Processed < Downloaded**, there are shows waiting to be populated
- Click **Populate** to convert remaining shows to products

### Triggering Operations

Each artist row has action buttons:

**Download Button:**
- Downloads new shows from Archive.org
- Progress visible in real-time (if real-time features enabled)
- Safe to run multiple times (incremental by default)

**Populate Button:**
- Converts downloaded JSON metadata into catalog products
- Matches tracks against configured aliases
- Logs unmatched tracks for manual resolution

**View Unmatched Button:**
- Jumps directly to Unmatched Tracks grid filtered for this artist
- Shows tracks requiring manual alias configuration

### Adding a New Artist

**Prerequisites:** YAML configuration file must exist (contact developer)

**Steps:**
1. Developer creates YAML file: `config/artists/{artist-name}.yaml`
2. Run setup command (via CLI or dashboard button):
   ```
   Click "Setup Artist" button in dashboard
   ```
3. This creates category structure for albums
4. Click **Download** to fetch metadata from Archive.org
5. Click **Populate** to create products
6. Review **Match Rate** - should be >90%
7. If Match Rate is low, proceed to **Resolving Unmatched Tracks**

---

## Import History

Navigate to **Content → Archive.org Import → Import History**

This grid shows all import operations (downloads and populates) with:

| Column | Description |
|--------|-------------|
| **Correlation ID** | Unique identifier for tracking (UUID format) |
| **Artist** | Artist name |
| **Command** | Operation type (download, populate, etc.) |
| **Status** | Running, Completed, or Failed |
| **Started** | Timestamp when operation began |
| **Completed** | Timestamp when operation finished (or "(running)" if active) |
| **Duration** | How long the operation took (e.g., "2h 15m", "45s") |
| **Shows** | Number of shows processed |
| **Tracks** | Number of tracks processed |
| **Error** | Error message (if status = Failed) |

### Filtering History

Use the filters above the grid:

**Artist Filter:**
- Select from dropdown to view operations for specific artist
- Useful for tracking a single artist's import history

**Command Type Filter:**
- Download: Metadata retrieval operations
- Populate: Product creation operations
- Status: Read-only status checks
- Other: Cleanup, validation, etc.

**Status Filter:**
- **Running**: Currently executing (real-time progress if enabled)
- **Completed**: Finished successfully
- **Failed**: Encountered an error (check Error column)

**Date Range Filter:**
- Last 24 hours
- Last 7 days
- Last 30 days
- Custom range

### Understanding Failed Imports

If **Status = Failed**, check the **Error** column for details:

**Common Errors:**

| Error Message | Cause | Solution |
|---------------|-------|----------|
| "Lock already held by process..." | Concurrent operation on same artist | Wait for other operation to complete, or kill stale lock |
| "Rate limit exceeded" | Archive.org API throttling | Wait and retry - command has built-in retry logic |
| "Progress file corrupted" | Interrupted write operation | Auto-recovered - retry operation |
| "YAML validation failed" | Invalid artist configuration | Contact developer to fix YAML file |

### Retrying Failed Imports

Click **Retry** button in the Actions column to:
- Re-run the same command with same parameters
- Continues from last successful point (if progress tracking enabled)

---

## Resolving Unmatched Tracks

Navigate to **Content → Archive.org Import → Unmatched Tracks**

This grid shows tracks that could NOT be automatically matched to configured track names.

### Understanding Unmatched Tracks

**Why do tracks go unmatched?**
1. **Misspellings**: "Twezer" vs. "Tweezer"
2. **Abbreviations**: "YEM" vs. "You Enjoy Myself"
3. **Punctuation differences**: "Free-form" vs. "Freeform"
4. **New/unconfigured tracks**: Track exists in live shows but not in YAML

### Unmatched Tracks Grid

| Column | Description |
|--------|-------------|
| **Track Name** | Exact name found in Archive.org metadata |
| **Artist** | Artist name |
| **Occurrences** | How many times this track appeared across all shows |
| **Suggested Match** | Auto-suggested match from phonetic matching (if available) |
| **Example Show** | One show identifier where this track appeared |
| **First Seen** | First time this track was encountered |
| **Last Seen** | Most recent occurrence |
| **Resolved** | Checkbox indicating if this has been manually handled |

### Priority Indicators

Tracks are automatically prioritized based on occurrence count:

- 🔴 **High Priority (10+ occurrences)**: Appears frequently - likely a misspelling or missing alias
- 🟡 **Medium Priority (3-9 occurrences)**: Moderately common - review recommended
- 🟢 **Low Priority (1-2 occurrences)**: Rare variant or one-off error - low urgency

### Resolution Workflow

**For each unmatched track:**

#### Option 1: Add as Alias (Recommended for Misspellings)

**When to use:** Suggested match looks correct

**Steps:**
1. Review **Suggested Match** column
2. If match looks correct (e.g., "Twezer" → "Tweezer"), click **Add as Alias**
3. System automatically adds to YAML configuration
4. Re-run populate to match all instances:
   - Navigate to **Artists** grid
   - Click **Populate** for the artist
5. Track should now disappear from Unmatched Tracks grid

**Example:**
```
Track Name: "The Flue"
Suggested Match: "The Flu"
Action: Click "Add as Alias"
Result: All 5 occurrences now matched to "The Flu"
```

#### Option 2: Manual YAML Edit (For New Tracks)

**When to use:** Track doesn't exist in configuration at all

**Steps:**
1. Note the **Track Name** (e.g., "New Jam")
2. Contact developer or edit YAML file directly:
   ```yaml
   tracks:
     - key: "new-jam"
       name: "New Jam"
       url_key: "new-jam"
       albums: ["live-only"]
       canonical_album: "live-only"
       type: "jam"
   ```
3. Re-run populate
4. Mark as **Resolved** in grid

#### Option 3: Mark as Resolved (Ignore)

**When to use:** Track is an error, one-off occurrence, or not worth configuring

**Steps:**
1. Select track(s) in grid (checkbox on left)
2. Click **Actions** → **Mark as Resolved**
3. Track remains in database but hidden from default view
4. Can be unmarked later if needed

### Mass Actions

Select multiple tracks using checkboxes to perform bulk operations:

**Actions:**
- **Mark as Resolved**: Hide multiple tracks at once
- **Mark as Unresolved**: Un-hide previously resolved tracks
- **Export to YAML Template**: Generates YAML snippet for easy copy-paste into config file

### Export to YAML Template

Click **Export to YAML Template** to generate a ready-to-use YAML snippet:

```yaml
# Generated from Unmatched Tracks - 2026-01-28
tracks:
  - key: "twezer"  # SUGGESTED: Add as alias to "tweezer"
    name: "Twezer"
    aliases: []
    # Occurrences: 12, First seen: 2025-12-15

  - key: "the-flue"  # SUGGESTED: Add as alias to "the-flu"
    name: "The Flue"
    aliases: []
    # Occurrences: 5, First seen: 2026-01-02
```

**How to use:**
1. Copy exported YAML
2. Paste into `config/artists/{artist}.yaml`
3. Adjust as needed (merge with existing tracks, add to aliases)
4. Save and re-run populate

---

## Performance Tuning

### Import Speed Optimization

**Download Performance:**
- **Bottleneck**: Archive.org API rate limiting (~1 request/second)
- **Expected time**: ~3.5 hours per 10,000 shows
- **Optimization**: Use `--incremental` flag to skip already-downloaded shows

**Populate Performance:**
- **Bottleneck**: Database writes (ORM overhead)
- **Optimization**: System uses BulkProductImporter (~10x faster than standard ORM)
- **Expected time**: 1,000 products in <5 minutes

### Batch Size Recommendations

**Recommended batch sizes:**

| Operation | Batch Size | Frequency |
|-----------|------------|-----------|
| **Initial download** | 500-1000 shows | One-time per artist |
| **Incremental download** | 50-100 shows | Daily/weekly |
| **Populate** | 1000+ shows | After each download |
| **Unmatched review** | 10-20 tracks | Weekly |

### Cache Management

**When to clear cache:**
- After YAML configuration changes
- After database schema updates
- If dashboard shows stale data

**How to clear cache:**
```bash
Admin Panel → System → Cache Management → Flush Magento Cache
```

Or via CLI:
```bash
bin/magento cache:flush
```

### Cron Scheduling (If Enabled)

**Recommended cron schedule:**

| Job | Schedule | Purpose |
|-----|----------|---------|
| **Auto-download** | Daily at 2 AM | Fetch new shows automatically |
| **Auto-populate** | Daily at 4 AM | Convert new downloads to products |
| **Metrics aggregation** | Daily at midnight | Update dashboard charts |
| **Cleanup old progress** | Weekly (Sunday) | Remove stale progress files |

**To enable cron jobs:**
```bash
bin/magento cron:run --group=archivedotorg
```

---

## Common Tasks

### Daily Maintenance

**Morning routine (5 minutes):**
1. Check **Artists** grid for low match rates (<90%)
2. Review **Import History** for any failed operations
3. Check **Unmatched Tracks** for high-priority items (10+ occurrences)

### Weekly Tasks

**Quality assurance (30 minutes):**
1. Review all **Unmatched Tracks** for the week
2. Add aliases for common misspellings
3. Re-run populate for artists with updates
4. Verify match rates improved

### Monthly Review

**Performance check (1 hour):**
1. Review overall match rates across all artists
2. Identify artists with consistently low match rates
3. Work with developer to improve YAML configurations
4. Archive or delete old import history (optional)

---

## Troubleshooting

### Dashboard is Slow

**Symptom:** Artists or History grid takes >5 seconds to load

**Causes:**
1. Missing database indexes
2. Too many records (>10,000 import runs)
3. Cache disabled

**Solutions:**
1. Check indexes via CLI:
   ```bash
   bin/mysql -e "SHOW INDEX FROM archivedotorg_artist_status;"
   ```
2. Archive old import history (>6 months)
3. Enable full-page cache

### Import Shows as "Running" Forever

**Symptom:** Import History shows status = "Running" but process finished hours ago

**Causes:**
1. Command crashed without updating status
2. Database write failed

**Solutions:**
1. Check if process is actually running:
   ```
   Search for PID in server process list
   ```
2. Manually update status in database (contact developer)
3. Re-run operation with `--force` flag

### Match Rate Suddenly Dropped

**Symptom:** Artist that had 95% match rate now shows 75%

**Causes:**
1. New show with many previously unseen tracks
2. Archive.org metadata changed (rare)
3. YAML configuration accidentally modified

**Solutions:**
1. Check **Unmatched Tracks** grid for new entries
2. Review recent shows for pattern (new venue, guest artist, etc.)
3. Add new tracks or aliases to YAML
4. Re-run populate

### Cannot Trigger Download/Populate

**Symptom:** Click "Download" or "Populate" button, nothing happens

**Causes:**
1. Lock already held by another process
2. Permissions issue
3. JavaScript error in browser

**Solutions:**
1. Check **Import History** for "Running" operations on same artist
2. Open browser console (F12) and check for JavaScript errors
3. Refresh page and retry
4. Contact developer if error persists

---

## FAQ

**Q: How long does it take to import an artist with 1,000 shows?**

A: Download: ~1 hour (API rate limiting), Populate: ~10-15 minutes

**Q: What happens if I click "Download" twice?**

A: System uses lock protection. Second click will fail with "Lock already held" error.

**Q: Can I import shows from multiple artists simultaneously?**

A: Yes! Different artists can be imported in parallel without conflicts.

**Q: What's the difference between "Shows Downloaded" and "Shows Processed"?**

A: Downloaded = JSON metadata saved to disk, Processed = Converted to catalog products

**Q: How do I know if my YAML changes worked?**

A: After editing YAML, re-run populate and check match rate. Should increase if aliases were correct.

**Q: Can I delete unmatched tracks?**

A: You can mark them as "Resolved" to hide them, but data is preserved for audit purposes.

**Q: Why are some tracks showing confidence <100%?**

A: Confidence indicates match quality:
- 100% = Exact match
- 95% = Alias match
- 85% = Phonetic (metaphone) match
- 80-90% = Fuzzy match (limited)

**Q: How often should I review unmatched tracks?**

A: Weekly for high-priority (10+ occurrences), monthly for medium/low priority.

---

## Getting Help

**Dashboard Issues:**
- Check browser console for JavaScript errors (F12 → Console tab)
- Clear browser cache and cookies
- Try different browser (Chrome, Firefox, Safari)

**Data Issues:**
- Review `var/log/archivedotorg.log` for errors
- Check **Import History** for failed operations
- Contact developer with correlation ID from failed import

**Performance Issues:**
- Verify database indexes exist
- Check server resources (CPU, memory, disk)
- Review cron job scheduling

**Configuration Issues:**
- Validate YAML syntax (contact developer)
- Check artist collection ID matches Archive.org exactly
- Verify category structure exists (run setup command)

---

## Next Steps

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Technical documentation for customization
- **[Import Rearchitecture Plan](import-rearchitecture/)** - Full implementation documentation
- **[FIXES.md](import-rearchitecture/FIXES.md)** - Known issues and solutions

---

**Questions?** Contact your system administrator or review logs at `var/log/archivedotorg.log`
