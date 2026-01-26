# Implementation Plan: User-Facing Data Fixes

## Objective

Fix missing and broken data mappings in the Archive.org import system so users see complete information about tracks, shows, venues, tapers, etc.

---

## Project Context

- **Module Location:** `/Users/chris.majorossy/Projects/docker-desktop/8pm/src/app/code/ArchiveDotOrg/Core/`
- **Purpose:** Import live concert recordings from Archive.org into Magento as virtual products
- **Current Issue:** Many product attributes are empty or poorly formatted after import

---

## Current Data Gaps

| Data | User Value | Status | Root Cause |
|------|------------|--------|------------|
| Track Number | Setlist position | NOT SET | Attribute exists, Track DTO has value, never mapped |
| Transferer | Who digitized recording | NOT SET | Captured in Show DTO, never mapped to product |
| City/Location | Search by city | NOT CAPTURED | Archive.org `coverage` field is ignored |
| pubDate | When uploaded to Archive.org | NOT SET | Show DTO property exists but never populated |
| guid | Link to Archive.org | NOT SET | Show DTO property exists but never populated |
| Track Length | Song duration | RAW FORMAT | Stored as "383.24" seconds instead of "6:23" |

---

## Implementation Tasks

### Task 1: Set Track Number (1 line)

**File:** `Model/TrackImporter.php`

**Location:** `setProductData()` method, after line 235

**Current code around line 233-236:**
```php
// Track-specific attributes
$product->setData('title', $track->getTitle());
$product->setData('length', $track->getLength());
```

**Add this line after `setData('length', ...)`:**
```php
$product->setData('album_track', $track->getTrackNumber());
```

**Verification:** The `album_track` attribute already exists (created in `Setup/Patch/Data/CreateProductAttributes.php`). The `Track` DTO already has `getTrackNumber()` method that returns the track position.

---

### Task 2: Set Transferer (1 line)

**File:** `Model/TrackImporter.php`

**Location:** `setProductData()` method, after line 264

**Current code around line 261-264:**
```php
// Dropdown attributes (using AttributeOptionManager)
$this->setDropdownAttribute($product, 'show_year', $show->getYear());
$this->setDropdownAttribute($product, 'show_venue', $show->getVenue());
$this->setDropdownAttribute($product, 'show_taper', $show->getTaper());
$this->setDropdownAttribute($product, 'archive_collection', $artistName);
```

**Add this line after `archive_collection`:**
```php
$this->setDropdownAttribute($product, 'show_transferer', $show->getTransferer());
```

**Verification:** The `show_transferer` attribute already exists. The `Show` DTO already captures transferer via `getTransferer()` - it's parsed in `ArchiveApiClient.php` line 278.

---

### Task 3: Set pubDate and guid (2 lines)

**File:** `Model/ArchiveApiClient.php`

**Location:** `parseShowResponse()` method, after line 284

**Current code around line 282-284:**
```php
$show->setDir($data['dir'] ?? null);
$show->setServerOne($data['d1'] ?? null);
$show->setServerTwo($data['d2'] ?? null);
```

**Add these lines after `setServerTwo()`:**
```php
$show->setPubDate($this->extractValue($metadata, 'publicdate'));
$show->setGuid('https://archive.org/details/' . $identifier);
```

**Verification:**
- The `Show` DTO already has `setPubDate()` and `setGuid()` methods (see `Model/Data/Show.php` lines 323-352)
- Archive.org provides `publicdate` in the metadata object
- The guid is constructed from the identifier to create a direct link

---

### Task 4: Format Track Length (10 lines)

**File:** `Model/TrackImporter.php`

**Step 1:** Add helper method at the end of the class (before the closing brace):

```php
/**
 * Format track length from seconds to MM:SS or H:MM:SS
 *
 * @param string|null $seconds
 * @return string|null
 */
private function formatTrackLength(?string $seconds): ?string
{
    if ($seconds === null || !is_numeric($seconds)) {
        return $seconds;
    }

    $totalSeconds = (int) floor((float) $seconds);
    $hours = (int) floor($totalSeconds / 3600);
    $minutes = (int) floor(($totalSeconds % 3600) / 60);
    $secs = $totalSeconds % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }

    return sprintf('%d:%02d', $minutes, $secs);
}
```

**Step 2:** Update the `setProductData()` method to use the formatter.

**Current code around line 235:**
```php
$product->setData('length', $track->getLength());
```

**Change to:**
```php
$product->setData('length', $this->formatTrackLength($track->getLength()));
```

---

### Task 5: Capture City/Location (30 lines across 4 files)

#### Step 5.1: Update Show Interface

**File:** `Api/Data/ShowInterface.php`

**Add these method signatures** (find the other getter/setter pairs and add nearby):

```php
/**
 * Get show location (city/state)
 *
 * @return string|null
 */
public function getLocation(): ?string;

/**
 * Set show location
 *
 * @param string|null $location
 * @return ShowInterface
 */
public function setLocation(?string $location): ShowInterface;
```

#### Step 5.2: Update Show DTO Implementation

**File:** `Model/Data/Show.php`

**Add property** (with the other private properties around line 17-34):
```php
private ?string $location = null;
```

**Add methods** (with the other getter/setter pairs):
```php
/**
 * @inheritDoc
 */
public function getLocation(): ?string
{
    return $this->location;
}

/**
 * @inheritDoc
 */
public function setLocation(?string $location): ShowInterface
{
    $this->location = $location;
    return $this;
}
```

#### Step 5.3: Parse Location in API Client

**File:** `Model/ArchiveApiClient.php`

**Location:** `parseShowResponse()` method, around line 281 (with the other `$show->set...` calls)

**Add:**
```php
$show->setLocation($this->extractValue($metadata, 'coverage'));
```

#### Step 5.4: Create New Product Attribute

**File:** `Setup/Patch/Data/AddLocationAttribute.php` (NEW FILE)

```php
<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddLocationAttribute implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply(): self
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        if ($eavSetup->getAttributeId(Product::ENTITY, 'show_location')) {
            return $this; // Already exists
        }

        $eavSetup->addAttribute(
            Product::ENTITY,
            'show_location',
            [
                'type' => 'int',
                'label' => 'Show Location',
                'input' => 'select',
                'group' => 'Product Details',
                'sort_order' => 35,
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'required' => false,
                'used_in_product_listing' => true,
                'searchable' => true,
                'filterable' => true,
                'visible' => true,
                'visible_on_front' => true
            ]
        );

        return $this;
    }

    public static function getDependencies(): array
    {
        return [CreateProductAttributes::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
```

#### Step 5.5: Map Location in TrackImporter

**File:** `Model/TrackImporter.php`

**Location:** `setProductData()` method, after the other `setDropdownAttribute()` calls

**Add:**
```php
$this->setDropdownAttribute($product, 'show_location', $show->getLocation());
```

---

## Testing

### Manual Test

```bash
# Run a small test import
cd /Users/chris.majorossy/Projects/docker-desktop/8pm
bin/magento archive:import:shows "STS9" --collection=STS9 --limit=1

# Check the imported product in Magento admin:
# - album_track should show track number (e.g., "3")
# - show_transferer should be populated (if Archive.org has this data)
# - length should be formatted (e.g., "7:02" instead of "422.35")
# - show_location should show city (e.g., "Red Rocks, Morrison, CO")
```

### Run Existing Unit Tests

```bash
vendor/bin/phpunit -c app/code/ArchiveDotOrg/Core/Test/phpunit.xml
```

### After Creating New Attribute

```bash
bin/magento setup:upgrade
bin/magento cache:flush
```

---

## Files Modified Summary

| File | Changes |
|------|---------|
| `Model/TrackImporter.php` | Add album_track, show_transferer, show_location mapping; add formatTrackLength() |
| `Model/ArchiveApiClient.php` | Add pubDate, guid, location parsing |
| `Model/Data/Show.php` | Add location property and getter/setter |
| `Api/Data/ShowInterface.php` | Add getLocation/setLocation method signatures |
| `Setup/Patch/Data/AddLocationAttribute.php` | **NEW** - Create show_location attribute |

---

## Expected Before/After

**Before:**
```
Title: Scheme
Length: 421.56
Track #: (empty)
Transferer: (empty)
Location: (empty)
pubDate: (empty)
guid: (empty)
```

**After:**
```
Title: Scheme
Length: 7:02
Track #: 3
Transferer: Charlie Miller
Location: Red Rocks, Morrison, CO
pubDate: 2015-03-22
guid: https://archive.org/details/sts9-2015-03-21.flac16
```

---

## Implementation Order

1. Tasks 1-3 (quick fixes) - Can be done in any order
2. Task 4 (format length) - Independent
3. Task 5 (location) - Do last as it requires new file + setup:upgrade

---

## Notes

- All existing attributes were created in `Setup/Patch/Data/CreateProductAttributes.php`
- The `setDropdownAttribute()` helper method handles creating dropdown option values automatically via `AttributeOptionManager`
- Archive.org data quality varies - some shows may not have all fields (taper, transferer, coverage)
