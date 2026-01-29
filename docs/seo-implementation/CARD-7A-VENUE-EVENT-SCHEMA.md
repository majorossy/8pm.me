# CARD-7A: Venue & Event Schema (Local SEO)

**Priority:** 🔴 HIGH - Huge opportunity for jam band audience
**Estimated Time:** 3-4 hours
**Assignee:** Frontend Developer
**Dependencies:** CARD-3 (Structured Data) should be complete

---

## 📋 Objective

Add venue-based and event-based structured data to capture local search traffic for concert recordings. This is critical for jam band culture where venues like Red Rocks, Madison Square Garden, and The Fillmore have legendary status.

---

## ✅ Acceptance Criteria

- [ ] MusicEvent schema added to all show/album pages
- [ ] Place schema with geocoordinates for venues
- [ ] Venue landing pages created (optional Phase 2)
- [ ] Local search queries testable ("grateful dead red rocks 1978")
- [ ] Google Rich Results Test validates Event schema

---

## 🎯 SEO Impact

**Target Queries:**
- "{artist} at {venue}" (e.g., "phish madison square garden")
- "{artist} {venue} {year}" (e.g., "grateful dead red rocks 1978")
- "concerts at {venue}" (e.g., "concerts at fillmore")
- "best {artist} shows {location}" (e.g., "best grateful dead shows colorado")

**Expected Traffic Increase:** +30-50% from venue-specific queries

---

## 🔧 Implementation Steps

### Step 1: Add MusicEvent Schema to Album Pages (90 min)

**File:** `frontend/app/artists/[slug]/album/[album]/page.tsx`

Add Event schema alongside existing MusicAlbum schema:

```typescript
import { formatDuration } from '@/lib/formatDuration';

export default async function AlbumPage({ params }) {
  const { slug, album: albumSlug } = await params;
  const album = await fetchCategory(albumSlug);
  const tracks = await fetchTracksInCategory(album.entity_id);
  const firstTrack = tracks[0];

  // Existing MusicAlbum schema (from CARD-3)
  const musicAlbumSchema = { /* ... */ };

  // NEW: Add MusicEvent schema for local SEO
  const musicEventSchema = {
    '@context': 'https://schema.org',
    '@type': 'MusicEvent',
    name: `${artistName} Live at ${firstTrack.show_venue}`,
    startDate: firstTrack.show_date,  // ISO 8601: "1977-05-08"
    location: {
      '@type': 'Place',
      name: firstTrack.show_venue,  // e.g., "Barton Hall - Cornell University"
      address: {
        '@type': 'PostalAddress',
        addressLocality: extractCity(firstTrack.show_location),  // "Ithaca"
        addressRegion: extractState(firstTrack.show_location),   // "NY"
        addressCountry: 'US',
      },
      // Optional: Add geocoordinates if available
      geo: firstTrack.venue_lat && firstTrack.venue_lon ? {
        '@type': 'GeoCoordinates',
        latitude: firstTrack.venue_lat,
        longitude: firstTrack.venue_lon,
      } : undefined,
    },
    performer: {
      '@type': 'MusicGroup',
      name: artistName,
      url: `https://8pm.fm/artists/${slug}`,
    },
    recordedIn: {
      '@type': 'CreativeWork',
      name: firstTrack.show_name,
      url: `https://8pm.fm/artists/${slug}/album/${albumSlug}`,
    },
    eventStatus: 'https://schema.org/EventScheduled',  // Historical event
    eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
    offers: {
      '@type': 'Offer',
      price: 0,
      priceCurrency: 'USD',
      availability: 'https://schema.org/InStock',
      url: `https://8pm.fm/artists/${slug}/album/${albumSlug}`,
    },
  };

  const breadcrumbSchema = { /* ... from CARD-3 */ };

  // Combine with @graph
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicAlbumSchema, musicEventSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      {/* Rest of component */}
    </>
  );
}

// Helper functions to parse location string
function extractCity(location: string): string {
  // "Ithaca, NY" → "Ithaca"
  return location?.split(',')[0]?.trim() || '';
}

function extractState(location: string): string {
  // "Ithaca, NY" → "NY"
  return location?.split(',')[1]?.trim() || '';
}
```

---

### Step 2: Enhance Place Schema with Venue Details (60 min)

**Optional Enhancement:** If you want to add venue geocoordinates, create a venue mapping file:

**File:** `frontend/lib/venues.ts`

```typescript
interface Venue {
  name: string;
  city: string;
  state: string;
  country: string;
  lat: number;
  lon: number;
  aliases: string[];  // Venue name variations
}

// Top jam band venues
export const VENUE_DATABASE: Record<string, Venue> = {
  'red-rocks': {
    name: 'Red Rocks Amphitheatre',
    city: 'Morrison',
    state: 'CO',
    country: 'US',
    lat: 39.6654,
    lon: -105.2057,
    aliases: ['Red Rocks', 'Red Rocks Amphitheater', 'Morrison'],
  },
  'msg': {
    name: 'Madison Square Garden',
    city: 'New York',
    state: 'NY',
    country: 'US',
    lat: 40.7505,
    lon: -73.9934,
    aliases: ['Madison Square Garden', 'MSG', 'The Garden'],
  },
  'fillmore': {
    name: 'The Fillmore',
    city: 'San Francisco',
    state: 'CA',
    country: 'US',
    lat: 37.7833,
    lon: -122.4333,
    aliases: ['Fillmore', 'The Fillmore', 'Fillmore Auditorium'],
  },
  // Add more venues as needed
};

export function getVenueDetails(venueName: string): Venue | null {
  const normalized = venueName?.toLowerCase().trim();

  for (const [key, venue] of Object.entries(VENUE_DATABASE)) {
    if (venue.aliases.some(alias => normalized.includes(alias.toLowerCase()))) {
      return venue;
    }
  }

  return null;
}
```

**Update album page:**

```typescript
const venueDetails = getVenueDetails(firstTrack.show_venue);

const musicEventSchema = {
  // ... existing fields
  location: venueDetails ? {
    '@type': 'Place',
    name: venueDetails.name,
    address: {
      '@type': 'PostalAddress',
      addressLocality: venueDetails.city,
      addressRegion: venueDetails.state,
      addressCountry: venueDetails.country,
    },
    geo: {
      '@type': 'GeoCoordinates',
      latitude: venueDetails.lat,
      longitude: venueDetails.lon,
    },
  } : {
    '@type': 'Place',
    name: firstTrack.show_venue,
    address: firstTrack.show_location,
  },
};
```

---

### Step 3: Test Venue Schema (30 min)

**Validation:**
1. Visit album page: `http://localhost:3001/artists/grateful-dead/album/1977-05-08`
2. View source (Ctrl+U)
3. Find `<script type="application/ld+json">`
4. Verify MusicEvent schema is present
5. Test with [Google Rich Results Test](https://search.google.com/test/rich-results)

**Expected Output:**

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "MusicAlbum",
      "name": "Live at Barton Hall - Cornell University"
    },
    {
      "@type": "MusicEvent",
      "name": "Grateful Dead Live at Barton Hall - Cornell University",
      "startDate": "1977-05-08",
      "location": {
        "@type": "Place",
        "name": "Barton Hall - Cornell University",
        "address": {
          "@type": "PostalAddress",
          "addressLocality": "Ithaca",
          "addressRegion": "NY",
          "addressCountry": "US"
        }
      }
    }
  ]
}
```

---

## 🧪 Testing Checklist

### Manual Testing

- [ ] View source on album page shows MusicEvent schema
- [ ] Google Rich Results Test validates with no errors
- [ ] Venue name, city, state parsed correctly from show_location
- [ ] Multiple album pages (different venues) all have Event schema

### Search Console (After Indexing)

- [ ] Pages with Event markup appear in Performance report
- [ ] Event rich results impressions show in Search Console
- [ ] Queries like "{artist} {venue}" start appearing

---

## 🎯 Phase 2: Venue Landing Pages (Optional)

**If you want to maximize local SEO, create venue pages:**

**File:** `frontend/app/venues/[slug]/page.tsx`

```typescript
export default async function VenuePage({ params }: { params: { slug: string } }) {
  const { slug } = await params;
  const venueDetails = VENUE_DATABASE[slug];
  const shows = await fetchShowsAtVenue(venueDetails.name);

  return (
    <div>
      <h1>{venueDetails.name}</h1>
      <p>{venueDetails.city}, {venueDetails.state}</p>

      <h2>All Shows at {venueDetails.name}</h2>
      <ul>
        {shows.map(show => (
          <li key={show.id}>
            <a href={`/artists/${show.artistSlug}/album/${show.albumSlug}`}>
              {show.artistName} - {show.date}
            </a>
          </li>
        ))}
      </ul>
    </div>
  );
}
```

**Benefits:**
- Appear in "concerts at {venue}" searches
- Cross-linking for crawl depth
- Venue-specific authority

**Time Estimate:** +4-6 hours

---

## 📊 Expected Results

### Week 1-2 (After Indexing)
- Event markup detected by Google
- Pages eligible for Event rich results

### Month 1
- Impressions for venue-specific queries
- "Events near me" (historical) may appear for local searches

### Month 3
- Top 3 rankings for "{artist} {venue}" queries
- Knowledge panels linking concerts to venues
- Featured snippets for "best {artist} shows"

---

## 🐛 Troubleshooting

**Issue:** Google Rich Results Test shows "Missing field: location.address"

**Solution:** Ensure `show_location` field is populated in Magento. If null, use venue name only:

```typescript
address: firstTrack.show_location || firstTrack.show_venue
```

**Issue:** Venue name too generic ("Theater")

**Solution:** Prepend city: `${venueDetails.city} ${venueName}`

**Issue:** Historical events not showing in "Events" tab

**Solution:** This is expected. Historical events appear in regular search, not the Events tab (which is for upcoming events).

---

## 📚 References

- [MusicEvent - Schema.org](https://schema.org/MusicEvent)
- [Place - Schema.org](https://schema.org/Place)
- [Event Structured Data | Google Search Central](https://developers.google.com/search/docs/appearance/structured-data/event)
- [GeoCoordinates - Schema.org](https://schema.org/GeoCoordinates)

---

## ✋ Next Steps

After implementing venue schema, monitor in Google Search Console:
1. Navigate to **Performance** report
2. Filter by pages with Event markup
3. Track impressions for venue-specific queries
4. Identify top-performing venues and expand venue database

**High-Value Venues to Prioritize:**
1. Red Rocks Amphitheatre (CO)
2. Madison Square Garden (NY)
3. The Fillmore (SF)
4. Gorge Amphitheatre (WA)
5. Radio City Music Hall (NY)
6. Alpine Valley (WI)
7. Deer Creek / Ruoff (IN)
8. Shoreline Amphitheatre (CA)
9. Bonnaroo (TN)
10. Lockn' Festival (VA)
