# Festival Lineup Algorithm Selector - Updated Plan (2026-01-30)

## Changes from Original Implementation

### Original Design (Completed):
- ✅ Balanced (75% songs + 25% albums)
- ✅ Songs (100% songCount)
- ✅ Catalog (totalRecordings)
- ✅ Font size: Weighted combination

### New Requirements:
- 🎵 **Song Versions** (sort by songCount, font size by songCount)
- ⭐ **Shows** (sort by totalShows, font size by totalShows)
- ⏱️ **Hours** (sort by totalHours, font size by totalHours)
- **Font size based ONLY on selected stat** (not weighted)
- Stars adjust correctly after reorder

---

## Implementation Changes Needed

### 1. Update Sorting Functions (`frontend/utils/festivalSorting.ts`)

**Remove:**
- `sortBalanced()` function
- `sortByCatalog()` function

**Update:**
- Rename `sortBySongs()` → `sortBySongVersions()`

**Add:**
- `sortByShows(artists)` - Sort by totalShows descending
- `sortByHours(artists)` - Sort by totalHours descending

**Update SortAlgorithm type:**
```typescript
export type SortAlgorithm = 'songVersions' | 'shows' | 'hours';
```

**Update main function:**
```typescript
export function sortArtistsByAlgorithm(
  artists: ArtistWithStats[],
  algorithm: SortAlgorithm
): ArtistWithStats[] {
  switch (algorithm) {
    case 'songVersions':
      return sortBySongVersions(artists);
    case 'shows':
      return sortByShows(artists);
    case 'hours':
      return sortByHours(artists);
    default:
      return sortBySongVersions(artists); // Default fallback
  }
}
```

### 2. Update Context Provider (`frontend/context/FestivalSortContext.tsx`)

**Change default algorithm:**
```typescript
const DEFAULT_ALGORITHM: SortAlgorithm = 'songVersions';
```

**Update validation:**
```typescript
export function isValidAlgorithm(value: string): value is SortAlgorithm {
  return ['songVersions', 'shows', 'hours'].includes(value);
}
```

### 3. Update AlgorithmSelector Component (`frontend/components/AlgorithmSelector.tsx`)

**Update algorithm options:**
```typescript
const ALGORITHMS: AlgorithmOption[] = [
  {
    id: 'songVersions',
    icon: '🎵',
    label: 'Song Versions',
    description: 'Sort by number of song versions',
  },
  {
    id: 'shows',
    icon: '⭐',
    label: 'Shows',
    description: 'Sort by number of shows',
  },
  {
    id: 'hours',
    icon: '⏱️',
    label: 'Hours',
    description: 'Sort by hours of music',
  },
];
```

### 4. Update FestivalHero Component (`frontend/components/FestivalHero.tsx`)

**CRITICAL CHANGE - Font Size Calculation:**

Current implementation calculates font size using weighted combination (75% songs + 25% albums).

**New implementation must:**
- Get current algorithm from context
- Calculate font size based ONLY on the selected metric
- Use min/max from only that metric

**New getFontSize function:**
```typescript
const getFontSize = (artist: LineupArtist) => {
  const { algorithm } = useFestivalSort();

  let value: number;
  let minValue: number;
  let maxValue: number;

  switch (algorithm) {
    case 'songVersions':
      value = artist.songCount || 0;
      minValue = Math.min(...lineupArtists.map(a => a.songCount || 0));
      maxValue = Math.max(...lineupArtists.map(a => a.songCount || 0));
      break;
    case 'shows':
      value = artist.totalShows || 0;
      minValue = Math.min(...lineupArtists.map(a => a.totalShows || 0));
      maxValue = Math.max(...lineupArtists.map(a => a.totalShows || 0));
      break;
    case 'hours':
      value = artist.totalHours || 0;
      minValue = Math.min(...lineupArtists.map(a => a.totalHours || 0));
      maxValue = Math.max(...lineupArtists.map(a => a.totalHours || 0));
      break;
  }

  const range = maxValue - minValue || 1;
  const ratio = (value - minValue) / range;

  // Scale from 0.6rem (smallest) to 3.6rem (largest) on mobile
  // Scale from 0.8rem (smallest) to 7.2rem (largest) on desktop
  return {
    mobile: 0.6 + ratio * 3.0,    // 0.6rem to 3.6rem
    desktop: 0.8 + ratio * 6.4,   // 0.8rem to 7.2rem
  };
};
```

**Update usage:**
```typescript
{lineupArtists.map((artist, index) => {
  const fontSize = getFontSize(artist); // No longer pass individual values
  // ... rest of code
})}
```

### 5. Update Unit Tests (`frontend/utils/__tests__/festivalSorting.test.ts`)

**Remove tests for:**
- `sortBalanced()`
- `sortByCatalog()`

**Add tests for:**
- `sortBySongVersions()` (rename from `sortBySongs`)
- `sortByShows()` - Verify sorts by totalShows descending
- `sortByHours()` - Verify sorts by totalHours descending

**Test edge cases:**
- Artists with missing totalShows (should handle gracefully)
- Artists with missing totalHours (should handle gracefully)
- All artists with same value (should maintain stable order)

### 6. Star Detection (Already Fixed)

The star detection has been improved to work correctly after reordering:
- ✅ `onLayoutAnimationComplete` callback on all elements
- ✅ Debounced to run once after all animations complete
- ✅ Double RAF to ensure DOM is fully settled
- ✅ Should now correctly hide stars at line starts

---

## Migration Strategy

Since this changes the localStorage key values, we need to handle migration:

**Option 1: Simple Reset**
- Let existing users default to `songVersions`
- Old values ('balanced', 'songs', 'catalog') will fail validation and fallback

**Option 2: Migration Logic**
```typescript
// In FestivalSortContext
const stored = localStorage.getItem(STORAGE_KEY);
let initialAlgorithm = DEFAULT_ALGORITHM;

if (stored) {
  // Migrate old values
  if (stored === 'songs' || stored === 'balanced') {
    initialAlgorithm = 'songVersions';
  } else if (isValidAlgorithm(stored)) {
    initialAlgorithm = stored;
  }
}
```

**Recommendation:** Use Option 1 (simple reset) since feature is brand new.

---

## Updated Success Criteria

- ✅ 3-button selector with new labels: Song Versions, Shows, Hours
- ✅ Each button sorts by its specific metric only
- ✅ Font sizes based ONLY on selected metric (not weighted)
- ✅ Sorting changes lineup order correctly
- ✅ Font sizes morph smoothly when switching algorithms
- ✅ **Stars hidden at line starts, visible between artists on same line**
- ✅ Selection persists in localStorage
- ✅ Smooth animations when switching
- ✅ Keyboard accessible
- ✅ No console errors

---

## Implementation Order

1. ✅ **Fix star detection** (nearly complete)
2. Update `festivalSorting.ts` with new algorithms
3. Update `FestivalSortContext.tsx` with new type/default
4. Update `AlgorithmSelector.tsx` with new options
5. **Update `FestivalHero.tsx` with new font size logic** (most complex)
6. Update unit tests
7. Test all three algorithms
8. Verify font sizes change correctly
9. Verify stars adjust correctly

---

## Visual Behavior Examples

**Song Versions Selected:**
- Artists sorted by songCount descending
- Largest songCount = largest font
- Smallest songCount = smallest font
- Stars correctly positioned

**Shows Selected:**
- Artists sorted by totalShows descending
- Most shows = largest font
- Fewest shows = smallest font
- Stars correctly positioned

**Hours Selected:**
- Artists sorted by totalHours descending
- Most hours = largest font
- Fewest hours = smallest font
- Stars correctly positioned

---

## Estimated Time for Changes

- Update sorting functions: 20 min
- Update context/types: 10 min
- Update selector UI: 15 min
- **Update font size logic in FestivalHero: 30 min** (most complex)
- Update tests: 20 min
- Testing & verification: 20 min

**Total: ~2 hours**

---

## Notes

- This is a **breaking change** from the original implementation
- The weighted "Balanced" algorithm is being replaced with pure metric sorting
- Font size calculation is simpler (single metric instead of combined)
- Should be more intuitive for users (what you sort by is what sizes represent)
- Star detection improvements carry over to new implementation
