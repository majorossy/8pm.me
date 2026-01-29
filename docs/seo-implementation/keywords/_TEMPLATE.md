# {Artist Name} Keywords - Deep Dive

**Artist:** {Artist Name}
**Category ID:** TBD (from Magento)
**Total Shows in Archive.org:** TBD
**Active Years:** {Years Active}
**Origin:** {City, State/Country}

---

## High-Volume Keywords (1,000+ searches/month)

### Primary Target
- `{artist} live recordings` - **#1 PRIORITY**
- `{artist} concert downloads`
- `{artist} soundboard`
- `{artist} live shows`
- `{artist} audio recordings`
- `{artist} free downloads`

### Artist Variations
- `{artist} band`
- `{artist} music`
- `{artist} concerts`
- `{artist} tour`

---

## Medium-Volume Keywords (500-1,000/month)

### Venue-Specific
- `{artist} {famous venue 1}` - Why famous?
- `{artist} {famous venue 2}` - Why famous?
- `{artist} {famous venue 3}` - Why famous?

### Event-Specific
- `{artist} {famous event 1}` - What made it special?
- `{artist} {famous event 2}` - What made it special?

---

## Long-Tail Keywords (100-500/month)

### Year + Venue Combinations
- `{artist} {venue} {year}`
- `{artist} {venue} {year}`

### Quality/Format Keywords
- `{artist} soundboard downloads`
- `{artist} sbd vs aud`
- `{artist} flac downloads`
- `{artist} lossless`

---

## Ultra Long-Tail Keywords (<100/month, HIGH INTENT)

### Specific Date Searches
- `{artist} {YYYY-MM-DD}` - Why famous?
- `{artist} {YYYY-MM-DD}` - Why famous?

### Song-Specific Searches
- `{song 1} {artist} live` - Most-played song?
- `{song 2} {artist}` - Fan favorite?
- `{song 3} {artist} live`

---

## Famous Shows to Prioritize (SEO Gold)

List shows with dedicated search volume:

### Era 1 ({Decade})
- **{Date}** - {Venue} "{Nickname}" (keywords: `{keyword 1}`, `{keyword 2}`)

### Era 2 ({Decade})
- **{Date}** - {Venue} "{Nickname}" (keywords: `{keyword 1}`, `{keyword 2}`)

### Festivals/Special Events
- **{Date}** - {Event Name} (keywords: `{keyword 1}`, `{keyword 2}`)

---

## Song Keywords (Top 20 Most-Played)

List songs with independent search volume:

1. **{Song 1}** - "{keyword variant}"
2. **{Song 2}** - "{keyword variant}"
3. **{Song 3}** - "{keyword variant}"

---

## Quality Indicators & Terminology

### Recording Quality (Use in Metadata)
- `soundboard recording`
- `audience recording`
- `matrix recording`

### Audio Format Keywords
- `flac`
- `lossless`
- `24-bit`

### Artist-Specific Terms
- `{term 1}` - Definition
- `{term 2}` - Definition

---

## Seasonal & Trending Keywords

### Time-Based Patterns
- **{Month}:** `{keyword}` spikes (why?)
- **{Season}:** `{keyword}` spikes (why?)

---

## Competitor Keyword Analysis

### What Archive.org Ranks For
- {Keyword 1}
- {Keyword 2}

**Opportunity:** {How we can outrank}

---

### What {Other Competitor} Ranks For
- {Keyword 1}
- {Keyword 2}

**Opportunity:** {How we can outrank}

---

## Negative Keywords (Avoid These)

- ❌ `bootleg`
- ❌ `pirated`
- ❌ `{artist-specific term to avoid}`

---

## Title Examples ({Artist}-Specific)

### Artist Page
```
{Artist} Live Recordings & Concert Downloads | 8PM Archive
```

### Famous Show
```
{Artist} Live at {Venue} ({Date}) - {Nickname/Context} | 8PM
```

### Song-Specific
```
{Song} - {Artist} Live at {Venue} ({Date}) | 8PM
```

---

## Meta Description Examples ({Artist}-Specific)

### Artist Page
```
Stream and download {Artist} live recordings from {band_total_shows} shows ({years_active}). High-quality soundboard and audience recordings. Free streaming, no signup required.
```

### Famous Show
```
Stream {Artist}'s {adjective} {Date} show at {Venue}. {Special context}. Complete soundboard recording with {track_count} tracks. Free streaming.
```

---

## Content Opportunities (Future)

### FAQ Schema Targets (CARD-7B)
- "{Question 1}?" (Answer: ...)
- "{Question 2}?" (Answer: ...)

### Artist Page Content Ideas
- Total shows: {Number}
- Most-played song: {Song}
- Origin: {Location}
- Genres: {Genres}
- Famous venues: {List}

---

## Implementation Checklist ({Artist})

### Backend (CARD-1)
- [ ] Generate meta_title for {Artist} category using artist page template
- [ ] Pull `band_total_shows` for {Artist} description
- [ ] Generate meta_title for {Artist} products using show page template
- [ ] Include `show_venue` + `show_date` in show titles

### Frontend (CARD-2)
- [ ] Query `meta_title` and `meta_description` from GraphQL for {Artist} pages
- [ ] Add fallback: "{Artist} Live Recordings | 8PM" if backend field empty
- [ ] Test Open Graph tags
- [ ] Verify canonical URLs

### Testing (Post-Implementation)
- [ ] Google Search Console: Monitor "{artist} live recordings" impressions
- [ ] Check rankings for "{artist} {famous venue}"
- [ ] Verify famous show titles display correctly in SERPs
- [ ] Monitor CTR for venue-specific keywords

---

## Related Documents
- Main keyword research: `../KEYWORD_RESEARCH.md`
- SEO implementation: `../../API.md` (CARD-1, CARD-2)

---

**Document Status:** 📝 TEMPLATE
**Last Updated:** 2026-01-29
**Instructions:** Copy this template when researching new artists
