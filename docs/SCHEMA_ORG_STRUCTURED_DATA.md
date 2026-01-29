# Schema.org Structured Data for 8PM Concert Archive

Complete guide to implementing Schema.org JSON-LD structured data for the live music concert archive platform.

## Table of Contents

1. [Overview](#overview)
2. [Individual Track (MusicRecording)](#individual-track-musicrecording)
3. [Concert Event (MusicEvent)](#concert-event-musicevent)
4. [Album/Show Collection (MusicAlbum)](#albumshow-collection-musicalbum)
5. [Artist Page (MusicGroup)](#artist-page-musicgroup)
6. [Breadcrumb Navigation](#breadcrumb-navigation)
7. [Audio Streaming (AudioObject)](#audio-streaming-audioobject)
8. [Aggregate Ratings](#aggregate-ratings)
9. [Implementation Strategy](#implementation-strategy)
10. [SEO Benefits](#seo-benefits)

---

## Overview

Schema.org structured data helps search engines understand the content and context of your live music archive. This improves discoverability through rich snippets, knowledge panels, and enhanced search results.

**Key Schema Types for 8PM:**
- **MusicRecording** - Individual tracks from concerts
- **MusicEvent** - Live concert performances
- **MusicAlbum** - Show/album collections (each concert is like a "live album")
- **MusicGroup** - Artists/bands with member lineups
- **Person** - Individual band members
- **AudioObject** - Streaming audio files (MP3, FLAC)
- **BreadcrumbList** - Site navigation hierarchy
- **AggregateRating** - Archive.org ratings and reviews

---

## Individual Track (MusicRecording)

Represents a single track from a concert recording.

### Example: Grateful Dead - Fire on the Mountain (1977-05-08)

```json
{
  "@context": "https://schema.org",
  "@type": "MusicRecording",
  "@id": "https://8pm.local/archive-a1b2c3d4e5f6.html",
  "name": "Fire on the Mountain",
  "alternateName": "gd1977-05-08d1t01",
  "description": "Fire on the Mountain performed live at Barton Hall, Cornell University on May 8, 1977. Recorded by Betty Cantor-Jackson.",
  "duration": "PT12M34S",
  "isrcCode": null,
  "recordingOf": {
    "@type": "MusicComposition",
    "name": "Fire on the Mountain",
    "composer": {
      "@type": "Person",
      "name": "Mickey Hart"
    }
  },
  "byArtist": {
    "@type": "MusicGroup",
    "@id": "https://8pm.local/grateful-dead",
    "name": "Grateful Dead"
  },
  "inAlbum": {
    "@type": "MusicAlbum",
    "@id": "https://8pm.local/grateful-dead/1977-05-08",
    "name": "Grateful Dead - 1977-05-08 - Barton Hall, Cornell University"
  },
  "audio": {
    "@type": "AudioObject",
    "@id": "https://archive.org/download/gd1977-05-08/gd1977-05-08d1t01.mp3",
    "contentUrl": "https://archive.org/download/gd1977-05-08/gd1977-05-08d1t01.mp3",
    "encodingFormat": "audio/mpeg",
    "bitrate": "320 kbps",
    "duration": "PT12M34S"
  },
  "datePublished": "2005-03-15",
  "copyrightYear": 1977,
  "copyrightHolder": {
    "@type": "Organization",
    "name": "Grateful Dead Productions"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "bestRating": "5",
    "ratingCount": "342"
  },
  "interactionStatistic": {
    "@type": "InteractionCounter",
    "interactionType": "https://schema.org/DownloadAction",
    "userInteractionCount": 15234
  }
}
```

### Product Attribute Mapping

| Schema.org Property | Magento Attribute | Notes |
|---------------------|-------------------|-------|
| `name` | `title` | Track title |
| `duration` | `length` | Convert to ISO 8601 (PT12M34S) |
| `byArtist.name` | `archive_collection` or `artist` | Artist name |
| `inAlbum.name` | `show_name` | Show/album name |
| `datePublished` | `show_pub_date` | Archive.org publish date |
| `aggregateRating.ratingValue` | `archive_avg_rating` | Average rating |
| `aggregateRating.ratingCount` | `archive_num_reviews` | Review count |
| `interactionStatistic.userInteractionCount` | `archive_downloads` | Total downloads |
| `audio.contentUrl` | `song_url` | Streaming URL |

---

## Concert Event (MusicEvent)

Represents the live performance event that was recorded.

### Example: Grateful Dead at Cornell 1977

```json
{
  "@context": "https://schema.org",
  "@type": "MusicEvent",
  "@id": "https://8pm.local/grateful-dead/1977-05-08",
  "name": "Grateful Dead at Barton Hall, Cornell University",
  "description": "Legendary Grateful Dead concert from May 8, 1977 at Cornell University. Often cited as one of the greatest shows in the band's history.",
  "startDate": "1977-05-08T20:00:00-05:00",
  "endDate": "1977-05-08T23:30:00-05:00",
  "eventStatus": "https://schema.org/EventScheduled",
  "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
  "location": {
    "@type": "Place",
    "name": "Barton Hall, Cornell University",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Ithaca",
      "addressRegion": "NY",
      "addressCountry": "US"
    }
  },
  "performer": {
    "@type": "MusicGroup",
    "@id": "https://8pm.local/grateful-dead",
    "name": "Grateful Dead",
    "member": [
      {
        "@type": "OrganizationRole",
        "member": {
          "@type": "Person",
          "name": "Jerry Garcia"
        },
        "roleName": ["guitar", "lead vocals"]
      },
      {
        "@type": "OrganizationRole",
        "member": {
          "@type": "Person",
          "name": "Bob Weir"
        },
        "roleName": ["rhythm guitar", "vocals"]
      }
    ]
  },
  "recordedIn": {
    "@type": "Event",
    "name": "Concert Recording",
    "recordedBy": {
      "@type": "Person",
      "name": "Betty Cantor-Jackson"
    }
  },
  "workPerformed": [
    {
      "@type": "MusicComposition",
      "name": "Fire on the Mountain"
    },
    {
      "@type": "MusicComposition",
      "name": "Scarlet Begonias"
    }
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "bestRating": "5",
    "ratingCount": "342"
  }
}
```

### Product Attribute Mapping

| Schema.org Property | Magento Attribute | Notes |
|---------------------|-------------------|-------|
| `name` | `show_name` | Concert name |
| `startDate` | `show_date` | Performance date |
| `location.name` | `show_venue` | Venue name |
| `recordedIn.recordedBy.name` | `show_taper` | Taper/source |
| `description` | `notes` | Show notes |

---

## Album/Show Collection (MusicAlbum)

Each concert recording is treated as a "live album" - a collection of tracks from a single performance.

### Example: Grateful Dead 1977-05-08 (Show as Album)

```json
{
  "@context": "https://schema.org",
  "@type": "MusicAlbum",
  "@id": "https://8pm.local/grateful-dead/1977-05-08",
  "name": "Grateful Dead - 1977-05-08 - Barton Hall, Cornell University",
  "alternateName": "Cornell 5/8/77",
  "albumProductionType": "https://schema.org/LiveAlbum",
  "albumReleaseType": "https://schema.org/AlbumRelease",
  "byArtist": {
    "@type": "MusicGroup",
    "@id": "https://8pm.local/grateful-dead",
    "name": "Grateful Dead"
  },
  "datePublished": "2005-03-15",
  "numTracks": 18,
  "genre": ["Rock", "Jam Band", "Psychedelic Rock"],
  "recordLabel": {
    "@type": "Organization",
    "name": "Internet Archive"
  },
  "image": "https://8pm.local/media/catalog/category/gd1977-05-08_itemimage.jpg",
  "track": [
    {
      "@type": "MusicRecording",
      "@id": "https://8pm.local/archive-a1b2c3d4e5f6.html",
      "position": 1,
      "name": "New Minglewood Blues",
      "duration": "PT6M45S"
    },
    {
      "@type": "MusicRecording",
      "@id": "https://8pm.local/archive-b2c3d4e5f6g7.html",
      "position": 2,
      "name": "Loser",
      "duration": "PT7M12S"
    }
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "bestRating": "5",
    "ratingCount": "342"
  },
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD",
    "availability": "https://schema.org/InStock",
    "url": "https://8pm.local/grateful-dead/1977-05-08"
  }
}
```

### Category Attribute Mapping

| Schema.org Property | Category Attribute | Notes |
|---------------------|-------------------|-------|
| `name` | Category name | Show name |
| `image` | `wikipedia_artwork_url` | Album artwork |
| `albumProductionType` | Fixed value | Always "LiveAlbum" |
| `numTracks` | Calculated | Count products in category |
| `genre` | `band_genres` | Comma-separated list |

---

## Artist Page (MusicGroup)

Represents the band/artist with member lineup, biography, and statistics.

### Example: Phish

```json
{
  "@context": "https://schema.org",
  "@type": "MusicGroup",
  "@id": "https://8pm.local/phish",
  "name": "Phish",
  "alternateName": ["Phish", "The Phish"],
  "description": "Phish is an American rock band formed in Burlington, Vermont in 1983. Known for their musical improvisation, extended jams, and dedicated fan base.",
  "genre": ["Rock", "Jam Band", "Progressive Rock", "Jazz Fusion"],
  "foundingDate": "1983",
  "foundingLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Burlington",
      "addressRegion": "VT",
      "addressCountry": "US"
    }
  },
  "image": "https://8pm.local/media/catalog/category/phish-band-photo.jpg",
  "logo": "https://8pm.local/media/catalog/category/phish-logo.png",
  "sameAs": [
    "https://phish.com",
    "https://www.facebook.com/phish",
    "https://twitter.com/phish",
    "https://www.instagram.com/phish",
    "https://en.wikipedia.org/wiki/Phish",
    "https://www.wikidata.org/wiki/Q639739",
    "https://musicbrainz.org/artist/2776ace5-284c-4559-87f0-d47a88e1f4d4"
  ],
  "url": "https://8pm.local/phish",
  "member": [
    {
      "@type": "OrganizationRole",
      "member": {
        "@type": "Person",
        "name": "Trey Anastasio",
        "sameAs": "https://en.wikipedia.org/wiki/Trey_Anastasio"
      },
      "startDate": "1983",
      "roleName": ["guitar", "lead vocals"]
    },
    {
      "@type": "OrganizationRole",
      "member": {
        "@type": "Person",
        "name": "Page McConnell"
      },
      "startDate": "1985",
      "roleName": ["keyboards", "vocals"]
    },
    {
      "@type": "OrganizationRole",
      "member": {
        "@type": "Person",
        "name": "Mike Gordon"
      },
      "startDate": "1983",
      "roleName": ["bass guitar", "vocals"]
    },
    {
      "@type": "OrganizationRole",
      "member": {
        "@type": "Person",
        "name": "Jon Fishman"
      },
      "startDate": "1983",
      "roleName": ["drums", "vocals"]
    }
  ],
  "album": [
    {
      "@type": "MusicAlbum",
      "@id": "https://8pm.local/phish/1994-12-31",
      "name": "Phish - 1994-12-31 - Boston Garden"
    },
    {
      "@type": "MusicAlbum",
      "@id": "https://8pm.local/phish/1997-11-22",
      "name": "Phish - 1997-11-22 - Hampton Coliseum"
    }
  ],
  "track": [
    {
      "@type": "MusicRecording",
      "name": "You Enjoy Myself"
    },
    {
      "@type": "MusicRecording",
      "name": "Tweezer"
    }
  ],
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.7",
    "bestRating": "5",
    "ratingCount": "8453"
  }
}
```

### Category Attribute Mapping

| Schema.org Property | Category Attribute | Notes |
|---------------------|-------------------|-------|
| `name` | Category name | Artist name |
| `description` | `band_extended_bio` | Wikipedia biography |
| `foundingDate` | `band_formation_date` | Year formed |
| `foundingLocation` | `band_origin_location` | Parse city/state/country |
| `genre` | `band_genres` | Comma-separated list |
| `sameAs[0]` | `band_official_website` | Official website |
| `sameAs[1]` | `band_facebook` | Facebook URL |
| `sameAs[2]` | `band_twitter` | Twitter URL |
| `sameAs[3]` | `band_instagram` | Instagram URL |
| `member` | Database or API | Member lineup data |

---

## Breadcrumb Navigation

Helps search engines understand site hierarchy and can display breadcrumb trails in search results.

### Example: Track Page Breadcrumbs

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://8pm.local/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Library",
      "item": "https://8pm.local/library"
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Grateful Dead",
      "item": "https://8pm.local/grateful-dead"
    },
    {
      "@type": "ListItem",
      "position": 4,
      "name": "1977-05-08 - Barton Hall, Cornell University",
      "item": "https://8pm.local/grateful-dead/1977-05-08"
    },
    {
      "@type": "ListItem",
      "position": 5,
      "name": "Fire on the Mountain"
    }
  ]
}
```

### Typical Breadcrumb Hierarchies

**For Track Page:**
```
Home > Library > Artist > Show > Track
```

**For Show Page:**
```
Home > Library > Artist > Show
```

**For Artist Page:**
```
Home > Library > Artist
```

**For Search Results:**
```
Home > Search Results > {query}
```

---

## Audio Streaming (AudioObject)

Represents the actual audio file that can be streamed or downloaded.

### Example: MP3 Audio Stream

```json
{
  "@context": "https://schema.org",
  "@type": "AudioObject",
  "@id": "https://archive.org/download/gd1977-05-08/gd1977-05-08d1t01.mp3",
  "name": "Fire on the Mountain (MP3)",
  "contentUrl": "https://archive.org/download/gd1977-05-08/gd1977-05-08d1t01.mp3",
  "encodingFormat": "audio/mpeg",
  "bitrate": "320 kbps",
  "duration": "PT12M34S",
  "contentSize": "29.8 MB",
  "uploadDate": "2005-03-15",
  "associatedMedia": [
    {
      "@type": "AudioObject",
      "name": "Fire on the Mountain (FLAC)",
      "contentUrl": "https://archive.org/download/gd1977-05-08/gd1977-05-08d1t01.flac",
      "encodingFormat": "audio/flac",
      "bitrate": "1411 kbps",
      "duration": "PT12M34S"
    }
  ]
}
```

### Product Attribute Mapping

| Schema.org Property | Magento Attribute | Notes |
|---------------------|-------------------|-------|
| `contentUrl` | `song_url` | Full streaming URL |
| `duration` | `length` | Convert to ISO 8601 |
| `encodingFormat` | Fixed or derived | "audio/mpeg" for MP3 |
| `uploadDate` | `show_pub_date` | Archive.org publish date |

### Multiple Audio Formats

For shows with multiple formats (MP3, FLAC, OGG), you can provide multiple `AudioObject` instances:

```json
{
  "@type": "MusicRecording",
  "audio": [
    {
      "@type": "AudioObject",
      "contentUrl": "https://archive.org/download/show/track.mp3",
      "encodingFormat": "audio/mpeg",
      "bitrate": "320 kbps"
    },
    {
      "@type": "AudioObject",
      "contentUrl": "https://archive.org/download/show/track.flac",
      "encodingFormat": "audio/flac",
      "bitrate": "1411 kbps"
    }
  ]
}
```

---

## Aggregate Ratings

Archive.org ratings and reviews can enhance search result snippets.

### Example: Rating with Reviews

```json
{
  "@type": "MusicRecording",
  "name": "Dark Star",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "bestRating": "5",
    "worstRating": "1",
    "ratingCount": "342",
    "reviewCount": "89"
  }
}
```

### Product Attribute Mapping

| Schema.org Property | Magento Attribute | Notes |
|---------------------|-------------------|-------|
| `ratingValue` | `archive_avg_rating` | Average rating (e.g., "4.8") |
| `ratingCount` | `archive_num_reviews` | Number of reviews |
| `bestRating` | Fixed value | Always "5" for Archive.org |
| `worstRating` | Fixed value | Always "1" |

### Requirements

Per Schema.org spec, **at least one of `ratingCount` or `reviewCount` is required** for valid markup.

---

## Implementation Strategy

### 1. PHP Helper Class

Create a helper class to generate Schema.org JSON-LD for different page types:

```php
<?php
namespace ArchiveDotOrg\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class SchemaOrgHelper extends AbstractHelper
{
    /**
     * Generate MusicRecording schema for track product
     */
    public function getMusicRecordingSchema($product): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'MusicRecording',
            '@id' => $product->getProductUrl(),
            'name' => $product->getData('title') ?: $product->getName(),
            'duration' => $this->convertDurationToISO8601($product->getData('length')),
            'byArtist' => [
                '@type' => 'MusicGroup',
                'name' => $product->getAttributeText('archive_collection')
            ],
            'audio' => $this->getAudioObjectSchema($product),
            'aggregateRating' => $this->getAggregateRatingSchema($product)
        ];
    }

    /**
     * Convert duration from seconds or MM:SS to ISO 8601 format
     */
    private function convertDurationToISO8601(?string $length): ?string
    {
        if (!$length) {
            return null;
        }

        // If already in seconds
        if (is_numeric($length)) {
            $minutes = floor($length / 60);
            $seconds = $length % 60;
            return sprintf('PT%dM%dS', $minutes, $seconds);
        }

        // If in MM:SS or HH:MM:SS format
        $parts = explode(':', $length);
        if (count($parts) === 2) {
            return sprintf('PT%dM%dS', (int)$parts[0], (int)$parts[1]);
        } elseif (count($parts) === 3) {
            return sprintf('PT%dH%dM%dS', (int)$parts[0], (int)$parts[1], (int)$parts[2]);
        }

        return null;
    }

    /**
     * Generate AudioObject schema
     */
    private function getAudioObjectSchema($product): ?array
    {
        $songUrl = $product->getData('song_url');
        if (!$songUrl) {
            return null;
        }

        return [
            '@type' => 'AudioObject',
            'contentUrl' => $songUrl,
            'encodingFormat' => 'audio/mpeg',
            'duration' => $this->convertDurationToISO8601($product->getData('length'))
        ];
    }

    /**
     * Generate AggregateRating schema
     */
    private function getAggregateRatingSchema($product): ?array
    {
        $rating = $product->getData('archive_avg_rating');
        $reviews = $product->getData('archive_num_reviews');

        if (!$rating || !$reviews) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => (string)$rating,
            'bestRating' => '5',
            'ratingCount' => (int)$reviews
        ];
    }

    /**
     * Generate BreadcrumbList schema
     */
    public function getBreadcrumbSchema(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $position => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $crumb['label']
            ];
            if (isset($crumb['link'])) {
                $item['item'] = $crumb['link'];
            }
            $items[] = $item;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }
}
```

### 2. Block Method

Add method to render JSON-LD in page head:

```php
<?php
namespace ArchiveDotOrg\Core\Block;

use Magento\Framework\View\Element\Template;

class SchemaOrg extends Template
{
    private \ArchiveDotOrg\Core\Helper\SchemaOrgHelper $schemaHelper;

    public function __construct(
        Template\Context $context,
        \ArchiveDotOrg\Core\Helper\SchemaOrgHelper $schemaHelper,
        array $data = []
    ) {
        $this->schemaHelper = $schemaHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get JSON-LD script tag
     */
    public function getJsonLd(): string
    {
        $schema = $this->getData('schema');
        if (!$schema) {
            return '';
        }

        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
```

### 3. Template Integration

In product page template (`catalog_product_view.xml`):

```xml
<referenceContainer name="head.additional">
    <block class="ArchiveDotOrg\Core\Block\SchemaOrg" name="schema.org.product" template="ArchiveDotOrg_Core::schema-org.phtml">
        <arguments>
            <argument name="schema" xsi:type="object">ProductSchemaDataProvider</argument>
        </arguments>
    </block>
</referenceContainer>
```

In `schema-org.phtml`:

```php
<?= /* @noEscape */ $block->getJsonLd() ?>
```

### 4. Next.js Implementation

For the Next.js frontend, use `next/head`:

```tsx
import Head from 'next/head';

export function TrackSchemaOrg({ product }: { product: Product }) {
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'MusicRecording',
    '@id': `https://8pm.local/${product.url_key}.html`,
    name: product.title || product.name,
    duration: convertDurationToISO8601(product.length),
    byArtist: {
      '@type': 'MusicGroup',
      name: product.archive_collection
    },
    audio: {
      '@type': 'AudioObject',
      contentUrl: product.song_url,
      encodingFormat: 'audio/mpeg'
    },
    aggregateRating: product.archive_avg_rating ? {
      '@type': 'AggregateRating',
      ratingValue: product.archive_avg_rating,
      bestRating: '5',
      ratingCount: product.archive_num_reviews
    } : undefined
  };

  return (
    <Head>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
    </Head>
  );
}
```

---

## SEO Benefits

### Rich Snippets

Properly implemented Schema.org markup can result in:

1. **Music Player Snippets** - Play button directly in search results
2. **Star Ratings** - Visual rating stars in search listings
3. **Event Information** - Date, venue, location in search results
4. **Breadcrumbs** - Navigation path shown in search results
5. **Knowledge Panels** - Artist info panels with band members, discography
6. **Audio Action** - "Listen" action in Google Assistant and voice search

### Search Engine Understanding

Schema.org helps search engines understand:

- **Content Type** - These are music recordings, not blog posts
- **Relationships** - How tracks, albums, and artists relate
- **Entities** - Who performed, where, when
- **Quality Signals** - Ratings, downloads, reviews

### Voice Search Optimization

Structured data improves voice search results:

- "Play Fire on the Mountain by Grateful Dead"
- "When did Phish play at Madison Square Garden?"
- "What's the most popular Grateful Dead show?"

---

## Testing & Validation

### Google Rich Results Test

Test your markup at: https://search.google.com/test/rich-results

### Schema.org Validator

Validate syntax at: https://validator.schema.org/

### Example Test URLs

Once implemented, test these page types:

1. **Track page**: `https://8pm.local/archive-{sha1}.html`
2. **Show page**: `https://8pm.local/grateful-dead/1977-05-08`
3. **Artist page**: `https://8pm.local/phish`
4. **Search results**: `https://8pm.local/search?q=dark+star`

---

## Priority Implementation Order

1. **BreadcrumbList** - Easy win, helps all pages
2. **MusicRecording** - Core content type for tracks
3. **AggregateRating** - Enhances search snippets immediately
4. **MusicAlbum** - Shows as collections
5. **MusicGroup** - Artist pages with member data
6. **MusicEvent** - Historical performance data
7. **AudioObject** - Multi-format streaming details

---

## References

- [Schema.org MusicRecording](https://schema.org/MusicRecording)
- [Schema.org MusicEvent](https://schema.org/MusicEvent)
- [Schema.org MusicAlbum](https://schema.org/MusicAlbum)
- [Schema.org MusicGroup](https://schema.org/MusicGroup)
- [Schema.org AudioObject](https://schema.org/AudioObject)
- [Schema.org BreadcrumbList](https://schema.org/BreadcrumbList)
- [Schema.org AggregateRating](https://schema.org/AggregateRating)
- [Google Search: Music Carousel](https://developers.google.com/search/docs/appearance/structured-data/music)
- [Google Search: Event Structured Data](https://developers.google.com/search/docs/appearance/structured-data/event)

---

## Sources

- [MusicEvent - Schema.org Type](https://schema.org/MusicEvent)
- [MusicRecording - Schema.org Type](https://schema.org/MusicRecording)
- [AudioObject - Schema.org Type](https://schema.org/AudioObject)
- [MusicGroup - Schema.org Type](https://schema.org/MusicGroup)
- [BreadcrumbList - Schema.org Type](https://schema.org/BreadcrumbList)
- [How To Add Breadcrumb (BreadcrumbList) Markup | Google Search Central](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb)
- [AggregateRating - Schema.org Type](https://schema.org/AggregateRating)
- [Review Snippet (Review, AggregateRating) Structured Data | Google Search Central](https://developers.google.com/search/docs/appearance/structured-data/review-snippet)
- [Schema Markup for Musicians: Boost Your Search Visibility](https://inclassics.com/blog/seo-for-musicians-schema-markup)
- [How to optimize your band schema for SEO | Bandzoogle Blog](https://bandzoogle.com/blog/how-to-optimize-your-band-schema)
