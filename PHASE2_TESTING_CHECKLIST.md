# Phase 2 Testing Checklist

**Date:** 2026-01-27
**Features:** Save Queue, Recently Played, Sleep Timer, Share Functionality

---

## Pre-Test Setup

- [ ] Build completes without errors (`npm run build`)
- [ ] Dev server running on http://localhost:3001 (`npm run dev`)
- [ ] Browser console clear of errors
- [ ] LocalStorage cleared for fresh test (optional)

---

## Feature 1: Save Queue as Playlist ✅

### Test Cases

**TC1.1: Save album queue to playlist**
- [ ] Navigate to an album page
- [ ] Click play album (loads tracks into queue)
- [ ] Open Queue drawer (bottom player)
- [ ] Verify green "Save as Playlist" button appears at top
- [ ] Click "Save as Playlist"
- [ ] Modal appears with input field
- [ ] Enter playlist name: "Test Album Queue"
- [ ] Click Save (or press Enter)
- [ ] Success message shows (green checkmark)
- [ ] Modal auto-closes after 1.5 seconds
- [ ] Queue drawer auto-closes

**TC1.2: Save mixed queue (album + up next)**
- [ ] Play an album
- [ ] Add 2-3 songs from another artist to "Up Next"
- [ ] Open Queue drawer
- [ ] Verify both album tracks and up next songs shown
- [ ] Click "Save as Playlist"
- [ ] Name: "Mixed Queue Test"
- [ ] Save and verify success
- [ ] Navigate to Playlists page (/playlists)
- [ ] Find "Mixed Queue Test" playlist
- [ ] Open playlist - verify all songs present (album + up next)

**TC1.3: Empty queue handling**
- [ ] Clear queue (skip to end of playlist)
- [ ] Open Queue drawer
- [ ] Verify "Save as Playlist" button NOT shown when queue empty

**TC1.4: Cancel save**
- [ ] Open Queue with songs
- [ ] Click "Save as Playlist"
- [ ] Click Cancel button
- [ ] Modal closes, queue drawer still open

---

## Feature 2: Recently Played 🎵

### Test Cases

**TC2.1: Track playback after 30 seconds**
- [ ] Play a song
- [ ] Let it play for 35+ seconds
- [ ] Navigate to Library page (/library)
- [ ] Click "Recently Played" tab (4th tab)
- [ ] Verify song appears in list
- [ ] Check timestamp shows "Just now"

**TC2.2: Play count increment**
- [ ] Play the same song again from the beginning
- [ ] Let it play for 30+ seconds
- [ ] Go to Recently Played tab
- [ ] Verify play count shows "2 plays" (or similar)
- [ ] Verify song moved to top of list
- [ ] Timestamp updated to "Just now"

**TC2.3: Multiple songs tracking**
- [ ] Play 5 different songs, each for 30+ seconds
- [ ] Go to Recently Played tab
- [ ] Verify all 5 songs appear
- [ ] Verify songs in reverse chronological order (newest first)
- [ ] Check timestamps: "Just now", "1 minute ago", "2 minutes ago", etc.

**TC2.4: Timestamp formatting**
- [ ] Check timestamps format correctly:
  - [ ] < 1 minute: "Just now"
  - [ ] 1-59 minutes: "X minutes ago"
  - [ ] 1-23 hours: "X hours ago"
  - [ ] 24-48 hours: "Yesterday"
  - [ ] 2-6 days: "X days ago"
  - [ ] 7-29 days: "X weeks ago"
  - [ ] 30+ days: "X months ago"

**TC2.5: LocalStorage persistence**
- [ ] Play 3 songs (30s each)
- [ ] Go to Recently Played - verify 3 songs
- [ ] Refresh page (F5)
- [ ] Go to Recently Played again
- [ ] Verify history persisted (still shows 3 songs)

**TC2.6: 50-song limit**
- [ ] (Optional - time-intensive) Play 52 different songs
- [ ] Verify only last 50 appear in Recently Played
- [ ] Oldest 2 songs trimmed automatically

**TC2.7: Empty state**
- [ ] Clear localStorage: `localStorage.removeItem('jamify_recently_played')`
- [ ] Refresh page
- [ ] Go to Recently Played tab
- [ ] Verify empty state shows:
  - [ ] Clock icon
  - [ ] "No recently played songs" message
  - [ ] Instructions to start listening

---

## Feature 3: Sleep Timer ⏰

### Test Cases

**TC3.1: Open settings panel**
- [ ] Play any song
- [ ] Swipe up to expand to full player
- [ ] Verify gear icon (settings) in top-right corner
- [ ] Click gear icon
- [ ] Settings panel modal opens
- [ ] Panel shows 5 preset buttons:
  - [ ] 5 minutes
  - [ ] 15 minutes
  - [ ] 30 minutes
  - [ ] 1 hour
  - [ ] End of current track

**TC3.2: Start 5-minute timer**
- [ ] Open settings panel
- [ ] Click "5 minutes" preset
- [ ] Panel closes automatically
- [ ] Bottom indicator appears: "Sleep timer: 5:00 remaining"
- [ ] Countdown updates every second (4:59, 4:58, ...)
- [ ] Music continues playing

**TC3.3: Active timer display in settings**
- [ ] With timer running, open settings again
- [ ] Verify large countdown shows: "Sleep timer: 4:XX remaining"
- [ ] Verify "Cancel" button appears
- [ ] Close settings - timer continues

**TC3.4: Cancel timer**
- [ ] Start a 15-minute timer
- [ ] Open settings panel
- [ ] Click "Cancel" button
- [ ] Timer stops
- [ ] Bottom indicator disappears
- [ ] Music continues playing

**TC3.5: 1-minute warning notification**
- [ ] Start a 5-minute timer
- [ ] Wait 4 minutes (or manually adjust countdown for testing)
- [ ] At 1:00 remaining:
  - [ ] Green toast notification appears
  - [ ] Message: "Sleep timer will stop playback in 1 minute"
  - [ ] Notification auto-dismisses after 5 seconds

**TC3.6: Auto-pause on expiry**
- [ ] Start a 5-minute timer
- [ ] Wait for countdown to reach 0:00
- [ ] Verify music automatically pauses
- [ ] Timer indicator disappears
- [ ] Verify player shows paused state

**TC3.7: "End of current track" preset**
- [ ] Play a song with ~1 minute remaining
- [ ] Open settings and select "End of current track"
- [ ] Timer shows remaining time of current song
- [ ] Song plays to completion
- [ ] Music pauses when song ends (doesn't auto-advance to next)

**TC3.8: Settings and timer coexist**
- [ ] Start any timer
- [ ] Verify gear icon still clickable
- [ ] Open settings - timer info displays alongside presets
- [ ] Can cancel and restart timer without issues

---

## Feature 4: Share Functionality 🔗

### Test Cases

**TC4.1: Share button in full player**
- [ ] Play any song
- [ ] Expand to full player
- [ ] Verify share icon/button in top-right header (near settings)
- [ ] Share button distinct from settings button

**TC4.2: Share modal - full player**
- [ ] Click share button in full player
- [ ] Modal opens with dark theme (#282828 background)
- [ ] Modal shows:
  - [ ] Song title and artist
  - [ ] "Copy Link" button
  - [ ] Web Share API button (mobile only - may not show on desktop)
  - [ ] Social media icons (Twitter, Facebook, WhatsApp)
- [ ] Click outside modal - modal closes

**TC4.3: Copy link functionality**
- [ ] Open share modal for a song
- [ ] Click "Copy Link" button
- [ ] Button shows success feedback (text changes or color)
- [ ] Open new browser tab
- [ ] Paste URL (Cmd+V or Ctrl+V)
- [ ] Verify URL format: `/artists/{artistSlug}/album/{albumIdentifier}`
- [ ] Navigate to URL - correct album page loads

**TC4.4: Web Share API (mobile only)**
- [ ] (Mobile device or mobile browser mode)
- [ ] Open share modal
- [ ] Verify "Share" button appears (uses native share)
- [ ] Click "Share" button
- [ ] Native share sheet appears
- [ ] Select app (e.g., Messages, Notes)
- [ ] Verify link shares correctly

**TC4.5: Share from SongCard (hover)**
- [ ] Navigate to any page with SongCards (Home, Search results, Playlists)
- [ ] Hover over a song row
- [ ] Verify 5 hover buttons appear:
  - [ ] Play button
  - [ ] Heart (like) button
  - [ ] Add to playlist button
  - [ ] Add to queue button
  - [ ] **Share button (NEW - 5th button)**
- [ ] Click share button
- [ ] Share modal opens for that song
- [ ] Copy link and verify URL correct

**TC4.6: Share from TrackCard (album page)**
- [ ] Navigate to an album page
- [ ] Hover over a track row
- [ ] Verify share button appears (5th hover button)
- [ ] Click share button
- [ ] Share modal opens
- [ ] Verify URL points to album page

**TC4.7: Share from playlist**
- [ ] Create a test playlist
- [ ] Open playlist detail page (/playlists/{id})
- [ ] Hover over a song in playlist
- [ ] Click share button
- [ ] Verify share URL is for the song's album

**TC4.8: Social media quick-share**
- [ ] Open share modal
- [ ] Click Twitter icon
- [ ] New tab opens with pre-filled tweet containing link
- [ ] Click Facebook icon
- [ ] New tab opens with Facebook share dialog
- [ ] Click WhatsApp icon
- [ ] WhatsApp share dialog opens (or web.whatsapp.com)

**TC4.9: Keyboard shortcuts**
- [ ] Open share modal
- [ ] Press Escape key
- [ ] Modal closes

---

## Integration Testing: Multiple Features

**INT1: Sleep Timer + Recently Played**
- [ ] Start a 5-minute sleep timer
- [ ] Play a song for 30+ seconds (gets tracked)
- [ ] Let timer expire (music pauses)
- [ ] Go to Recently Played tab
- [ ] Verify song was tracked before timer stopped
- [ ] Play count = 1

**INT2: Save Queue + Share**
- [ ] Save queue as playlist "Integration Test"
- [ ] Navigate to Playlists page
- [ ] Open "Integration Test" playlist
- [ ] Hover over a song
- [ ] Share the song
- [ ] Copy link and verify URL

**INT3: Recently Played + Share**
- [ ] Play 3 different songs (30s each)
- [ ] Go to Recently Played tab
- [ ] Hover over most recent song
- [ ] Click share button
- [ ] Verify share modal opens
- [ ] Copy link and verify URL

**INT4: All 4 features in sequence**
- [ ] Play an album (tracks in queue)
- [ ] Start 15-minute sleep timer
- [ ] Wait 30+ seconds (recently played tracks)
- [ ] Save queue as playlist "Full Test"
- [ ] Share a song from the queue
- [ ] Go to Recently Played - verify songs tracked
- [ ] Go to Playlists - verify "Full Test" exists
- [ ] Cancel sleep timer
- [ ] Open share modal from playlist
- [ ] All features work independently

---

## Coordination Testing: Modified Files

**COORD1: PlayerContext.tsx (modified by 2 agents)**
- [ ] Recently Played tracking works (30s rule)
- [ ] Sleep Timer pause() works
- [ ] No console errors from conflicting code
- [ ] Both features operate independently

**COORD2: JamifyFullPlayer.tsx (modified by 2 agents)**
- [ ] Settings gear icon present (Sleep Timer)
- [ ] Share button present (Share feature)
- [ ] Both buttons visible in header (no layout issues)
- [ ] Click settings - panel opens
- [ ] Click share - modal opens
- [ ] No button overlap or z-index issues

---

## Regression Testing: Phase 1 Features

**REG1: Search**
- [ ] Open search (Cmd+K or click search icon)
- [ ] Type artist name
- [ ] Results appear
- [ ] Click result - artist page loads

**REG2: Like Button**
- [ ] Click heart on a song
- [ ] Button turns green/filled
- [ ] Go to Library > Liked Songs
- [ ] Song appears in liked songs
- [ ] Click heart again - un-likes

**REG3: Library Page**
- [ ] Navigate to Library (/library)
- [ ] 4 tabs present: Songs, Artists, Albums, Recently Played
- [ ] Songs tab shows liked songs
- [ ] Recently Played tab works (tested above)

**REG4: Playlists**
- [ ] Navigate to Playlists (/playlists)
- [ ] All playlists show
- [ ] Can create new playlist
- [ ] Can add songs to playlist
- [ ] Can remove songs from playlist
- [ ] Can delete playlist

---

## Mobile Testing (if applicable)

**MOB1: Full player swipe gestures**
- [ ] Swipe up from mini player - expands
- [ ] Swipe down from full player - collapses
- [ ] Settings button accessible on mobile
- [ ] Share button accessible on mobile

**MOB2: Share - native API**
- [ ] Web Share API works on mobile
- [ ] Native share sheet appears
- [ ] Can share to apps (Messages, Notes, etc.)

**MOB3: Touch feedback**
- [ ] All buttons respond to touch
- [ ] No double-tap issues
- [ ] Modal close on backdrop tap works

---

## Performance Testing

**PERF1: LocalStorage size**
- [ ] Play 50 songs (recently played max)
- [ ] Create 10 playlists
- [ ] Check browser DevTools > Application > LocalStorage
- [ ] Verify data size reasonable (< 5MB)

**PERF2: Timer accuracy**
- [ ] Start 5-minute timer
- [ ] Use stopwatch to verify countdown accurate
- [ ] Timer should stop within 1-2 seconds of 0:00

**PERF3: No memory leaks**
- [ ] Open DevTools > Performance > Memory
- [ ] Start sleep timer
- [ ] Play songs for 5 minutes
- [ ] Stop timer
- [ ] Check for memory leaks (should not grow indefinitely)

---

## Browser Compatibility

**COMP1: Chrome/Edge**
- [ ] All features work
- [ ] No console errors

**COMP2: Firefox**
- [ ] All features work
- [ ] Web Share API may not be available (expected)

**COMP3: Safari**
- [ ] All features work
- [ ] Native share works on mobile Safari

---

## Edge Cases

**EDGE1: Rapid timer changes**
- [ ] Start 5-minute timer
- [ ] Immediately cancel
- [ ] Start 1-hour timer
- [ ] Immediately start "end of track" timer
- [ ] Verify no timer conflicts or UI glitches

**EDGE2: Share during playback transition**
- [ ] Song ending (last 5 seconds)
- [ ] Open share modal
- [ ] Song changes to next track
- [ ] Verify modal still shows correct song info
- [ ] (Modal may need to close on song change)

**EDGE3: Queue cleared during save**
- [ ] Open "Save as Playlist" modal
- [ ] (In another tab or via code) Clear the queue
- [ ] Try to save
- [ ] Verify graceful handling (no crash)

**EDGE4: LocalStorage full**
- [ ] (Difficult to test - skip unless critical)
- [ ] Fill localStorage to browser limit
- [ ] Try to save recently played or playlist
- [ ] Verify error handling (no crash)

---

## Final Checks

- [ ] No console errors throughout testing
- [ ] No TypeScript build errors
- [ ] All localStorage keys follow naming convention: `jamify_*`
- [ ] All modals use consistent dark theme (#282828)
- [ ] All buttons have hover states
- [ ] All features accessible via keyboard
- [ ] No broken links
- [ ] All icons render correctly

---

## Test Summary

**Total Test Cases:** 50+
**Features Tested:** 4 (Save Queue, Recently Played, Sleep Timer, Share)
**Integration Tests:** 4
**Regression Tests:** 4
**Edge Cases:** 4

**Pass Rate:** ____%
**Failures:** (list any failures)
**Notes:** (any observations or issues)

---

## Sign-Off

**Tested by:** _________________
**Date:** 2026-01-27
**Build Version:** _________________
**Ready for Phase 3:** [ ] Yes [ ] No
