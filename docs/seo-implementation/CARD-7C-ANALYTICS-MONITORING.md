# CARD-7C: Analytics & SEO Monitoring

**Priority:** 🔴 CRITICAL - Must launch with site
**Estimated Time:** 3 hours setup + 1 hour/month ongoing
**Assignee:** Frontend Developer
**Dependencies:** None (implement early)

---

## 📋 Objective

Set up comprehensive analytics and SEO monitoring to measure implementation success, track user behavior, and identify optimization opportunities.

---

## ✅ Acceptance Criteria

- [ ] Google Analytics 4 integrated with custom events
- [ ] Google Search Console configured and sitemap submitted
- [ ] web-vitals tracking active (covered in CARD-5)
- [ ] Monthly SEO audit checklist created
- [ ] Alert system for critical issues (coverage errors, performance drops)

---

## 🔧 Implementation Steps

### Step 1: Google Analytics 4 Integration (60 min)

**A. Create GA4 Property**

1. Go to [Google Analytics](https://analytics.google.com)
2. Create new GA4 property: "EIGHTPM Production"
3. Copy Measurement ID (format: `G-XXXXXXXXXX`)
4. Add to environment variables

**File:** `frontend/.env.local`

```bash
NEXT_PUBLIC_GA_MEASUREMENT_ID=G-XXXXXXXXXX
```

**B. Install Analytics**

```bash
cd frontend
npm install @next/third-parties
```

**File:** `frontend/app/layout.tsx`

```typescript
import { GoogleAnalytics } from '@next/third-parties/google';

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>
        {children}
        {process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID && (
          <GoogleAnalytics gaId={process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID} />
        )}
      </body>
    </html>
  );
}
```

**C. Track Custom Events**

**File:** `frontend/lib/analytics.ts`

```typescript
export function trackEvent(
  action: string,
  category: string,
  label?: string,
  value?: number
) {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', action, {
      event_category: category,
      event_label: label,
      value: value,
    });
  }
}

// Specific event trackers
export function trackSongPlay(song: Song) {
  trackEvent('play', 'Audio', `${song.artistName} - ${song.trackTitle}`);
}

export function trackAddToPlaylist(song: Song) {
  trackEvent('add_to_playlist', 'Engagement', song.trackTitle);
}

export function trackSearch(query: string, resultsCount: number) {
  trackEvent('search', 'Discovery', query, resultsCount);
}

export function trackShare(contentType: string, contentName: string) {
  trackEvent('share', 'Social', `${contentType}: ${contentName}`);
}
```

**D. Add Event Tracking to Components**

```tsx
// In AudioContext or player component
import { trackSongPlay } from '@/lib/analytics';

const handlePlay = (song: Song) => {
  playSong(song);
  trackSongPlay(song);
};

// In search component
const handleSearch = async (query: string) => {
  const results = await searchSongs(query);
  trackSearch(query, results.length);
  setResults(results);
};

// In share hook
const handleShare = (song: Song) => {
  shareContent(song);
  trackShare('song', song.trackTitle);
};
```

---

### Step 2: Google Search Console Setup (30 min)

**A. Verify Domain Ownership**

1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add property: `https://8pm.fm` (use domain property for all subdomains)
3. Verify via DNS TXT record:
   - Add TXT record to DNS: `google-site-verification=XXXXXXXXX`
   - Wait 5-10 minutes for propagation
   - Click "Verify"

**B. Submit Sitemap**

1. In Search Console, navigate to **Sitemaps** (left sidebar)
2. Enter sitemap URL: `https://8pm.fm/sitemap.xml`
3. Click **Submit**
4. Wait 24-48 hours for initial crawl

**C. Request Indexing for Key Pages**

Use **URL Inspection** tool to fast-track important pages:
- Homepage: `https://8pm.fm`
- Top 5 artist pages (Grateful Dead, Phish, etc.)
- Most popular albums

**D. Set Up Email Alerts**

1. Navigate to **Settings** (gear icon)
2. **Email notifications** → Enable all
3. Add your email address
4. Enable alerts for:
   - Coverage issues
   - Manual actions
   - Performance issues

---

### Step 3: Create Monthly SEO Audit Checklist (30 min)

**File:** `docs/seo-implementation/MONTHLY_SEO_AUDIT.md`

```markdown
# Monthly SEO Audit Checklist

**Time Required:** ~1 hour/month
**Frequency:** First Monday of each month

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
- [ ] Verify Event, Music, Breadcrumb markup detected
- [ ] Review rich result impressions (growing?)

---

## Lighthouse Audit (15 min)

Run automated audit:

```bash
cd frontend
npx lighthouse http://localhost:3001/artists/phish \
  --view \
  --preset=desktop \
  --only-categories=performance,seo,accessibility
```

**Check for:**
- [ ] Performance score >90
- [ ] SEO score 100
- [ ] Accessibility score >95
- [ ] No new errors or warnings

---

## Content Health (10 min)

- [ ] Check for broken internal links (use Screaming Frog)
- [ ] Review top landing pages (Search Console → Performance)
- [ ] Identify thin content pages (add depth)
- [ ] Update outdated show information

---

## Competitor Analysis (10 min)

- [ ] Check rankings for "{top-artist} live recordings" (should be top 5)
- [ ] Monitor relisten.net, setlist.fm, nugs.net rankings
- [ ] Identify content gaps (do they have content types you don't?)
- [ ] Backlink opportunities (music blogs, forums)

---

## Action Items

Based on findings, create tasks for:
- Fixing technical issues
- Expanding thin content
- Optimizing high-impression, low-CTR pages
- Building backlinks
```

---

### Step 4: Set Up Uptime Monitoring (30 min)

**Option A: UptimeRobot (Free)**

1. Sign up at [UptimeRobot](https://uptimerobot.com)
2. Add monitor: `https://8pm.fm` (check every 5 minutes)
3. Set up email/SMS alerts for downtime
4. Monitor key pages: homepage, top artists, API endpoint

**Option B: Google Cloud Monitoring**

If using Google Cloud Platform:
- Set up uptime checks
- Monitor /api/health endpoint
- Alert on 5xx errors

---

### Step 5: Performance Monitoring Dashboard (30 min)

**File:** `frontend/app/admin/analytics/page.tsx`

Create admin dashboard showing:
- Real-time active users (GA4 API)
- Top played songs (last 7 days)
- Search Console metrics (impressions, clicks, CTR)
- Core Web Vitals summary
- Coverage status (indexed pages)

```typescript
// Example using GA4 Data API
import { BetaAnalyticsDataClient } from '@google-analytics/data';

export async function getRecentActivity() {
  const analyticsData = new BetaAnalyticsDataClient();

  const [response] = await analyticsData.runReport({
    property: `properties/${process.env.GA_PROPERTY_ID}`,
    dateRanges: [{ startDate: '7daysAgo', endDate: 'today' }],
    dimensions: [{ name: 'eventName' }],
    metrics: [{ name: 'eventCount' }],
  });

  return response.rows;
}
```

---

## 🧪 Testing Checklist

### GA4 Testing (Dev Environment)

- [ ] Install [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger) extension
- [ ] Enable debug mode
- [ ] Trigger events (play song, search, add to playlist)
- [ ] Verify events appear in GA4 DebugView (real-time)

### Search Console Testing

- [ ] Verify sitemap appears in Sitemaps report
- [ ] Check URL Inspection for homepage (should show "URL is on Google")
- [ ] Wait 24-48 hours for coverage data

---

## 📊 KPIs to Track

### Week 1
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

## 🚨 Alert Triggers

Set up alerts for:
- **Coverage:** >100 pages suddenly drop from index
- **Performance:** Core Web Vitals fail threshold (LCP >4s, CLS >0.25, INP >500ms)
- **Downtime:** Site unreachable for >5 minutes
- **Manual Actions:** Google penalty (rare but critical)

**How to set up:**
- Search Console: Settings → Email notifications
- GA4: Admin → Custom alerts
- UptimeRobot: Automatic email alerts

---

## 📚 References

- [Google Analytics 4 Setup Guide](https://support.google.com/analytics/answer/9304153)
- [Next.js Third Parties Package](https://nextjs.org/docs/app/building-your-application/optimizing/third-party-libraries)
- [Google Search Console Guide](https://developers.google.com/search/docs/monitor-debug/search-console-start)
- [SEO Monitoring Best Practices | Moz](https://moz.com/learn/seo/seo-monitoring)

---

## ✋ Ongoing Maintenance

**Monthly Tasks (1 hour):**
- Run SEO audit checklist
- Review Search Console reports
- Check Lighthouse scores
- Update content based on findings

**Quarterly Tasks (2-3 hours):**
- Comprehensive competitor analysis
- Backlink profile review
- Content gap analysis
- Technical SEO deep dive (crawl with Screaming Frog)

**Annual Tasks (4-6 hours):**
- SEO strategy review
- Keyword research refresh
- Schema.org updates (check for new types)
- Performance optimization sprint
