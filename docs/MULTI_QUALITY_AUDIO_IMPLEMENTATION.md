# Multi-Quality Audio Support - Implementation Complete ✅

**Date**: 2026-01-29
**Status**: Backend & Frontend Complete - Ready for Testing

## Overview

Implemented multi-quality audio support (High/Medium/Low) to give users control over bandwidth usage. Users can now select their preferred quality via a global selector in the top right that applies to all audio playback.

---

## Backend Implementation ✅

### 1. Product Attribute (`song_urls`)
**File**: `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddMultiQualityUrlsAttribute.php`

- ✅ Created `song_urls` text attribute for JSON storage
- ✅ Data patch applied successfully (confirmed in DB: patch_id 315)
- ✅ Backward compatible with legacy `song_url` attribute

**JSON Structure**:
```json
{
  "high": {
    "url": "https://archive.org/.../track.flac",
    "format": "flac",
    "bitrate": "lossless",
    "size_mb": 45
  },
  "medium": {
    "url": "https://archive.org/.../track.mp3",
    "format": "mp3",
    "bitrate": "320k",
    "size_mb": 10
  },
  "low": {
    "url": "https://archive.org/.../track.mp3",
    "format": "mp3",
    "bitrate": "128k",
    "size_mb": 4
  }
}
```

### 2. Archive API Client
**File**: `src/app/code/ArchiveDotOrg/Core/Model/ArchiveApiClient.php` (Modified line 461-494)

- ✅ Changed from single-format filtering to collecting ALL formats (FLAC, MP3, OGG)
- ✅ Tracks now include format metadata from file extensions
- ✅ Non-audio files are skipped

### 3. Track Importer
**File**: `src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php` (Modified)

- ✅ Groups tracks by base filename (without extension)
- ✅ Builds multi-quality JSON from all available formats
- ✅ Determines quality tier based on format and file size:
  - FLAC → High
  - MP3 (estimated 320kbps) → Medium
  - MP3 (estimated 128kbps) → Low
- ✅ Estimates bitrate from file size and duration
- ✅ Maintains backward compatibility (legacy `song_url` uses highest quality)

**Helper Methods Added**:
- `groupTracksByBasename()` - Groups format variations
- `buildMultiQualityUrls()` - Constructs JSON structure
- `determineQualityTier()` - Maps format → quality
- `estimateBitrate()` - Calculates bitrate from file size

### 4. GraphQL Schema
**File**: `src/app/code/ArchiveDotOrg/Core/etc/schema.graphqls`

- ✅ Added 4 new fields to `ProductInterface`:
  - `song_urls_json` - Full JSON object
  - `song_url_high` - High quality URL
  - `song_url_medium` - Medium quality URL
  - `song_url_low` - Low quality URL

### 5. GraphQL Resolvers
**Files**: `src/app/code/ArchiveDotOrg/Core/Model/Resolver/`

Created 4 resolvers (all with fallback to legacy `song_url`):
- ✅ `SongUrlsJson.php` - Returns full JSON
- ✅ `SongUrlHigh.php` - Extracts high quality URL
- ✅ `SongUrlMedium.php` - Extracts medium quality URL
- ✅ `SongUrlLow.php` - Extracts low quality URL

---

## Frontend Implementation ✅

### 1. Type System
**File**: `frontend/lib/types.ts`

- ✅ Added `AudioQuality` type: `'high' | 'medium' | 'low'`
- ✅ Extended `Song` interface with:
  - `qualityUrls?: { high?: string; medium?: string; low?: string }`
  - `defaultQuality?: AudioQuality`

### 2. GraphQL API Layer
**File**: `frontend/lib/api.ts`

- ✅ Updated all GraphQL queries to fetch quality URL fields
- ✅ Extended `MagentoProduct` interface
- ✅ Updated `productToSong()` transformation to populate quality URLs
- ✅ Sets `defaultQuality: 'medium'` (recommended default)

### 3. Quality Context (Global State)
**File**: `frontend/context/QualityContext.tsx` (NEW)

- ✅ Manages preferred quality setting (localStorage persistence)
- ✅ `getStreamUrl(song)` - Selects URL based on preference
- ✅ Fallback order: preferred → high → medium → low → legacy
- ✅ Graceful handling when preferred quality unavailable

**API**:
```typescript
const { preferredQuality, setPreferredQuality, getStreamUrl } = useQuality();
```

### 4. Quality Selector Component
**File**: `frontend/components/QualitySelector.tsx` (NEW)

- ✅ Dropdown selector with Campfire theme styling
- ✅ Music note icon
- ✅ Haptic feedback on change (mobile)
- ✅ Accessible (ARIA labels, keyboard navigation)
- ✅ Positioned in top bar (always visible)

**Colors**:
- Background: `#2a2520`
- Border: `#4a3a28`
- Text: `#a89080`
- Focus/Hover: `#d4a060`

### 5. Top Bar Integration
**File**: `frontend/components/JamifyTopBar.tsx`

- ✅ Added `QualitySelector` to top right corner
- ✅ Breadcrumb navigation on left, quality selector on right
- ✅ Responsive on mobile/desktop

### 6. Player Context Integration
**File**: `frontend/context/PlayerContext.tsx`

✅ **All 8 audio loading points updated to use `getStreamUrl()`**:
1. `playSong()` - Direct song playback
2. `playNext()` - Next track
3. `playPrev()` - Previous track
4. `playFromQueue()` - Play specific queue index
5. `playAlbum()` - Album playback
6. `playTrack()` - Track playback
7. `playAlbumFromTrack()` - Album with specific track
8. **Preload logic** - Preloads next track with selected quality
9. **currentSong effect** - Reloads if quality changes mid-session
10. **handlePlaybackError** - Loads next song with quality

**Quality Switching Behavior**:
- Quality changes apply to **next track** (not current track)
- Avoids playback interruption
- Preload uses selected quality

### 7. Provider Hierarchy
**File**: `frontend/components/ClientLayout.tsx`

- ✅ Added `QualityProvider` wrapping `PlayerProvider`
- ✅ Ensures `useQuality()` hook is available throughout app

---

## Testing Plan

### Backend Tests

```bash
# 1. Verify attribute created
bin/mysql -e "SELECT attribute_id, attribute_code FROM eav_attribute WHERE attribute_code = 'song_urls';"

# 2. Test import with multi-quality
bin/magento archive:import:shows "Phish" --limit=5

# 3. Check product data
bin/mysql -e "SELECT entity_id, sku, song_urls FROM catalog_product_entity_text WHERE attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'song_urls') LIMIT 5;"

# 4. Test GraphQL query
```

**GraphQL Test Query**:
```graphql
query {
  products(filter: { sku: { eq: "archive-YOUR_SKU" } }) {
    items {
      sku
      name
      song_url
      song_url_high
      song_url_medium
      song_url_low
      song_urls_json
    }
  }
}
```

### Frontend Tests

#### Manual Testing Checklist

**Desktop**:
- [ ] Quality selector appears in top right corner
- [ ] Selecting "High" loads FLAC (or best available)
- [ ] Selecting "Medium" loads MP3 320k
- [ ] Selecting "Low" loads MP3 128k
- [ ] Quality persists across page reload
- [ ] Quality persists across browser sessions
- [ ] Switching quality mid-session applies to next track (not current)
- [ ] Player gracefully falls back if quality unavailable

**Mobile**:
- [ ] Quality selector visible and usable
- [ ] Dropdown works on touch devices
- [ ] Haptic feedback fires on selection
- [ ] Responsive styling looks good

**Edge Cases**:
- [ ] Old products with only `song_url` (no `song_urls`) still play
- [ ] Songs with only one quality available fall back correctly
- [ ] Selecting quality not available falls back to best available
- [ ] No console errors

### Browser Testing

**Check Developer Tools**:
1. Network tab → Verify correct quality URL loaded
2. Console → No errors
3. Application → LocalStorage → `audioQuality` persists

**Sample Debug**:
```javascript
// In browser console
localStorage.getItem('audioQuality')  // Should be 'high', 'medium', or 'low'

// Check song quality URLs
const song = /* get from player state */;
console.log(song.qualityUrls);
console.log(getStreamUrl(song));  // From QualityContext
```

---

## Migration Strategy

### For Existing Products

**Option A: CLI Command to Backfill (Recommended)**
```bash
# Create command to backfill existing products
bin/magento archive:backfill:quality-urls --collection=GratefulDead --limit=100
```

This command would:
1. Read existing `song_url`
2. Parse filename to determine format
3. Build JSON with available quality (even if only one)
4. Update `song_urls` attribute

**Option B: Lazy Migration**
- Products imported before this feature only have `song_url`
- Frontend gracefully falls back to `song_url` when `song_urls` is empty
- New imports automatically populate `song_urls` JSON

### Re-import Strategy

Users can re-import specific artists to get multi-quality URLs:

```bash
bin/magento archive:import:shows "Grateful Dead" --limit=50
```

This will overwrite existing products with new multi-quality data.

---

## File Manifest

### Backend (6 files)

**Created**:
1. `src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddMultiQualityUrlsAttribute.php`
2. `src/app/code/ArchiveDotOrg/Core/Model/Resolver/SongUrlsJson.php`
3. `src/app/code/ArchiveDotOrg/Core/Model/Resolver/SongUrlHigh.php`
4. `src/app/code/ArchiveDotOrg/Core/Model/Resolver/SongUrlMedium.php`
5. `src/app/code/ArchiveDotOrg/Core/Model/Resolver/SongUrlLow.php`

**Modified**:
1. `src/app/code/ArchiveDotOrg/Core/Model/ArchiveApiClient.php`
2. `src/app/code/ArchiveDotOrg/Core/Model/TrackImporter.php`
3. `src/app/code/ArchiveDotOrg/Core/etc/schema.graphqls`

### Frontend (7 files)

**Created**:
1. `frontend/context/QualityContext.tsx`
2. `frontend/components/QualitySelector.tsx`

**Modified**:
1. `frontend/lib/types.ts`
2. `frontend/lib/api.ts`
3. `frontend/components/JamifyTopBar.tsx`
4. `frontend/context/PlayerContext.tsx`
5. `frontend/components/ClientLayout.tsx`

---

## Performance Impact

### Storage
- Adds ~300 bytes per product (JSON with 3 quality URLs)
- Minimal database impact

### API
- GraphQL: 3 additional fields returned (negligible)
- Network: Same (only one URL loaded at a time)

### Client
- LocalStorage: 1 key (`audioQuality`)
- State: QualityContext adds minimal overhead

---

## Future Enhancements

### Adaptive Quality (Post-MVP)
- Detect connection speed
- Auto-suggest quality based on bandwidth
- Show estimated bandwidth usage

### Quality Badges
- Show current quality in player UI
- Show available qualities per track
- Visual indicator when quality unavailable

### Download for Offline
- Allow users to download specific quality
- Cache for offline playback

### Analytics
- Track which qualities are most popular
- Identify frequently unavailable qualities
- Optimize backend quality generation

---

## Notes

- **Default Quality**: Medium (320k MP3) - balanced between quality and bandwidth
- **Quality Changes**: Apply to next track, not current track (avoids playback interruption)
- **Fallback**: If preferred quality unavailable, system falls back to best available
- **Global Selector**: In top bar ensures quality is always accessible
- **JSON Storage**: Allows future extensibility (e.g., adding AAC, OGG Opus formats)

---

## Questions for Testing

1. **Does the quality selector appear correctly on all pages?**
2. **Do quality changes persist across sessions?**
3. **Does audio playback use the selected quality?**
4. **Are there any console errors?**
5. **Do old products (without multi-quality data) still play?**

---

**Implementation Status**: ✅ Complete
**Ready for**: User Acceptance Testing
