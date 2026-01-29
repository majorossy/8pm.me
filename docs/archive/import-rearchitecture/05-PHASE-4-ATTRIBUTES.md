# Phase 4: Extended Attributes

**Timeline:** Week 3 (can run parallel with Phase 1)
**Status:** ⏸️ Blocked by Phase 0
**Prerequisites:** Phase 0 complete

---

## Overview

Add new EAV attributes to store additional track and show metadata from Archive.org.

**New track attributes:**
- `track_file_size`, `track_md5`, `track_acoustid`, `track_bitrate`

**New show attributes:**
- `show_files_count`, `show_total_size`, `show_uploader`, `show_created_date`, `show_last_updated`

**Note:** `show_reviews_json` and `show_workable_servers` moved to separate table (Phase 0).

**Completion Criteria:**
- [ ] All 9 new EAV attributes created
- [ ] Track/Show DTOs updated with new properties
- [ ] JSON parser extracts new fields
- [ ] TrackImporter/BulkProductImporter save new attributes
- [ ] ShowMetadataRepository handles separate table data

---

## 🟨 P2 - New EAV Attributes

### Task 4.1: Create Extended Attributes Data Patch
**Create:** `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddExtendedArchiveAttributes.php`

**Track-level attributes:**

| Attribute | Type | Label |
|-----------|------|-------|
| `track_file_size` | int | File Size (bytes) |
| `track_md5` | varchar | MD5 Hash |
| `track_acoustid` | varchar | AcoustID Fingerprint |
| `track_bitrate` | int | Bitrate (kbps) |

**Show-level attributes:**

| Attribute | Type | Label |
|-----------|------|-------|
| `show_files_count` | int | Files Count |
| `show_total_size` | int | Total Size (bytes) |
| `show_uploader` | varchar | Uploader |
| `show_created_date` | datetime | Created Date |
| `show_last_updated` | datetime | Last Updated |

- [ ] Create data patch
- [ ] Run: `bin/magento setup:upgrade`
- [ ] Verify: 9 attributes visible in admin

---

### Task 4.2: Update Track DTO
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/Data/Track.php`

**Add properties:**
```php
private ?int $fileSize = null;
private ?string $md5 = null;
private ?string $acoustid = null;
private ?int $bitrate = null;
```

- [ ] Add private properties
- [ ] Add getters/setters
- [ ] Update `toArray()` if exists
- [ ] Test: Set/get new properties

---

### Task 4.3: Update Show DTO
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/Data/Show.php`

**Add properties:**
```php
private ?int $filesCount = null;
private ?int $itemSize = null;
private ?string $uploader = null;
private ?int $createdTimestamp = null;
private ?int $lastUpdatedTimestamp = null;
```

- [ ] Add private properties
- [ ] Add getters/setters
- [ ] Test: Set/get new properties

---

### Task 4.4: Update JSON Parser
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php`

**Archive.org JSON fields to extract:**

Track fields (from `files[]` array):
```json
{
  "name": "track01.mp3",
  "size": "12345678",
  "md5": "abc123...",
  "external-identifier": ["acoustid:xyz..."],
  "bitrate": "320"
}
```

Show fields (from root):
```json
{
  "files_count": 25,
  "item_size": 567890123,
  "metadata": {
    "uploader": "user@archive.org"
  },
  "created": 1609459200,
  "item_last_updated": 1704067200
}
```

- [ ] Extract track fields: `size`, `md5`, `external-identifier` (parse acoustid), `bitrate`
- [ ] Extract show fields: `files_count`, `item_size`, `uploader`, `created`, `item_last_updated`
- [ ] Handle missing fields gracefully (null)
- [ ] Test: Parse sample JSON, verify all fields extracted

---

### Task 4.5: Update TrackImporter
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php`

- [ ] Map DTO properties to EAV attributes
- [ ] Save new attributes when creating products
- [ ] Test: Create product, verify attributes saved

**Mapping:**
```php
$product->setData('track_file_size', $track->getFileSize());
$product->setData('track_md5', $track->getMd5());
$product->setData('track_acoustid', $track->getAcoustid());
$product->setData('track_bitrate', $track->getBitrate());
```

---

### Task 4.6: Update BulkProductImporter
**Modify:** `src/app/code/ArchiveDotOrg/Core/Model/BulkProductImporter.php`

- [ ] Add new attributes to batch SQL inserts
- [ ] Ensure attribute IDs are fetched correctly
- [ ] Test: Bulk import, verify attributes saved

---

### Task 4.7: Create ShowMetadataRepository
**Create:** `src/app/code/ArchiveDotOrg/Core/Model/ShowMetadataRepository.php`

**Purpose:** Handle data stored in `archivedotorg_show_metadata` table (from Phase 0).

**Methods:**
```php
public function save(string $identifier, array $workableServers, array $reviews): void;
public function getByIdentifier(string $identifier): ?ShowMetadata;
public function getWorkableServers(string $identifier): array;
public function getReviews(string $identifier): array;
```

- [ ] Create repository class
- [ ] Create ShowMetadata model/resource model
- [ ] Register in DI
- [ ] Test: Insert/retrieve show metadata

---

## Verification Checklist

Before considering Phase 4 complete:

```bash
# 1. Verify attributes exist
bin/magento catalog:attributes:list | grep track_
# Should show: track_file_size, track_md5, track_acoustid, track_bitrate

bin/magento catalog:attributes:list | grep show_
# Should show: show_files_count, show_total_size, show_uploader, show_created_date, show_last_updated

# 2. Test import with new attributes
bin/magento archive:download lettuce --limit=5
bin/magento archive:populate lettuce --limit=5

# 3. Verify attributes populated
# Check in admin: Catalog > Products > [any imported track]
# New attributes should have values

# 4. Verify show metadata table
mysql magento -e "SELECT * FROM archivedotorg_show_metadata LIMIT 5;"
# Should show workable_servers and reviews JSON
```

---

## Data Sources Reference

### Archive.org JSON Structure

```json
{
  "metadata": {
    "identifier": "phish2024-01-01",
    "title": "Phish Live at MSG 2024-01-01",
    "uploader": "user@archive.org",
    "collection": ["Phish", "etree"]
  },
  "created": 1704067200,
  "item_last_updated": 1704153600,
  "files_count": 25,
  "item_size": 567890123,
  "files": [
    {
      "name": "phish2024-01-01t01.mp3",
      "format": "VBR MP3",
      "size": "12345678",
      "md5": "abc123def456...",
      "bitrate": "320",
      "length": "543.21",
      "title": "Set 1 Track 01",
      "track": "01",
      "external-identifier": ["acoustid:abc-123-xyz"]
    }
  ],
  "reviews": [
    {"reviewer": "user1", "stars": 5, "reviewtitle": "Great show!"}
  ],
  "workable_servers": ["ia801234.us.archive.org"]
}
```

---

## Next Phase

Phase 4 can be done in parallel with Phase 1-3. After Phase 3 is complete → [Phase 5: Admin Dashboard](./06-PHASE-5-DASHBOARD.md)
