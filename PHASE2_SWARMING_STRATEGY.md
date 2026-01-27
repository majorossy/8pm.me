# Phase 2 Swarming Strategy - Parallel Development Plan

**Goal:** Implement 6 Phase 2 features in parallel using multiple Claude agents
**Challenge:** Minimize merge conflicts and coordinate file changes
**Estimated Sequential Time:** 30-35 hours → **Swarmed Time:** 8-10 hours (3-4x speedup)

---

## File Conflict Analysis

### Files Modified by Multiple Features:

| File | Modified By | Conflict Risk |
|------|-------------|---------------|
| `JamifyFullPlayer.tsx` | Sleep Timer, Share, Lyrics | **HIGH** ⚠️ |
| `PlayerContext.tsx` | Recently Played, Sleep Timer | **MEDIUM** ⚠️ |
| `SongCard.tsx` | Share | LOW |
| `TrackCard.tsx` | Share | LOW |
| `Queue.tsx` | Save Queue | LOW |
| `app/library/page.tsx` | Recently Played | LOW |
| `app/search/page.tsx` | Search Suggestions | LOW |

### Conflict-Free Features (Can Run 100% Parallel):

✅ **Save Queue as Playlist** - Only touches `Queue.tsx`
✅ **Search Suggestions** - Only touches search files
✅ **Lyrics Display** - Creates new files (`LyricsPanel.tsx`, `useLyrics.ts`, etc.)

### Conflicting Features (Need Coordination):

⚠️ **Recently Played + Sleep Timer** - Both modify `PlayerContext.tsx`
⚠️ **Sleep Timer + Share + Lyrics** - All modify `JamifyFullPlayer.tsx`

---

## Recommended Swarming Strategy

### 🚀 Option 1: Three Parallel Waves (RECOMMENDED)

**Best for:** Minimizing conflicts while maximizing parallelism

#### Wave 1 (3 agents in parallel - 8-10h)
Launch these simultaneously - **zero file conflicts:**

**Agent A:** Save Queue as Playlist (4-5h)
- Files: `components/Queue.tsx`, uses `PlaylistContext`
- Output: PR branch `feature/save-queue`

**Agent B:** Search Suggestions (4-6h)
- Files: `app/search/page.tsx`, `JamifySearchOverlay.tsx`, new API route
- Output: PR branch `feature/search-suggestions`

**Agent C:** Lyrics Display (8-10h)
- Files: NEW `LyricsPanel.tsx`, `useLyrics.ts`, `app/api/lyrics/route.ts`
- Output: PR branch `feature/lyrics` (creates files, doesn't modify existing)

#### Wave 2 (2 agents in parallel - 6-8h)
After Wave 1 merges, launch these:

**Agent D:** Recently Played (6-8h)
- Files: NEW `RecentlyPlayedContext.tsx`, modify `PlayerContext.tsx` (add tracking), modify `app/library/page.tsx`
- Output: PR branch `feature/recently-played`

**Agent E:** Sleep Timer (3-4h)
- Files: NEW `useSleepTimer.ts`, modify `PlayerContext.tsx` (add pause hook)
- Output: PR branch `feature/sleep-timer`
- **Coordinate:** Agent E waits for Agent D to finish `PlayerContext.tsx` changes

#### Wave 3 (1 agent - 4-5h)
After Waves 1 & 2 merge:

**Agent F:** Share Functionality (4-5h)
- Files: NEW `ShareModal.tsx`, `useShare.ts`, modify `JamifyFullPlayer.tsx`, `SongCard.tsx`, `TrackCard.tsx`
- Output: PR branch `feature/share`

#### Integration (1h)
- Integrate Lyrics into `JamifyFullPlayer.tsx` (Sleep Timer + Share already there)
- Final merge and testing

**Total Time:** 10-11 hours (with 3 concurrent agents max)

---

### 🔥 Option 2: Maximum Parallelism (AGGRESSIVE)

**Best for:** Speed over safety (more merge conflicts)

Launch **all 6 agents simultaneously** with coordination plan:

**Independent Group (no conflicts):**
- Agent A: Save Queue as Playlist
- Agent B: Search Suggestions
- Agent C: Lyrics Display

**Coordinated Group (file conflicts):**
- Agent D: Recently Played → Modify `PlayerContext.tsx` section A
- Agent E: Sleep Timer → Modify `PlayerContext.tsx` section B, `JamifyFullPlayer.tsx` section A
- Agent F: Share → Modify `JamifyFullPlayer.tsx` section B, `SongCard.tsx`

**Coordination Protocol:**
1. Each agent creates feature branch
2. Each agent documents their changes in `PlayerContext.tsx` and `JamifyFullPlayer.tsx`
3. You manually merge in order: D → E → F (or use git merge)

**Total Time:** 8-10 hours (all parallel, 1-2h merge)

**Risk:** Medium-high merge conflicts

---

### 🎯 Option 3: Smart Grouping (BALANCED)

**Best for:** Balance of speed and safety

Group features by integration points:

#### Group 1 (2 agents parallel - 8-10h)
**Theme:** Playback History & Management

**Agent A:** Recently Played + Save Queue as Playlist (10-13h)
- Both are about tracking/saving what you've played
- Modify: `PlayerContext.tsx`, `Queue.tsx`, `app/library/page.tsx`
- Single coordinated implementation

**Agent B:** Search Suggestions (4-6h)
- Completely independent
- Modify: Search files only

#### Group 2 (2 agents parallel - 8-10h)
**Theme:** Player Enhancements

**Agent C:** Sleep Timer + Share (7-9h)
- Both add controls to player
- Coordinate changes to `JamifyFullPlayer.tsx`
- Agent C handles both features together

**Agent D:** Lyrics Display (8-10h)
- Creates new components
- Integrates into player after Agent C finishes

**Total Time:** 8-10 hours + 2h integration = 10-12 hours

---

## Detailed Swarming Instructions

### Wave 1 Prompts (Copy-paste for agents):

**Agent A - Save Queue as Playlist:**
```
Implement "Save Queue as Playlist" feature for Jamify music app.

Requirements:
- Add "Save as Playlist" button to Queue drawer (components/Queue.tsx)
- When clicked, show modal to name new playlist
- Copy all songs from current queue.tracks + queue.upNext to new playlist
- Use existing PlaylistContext.createPlaylist() and addToPlaylist()
- Show success feedback
- Files: components/Queue.tsx only

Context files to read:
- context/PlaylistContext.tsx (already exists)
- context/QueueContext.tsx (already exists)
- components/Queue.tsx (modify this)

Deliverable: Working "Save Queue" button that creates playlists from current queue
```

**Agent B - Search Suggestions:**
```
Implement search autocomplete/suggestions for Jamify music app.

Requirements:
- Show dropdown suggestions as user types in search box
- Display recent searches (already exists in useRecentSearches hook)
- Add trending/popular artist suggestions (top 5 artists by song count)
- Debounce: 150ms for suggestions, 300ms for full search
- Keyboard navigation (up/down arrows, enter to select)

Files to modify:
- app/search/page.tsx
- components/JamifySearchOverlay.tsx

Optional: Create app/api/search/suggestions/route.ts for backend suggestions

Deliverable: Working autocomplete dropdown in search
```

**Agent C - Lyrics Display:**
```
Implement lyrics display feature for Jamify music app.

Requirements:
- Create LyricsPanel component that shows song lyrics
- Try to get lyrics from Archive.org metadata first (song.notes field)
- If not available, integrate Genius API (free tier)
- Add lyrics toggle button in JamifyFullPlayer (mobile full-screen player)
- Lyrics panel slides in from bottom or shows as overlay
- Cache lyrics in localStorage to avoid repeated API calls

Files to create:
- components/LyricsPanel.tsx
- hooks/useLyrics.ts
- app/api/lyrics/route.ts (Genius API proxy)

Files to read for context:
- components/JamifyFullPlayer.tsx (add button here)
- context/PlayerContext.tsx (current song state)

Deliverable: Working lyrics display for current song
```

---

### Wave 2 Prompts (After Wave 1 merges):

**Agent D - Recently Played:**
```
Implement "Recently Played" tracking for Jamify music app.

Requirements:
- Create RecentlyPlayedContext to track playback history
- Track song as "played" after 30 seconds of playback
- Store last 50 songs in localStorage (jamify_recently_played)
- Add "Recently Played" tab to Library page
- Show songs in reverse chronological order (newest first)
- Include: song title, artist, album art, "played X mins ago" timestamp

Files to create:
- context/RecentlyPlayedContext.tsx

Files to modify:
- context/PlayerContext.tsx (add tracking on audio timeupdate)
- app/library/page.tsx (add new tab)
- components/ClientLayout.tsx (add RecentlyPlayedProvider)

Deliverable: Working "Recently Played" tab in Library
```

**Agent E - Sleep Timer:**
```
Implement sleep timer feature for Jamify music app.

Requirements:
- Create useSleepTimer hook with presets: 5min, 15min, 30min, 1hr, "end of current track"
- Settings panel in JamifyFullPlayer (mobile) - add gear icon button
- Countdown display in player when timer active
- Auto-pause playback when timer expires
- "5 min remaining" notification
- Cancel timer button

Files to create:
- hooks/useSleepTimer.ts

Files to modify:
- components/JamifyFullPlayer.tsx (add settings panel)
- context/PlayerContext.tsx (add pause hook for timer)

Coordinate with Recently Played agent on PlayerContext.tsx changes.

Deliverable: Working sleep timer in full player
```

---

### Wave 3 Prompt:

**Agent F - Share Functionality:**
```
Implement share functionality for Jamify music app.

Requirements:
- Share button in JamifyFullPlayer (mobile)
- Share button on hover in SongCard and TrackCard
- Web Share API for mobile (native share sheet)
- Fallback: Copy to clipboard on desktop
- Share URLs:
  - Song: /artists/{slug}/album/{album} (with note: "Check out this track: {title}")
  - Artist: /artists/{slug}
  - Album: /artists/{slug}/album/{album}
  - Playlist: /playlists/{id}
- ShareModal component with social media icons (optional)

Files to create:
- components/ShareModal.tsx
- hooks/useShare.ts

Files to modify:
- components/JamifyFullPlayer.tsx (add share button)
- components/SongCard.tsx (add share button on hover)
- components/TrackCard.tsx (add share button on hover)

Deliverable: Working share functionality across the app
```

---

## Execution Plan

### Parallel Launch Commands:

```bash
# Terminal 1 - Save Queue as Playlist
claude --session "phase2-save-queue" < agent-a-prompt.txt

# Terminal 2 - Search Suggestions
claude --session "phase2-search" < agent-b-prompt.txt

# Terminal 3 - Lyrics Display
claude --session "phase2-lyrics" < agent-c-prompt.txt
```

Or launch all via Task tool in single message (parallel):

```
Launch 3 agents in parallel for Phase 2 Wave 1:
- Agent A: Save Queue as Playlist
- Agent B: Search Suggestions
- Agent C: Lyrics Display
```

---

## Merge Strategy

### After Each Wave:

1. **Review each agent's PR/branch**
2. **Test individually:** Does the feature work in isolation?
3. **Merge in order:**
   - Wave 1: Merge all 3 (no conflicts)
   - Wave 2: Merge Recently Played first, then Sleep Timer (coordinate PlayerContext.tsx)
   - Wave 3: Merge Share last

4. **Integration test:** All features work together?

### Handling Conflicts:

**If PlayerContext.tsx conflicts (Recently Played vs Sleep Timer):**
```typescript
// Recently Played adds:
useEffect(() => {
  if (currentTime > 30 && !hasTrackedPlay) {
    trackRecentlyPlayed(currentSong);
  }
}, [currentTime]);

// Sleep Timer adds:
useEffect(() => {
  if (sleepTimerExpired) {
    pause();
  }
}, [sleepTimerExpired]);

// ✅ No conflict - different hooks!
```

**If JamifyFullPlayer.tsx conflicts (3 features):**
- Sleep Timer: Adds settings gear icon (top right)
- Share: Adds share button (top right, next to settings)
- Lyrics: Adds lyrics button (bottom, near progress bar)

**Coordinate:** All agents add to different sections, easy manual merge.

---

## Dependency Graph

```
Wave 1 (Parallel - No Dependencies)
├── Save Queue ─────────────────┐
├── Search Suggestions ─────────┤──> Merge Wave 1
└── Lyrics Display ─────────────┘

Wave 2 (Parallel - Coordinate PlayerContext)
├── Recently Played ────────────┐
└── Sleep Timer ────────────────┤──> Merge Wave 2
                                 │
Wave 3 (After Waves 1 & 2)      │
└── Share Functionality ────────┴──> Final Integration
```

---

## Timeline with Swarming

### Sequential (Current Plan):
- Feature 1: 6-8h
- Feature 2: 4-5h
- Feature 3: 4-6h
- Feature 4: 3-4h
- Feature 5: 4-5h
- Feature 6: 8-10h
**Total: 30-35 hours**

### Swarmed (Parallel):
- **Wave 1** (3 agents parallel): 8-10h (longest task)
- **Wave 2** (2 agents parallel): 6-8h (longest task)
- **Wave 3** (1 agent): 4-5h
- **Integration:** 2h
**Total: 20-25 hours elapsed time** (10-15h saved!)

---

## Risk Mitigation

### Low Risk (Independent):
✅ Save Queue as Playlist
✅ Search Suggestions
✅ Lyrics Display

**Strategy:** Launch in parallel, merge independently

### Medium Risk (Same File):
⚠️ Recently Played + Sleep Timer (PlayerContext.tsx)

**Strategy:**
- Agent D modifies PlayerContext lines 100-120 (recently played tracking)
- Agent E modifies PlayerContext lines 200-220 (sleep timer logic)
- **Coordinate:** Both agents read the file first, add separate hooks
- Manual merge: Trivial (different sections)

### High Risk (Same File, Same Section):
⚠️⚠️ Sleep Timer + Share + Lyrics (all add to JamifyFullPlayer.tsx header)

**Strategy:**
- **Sleep Timer:** Adds settings gear icon (top-right)
- **Share:** Adds share button (top-right, after settings)
- **Lyrics:** Adds lyrics button (bottom section, near progress bar)

**Coordinate:** Define exact locations:
```tsx
{/* Top right buttons */}
<div className="flex gap-2">
  <button>{/* Settings - Sleep Timer */}</button>
  <button>{/* Share */}</button>
  <button>{/* Close */}</button>
</div>

{/* Bottom section */}
<div>
  <button>{/* Lyrics toggle */}</button>
</div>
```

---

## Implementation Commands

### Launch Wave 1 (Now):

```typescript
// In Claude Code CLI - send single message with 3 Task calls:

Task 1: Save Queue as Playlist
Task 2: Search Suggestions
Task 3: Lyrics Display

// All launched in parallel!
```

### Monitor Progress:

```bash
# Check agent outputs:
tail -f /private/tmp/claude/.../tasks/agent-a.output
tail -f /private/tmp/claude/.../tasks/agent-b.output
tail -f /private/tmp/claude/.../tasks/agent-c.output
```

### Merge Wave 1:

```bash
# After all 3 agents complete:
git checkout main
git pull origin feature/save-queue
git pull origin feature/search-suggestions
git pull origin feature/lyrics

# Test together
npm run dev -- -p 3001
# Manual QA all 3 features
```

---

## Success Criteria

### After Wave 1:
- ✅ "Save Queue" button in Queue drawer works
- ✅ Search shows suggestions dropdown
- ✅ Lyrics panel shows for current song
- ✅ No merge conflicts
- ✅ All 3 features work independently

### After Wave 2:
- ✅ "Recently Played" tab in Library shows history
- ✅ Sleep timer in full player works
- ✅ PlayerContext.tsx changes don't conflict

### After Wave 3:
- ✅ Share button in player and song cards
- ✅ Web Share API works on mobile
- ✅ Copy link works on desktop

### Final Integration:
- ✅ All 6 features work together
- ✅ No regressions in Phase 1 features
- ✅ Clean code, no dead theme code

---

## Alternative: Single Multi-Feature Agent

Instead of multiple agents, one agent implements all features sequentially but uses the parallel task structure:

**Prompt:**
```
Implement all 6 Phase 2 features for Jamify:

1. Recently Played (context + library tab)
2. Save Queue as Playlist (button in Queue.tsx)
3. Search Suggestions (autocomplete dropdown)
4. Sleep Timer (useSleepTimer hook + UI)
5. Share Functionality (ShareModal + buttons)
6. Lyrics Display (LyricsPanel + API)

Work on conflict-free features first:
- Start with: Save Queue, Search Suggestions, Lyrics
- Then: Recently Played, Sleep Timer
- Finally: Share (integrates with all)

Coordinate changes to shared files (PlayerContext, JamifyFullPlayer).
```

**Pro:** Single agent, easier coordination
**Con:** Slower (sequential), 30-35 hours

---

## My Recommendation

**Use Option 1 (Three Waves)** because:

1. ✅ **3x speedup** (10h vs 30h)
2. ✅ **Low conflict risk** (only 2 files have conflicts)
3. ✅ **Easy coordination** (clear file ownership)
4. ✅ **Testable milestones** (test after each wave)
5. ✅ **Rollback safety** (if Wave 1 fails, Wave 2 doesn't start)

**Start with Wave 1 NOW:**
- 3 agents in parallel
- Zero conflicts
- 8-10 hours to completion
- Deliver 3 solid features

**Want me to launch Wave 1?** I can start all 3 agents in a single message.
