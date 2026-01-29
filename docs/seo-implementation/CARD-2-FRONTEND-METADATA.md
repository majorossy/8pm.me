# CARD-2: Frontend Dynamic Metadata (Next.js)

**Priority:** 🟠 High - Blocks search engine indexing
**Estimated Time:** 8-10 hours (updated: +manual canonical URLs)
**Assignee:** Frontend Developer
**Dependencies:** CARD-1 (Backend SEO) must be complete

---

## 📋 Objective

Implement dynamic SEO metadata for all page types using Next.js 14 Metadata API, including Open Graph tags, Twitter Cards, and canonical URLs.

---

## ✅ Acceptance Criteria

- [ ] All dynamic routes have `generateMetadata()` functions
- [ ] Meta titles, descriptions, and Open Graph tags visible in page source
- [ ] Canonical URLs are absolute and correct
- [ ] Social sharing previews work (Twitter, Facebook)
- [ ] No duplicate title tags
- [ ] Environment variable for base URL configured

---

## 🔧 Implementation Steps

### Step 1: Create SEO Utility Library (45 min)

**File:** `frontend/lib/seo.ts`

```typescript
import { Metadata } from 'next';

interface SeoData {
  title?: string;
  description?: string;
  keywords?: string;
  path?: string;  // Changed from canonicalUrl to path (will be prefixed with baseUrl)
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
  const fullUrl = data.path ? `${baseUrl}${data.path}` : baseUrl;
  const ogImage = data.image || `${baseUrl}/images/og-default.jpg`;

  return {
    title: data.title || 'EIGHTPM - Live Music Archive',
    description: data.description || 'Discover and stream thousands of live concert recordings from Archive.org',
    keywords: data.keywords,
    alternates: {
      canonical: fullUrl,
    },
    openGraph: {
      title: data.title,
      description: data.description,
      url: fullUrl,
      siteName: 'EIGHTPM',
      images: [{
        url: ogImage,
        width: 1200,
        height: 630,
        alt: data.title || 'EIGHTPM',
      }],
      type: data.type || 'website',
    },
    twitter: {
      card: 'summary_large_image',
      title: data.title,
      description: data.description,
      images: [ogImage],
    },
  };
}
```

### Step 2: Add Environment Variable (5 min)

**File:** `frontend/.env.local`

```bash
NEXT_PUBLIC_BASE_URL=http://localhost:3001
```

For production deployment, update to:
```bash
NEXT_PUBLIC_BASE_URL=https://8pm.fm
```

### Step 3: Update GraphQL Queries (30 min)

**File:** `frontend/lib/api.ts`

Add SEO fields to your GraphQL fragments:

```typescript
const PRODUCT_SEO_FIELDS = `
  meta_title
  meta_description
  meta_keyword
  url_key
`;

const CATEGORY_SEO_FIELDS = `
  meta_title
  meta_description
  url_key
  url_path
`;

// Update your product query:
const PRODUCT_QUERY = `
  query GetProduct($sku: String!) {
    products(filter: { sku: { eq: $sku } }) {
      items {
        ${PRODUCT_SEO_FIELDS}
        # ... existing fields
      }
    }
  }
`;
```

**Important:** Magento does not expose `canonical_url` in GraphQL. We'll construct canonical URLs manually using `url_key` and routing structure.

### Step 4: Update Root Layout (20 min)

**File:** `frontend/app/layout.tsx`

```typescript
import type { Metadata } from 'next';

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001'), // ⚠️ CRITICAL
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
    description: 'Stream high-quality live concert recordings',
  },

  twitter: {
    card: 'summary_large_image',
    title: 'EIGHTPM - Live Music Archive',
    description: 'Stream high-quality live concert recordings',
  },
};

// Rest of layout unchanged...
```

### Step 5: Artist Page Metadata (45 min)

**File:** `frontend/app/artists/[slug]/page.tsx`

Add this function **above** the component:

```typescript
import { Metadata } from 'next';
import { generateSeoMetadata } from '@/lib/seo';
import { fetchArtist } from '@/lib/api';

export async function generateMetadata({
  params
}: {
  params: { slug: string }
}): Promise<Metadata> {
  const { slug } = await params;
  const artist = await fetchArtist(slug);

  if (!artist) {
    return { title: 'Artist Not Found' };
  }

  const genres = artist.band_genres?.split(',') || [];

  const description = artist.band_extended_bio
    ? artist.band_extended_bio.substring(0, 155) + '...'
    : `Stream ${artist.band_total_shows || 0} live shows from ${artist.name}. High-quality concert recordings from Archive.org.`;

  return generateSeoMetadata({
    title: `${artist.name} - Live Concert Recordings`,
    description,
    keywords: [artist.name, ...genres, 'live concerts', 'archive.org'].join(', '),
    path: `/artists/${slug}`,  // Changed from canonicalUrl to path
    image: artist.wikipedia_artwork_url || artist.image,
    type: 'profile',
  });
}

export default async function ArtistPage({ params }: { params: { slug: string } }) {
  // Existing component code...
}
```

### Step 6: Album/Show Page Metadata (45 min)

**File:** `frontend/app/artists/[slug]/album/[album]/page.tsx`

```typescript
export async function generateMetadata({
  params
}: {
  params: { slug: string; album: string }
}): Promise<Metadata> {
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
    path: `/artists/${slug}/album/${albumSlug}`,  // Changed from canonicalUrl
    image: album.coverArt || album.wikipediaArtworkUrl,
    type: 'music.album',
  });
}
```

### Step 7: Track Page Metadata (45 min)

**File:** `frontend/app/artists/[slug]/album/[album]/track/[track]/page.tsx`

```typescript
export async function generateMetadata({
  params
}: {
  params: { slug: string; album: string; track: string }
}): Promise<Metadata> {
  const { slug, album: albumSlug, track: trackSlug } = await params;
  const track = await fetchTrack(slug, albumSlug, trackSlug);

  if (!track) {
    return { title: 'Track Not Found' };
  }

  const description = `${track.title} from ${track.albumName} by ${track.artistName} • ${track.songCount} recording(s) available`;

  return generateSeoMetadata({
    title: `${track.title} - ${track.artistName}`,
    description,
    path: `/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,  // Changed from canonicalUrl
    type: 'music.song',
  });
}
```

### Step 8: Home Page Metadata (20 min)

**File:** `frontend/app/page.tsx`

```typescript
import { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'EIGHTPM - Stream Live Concert Recordings',
  description: 'Discover and stream thousands of live concert recordings from Archive.org. Featuring Grateful Dead, Phish, Widespread Panic, and more.',
  alternates: {
    canonical: '/',
  },
};

export default function HomePage() {
  // Component code...
}
```

---

## 🧪 Testing Checklist

### Manual Testing

- [ ] Visit artist page, check `View Source` for meta tags
- [ ] Verify `<meta property="og:title">` exists
- [ ] Verify `<link rel="canonical">` exists with full URL
- [ ] Check no duplicate title tags
- [ ] Test social sharing preview:
  - Twitter: https://cards-dev.twitter.com/validator
  - Facebook: https://developers.facebook.com/tools/debug/
  - LinkedIn: https://www.linkedin.com/post-inspector/

### Automated Testing

```bash
# Build production bundle
cd frontend
npm run build

# Check for TypeScript errors
npm run type-check

# Verify metadata in build output
npm run build | grep "generateMetadata"

# Test page rendering
curl http://localhost:3001/artists/phish | grep '<meta property="og:title"'
curl http://localhost:3001/artists/phish | grep '<link rel="canonical"'
```

### Validation Tools

- [ ] [OpenGraph.xyz](https://www.opengraph.xyz/) - Preview how pages appear when shared
- [ ] [Google Rich Results Test](https://search.google.com/test/rich-results)
- [ ] Browser inspector: Check `<head>` tag for all meta elements

---

## 🐛 Troubleshooting

**Issue:** Meta tags not appearing in page source

**Solution:** Ensure `generateMetadata()` is:
1. Exported as a named export
2. Async function
3. Returns `Metadata` type
4. Check in **View Source** (not browser inspector - that shows client-rendered HTML)

**Issue:** Canonical URL is relative, not absolute

**Solution:** Verify `metadataBase` is set in root `layout.tsx`

**Issue:** GraphQL query returns null for SEO fields

**Solution:** Ensure CARD-1 is complete and backend is returning data. Test GraphQL endpoint directly. Note that `canonical_url` does not exist - use `url_key` instead.

**Issue:** TypeScript errors on `generateMetadata`

**Solution:** Ensure proper import: `import { Metadata } from 'next'`

---

## 📚 References

- [Next.js Metadata API](https://nextjs.org/docs/app/api-reference/functions/generate-metadata)
- [Next.js App Router Metadata](https://nextjs.org/docs/app/building-your-application/optimizing/metadata)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards Guide](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)

---

## ✋ Hand-off to CARD-3

Once metadata is working, structured data (Schema.org JSON-LD) can be added in CARD-3 to enable rich results in search engines.

**Deliverables:**
- All page types have dynamic meta tags
- Social sharing previews working
- Canonical URLs present
- No console errors or TypeScript issues
