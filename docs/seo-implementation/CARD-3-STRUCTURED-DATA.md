# CARD-3: Schema.org Structured Data (JSON-LD)

**Priority:** 🟡 Medium - Enables rich search results
**Estimated Time:** 7-9 hours (updated: +field corrections +@graph wrapper)
**Assignee:** Frontend Developer
**Dependencies:** CARD-2 (Frontend Metadata) recommended

---

## 📋 Objective

Implement Schema.org structured data (JSON-LD) for music content to enable rich results in Google Search, including music snippets, ratings, and knowledge panels.

---

## ✅ Acceptance Criteria

- [ ] All page types have appropriate Schema.org markup
- [ ] JSON-LD validates with Schema.org validator
- [ ] Google Rich Results Test shows no errors
- [ ] BreadcrumbList schema on all pages
- [ ] MusicRecording, MusicGroup, MusicAlbum schemas implemented
- [ ] AggregateRating included where applicable

---

## 🎯 Schema Types by Page

| Page Type | Schema Types |
|-----------|-------------|
| Artist | MusicGroup, BreadcrumbList |
| Album/Show | MusicAlbum, BreadcrumbList, AggregateRating |
| Track | MusicRecording, BreadcrumbList |
| Home | WebSite, SearchAction |

---

## ⚠️ Important Data Structure Note

**Albums in your system are CATEGORIES, not products.** They contain multiple track products. You must:
1. Fetch the category for album metadata (name, description)
2. Fetch child products for track data (show_date, show_venue, song_duration, etc.)
3. Derive album fields from the first track (e.g., `showDate = tracks[0].show_date`)

## 🔧 Implementation Steps

### Step 0: Create Duration Helper Utility (15 min)

**File:** `frontend/lib/formatDuration.ts`

```typescript
/**
 * Convert seconds to ISO 8601 duration format (PT12M34S)
 * Required for Schema.org duration fields
 */
export function formatDuration(seconds: number): string {
  if (!seconds || seconds <= 0) return 'PT0S';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);  // Important: Use Math.floor to avoid decimals
  return `PT${mins}M${secs}S`;
}
```

### Step 1: Create StructuredData Component (20 min)

**File:** `frontend/components/StructuredData.tsx`

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

### Step 2: Artist Page - MusicGroup Schema (60 min)

**File:** `frontend/app/artists/[slug]/page.tsx`

Add to component:

```typescript
import StructuredData from '@/components/StructuredData';

export default async function ArtistPage({ params }: { params: { slug: string } }) {
  const { slug } = await params;
  const artist = await fetchArtist(slug);  // This fetches a CATEGORY, not a product
  const bandData = await fetchBandMembers(slug); // If available

  const musicGroupSchema = {
    '@context': 'https://schema.org',
    '@type': 'MusicGroup',
    name: artist.name,
    url: `https://8pm.fm/artists/${slug}`,
    image: artist.wikipedia_artwork_url,  // From category attribute
    description: artist.band_extended_bio,
    genre: artist.band_genres?.split(',').map(g => g.trim()),
    foundingDate: artist.band_formation_date,
    foundingLocation: artist.band_origin_location,
    sameAs: [
      artist.band_official_website,
      artist.band_facebook,
      artist.band_instagram,
      artist.band_twitter,
      // Note: band_youtube_channel doesn't exist in GraphQL schema
    ].filter(Boolean),
    member: bandData?.members?.map(member => ({
      '@type': 'OrganizationRole',
      member: {
        '@type': 'Person',
        name: member.name,
      },
      roleName: member.instruments?.join(', '),  // e.g., "Vocals, Guitar"
      startDate: member.years?.split('-')[0],
      endDate: member.years?.includes('present') ? undefined : member.years?.split('-')[1],
    })),
  };

  const breadcrumbSchema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: 'Home',
        item: 'https://8pm.fm',
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: 'Artists',
        item: 'https://8pm.fm/artists',
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: artist.name,
        item: `https://8pm.fm/artists/${slug}`,
      },
    ],
  };

  // Combine multiple schemas using @graph (Google's preferred format)
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicGroupSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      {/* Rest of component */}
    </>
  );
}
```

### Step 3: Album/Show Page - MusicAlbum Schema (60 min)

**File:** `frontend/app/artists/[slug]/album/[album]/page.tsx`

```typescript
import { formatDuration } from '@/lib/formatDuration';

export default async function AlbumPage({ params }) {
  const { slug, album: albumSlug } = await params;

  // Albums are CATEGORIES, not products
  const album = await fetchCategory(albumSlug);
  const tracks = await fetchTracksInCategory(album.entity_id);
  const firstTrack = tracks[0];  // Derive show metadata from first track

  // Calculate aggregate rating from all tracks
  const totalReviews = tracks.reduce((sum, t) => sum + (t.archive_num_reviews || 0), 0);
  const avgRating = totalReviews > 0
    ? tracks.reduce((sum, t) => sum + ((t.archive_avg_rating || 0) * (t.archive_num_reviews || 0)), 0) / totalReviews
    : null;

  const musicAlbumSchema = {
    '@context': 'https://schema.org',
    '@type': 'MusicAlbum',
    name: firstTrack.show_name,  // From product attribute
    url: `https://8pm.fm/artists/${slug}/album/${albumSlug}`,
    image: album.wikipedia_artwork_url,  // From category attribute
    datePublished: firstTrack.show_date,  // From first track
    byArtist: {
      '@type': 'MusicGroup',
      name: artistName,
      url: `https://8pm.fm/artists/${slug}`,
    },
    numTracks: tracks.length,
    albumProductionType: 'LiveAlbum',  // Use bare type name, not URL
    albumReleaseType: 'AlbumRelease',
    recordedAt: {
      '@type': 'Place',
      name: firstTrack.show_venue,
      address: firstTrack.show_location,
    },
    track: tracks.map((track, index) => ({
      '@type': 'MusicRecording',
      position: index + 1,
      name: track.song_title,  // Correct GraphQL field name
      url: `https://8pm.fm/artists/${slug}/album/${albumSlug}/track/${track.url_key}`,
      duration: formatDuration(track.song_duration),  // Use helper function
    })),
    aggregateRating: totalReviews >= 2 ? {  // Google requires >=2 reviews
      '@type': 'AggregateRating',
      ratingValue: avgRating.toFixed(1),
      reviewCount: totalReviews,
      bestRating: 5,
      worstRating: 1,  // Always include worstRating
    } : undefined,
  };

  const breadcrumbSchema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: 'Home',
        item: 'https://8pm.fm',
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: album.artistName,
        item: `https://8pm.fm/artists/${slug}`,
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: album.name,
        item: `https://8pm.fm/artists/${slug}/album/${albumSlug}`,
      },
    ],
  };

  // Use @graph to combine schemas
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicAlbumSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      {/* Rest of component */}
    </>
  );
}
```

### Step 4: Track Page - MusicRecording Schema (60 min)

**File:** `frontend/app/artists/[slug]/album/[album]/track/[track]/page.tsx`

```typescript
import { formatDuration } from '@/lib/formatDuration';

export default async function TrackPage({ params }) {
  const { slug, album: albumSlug, track: trackSlug } = await params;
  const track = await fetchProduct(trackSlug);  // Tracks are products

  const musicRecordingSchema = {
    '@context': 'https://schema.org',
    '@type': 'MusicRecording',
    name: track.song_title,  // Correct GraphQL field
    url: `https://8pm.fm/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,
    byArtist: {
      '@type': 'MusicGroup',
      name: artistName,
      url: `https://8pm.fm/artists/${slug}`,
    },
    recordingOf: {
      '@type': 'MusicComposition',
      name: track.song_title,
      composer: {
        '@type': 'MusicGroup',
        name: artistName,
      },
    },
    inAlbum: {
      '@type': 'MusicAlbum',
      name: track.show_name,  // Correct GraphQL field
      datePublished: track.show_date,
      url: `https://8pm.fm/artists/${slug}/album/${albumSlug}`,
    },
    duration: formatDuration(track.song_duration),  // Use helper
    recordedAt: {
      '@type': 'Place',
      name: track.show_venue,  // Correct GraphQL field
      address: track.show_location,
    },
    datePublished: track.show_date,  // Correct GraphQL field
    aggregateRating: track.archive_num_reviews >= 2 ? {  // Google requires >=2
      '@type': 'AggregateRating',
      ratingValue: track.archive_avg_rating?.toFixed(1),  // Correct field
      reviewCount: track.archive_num_reviews,
      bestRating: 5,
      worstRating: 1,  // Always include
    } : undefined,
    audio: {
      '@type': 'AudioObject',
      contentUrl: track.song_url,  // Correct GraphQL field
      encodingFormat: 'audio/mpeg',  // More generic format
      duration: formatDuration(track.song_duration),  // Use helper
    },
  };

  const breadcrumbSchema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: 'Home',
        item: 'https://8pm.fm',
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: artistName,
        item: `https://8pm.fm/artists/${slug}`,
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: track.show_name,  // Correct GraphQL field
        item: `https://8pm.fm/artists/${slug}/album/${albumSlug}`,
      },
      {
        '@type': 'ListItem',
        position: 4,
        name: track.song_title,  // Correct GraphQL field
        item: `https://8pm.fm/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,
      },
    ],
  };

  // Use @graph to combine schemas
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicRecordingSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      {/* Rest of component */}
    </>
  );
}
```

### Step 5: Home Page - WebSite Schema (30 min)

**File:** `frontend/app/page.tsx`

```typescript
export default function HomePage() {
  const webSiteSchema = {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'EIGHTPM',
    url: 'https://8pm.fm',
    description: 'Stream live concert recordings from Archive.org',
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: 'https://8pm.fm/search?q={search_term_string}',
      },
      'query-input': 'required name=search_term_string',
    },
  };

  return (
    <>
      <StructuredData data={webSiteSchema} />
      {/* Rest of component */}
    </>
  );
}
```

---

## 🧪 Testing & Validation

### Validation Tools

1. **Google Rich Results Test**
   - URL: https://search.google.com/test/rich-results
   - Test each page type (artist, album, track)
   - Verify no errors or warnings

2. **Schema.org Validator**
   - URL: https://validator.schema.org/
   - Paste page source or URL
   - Check for structural issues

3. **Manual Inspection**
   ```bash
   # View page source and verify JSON-LD
   curl http://localhost:3001/artists/phish | grep 'application/ld+json'

   # Extract and validate JSON
   curl http://localhost:3001/artists/phish | \
     grep -oP '(?<=<script type="application/ld\+json">).*?(?=</script>)' | \
     jq .
   ```

### Testing Checklist

- [ ] Artist page validates (MusicGroup + BreadcrumbList)
- [ ] Album page validates (MusicAlbum + BreadcrumbList + AggregateRating)
- [ ] Track page validates (MusicRecording + BreadcrumbList + AudioObject)
- [ ] Home page validates (WebSite + SearchAction)
- [ ] All URLs are absolute (https://8pm.fm/...)
- [ ] All durations are ISO 8601 format (PT12M34S)
- [ ] No missing required fields
- [ ] Images have valid URLs
- [ ] Ratings only included when data exists (conditional rendering)

---

## 🎯 Expected Rich Results

After implementation and Google indexing (2-4 weeks), you should see:

1. **Music Player Snippets** - Play button in search results
2. **Star Ratings** - Review count and average rating
3. **Knowledge Panels** - Artist info, band members, discography
4. **Breadcrumb Navigation** - In search result URLs
5. **Rich Cards** - Enhanced artist/album cards on mobile

---

## 🐛 Troubleshooting

**Issue:** Validator shows "Missing required field"

**Solution:** Check Schema.org docs for required fields. For MusicRecording, `name` and `url` are required. Make sure all required fields have values.

**Issue:** Duration format invalid

**Solution:** Use ISO 8601 format: `PT12M34S` (12 minutes, 34 seconds). Helper:
```typescript
function formatDuration(seconds: number): string {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `PT${mins}M${secs}S`;
}
```

**Issue:** JSON-LD not appearing in page source

**Solution:** Ensure `<StructuredData>` component is rendered in server component (not client). Check that `dangerouslySetInnerHTML` is used correctly.

**Issue:** Google not showing rich results

**Solution:** This takes time. Ensure:
- Page is indexed (check Search Console)
- Structured data validates with no errors
- Wait 2-4 weeks for Google to process

---

## 📚 References

- [Schema.org MusicRecording](https://schema.org/MusicRecording)
- [Schema.org MusicGroup](https://schema.org/MusicGroup)
- [Schema.org MusicAlbum](https://schema.org/MusicAlbum)
- [Schema.org BreadcrumbList](https://schema.org/BreadcrumbList)
- [Google Structured Data Guide](https://developers.google.com/search/docs/appearance/structured-data)
- [Next.js JSON-LD Guide](https://nextjs.org/docs/app/guides/json-ld)

---

## ✋ Next Steps

After structured data is implemented, monitor Google Search Console for:
- Rich result impressions
- Structured data errors/warnings
- Click-through rate improvements

Structured data typically shows results within 2-4 weeks of indexing.
