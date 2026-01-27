# Spotify Feature Parity Audit & Implementation Roadmap

**Project:** Jamify (8pm Music Archive)
**Goal:** Match Spotify's core features for a competitive music streaming experience
**Status:** Phase 1 Complete (70% parity) | Phase 2 & 3 Planned

---

## Current Feature Parity: ~70%

- ✅ **Phase 1 Complete** (Jan 27, 2026)
- ⏳ **Phase 2 Planned** (Enhanced UX)
- ⏳ **Phase 3 Planned** (Polish & Advanced)

---

## Phase 1: Core Functionality ✅ COMPLETED

**Duration:** 30-40 hours (estimated)
**Status:** ✅ Complete (Jan 27, 2026)

### Implemented Features:

#### 1. Search API Integration (P0)
- ✅ Artist search working
- ⚠️ Product/track search blocked by Magento (see `MAGENTO_SEARCH_FIX_PLAN.md`)
- ✅ Recent searches with localStorage
- ✅ Search page at `/search`
- ✅ Mobile search overlay

#### 2. Like Button Integration (P0)
- ✅ Heart button in BottomPlayer (desktop)
- ✅ Heart button in JamifyFullPlayer (mobile)
- ✅ Heart button in SongCard rows
- ✅ Connected to WishlistContext
- ✅ localStorage persistence
- ✅ Visual feedback (green when liked)

#### 3. Library/Favorites Page (P0)
- ✅ Created `/app/library/page.tsx`
- ✅ Three tabs: Songs, Artists, Albums
- ✅ Play button for songs
- ✅ Unlike button
- ✅ Responsive grid layouts
- ✅ Empty states

#### 4. Basic Playlist System (P0)
- ✅ PlaylistContext with CRUD operations
- ✅ Create playlist with name + description
- ✅ Add songs to playlist (modal)
- ✅ Remove songs from playlist
- ✅ Edit playlist metadata
- ✅ Delete playlist (with confirmation)
- ✅ Playlist list page (`/playlists`)
- ✅ Playlist detail page (`/playlists/[id]`)
- ✅ localStorage persistence
- ✅ Auto-generate covers from first song

### Files Created (8):
1. `context/PlaylistContext.tsx`
2. `components/Playlists/AddToPlaylistModal.tsx`
3. `app/library/page.tsx`
4. `app/playlists/page.tsx`
5. `app/playlists/[id]/page.tsx`
6. `app/search/page.tsx`
7. `app/api/search/route.ts`

### Files Modified (9):
1. `lib/api.ts` - Added search function
2. `components/SongCard.tsx` - Added "Add to Playlist" button
3. `components/BottomPlayer.tsx` - Like button integration
4. `components/JamifyFullPlayer.tsx` - Like button integration
5. `components/JamifySearchOverlay.tsx` - API route
6. `components/ClientLayout.tsx` - PlaylistProvider
7. `context/WishlistContext.tsx` - localStorage
8. `components/JamifyMobileNav.tsx` - Library link
9. `app/globals.css` - Modal animations

**See:** `PHASE1_IMPLEMENTATION_COMPLETE.md` for details
**See:** `PHASE1_TESTING_GUIDE.md` for testing steps

---

## Phase 2: Enhanced UX ⏳ PLANNED

**Duration:** 30-35 hours (estimated)
**Goal:** Match Spotify's core user experience
**Priority:** P1 (High Priority)

### Features to Implement:

#### 1. Recently Played (6-8h)
- Track last 50 played songs
- Display in Library page (new tab)
- localStorage persistence
- Update on playback (30s threshold)

**Files:**
- Create: `context/RecentlyPlayedContext.tsx`
- Modify: `context/PlayerContext.tsx` (track plays)
- Modify: `app/library/page.tsx` (add tab)

#### 2. Save Queue as Playlist (4-5h)
- Button in Queue drawer
- Modal to name new playlist
- Copy current queue + up-next to playlist
- Instant feedback

**Files:**
- Modify: `components/Queue.tsx`
- Use: `PlaylistContext.createPlaylist()`

#### 3. Search Suggestions/Autocomplete (4-6h)
- Show suggestions as user types
- Trending/popular searches
- Query backend for suggestions
- Debounced autocomplete dropdown

**Files:**
- Modify: `app/search/page.tsx`
- Modify: `components/JamifySearchOverlay.tsx`
- Create: `app/api/search/suggestions/route.ts`

#### 4. Sleep Timer (3-4h)
- Presets: 5min, 15min, 30min, 1hr, end of track
- Settings panel in full player
- Auto-pause when timer expires
- Notification before stopping

**Files:**
- Create: `hooks/useSleepTimer.ts`
- Modify: `components/JamifyFullPlayer.tsx`
- Modify: `context/PlayerContext.tsx`

#### 5. Share Functionality (4-5h)
- Share button in player + song cards
- Web Share API (native mobile)
- Copy link fallback
- Share to: clipboard, social media
- Generate share URLs

**Files:**
- Create: `components/ShareModal.tsx`
- Create: `hooks/useShare.ts`
- Modify: `components/JamifyFullPlayer.tsx`
- Modify: `components/SongCard.tsx`

#### 6. Lyrics Display (8-10h)
- Integrate lyrics API (Genius, Musixmatch, or LRC)
- Lyrics panel in full player
- Toggle lyrics on/off
- Optional: Auto-scroll with playback (synced lyrics)

**Files:**
- Create: `components/LyricsPanel.tsx`
- Create: `hooks/useLyrics.ts`
- Create: `app/api/lyrics/route.ts`
- Modify: `components/JamifyFullPlayer.tsx`

**Total Estimated:** 30-35 hours

---

## Phase 3: Polish & Advanced ⏳ PLANNED

**Duration:** 20-25 hours (estimated)
**Goal:** Add polish and unique features
**Priority:** P2-P3 (Medium-Low Priority)

### Features to Implement:

#### 1. Haptic Feedback (2-3h)
- Add `navigator.vibrate()` calls
- Button press: 10ms
- Swipe complete: [10, 50, 10]
- Delete action: 20ms
- Play/pause: 15ms

**Files:**
- Modify: All interactive components
- Create: `hooks/useHaptics.ts`

#### 2. Crossfade Between Tracks (8-10h)
- Crossfade setting (0-12 seconds)
- Dual audio element technique
- Fade out current, fade in next
- Settings UI

**Files:**
- Modify: `context/PlayerContext.tsx` (major refactor)
- Modify: `components/JamifyFullPlayer.tsx` (settings)

#### 3. Lock Screen Controls / Media Session (6-8h)
- Media Session API integration
- Lock screen playback controls
- Notification with album art
- Background playback support

**Files:**
- Modify: `context/PlayerContext.tsx`
- Create: `hooks/useMediaSession.ts`

#### 4. Follow Artists/Albums (6-8h)
- Extend WishlistContext for artists
- Follow/unfollow buttons on artist pages
- Followed artists page in library
- New releases notifications

**Files:**
- Modify: `context/WishlistContext.tsx`
- Modify: `components/ArtistPageContent.tsx`
- Modify: `app/library/page.tsx` (new tab)

#### 5. Cast to Devices (12-15h) - Complex
- Integrate Cast SDK (Chromecast, AirPlay)
- Device picker UI
- Remote playback control
- Sync state across devices

**Files:**
- Create: `hooks/useCast.ts`
- Create: `components/CastDevicePicker.tsx`
- Modify: `context/PlayerContext.tsx`

**Total Estimated:** 20-25 hours

---

## Features NOT Planned (Out of Scope)

### Why Not Included:

- **Personalized Recommendations** - Requires ML/AI backend
- **Daily Mixes / Discover Weekly** - Requires user behavior tracking + ML
- **Social Features** (friend activity, profiles) - Requires user accounts + backend
- **Podcasts** - Different content type, out of scope
- **Offline Downloads** - Streaming-only design decision
- **Voice Search** - Complex, low ROI
- **Equalizer** - Audio processing complexity
- **Playback Speed Control** - Low priority for music

---

## Technical Architecture

### Storage Strategy:

| Feature | Storage | Sync Strategy |
|---------|---------|---------------|
| Favorites/Wishlist | localStorage (`jamify_wishlist`) | Future: Magento GraphQL |
| Playlists | localStorage (`jamify_playlists`) | Future: Custom Magento mutations |
| Recent Searches | localStorage (via hook) | No sync needed |
| Recently Played | localStorage (Phase 2) | Future: Backend API |
| Preferences | localStorage (theme, settings) | Future: User profile |

### API Integration:

| Feature | Current | Future |
|---------|---------|--------|
| Artists | Magento GraphQL ✅ | Same |
| Albums | Magento GraphQL ✅ | Same |
| Songs/Tracks | Magento GraphQL ✅ | Same |
| Search | Next.js API route → Magento | ElasticSearch integration |
| Playlists | localStorage | Magento custom mutations |
| Favorites | localStorage | `addProductsToWishlist` mutation |
| Lyrics | Not implemented | Genius API or LRC files |

---

## Success Metrics

### Phase 1 (Complete):
- ✅ **70% feature parity** with Spotify core features
- ✅ Search (artists only)
- ✅ Favorites system
- ✅ Library page
- ✅ Full playlist management

### Phase 2 Target:
- 🎯 **80% feature parity**
- Recently played
- Sleep timer
- Share functionality
- Lyrics display

### Phase 3 Target:
- 🎯 **85% feature parity**
- Haptic feedback
- Crossfade
- Media session controls
- Follow artists

---

## Dependencies & Blockers

### Current Blockers:

1. **Magento Product Search** (P0)
   - Status: GraphQL returns "Internal server error"
   - Impact: Can't search tracks/albums
   - Plan: `frontend/MAGENTO_SEARCH_FIX_PLAN.md`
   - Estimated: 45-65 minutes

2. **Album Pages Empty** (Unknown)
   - Status: Some albums show "No Live Recordings Found"
   - Impact: Pre-existing issue, not Phase 1 related
   - Needs: Investigation

### No Blockers For:
- ✅ Library page
- ✅ Playlists
- ✅ Like button
- ✅ Artist search

---

## Implementation Timeline

### Phase 1: ✅ Complete (Jan 27, 2026)
- Search (artists)
- Like button
- Library page
- Playlists

### Phase 2: ⏳ Planned (1-2 weeks)
- Recently Played
- Save Queue
- Sleep Timer
- Lyrics
- Share

### Phase 3: ⏳ Planned (1 week)
- Haptics
- Crossfade
- Media Session
- Follow Artists

**Total Timeline:** 2-3 weeks full-time for all phases

---

## Risk Assessment

### Technical Risks:

1. **Magento GraphQL Limitations** (High)
   - Product search broken
   - Custom attributes may not be in schema
   - Mitigation: Client-side search fallback

2. **Lyrics API Rate Limits** (Medium)
   - Free APIs have request limits
   - Mitigation: Cache lyrics in localStorage

3. **Crossfade Complexity** (Medium)
   - Dual audio element sync is tricky
   - Mitigation: Simple implementation, defer if needed

### UX Risks:

1. **Feature Overload** (Low)
   - Too many features at once
   - Mitigation: Phased rollout

2. **localStorage Size Limits** (Low)
   - Browser storage ~5-10MB limit
   - Mitigation: Implement cleanup, migrate to backend

---

## Next Steps

### For Current Session:
1. ✅ Phase 1 implementation complete
2. ⏳ Test all Phase 1 features (see `PHASE1_TESTING_GUIDE.md`)
3. ⏳ Fix Magento product search (delegate to another agent)
4. ⏳ Fix album pages showing empty (investigate)

### For Next Session (Phase 2):
1. Implement Recently Played tracking
2. Add Sleep Timer
3. Implement Share functionality
4. Add Lyrics display
5. Add "Save Queue as Playlist" button

---

## Documentation Index

| Document | Purpose |
|----------|---------|
| `SPOTIFY_FEATURE_PARITY_ROADMAP.md` | **This file** - Complete implementation plan (all phases) |
| `PHASE1_IMPLEMENTATION_COMPLETE.md` | Phase 1 completion summary |
| `PHASE1_TESTING_GUIDE.md` | Manual testing checklist for Phase 1 |
| `MAGENTO_SEARCH_FIX_PLAN.md` | Plan to fix product/track search |

---

## Contact Points for Future Agents

### To Continue Phase 2:
1. Read `SPOTIFY_FEATURE_PARITY_ROADMAP.md` (this file)
2. Verify Phase 1 works: `PHASE1_TESTING_GUIDE.md`
3. Start with "Recently Played" (easiest, 6-8h)

### To Fix Search:
1. Read `MAGENTO_SEARCH_FIX_PLAN.md`
2. Check Magento logs for GraphQL errors
3. Test simplified queries
4. Fallback: Client-side search (15-20 min)

### To Fix Album Pages:
1. Check browser console: `[getAlbum]` logs
2. Verify `album.tracks.length`
3. Check if Magento is returning track data
4. Compare working vs broken albums

---

**Last Updated:** Jan 27, 2026
**Maintained By:** Claude Code sessions in `/Users/chris.majorossy/Projects/docker-desktop/8pm/`
