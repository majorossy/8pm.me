# SEO Keyword Research Documentation

**Created:** 2026-01-29
**Purpose:** Comprehensive keyword strategy for 8PM Archive
**Status:** 📝 Research Phase Complete → Ready for Implementation

---

## Documentation Structure

```
seo-implementation/
├── README.md                    # Main SEO cards overview
├── KEYWORD_RESEARCH_README.md   # This file - keyword research navigation
├── KEYWORD_RESEARCH.md          # Master keyword strategy document
└── keywords/
    ├── _TEMPLATE.md             # Template for new artist research
    ├── phish.md                 # Phish-specific keywords (COMPLETE)
    ├── grateful-dead.md         # (Future)
    ├── sts9.md                  # (Future)
    └── [35+ more artists]       # (Future expansion)
```

---

## Document Overview

### 1. KEYWORD_RESEARCH.md (Master Document)
**Purpose:** Central SEO strategy and title/description templates
**Audience:** Developers implementing CARD-1 and CARD-2
**Contains:**
- ✅ Title templates for artist/show/track pages
- ✅ Meta description templates
- ✅ Competitor analysis (Archive.org, LivePhish, Phish.in)
- ✅ Unique selling points (free streaming, no signup)
- ✅ Rich data integration guide (using GraphQL fields)
- ✅ Implementation checklist for CARD-1 and CARD-2

**When to Use:**
- Implementing backend meta_title/meta_description generators (CARD-1)
- Implementing frontend `generateMetadata()` functions (CARD-2)
- Writing product descriptions or artist bios
- Creating FAQ schema (CARD-7B)

---

### 2. keywords/phish.md (Artist-Specific Deep Dive)
**Purpose:** Comprehensive Phish keyword research
**Audience:** SEO specialists, content writers, developers
**Contains:**
- ✅ High-volume keywords (1,000+ searches/month)
- ✅ Famous shows to prioritize (Baker's Dozen, Big Cypress, etc.)
- ✅ Song-specific keywords (Tweezer, You Enjoy Myself, etc.)
- ✅ Venue-specific keywords (Madison Square Garden, Red Rocks, etc.)
- ✅ Seasonal trends (Halloween, NYE, summer tour)
- ✅ Phish-specific terminology (taper section, soundboard, etc.)

**When to Use:**
- Testing SEO implementation with Phish data
- Prioritizing which Phish shows to import first
- Writing Phish artist page content
- Creating Phish-specific metadata

---

### 3. keywords/_TEMPLATE.md (Expansion Template)
**Purpose:** Standardized template for researching new artists
**Audience:** SEO team expanding to new artists
**Contains:**
- ✅ Pre-formatted sections for all keyword types
- ✅ Placeholders for artist-specific data
- ✅ Implementation checklist template

**How to Use:**
```bash
# Copy template
cp keywords/_TEMPLATE.md keywords/grateful-dead.md

# Fill in artist-specific data
# - Replace {Artist Name} with "Grateful Dead"
# - Research famous shows (Cornell '77, etc.)
# - Find venue-specific keywords (Winterland, etc.)
# - Document song keywords (Dark Star, etc.)
```

---

## Implementation Roadmap

### Phase 1: Keyword Research ✅ COMPLETE
**What Was Done:**
- ✅ Created master keyword strategy (`KEYWORD_RESEARCH.md`)
- ✅ Deep-dive Phish research (`keywords/phish.md`)
- ✅ Template for future artists (`keywords/_TEMPLATE.md`)
- ✅ Competitor analysis (Archive.org, LivePhish, Phish.in)
- ✅ Title/description templates ready for CARD-1 and CARD-2

**Deliverables:**
- 3 comprehensive markdown documents
- Expandable structure for 35+ artists
- Ready-to-use title/description templates

---

### Phase 2: Backend Implementation (CARD-1) ⏳ NEXT
**What to Do:**
Implement meta_title and meta_description generators in Magento backend.

**Reference These Documents:**
- `KEYWORD_RESEARCH.md` - Title/description templates
- `keywords/phish.md` - Test with Phish data

**Backend Tasks:**
1. Add `meta_title` and `meta_description` fields to category attributes
2. Create generator for artist pages using template:
   ```
   {Artist} Live Recordings & Concert Downloads | 8PM Archive
   ```
3. Create generator for show/album pages using template:
   ```
   {Artist} Live at {Venue} ({Date}) - Soundboard Recording | 8PM
   ```
4. Pull rich data from GraphQL:
   - `band_total_shows` for artist descriptions
   - `show_venue`, `show_date` for show titles
   - `archive_downloads` for show descriptions
5. Ensure titles stay under 60 characters
6. Ensure descriptions stay under 160 characters

**See:** `CARD-1-BACKEND-SEO.md`

---

### Phase 3: Frontend Implementation (CARD-2) ⏳ AFTER CARD-1
**What to Do:**
Implement `generateMetadata()` functions in Next.js frontend.

**Reference These Documents:**
- `KEYWORD_RESEARCH.md` - Fallback templates
- `keywords/phish.md` - Test with Phish pages

**Frontend Tasks:**
1. Add `generateMetadata()` to `app/artists/[slug]/page.tsx`
2. Add `generateMetadata()` to `app/artists/[slug]/album/[albumSlug]/page.tsx`
3. Add `generateMetadata()` to track pages
4. Query `meta_title` and `meta_description` from GraphQL
5. Add fallbacks if backend fields are empty
6. Implement Open Graph tags (og:title, og:description)
7. Implement Twitter Cards (twitter:title, twitter:description)
8. Set canonical URLs properly

**See:** `CARD-2-FRONTEND-METADATA.md`

---

### Phase 4: Testing & Validation ⏳ AFTER CARD-2
**What to Do:**
Verify SEO implementation works and tracks performance.

**Testing Checklist:**
- [ ] Titles display correctly in browser tabs
- [ ] Titles under 60 characters (not truncated in Google)
- [ ] Descriptions under 160 characters
- [ ] Open Graph tags present (`curl -I` or browser inspector)
- [ ] Twitter Cards validate (Twitter Card Validator)
- [ ] Canonical URLs correct
- [ ] Rich data used properly (venue, date, downloads)

**Performance Tracking:**
- [ ] Google Search Console setup
- [ ] Monitor impressions for "phish live recordings"
- [ ] Monitor CTR for venue-specific keywords
- [ ] Track rankings for famous shows (Baker's Dozen, etc.)
- [ ] Analyze which keywords drive traffic

**See:** `KEYWORD_RESEARCH.md` (Implementation Checklist section)

---

### Phase 5: Expansion ⏳ ONGOING
**What to Do:**
Expand keyword research to more artists (Grateful Dead, STS9, etc.).

**How to Expand:**
1. Copy `keywords/_TEMPLATE.md` to `keywords/{artist-slug}.md`
2. Research artist-specific keywords:
   - Famous shows (e.g., Cornell '77 for Grateful Dead)
   - Famous venues (e.g., Winterland for Grateful Dead)
   - Song keywords (e.g., Dark Star for Grateful Dead)
   - Seasonal trends (e.g., New Year's for Grateful Dead)
3. Update `KEYWORD_RESEARCH.md` with artist link
4. Test backend/frontend implementation with new artist
5. Monitor Search Console for new keyword performance

**Priority Order:**
1. ✅ Phish (COMPLETE)
2. ⏳ Grateful Dead (highest search volume after Phish)
3. ⏳ STS9 (Sound Tribe Sector 9)
4. ⏳ Widespread Panic
5. ⏳ [Remaining 31+ artists]

---

## Quick Reference

### For Backend Developers (CARD-1)
**Read:**
1. `KEYWORD_RESEARCH.md` - Sections: "Title Templates", "Meta Description Templates"
2. `keywords/phish.md` - Section: "Title Examples", "Meta Description Examples"

**Use:**
- Artist page title: `{Artist} Live Recordings & Concert Downloads | 8PM Archive`
- Show page title: `{Artist} Live at {Venue} ({Date}) - Soundboard Recording | 8PM`
- Keep titles under 60 characters
- Pull `band_total_shows`, `show_venue`, `show_date` from database

---

### For Frontend Developers (CARD-2)
**Read:**
1. `KEYWORD_RESEARCH.md` - Sections: "Title Templates", "Implementation Checklist"
2. `keywords/phish.md` - Section: "Implementation Checklist (Phish)"

**Use:**
- Query `meta_title` and `meta_description` from GraphQL
- Add fallback: `{Artist} Live Recordings | 8PM` if backend empty
- Implement Open Graph and Twitter Cards
- Verify canonical URLs

---

### For SEO Specialists
**Read:**
1. `KEYWORD_RESEARCH.md` - All sections
2. `keywords/phish.md` - All sections
3. `keywords/_TEMPLATE.md` - For expanding to new artists

**Use:**
- Competitor analysis insights
- Keyword prioritization matrix
- Seasonal trend data
- Famous show prioritization

---

### For Content Writers
**Read:**
1. `KEYWORD_RESEARCH.md` - Sections: "Unique Selling Points", "What NOT to Use"
2. `keywords/phish.md` - Sections: "Quality Indicators", "Content Opportunities"

**Use:**
- Artist page descriptions
- Show descriptions
- FAQ schema content (CARD-7B)
- Terminology guidelines

---

## Key Insights Summary

### 1. Users Search for Specificity
**Pattern:** `{artist} + {venue} + {date}` outperforms generic `{artist} concerts`

**Example:** "phish madison square garden 1997-12-30" has higher intent than "phish concerts"

**Action:** Always include venue + date in show titles

---

### 2. "Soundboard" Is a Quality Signal
**Pattern:** "soundboard" searches have 3x volume of "audience" searches

**Action:** Prioritize "soundboard recording" in titles when applicable

---

### 3. "Free" Is a Competitive Advantage
**Pattern:** Users add "free" to filter out LivePhish.com (paid service)

**Action:** Emphasize "free streaming, no signup" in descriptions

---

### 4. Long-Tail Keywords Have Higher Intent
**Pattern:** Specific show searches convert better than generic artist searches

**Example:** "phish baker's dozen 2017-07-21" = high intent (fan looking for specific show)

**Action:** Optimize show pages for long-tail venue + date combinations

---

## Success Metrics (Post-Implementation)

### 30 Days Post-Launch
**Track in Google Search Console:**
- [ ] Impressions for "{artist} live recordings" (target: 1,000+)
- [ ] Average position for primary keywords (target: top 20)
- [ ] CTR for artist pages (target: 2%+)

### 90 Days Post-Launch
**Track in Google Search Console:**
- [ ] Impressions for venue-specific keywords (target: 5,000+)
- [ ] Average position for famous shows (target: top 10)
- [ ] CTR for show pages (target: 3%+)
- [ ] Total organic traffic from Google (target: 10,000+ sessions/month)

### 180 Days Post-Launch
**Track in Google Search Console:**
- [ ] Ranking #1 for long-tail show keywords
- [ ] Competing with Archive.org and Phish.in for top 3
- [ ] Organic traffic growing 20%+ month-over-month

---

## Related Documentation

### SEO Cards
- **CARD-1:** Backend meta field generation (uses title/description templates)
- **CARD-2:** Frontend metadata implementation (uses keyword research)
- **CARD-3:** Structured data (JSON-LD)
- **CARD-7B:** FAQ schema (uses content opportunities)

### Technical Documentation
- GraphQL schema: `../../frontend/lib/api.ts`
- Category attributes: Magento Admin > Catalog > Categories
- Product attributes: Magento Admin > Catalog > Products

---

## Questions?

### "Which document should I read first?"
**Answer:** Start with `KEYWORD_RESEARCH.md` - it has all templates and strategy.

### "I'm implementing CARD-1, what do I need?"
**Answer:** Read `KEYWORD_RESEARCH.md` sections: "Title Templates" and "Meta Description Templates". Reference `keywords/phish.md` for test data.

### "I'm adding a new artist, what do I do?"
**Answer:** Copy `keywords/_TEMPLATE.md` to `keywords/{artist-slug}.md`, fill in artist-specific data, and add link to `KEYWORD_RESEARCH.md`.

### "How do I know if SEO is working?"
**Answer:** After implementing CARD-1 and CARD-2, set up Google Search Console and monitor impressions/CTR for "{artist} live recordings" and venue-specific keywords.

---

**Document Status:** ✅ COMPLETE
**Last Updated:** 2026-01-29
**Owner:** SEO Implementation Team
**Next Phase:** CARD-1 Backend Implementation
