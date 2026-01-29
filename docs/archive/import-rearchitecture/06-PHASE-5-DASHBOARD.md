# Phase 5: Admin Dashboard

**Timeline:** Week 8-10
**Status:** ⏸️ Blocked by Phase 3
**Prerequisites:** Phase 0-3 complete, Phase 4 recommended

---

## ✅ DECISION: Split into 5a (MVP) and 5b (Enhanced)

**Phase 5a (MVP) - Week 8:** Essential grids only
- Artist grid with status
- Import history grid
- Basic dashboard stats

**Phase 5b (Enhanced) - Week 9-10:** Polish and visualization
- Charts (ApexCharts)
- Real-time progress polling
- Unmatched tracks management

This allows shipping useful admin visibility sooner.

---

## Overview

Create an admin dashboard for monitoring import status, viewing unmatched tracks, and managing artists.

**5a Features (MVP):**
- Dashboard overview with stats cards
- Artist grid with status
- Import history grid

**5b Features (Enhanced):**
- Unmatched tracks grid with suggestions
- Real-time progress polling
- Charts and visualizations

**Completion Criteria (5a):**
- [ ] Dashboard loads in <100ms
- [ ] Artist grid shows all 35 artists with status
- [ ] Import history is searchable/filterable

**Completion Criteria (5b):**
- [ ] Unmatched tracks show metaphone suggestions
- [ ] Real-time progress updates during imports
- [ ] Charts render correctly

---

## 🟨 P2 - Database Tables

### Task 5.1: Create import_run Table
**File:** `src/app/code/ArchiveDotOrg/Admin/Setup/Patch/Schema/CreateImportRunTable.php`
**SQL:** `migrations/005_create_import_run_table.sql`

**Columns:**
- `run_id` - Primary key
- `correlation_id` - UUID for linking logs
- `artist_id` - FK to artist table
- `command` - Command name (download, populate)
- `status` - running, completed, failed
- `started_at`, `completed_at` - Timestamps
- `options_json` - Command options (JSON)
- `error_message` - If failed
- `shows_processed`, `tracks_processed` - Counts

- [ ] Create schema patch
- [ ] Run migration
- [ ] Test: Insert run record, query history

---

### Task 5.2: Create artist_status Table
**File:** `src/app/code/ArchiveDotOrg/Admin/Setup/Patch/Schema/CreateArtistStatusTable.php`
**SQL:** `migrations/006_create_artist_status_table.sql`

**Columns:**
- `status_id` - Primary key
- `artist_id` - FK to artist table
- `shows_downloaded` - Count
- `shows_processed` - Count
- `tracks_matched` - Count
- `tracks_unmatched` - Count
- `match_rate` - Percentage
- `last_download_at`, `last_populate_at` - Timestamps

- [ ] Create schema patch
- [ ] Run migration
- [ ] Test: Update artist status after import

---

### Task 5.3: Create unmatched_track Table
**File:** `src/app/code/ArchiveDotOrg/Admin/Setup/Patch/Schema/CreateUnmatchedTrackTable.php`
**SQL:** `migrations/007_create_unmatched_track_table.sql`

**Columns:**
- `unmatched_id` - Primary key
- `artist_id` - FK to artist table
- `track_name` - Original track name from JSON
- `show_identifier` - Which show it came from
- `suggested_match` - Soundex suggestion
- `occurrence_count` - How many shows have this track
- `first_seen_at`, `last_seen_at` - Timestamps
- `resolved` - Boolean (admin marked as handled)

- [ ] Create schema patch
- [ ] Run migration
- [ ] Test: Insert unmatched track, query grid

---

### Task 5.4: Create daily_metrics Table
**File:** `src/app/code/ArchiveDotOrg/Admin/Setup/Patch/Schema/CreateDailyMetricsTable.php`
**SQL:** `migrations/008_create_daily_metrics_table.sql`

**Columns:**
- `metric_id` - Primary key
- `artist_id` - FK to artist table
- `date` - Date
- `shows_imported` - Count for day
- `tracks_imported` - Count for day
- `match_rate` - Rate for day

- [ ] Create schema patch
- [ ] Run migration
- [ ] Test: Aggregate daily stats

---

## 🟨 P2 - Models & Repositories

### Task 5.5: Create ImportRun Model
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Model/ImportRun.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/ImportRun.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/ImportRun/Collection.php`
- `src/app/code/ArchiveDotOrg/Admin/Api/ImportRunRepositoryInterface.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ImportRunRepository.php`

- [ ] Create model classes
- [ ] Register in DI
- [ ] Test: CRUD operations

---

### Task 5.6: Create ArtistStatus Model
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Model/ArtistStatus.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/ArtistStatus.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/ArtistStatus/Collection.php`

- [ ] Create model classes
- [ ] Register in DI
- [ ] Test: Update status after download/populate

---

### Task 5.7: Create UnmatchedTrack Model
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Model/UnmatchedTrack.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/UnmatchedTrack.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/UnmatchedTrack/Collection.php`

- [ ] Create model classes
- [ ] Register in DI
- [ ] Test: Insert/query unmatched tracks

---

### Task 5.8: Create DailyMetrics Model
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Model/DailyMetrics.php`
- `src/app/code/ArchiveDotOrg/Admin/Model/ResourceModel/DailyMetrics.php`

- [ ] Create model classes
- [ ] Register in DI
- [ ] Test: Aggregate and save daily stats

---

## 🟨 P2 - Redis Progress Tracking

### Task 5.9: Create ProgressTracker Service
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/Redis/ProgressTracker.php`

**Redis keys:**
```
archivedotorg:progress:{artist}:current      # Current item number
archivedotorg:progress:{artist}:total        # Total items
archivedotorg:progress:{artist}:processed    # Processed count
archivedotorg:progress:{artist}:eta          # Estimated completion
archivedotorg:progress:{artist}:status       # running/completed/failed
archivedotorg:progress:{artist}:correlation_id
```

**Features:**
- [ ] Set 1hr TTL on all keys
- [ ] Update method called from commands
- [ ] Clean up on completion
- [ ] Handle Redis connection failures gracefully

---

### Task 5.10: Integrate ProgressTracker with BaseLoggedCommand
**Modify:** `src/app/code/ArchiveDotOrg/Core/Console/Command/BaseLoggedCommand.php`

- [ ] Inject ProgressTracker
- [ ] Update Redis progress after each show/track
- [ ] Clean up keys on completion/failure
- [ ] Test: Run download, verify Redis keys updated

---

## 🟨 P2 - Admin Controllers

### Task 5.11: Create Admin Module Structure
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/registration.php`
- `src/app/code/ArchiveDotOrg/Admin/etc/module.xml`
- `src/app/code/ArchiveDotOrg/Admin/etc/adminhtml/menu.xml`
- `src/app/code/ArchiveDotOrg/Admin/etc/adminhtml/routes.xml`

**Menu structure:**
```
Content
└── Archive.org Import
    ├── Dashboard
    ├── Artists
    ├── Import History
    └── Unmatched Tracks
```

- [ ] Create module files
- [ ] Define menu items
- [ ] Define routes
- [ ] Test: Menu appears in admin sidebar

---

### Task 5.12: Create Dashboard Controller
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/Dashboard/Index.php`
- `src/app/code/ArchiveDotOrg/Admin/Block/Adminhtml/Dashboard.php`
- `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/layout/archivedotorg_dashboard_index.xml`
- `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/templates/dashboard.phtml`

**Dashboard cards:**
- Total artists (35)
- Total shows downloaded
- Total tracks imported
- Overall match rate
- Active imports (real-time)

- [ ] Create controller
- [ ] Create block
- [ ] Create layout XML
- [ ] Create template
- [ ] Test: Dashboard loads with stat cards

---

### Task 5.13: Create Artist Grid
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/Artist/Index.php`
- `src/app/code/ArchiveDotOrg/Admin/Ui/Component/Listing/ArtistDataProvider.php`
- `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/ui_component/archivedotorg_artist_listing.xml`

**Grid columns:**
- Artist name
- Shows downloaded
- Shows processed
- Match rate
- Last download
- Last populate
- Actions (Download, Populate, View Unmatched)

- [ ] Create controller
- [ ] Create data provider
- [ ] Create UI component XML
- [ ] Test: Grid shows all artists with status

---

### Task 5.14: Create Import History Grid
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/History/Index.php`
- `src/app/code/ArchiveDotOrg/Admin/Ui/Component/Listing/HistoryDataProvider.php`
- `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/ui_component/archivedotorg_history_listing.xml`

**Grid columns:**
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

- [ ] Create controller
- [ ] Create data provider
- [ ] Create UI component XML
- [ ] Test: Grid shows import runs, filterable

---

### Task 5.15: Create Unmatched Tracks Grid
**Create:**
- `src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/Unmatched/Index.php`
- `src/app/code/ArchiveDotOrg/Admin/Ui/Component/Listing/UnmatchedDataProvider.php`
- `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/ui_component/archivedotorg_unmatched_listing.xml`

**Grid columns:**
- Track name
- Artist
- Occurrences
- Suggested match
- First seen
- Resolved (checkbox)

**Actions:**
- Mark as resolved
- Add to YAML aliases (future)

- [ ] Create controller
- [ ] Create data provider
- [ ] Create UI component XML
- [ ] Test: Grid shows unmatched tracks with suggestions

---

### Task 5.16: Create Progress AJAX Endpoint
**Create:** `src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/Progress/Status.php`

**Returns:**
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

- [ ] Create controller
- [ ] Read from Redis
- [ ] Return JSON
- [ ] Test: Poll endpoint during active import

---

## 🟨 P2 - Visualizations

### Task 5.17: Add ApexCharts Library
**Create:** `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/requirejs-config.js`
**Download:** `apexcharts.min.js` to `view/adminhtml/web/js/lib/`

- [ ] Add RequireJS config
- [ ] Include ApexCharts
- [ ] Test: Library loads on dashboard

---

### Task 5.18: Create Imports Per Day Chart
**Create:** `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/web/js/dashboard-charts.js`

**Type:** Bar chart
**Data source:** `archivedotorg_daily_metrics`
**Range:** Last 7/30 days

- [ ] Create chart initialization
- [ ] AJAX data fetch
- [ ] Test: Shows last 7 days of imports

---

### Task 5.19: Create Match Rate Gauge
**Add to:** `dashboard-charts.js`

**Type:** Radial bar (gauge)
**Data source:** `archivedotorg_artist_status` (aggregate)
**Display:** 0-100%

- [ ] Add gauge chart
- [ ] Style with colors (red < 80%, yellow 80-95%, green > 95%)
- [ ] Test: Shows overall match rate

---

### Task 5.20: Create Real-Time Progress Widget
**Create:** `src/app/code/ArchiveDotOrg/Admin/view/adminhtml/web/js/progress-poller.js`

**Features:**
- [ ] Poll `/progress/status` every 2 seconds
- [ ] Update progress bar
- [ ] Show current item / total
- [ ] Show ETA
- [ ] Stop polling when status = completed/failed
- [ ] Test: Start download, watch progress update

---

## 🟨 P2 - Daily Metrics Aggregation

### Task 5.21: Create Aggregation Cron Job
**Create:** `src/app/code/ArchiveDotOrg/Admin/Cron/AggregateDailyMetrics.php`
**Register:** `etc/crontab.xml` (daily 4 AM)

**Logic:**
- Count tracks imported per artist per day
- Calculate match rates
- Store in `archivedotorg_daily_metrics`

- [ ] Create cron class
- [ ] Register in crontab.xml
- [ ] Test: Run manually, verify metrics aggregated

```xml
<config>
    <group id="archivedotorg">
        <job name="aggregate_daily_metrics" instance="ArchiveDotOrg\Admin\Cron\AggregateDailyMetrics" method="execute">
            <schedule>0 4 * * *</schedule>
        </job>
    </group>
</config>
```

---

## Verification Checklist

Before moving to Phase 6:

```bash
# 1. Admin menu visible
# Navigate to admin, verify Archive.org Import menu exists

# 2. Dashboard loads quickly
# Load dashboard, should be <100ms (check Network tab)

# 3. Grids work
# Test each grid: Artists, History, Unmatched
# Test filters and sorting

# 4. Real-time progress works
bin/magento archive:download lettuce --limit=50 &
# Watch dashboard for live progress updates

# 5. Charts render
# Verify bar chart and gauge on dashboard
```

---

## Next Phase

Once ALL tasks above are complete → [Phase 6: Testing & Documentation](./07-PHASE-6-TESTING.md)
