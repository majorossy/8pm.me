# CARD-6: Sitemap & Robots.txt Implementation

**Priority:** 🟠 High - Required for search engine discovery
**Estimated Time:** 5-6 hours (updated: +pagination fix +ISR)
**Assignee:** Frontend Developer
**Dependencies:** CARD-2 (Frontend Metadata) recommended

---

## 📋 Objective

Implement dynamic sitemap generation and robots.txt configuration for a large music catalog (10,000+ recordings), enabling efficient crawling and indexing by search engines.

---

## ✅ Acceptance Criteria

- [ ] Sitemap.xml accessible at `/sitemap.xml`
- [ ] Robots.txt accessible at `/robots.txt`
- [ ] Sitemap includes all public pages (artists, albums, tracks)
- [ ] Sitemap submitted to Google Search Console
- [ ] No crawl errors in Search Console
- [ ] Audio files blocked from crawling (save bandwidth)
- [ ] Proper priority and changefreq values

---

## ⚠️ CRITICAL: Your Catalog Size

**Current Reality:**
- **186,461 total products (tracks)**
- **~35,420 albums/shows** (level 3 categories)
- Largest show: moe. with 34,420 tracks

**This means:**
- ❌ Including all tracks will take ~2 hours to generate (Next.js times out at 60 seconds)
- ❌ The N+1 query pattern in Step 2 will make 35,420 sequential API calls
- ✅ Must use pagination + ISR (Incremental Static Regeneration)

## 🎯 Sitemap Strategy

**For a catalog with 186k+ recordings:**
- **Static sitemap:** Homepage, artists list, library
- **Paginated sitemap:** Artist pages + album pages (use single paginated query)
- **Tracks:** EXCLUDE from sitemap (too many URLs, low SEO value)
- **ISR:** Regenerate sitemap every hour, not on every request

**Why not include tracks?**
- Google's limit: 50,000 URLs per sitemap file
- 186k tracks would require 4+ sitemap files
- Individual tracks have low search demand
- Albums are the main entry point

---

## 🔧 Implementation Steps

### Step 1: Basic Sitemap (Static Pages) (45 min)

**File:** `frontend/app/sitemap.ts`

```typescript
import { MetadataRoute } from 'next';

export default function sitemap(): MetadataRoute.Sitemap {
  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001';

  return [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/artists`,
      lastModified: new Date(),
      changeFrequency: 'weekly',
      priority: 0.9,
    },
    {
      url: `${baseUrl}/library`,
      lastModified: new Date(),
      changeFrequency: 'weekly',
      priority: 0.8,
    },
    {
      url: `${baseUrl}/search`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.7,
    },
  ];
}
```

**Test:**
```bash
curl http://localhost:3001/sitemap.xml
```

### Step 2: Dynamic Artist Sitemap with Pagination (60 min)

⚠️ **CRITICAL FIX:** The original approach would timeout. Use pagination instead.

**File:** `frontend/lib/sitemap.ts`

Create utility to fetch all data with pagination:

```typescript
import { fetchGraphQL } from './api';

export async function getAllArtists() {
  const query = `
    query GetAllArtists {
      categoryList(filters: { level: { eq: 2 } }) {
        id
        uid
        url_key
        name
        updated_at
      }
    }
  `;

  const response = await fetchGraphQL(query);
  return response.data.categoryList || [];
}

/**
 * Fetch ALL albums with pagination (avoid N+1 query problem)
 * Replaces sequential getAllAlbums(artistSlug) calls
 */
export async function getAllArtistAlbumsPaginated() {
  const albums = [];
  let currentPage = 1;
  const pageSize = 100;
  let hasMore = true;

  while (hasMore) {
    const query = `
      query GetAllAlbums($pageSize: Int!, $currentPage: Int!) {
        categoryList(
          filters: { level: { eq: 3 } }
          pageSize: $pageSize
          currentPage: $currentPage
        ) {
          id
          uid
          url_key
          url_path
          updated_at
        }
      }
    `;

    const response = await fetchGraphQL(query, { pageSize, currentPage });
    const page = response.data.categoryList || [];

    albums.push(...page);

    if (page.length < pageSize) {
      hasMore = false;
    } else {
      currentPage++;
    }
  }

  return albums;
}
```

**Update:** `frontend/app/sitemap.ts`

```typescript
import { MetadataRoute } from 'next';
import { getAllArtists, getAllArtistAlbumsPaginated } from '@/lib/sitemap';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001';

  // Static pages
  const staticPages: MetadataRoute.Sitemap = [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/artists`,
      lastModified: new Date(),
      changeFrequency: 'weekly',
      priority: 0.9,
    },
  ];

  // Artist pages
  const artists = await getAllArtists();
  const artistPages: MetadataRoute.Sitemap = artists.map(artist => ({
    url: `${baseUrl}/artists/${artist.url_key}`,
    lastModified: artist.updated_at ? new Date(artist.updated_at) : new Date(),
    changeFrequency: 'monthly',
    priority: 0.8,
  }));

  // Album pages - SINGLE PAGINATED QUERY (not N+1)
  const albums = await getAllArtistAlbumsPaginated();
  const albumPages: MetadataRoute.Sitemap = albums.map(album => {
    // Extract artist slug from url_path (e.g., "music/grateful-dead/1977-05-08")
    const pathParts = album.url_path.split('/');
    const artistSlug = pathParts[1];
    const albumSlug = album.url_key;

    return {
      url: `${baseUrl}/artists/${artistSlug}/album/${albumSlug}`,
      lastModified: album.updated_at ? new Date(album.updated_at) : new Date(),
      changeFrequency: 'never',  // Historical concerts don't change
      priority: 0.7,
    };
  });

  console.log(`Generated sitemap: ${staticPages.length} static, ${artistPages.length} artists, ${albumPages.length} albums`);

  return [...staticPages, ...artistPages, ...albumPages];
}

// ISR: Regenerate sitemap every hour (not on every request)
export const revalidate = 3600;
```

**Performance Comparison:**
- ❌ Old approach: ~35,420 API calls, ~2 hours build time
- ✅ New approach: ~355 API calls (100 per page), ~10 seconds build time
- 🚀 **720x faster!**

### Step 3: Robots.txt Configuration (30 min)

**File:** `frontend/app/robots.ts`

```typescript
import { MetadataRoute } from 'next';

export default function robots(): MetadataRoute.Robots {
  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001';

  return {
    rules: [
      {
        userAgent: '*',
        allow: '/',
        disallow: [
          '/account/',      // User accounts (private)
          '/api/',          // API endpoints
          '/_next/',        // Next.js internals
          '/search?*',      // Paginated search results
          '/*?sort=*',      // Sorted views
          '/*?filter=*',    // Filtered views
        ],
      },
      {
        userAgent: 'Googlebot',
        allow: '/',
        disallow: ['/account/'],
      },
      // Block AI scrapers (optional)
      {
        userAgent: 'GPTBot',
        disallow: ['/'],
      },
      {
        userAgent: 'CCBot',
        disallow: ['/'],
      },
      // Slow down aggressive crawlers
      {
        userAgent: 'AhrefsBot',
        crawlDelay: 10,
        disallow: ['/'],
      },
      {
        userAgent: 'SemrushBot',
        crawlDelay: 10,
        disallow: ['/'],
      },
    ],
    sitemap: `${baseUrl}/sitemap.xml`,
  };
}
```

**Test:**
```bash
curl http://localhost:3001/robots.txt
```

**Expected output:**
```
User-agent: *
Allow: /
Disallow: /account/
Disallow: /api/
Disallow: /_next/
Disallow: /search?*
...

Sitemap: http://localhost:3001/sitemap.xml
```

### Step 4: Sitemap Index (Advanced - For Large Catalogs) (60 min)

If you exceed 50,000 URLs, create a sitemap index:

**File:** `frontend/app/sitemap-index.xml/route.ts`

```typescript
export async function GET() {
  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001';

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>${baseUrl}/sitemap-static.xml</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
  </sitemap>
  <sitemap>
    <loc>${baseUrl}/sitemap-artists.xml</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
  </sitemap>
  <sitemap>
    <loc>${baseUrl}/sitemap-albums-0.xml</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
  </sitemap>
  <sitemap>
    <loc>${baseUrl}/sitemap-albums-1.xml</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
  </sitemap>
</sitemapindex>`;

  return new Response(xml, {
    headers: {
      'Content-Type': 'application/xml',
      'Cache-Control': 'public, max-age=86400', // 24 hours
    },
  });
}
```

Then create separate files:
- `app/sitemap-static.xml/route.ts` - Static pages
- `app/sitemap-artists.xml/route.ts` - Artist pages
- `app/sitemap-albums-0.xml/route.ts` - Albums 0-10,000
- `app/sitemap-albums-1.xml/route.ts` - Albums 10,001-20,000

### Step 5: Submit to Google Search Console (20 min)

**A. Verify Domain Ownership**

1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add property (use domain property for `8pm.fm`)
3. Verify via DNS TXT record or HTML file upload

**B. Submit Sitemap**

1. In Search Console, navigate to **Sitemaps**
2. Enter sitemap URL: `https://8pm.fm/sitemap.xml`
3. Click **Submit**
4. Wait 24-48 hours for initial crawl
5. Monitor for errors in **Coverage** report

**C. Request Indexing for Key Pages**

1. Navigate to **URL Inspection**
2. Enter important URLs (homepage, top artists)
3. Click **Request Indexing**
4. Prioritize:
   - Homepage
   - Top 10 artist pages
   - Most popular albums

---

## 🧪 Testing Checklist

### Manual Validation

- [ ] Visit `/sitemap.xml` in browser
- [ ] Verify XML is well-formed (no errors)
- [ ] Check URL count (should be <50,000 per file)
- [ ] Verify `<loc>` URLs are absolute (https://...)
- [ ] Check `<lastmod>` dates are ISO 8601 format
- [ ] Verify `<priority>` values are 0.0-1.0
- [ ] Test `/robots.txt` in browser
- [ ] Verify `Sitemap:` directive points to correct URL

### Automated Validation

```bash
# Validate sitemap XML
curl http://localhost:3001/sitemap.xml | xmllint --noout -

# Count URLs in sitemap
curl http://localhost:3001/sitemap.xml | grep -c '<loc>'

# Extract all URLs
curl http://localhost:3001/sitemap.xml | grep -oP '(?<=<loc>).*?(?=</loc>)'

# Check robots.txt
curl http://localhost:3001/robots.txt | grep Sitemap

# Validate with Google
# Use: https://www.google.com/ping?sitemap=https://8pm.fm/sitemap.xml
```

### Search Console Validation

After submission (24-48 hours):
- [ ] No errors in Coverage report
- [ ] URLs discovered matches submitted count
- [ ] No "Duplicate without user-selected canonical" errors
- [ ] Crawl stats show successful requests

---

## 📊 Priority & Changefreq Strategy

| Content Type | Priority | Changefreq | Rationale |
|--------------|----------|------------|-----------|
| Homepage | 1.0 | daily | Highest visibility, updated playlists |
| Artists list | 0.9 | weekly | Main navigation page |
| Artist pages | 0.8 | monthly | Band info updates occasionally |
| Album/Shows | 0.7 | monthly | Studio album listings |
| Library | 0.8 | weekly | User-specific but public |
| Tracks | 0.5-0.6 | never | Archived content, static |

**Note:** Google largely ignores `changefreq` and `priority` in 2026, but they help with organization and some search engines (Bing) still use them. **Focus on accurate `lastmod` values.**

---

## 🐛 Troubleshooting

**Issue:** Sitemap timeout during build

**Solution:**
1. Limit to top N artists: `artists.slice(0, 100)`
2. Use sitemap index with pagination
3. Cache GraphQL responses
4. Consider static export: `npm run build && npm run export`

**Issue:** "Sitemap contains URLs from different domain"

**Solution:** Ensure `NEXT_PUBLIC_BASE_URL` matches production domain. All URLs must use same protocol (https) and domain.

**Issue:** Search Console shows "Couldn't fetch"

**Solution:**
- Verify sitemap is publicly accessible (not behind auth)
- Check server isn't blocking Googlebot
- Ensure no redirects from `/sitemap.xml`
- Validate XML is well-formed

**Issue:** URLs not getting indexed

**Solution:**
- Check robots.txt isn't blocking pages
- Verify canonical URLs point to correct pages
- Ensure pages return 200 status (not 404/500)
- Request indexing manually via URL Inspection tool
- Wait 2-4 weeks for natural crawling

---

## 📚 References

- [Next.js Sitemap Generation](https://nextjs.org/docs/app/api-reference/file-conventions/metadata/sitemap)
- [Next.js Robots.txt](https://nextjs.org/docs/app/api-reference/file-conventions/metadata/robots)
- [Google Sitemap Guide](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap)
- [Robots.txt Specification](https://developers.google.com/search/docs/crawling-indexing/robots/intro)
- [Large Sitemaps Strategy](https://developers.google.com/search/docs/crawling-indexing/sitemaps/large-sitemaps)

---

## ✋ Post-Implementation Monitoring

### Week 1
- [ ] Sitemap successfully crawled by Google
- [ ] No errors in Coverage report
- [ ] At least 50 pages indexed

### Month 1
- [ ] 500+ pages indexed
- [ ] All artist pages indexed
- [ ] Top albums indexed
- [ ] Crawl budget efficiently used

### Month 3
- [ ] 5,000+ pages indexed
- [ ] Organic search traffic growing
- [ ] Top rankings for "{artist} live recordings" queries

---

## 🎯 Next Steps After Completion

1. **Monitor Search Console weekly** for coverage issues
2. **Track crawl stats** to ensure efficient crawling
3. **Update sitemap** when adding new artists/albums
4. **Submit updated sitemap** after major content additions
5. **Review robots.txt** if crawl budget is exhausted
6. **Consider dynamic sitemap generation** for real-time updates
