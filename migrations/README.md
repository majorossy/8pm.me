# Database Migrations - Phase 0

## Completed Tasks (Card 0.A)

### ✅ Migration 001: Artist Normalization Table
**File:** `001_create_artist_table.sql`  
**Schema Patch:** `CreateArtistTable.php`  
**Status:** ✅ Applied

Created `archivedotorg_artist` table as single source of truth for artist data.

**Columns:**
- `artist_id` - Primary key
- `artist_name` - Unique artist name
- `collection_id` - Unique Archive.org collection ID
- `url_key` - URL-safe identifier
- `yaml_file_path` - Path to YAML config (optional)
- `created_at`, `updated_at` - Timestamps

**Indexes:**
- PRIMARY KEY on `artist_id`
- UNIQUE on `artist_name`
- UNIQUE on `collection_id`
- INDEX on `url_key`

---

### ✅ Migration 002: Dashboard Performance Indexes
**File:** `002_add_dashboard_indexes.sql`  
**Schema Patch:** `AddDashboardIndexes.php`  
**Status:** ✅ Applied

Added index on `catalog_product_entity.created_at` for dashboard queries.

**Performance Target:** Dashboard loads in <100ms with 186k products

---

### ⏳ Migration 003: Convert JSON Columns
**File:** `003_convert_json_columns.sql`  
**Schema Patch:** `ConvertJsonColumns.php`  
**Status:** ⏳ Deferred to Phase 5

Will convert TEXT columns to JSON type when `archivedotorg_import_run` table is created in Phase 5.

**Note:** MariaDB 10.6 stores JSON as LONGTEXT internally (compatible with MariaDB 10.2+).

---

### ✅ Migration 004: Show Metadata Table
**File:** `004_create_show_metadata_table.sql`  
**Schema Patch:** `CreateShowMetadataTable.php`  
**Status:** ✅ Applied

Created `archivedotorg_show_metadata` table to extract large JSON from EAV.

**Columns:**
- `metadata_id` - Primary key
- `show_identifier` - Unique Archive.org identifier
- `artist_id` - Foreign key to `archivedotorg_artist`
- `reviews_json` - Show reviews (up to 2MB)
- `workable_servers` - Server list (up to 64KB)
- `created_at`, `updated_at` - Timestamps

**Constraints:**
- FOREIGN KEY `artist_id` → `archivedotorg_artist.artist_id` ON DELETE CASCADE

**Indexes:**
- PRIMARY KEY on `metadata_id`
- UNIQUE on `show_identifier`
- INDEX on `artist_id`

---

## Verification Results

```sql
-- All tables created
mysql> SHOW TABLES LIKE 'archivedotorg_%';
+------------------------------------+
| archivedotorg_activity_log        |
| archivedotorg_artist              | ← NEW
| archivedotorg_artwork_overrides   |
| archivedotorg_show_metadata       | ← NEW
| archivedotorg_studio_albums       |
+------------------------------------+

-- Foreign key constraint verified
mysql> SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
       FROM information_schema.KEY_COLUMN_USAGE 
       WHERE TABLE_NAME = 'archivedotorg_show_metadata';
+-------------------------------------+-------------------------+
| archivedotorg_show_metadata_ibfk_1 | archivedotorg_artist    |
+-------------------------------------+-------------------------+

-- Dashboard index verified
mysql> SHOW INDEX FROM catalog_product_entity WHERE Key_name = 'idx_created_at';
+------------------------+------------+-----------------+
| Table                  | Key_name   | Column_name     |
+------------------------+------------+-----------------+
| catalog_product_entity | idx_created_at | created_at  |
+------------------------+------------+-----------------+
```

---

## Rollback Instructions

If rollback is needed, run scripts in reverse order:

```bash
docker exec -i 8pm-db-1 mysql -u magento -pmagento magento < migrations/rollback/004_drop_show_metadata_table.sql
docker exec -i 8pm-db-1 mysql -u magento -pmagento magento < migrations/rollback/002_drop_dashboard_indexes.sql
docker exec -i 8pm-db-1 mysql -u magento -pmagento magento < migrations/rollback/001_drop_artist_table.sql
```

**WARNING:** Rollback will delete all data in these tables.

---

## Next Steps

1. **Phase -1 Prerequisites:**
   - Service interfaces (Card -1.A)
   - Exceptions & feature flags (Card -1.B)
   - Test plan alignment (Card -1.C)

2. **Phase 0 Remaining Tasks:**
   - Task 0.5: Implement file locking service
   - Task 0.6: Add atomic progress file writes
   - Task 0.7: Add progress file validation
   - Task 0.8: Document SKU generation format
   - Task 0.9: Add category duplication check
   - Task 0.10: Enforce fuzzy matching disabled
   - Task 0.11: Add soundex phonetic matching

3. **Phase 1:** Folder migration
4. **Phase 2:** YAML infrastructure
5. **Phase 5:** Complete import_run table and JSON column conversion

---

## Files Created

### Schema Patches
- `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/CreateArtistTable.php`
- `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/AddDashboardIndexes.php`
- `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/ConvertJsonColumns.php`
- `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Schema/CreateShowMetadataTable.php`

### SQL Migrations
- `migrations/001_create_artist_table.sql`
- `migrations/002_add_dashboard_indexes.sql`
- `migrations/003_convert_json_columns.sql`
- `migrations/004_create_show_metadata_table.sql`

### Rollback Scripts
- `migrations/rollback/001_drop_artist_table.sql`
- `migrations/rollback/002_drop_dashboard_indexes.sql`
- `migrations/rollback/003_revert_json_columns.sql`
- `migrations/rollback/004_drop_show_metadata_table.sql`

---

## Status: ✅ Card 0.A Complete

All database foundation tasks completed successfully. Tables created, indexes applied, foreign keys verified.
