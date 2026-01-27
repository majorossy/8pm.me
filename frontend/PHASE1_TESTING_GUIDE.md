# Phase 1 Testing Guide - Manual QA Checklist

**Port:** http://localhost:3001
**Theme:** Jamify (Spotify-style)

---

## ✅ Feature 1: Search (Artists Only)

### Test Steps:

1. **Navigate to Search:**
   - URL: http://localhost:3001/search
   - OR click "Search" button in mobile bottom nav

2. **Search for "sts9":**
   - Type "sts9" in search box
   - Wait 300ms for debounce
   - ✅ Should show **STS9** artist result
   - ✅ Click artist → Navigate to `/artists/sts9`

3. **Search for "string":**
   - Type "string"
   - ✅ Should show **String Cheese Incident**

4. **Search for "grateful":**
   - Type "grateful"
   - ✅ Should show **Grateful Dead**

5. **Recent Searches:**
   - Previous searches appear as pills
   - ✅ Click pill → Runs that search again
   - ✅ "Clear all" → Removes all recent searches
   - ✅ Persists across page refresh (localStorage)

### Known Limitations:
- ⚠️ Only searches artists (not albums/tracks yet)
- ⚠️ Product search needs Magento fix (see `MAGENTO_SEARCH_FIX_PLAN.md`)

---

## ✅ Feature 2: Like Button

### Test Steps:

1. **Navigate to Artist:**
   - URL: http://localhost:3001/artists/sts9

2. **Play a Song:**
   - Hover over any song row
   - Click play button
   - Song should start playing in bottom player

3. **Like the Song:**
   - Click heart icon in bottom player (desktop)
   - OR expand full player on mobile and click heart
   - ✅ Heart turns **green/filled**
   - ✅ Shows as liked

4. **Unlike the Song:**
   - Click heart again
   - ✅ Heart turns **gray/outline**

5. **Persistence:**
   - Like a song
   - Refresh page (F5)
   - ✅ Song should still be liked (green heart)

### Test Locations:
- ✅ Desktop bottom player (Jamify 3-column layout)
- ✅ Mobile mini player (not visible there)
- ✅ Mobile full player (expand mini player)
- ✅ SongCard rows (hover to see favorite button)

---

## ✅ Feature 3: Library Page

### Test Steps:

1. **Like Some Songs First:**
   - Go to `/artists/sts9`
   - Like 3-5 different songs
   - Like songs from different artists

2. **Navigate to Library:**
   - URL: http://localhost:3001/library
   - OR click "Your Library" in mobile bottom nav

3. **Songs Tab (Default):**
   - ✅ Shows all liked songs in list
   - ✅ Shows artist name (clickable link)
   - ✅ Shows duration (desktop only)
   - ✅ Play button appears on hover (adds to queue)
   - ✅ Unlike button (green heart) removes song

4. **Artists Tab:**
   - Click "Artists" tab
   - ✅ Grid of artists with liked songs
   - ✅ Shows "X liked songs" count
   - ✅ Click artist → Navigate to artist page

5. **Albums Tab:**
   - Click "Albums" tab
   - ✅ Grid of albums with liked songs
   - ✅ Shows album cover art
   - ✅ Shows "X liked songs" count

6. **Empty States:**
   - Unlike all songs
   - ✅ Should show "No liked songs yet" message
   - ✅ Helpful text: "Tap the heart icon to save favorites"

### Mobile vs Desktop:
- ✅ Responsive grid (2 cols mobile, 3-5 cols desktop)
- ✅ Bottom spacing for player bar

---

## ✅ Feature 4: Create Playlist

### Test Steps:

1. **Navigate to Playlists:**
   - URL: http://localhost:3001/playlists

2. **Create First Playlist:**
   - ✅ Shows empty state: "No playlists yet"
   - Click "Create Playlist" button
   - ✅ Form appears with name + description inputs
   - Enter name: "My Favorites"
   - Enter description: "Test playlist" (optional)
   - Click "Create"
   - ✅ Playlist appears in grid

3. **Create Second Playlist:**
   - Click "Create Playlist" again
   - Name: "Chill Vibes"
   - ✅ Both playlists show in grid

4. **Persistence:**
   - Refresh page
   - ✅ Playlists still there (localStorage)

---

## ✅ Feature 5: Add to Playlist

### Test Steps:

1. **Go to Artist Page:**
   - URL: http://localhost:3001/artists/sts9

2. **Hover Over Song:**
   - Hover over any song row
   - ✅ Three buttons appear: Play, Heart, **Playlist icon** (new!)

3. **Click "Add to Playlist" Button:**
   - Click the playlist icon (document with + sign)
   - ✅ Modal opens with dark backdrop
   - ✅ Shows "Add to Playlist" header
   - ✅ Shows song title + artist name
   - ✅ Shows "Create new playlist" button
   - ✅ Shows list of existing playlists

4. **Add to Existing Playlist:**
   - Click on "My Favorites" playlist
   - ✅ Modal closes
   - ✅ Song added to playlist

5. **Create New Playlist Inline:**
   - Open modal again for different song
   - Click "Create new playlist"
   - ✅ Form appears in modal
   - Enter name: "Quick Test"
   - Click "Create"
   - ✅ Playlist created AND song added
   - ✅ Modal closes

6. **Verify Addition:**
   - Go to `/playlists`
   - Click "My Favorites"
   - ✅ Song appears in playlist

---

## ✅ Feature 6: Playlist Detail Page

### Test Steps:

1. **Navigate to Playlist:**
   - Go to `/playlists`
   - Click "My Favorites" playlist
   - URL: `/playlists/playlist-XXXXXX`

2. **Verify Display:**
   - ✅ Shows playlist name as title
   - ✅ Shows description (if provided)
   - ✅ Shows "X songs" count
   - ✅ Shows total duration
   - ✅ Cover art = first song's album art

3. **Play All:**
   - Click big green play button
   - ✅ Starts playing first song in playlist
   - ✅ Queue loads all playlist songs

4. **Edit Playlist:**
   - Click edit button (pencil icon)
   - ✅ Name becomes editable input
   - ✅ Description becomes editable input
   - Change name to "My Best Tracks"
   - Click "Save"
   - ✅ Changes saved
   - ✅ Updates persist on refresh

5. **Remove Song:**
   - Hover over a song
   - Click X button (remove)
   - ✅ Song removed from playlist
   - ✅ Updates immediately

6. **Delete Playlist:**
   - Click delete button (trash icon)
   - ✅ Confirmation modal appears
   - ✅ Shows "This action cannot be undone" warning
   - Click "Delete"
   - ✅ Redirects to `/playlists`
   - ✅ Playlist is gone

---

## 📊 Summary Checklist

### Core Functionality:
- [ ] Search returns artist results
- [ ] Like button works and persists
- [ ] Library page shows favorites in 3 tabs
- [ ] Can create playlists
- [ ] Can add songs to playlists
- [ ] Can view playlist details
- [ ] Can edit playlist name/description
- [ ] Can remove songs from playlists
- [ ] Can delete playlists
- [ ] All changes persist across refresh

### UI/UX:
- [ ] Smooth animations (modals fade/scale in)
- [ ] Hover states work correctly
- [ ] Empty states are helpful
- [ ] Mobile responsive
- [ ] Theme switcher still works (Jamify, Tron, Metro, etc.)

### Known Issues:
- ⚠️ Product/track search not working (Magento GraphQL error)
- ⚠️ Album pages might have issues (pre-existing)

---

## Bug Report Template

If you find issues, report with:

**What you did:**
1. Step 1
2. Step 2

**What you expected:**
- Expected behavior

**What happened:**
- Actual behavior

**Browser console errors:**
```
Paste any red errors from DevTools Console (F12)
```

---

## Next: Phase 2 Preview

After Phase 1 testing is complete, Phase 2 will add:
- Recently Played tracking
- Save Queue as Playlist
- Sleep Timer
- Lyrics Display
- Share Functionality

**Estimated:** 30-35 hours
