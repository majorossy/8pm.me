# Track Search Plan (Revised)

**Status:** ✅ COMPLETE - All filter functionality implemented and wired.

**Last Updated:** 2026-01-30

---

## Implementation Status

| Component | Status | Notes |
|-----------|--------|-------|
| `SearchFilters.tsx` | ✅ Done | UI with Year, Venue, SBD toggle |
| `getVersionsForTrack()` | ✅ Done | `frontend/lib/api.ts` |
| `/api/track-versions` route | ✅ Done | `frontend/app/api/track-versions/route.ts` |
| `VersionFilters` interface | ✅ Done | `frontend/lib/filters.ts` (centralized) |
| `applyFilters()` | ✅ Done | `frontend/lib/filters.ts` |
| `searchTracksWithVersions()` | ✅ Done | `frontend/lib/api.ts` |
| `reapplyFilters()` | ✅ Done | `frontend/lib/api.ts` (instant client-side re-filter) |
| Filter state in search overlay | ✅ Done | `JamifySearchOverlay.tsx` updated |
| Filter state in search page | ✅ Done | `app/search/page.tsx` updated |
| Available years extraction | ✅ Done | `getAllAvailableYears()` in api.ts |
| Loading states during filter | ✅ Done | Skeleton loaders added |
| Race condition handling | ✅ Done | AbortController in search effects |
| "No tracks match filters" message | ✅ Done | Clear filters button included |
| Version count in track results | ✅ Done | Shows "X versions (of Y)" when filtered |

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

| File | Purpose | Status |
|------|---------|--------|
| `frontend/components/SearchFilters.tsx` | Filter bar (Year, Date, Venue, Soundboard) | ✅ EXISTS |
| `frontend/lib/filters.ts` | Filter utility functions | ❌ TODO |

## Files to Modify

| File | Changes | Status |
|------|---------|--------|
| `frontend/lib/api.ts` | Add `searchTracksWithVersions()`, `applyFilters()` | ❌ TODO |
| `frontend/components/JamifySearchOverlay.tsx` | Add filter state, render `SearchFilters`, use filtered results | ❌ TODO |
| `frontend/app/search/page.tsx` | Same changes as overlay | ❌ TODO |
| `frontend/components/SearchTrackResult.tsx` | Accept pre-filtered versions, show version count | ❌ TODO |

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

## Detailed Implementation (TODO)

### 1. `applyFilters()` Function

**File:** `frontend/lib/filters.ts` (new file)

```typescript
import { Song } from './api';
import { VersionFilters } from '@/components/SearchFilters';
import { isSoundboard } from './utils/recordingSource';

/**
 * Apply filters to a list of song versions
 * Returns only versions matching ALL active filters
 */
export function applyFilters(versions: Song[], filters: VersionFilters): Song[] {
  return versions.filter(version => {
    // Year filter
    if (filters.year) {
      const versionYear = extractYear(version.showDate || '');
      if (versionYear !== filters.year) return false;
    }

    // Date range filter
    if (filters.dateFrom || filters.dateTo) {
      const versionDate = version.showDate || '';
      if (filters.dateFrom && versionDate < filters.dateFrom) return false;
      if (filters.dateTo && versionDate > filters.dateTo) return false;
    }

    // Venue filter (partial match)
    if (filters.venue) {
      const venue = version.showVenue || '';
      if (!venue.toLowerCase().includes(filters.venue.toLowerCase())) return false;
    }

    // Soundboard filter
    if (filters.isSoundboard) {
      const lineage = version.lineage || '';
      if (!isSoundboard(lineage)) return false;
    }

    return true;
  });
}

/**
 * Extract year from show date string
 */
export function extractYear(showDate: string): number | null {
  if (!showDate) return null;
  const match = showDate.match(/\b(19|20)\d{2}\b/);
  return match ? parseInt(match[0]) : null;
}

/**
 * Get unique years from a list of versions
 * Used to populate the year dropdown with available options
 */
export function getAvailableYears(versions: Song[]): number[] {
  const years = new Set<number>();
  versions.forEach(v => {
    const year = extractYear(v.showDate || '');
    if (year) years.add(year);
  });
  return Array.from(years).sort((a, b) => b - a); // Descending
}
```

### 2. `searchTracksWithVersions()` Function

**File:** `frontend/lib/api.ts` (add to existing)

```typescript
import { applyFilters, getAvailableYears } from './filters';
import { VersionFilters } from '@/components/SearchFilters';

export interface TrackWithVersions extends TrackCategory {
  versions: Song[];
  filteredVersions: Song[];
  availableYears: number[];
}

/**
 * Search tracks and fetch versions, applying filters
 * Returns tracks with their filtered versions (tracks with 0 matches are excluded)
 */
export async function searchTracksWithVersions(
  query: string,
  filters: VersionFilters
): Promise<TrackWithVersions[]> {
  // 1. Search track categories (fast, cached)
  const tracks = await searchTrackCategories(query);

  // 2. Fetch versions for each track in parallel
  const tracksWithVersions = await Promise.all(
    tracks.slice(0, 20).map(async (track) => {
      const versions = await getVersionsForTrack(track.uid);
      const filteredVersions = applyFilters(versions, filters);
      const availableYears = getAvailableYears(versions);

      return {
        ...track,
        versions,
        filteredVersions,
        availableYears,
      };
    })
  );

  // 3. Filter out tracks with 0 matching versions (when filters active)
  const hasActiveFilters = filters.year || filters.venue || filters.isSoundboard || filters.dateFrom || filters.dateTo;

  if (hasActiveFilters) {
    return tracksWithVersions.filter(t => t.filteredVersions.length > 0);
  }

  return tracksWithVersions;
}
```

### 3. State Management in Search Components

**File:** `frontend/components/JamifySearchOverlay.tsx` (modifications)

```typescript
import { useState, useCallback } from 'react';
import { SearchFilters, VersionFilters } from './SearchFilters';
import { searchTracksWithVersions, TrackWithVersions } from '@/lib/api';

// Add to existing state
const [filters, setFilters] = useState<VersionFilters>({});
const [tracksWithVersions, setTracksWithVersions] = useState<TrackWithVersions[]>([]);
const [isLoadingVersions, setIsLoadingVersions] = useState(false);

// Handle filter changes
const handleFiltersChange = useCallback((newFilters: VersionFilters) => {
  setFilters(newFilters);
  // Re-filter existing tracks immediately (client-side)
  if (tracksWithVersions.length > 0) {
    const refiltered = tracksWithVersions.map(track => ({
      ...track,
      filteredVersions: applyFilters(track.versions, newFilters),
    })).filter(t => {
      const hasActiveFilters = newFilters.year || newFilters.venue || newFilters.isSoundboard;
      return !hasActiveFilters || t.filteredVersions.length > 0;
    });
    setTracksWithVersions(refiltered);
  }
}, [tracksWithVersions]);

// Modify search effect to fetch versions
useEffect(() => {
  if (!debouncedQuery.trim()) return;

  const controller = new AbortController();

  const performSearch = async () => {
    setIsSearching(true);
    setIsLoadingVersions(true);
    try {
      // Existing search for artists/albums
      const response = await fetch(`/api/search?q=${encodeURIComponent(debouncedQuery)}`);
      const searchResults = await response.json();
      setResults(searchResults);

      // NEW: Fetch tracks with versions
      const tracks = await searchTracksWithVersions(debouncedQuery, filters);
      setTracksWithVersions(tracks);
    } catch (error) {
      if (error.name !== 'AbortError') {
        console.error('Search failed:', error);
      }
    } finally {
      setIsSearching(false);
      setIsLoadingVersions(false);
    }
  };

  performSearch();
  return () => controller.abort();
}, [debouncedQuery, filters]);

// In JSX, render filters above results
{debouncedQuery && (
  <SearchFilters
    filters={filters}
    onFiltersChange={handleFiltersChange}
    availableYears={getAllAvailableYears(tracksWithVersions)}
    className="px-4 py-2 border-b border-white/10"
  />
)}
```

### 4. Available Years Aggregation

```typescript
/**
 * Aggregate all available years from all tracks
 * Used to populate year dropdown with valid options
 */
function getAllAvailableYears(tracks: TrackWithVersions[]): number[] {
  const years = new Set<number>();
  tracks.forEach(track => {
    track.availableYears.forEach(y => years.add(y));
  });
  return Array.from(years).sort((a, b) => b - a);
}
```

### 5. Loading States

```typescript
// Skeleton loader for tracks during version fetch
{isLoadingVersions && (
  <div className="space-y-2 p-4">
    {[1, 2, 3].map(i => (
      <div key={i} className="animate-pulse">
        <div className="h-16 bg-[#2d2a26] rounded-lg" />
      </div>
    ))}
  </div>
)}
```

### 6. Empty Filter Results Message

```typescript
{tracksWithVersions.length === 0 && hasActiveFilters && (
  <div className="text-center py-8 text-gray-400">
    <p>No tracks match your filters</p>
    <button
      onClick={() => setFilters({})}
      className="text-[#d4a060] hover:underline mt-2"
    >
      Clear filters
    </button>
  </div>
)}
```

---

## Task Cards

### CARD-FILTER-1: Create Filter Utilities
**File:** `frontend/lib/filters.ts`
**Effort:** 1 hour
**Dependencies:** None

Create new file with:
- `applyFilters(versions, filters)` - Filter song array
- `extractYear(showDate)` - Parse year from date string
- `getAvailableYears(versions)` - Get unique years
- Unit tests for edge cases

### CARD-FILTER-2: Add searchTracksWithVersions()
**File:** `frontend/lib/api.ts`
**Effort:** 1 hour
**Dependencies:** CARD-FILTER-1

Add function that:
- Searches track categories
- Fetches versions for each (parallel)
- Applies filters
- Returns `TrackWithVersions[]`

### CARD-FILTER-3: Wire Filters to Search Overlay
**File:** `frontend/components/JamifySearchOverlay.tsx`
**Effort:** 2 hours
**Dependencies:** CARD-FILTER-1, CARD-FILTER-2

- Add `filters` state
- Import and render `SearchFilters`
- Call `searchTracksWithVersions()` instead of basic search
- Re-filter on filter change (instant, client-side)
- Add loading skeleton
- Add "no results" message for empty filter

### CARD-FILTER-4: Wire Filters to Search Page
**File:** `frontend/app/search/page.tsx`
**Effort:** 1.5 hours
**Dependencies:** CARD-FILTER-1, CARD-FILTER-2

Same changes as CARD-FILTER-3 but for full page.

### CARD-FILTER-5: Update SearchTrackResult for Versions
**File:** `frontend/components/SearchTrackResult.tsx`
**Effort:** 1 hour
**Dependencies:** CARD-FILTER-2

- Accept `filteredVersions` prop
- Show version count badge ("5 versions")
- Expand to show VersionCarousel
- Play button for first/best version

### CARD-FILTER-6: Race Condition Handling
**Files:** Search overlay + page
**Effort:** 0.5 hours
**Dependencies:** CARD-FILTER-3, CARD-FILTER-4

- Add AbortController to search effects
- Cancel in-flight requests on new search
- Prevent stale results from appearing

**Total Estimated Effort:** 7 hours

---

## Success Criteria

### Core Functionality
- [ ] Search "Dark Star" returns track category results quickly (<500ms)
- [ ] Filter bar appears above search results when query is entered
- [ ] Applying "Year=1972" hides tracks with no 1972 versions
- [ ] Applying "Soundboard=true" hides tracks with no SBD recordings
- [ ] Combining filters (Year + SBD) works correctly
- [ ] Tracks show count of matching versions (e.g., "5 versions")
- [ ] Expand track → VersionCarousel shows only filtered versions
- [ ] Play button plays selected version

### UX
- [ ] Filter changes apply instantly (no loading delay)
- [ ] Loading skeleton shows while fetching versions
- [ ] "No tracks match your filters" message when filters exclude all
- [ ] "Clear filters" button resets all filters
- [ ] Mobile: Filter bar collapses, expands on tap
- [ ] Mobile: Filter controls are touch-friendly (44px tap targets)

### Performance
- [ ] Track categories cached (subsequent searches instant)
- [ ] Version fetch parallelized (all tracks at once)
- [ ] Filter application is client-side (<50ms)
- [ ] No race conditions (AbortController prevents stale results)

### Edge Cases
- [ ] Empty search query shows recent searches (no filters)
- [ ] Search with no results shows appropriate message
- [ ] Filters persist when search query changes
- [ ] Works correctly when track has 0 versions
- [ ] Year dropdown shows only years that exist in data
