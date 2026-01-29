# SEO Implementation Guide for 8PM Music Archive

**Project:** Magento 2 Headless + Next.js 14 Music Streaming Platform
**Last Updated:** 2026-01-29
**Status:** Ready for Implementation

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current State Analysis](#current-state-analysis)
3. [Architecture Overview](#architecture-overview)
4. [Backend SEO (Magento/PHP)](#backend-seo-magentophp)
5. [Frontend SEO (Next.js)](#frontend-seo-nextjs)
6. [Schema.org Structured Data](#schemaorg-structured-data)
7. [Core Web Vitals Optimization](#core-web-vitals-optimization)
8. [Sitemap & Robots.txt Strategy](#sitemap--robotstxt-strategy)
9. [Implementation Roadmap](#implementation-roadmap)
10. [Monitoring & Validation](#monitoring--validation)

---

## Executive Summary

This guide provides a complete SEO implementation strategy for the 8PM live music archive platform, covering both the Magento backend and Next.js frontend. The platform's unique architecture (headless Magento with GraphQL + Next.js App Router) requires coordinated SEO efforts across both layers.

### Key Priorities

**High Impact (Weeks 1-2):**
1. ✅ Add dynamic metadata with `generateMetadata()` to all pages
2. ✅ Enable Next.js Image optimization (remove `unoptimized: true`)
3. ✅ Implement sitemap.xml and robots.txt
4. ✅ Add canonical URLs

**Medium Impact (Weeks 3-4):**
1. ✅ Implement Schema.org structured data (MusicRecording, MusicGroup, etc.)
2. ✅ Add Open Graph images for social sharing
3. ✅ Optimize Core Web Vitals (LCP, CLS, INP)

**Ongoing:**
1. ✅ Monitor Google Search Console
2. ✅ Track Core Web Vitals with RUM
3. ✅ Iterate based on performance data

---

## Current State Analysis

### ✅ What You Have (Strong Foundation)

**Frontend:**
- Next.js 14 App Router with SSG (`generateStaticParams()`)
- PWA with comprehensive service worker caching
- Fixed-position UI (prevents CLS issues)
- Font optimization with `display: 'swap'`
- Basic metadata in root layout

**Backend:**
- Custom GraphQL fields for concert data (20+ attributes)
- URL key generation in `BulkProductImporter`
- Category hierarchy (Artists → Albums → Tracks)
- Import tracking and audit logs

### ❌ Critical Gaps

**Frontend:**
- No `generateMetadata()` functions on dynamic pages
- No Open Graph images
- No canonical URLs
- No sitemap.xml or robots.txt
- No structured data (JSON-LD)
- Images not optimized (`unoptimized: true`)

**Backend:**
- Products missing `meta_title`, `meta_description`, `meta_keyword`
- Categories missing SEO metadata
- No `canonical_url` in GraphQL queries

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                                                     │
│  Next.js Frontend (Port 3001)                      │
│  ├─ SSG with generateStaticParams()                │
│  ├─ Dynamic Metadata (generateMetadata)            │
│  ├─ Schema.org JSON-LD                             │
│  ├─ Sitemap.xml (dynamic generation)               │
│  └─ Image Optimization                             │
│                                                     │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ GraphQL API
                   │
┌──────────────────▼──────────────────────────────────┐
│                                                     │
│  Magento Backend (Docker)                          │
│  ├─ SEO Metadata (meta_title, meta_description)    │
│  ├─ Canonical URLs                                 │
│  ├─ Breadcrumb Data                                │
│  ├─ Custom Attributes (show_venue, show_date, etc.)│
│  └─ URL Key Management                             │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Backend SEO (Magento/PHP)

### 1. Add SEO Metadata to Products

**File:** `/Users/chris.majorossy/Education/8pm/src/app/code/ArchiveDotOrg/Core/Model/BulkProductImporter.php`

**Location:** Around line 460, in the `$varcharAttributes` array

```php
// SEO attributes
$metaTitle = sprintf(
    '%s - %s (%s at %s) | 8pm.me',
    $track->getTitle(),
    $artistName,
    $show->getYear() ?? 'Live',
    $show->getVenue() ?? 'Unknown Venue'
);
$metaDescription = sprintf(
    'Listen to %s performed by %s on %s at %s. Free streaming from Archive.org. %s',
    $track->getTitle(),
    $artistName,
    $show->getDate() ?? $show->getYear() ?? 'Unknown Date',
    $show->getVenue() ?? 'Unknown Venue',
    $show->getNotes() ? substr($show->getNotes(), 0, 100) . '...' : ''
);

$varcharAttributes['meta_title'] = substr($metaTitle, 0, 70); // Google limit ~60-70 chars
$varcharAttributes['meta_description'] = substr($metaDescription, 0, 160); // Google limit ~150-160 chars
$varcharAttributes['meta_keyword'] = implode(', ', array_filter([
    $artistName,
    $track->getTitle(),
    $show->getVenue(),
    $show->getYear(),
    'live concert',
    'archive.org',
    'free streaming'
]));
```

### 2. Enable Canonical URLs in Magento

**Admin Path:** Stores → Configuration → Catalog → Catalog → Search Engine Optimization

```
Use Canonical Link Meta Tag For Products: Yes
Use Canonical Link Meta Tag For Categories: Yes
```

This makes `canonical_url` available in GraphQL queries.

### 3. Update GraphQL Queries (Frontend)

**File:** `/Users/chris.majorossy/Education/8pm/frontend/lib/api.ts`

Add SEO fields to product queries:

```typescript
const PRODUCT_SEO_FIELDS = `
  meta_title
  meta_description
  meta_keyword
  canonical_url
  url_key
  breadcrumbs {
    category_uid
    category_name
    category_level
    category_url_key
    category_url_path
  }
`;

// In your fetchSongDetails or fetchProduct function:
const query = `
  query GetProduct($sku: String!) {
    products(filter: { sku: { eq: $sku } }) {
      items {
        ${PRODUCT_SEO_FIELDS}
        # ... existing fields ...
      }
    }
  }
`;
```

### 4. Test Backend Changes

```bash
# After adding SEO fields to BulkProductImporter
bin/magento cache:flush
bin/magento archivedotorg:import-shows "Test Artist" --limit=1

# Verify SEO fields in database
bin/mysql -e "SELECT sku, value FROM catalog_product_entity_varchar
WHERE attribute_id IN (
  SELECT attribute_id FROM eav_attribute
  WHERE attribute_code IN ('meta_title', 'meta_description')
) LIMIT 5;"
```

---

## Frontend SEO (Next.js)

### 1. Create SEO Utility

**File:** `/Users/chris.majorossy/Education/8pm/frontend/lib/seo.ts`

```typescript
import { Metadata } from 'next';

interface SeoData {
  title?: string;
  description?: string;
  keywords?: string;
  canonicalUrl?: string;
  image?: string;
  type?: 'website' | 'music.song' | 'music.album' | 'profile';
}

export function getBaseUrl(): string {
  if (process.env.NEXT_PUBLIC_BASE_URL) {
    return process.env.NEXT_PUBLIC_BASE_URL;
  }
  if (typeof window !== 'undefined') {
    return window.location.origin;
  }
  return 'http://localhost:3001'; // Development default
}

export function getCanonicalUrl(path: string): string {
  const base = getBaseUrl();
  const cleanPath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${cleanPath}`;
}

export function generateSeoMetadata(data: SeoData): Metadata {
  const baseUrl = getBaseUrl();

  return {
    title: data.title || 'EIGHTPM - Live Music Archive',
    description: data.description || 'Discover and stream thousands of live concert recordings from Archive.org',
    keywords: data.keywords,
    alternates: {
      canonical: data.canonicalUrl ? `${baseUrl}${data.canonicalUrl}` : undefined,
    },
    openGraph: {
      title: data.title,
      description: data.description,
      url: data.canonicalUrl ? `${baseUrl}${data.canonicalUrl}` : baseUrl,
      siteName: 'EIGHTPM',
      images: data.image ? [{ url: data.image, width: 1200, height: 630 }] : undefined,
      type: data.type || 'website',
    },
    twitter: {
      card: 'summary_large_image',
      title: data.title,
      description: data.description,
      images: data.image ? [data.image] : undefined,
    },
  };
}
```

### 2. Add Environment Variable

**File:** `/Users/chris.majorossy/Education/8pm/frontend/.env.local`

```bash
NEXT_PUBLIC_BASE_URL=http://localhost:3001
```

For production:
```bash
NEXT_PUBLIC_BASE_URL=https://8pm.fm
```

### 3. Update Root Layout

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/layout.tsx`

```typescript
import type { Metadata } from 'next';
import { getBaseUrl } from '@/lib/seo';

const baseUrl = getBaseUrl();

export const metadata: Metadata = {
  metadataBase: new URL(baseUrl), // ⚠️ CRITICAL for relative URLs
  title: {
    default: 'EIGHTPM - Live Music Archive',
    template: '%s | EIGHTPM',
  },
  description: 'Stream high-quality live concert recordings from legendary artists. Discover thousands of shows from Archive.org in a modern music player.',
  keywords: ['live music', 'concert recordings', 'archive.org', 'streaming', 'grateful dead', 'phish', 'jam bands'],

  openGraph: {
    type: 'website',
    locale: 'en_US',
    url: baseUrl,
    siteName: 'EIGHTPM',
    title: 'EIGHTPM - Live Music Archive',
    description: 'Stream high-quality live concert recordings from legendary artists',
  },

  twitter: {
    card: 'summary_large_image',
    title: 'EIGHTPM - Live Music Archive',
    description: 'Stream high-quality live concert recordings',
  },

  appleWebApp: {
    capable: true,
    statusBarStyle: 'black-translucent',
    title: 'EIGHTPM',
  },
};

// Rest of layout...
```

### 4. Add Dynamic Metadata to Pages

#### Artist Page

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/artists/[slug]/page.tsx`

```typescript
import { Metadata } from 'next';
import { generateSeoMetadata } from '@/lib/seo';
import { fetchArtist } from '@/lib/api';

export async function generateMetadata({ params }: { params: { slug: string } }): Promise<Metadata> {
  const { slug } = await params;
  const artist = await fetchArtist(slug);

  if (!artist) {
    return { title: 'Artist Not Found' };
  }

  const genres = artist.band_genres?.split(',') || [];

  return generateSeoMetadata({
    title: `${artist.name} - Live Concert Recordings`,
    description: artist.band_extended_bio?.substring(0, 160) || `Stream ${artist.band_total_shows || 0} live shows from ${artist.name}. High-quality concert recordings from Archive.org.`,
    keywords: [artist.name, ...genres, 'live concerts', 'archive.org'].join(', '),
    canonicalUrl: `/artists/${slug}`,
    image: artist.wikipedia_artwork_url || artist.image,
    type: 'profile',
  });
}

export default async function ArtistPage({ params }: { params: { slug: string } }) {
  // Component code...
}
```

#### Album/Show Page

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/artists/[slug]/album/[album]/page.tsx`

```typescript
export async function generateMetadata({ params }: { params: { slug: string; album: string } }): Promise<Metadata> {
  const { slug, album: albumSlug } = await params;
  const album = await fetchAlbum(slug, albumSlug);

  if (!album) {
    return { title: 'Album Not Found' };
  }

  const trackList = album.tracks.map(t => t.title).slice(0, 5).join(', ');
  const description = `${album.name} by ${album.artistName} • ${album.totalTracks} tracks • Recorded ${album.showDate || ''} at ${album.showVenue || 'live venue'}. Featuring: ${trackList}`;

  return generateSeoMetadata({
    title: `${album.name} - ${album.artistName}`,
    description,
    canonicalUrl: `/artists/${slug}/album/${albumSlug}`,
    image: album.coverArt || album.wikipediaArtworkUrl,
    type: 'music.album',
  });
}
```

#### Track Page

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/artists/[slug]/album/[album]/track/[track]/page.tsx`

```typescript
export async function generateMetadata({ params }: { params: { slug: string; album: string; track: string } }): Promise<Metadata> {
  const { slug, album: albumSlug, track: trackSlug } = await params;
  const track = await fetchTrack(slug, albumSlug, trackSlug);

  if (!track) {
    return { title: 'Track Not Found' };
  }

  const description = `${track.title} from ${track.albumName} by ${track.artistName} • ${track.songCount} recording(s) available`;

  return generateSeoMetadata({
    title: `${track.title} - ${track.artistName}`,
    description,
    canonicalUrl: `/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,
    type: 'music.song',
  });
}
```

### 5. Enable Image Optimization

**File:** `/Users/chris.majorossy/Education/8pm/frontend/next.config.js`

```javascript
const nextConfig = {
  images: {
    domains: ['localhost', 'magento.test'],
    unoptimized: false,  // ❌ REMOVE THIS LINE
    formats: ['image/avif', 'image/webp'],
    deviceSizes: [640, 750, 828, 1080, 1200],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
    minimumCacheTTL: 60 * 60 * 24 * 30, // 30 days
  },
};
```

### 6. Replace `<img>` with Next.js `<Image>`

**Example:** `/Users/chris.majorossy/Education/8pm/frontend/components/BottomPlayer.tsx`

```tsx
import Image from 'next/image';

// Before:
<img
  src={queue.album.coverArt}
  alt={queue.album.name}
  loading="lazy"
  className="w-full h-full object-cover"
/>

// After:
<Image
  src={queue.album.coverArt}
  alt={`${queue.album.name} by ${currentSong.artistName}`}
  width={56}
  height={56}
  quality={85}
  className="rounded object-cover"
/>
```

---

## Schema.org Structured Data

### 1. Create JSON-LD Component

**File:** `/Users/chris.majorossy/Education/8pm/frontend/components/StructuredData.tsx`

```typescript
interface StructuredDataProps {
  data: Record<string, any>;
}

export default function StructuredData({ data }: StructuredDataProps) {
  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
    />
  );
}
```

### 2. MusicRecording Schema (Track Pages)

```typescript
import StructuredData from '@/components/StructuredData';

const musicRecordingSchema = {
  '@context': 'https://schema.org',
  '@type': 'MusicRecording',
  name: track.title,
  url: `https://8pm.fm/artists/${artistSlug}/album/${albumSlug}/track/${trackSlug}`,
  byArtist: {
    '@type': 'MusicGroup',
    name: track.artistName,
    url: `https://8pm.fm/artists/${artistSlug}`,
  },
  inAlbum: {
    '@type': 'MusicAlbum',
    name: track.albumName,
    datePublished: track.showDate,
  },
  duration: `PT${Math.floor(track.totalDuration / 60)}M${track.totalDuration % 60}S`,
  recordedAt: {
    '@type': 'Place',
    name: track.showVenue,
    address: track.showLocation,
  },
  aggregateRating: track.avgRating && track.numReviews ? {
    '@type': 'AggregateRating',
    ratingValue: track.avgRating,
    reviewCount: track.numReviews,
    bestRating: 5,
  } : undefined,
};

// In JSX:
<StructuredData data={musicRecordingSchema} />
```

### 3. MusicGroup Schema (Artist Pages)

```typescript
const musicGroupSchema = {
  '@context': 'https://schema.org',
  '@type': 'MusicGroup',
  name: artist.name,
  url: `https://8pm.fm/artists/${artist.slug}`,
  image: artist.wikipedia_artwork_url,
  description: artist.band_extended_bio,
  genre: artist.band_genres?.split(','),
  foundingDate: artist.band_formation_date,
  foundingLocation: artist.band_origin_location,
  sameAs: [
    artist.band_official_website,
    artist.band_facebook,
    artist.band_instagram,
    artist.band_twitter,
  ].filter(Boolean),
  member: bandMembers?.map(member => ({
    '@type': 'OrganizationRole',
    member: {
      '@type': 'Person',
      name: member.name,
    },
    roleName: member.role,
    startDate: member.years.split('-')[0],
    endDate: member.years.includes('present') ? undefined : member.years.split('-')[1],
  })),
};

<StructuredData data={musicGroupSchema} />
```

### 4. MusicAlbum Schema (Show Pages)

```typescript
const musicAlbumSchema = {
  '@context': 'https://schema.org',
  '@type': 'MusicAlbum',
  name: album.name,
  url: `https://8pm.fm/artists/${artistSlug}/album/${albumSlug}`,
  image: album.coverArt || album.wikipediaArtworkUrl,
  datePublished: album.showDate,
  byArtist: {
    '@type': 'MusicGroup',
    name: album.artistName,
    url: `https://8pm.fm/artists/${artistSlug}`,
  },
  numTracks: album.totalTracks,
  albumProductionType: 'http://schema.org/LiveAlbum',
  recordedAt: {
    '@type': 'Place',
    name: album.showVenue,
    address: album.showLocation,
  },
  track: album.tracks.map((track, index) => ({
    '@type': 'MusicRecording',
    position: index + 1,
    name: track.title,
    url: `https://8pm.fm/artists/${artistSlug}/album/${albumSlug}/track/${track.slug}`,
    duration: `PT${Math.floor(track.totalDuration / 60)}M${track.totalDuration % 60}S`,
  })),
};

<StructuredData data={musicAlbumSchema} />
```

### 5. BreadcrumbList Schema

```typescript
const breadcrumbSchema = {
  '@context': 'https://schema.org',
  '@type': 'BreadcrumbList',
  itemListElement: breadcrumbs.map((crumb, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: crumb.label,
    item: `https://8pm.fm${crumb.href}`,
  })),
};

<StructuredData data={breadcrumbSchema} />
```

---

## Core Web Vitals Optimization

### 1. LCP (Largest Contentful Paint) - Target: <2.5s

**Priority Actions:**

1. **Use Next.js Image component** (see section above)
2. **Preload critical resources:**

```tsx
// In app/layout.tsx
<head>
  <link rel="preconnect" href="https://magento.test" />
  <link rel="dns-prefetch" href="https://magento.test" />
  <link rel="preconnect" href="https://archive.org" />
</head>
```

3. **Add `priority` to above-fold images:**

```tsx
<Image
  src={heroAlbumArt}
  alt="Album"
  priority  // ⚠️ Only for hero images
  width={300}
  height={300}
/>
```

### 2. CLS (Cumulative Layout Shift) - Target: <0.1

**Already Good:** Your fixed-position bottom player prevents CLS.

**Improvements:**

1. **Reserve space for lazy-loaded images:**

```tsx
<div className="relative w-14 h-14">  {/* Container reserves space */}
  <Image
    src={coverArt}
    alt={album}
    fill
    sizes="56px"
    className="object-cover rounded"
  />
</div>
```

2. **Add skeleton loaders:**

```tsx
{isLoading ? (
  <div className="animate-pulse bg-[#2d2a26] w-14 h-14 rounded" />
) : (
  <Image src={coverArt} ... />
)}
```

### 3. INP (Interaction to Next Paint) - Target: <200ms

**Priority Actions:**

1. **Memoize expensive components:**

```tsx
import { memo } from 'react';

export const VUMeter = memo(({ volume, size }: VUMeterProps) => {
  // Component logic
}, (prevProps, nextProps) => {
  return Math.abs(prevProps.volume - nextProps.volume) < 0.01;
});
```

2. **Lazy load visualizations:**

```tsx
import dynamic from 'next/dynamic';

const VUMeter = dynamic(
  () => import('./AudioVisualizations').then(mod => ({ default: mod.VUMeter })),
  {
    ssr: false,
    loading: () => <div className="w-9 h-5 bg-[#2d2a26] animate-pulse rounded" />
  }
);
```

3. **Throttle audio analyzer updates:**

```typescript
// Reduce from 60fps to 30fps
let lastUpdate = 0;
const throttleMs = 33; // ~30fps

if (Date.now() - lastUpdate >= throttleMs) {
  setAnalyzerData({ waveform, volume, frequencyData });
  lastUpdate = Date.now();
}
```

### 4. Monitor with web-vitals

```bash
npm install web-vitals
```

```tsx
// app/layout.tsx
'use client';

import { useEffect } from 'react';
import { onCLS, onLCP, onINP } from 'web-vitals';

export default function RootLayout({ children }) {
  useEffect(() => {
    onCLS(console.log);
    onLCP(console.log);
    onINP(console.log);
    // Send to analytics endpoint in production
  }, []);

  return children;
}
```

---

## Sitemap & Robots.txt Strategy

### 1. Sitemap Index Structure

For 10,000+ recordings, use a sitemap index:

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/sitemap.ts`

```typescript
import { MetadataRoute } from 'next';

export default function sitemap(): MetadataRoute.Sitemap {
  const baseUrl = 'https://8pm.fm';

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
  ];
}
```

### 2. Dynamic Artist Sitemap

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/sitemap-artists.ts`

```typescript
import { MetadataRoute } from 'next';
import { fetchAllArtists } from '@/lib/api';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = 'https://8pm.fm';
  const artists = await fetchAllArtists();

  return artists.map(artist => ({
    url: `${baseUrl}/artists/${artist.slug}`,
    lastModified: new Date(),
    changeFrequency: 'monthly',
    priority: 0.8,
  }));
}
```

### 3. Robots.txt

**File:** `/Users/chris.majorossy/Education/8pm/frontend/app/robots.ts`

```typescript
import { MetadataRoute } from 'next';

export default function robots(): MetadataRoute.Robots {
  const baseUrl = 'https://8pm.fm';

  return {
    rules: [
      {
        userAgent: '*',
        allow: '/',
        disallow: [
          '/account/',
          '/api/',
          '/_next/',
          '/search?*',  // Block paginated search
          '/*?sort=*',  // Block sorted views
          '/*?filter=*', // Block filtered views
        ],
      },
    ],
    sitemap: `${baseUrl}/sitemap.xml`,
  };
}
```

### 4. Priority Strategy

| Content Type | Priority | Changefreq | Rationale |
|--------------|----------|------------|-----------|
| Homepage | 1.0 | weekly | Highest visibility |
| Artist pages | 0.9 | monthly | Important landing pages |
| Albums/Shows | 0.8 | monthly | Studio albums |
| Recordings | 0.7 | never | Archived, unchanging |
| Tracks | 0.5 | never | Leaf nodes |

---

## Implementation Roadmap

### Week 1: Backend Foundation

**Day 1-2: Magento SEO Fields**
- [ ] Add `meta_title`, `meta_description`, `meta_keyword` to `BulkProductImporter.php`
- [ ] Enable canonical URLs in Magento admin
- [ ] Run `bin/magento cache:flush`
- [ ] Test with `bin/magento archivedotorg:import-shows "Test Artist" --limit=1`

**Day 3-4: GraphQL Queries**
- [ ] Add SEO fields to `lib/api.ts` GraphQL queries
- [ ] Update TypeScript types
- [ ] Test queries in GraphQL playground

**Day 5: Validation**
- [ ] Verify SEO fields in database
- [ ] Test GraphQL endpoint returns SEO data
- [ ] Re-import one artist to confirm full pipeline

### Week 2: Frontend Metadata

**Day 1-2: Foundation Files**
- [ ] Create `lib/seo.ts` with helper functions
- [ ] Add `NEXT_PUBLIC_BASE_URL` to `.env.local`
- [ ] Update root `layout.tsx` with `metadataBase`
- [ ] Create `robots.ts` and `sitemap.ts`

**Day 3-5: Dynamic Metadata**
- [ ] Add `generateMetadata()` to artist pages
- [ ] Add `generateMetadata()` to album/show pages
- [ ] Add `generateMetadata()` to track pages
- [ ] Test with `View Source` in browser

### Week 3: Structured Data

**Day 1-2: Components**
- [ ] Create `StructuredData.tsx` component
- [ ] Create schema generation utilities

**Day 3-5: Implementation**
- [ ] Add MusicGroup schema to artist pages
- [ ] Add MusicAlbum schema to show pages
- [ ] Add MusicRecording schema to track pages
- [ ] Add BreadcrumbList schema
- [ ] Validate with Google Rich Results Test

### Week 4: Core Web Vitals

**Day 1-2: Image Optimization**
- [ ] Enable Next.js image optimization (remove `unoptimized: true`)
- [ ] Replace `<img>` tags with `<Image>` component
- [ ] Add `priority` to hero images
- [ ] Test with Lighthouse

**Day 3-4: Performance Optimization**
- [ ] Memoize AudioVisualizations components
- [ ] Lazy load heavy components (Queue, SearchOverlay)
- [ ] Throttle audio analyzer to 30fps
- [ ] Add web-vitals monitoring

**Day 5: Testing**
- [ ] Run Lighthouse on all page types
- [ ] Test on mobile (Chrome DevTools throttling)
- [ ] Verify Core Web Vitals metrics

### Week 5+: Monitoring & Iteration

- [ ] Set up Google Search Console
- [ ] Submit sitemap.xml
- [ ] Monitor coverage reports
- [ ] Track Core Web Vitals in Search Console
- [ ] Iterate based on real user data

---

## Monitoring & Validation

### Tools Checklist

**Pre-Launch Validation:**
- [ ] [Google Rich Results Test](https://search.google.com/test/rich-results) - Validate structured data
- [ ] [OpenGraph.xyz](https://www.opengraph.xyz/) - Preview social shares
- [ ] [Schema.org Validator](https://validator.schema.org/) - Validate JSON-LD
- [ ] Chrome Lighthouse - Performance, SEO, Accessibility scores
- [ ] `curl https://8pm.fm/sitemap.xml | xmllint --noout -` - Validate sitemap XML

**Post-Launch Monitoring:**
- [ ] [Google Search Console](https://search.google.com/search-console) - Coverage, sitemaps, Core Web Vitals
- [ ] [PageSpeed Insights](https://pagespeed.web.dev/) - Real user Core Web Vitals
- [ ] Chrome DevTools → Performance → Web Vitals
- [ ] Google Analytics 4 - Traffic sources, user behavior

### Success Metrics

**Week 1-2 (Post-Launch):**
- Sitemap successfully submitted to Search Console
- 0 coverage errors
- Structured data detected by Google

**Month 1:**
- 50+ pages indexed
- LCP < 2.5s on 75% of page loads
- CLS < 0.1 on 75% of page loads
- INP < 200ms on 75% of interactions

**Month 3:**
- 1,000+ pages indexed
- Rich results appearing in search (music snippets, ratings)
- Organic traffic growing week-over-week

**Month 6:**
- 10,000+ pages indexed
- Featured in Google Knowledge Graph
- Top 3 ranking for "{artist} live recordings" queries

---

## Quick Reference Commands

### Backend (Magento)

```bash
# Clear cache after SEO changes
bin/magento cache:flush

# Re-import artist with new SEO fields
bin/magento archivedotorg:import-shows "Phish" --limit=10

# Check SEO fields in database
bin/mysql -e "SELECT sku, value FROM catalog_product_entity_varchar
WHERE attribute_id IN (
  SELECT attribute_id FROM eav_attribute
  WHERE attribute_code = 'meta_title'
) LIMIT 5;"

# Test GraphQL query
curl -X POST https://magento.test/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ products(filter:{sku:{eq:\"test-sku\"}}) { items { meta_title meta_description canonical_url } } }"}'
```

### Frontend (Next.js)

```bash
# Build production bundle
cd frontend
npm run build

# Check sitemap
curl http://localhost:3001/sitemap.xml

# Check robots.txt
curl http://localhost:3001/robots.txt

# Run Lighthouse
npx lighthouse http://localhost:3001 --view

# Check for TypeScript errors
npm run type-check
```

---

## Troubleshooting

### Issue: Meta tags not appearing

**Check:**
1. `generateMetadata()` function is exported
2. Function is async
3. GraphQL query returns SEO fields
4. View page source (not browser inspector - shows client-side rendered)

### Issue: Images not optimizing

**Check:**
1. `unoptimized: false` in `next.config.js`
2. Image domains configured correctly
3. Using `<Image>` component (not `<img>`)
4. Build succeeds without image optimization errors

### Issue: Sitemap not generating

**Check:**
1. `sitemap.ts` file is in `app/` directory
2. File exports default function
3. Function returns array of URLs
4. Visit `/sitemap.xml` directly in browser

### Issue: Structured data not validating

**Check:**
1. JSON is valid (use JSON validator)
2. Required fields present (`@context`, `@type`)
3. Dates in ISO 8601 format
4. URLs are absolute (not relative)
5. Test with Google Rich Results Test

---

## Additional Resources

### Documentation
- [Next.js Metadata API](https://nextjs.org/docs/app/api-reference/functions/generate-metadata)
- [Schema.org Music Types](https://schema.org/MusicRecording)
- [Google Search Central](https://developers.google.com/search)
- [Core Web Vitals Guide](https://web.dev/vitals/)

### Validation Tools
- [Google Rich Results Test](https://search.google.com/test/rich-results)
- [Schema Markup Validator](https://validator.schema.org/)
- [Open Graph Debugger](https://www.opengraph.xyz/)
- [PageSpeed Insights](https://pagespeed.web.dev/)

### Monitoring
- [Google Search Console](https://search.google.com/search-console)
- [Lighthouse CI](https://github.com/GoogleChrome/lighthouse-ci)
- [web-vitals Library](https://github.com/GoogleChrome/web-vitals)

---

**Last Updated:** 2026-01-29
**Next Review:** After Week 4 implementation completion
