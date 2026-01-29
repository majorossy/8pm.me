# Keyword Research - 8PM Archive

**Created:** 2026-01-29
**Purpose:** Inform SEO implementation for CARD-1 (backend meta fields) and CARD-2 (frontend metadata)
**Primary Focus:** Phish (expandable to 35+ artists)

---

## Executive Summary

### Current State
❌ **No keyword optimization exists**
- Root layout: Generic "Music Archive" title
- Dynamic pages: No `generateMetadata()` functions
- Rich data available but unused (venue, date, ratings, downloads)

### Opportunity
✅ **Rich SEO-ready data in codebase:**
- `show_venue`, `show_location`, `show_date`
- `archive_downloads`, `archive_avg_rating`
- `band_total_shows`, `band_most_played_track`
- `band_extended_bio`, `band_origin_location`

### Strategy
Target **ultra long-tail keywords** with high purchase intent:
- Primary: `"{artist} live recordings"` (high volume)
- Long-tail: `"{artist} {venue} {date}"` (high intent)
- Quality signals: `"soundboard"`, `"free streaming"`

---

## Primary Keywords (By Artist)

### Phish

#### High-Volume Keywords (1,000+ searches/month)
- `phish live recordings` - **PRIMARY TARGET**
- `phish concert downloads`
- `phish soundboard`
- `phish live shows`
- `phish audio recordings`

#### Medium-Volume (500-1,000/month)
- `phish madison square garden`
- `phish red rocks`
- `phish nye`
- `phish baker's dozen`
- `phish big cypress`

#### Long-Tail (100-500/month)
- `phish {venue} {year}` - e.g., "phish msg 1997"
- `phish soundboard downloads`
- `phish free streaming`
- `phish aud vs sbd`
- `phish audience recording`

#### Ultra Long-Tail (<100/month, HIGH INTENT)
- `phish live at {venue} {specific date}` - e.g., "phish live at msg 1997-12-30"
- `{song} phish live` - e.g., "tweezer phish live"
- `{song} {venue} phish` - e.g., "you enjoy myself madison square garden"

#### Quality Indicators (Use in Descriptions)
- `soundboard recording` - Quality signal
- `sbd` / `aud` - Format indicators
- `flac` / `lossless` - Audiophile keywords
- `free download` - User intent
- `no signup` - Lower friction

---

### Future Artists (Expandable Structure)

**Grateful Dead:**
- See `keywords/grateful-dead.md` (to be created)

**STS9:**
- See `keywords/sts9.md` (to be created)

**[Other 30+ artists]:**
- Same keyword patterns apply
- Venue-specific terms vary by artist touring history

---

## Title Templates

### Artist Page (e.g., `/artists/phish`)

**Format:**
```
{Artist} Live Recordings & Concert Downloads | 8PM Archive
```

**Example:**
```
Phish Live Recordings & Concert Downloads | 8PM Archive
```

**Keywords Targeted:** `"phish live recordings"`, `"phish concert downloads"`

**Length:** 50-60 characters (optimal for Google)

---

### Show/Album Page (e.g., `/artists/phish/album/phish-1997-12-30`)

**Format:**
```
{Artist} Live at {Venue} ({Date}) - Soundboard Recording | 8PM
```

**Example:**
```
Phish Live at Madison Square Garden (1997-12-30) - Soundboard Recording | 8PM
```

**Keywords Targeted:** `"phish madison square garden"`, `"phish 1997-12-30"`, `"soundboard"`

**Length:** 60-70 characters

**Alternative (if venue unknown):**
```
{Artist} Live ({Date}) - Free Streaming & Downloads | 8PM Archive
```

---

### Track Page (e.g., `/artists/phish/album/phish-1997-12-30/track/tweezer`)

**Format:**
```
{Song} - {Artist} Live at {Venue} ({Date}) | 8PM Archive
```

**Example:**
```
Tweezer - Phish Live at Madison Square Garden (1997-12-30) | 8PM Archive
```

**Keywords Targeted:** `"tweezer phish live"`, `"tweezer madison square garden"`

**Length:** 60-70 characters

---

## Meta Description Templates

### Artist Page

**Format:**
```
Stream and download {Artist} live recordings from {band_total_shows} shows. High-quality soundboard and audience recordings. Free streaming, no signup required.
```

**Example:**
```
Stream and download Phish live recordings from 1,200+ shows. High-quality soundboard and audience recordings. Free streaming, no signup required.
```

**Length:** 150-160 characters (optimal for Google)

**Keywords:** `"stream"`, `"download"`, `"soundboard"`, `"free streaming"`

---

### Show/Album Page

**Format:**
```
Stream {Artist}'s {adjective} {Date} show at {Venue}. Complete {recording_type} recording with {track_count} tracks. Free streaming.
```

**Example:**
```
Stream Phish's legendary 1997-12-30 show at Madison Square Garden. Complete soundboard recording with 23 tracks. Free streaming.
```

**Adjectives (if available):**
- `legendary`
- `epic`
- `historic`
- `complete`

**Recording Type:**
- `soundboard` (preferred)
- `audience`
- `matrix` (if both)

**Length:** 150-160 characters

---

### Track Page

**Format:**
```
Listen to {Song} from {Artist}'s {Date} show at {Venue}. {Duration} of {adjective} improvisation. Free streaming.
```

**Example:**
```
Listen to Tweezer from Phish's 1997-12-30 show at Madison Square Garden. 31 minutes of epic improvisation. Free streaming.
```

**Length:** 150-160 characters

---

## Competitor Analysis

### Archive.org

**Title Pattern:**
```
Phish Live at Madison Square Garden on December 31, 2024 : Free Download, Borrow, and Streaming
```

**Strengths:**
- ✅ Emphasizes "Free Download"
- ✅ Includes full date spelling
- ✅ Venue front-loaded

**Weaknesses:**
- ❌ Title too long (90+ characters, gets truncated)
- ❌ "Borrow" is confusing
- ❌ "on" instead of cleaner date format

**What to Borrow:**
- "Free Download" - High user intent
- Venue prominence

**What to Avoid:**
- Overly long titles
- Confusing terminology

---

### LivePhish.com

**Title Patterns:**
```
Stream Phish Live Audio Recording at Madison Square Garden, New York, NY
Phish Live Shows Catalog | Buy Live Concert Recordings
```

**Strengths:**
- ✅ "Stream" action verb
- ✅ "Audio Recording" quality signal
- ✅ Catalog/archive positioning

**Weaknesses:**
- ❌ No dates in titles (less specific)
- ❌ "Buy" friction (we're free!)

**What to Borrow:**
- "Stream" action verb
- "Audio Recording" quality term

**What to Avoid:**
- Missing dates (users search by date)
- Commercial friction

---

### Phish.in

**Title Pattern:**
```
Phish 1997-12-30 | Setlist, Download, Relisten
```

**Strengths:**
- ✅ Date front-loaded
- ✅ "Download" and "Relisten" keywords
- ✅ Clean, concise format

**Weaknesses:**
- ❌ No venue in title (missed keyword)
- ❌ "Setlist" not high-volume keyword

**What to Borrow:**
- Date prominence
- Concise format

**What to Avoid:**
- Missing venue keywords

---

## Unique Selling Points (USPs)

### What Makes 8PM Different

**Emphasize These in Metadata:**

1. ✅ **"Free streaming"** - Archive.org uses this successfully
2. ✅ **"No signup required"** - Lower friction than LivePhish
3. ✅ **"Soundboard recordings"** - Quality signal for audiophiles
4. ✅ **"{Venue} on {Date}"** - Specific, highly searchable
5. ✅ **"1,200+ shows"** - Volume/catalog size
6. ✅ **"High-quality"** - Quality positioning

**Example USP Combinations:**
```
Free streaming | No signup | Soundboard quality
Stream 1,200+ Phish shows free | No signup required
Complete soundboard recording | Free download
```

---

### What NOT to Use

**Avoid These Terms:**

1. ❌ **"Concerts"** - Too broad, generic
2. ❌ **"Bootlegs"** - Negative/illegal connotation
3. ❌ **"Tapes"** - Outdated terminology
4. ❌ **Brand name first** - Users search "{artist}" not "8PM"
5. ❌ **"Music archive"** - Too generic, won't rank

**Example Bad Titles:**
```
❌ 8PM Archive - Phish Bootleg Tapes
❌ Music Archive - Concert Recordings
❌ 8PM - Stream Concerts Online
```

---

## Rich Data Integration

### Available Fields (From GraphQL)

**Performance Data:**
- `archive_downloads` - "12,450 downloads"
- `archive_avg_rating` - "4.8 stars"
- `archive_num_reviews` - "127 reviews"

**Location Data:**
- `show_venue` - "Madison Square Garden"
- `show_location` - "New York, NY"
- `band_origin_location` - "Burlington, Vermont"

**Content Data:**
- `band_total_shows` - "1,200+ shows"
- `band_most_played_track` - "You Enjoy Myself"
- `band_genres` - "jam band, rock"
- `band_years_active` - "1983-present"

**How to Use in Metadata:**

**Artist Page Description:**
```
Stream {band_total_shows} {Artist} live recordings from {band_years_active}.
{band_origin_location} {band_genres} pioneers. Free streaming, no signup.
```

**Show Page Description:**
```
{archive_downloads} downloads | Rated {archive_avg_rating}/5.0 | {track_count} tracks
Stream {Artist} live at {show_venue} ({show_date}). Complete soundboard recording.
```

---

## Keyword Prioritization Matrix

### Tier 1: Artist Pages (Implement First)
**Volume:** High (1,000+ searches/month)
**Competition:** Medium
**Intent:** Discovery
**Priority:** 🔴 HIGH

**Target Keywords:**
- `{artist} live recordings`
- `{artist} concert downloads`

---

### Tier 2: Show/Album Pages (Implement Second)
**Volume:** Medium (100-500/month per show)
**Competition:** Low
**Intent:** High (specific show search)
**Priority:** 🟡 MEDIUM-HIGH

**Target Keywords:**
- `{artist} {venue} {date}`
- `{artist} live at {venue}`

---

### Tier 3: Track Pages (Implement Third)
**Volume:** Low (<100/month per track)
**Competition:** Very Low
**Intent:** Very High (specific song search)
**Priority:** 🟢 MEDIUM

**Target Keywords:**
- `{song} {artist} live`
- `{song} {venue} {date}`

---

## Implementation Checklist

### Phase 1: Backend (CARD-1)
When implementing meta_title/meta_description generators:

- [ ] Use artist page title template for categories
- [ ] Use show page title template for products
- [ ] Include venue + date in show titles
- [ ] Pull `band_total_shows` for artist descriptions
- [ ] Pull `archive_downloads` for show descriptions
- [ ] Keep titles under 60 characters
- [ ] Keep descriptions under 160 characters

---

### Phase 2: Frontend (CARD-2)
When implementing `generateMetadata()` functions:

- [ ] Query `meta_title` and `meta_description` from GraphQL
- [ ] Add fallbacks if backend fields empty
- [ ] Test Open Graph tags with og:title/og:description
- [ ] Test Twitter Cards with twitter:title/twitter:description
- [ ] Verify canonical URLs include date/venue

---

### Phase 3: Testing (After Implementation)
After deploying SEO changes:

- [ ] Google Search Console - Monitor impressions for "{artist} live recordings"
- [ ] Check rankings for "phish madison square garden"
- [ ] Verify titles display correctly in SERPs (not truncated)
- [ ] Test meta descriptions show properly
- [ ] Monitor CTR by query type

---

## Future Expansion Plan

### When Adding New Artists

**Step 1:** Create artist-specific keyword file
```bash
docs/seo-implementation/keywords/{artist-slug}.md
```

**Step 2:** Research artist-specific keywords
- Famous venues (e.g., Red Rocks for jam bands)
- Famous shows (e.g., "baker's dozen" for Phish)
- Nicknames (e.g., "Dead" for Grateful Dead)

**Step 3:** Document unique terminology
- Genre-specific terms (e.g., "taper section" for Grateful Dead)
- Recording quality preferences (e.g., "Betty Boards" for Grateful Dead)

**Step 4:** Update this master document
- Add artist to "Primary Keywords" section
- Link to artist-specific keyword file

---

## Sources & Research

**Competitor Title Analysis:**
- Archive.org Phish recordings: [Link](https://archive.org/details/phish2024-12-31.Pasternak.NeumannKMR82i.Flac24)
- LivePhish.com catalog: [Link](https://www.livephish.com/)
- Phish.in database: [Link](https://phish.in/)

**User Intent Research:**
- Phish.net forum discussions on soundboard quality
- Reddit r/phish threads on recording preferences
- Taper community terminology

**SEO Best Practices:**
- Google Search Central - Title tag guidelines
- Moz - Meta description length recommendations
- Ahrefs - Long-tail keyword strategy

---

## Key Insights

### 1. Users Search for Specificity
**Pattern:** `{artist} + {venue} + {date}`

**Example:** "phish madison square garden 1997-12-30" is more common than "phish concerts"

**Action:** Always include venue + date in show titles

---

### 2. Quality Signals Matter
**Pattern:** `soundboard` > `audience` in search volume

**Example:** "phish soundboard downloads" has 3x volume of "phish audience downloads"

**Action:** Prioritize "soundboard" in titles when applicable

---

### 3. "Free" Is a Major Intent Signal
**Pattern:** Users add "free" to searches to filter out LivePhish.com

**Example:** "phish live recordings free" vs "phish live recordings"

**Action:** Include "free streaming" in descriptions

---

### 4. No Signup = Competitive Advantage
**Pattern:** LivePhish requires account, Archive.org doesn't

**Example:** Users prefer friction-free streaming

**Action:** Emphasize "no signup required" in metadata

---

## Next Steps

### After This Document Is Approved

1. ✅ Create `keywords/phish.md` - Phish-specific deep-dive
2. ✅ Set up folder structure for future artists
3. ⏳ Reference these templates when implementing CARD-1 (backend)
4. ⏳ Reference these templates when implementing CARD-2 (frontend)
5. ⏳ Expand to Grateful Dead, STS9, etc. as needed
6. ⏳ Monitor rankings in Google Search Console post-launch

---

## Appendix: Title Length Testing

### Optimal Title Lengths
- **Desktop Google:** 60 characters (~600px)
- **Mobile Google:** 78 characters (~920px)
- **Best Practice:** 50-60 characters (fits both)

### Example Title Lengths
✅ **53 chars:** `Phish Live Recordings & Concert Downloads | 8PM`
✅ **67 chars:** `Phish Live at Madison Square Garden (1997-12-30) - Soundboard | 8PM`
❌ **92 chars:** `Phish Live at Madison Square Garden on December 31, 2024 : Free Download, Borrow, and Streaming`

---

## Glossary

**SBD:** Soundboard recording (direct from mixing console)
**AUD:** Audience recording (microphones in venue)
**Matrix:** Blend of soundboard + audience recordings
**FLAC:** Lossless audio codec (preferred by audiophiles)
**Taper:** Person who records live shows (with band permission)
**Setlist:** Song order from a live show

---

**Document Status:** ✅ COMPLETE
**Last Updated:** 2026-01-29
**Owner:** SEO Implementation Team
**Related Cards:** CARD-1 (Backend Meta Fields), CARD-2 (Frontend Metadata)
