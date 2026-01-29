# Fix Search for Individual Songs with Version Display

## Problem

Search currently only works for artists. Searching for song titles returns empty results because the search function explicitly returns `tracks: []` with a comment "Product search has issues with Magento GraphQL - will fix separately."

**User wants:**
- Search for individual songs/tracks
- Show multiple versions like album pages do (using VersionCarousel)

## Current State

**Frontend:**
- `search()` in `frontend/lib/api.ts:1105-1139` returns `{ artists: Artist[], albums: [], tracks: [] }`
- Search UI expects `tracks: Song[]` but should be `tracks: Track[]`
- TrackCard component exists but isn't used in search results
- VersionCarousel component exists and displays multiple versions

**Backend:**
- GraphQL query `GET_SONGS_BY_SEARCH_QUERY` is defined but unused
- `getSongs()` function works and fetches products
- `groupProductsIntoTracks()` helper exists (line 655) - groups products by song_title

## Solution Approach

**Client-side search with critical fixes** (recommended - 45-60 min vs 30-45 min for GraphQL fix)

Fetch products client-side, filter by query, group by song_title into Track objects, display with TrackCard + VersionCarousel.

**Swarm Review Findings (3 agents):**
- ✅ Performance viable for 500-2000 products (3s cold, <500ms warm)
- ❌ **Critical**: Artist collision bug - "Fire" by Grateful Dead mixes with "Fire" by Phish
- ❌ **Critical**: Code duplication - 200+ lines duplicated between 2 components
- ❌ **Critical**: Tracks with 100+ versions overwhelm carousel
- 💡 **Fix**: 4 improvements add 30 min but prevent major issues

## Critical Fixes (From Swarm Review)

**Must implement these 4 changes to avoid major bugs:**

### Fix 1: Prevent Artist Collision
**Problem:** Grouping by song_title only mixes tracks from different artists
**Fix:** Group by `(artistId, trackTitle)` tuple instead

### Fix 2: Extract SearchResults Component
**Problem:** 200+ lines duplicated between JamifySearchOverlay and search/page
**Fix:** Create `frontend/components/SearchResults.tsx` shared component

### Fix 3: Limit Versions Per Track
**Problem:** Tracks with 100+ versions create unusable carousels
**Fix:** Show max 20 versions, add "View all X versions" button

### Fix 4: Add localStorage Cache
**Problem:** Fetching 500-1000 products on every search is slow
**Fix:** 5-minute localStorage cache for instant repeat searches

## Implementation Steps

### 1. Update `frontend/lib/api.ts` search function (lines 1105-1139)

**Change return type:**
```typescript
// From:
tracks: Song[]
// To:
tracks: Track[]
```

**Replace implementation:**
1. Keep artist search (already works)
2. Add: `const allSongs = await getSongs(500)` - fetch 500 products
3. Filter: Match query against `song.trackTitle`, `song.title`, `song.artistName`, or `song.albumName`
4. Group by track title:
   - Create Map<string, MagentoProduct[]>
   - Convert Song → MagentoProduct for grouping
   - Use existing data structure pattern from groupProductsIntoTracks()
5. Convert to Track objects with:
   - `songs: Song[]` array (multiple versions)
   - `songCount: number` (number of versions)
6. Sort by version count (most versions first)
7. Limit to 20 tracks
8. Return `{ artists, albums: [], tracks }`

**Key code pattern (with artist collision fix):**
```typescript
// CRITICAL FIX: Group by (artistId, trackTitle) to prevent cross-artist collision
const tracksMap = new Map<string, MagentoProduct[]>();
productsForGrouping.forEach(product => {
  const artistId = product.categories?.[0]?.uid || 'unknown';
  const trackTitle = product.song_title || product.name;
  const key = `${artistId}:${trackTitle}`; // Artist-scoped key

  if (!tracksMap.has(key)) {
    tracksMap.set(key, []);
  }
  tracksMap.get(key)!.push(product);
});

// Convert to Track objects (limit to 20 versions per track)
const tracks = Array.from(tracksMap.entries()).map(([key, trackProducts]) => {
  const firstProduct = trackProducts[0];
  const artistCategory = firstProduct.categories?.[0];

  return {
    id: `search-${slugify(key)}`,
    title: firstProduct.song_title || firstProduct.name,
    slug: slugify(firstProduct.song_title || firstProduct.name),
    songs: trackProducts.slice(0, 20).map(p => productToSong(p)), // LIMIT to 20
    songCount: trackProducts.length, // Keep full count for "View all"
    // ... other fields
  };
});
```

### 2. Create `frontend/components/SearchResults.tsx` (NEW - Eliminates Duplication)

**Purpose:** Shared component for both search overlay and search page

```typescript
'use client';

import { type Artist, type Album, type Track } from '@/lib/api';
import TrackCard from '@/components/TrackCard';
import { useRouter } from 'next/navigation';

interface SearchResultsProps {
  results: {
    artists: Artist[];
    albums: Album[];
    tracks: Track[];
  };
  query: string;
  onResultClick?: () => void; // For closing overlay
}

export function SearchResults({ results, query, onResultClick }: SearchResultsProps) {
  const router = useRouter();

  if (!results.artists.length && !results.albums.length && !results.tracks.length) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-400">No results found for "{query}"</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Artists section */}
      {results.artists.length > 0 && (
        <div>
          <h3 className="text-white font-bold text-lg mb-3">Artists</h3>
          {/* Render artists */}
        </div>
      )}

      {/* Tracks section */}
      {results.tracks.length > 0 && (
        <div>
          <h3 className="text-white font-bold text-lg mb-3">
            Tracks
            <span className="text-gray-400 text-sm font-normal ml-2">
              ({results.tracks.reduce((sum, t) => sum + t.songCount, 0)} versions)
            </span>
          </h3>
          <div className="space-y-1">
            {results.tracks.map((track) => (
              <TrackCard key={track.id} track={track} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
```

### 3. Update `frontend/components/JamifySearchOverlay.tsx`

**Line 14-18: Update SearchResults interface:**
```typescript
tracks: Track[];  // Was: Song[]
```

**Top of file: Add import:**
```typescript
import { SearchResults } from '@/components/SearchResults';
```

**Lines 239-263: Replace entire results section:**
```typescript
<SearchResults
  results={results}
  query={debouncedQuery}
  onResultClick={onClose}
/>
```

**Remove:** All result rendering code and `handleTrackClick` function

### 4. Update `frontend/app/search/page.tsx`

**Simpler changes (using shared component):**
1. Update SearchResults interface (lines 10-14): `tracks: Track[]`
2. Add import: `import { SearchResults } from '@/components/SearchResults';`
3. Replace results section with: `<SearchResults results={results} query={debouncedQuery} />`

## How It Works

**Data Flow:**
```
User searches "dark star"
  ↓
search() fetches 500 products via getSongs()
  ↓
Filters: products where title/artist/album contains "dark star"
  ↓
Groups by song_title:
  Track {
    title: "Dark Star",
    songs: [
      Song { showDate: "1972-05-11", venue: "Rotterdam" },
      Song { showDate: "1973-11-14", venue: "San Diego" },
      Song { showDate: "1974-06-23", venue: "Miami" }
    ],
    songCount: 3
  }
  ↓
UI displays TrackCard with VersionCarousel
  ↓
User clicks track → expands → sees scrollable version cards
```

**UI Pattern (from album pages):**
- TrackCard is expandable row
- Click to expand → shows VersionCarousel
- VersionCarousel displays scrollable version cards
- Each card shows: year, venue, location, date, rating, taper
- Sort by "Newest First" / "Oldest First"
- Play/Queue buttons per version

## Testing

**Test queries:**
1. "dark star" → Track with multiple versions from different shows
2. "phish" → Phish artist + multiple Phish tracks
3. "madison square garden" → Tracks from MSG
4. "china" → Partial matches (China Cat, China Doll, etc.)
5. "" → Empty results
6. "xyzabc123" → No results message

**For each track result:**
- Click to expand
- Verify VersionCarousel appears
- Verify multiple version cards shown
- Click version, verify play button works
- Test sorting (Newest/Oldest)

**Console validation:**
```
[search] Starting search for: dark star
[search] Fetching songs for search...
[search] Found matching songs: 45
[search] Grouped into tracks: 1
[search] Returning results: { artists: 0, albums: 0, tracks: 1 }
```

## Critical Files

**To create:**
1. `frontend/components/SearchResults.tsx` - NEW shared component (eliminates 200+ lines duplication)

**To modify:**
1. `frontend/lib/api.ts` - Update search() function with artist-scoped grouping + cache (lines 1105-1139)
2. `frontend/components/JamifySearchOverlay.tsx` - Use SearchResults component (lines 239-263)
3. `frontend/app/search/page.tsx` - Use SearchResults component (lines 239-263)

**Reference (existing patterns):**
- `frontend/lib/api.ts:655` - groupProductsIntoTracks() helper (reference, don't reuse directly)
- `frontend/components/TrackCard.tsx` - Track display with VersionCarousel
- `frontend/components/VersionCarousel.tsx` - Scrollable version cards (auto-limits to viewport)
- `frontend/lib/types.ts` - Track and Song type definitions

## Performance

**With optimizations:**
- Initial: 1000 products fetched (~3-4 sec cold, 0ms warm with cache)
- localStorage cache: 5min TTL, instant for repeat searches
- Filtering: <100ms (in-memory)
- Grouping: <50ms (Map operations)
- **Total: ~4 sec cold, 0-100ms warm with cache**
- Memory: ~2.5MB (1000 products + cache)

**Performance tested by swarm agent:**
- ✅ Scales to 2000 products before degradation
- ✅ <500ms for cached searches
- ✅ Progressive loading shows artists instantly, tracks after

**Future migration:**
- Switch to GraphQL search when Magento issues fixed (separate task)

## Success Criteria

**Functionality:**
- ✅ Search "dark star" returns Track with multiple Song versions
- ✅ "Fire" by Grateful Dead separate from "Fire" by Phish (artist collision fix)
- ✅ Tracks limited to 20 versions max (prevents carousel overload)
- ✅ Each track expands to show VersionCarousel
- ✅ Version cards show date, venue, rating, etc.
- ✅ Play button plays correct version
- ✅ Sorting by Newest/Oldest works

**Performance:**
- ✅ Cold search <4 seconds
- ✅ Cached search <100ms
- ✅ No memory leaks in long sessions

**Code Quality:**
- ✅ No code duplication (shared SearchResults component)
- ✅ Works on both search overlay and /search page
- ✅ No console errors

## Alternative: Fix GraphQL Search (Optional)

If client-side search is too slow, follow diagnostic plan in:
`docs/frontend/MAGENTO_SEARCH_FIX_PLAN.md`

**Steps:**
1. Diagnose Magento logs for GraphQL errors
2. Test simplified query
3. Fix custom attributes in GraphQL schema
4. Reindex search
5. Use GET_SONGS_BY_SEARCH_QUERY instead of getSongs()

**Time:** 30-45 min vs 15-20 min for client-side

## Rollback

If issues occur:
1. Revert search() to return `tracks: []`
2. Revert SearchResults interface to `tracks: Song[]`
3. Run `frontend/bin/refresh`
