# SEO Implementation Task Cards

**Last Updated:** 2026-01-29 (Post-Swarm Review)
**Status:** ✅ All cards reviewed and improved
**Total Estimated Time:** 46-60 hours (6-8 weeks)

This folder contains the complete SEO implementation plan broken down into actionable task cards.

---

## 📋 Task Card Structure

### Core Implementation (Cards 1-6)
- **00_MASTER_PLAN.md** - Complete SEO implementation guide (reference document)
- **CARD-1-BACKEND-SEO.md** - Magento metadata & GraphQL setup (5-7h)
- **CARD-2-FRONTEND-METADATA.md** - Next.js dynamic metadata implementation (8-10h)
- **CARD-3-STRUCTURED-DATA.md** - Schema.org JSON-LD for music content (7-9h)
- **CARD-4-IMAGE-OPTIMIZATION.md** - Next.js Image component migration (5-7h)
- **CARD-5-CORE-WEB-VITALS.md** - Performance optimization (LCP, CLS, INP) (6-8h)
- **CARD-6-SITEMAP-ROBOTS.md** - Sitemap and robots.txt for large catalog (5-6h)

### Strategic Additions (Cards 7A-C)
- **CARD-7A-VENUE-EVENT-SCHEMA.md** - Venue/local SEO with Event schema (3-4h) 🔴 HIGH ROI
- **CARD-7B-CONTENT-ENRICHMENT.md** - Content depth, FAQ schema, voice search (4-6h)
- **CARD-7C-ANALYTICS-MONITORING.md** - GA4, Search Console, monitoring (3h + 1h/month) 🔴 CRITICAL

### Review Documentation
- **IMPROVEMENTS_SUMMARY.md** - All fixes applied after swarm review
- **PERFORMANCE_OPTIMIZATION_REVIEW.md** - Deep dive on Core Web Vitals
- **SITEMAP_SCALABILITY_ANALYSIS.md** - Handling 186k products

---

## 📅 Implementation Timeline (Updated)

### Week 1: Backend Foundation
- **CARD-1**: Magento SEO fields, null safety, truncation (5-7h)
- **CARD-7C (partial)**: Set up Google Analytics 4 (1h)

### Week 2: Frontend Metadata & Analytics
- **CARD-2**: Dynamic metadata, Open Graph, manual canonical URLs (8-10h)
- **CARD-6**: Sitemap with pagination fix (5-6h)
- **CARD-7C (complete)**: Search Console setup, monitoring (2h)

### Week 3: Structured Data
- **CARD-3**: Schema.org with correct field names, @graph, formatDuration (7-9h)
- **CARD-7A**: Venue & Event schema for local SEO (3-4h)

### Week 4: Performance
- **CARD-4**: Image optimization, Archive.org domains, PWA fix (5-7h)
- **CARD-5**: Memoization, lazy loading, throttling (6-8h)

### Week 5-6: Content & Polish
- **CARD-7B**: Content enrichment, FAQ schema, related artists (4-6h)
- Testing, validation, refinement (4-6h)

**Total Time:** 46-60 hours across 6-8 weeks

---

## 🚨 Critical Fixes Applied (Post-Review)

### BLOCKER Fixes
1. ✅ **GraphQL field corrections** - Removed non-existent `canonical_url` field
2. ✅ **Sitemap timeout fix** - Changed from N+1 (2 hours) to pagination (10 seconds)
3. ✅ **Schema field names** - Updated to match actual GraphQL (song_title, song_url, etc.)
4. ✅ **Duration calculation** - Fixed decimal bug in ISO 8601 format
5. ✅ **Image optimization** - Documented current disabled state, added Archive.org domains

### Important Improvements
6. ✅ **Null safety** - Added to all PHP getters in CARD-1
7. ✅ **Word-aware truncation** - Prevent breaking words mid-string
8. ✅ **PWA cache fix** - Cache Next.js optimized images correctly
9. ✅ **@graph wrapper** - Combine multiple schemas properly
10. ✅ **Context providers** - Documented that current structure is optimal (don't consolidate)

---

## 🎯 Success Criteria

### Week 2 (After CARD-1, CARD-2, CARD-6)
- [ ] All pages have dynamic meta tags
- [ ] Sitemap submitted to Search Console (builds in <60 seconds)
- [ ] GA4 tracking basic events

### Month 1 (After all cards)
- [ ] 100+ pages indexed
- [ ] LCP < 2.5s (75th percentile)
- [ ] CLS < 0.1 (75th percentile)
- [ ] INP < 200ms (75th percentile)
- [ ] Structured data validated (no errors)
- [ ] 1,000+ organic sessions

### Month 3
- [ ] 5,000+ pages indexed (artists + albums)
- [ ] Rich results appearing in search (Music, Event snippets)
- [ ] Venue-specific queries ranking (e.g., "phish madison square garden")
- [ ] 5,000+ organic sessions/month
- [ ] Organic traffic +50% vs launch

### Month 6
- [ ] 10,000+ pages indexed
- [ ] Top 3 rankings for "{artist} live recordings"
- [ ] Featured in Google Knowledge Graph
- [ ] 20,000+ organic sessions/month
- [ ] Organic traffic +100% vs launch

---

## 🔴 High-Priority Cards (Launch Blockers)

**Must complete before public launch:**
1. **CARD-1** - Backend SEO (no frontend work without this)
2. **CARD-2** - Frontend metadata (required for indexing)
3. **CARD-6** - Sitemap (search engines need this to discover pages)
4. **CARD-7C** - Analytics (can't improve what you don't measure)

---

## 🟡 Medium-Priority Cards (Phase 1.5)

**Complete within 2 weeks of launch:**
5. **CARD-3** - Structured data (rich results)
6. **CARD-7A** - Venue schema (highest ROI addition)
7. **CARD-4** - Image optimization (Core Web Vitals)

---

## 🟢 Lower-Priority Cards (Phase 2)

**Complete within 1-2 months:**
8. **CARD-5** - Advanced Core Web Vitals tuning
9. **CARD-7B** - Content enrichment (editorial effort)

---

## 📊 Performance Impact Estimates

### Current Plan (Cards 1-6 only)
- Lighthouse Score: 70 → 85-90
- LCP: 4-6s → 2.5-3s
- Organic Traffic: Baseline → +30-50%

### With Strategic Additions (Cards 7A-C)
- Lighthouse Score: 70 → 90-95
- LCP: 4-6s → 1.5-2.5s
- Organic Traffic: Baseline → +80-100%

**Key Driver:** Venue/local SEO (CARD-7A) unlocks huge jam band audience segment.

---

## 🗺️ Catalog Scale Considerations

**Your Current Stats:**
- 186,461 total products (tracks)
- ~35,420 albums/shows
- 1 configured artist (room to grow)

**Sitemap Strategy:**
- ✅ Include: Homepage, artists, albums (~35k URLs)
- ❌ Exclude: Individual tracks (186k URLs - too many)
- Use ISR (Incremental Static Regeneration) for hourly updates

**Build Performance:**
- Original plan: ~2 hours (TIMEOUT)
- Fixed plan: ~10 seconds (720x faster)

---

## 📝 Documentation Index

| Document | Purpose |
|----------|---------|
| README.md | This file - overview and timeline |
| 00_MASTER_PLAN.md | Complete technical reference |
| CARD-1 through CARD-7C | Individual implementation tasks |
| IMPROVEMENTS_SUMMARY.md | All swarm review findings |
| PERFORMANCE_OPTIMIZATION_REVIEW.md | Deep dive on CARD-4 & CARD-5 |
| SITEMAP_SCALABILITY_ANALYSIS.md | Handling 186k products |
| MONTHLY_SEO_AUDIT.md | Ongoing maintenance checklist |

---

## 🚀 Quick Start

**For Developers:**
1. Start with CARD-1 (backend work required first)
2. Move to CARD-2 (frontend metadata)
3. Submit sitemap (CARD-6) as soon as CARD-2 completes
4. Set up analytics (CARD-7C) in parallel with CARD-3

**For Project Managers:**
- Review time estimates (realistic: 46-60 hours)
- Prioritize CARD-7A (venue schema) - highest ROI
- Budget 1 hour/month for ongoing SEO monitoring

---

## ⚠️ Common Pitfalls to Avoid

1. **Don't skip CARD-1** - Frontend needs backend SEO fields
2. **Don't include all tracks in sitemap** - Will timeout and exceed Google's limits
3. **Don't consolidate context providers** - Current structure is optimal
4. **Don't forget analytics** - Set up on day 1, not as afterthought
5. **Don't ignore venue culture** - This is unique to live music niche

---

**Questions?** See individual card files for detailed implementation steps and troubleshooting.
