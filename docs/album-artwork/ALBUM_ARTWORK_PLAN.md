# Studio Album Artwork Integration - Implementation Plan

## What We're Building

Add studio album artwork from Wikipedia to display alongside live recordings from Archive.org. This gives users context about the artists' studio work while browsing live shows.

**Example:** When viewing Grateful Dead live shows from 1977, display their studio albums like "American Beauty" and "Workingman's Dead" with official artwork in a sidebar or dedicated section.

**Current Status:** ✅ **FUNCTIONAL** - Using Wikipedia API for artwork (MusicBrainz fallback available)

---

## ✅ What's Already Done (2026-01-28)

### Backend Services Created
- ✅ `AlbumArtworkServiceInterface.php` - Service contract
- ✅ `WikipediaClient.php` - Wikipedia API client for artwork
- ✅ `MusicBrainzClient.php` - Legacy code (not currently used)
- ✅ `AlbumArtworkService.php` - Main service implementation (uses Wikipedia only)
- ✅ `DownloadAlbumArtCommand.php` - CLI: `bin/magento archive:artwork:download`
- ✅ `di.xml` - Services registered in DI container
- ✅ `db_schema.xml` - Database table created

### Database Schema
```sql
archivedotorg_studio_albums:
  - entity_id (PK)
  - artist_name VARCHAR(255)
  - album_title VARCHAR(255)
  - release_year INT
  - musicbrainz_id VARCHAR(64)
  - artwork_url VARCHAR(512)      -- Direct Cover Art Archive URL
  - cached_image_path VARCHAR(512) -- Local cached file path
  - created_at, updated_at
  UNIQUE(artist_name, album_title)
```

### What Works ✅
- Database table created successfully
- CLI command: `bin/magento archive:artwork:download "Artist" --limit=20`
- Wikipedia API integration working perfectly
- Services compiled and injectable
- Artwork downloads and enriches artist categories
- Code architecture is solid

### Implementation Change: Wikipedia API (2026-01-28)

**Decision:** Switched from MusicBrainz to Wikipedia as primary data source due to SSL connectivity issues.

**Wikipedia API Benefits:**
- ✅ No authentication required
- ✅ No rate limiting concerns
- ✅ Works perfectly from Docker container
- ✅ High-quality album artwork available
- ✅ Reliable and fast

**MusicBrainz Status:** Proxy created but not actively used. AlbumArtworkService uses Wikipedia exclusively.

---

## 🎯 Tomorrow's Plan

### Phase 1: Solve the SSL Issue (Priority #1)

We need to get MusicBrainz API working from the Docker container. Three approaches:

#### Option A: Host-Based Proxy (FASTEST - 30 mins)
**Create a simple Node.js/Python proxy on Mac that forwards requests to MusicBrainz**

```javascript
// proxy.js - Run on Mac at localhost:3333
const express = require('express');
const axios = require('axios');
const app = express();

app.get('/musicbrainz/*', async (req, res) => {
  const mbUrl = 'https://musicbrainz.org' + req.path.replace('/musicbrainz', '');
  const response = await axios.get(mbUrl, { params: req.query });
  res.json(response.data);
});

app.listen(3333, () => console.log('MusicBrainz proxy on :3333'));
```

Then update `MusicBrainzClient.php`:
```php
private const MUSICBRAINZ_API_BASE = 'http://host.docker.internal:3333/musicbrainz/ws/2';
```

**Pros:** Quick, works around SSL completely, non-invasive
**Cons:** Extra process to run, dev-only solution

#### Option B: Update Docker Container SSL Config (MEDIUM - 1-2 hours)
**Fix the container's OpenSSL/curl to work with MusicBrainz's TLS**

Steps:
1. Check MusicBrainz's TLS config: `nmap --script ssl-enum-ciphers -p 443 musicbrainz.org`
2. Update PHP container's OpenSSL config to match
3. May need to rebuild container with specific curl/openssl versions
4. Test against multiple HTTPS endpoints

**Pros:** Proper fix, works for all future HTTPS issues
**Cons:** Time-consuming, may require container rebuild

#### Option C: Batch Pre-Population from Host (FASTEST FOR DATA - 1 hour)
**Run a quick script on Mac to fetch album data and insert directly into MySQL**

```bash
#!/bin/bash
# fetch_albums.sh - Run on Mac
ARTISTS=("Grateful Dead" "Phish" "STS9" "String Cheese Incident")

for artist in "${ARTISTS[@]}"; do
  echo "Fetching $artist..."
  curl -s "https://musicbrainz.org/ws/2/release/?query=artist:\"$artist\"%20AND%20type:album&fmt=json&limit=50" \
    | jq -r '.releases[] | [.title, .id, .date] | @csv' \
    | while IFS=',' read title mbid date; do
        # Insert into DB via bin/mysql
        echo "INSERT INTO archivedotorg_studio_albums ..."
      done
  sleep 1  # MusicBrainz rate limit
done
```

**Pros:** Populates data immediately, bypasses container issue entirely
**Cons:** Not sustainable (can't auto-update), manual process

**RECOMMENDATION: Option A (proxy) for immediate development, then Option B for production**

---

### Phase 2: Test & Populate Album Data (1-2 hours)

Once SSL is working, populate the database:

```bash
# Test with single artist
bin/magento archivedotorg:download-album-art "Grateful Dead" --limit=10

# Check what was found
bin/mysql -e "SELECT artist_name, album_title, release_year, artwork_url FROM archivedotorg_studio_albums LIMIT 10;"

# Populate all configured artists
bin/magento archivedotorg:download-album-art --all --limit=50

# Download and cache images locally (saves ~500KB per album)
bin/magento archivedotorg:download-album-art "Phish" --limit=20
ls -lh var/archivedotorg/artwork/
```

**Expected Results:**
- ~20-50 albums per jam band artist
- Artwork URLs pointing to `https://coverartarchive.org/release/{mbid}/front-500`
- Some albums may not have artwork (community-dependent)
- Local cache fills with JPEG files

**Quality Check:**
```sql
-- Albums with artwork vs without
SELECT
  COUNT(*) as total,
  SUM(CASE WHEN artwork_url IS NOT NULL THEN 1 ELSE 0 END) as with_artwork,
  SUM(CASE WHEN artwork_url IS NULL THEN 1 ELSE 0 END) as without_artwork
FROM archivedotorg_studio_albums;

-- Check a few URLs manually
SELECT artist_name, album_title, artwork_url
FROM archivedotorg_studio_albums
WHERE artwork_url IS NOT NULL
LIMIT 5;
```

---

### Phase 3: GraphQL API Endpoint (2-3 hours)

Add GraphQL queries for frontend to fetch album data.

#### Create GraphQL Schema

**File:** `src/app/code/ArchiveDotOrg/Core/etc/schema.graphqls`

```graphql
type Query {
    studioAlbums(
        artistName: String!
        limit: Int = 50
    ): StudioAlbumsOutput @resolver(class: "ArchiveDotOrg\\Core\\Model\\Resolver\\StudioAlbumsResolver")
}

type StudioAlbumsOutput {
    items: [StudioAlbum]
    total_count: Int
}

type StudioAlbum {
    entity_id: Int
    artist_name: String
    album_title: String
    release_year: Int
    release_date: String
    musicbrainz_id: String
    artwork_url: String
    cached_image_path: String
}
```

#### Create Resolver

**File:** `src/app/code/ArchiveDotOrg/Core/Model/Resolver/StudioAlbumsResolver.php`

```php
<?php
namespace ArchiveDotOrg\Core\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\App\ResourceConnection;

class StudioAlbumsResolver implements ResolverInterface
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource) {
        $this->resource = $resource;
    }

    public function resolve($field, $context, $info, $value = null, $args = null)
    {
        $artistName = $args['artistName'];
        $limit = $args['limit'] ?? 50;

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('archivedotorg_studio_albums');

        $query = $connection->select()
            ->from($table)
            ->where('artist_name = ?', $artistName)
            ->where('artwork_url IS NOT NULL')
            ->order('release_year DESC')
            ->limit($limit);

        $albums = $connection->fetchAll($query);

        return [
            'items' => $albums,
            'total_count' => count($albums)
        ];
    }
}
```

#### Register in di.xml
Already done via auto-discovery, just need the files.

#### Test GraphQL
```bash
# GraphQL query
curl -X POST https://magento.test/graphql \
  -H "Content-Type: application/json" \
  -d '{
    "query": "{ studioAlbums(artistName: \"Grateful Dead\") { items { album_title release_year artwork_url } total_count } }"
  }'
```

---

### Phase 4: Frontend Integration (3-4 hours)

Display studio albums on artist pages in the Next.js frontend.

#### Update GraphQL Queries

**File:** `frontend/graphql/StudioAlbums.graphql` (create new)

```graphql
query GetStudioAlbums($artistName: String!, $limit: Int) {
  studioAlbums(artistName: $artistName, limit: $limit) {
    items {
      entity_id
      album_title
      release_year
      artwork_url
    }
    total_count
  }
}
```

Run codegen:
```bash
cd frontend
npm run codegen
```

#### Create Studio Albums Component

**File:** `frontend/components/StudioAlbums.tsx`

```typescript
import React from 'react';
import { useGetStudioAlbumsQuery } from '../generated/graphql';
import Image from 'next/image';

interface StudioAlbumsProps {
  artistName: string;
  limit?: number;
}

export const StudioAlbums: React.FC<StudioAlbumsProps> = ({
  artistName,
  limit = 20
}) => {
  const { data, loading, error } = useGetStudioAlbumsQuery({
    variables: { artistName, limit }
  });

  if (loading) return <div>Loading albums...</div>;
  if (error) return <div>Error loading albums</div>;
  if (!data?.studioAlbums?.items?.length) return null;

  return (
    <div className="studio-albums">
      <h2 className="text-2xl font-bold mb-4">Studio Albums</h2>
      <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        {data.studioAlbums.items.map((album) => (
          <div key={album.entity_id} className="album-card">
            {album.artwork_url && (
              <img
                src={album.artwork_url}
                alt={album.album_title}
                className="w-full h-auto rounded shadow-lg hover:shadow-xl transition-shadow"
              />
            )}
            <div className="mt-2 text-sm">
              <p className="font-semibold truncate">{album.album_title}</p>
              {album.release_year && (
                <p className="text-gray-400">{album.release_year}</p>
              )}
            </div>
          </div>
        ))}
      </div>
      <p className="text-sm text-gray-500 mt-4">
        Album artwork from <a href="https://coverartarchive.org" target="_blank" className="underline">Cover Art Archive</a>
      </p>
    </div>
  );
};
```

#### Integrate into Artist Page

**File:** `frontend/app/artist/[slug]/page.tsx` (or wherever artist pages are)

```typescript
import { StudioAlbums } from '@/components/StudioAlbums';

// In the artist page component:
<StudioAlbums artistName="Grateful Dead" limit={20} />
```

#### Mobile-First Styling

```css
/* Mobile: 2 columns */
@media (max-width: 768px) {
  .studio-albums .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Tablet: 4 columns */
@media (min-width: 768px) and (max-width: 1024px) {
  .studio-albums .grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* Desktop: 6 columns */
@media (min-width: 1024px) {
  .studio-albums .grid {
    grid-template-columns: repeat(6, 1fr);
  }
}
```

---

### Phase 5: Enhancement & Polish (2-3 hours)

#### Album Detail Modal
Click album artwork to show details:
- Full album title
- Release date
- Track listing (if available from MusicBrainz)
- Link to MusicBrainz page
- Link to purchase (Apple Music, Spotify, Bandcamp)

#### Lazy Loading & Performance
```typescript
// Only load when scrolled into view
import { LazyLoadImage } from 'react-lazy-load-image-component';

<LazyLoadImage
  src={album.artwork_url}
  alt={album.album_title}
  effect="blur"
  threshold={100}
/>
```

#### Caching Strategy
- Browser: Cache artwork URLs for 1 week
- CDN: Add `Cache-Control: public, max-age=604800` for Cover Art Archive URLs
- Database: Consider album data immutable (rarely changes)

#### Error Handling
- Broken image fallback: Generic vinyl record icon
- No albums found: "No studio albums available for this artist"
- API timeout: Graceful degradation (show live recordings only)

---

## 🎨 Design Considerations

### Layout Options

#### Option 1: Sidebar (Spotify-style)
```
┌─────────────────────────────────────┐
│  Artist Name                        │
├───────────┬─────────────────────────┤
│           │  Live Shows             │
│  Studio   │  - 1977-05-08 Cornell  │
│  Albums   │  - 1977-05-09 Buffalo  │
│           │  - 1978-04-11 Duke     │
│  [img]    │                         │
│  [img]    │  (main content)         │
│  [img]    │                         │
│           │                         │
└───────────┴─────────────────────────┘
```

#### Option 2: Top Section (Bandcamp-style)
```
┌─────────────────────────────────────┐
│  Artist Name                        │
├─────────────────────────────────────┤
│  Studio Albums                      │
│  [img] [img] [img] [img] [img]      │
├─────────────────────────────────────┤
│  Live Shows                         │
│  - 1977-05-08 Cornell               │
│  - 1977-05-09 Buffalo               │
│  (main content)                     │
└─────────────────────────────────────┘
```

#### Option 3: Tabbed View
```
┌─────────────────────────────────────┐
│  Artist Name                        │
│  [Live Shows] [Studio Albums]       │
├─────────────────────────────────────┤
│                                     │
│  (selected tab content)             │
│                                     │
└─────────────────────────────────────┘
```

**RECOMMENDATION: Option 1 (Sidebar) for desktop, Option 2 (Top) for mobile**

---

## 🧪 Testing Checklist

### Backend Tests
- [ ] MusicBrainz API connectivity (once SSL fixed)
- [ ] Album search returns results for "Grateful Dead"
- [ ] Artwork URLs are valid (HTTP 200 or 307 redirect)
- [ ] Database inserts with unique constraint (no duplicates)
- [ ] CLI command with --all flag processes multiple artists
- [ ] CLI command with --limit flag respects limit
- [ ] Local caching downloads and stores JPEG files
- [ ] Rate limiting (1 req/sec to MusicBrainz)

### GraphQL Tests
```bash
# Test query in GraphQL playground
https://magento.test/graphql

# Query with variables
{
  studioAlbums(artistName: "Phish", limit: 10) {
    items {
      album_title
      release_year
      artwork_url
    }
    total_count
  }
}

# Test with non-existent artist (should return empty)
{
  studioAlbums(artistName: "Nonexistent Band") {
    total_count
  }
}
```

### Frontend Tests
- [ ] Component renders with data
- [ ] Images load correctly (no broken images)
- [ ] Responsive grid (2/4/6 columns on mobile/tablet/desktop)
- [ ] Loading state shows while fetching
- [ ] Error state shows on GraphQL failure
- [ ] No albums shows appropriate message
- [ ] Attribution link to Cover Art Archive present
- [ ] Hover effects on album cards work
- [ ] Mobile touch targets are 44px minimum

### Cross-Browser Tests
- [ ] Chrome (primary)
- [ ] Safari (Mac/iOS)
- [ ] Firefox
- [ ] Mobile Safari (iPhone)

---

## 📊 Success Metrics

### Data Population Goals
- Grateful Dead: ~13 studio albums (1967-1990)
- Phish: ~15 studio albums (1988-2020)
- STS9: ~10 studio albums (1998-2016)
- String Cheese Incident: ~8 studio albums (1997-2014)
- Overall target: 200+ albums across 20+ artists

### Coverage Expectations
- **With artwork:** 70-85% (MusicBrainz is community-curated)
- **Without artwork:** 15-30% (obscure releases, bootlegs, compilation oddities)
- **False positives:** <5% (wrong album, live album misclassified)

### Performance Targets
- Page load: <2s with 50 albums
- Image load: Progressive (lazy load below fold)
- GraphQL query: <100ms
- MusicBrainz API: ~1s per artist (rate-limited)

---

## 🚀 Future Enhancements (Phase 6+)

### Auto-Linking Live to Studio
Match live show tracks to studio albums:
```sql
-- Example: "Scarlet Begonias" from 1977-05-08 → "Mars Hotel" (1974)
UPDATE catalog_product_entity
SET studio_album_id = 123
WHERE title LIKE '%Scarlet Begonias%'
  AND show_date = '1977-05-08';
```

Display on track detail: "Originally from: Mars Hotel (1974) [artwork]"

### Playlist Generation
"Studio Version" button next to live tracks:
- User clicks "Studio Version" on live track
- Auto-generates playlist with studio version from Spotify/Apple Music
- Or links to purchase studio album

### Artist Discography Timeline
Visual timeline showing studio albums + live recordings:
```
1967 ■──┬──────────────────────────────────── 2026
        │
   [Studio Album]
        │
        ├──● Live Show
        ├──● Live Show
        │
   [Studio Album]
        ├──● Live Show
        ...
```

### Fallback APIs (if coverage is poor)
Only add if MusicBrainz coverage is <70%:
1. Discogs API (20 req/min limit, better indie coverage)
2. Deezer API (free unlimited, good European artists)
3. TheAudioDB ($8/mo for commercial use)

---

## 📝 Documentation Updates

After implementation, update:

1. **Project CLAUDE.md**
   - Add Studio Albums section
   - Document GraphQL schema
   - CLI command examples
   - Frontend component usage

2. **Module README** (`src/app/code/ArchiveDotOrg/Core/CLAUDE.md`)
   - Add AlbumArtworkService documentation
   - MusicBrainz integration details
   - Database schema changes
   - API endpoints

3. **Frontend README** (`frontend/README.md`)
   - StudioAlbums component props
   - GraphQL query examples
   - Styling guidelines

---

## 🐛 Known Issues & Workarounds

### Docker SSL with MusicBrainz
**Issue:** OpenSSL 3.0.15 in Debian 12 container can't connect to musicbrainz.org
**Workaround:** Use host-based proxy or pre-populate from Mac
**Permanent fix:** Rebuild container with compatible OpenSSL/curl versions

### MusicBrainz Rate Limiting
**Issue:** 1 request per second (strict)
**Workaround:** Built-in rate limiting in `MusicBrainzClient::respectRateLimit()`
**Best practice:** Run bulk imports during off-hours

### Missing Artwork
**Issue:** ~20-30% of albums don't have artwork in Cover Art Archive
**Workaround:** Gracefully hide albums without artwork
**Enhancement:** Add fallback to Discogs/Deezer if needed

### Large Images
**Issue:** Cover Art Archive full-res images can be 5-10MB
**Workaround:** Always use `-500` suffix for 500px thumbnails
**Enhancement:** Implement progressive image loading

---

## 🕐 Time Estimates

| Phase | Task | Estimated Time |
|-------|------|----------------|
| 1 | Solve SSL issue (proxy approach) | 30 mins |
| 2 | Test & populate album data | 1-2 hours |
| 3 | GraphQL API endpoint | 2-3 hours |
| 4 | Frontend integration | 3-4 hours |
| 5 | Enhancement & polish | 2-3 hours |
| **Total** | **End-to-end implementation** | **~8-12 hours** |

**Realistic timeline:** 1.5-2 days including testing and refinement

---

## 🎯 Tomorrow's Immediate Action Plan

### Morning (2-3 hours)
1. **Fix SSL issue** (30 mins)
   - Create host-based proxy script
   - Update `MusicBrainzClient` to use proxy
   - Test connection

2. **Populate data** (1 hour)
   - Run CLI for top 5 artists
   - Verify database entries
   - Check artwork URL validity

3. **Create GraphQL endpoint** (1 hour)
   - Write schema.graphqls
   - Create resolver
   - Test in GraphQL playground

### Afternoon (3-4 hours)
4. **Frontend component** (2 hours)
   - Create StudioAlbums.tsx
   - Run codegen
   - Basic styling

5. **Integration & testing** (1-2 hours)
   - Add to artist page
   - Test responsive design
   - Fix any issues

6. **Polish** (30 mins)
   - Attribution link
   - Loading states
   - Error handling

### End of Day
- Working studio albums display on artist pages
- 100-200 albums populated in database
- Mobile-responsive grid layout
- Attribution to Cover Art Archive

---

## 📚 Resources

- **MusicBrainz API Docs:** https://musicbrainz.org/doc/MusicBrainz_API
- **Cover Art Archive:** https://coverartarchive.org/
- **GraphQL Schema Design:** https://devdocs.magento.com/guides/v2.4/graphql/develop/
- **Next.js Image Optimization:** https://nextjs.org/docs/api-reference/next/image
- **React Query (Apollo):** Already using in frontend

---

## 💡 Key Decisions Made

1. **MusicBrainz ONLY** - No fallback APIs (keep it simple)
2. **Free tier sufficient** - No paid APIs needed
3. **Cover Art Archive URLs** - Direct linking, optional local cache
4. **GraphQL over REST** - Consistent with existing frontend
5. **Desktop sidebar + mobile top** - Best of both worlds
6. **Attribution required** - Legal compliance with Cover Art Archive
7. **Studio albums only** - Filter by `type:album AND status:official`

---

## 🎉 Success Criteria

Tomorrow is successful if:
- ✅ SSL/connectivity issue resolved
- ✅ 100+ studio albums in database
- ✅ GraphQL query returns data
- ✅ Frontend displays album artwork
- ✅ Responsive on mobile/desktop
- ✅ No console errors
- ✅ Attribution link present

**Stretch goals:**
- 200+ albums populated
- Album detail modal working
- Lazy loading implemented
- Multiple artist pages tested

---

## 🔧 Quick Reference Commands

```bash
# Test MusicBrainz connectivity
curl -s "https://musicbrainz.org/ws/2/release/?query=artist:Phish&fmt=json&limit=1"

# Run album art download
bin/magento archivedotorg:download-album-art "Grateful Dead" --limit=10

# Check database
bin/mysql -e "SELECT COUNT(*) FROM archivedotorg_studio_albums;"

# Test GraphQL
curl -X POST https://magento.test/graphql -H "Content-Type: application/json" \
  -d '{"query":"{ studioAlbums(artistName: \"Phish\") { total_count } }"}'

# Frontend codegen
cd frontend && npm run codegen

# Clear Magento cache
bin/magento cache:flush

# Rebuild generated code
rm -rf src/generated/code/ArchiveDotOrg && bin/magento setup:di:compile
```

---

**Ready to rock this tomorrow! 🎸🎶**
