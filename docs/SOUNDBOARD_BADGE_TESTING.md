# Soundboard Badge & Recording Source Display - Testing Plan

**Status:** ✅ Implementation Complete | ⏳ Testing Pending
**Date:** 2026-01-29
**Feature:** Soundboard badges on song cards + recording source display in player

---

## What Was Implemented

### 1. Utility Functions (`frontend/lib/lineageUtils.ts`)
- **Detection functions**: `isSoundboard()`, `isMatrix()`, `getRecordingType()`
- **Formatting**: `formatLineage()` with intelligent truncation
- **Badge config**: `getRecordingBadge()` returns styling for SBD/MATRIX badges

**Detection patterns:**
```typescript
/\bsoundboard\b/i  // matches "soundboard"
/\bsbd\b/i         // matches "SBD"
/\bmatrix\b/i      // matches "matrix"
```

### 2. Soundboard Badge on Album Cards
**File:** `frontend/components/AlbumPageContent.tsx`

- Gold "SBD" badge or "MATRIX" badge in top-right corner of recording version cards
- Only shows for soundboard/matrix recordings (not audience)
- Colors: `#d4a060` (soundboard gold), `#e8a050` (matrix lighter gold)
- Adapts styling for selected vs unselected card states

### 3. Recording Source Display (3 Locations)
Permanent source text under quality selector button:

**Mobile Mini Player** (`frontend/components/BottomPlayer.tsx` ~line 198)
- Right-aligned, truncated to 35 chars, max-width: 120px

**Desktop Player** (`frontend/components/BottomPlayer.tsx` ~line 502)
- Right-aligned, truncated to 50 chars, max-width: 180px

**Full Player Mobile** (`frontend/components/JamifyFullPlayer.tsx` ~line 269)
- Left-aligned, truncated to 60 chars, max-width: 200px

**Styling:** 9px, italic, muted gray (`#6a6458`), full text on hover

---

## Testing Challenge: STS9 Has No Soundboard Recordings

### Database Analysis Results

**STS9 Statistics:**
- Total tracks: **7,946**
- Tracks with lineage data: **7,946** (100%)
- Soundboard recordings: **0**
- Matrix recordings: **0**
- Tracks with keywords "board", "feed", "direct", "FOH": **0**

**All STS9 lineage examples are audience recordings:**
```
"2 Fujifilm Hi-MD masters > Sony MZ-RF710..."
"Shure SM94's -> Balanced Stereo XLR -> Minidisc"
"Sony ECM907> MD> Audacity (normalize)> CDWave"
```

**Conclusion:** The imported STS9 shows are all taped with portable audience equipment. No soundboard access was available for these particular concerts.

### Working Test Cases (Confirmed Soundboards)

**Database has 3,544 soundboard recordings across multiple artists:**

#### Option 1: The String Cheese Incident
- **Album:** 'Round The Wheel
- **URL:** http://localhost:3001/thestringcheeseincident/roundthewheel52539
- **SBD tracks:** 20
- **Example lineage:** `"SBD + Neumann TLM170; wav > Audacity > xACT (FLAC/TAG)"`

#### Option 2: The String Cheese Incident
- **Album:** Rhythm Of The Road
- **URL:** http://localhost:3001/thestringcheeseincident/rhythmoftheroad
- **SBD tracks:** 16

#### Option 3: moe.
- **URL:** http://localhost:3001/artists/moe
- **SBD tracks available:**
  - "Hi And Lo" - Seaside Park, Gathering Of The Vibes (2009)
  - "Down Boy" - Radio City Music Hall (2006)
  - "McBain" - Radio City Music Hall (2006)

---

## Next Steps to Enable STS9 Testing

### Option A: Import Different STS9 Shows (Recommended)
Search Archive.org for STS9 shows that specifically have soundboard sources.

**Commands to try:**
```bash
# Import from specific collection or identifier that might have SBDs
bin/magento archive:import:shows "STS9" --limit=50 --collection=<collection-id>

# Or search Archive.org manually for STS9 + soundboard keywords
# Then import specific identifiers
```

**Search strategy:**
1. Go to Archive.org and search: "STS9 soundboard" or "STS9 SBD"
2. Find show identifiers with confirmed soundboard sources
3. Import those specific shows

### Option B: Manual Test Data
Temporarily add soundboard lineage to an existing STS9 track for testing:

```sql
UPDATE catalog_product_entity_varchar
SET value = 'SBD > DAT > CD > EAC > FLAC (Level 8)'
WHERE entity_id = (
  SELECT entity_id FROM catalog_product_entity WHERE sku = '<some-sts9-sku>'
)
AND attribute_id = 246;
```

Then clear frontend cache: `cd frontend && bin/refresh`

### Option C: Use Alternative Artist
Accept that STS9 testing isn't currently possible and test with:
- String Cheese Incident (confirmed working)
- moe. (confirmed working)

---

## Verification Checklist

When soundboard recordings are available:

### Visual Checks
- [ ] Navigate to album page with soundboard recordings
- [ ] Expand track to see recordings carousel
- [ ] **Badge appears:** Gold "SBD" badge in top-right of version card
- [ ] **Badge absent:** No badge on audience recordings
- [ ] **Contrast:** Badge readable on both selected/unselected cards

### Source Display Checks
- [ ] **Mobile mini player:** Source text below quality badge (right-aligned, ~35 chars)
- [ ] **Desktop player:** Source text below quality button (right-aligned, ~50 chars)
- [ ] **Full player mobile:** Source text below quality badge (left-aligned, ~60 chars)
- [ ] **Hover tooltip:** Full lineage text shows in title attribute
- [ ] **Null handling:** "Source not specified" for tracks without lineage

### Responsive Testing
- [ ] Mobile viewport (320px-768px) - no overflow
- [ ] Desktop (1024px+) - proper alignment
- [ ] Text readable at 9px size
- [ ] Quality popup doesn't overlap source text

---

## Files Modified

1. **`frontend/lib/lineageUtils.ts`** - NEW: Detection and formatting utilities
2. **`frontend/components/AlbumPageContent.tsx`** - Soundboard badge on recording cards
3. **`frontend/components/BottomPlayer.tsx`** - Source display (mobile + desktop, 2 locations)
4. **`frontend/components/JamifyFullPlayer.tsx`** - Source display (full player)

---

## Technical Notes

### Lineage Attribute Details
- **Attribute ID:** 246
- **Backend type:** varchar
- **Table:** `catalog_product_entity_varchar`
- **GraphQL:** Already exposed on `Song` type

### Detection Priority
Matrix > Soundboard > Audience

### Badge Configuration
```typescript
Soundboard: { text: 'SBD', bgColor: '#d4a060', textColor: '#1c1a17' }
Matrix:     { text: 'MATRIX', bgColor: '#e8a050', textColor: '#1c1a17' }
Audience:   null (no badge)
```

---

## Return Checklist

When you come back to this:

1. **Decide on test artist:** STS9 (need to import soundboards) or String Cheese Incident (ready now)
2. **If STS9:** Run import commands from Option A above
3. **If String Cheese:** Go to http://localhost:3001/thestringcheeseincident/roundthewheel52539
4. **Run verification checklist** above
5. **Test all 3 player locations** (mobile mini, desktop, full player)
6. **Check badge styling** on light/dark card backgrounds
7. **Verify truncation** works properly on long lineage strings

---

## Questions for Next Session

- Do you want to import more STS9 shows to find soundboard recordings?
- Is String Cheese Incident an acceptable test case?
- Should we add more keywords to detection (e.g., "board feed", "FOH")?
- Should matrix recordings show a different colored badge?

---

**Implementation complete. Awaiting test data with soundboard recordings.**
