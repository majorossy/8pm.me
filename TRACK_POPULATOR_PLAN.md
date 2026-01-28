# Implementation Plan: Populate Track Categories with Archive.org Audio

## Overview

Populate ~3,000 track categories with audio products from Archive.org live recordings. Each track category (e.g., "Scarlet Begonias" under "Grateful Dead") will get **every available recording** - one product per Archive.org show that played that song.

**Example:**
```
Grateful Dead (artist)
└── Cornell 5/8/77 (album category)
    └── Scarlet Begonias (track category)
        ├── Product: Scarlet Begonias - 1977-05-08 Cornell
        ├── Product: Scarlet Begonias - 1977-05-09 Buffalo
        ├── Product: Scarlet Begonias - 1989-10-09 Hampton
        └── ... (every show that played this song)
```

## Existing Infrastructure (Fully Reusable)

| Component | What It Does | We Reuse |
|-----------|--------------|----------|
| `ArchiveApiClient` | Fetches shows/metadata from Archive.org | ✅ As-is |
| `ConcurrentApiClient` | Batch API fetching with rate limiting | ✅ As-is |
| `TrackImporter` | Creates products with `song_url` | ✅ As-is |
| `CategoryAssignmentService` | Links products to categories | ✅ Extend slightly |
| `Config` | Builds streaming URLs, rate limits | ✅ As-is |

**Only 2 new files needed:**
1. `TrackPopulatorService` - Matches imported tracks to existing track categories
2. `PopulateTracksCommand` - CLI to trigger the process

## Current State

- 37 artists, 285 albums, 3,014 track categories (with `is_song=1`)
- No products yet - products contain the `song_url` for playback
- Existing import command (`archive:import:shows`) creates products but assigns to show-date categories, not our track categories

## Implementation

### New File 1: TrackPopulatorService

**File:** `src/app/code/ArchiveDotOrg/Core/Model/TrackPopulatorService.php`

Uses existing services to:
1. Load track categories for an artist (by `is_song=1`)
2. Build title lookup map (normalized title → category ID)
3. Fetch shows from Archive.org collection
4. For each track, if title matches a category → create product & assign
5. SKU (based on SHA1) prevents duplicate products from same recording

```php
class TrackPopulatorService
{
    public function __construct(
        ArchiveApiClientInterface $apiClient,       // Existing
        ConcurrentApiClient $concurrentApiClient,   // Existing
        TrackImporterInterface $trackImporter,      // Existing
        CategoryAssignmentServiceInterface $categoryService,  // Existing
        CategoryCollectionFactory $categoryCollectionFactory,
        Logger $logger
    );

    public function populate(string $artistName, string $collectionId, ?callable $progressCallback = null): array;
}
```

### New File 2: PopulateTracksCommand

**File:** `src/app/code/ArchiveDotOrg/Core/Console/Command/PopulateTracksCommand.php`

```bash
bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead [--limit=100] [--dry-run]
```

### Minor Edit: di.xml

Add DI preference for the new interface.

### Minor Edit: CategoryAssignmentService

Add one helper method:
- `getTrackCategoriesForArtist(artistName)` - Returns all `is_song=1` categories under the artist

Note: `bulkAssignToCategory()` already exists for product assignment.

## Algorithm

```
1. Get artist category (is_artist=1, name matches)
2. Get all track categories (is_song=1 descendants)
3. Build map: normalize(category_name) => [category_ids]
   (array because same song name may appear under different albums)

4. For each show in collection (via ConcurrentApiClient):
   a. Fetch show metadata
   b. For each track in show:
      - Normalize track title
      - If matches any track category:
        - Create product (TrackImporter) - SKU prevents duplicates
        - Assign to ALL matching track categories
        - Track stats (matched/skipped)
      - If no match, skip (track not in our catalog)

5. Return stats: {products_created, products_skipped, shows_processed, tracks_matched, tracks_unmatched}
```

**Title Normalization:**
```php
// "Scarlet Begonias >" → "scarlet begonias"
// "Fire On The Mountain" → "fire on the mountain"
$normalized = strtolower(preg_replace('/[^\w\s]/', '', trim($title)));
```

## Execution

```bash
# Test with dry-run first
bin/magento archive:populate:tracks "Billy Strings" --collection=BillyStrings --dry-run --limit=20

# Run for real
bin/magento archive:populate:tracks "Billy Strings" --collection=BillyStrings

# Run all jam bands
bin/magento archive:populate:tracks "Grateful Dead" --collection=GratefulDead
bin/magento archive:populate:tracks "Phish" --collection=Phish
bin/magento archive:populate:tracks "Goose" --collection=Goose
# ... etc
```

## Artist Collection IDs

| Artist | Archive.org Collection |
|--------|----------------------|
| Grateful Dead | GratefulDead |
| Phish | Phish |
| Billy Strings | BillyStrings |
| Goose | Goose |
| Widespread Panic | WidespreadPanic |
| moe. | moe |
| Umphrey's McGee | UmphreysMcGee |
| My Morning Jacket | MyMorningJacket |

**Note:** Studio bands (Smashing Pumpkins, King Gizzard, etc.) likely don't have Archive.org collections - skip for now, handle separately later.

## Files to Create/Modify

| File | Action |
|------|--------|
| `Model/TrackPopulatorService.php` | Create |
| `Api/TrackPopulatorServiceInterface.php` | Create |
| `Console/Command/PopulateTracksCommand.php` | Create |
| `etc/di.xml` | Add preference |
| `Api/CategoryAssignmentServiceInterface.php` | Add 1 method signature |
| `Model/CategoryAssignmentService.php` | Add 1 method implementation |

## Verification

```bash
# Total products created
bin/mysql -e "SELECT COUNT(*) FROM catalog_product_entity WHERE sku LIKE 'archive-%';"

# Products per track category (shows distribution)
bin/mysql -e "
SELECT c.name AS track_name, COUNT(cp.product_id) AS recording_count
FROM catalog_category_product cp
JOIN catalog_category_entity c ON cp.category_id = c.entity_id
JOIN catalog_category_entity_int ci ON c.entity_id = ci.entity_id
JOIN eav_attribute a ON ci.attribute_id = a.attribute_id
WHERE a.attribute_code = 'is_song' AND ci.value = 1
GROUP BY c.entity_id, c.name
ORDER BY recording_count DESC
LIMIT 20;"

# Track categories still without products
bin/mysql -e "
SELECT COUNT(*) AS empty_tracks FROM catalog_category_entity c
JOIN catalog_category_entity_int ci ON c.entity_id = ci.entity_id
JOIN eav_attribute a ON ci.attribute_id = a.attribute_id
LEFT JOIN catalog_category_product cp ON c.entity_id = cp.category_id
WHERE a.attribute_code = 'is_song' AND ci.value = 1 AND cp.product_id IS NULL;"

# Test frontend playback
curl "https://magento.test/graphql" -d '{"query":"{ products(filter:{sku:{like:\"archive-%\"}},pageSize:1) { items { song_url } } }"}'
```
