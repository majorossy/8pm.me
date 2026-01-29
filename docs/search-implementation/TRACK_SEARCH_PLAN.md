# Track Search Plan (Revised)

**Status:** Revised after swarm review identified scalability issues with original approach.

---

## Architecture Overview

**Problem with original plan:** Fetching 187,696 products client-side is unworkable.

**New approach:** Search track categories (~403), filter by version attributes, hide tracks with no matching versions.

```
User searches "Dark Star"
       ↓
Search track CATEGORIES (403 total, fast)
       ↓
Returns matching tracks with version counts
       ↓
User applies filters: Year=1972, Soundboard=true
       ↓
For each track, check if any versions match filters
       ↓
HIDE tracks with 0 matching versions
       ↓
Show tracks that have matching versions
       ↓
User expands track → sees filtered versions
       ↓
User plays selected version
```

**Key insight:** Filters apply to versions (products), but determine which tracks are visible. No separate track page needed.

---

## Data Model

### What Gets Searched (Categories)

| Level | Count | Example |
|-------|-------|---------|
| Artists | 6-7 | "STS9", "String Cheese Incident" |
| Albums | ~35 | "2024-01-15 - Red Rocks" |
| **Tracks** | ~403 | "Dark Star", "Fire on the Mountain" |

**Total searchable items:** ~445 categories (vs 187,696 products)

### What Gets Filtered (Products per Track)

Each track category contains products (versions/recordings):
- "Dark Star" → 150+ versions across different shows
- Each version has: `show_date`, `show_venue`, `lineage` (soundboard indicator)

---

## Implementation Plan

### Step 1: Track Category Search

**File:** `frontend/lib/api.ts`

```typescript
// New function: Search track categories only
export async function searchTracks(query: string): Promise<TrackCategory[]> {
  // Fetch all track categories (is_song=1) - ~403 items, cacheable
  const allTracks = await getTrackCategories();

  // Filter by query (case-insensitive match)
  const matches = allTracks.filter(track =>
    track.name.toLowerCase().includes(query.toLowerCase())
  );

  return matches.slice(0, 20); // Limit results
}

// Cache track categories (they rarely change)
let trackCategoryCache: TrackCategory[] | null = null;

async function getTrackCategories(): Promise<TrackCategory[]> {
  if (trackCategoryCache) return trackCategoryCache;

  const data = await graphqlFetch(GET_TRACK_CATEGORIES_QUERY);
  trackCategoryCache = data.categories.items;
  return trackCategoryCache;
}
```

**GraphQL Query:**
```graphql
query GetTrackCategories {
  categories(
    filters: { is_song: { eq: "1" } }
    pageSize: 500
  ) {
    items {
      uid
      name
      url_key
      parent_category_uid
      product_count
    }
  }
}
```

### Step 2: Fetch Versions for Matching Tracks

When search returns track categories, fetch versions (products) for each:

```typescript
async function searchTracksWithVersions(query: string, filters: VersionFilters) {
  // 1. Get matching track categories
  const tracks = await searchTracks(query);

  // 2. For each track, fetch its versions (products)
  const tracksWithVersions = await Promise.all(
    tracks.map(async (track) => {
      const versions = await getVersionsForTrack(track.uid);
      const filteredVersions = applyFilters(versions, filters);
      return { ...track, versions: filteredVersions };
    })
  );

  // 3. HIDE tracks with 0 matching versions
  return tracksWithVersions.filter(t => t.versions.length > 0);
}
```

### Step 3: Version Filters (Applied to Search Results)

**Filter UI Component:** `frontend/components/SearchFilters.tsx`

```typescript
interface VersionFilters {
  year?: number;           // 1972, 1973, etc.
  dateFrom?: string;       // "1972-05-11"
  dateTo?: string;         // "1972-12-31"
  venue?: string;          // "Madison Square Garden"
  isSoundboard?: boolean;  // true = SBD only
}
```

**Product fields to filter on:**
- `show_date` → Year, Date range
- `show_venue` → Venue text match
- `lineage` → Contains "SBD" or "soundboard" = soundboard

### Step 4: Update Search UI

**Files to modify:**
- `frontend/components/JamifySearchOverlay.tsx` - Use new `searchTracksWithVersions()`
- `frontend/app/search/page.tsx` - Add filter controls, use new search

**Search results display:**
- Filter bar at top (Year, Date, Venue, Soundboard toggle)
- Track results below (only tracks with matching versions)
- Each track shows: name, artist, matching version count
- Expand track → shows VersionCarousel with filtered versions
- Tracks with 0 matching versions are hidden

---

## Filter Implementation Details

### Soundboard Detection

**File:** `frontend/lib/utils/recordingSource.ts` (already exists)

```typescript
// Existing utility at frontend/lib/utils/recordingSource.ts
export function isSoundboard(lineage: string): boolean {
  const lower = lineage.toLowerCase();
  return lower.includes('sbd') ||
         lower.includes('soundboard') ||
         lower.includes('direct');
}
```

### Year Extraction

```typescript
function extractYear(showDate: string): number | null {
  // Format: "1972-05-11" or "May 11, 1972"
  const match = showDate.match(/\b(19|20)\d{2}\b/);
  return match ? parseInt(match[0]) : null;
}
```

### Venue Matching

```typescript
function matchesVenue(venue: string, query: string): boolean {
  return venue.toLowerCase().includes(query.toLowerCase());
}
```

---

## Files to Create

| File | Purpose |
|------|---------|
| `frontend/components/SearchFilters.tsx` | Filter bar (Year, Date, Venue, Soundboard) |

## Files to Modify

| File | Changes |
|------|---------|
| `frontend/lib/api.ts` | Add `searchTracksWithVersions()`, `getTrackCategories()`, `getVersionsForTrack()`, `applyFilters()` |
| `frontend/components/JamifySearchOverlay.tsx` | Add filters, use `searchTracksWithVersions()`, hide tracks with 0 matches |
| `frontend/app/search/page.tsx` | Same changes as overlay |
| `frontend/components/TrackCard.tsx` | Accept pre-filtered versions, display in VersionCarousel |

---

## Performance Characteristics

| Operation | Items | Payload | Time |
|-----------|-------|---------|------|
| Search tracks (categories) | ~403 categories | ~50KB | <500ms (cached) |
| Load versions for matches | ~10-20 tracks × ~100 versions each | ~200KB-1MB | 1-3s |
| Apply filters | Client-side | 0 | <50ms |

**Load strategy:** Upfront (fetch all versions for matching tracks immediately)
- Slower initial load (~2-3s) but enables instant filtering
- Tracks can be hidden immediately when no versions match

**Cache strategy:**
- Track categories: In-memory cache (module-level, ~50KB, rarely changes)
- Versions per track: Optional in-memory cache (cleared on filter change)

---

## Verification / Testing

1. **Search functionality:**
   - Search "Dark Star" → Should return matching track categories
   - Search "xyz123" → Should show "No results"

2. **Filter functionality:**
   - Set Year=1972 → Tracks with no 1972 versions disappear
   - Set Soundboard=true → Only tracks with SBD recordings remain
   - Clear filters → All matched tracks return

3. **Version display:**
   - Expand a track → VersionCarousel shows filtered versions only
   - Play a version → Audio plays correctly

4. **Edge cases:**
   - Apply filters with no results → "No tracks match your filters" message
   - Search with filters already applied → Filters persist

---

## UI/UX Improvements (From Swarm Review)

Include these from the original review:

1. **Race conditions** - Add AbortController to search
2. **Error states** - Distinguish errors from empty results
3. **Loading states** - Skeleton loaders during fetch
4. **Mobile carousel** - Make arrows visible on touch
5. **Accessibility** - Keyboard nav, aria labels, live regions

---

## Success Criteria

- [ ] Search "Dark Star" returns track category results quickly
- [ ] Filter bar appears on search results (Year, Date, Venue, Soundboard)
- [ ] Applying filter "Year=1972" hides tracks with no 1972 versions
- [ ] Applying "Soundboard=true" hides tracks with no SBD recordings
- [ ] Tracks show count of matching versions (e.g., "5 versions")
- [ ] Expand track → VersionCarousel shows only filtered versions
- [ ] Play button plays selected version
- [ ] Mobile-friendly filter UI
- [ ] Clear filters button resets all filters
