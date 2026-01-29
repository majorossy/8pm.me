# CARD-7B: Content Enrichment & SEO

**Priority:** 🟡 MEDIUM - Improves dwell time and keyword relevance
**Estimated Time:** 4-6 hours
**Assignee:** Frontend Developer + Content Writer
**Dependencies:** CARD-2 (Frontend Metadata) recommended

---

## 📋 Objective

Enhance content depth and quality across artist pages, album pages, and add FAQ schema for voice search optimization. This moves beyond technical SEO into content SEO.

---

## ✅ Acceptance Criteria

- [ ] Artist bios expanded to 300+ words (where available)
- [ ] Related artists sections added (internal linking)
- [ ] Keyword-rich headings implemented
- [ ] FAQ schema added for common queries
- [ ] Show notes/reviews enhanced (user-generated content)

---

## 🔧 Implementation Steps

### Step 1: Expand Artist Bios (60 min)

**Current:** `artist.band_extended_bio` may be short or missing
**Goal:** Ensure all major artists have 300+ word bios

**File:** `frontend/app/artists/[slug]/page.tsx`

```typescript
<section>
  <h2 className="text-2xl font-bold mb-4">
    About {artist.name} - Biography & History
  </h2>
  <div className="prose prose-invert max-w-none">
    {artist.band_extended_bio && artist.band_extended_bio.length > 300 ? (
      <p>{artist.band_extended_bio}</p>
    ) : (
      <>
        <p>{artist.band_extended_bio}</p>
        <p className="text-gray-400 italic">
          Explore {artist.band_total_shows || 0} live recordings from {artist.name} on EIGHTPM.
          Stream high-quality concert recordings from Archive.org, featuring legendary performances
          spanning {artist.band_years_active || 'decades'} of live music history.
        </p>
      </>
    )}
  </div>
</section>
```

**Keywords to include:**
- "{artist} live recordings"
- "{artist} concert archive"
- "best {artist} shows"
- Venue names (Red Rocks, MSG, etc.)
- Band member names
- Genre keywords (jam band, improvisational rock, etc.)

---

### Step 2: Related Artists Section (90 min)

**Goal:** Internal linking for crawl depth + discovery

**File:** `frontend/components/RelatedArtists.tsx`

```typescript
interface RelatedArtistsProps {
  currentArtist: string;
  genre: string;
}

export function RelatedArtists({ currentArtist, genre }: RelatedArtistsProps) {
  // Manually curated or algorithm-based
  const related = getRelatedArtists(currentArtist, genre);

  return (
    <section className="mt-12">
      <h2 className="text-xl font-bold mb-4">
        If you like {currentArtist}, you might also enjoy:
      </h2>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {related.map(artist => (
          <a
            key={artist.slug}
            href={`/artists/${artist.slug}`}
            className="block p-4 bg-[#2d2a26] rounded hover:bg-[#3d3a36] transition"
          >
            <img
              src={artist.image}
              alt={artist.name}
              className="w-full h-32 object-cover rounded mb-2"
            />
            <p className="font-semibold">{artist.name}</p>
            <p className="text-sm text-gray-400">{artist.showCount} shows</p>
          </a>
        ))}
      </div>
    </section>
  );
}

// Curated relationships (store in database or config file)
const RELATED_ARTISTS = {
  'grateful-dead': ['phish', 'widespread-panic', 'phil-lesh-and-friends', 'dead-and-company'],
  'phish': ['grateful-dead', 'umphrey-s-mcgee', 'string-cheese-incident', 'moe'],
  'widespread-panic': ['grateful-dead', 'gov-t-mule', 'tedeschi-trucks-band', 'string-cheese-incident'],
};
```

---

### Step 3: Add FAQ Schema for Voice Search (90 min)

**Goal:** Capture "how do I..." and "what is..." queries

**File:** `frontend/app/page.tsx` (homepage) or create `/faq` page

```typescript
const faqSchema = {
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  mainEntity: [
    {
      '@type': 'Question',
      name: 'How do I listen to Grateful Dead live recordings for free?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'EIGHTPM streams thousands of Grateful Dead concert recordings for free, sourced from Archive.org. No subscription required. Simply browse to the Grateful Dead artist page and select any show to start listening.',
      },
    },
    {
      '@type': 'Question',
      name: 'What is the best Phish show of all time?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'While opinions vary, Phish\'s December 30, 1997 performance at Madison Square Garden (known as "The Cow Funk Show") is widely considered one of their greatest performances. Other legendary shows include 8/31/2012 (Dick\'s Sporting Goods Park) and 12/31/1995 (Madison Square Garden).',
      },
    },
    {
      '@type': 'Question',
      name: 'Are these concert recordings legal to stream?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Yes! All recordings on EIGHTPM are sourced from Archive.org, which hosts concerts with permission from artists who allow fan taping and trading. Bands like Grateful Dead, Phish, and Widespread Panic have long encouraged fans to record and share their live performances.',
      },
    },
    {
      '@type': 'Question',
      name: 'What is Archive.org and how does taping culture work?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Archive.org is a non-profit digital library that preserves cultural artifacts. The jam band community has a rich "taping culture" where fans record concerts with the artists\' blessing. Tapers use high-quality microphones to create soundboard and audience recordings, which are then shared freely online.',
      },
    },
    {
      '@type': 'Question',
      name: 'Can I download these recordings or only stream them?',
      acceptedAnswer: {
        '@type': 'Answer',
        text: 'Currently, EIGHTPM offers free streaming. For downloads, visit the original Archive.org pages linked from each show. Archive.org provides downloads in multiple formats including MP3, FLAC, and Ogg Vorbis.',
      },
    },
  ],
};
```

**Add FAQ component to homepage:**

```typescript
<StructuredData data={faqSchema} />

<section className="faq mt-12">
  <h2 className="text-3xl font-bold mb-6">Frequently Asked Questions</h2>
  <div className="space-y-6">
    {faqSchema.mainEntity.map((faq, index) => (
      <details key={index} className="bg-[#2d2a26] p-6 rounded">
        <summary className="font-semibold text-lg cursor-pointer">
          {faq.name}
        </summary>
        <p className="mt-4 text-gray-300">{faq.acceptedAnswer.text}</p>
      </details>
    ))}
  </div>
</section>
```

---

### Step 4: Keyword-Rich Headings (30 min)

**Current:** Generic headings like "Albums" or "About"
**Better:** Keyword-rich headings

**Examples:**

```tsx
// ❌ Before
<h1>{artist.name}</h1>
<h2>Albums</h2>
<h2>About</h2>

// ✅ After
<h1>{artist.name} - Live Concert Recordings & Setlists</h1>
<h2>All {artist.name} Shows - Chronological Archive</h2>
<h2>About {artist.name} - Biography, Band Members & History</h2>
```

---

### Step 5: Show Notes & Reviews (60 min, optional)

**Goal:** Add user-generated content (if available)

**File:** `frontend/app/artists/[slug]/album/[album]/page.tsx`

```typescript
<section className="mt-8">
  <h3 className="text-xl font-bold mb-4">Taper Notes</h3>
  <div className="bg-[#2d2a26] p-6 rounded">
    <p className="text-gray-300">
      {firstTrack.notes || 'No taper notes available for this recording.'}
    </p>
    {firstTrack.show_taper && (
      <p className="mt-4 text-sm text-gray-400">
        Recorded by: {firstTrack.show_taper}<br />
        Source: {firstTrack.show_source}<br />
        Lineage: {firstTrack.lineage}
      </p>
    )}
  </div>
</section>

{firstTrack.archive_num_reviews > 0 && (
  <section className="mt-8">
    <h3 className="text-xl font-bold mb-4">
      Community Reviews ({firstTrack.archive_num_reviews})
    </h3>
    <div className="space-y-4">
      {/* Fetch and display reviews from Archive.org */}
    </div>
  </section>
)}
```

---

## 🧪 Testing Checklist

- [ ] Artist bios are at least 250-300 words (for major artists)
- [ ] Related artists links are functional
- [ ] FAQ schema validates with Google Rich Results Test
- [ ] Headings include target keywords
- [ ] Show notes display when available
- [ ] No keyword stuffing (natural language)

---

## 📊 Expected Results

### Week 1-2
- FAQ schema detected by Google
- Eligible for voice search features

### Month 1
- Improved dwell time (users read bios, FAQs)
- Related artists click-through increases crawl depth
- Long-tail keyword rankings improve

### Month 3
- Featured snippets for FAQ queries
- "People Also Ask" appearances
- +15-25% organic traffic from content improvements

---

## 📚 References

- [FAQ Structured Data | Google Search Central](https://developers.google.com/search/docs/appearance/structured-data/faqpage)
- [Content SEO Best Practices | Moz](https://moz.com/learn/seo/on-page-factors)
- [Internal Linking Guide | Ahrefs](https://ahrefs.com/blog/internal-links-for-seo/)

---

## ✋ Hand-off

This card has a content writing component. Consider:
1. **Phase 1:** Implement technical components (FAQ schema, related artists, headings)
2. **Phase 2:** Hire content writer to expand bios for top 50 artists
3. **Phase 3:** Develop editorial calendar for blog content (see master plan)
