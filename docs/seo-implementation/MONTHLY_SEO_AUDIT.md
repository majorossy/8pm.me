# Monthly SEO Audit Checklist

**Time Required:** ~1 hour/month
**Frequency:** First Monday of each month
**Last Updated:** 2026-02-01

---

## Pre-Audit Setup

Before starting the audit, ensure you have access to:
- [ ] Google Search Console (https://search.google.com/search-console)
- [ ] Google Analytics 4 (https://analytics.google.com)
- [ ] Lighthouse CLI or Chrome DevTools
- [ ] Site access for manual testing

---

## Google Search Console (20 min)

### Coverage Report
- [ ] Check for new indexing errors
- [ ] Review "Excluded" pages (should be minimal)
- [ ] Monitor "Valid" page count (should grow monthly)
- [ ] Fix any 404s or server errors

### Core Web Vitals (10 min)
- [ ] Check LCP, CLS, INP for all page types
- [ ] Review failing URLs (if any)
- [ ] Compare to previous month (trending up or down?)
- [ ] Mobile vs Desktop comparison

### Top Queries (10 min)
- [ ] Identify new high-impression keywords
- [ ] Check click-through rates (CTR)
- [ ] Find low CTR, high impression queries (optimize meta descriptions)
- [ ] Identify ranking improvements/declines

### Rich Results (5 min)
- [ ] Check for structured data errors
- [ ] Verify MusicEvent, MusicAlbum, Breadcrumb markup detected
- [ ] Review rich result impressions (growing?)
- [ ] Test sample pages with Rich Results Test tool

---

## Google Analytics 4 Review (15 min)

### Traffic Analysis
- [ ] Compare organic traffic to previous month
- [ ] Review top landing pages
- [ ] Check bounce rate trends
- [ ] Analyze user flow through site

### Event Tracking Verification
- [ ] Verify song_play events are firing
- [ ] Check search event tracking
- [ ] Confirm share events are logged
- [ ] Review Web Vitals events (CLS, LCP, INP, FCP, TTFB)

### User Engagement
- [ ] Average session duration
- [ ] Pages per session
- [ ] Returning vs new visitors
- [ ] Device breakdown (mobile vs desktop)

---

## Lighthouse Audit (15 min)

Run automated audit:

```bash
cd frontend
npx lighthouse http://localhost:3001/artists/railroad-earth \
  --view \
  --preset=desktop \
  --only-categories=performance,seo,accessibility
```

**Check for:**
- [ ] Performance score >90
- [ ] SEO score 100
- [ ] Accessibility score >95
- [ ] No new errors or warnings
- [ ] Image optimization issues
- [ ] Render-blocking resources

---

## Technical SEO Checks (10 min)

- [ ] Sitemap is accessible at /sitemap.xml
- [ ] Robots.txt is configured correctly
- [ ] SSL certificate is valid
- [ ] No mixed content warnings
- [ ] Canonical tags are correct
- [ ] Mobile rendering works properly

### Quick Technical Tests
```bash
# Test sitemap (replace with production URL when live)
curl -I https://8pm.fm/sitemap.xml

# Test robots.txt
curl https://8pm.fm/robots.txt

# Test SSL
curl -I https://8pm.fm
```

---

## Content Health (10 min)

- [ ] Check for broken internal links (use Screaming Frog or similar)
- [ ] Review top landing pages (Search Console -> Performance)
- [ ] Identify thin content pages (add depth)
- [ ] Update outdated show information

---

## Competitor Analysis (10 min)

- [ ] Check rankings for "{top-artist} live recordings" (should be top 5)
- [ ] Monitor relisten.net, setlist.fm, nugs.net rankings
- [ ] Identify content gaps (do they have content types we don't?)
- [ ] Backlink opportunities (music blogs, forums)

---

## Action Items Template

Based on findings, create tasks for:

### Technical Issues
| Issue | Priority | Owner | Status |
|-------|----------|-------|--------|
| | | | |

### Content Improvements
| Page | Improvement | Status |
|------|-------------|--------|
| | | |

### Optimization Opportunities
| Keyword | Current CTR | Target CTR | Action |
|---------|-------------|------------|--------|
| | | | |

---

## KPIs to Track

### Week 1 (Post-Launch)
- [ ] GA4 receiving events
- [ ] Sitemap submitted and processing
- [ ] No critical errors in Search Console

### Month 1
- [ ] 100+ pages indexed
- [ ] Core Web Vitals in "Good" range
- [ ] 500+ organic sessions
- [ ] Avg. 3+ minutes time on site

### Month 3
- [ ] 5,000+ pages indexed
- [ ] 5,000+ organic sessions/month
- [ ] Rich results appearing in search
- [ ] CTR improving (baseline: ~2%, goal: ~5%)

### Month 6
- [ ] 10,000+ pages indexed
- [ ] 20,000+ organic sessions/month
- [ ] Top 3 rankings for "{artist} live recordings"
- [ ] Backlinks from 10+ music sites

---

## Alert Triggers

Set up alerts for:
- **Coverage:** >100 pages suddenly drop from index
- **Performance:** Core Web Vitals fail threshold (LCP >4s, CLS >0.25, INP >500ms)
- **Downtime:** Site unreachable for >5 minutes
- **Manual Actions:** Google penalty (rare but critical)

---

## Quarterly Tasks (2-3 hours)

- [ ] Comprehensive competitor analysis
- [ ] Backlink profile review
- [ ] Content gap analysis
- [ ] Technical SEO deep dive (crawl with Screaming Frog)

---

## Annual Tasks (4-6 hours)

- [ ] SEO strategy review
- [ ] Keyword research refresh
- [ ] Schema.org updates (check for new types)
- [ ] Performance optimization sprint
- [ ] Content audit and pruning
- [ ] Backlink disavow review

---

## Monthly Metrics Snapshot Template

| Metric | Previous Month | This Month | Change |
|--------|---------------|------------|--------|
| Indexed Pages | | | |
| Organic Sessions | | | |
| Avg. CTR | | | |
| Avg. Position | | | |
| LCP (mobile) | | | |
| CLS (mobile) | | | |
| INP (mobile) | | | |

---

## Notes

_Add any observations or notes from this month's audit:_

```
Date:
Auditor:
Key Findings:
-
-
-
```
