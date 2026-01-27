# Phase 3: Swarming Execution Strategy

**Goal:** Implement 4 polish features using parallel agents
**Total Sequential Time:** 22-29 hours → **Swarmed Time:** ~15-16 hours

---

## Strategy: 3 Waves

**Why 3 waves instead of all parallel?**
- Haptic Feedback touches EVERY file (add vibrate to all buttons)
- Crossfade requires heavy PlayerContext refactor
- Running these with other agents causes massive merge conflicts

**Solution:** Isolate high-conflict features into separate waves

---

## Wave 1: Independent Features (8 hours)

Launch 2 agents in parallel - NO conflicts ✅

### Agent A: Follow Artists/Albums (6-8h)
**Files to create:**
- None (extends existing WishlistContext)

**Files to modify:**
- `context/WishlistContext.tsx` - Add followedArtists, followedAlbums arrays
- `app/artists/[slug]/page.tsx` - Add follow button
- `app/artists/[slug]/album/[album]/page.tsx` - Add follow button
- `app/library/page.tsx` - Add "Artists" data to existing Artists tab

**No conflicts:** Isolated feature, only touches artist pages

---

### Agent B: Media Session API (6-8h)
**Files to create:**
- `hooks/useMediaSession.ts`

**Files to modify:**
- `context/PlayerContext.tsx` - Integrate useMediaSession hook
- `components/ClientLayout.tsx` - Add MediaSession setup

**Low conflict:** PlayerContext integration is small (call the hook)

---

## Wave 2: Heavy Refactor (10 hours)

Launch 1 agent AFTER Wave 1 completes

### Agent C: Crossfade (8-10h)
**Files to create:**
- `hooks/useCrossfade.ts` (dual audio element logic)

**Files to modify:**
- `context/PlayerContext.tsx` - Major refactor (dual audio elements)
- `components/JamifyFullPlayer.tsx` - Add crossfade slider in settings
- `app/settings/page.tsx` - Add crossfade preference

**Why separate wave:**
- PlayerContext already modified by Media Session in Wave 1
- Crossfade requires complete rewrite of audio playback logic
- Need clean merge of Wave 1 before starting

---

## Wave 3: Final Polish (3 hours)

Launch 1 agent AFTER Wave 2 completes

### Agent D: Haptic Feedback (2-3h)
**Files to create:**
- `hooks/useHaptic.ts` (helper hook)

**Files to modify:**
- Every file with buttons/interactive elements (~15-20 files)
- `components/Queue.tsx`
- `components/SongCard.tsx`
- `components/TrackCard.tsx`
- `components/JamifyFullPlayer.tsx`
- `components/BottomPlayer.tsx`
- `components/JamifyMobileNav.tsx`
- All playlist components
- All library components
- All search components

**Why last wave:**
- Touches almost every file in the codebase
- Simple change (add 1-2 lines per file)
- Easy to apply to finished code
- Acts as final polish layer

---

## Timeline

| Wave | Features | Duration | Wait |
|------|----------|----------|------|
| Wave 1 | Follow Artists + Media Session | 8h | - |
| Merge 1 | - | 1h | After Wave 1 |
| Wave 2 | Crossfade | 10h | After Merge 1 |
| Merge 2 | - | 1h | After Wave 2 |
| Wave 3 | Haptic | 3h | After Merge 2 |
| Merge 3 | - | 30min | After Wave 3 |
| Testing | All features | 1h | After Merge 3 |

**Total elapsed:** ~15.5 hours (vs 22-29 hours sequential)
**Speedup:** 1.5-2x faster

---

## Wave 1: Agent Prompts

### Agent A: Follow Artists/Albums

```
Implement "Follow Artists/Albums" for Jamify (Spotify-style music app).

Context:
- WishlistContext already exists with likedSongs functionality
- Artist pages exist at app/artists/[slug]/page.tsx
- Album pages exist at app/artists/[slug]/album/[album]/page.tsx
- Library page has 4 tabs: Songs, Artists, Albums, Recently Played

Requirements:

1. Extend WishlistContext.tsx:
   - Add followedArtists: string[] (artist slugs)
   - Add followedAlbums: string[] (album identifiers)
   - Add methods:
     - followArtist(slug: string)
     - unfollowArtist(slug: string)
     - isArtistFollowed(slug: string): boolean
     - followAlbum(artistSlug: string, albumTitle: string)
     - unfollowAlbum(artistSlug: string, albumTitle: string)
     - isAlbumFollowed(artistSlug: string, albumTitle: string): boolean
   - Persist to localStorage (jamify_followed_artists, jamify_followed_albums)

2. Add follow button to artist page (app/artists/[slug]/page.tsx):
   - Heart icon button in header (next to artist name)
   - Shows filled/green when followed
   - Click toggles follow/unfollow

3. Add follow button to album page (app/artists/[slug]/album/[album]/page.tsx):
   - Heart icon button in header (next to album title)
   - Shows filled/green when followed
   - Click toggles follow/unfollow

4. Update Library page (app/library/page.tsx):
   - Artists tab: Filter to show only followed artists
   - Albums tab: Filter to show only followed albums
   - If no followed items, show empty state "Follow artists to see them here"

Data structures:
- followedArtists: ["sts9", "grateful-dead", "phish"]
- followedAlbums: ["sts9::Artifact", "grateful-dead::American Beauty"]

Files to modify:
- context/WishlistContext.tsx
- app/artists/[slug]/page.tsx
- app/artists/[slug]/album/[album]/page.tsx
- app/library/page.tsx

Working directory: /Users/chris.majorossy/Projects/docker-desktop/8pm/frontend

Deliverable: Working follow buttons and filtered Library tabs
```

---

### Agent B: Media Session API

```
Implement Media Session API for Jamify (Spotify-style music app).

Context:
- PlayerContext has currentSong, isPlaying, play(), pause(), next(), previous()
- Need lock screen controls and system notification integration
- Modern browsers support Media Session API

Requirements:

1. Create useMediaSession.ts hook:
   - Update navigator.mediaSession.metadata when song changes
   - Set artwork (album art), title, artist, album
   - Register action handlers:
     - play → PlayerContext.play()
     - pause → PlayerContext.pause()
     - previoustrack → PlayerContext.previous()
     - nexttrack → PlayerContext.next()
     - seekto → PlayerContext.seek(time)
   - Update playback state (playing/paused/none)
   - Update position state (current time, duration)

2. Integrate into PlayerContext.tsx:
   - Call useMediaSession() hook
   - Pass currentSong, isPlaying, currentTime, duration
   - Update metadata when song changes
   - Update position every second

3. Handle edge cases:
   - Feature detection (check if 'mediaSession' in navigator)
   - Artwork fallback if image fails to load
   - Clear metadata when no song playing

Example Media Session code:
```typescript
if ('mediaSession' in navigator) {
  navigator.mediaSession.metadata = new MediaMetadata({
    title: song.title,
    artist: song.artist,
    album: song.album,
    artwork: [
      { src: albumArt, sizes: '512x512', type: 'image/jpeg' }
    ]
  });

  navigator.mediaSession.setActionHandler('play', () => play());
  navigator.mediaSession.setActionHandler('pause', () => pause());
  navigator.mediaSession.setActionHandler('nexttrack', () => next());
  navigator.mediaSession.setActionHandler('previoustrack', () => previous());

  navigator.mediaSession.playbackState = isPlaying ? 'playing' : 'paused';
}
```

Files to create:
- hooks/useMediaSession.ts

Files to modify:
- context/PlayerContext.tsx (integrate hook)

Working directory: /Users/chris.majorossy/Projects/docker-desktop/8pm/frontend

Deliverable: Lock screen controls and system notifications working
```

---

## Wave 2: Agent Prompt

### Agent C: Crossfade

```
Implement crossfade between tracks for Jamify (Spotify-style music app).

IMPORTANT: Wave 1 completed - Media Session API already integrated into PlayerContext.

Context:
- PlayerContext currently uses single <audio> element
- Need dual audio elements for crossfade
- Crossfade duration: 0-12 seconds (user configurable)

Requirements:

1. Create useCrossfade.ts hook:
   - Manage 2 audio elements (audioA, audioB)
   - Track which is "active" and which is "next"
   - When remaining time < crossfade duration:
     - Preload next track in inactive element
     - Start fading out active element
     - Start fading in next element
   - Swap active/inactive when crossfade completes
   - Handle edge cases:
     - User skips during crossfade
     - No next track (don't crossfade)
     - Crossfade disabled (duration = 0)

2. Refactor PlayerContext.tsx:
   - Replace single audio ref with dual audio elements
   - Integrate useCrossfade hook
   - Keep existing API (play, pause, next, seek) working
   - Add crossfadeDuration state (default: 3 seconds)
   - Add setCrossfadeDuration(seconds)

3. Add crossfade settings UI:
   - In JamifyFullPlayer.tsx settings panel (already exists from Phase 2)
   - Slider: 0-12 seconds
   - Label shows current value: "Crossfade: 5s"
   - 0 = disabled (no crossfade)

4. Persist crossfade preference:
   - Save to localStorage: jamify_crossfade_duration
   - Load on mount

Volume fade algorithm:
```typescript
// When crossfading
const progress = timeInCrossfade / crossfadeDuration;
activeAudio.volume = Math.max(0, 1 - progress);
nextAudio.volume = Math.min(1, progress);
```

Files to create:
- hooks/useCrossfade.ts

Files to modify:
- context/PlayerContext.tsx (major refactor - dual audio)
- components/JamifyFullPlayer.tsx (add crossfade slider to settings panel)

Files to read for context:
- hooks/useSleepTimer.ts (similar hook pattern)
- context/PlayerContext.tsx (current implementation)

Working directory: /Users/chris.majorossy/Projects/docker-desktop/8pm/frontend

Deliverable: Smooth crossfade between tracks with configurable duration
```

---

## Wave 3: Agent Prompt

### Agent D: Haptic Feedback

```
Implement haptic feedback for Jamify (Spotify-style music app).

IMPORTANT: All Phase 3 features completed - this is final polish layer.

Context:
- All interactive elements already have CSS active: states
- Need to add navigator.vibrate() calls
- Must respect prefers-reduced-motion preference

Requirements:

1. Create useHaptic.ts hook:
   - vibrate(pattern: number | number[])
   - Feature detection ('vibrate' in navigator)
   - Check prefers-reduced-motion (skip if enabled)
   - Provide presets:
     - BUTTON_PRESS: 10ms
     - SWIPE_COMPLETE: [10, 50, 10]
     - DELETE_ACTION: 20ms
     - LONG_PRESS: 50ms

2. Add haptic feedback to ALL interactive elements:

Button presses (10ms):
   - Play/pause buttons
   - Next/previous buttons
   - Like/heart buttons
   - Playlist add buttons
   - Queue add buttons
   - Share buttons
   - Follow buttons
   - Settings buttons
   - All navigation buttons

Swipe gestures ([10, 50, 10]):
   - Swipe to delete in queue
   - Swipe up to expand player
   - Swipe down to collapse player

Delete actions (20ms):
   - Remove from queue
   - Remove from playlist
   - Delete playlist

Long press actions (50ms):
   - Long press to reorder
   - Long press menu open

3. Files to add haptic to (non-exhaustive):
   - components/BottomPlayer.tsx
   - components/JamifyFullPlayer.tsx
   - components/Queue.tsx
   - components/SongCard.tsx
   - components/TrackCard.tsx
   - components/JamifyMobileNav.tsx
   - components/Playlists/* (all playlist components)
   - components/Library/* (all library components)
   - components/JamifySearchOverlay.tsx
   - All components with buttons

4. Implementation pattern:
```typescript
import { useHaptic } from '@/hooks/useHaptic';

function MyComponent() {
  const { vibrate, BUTTON_PRESS } = useHaptic();

  const handleClick = () => {
    vibrate(BUTTON_PRESS);
    // ... existing click logic
  };
}
```

Files to create:
- hooks/useHaptic.ts

Files to modify:
- ~15-20 component files (add haptic to existing handlers)

Working directory: /Users/chris.majorossy/Projects/docker-desktop/8pm/frontend

Deliverable: Haptic feedback on all interactive elements
```

---

## Merge Strategy

### After Wave 1 (Follow Artists + Media Session)

**Conflicts: LOW** ✅

Only PlayerContext.tsx touched by both agents:
- Follow Artists: Doesn't touch PlayerContext
- Media Session: Adds useMediaSession() call

**Merge steps:**
1. Verify both features work independently
2. No actual conflicts expected
3. Test: Lock screen controls + follow buttons both work

---

### After Wave 2 (Crossfade)

**Conflicts: MEDIUM** ⚠️

PlayerContext.tsx heavily refactored:
- Media Session from Wave 1: Uses single audio element
- Crossfade: Switches to dual audio elements

**Merge steps:**
1. Crossfade agent must read current PlayerContext.tsx (with Media Session)
2. Maintain Media Session calls in refactored code
3. Test: Lock screen controls still work with dual audio
4. Test: Crossfade works
5. Verify: All existing playback features work (play/pause/seek/next/prev)

---

### After Wave 3 (Haptic)

**Conflicts: LOW** ✅

Haptic only adds 1-2 lines to each file (vibrate call):
- Unlikely to conflict with any logic
- Pure additive changes

**Merge steps:**
1. Verify haptic works on all buttons
2. Test: No regressions in existing features
3. Test: Respects reduced motion preference

---

## Verification Checklist

### After Wave 1:
- [ ] Artist page has follow button
- [ ] Album page has follow button
- [ ] Follow button turns green when clicked
- [ ] Library > Artists shows only followed artists
- [ ] Library > Albums shows only followed albums
- [ ] Lock screen shows now playing controls
- [ ] Lock screen controls work (play/pause/next/prev)
- [ ] System notification shows album art
- [ ] Bluetooth headphone controls work

### After Wave 2:
- [ ] Crossfade slider in settings (0-12s)
- [ ] Crossfade fades between tracks smoothly
- [ ] Volume fades correctly (out + in)
- [ ] Crossfade disabled when set to 0
- [ ] Skip during crossfade works correctly
- [ ] Last track doesn't attempt crossfade
- [ ] Lock screen controls still work (no regression)
- [ ] Media Session still updates correctly

### After Wave 3:
- [ ] All buttons vibrate on press (10ms)
- [ ] Swipe gestures vibrate ([10, 50, 10])
- [ ] Delete actions vibrate (20ms)
- [ ] Haptic respects reduced motion
- [ ] No regressions in any Phase 1/2/3 features

---

## Final Phase 3 Feature List

After all waves complete:
- ✅ Follow artists/albums
- ✅ Lock screen controls
- ✅ System notification with album art
- ✅ Crossfade between tracks (0-12s)
- ✅ Haptic feedback on all interactions

**Feature parity: 85%** 🎯

---

## Ready to Execute

**When to start:** After Phase 2 completes and merges

**Launch sequence:**
1. Launch Wave 1 (2 agents in parallel)
2. Wait for completion + merge (~9h)
3. Launch Wave 2 (1 agent)
4. Wait for completion + merge (~11h)
5. Launch Wave 3 (1 agent)
6. Wait for completion + merge (~4h)

**Total elapsed:** 15-16 hours

**Commands ready:** All agent prompts above are copy-paste ready for Task tool
