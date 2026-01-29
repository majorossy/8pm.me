# CARD-1: Backend SEO Implementation (Magento)

**Priority:** 🔴 Critical - Must complete before frontend work
**Estimated Time:** 5-7 hours (updated: +null safety +truncation helper)
**Assignee:** Backend Developer
**Dependencies:** None

---

## 📋 Objective

Add SEO metadata fields (`meta_title`, `meta_description`, `meta_keyword`) to all imported concert recordings and enable canonical URLs in Magento.

---

## ✅ Acceptance Criteria

- [ ] Products have auto-generated meta titles (≤70 chars)
- [ ] Products have auto-generated meta descriptions (≤160 chars)
- [ ] Products have relevant meta keywords
- [ ] GraphQL returns `meta_title`, `meta_description`, `url_key` fields
- [ ] Re-imported test artist shows SEO fields populated
- [ ] Null safety added for all Show and Track getters
- [ ] Word-aware truncation helper implemented

---

## 🔧 Implementation Steps

### Step 1: Update BulkProductImporter (30 min)

**File:** `src/app/code/ArchiveDotOrg/Core/Model/BulkProductImporter.php`
**Location:** Around line 460, in the `$varcharAttributes` array

Add this code:

```php
// SEO attributes with null safety
$trackTitle = $track->getTitle() ?? 'Untitled Track';
$showYear = $show->getYear() ?? 'Live';
$showVenue = $show->getVenue() ?? 'Unknown Venue';
$showDate = $show->getDate() ?? $showYear;

$metaTitle = sprintf(
    '%s - %s (%s at %s) | 8pm.me',
    $trackTitle,
    $artistName,
    $showYear,
    $showVenue
);

// Improved description without show notes (too technical for SEO)
$metaDescription = sprintf(
    'Listen to %s performed by %s on %s at %s. High-quality live concert recording from Archive.org - free streaming.',
    $trackTitle,
    $artistName,
    $showDate,
    $showVenue
);

// Use word-aware truncation helper (see below)
$varcharAttributes['meta_title'] = $this->truncateToLength($metaTitle, 70);
$varcharAttributes['meta_description'] = $this->truncateToLength($metaDescription, 160);
$varcharAttributes['meta_keyword'] = implode(', ', array_filter([
    $artistName,
    $trackTitle,
    $showVenue,
    $showYear,
    'live concert',
    'free streaming'
]));
```

**Add this helper method to the BulkProductImporter class:**

```php
/**
 * Truncate string to maximum length without breaking words
 *
 * @param string $text
 * @param int $maxLength
 * @return string
 */
private function truncateToLength(string $text, int $maxLength): string
{
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }

    // Truncate to max length
    $truncated = mb_substr($text, 0, $maxLength);

    // Find last space to avoid breaking words
    $lastSpace = mb_strrpos($truncated, ' ');

    // Only break at space if it's not too far back (>75% of max length)
    if ($lastSpace !== false && $lastSpace > $maxLength * 0.75) {
        return mb_substr($truncated, 0, $lastSpace) . '...';
    }

    return $truncated . '...';
}
```

**Example Output:**
- Title: `Terrapin Station - Grateful Dead (1977 at Cornell) | 8pm.me`
- Description: `Listen to Terrapin Station performed by Grateful Dead on 1977-05-08 at Cornell University. Free streaming from Archive.org. Legendary show...`
- Keywords: `Grateful Dead, Terrapin Station, Cornell University, 1977, live concert, archive.org, free streaming`

### Step 2: Clear Cache (2 min)

**Note:** Magento does not expose a `canonical_url` field in GraphQL by default. Canonical URLs will be constructed on the frontend using `url_key` and the site's routing structure.

```bash
bin/magento cache:flush
```

### Step 3: Test with Import (15 min)

Import a test artist to verify SEO fields populate:

```bash
bin/magento archivedotorg:import-shows "Test Artist" --limit=5
```

Verify in database:

```bash
bin/mysql -e "
SELECT
  cpe.sku,
  cpev1.value AS meta_title,
  cpev2.value AS meta_description
FROM catalog_product_entity cpe
LEFT JOIN catalog_product_entity_varchar cpev1 ON cpe.entity_id = cpev1.entity_id
  AND cpev1.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'meta_title')
LEFT JOIN catalog_product_entity_varchar cpev2 ON cpe.entity_id = cpev2.entity_id
  AND cpev2.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'meta_description')
WHERE cpe.sku LIKE 'test-artist%'
LIMIT 5;
"
```

### Step 4: Test GraphQL Endpoint (15 min)

Test that SEO fields are accessible via GraphQL:

```bash
curl -X POST https://magento.test/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "{ products(filter: { sku: { eq: \"TEST-SKU\" } }) { items { sku name meta_title meta_description meta_keyword url_key } } }"
  }' | jq
```

Expected response:
```json
{
  "data": {
    "products": {
      "items": [{
        "sku": "TEST-SKU",
        "name": "Track Name",
        "meta_title": "Track - Artist (Year at Venue) | 8pm.me",
        "meta_description": "Listen to Track performed by...",
        "meta_keyword": "Artist, Track, Venue, Year, live concert...",
        "url_key": "track-url-key"
      }]
    }
  }
}
```

**Note:** The `canonical_url` field does not exist in standard Magento GraphQL. Canonical URLs will be constructed on the frontend using the product's `url_key` and routing structure.

---

## 🧪 Testing Checklist

- [ ] Meta title ≤70 characters
- [ ] Meta description ≤160 characters
- [ ] Keywords are comma-separated and relevant
- [ ] Canonical URL is absolute (includes https://)
- [ ] GraphQL query returns all SEO fields
- [ ] Fields are non-null for newly imported products
- [ ] No PHP errors in logs after import

---

## 🐛 Troubleshooting

**Issue:** Need canonical URLs

**Solution:** Canonical URLs are not available via Magento GraphQL. They will be constructed on the frontend using `url_key` and the site's routing structure (see CARD-2).

**Issue:** Meta fields not appearing in database

**Solution:** Check EAV attribute codes exist:
```bash
bin/mysql -e "SELECT attribute_code FROM eav_attribute WHERE attribute_code IN ('meta_title', 'meta_description', 'meta_keyword');"
```

**Issue:** Import fails with "Attribute not found"

**Solution:** Ensure you're modifying the correct section of BulkProductImporter.php (line ~460 in `$varcharAttributes` array).

---

## 📚 References

- Magento SEO Guide: https://meetanshi.com/blog/magento-2-seo-guide/
- EAV Attributes: https://developer.adobe.com/commerce/php/development/components/attributes/
- GraphQL ProductInterface: https://developer.adobe.com/commerce/webapi/graphql/schema/products/interfaces/attributes/

---

## ✋ Hand-off to CARD-2

Once this card is complete, the frontend team can begin CARD-2 (Frontend Metadata) to consume these SEO fields via GraphQL.

**Required Output:**
- GraphQL endpoint returning `meta_title`, `meta_description`, `canonical_url`
- At least one artist fully imported with SEO metadata
