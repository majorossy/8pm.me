# GraphQL Schema Fix - COMPLETE ✅

**Date:** 2026-01-29
**Issue:** GraphQL studioAlbums query not registered
**Status:** ✅ FIXED

---

## Problem

GraphQL query `studioAlbums` returned error:
```
"Cannot query field \"studioAlbums\" on type \"Query\"."
```

**Root Cause:** Incomplete `schema.graphqls` file - only had CategoryInterface extension, missing Query and StudioAlbum type definitions.

---

## Solution

Added complete GraphQL schema to `/app/code/ArchiveDotOrg/Core/etc/schema.graphqls`:

```graphql
interface CategoryInterface {
    wikipedia_artwork_url: String @doc(description: "Wikipedia artwork URL for album categories")
}

type Query {
    studioAlbums(
        artistName: String @doc(description: "Filter albums by artist name")
    ): StudioAlbumsOutput @resolver(class: "ArchiveDotOrg\\Core\\Model\\Resolver\\StudioAlbums") @doc(description: "Get studio albums with artwork from MusicBrainz/Wikipedia")
}

type StudioAlbumsOutput {
    items: [StudioAlbum] @doc(description: "List of studio albums")
}

type StudioAlbum {
    entity_id: Int @doc(description: "Album ID")
    artist_name: String @doc(description: "Artist name")
    album_title: String @doc(description: "Album title")
    release_year: Int @doc(description: "Release year")
    release_date: String @doc(description: "Release date")
    musicbrainz_id: String @doc(description: "MusicBrainz album ID")
    category_id: Int @doc(description: "Associated category ID")
    artwork_url: String @doc(description: "Album artwork URL")
    cached_image_path: String @doc(description: "Cached local image path")
}
```

**Steps Taken:**
1. Added Query type with studioAlbums field
2. Pointed to existing resolver: `ArchiveDotOrg\Core\Model\Resolver\StudioAlbums`
3. Defined StudioAlbumsOutput wrapper type
4. Defined StudioAlbum type matching database schema
5. Synced to container
6. Flushed all Magento caches

---

## Verification

### Query Test
```graphql
query {
  studioAlbums(artistName: "Phish") {
    items {
      artist_name
      album_title
      release_year
      artwork_url
    }
  }
}
```

### Result ✅
```json
{
  "data": {
    "studioAlbums": {
      "items": [
        {
          "artist_name": "Phish",
          "album_title": "A Picture of Nectar",
          "release_year": null,
          "artwork_url": "https://upload.wikimedia.org/wikipedia/en/4/40/A_Picture_of_Nectar_%28Phish_album_-_cover_art%29.jpg"
        },
        {
          "artist_name": "Phish",
          "album_title": "Big Boat",
          "release_year": null,
          "artwork_url": "https://upload.wikimedia.org/wikipedia/en/e/e3/PhishBigBoatCover.jpg"
        },
        // ... 13 more albums
      ]
    }
  }
}
```

**Total Albums Returned:** 15 Phish albums with Wikipedia artwork URLs

---

## Usage

### Frontend Integration

```typescript
// frontend/graphql/queries/studioAlbums.ts
export const GET_STUDIO_ALBUMS = gql`
  query GetStudioAlbums($artistName: String!) {
    studioAlbums(artistName: $artistName) {
      items {
        entity_id
        artist_name
        album_title
        release_year
        artwork_url
      }
    }
  }
`;

// Usage in component
const { data, loading, error } = useQuery(GET_STUDIO_ALBUMS, {
  variables: { artistName: 'Phish' }
});
```

### cURL Test
```bash
curl -X POST "https://magento.test/graphql" \
  -H "Content-Type: application/json" \
  -d '{"query":"{ studioAlbums(artistName: \"Phish\") { items { album_title artwork_url } } }"}' \
  --insecure
```

---

## Database Source

Query pulls from `archivedotorg_studio_albums` table:
- **Total rows:** 236 albums across all artists
- **Phish albums:** 15 with artwork URLs
- **Data source:** Wikipedia artwork URLs (MusicBrainz blocked)
- **Updated:** Via `bin/magento archivedotorg:download-album-art`

---

## Available Fields

| Field | Type | Description | Nullable |
|-------|------|-------------|----------|
| `entity_id` | Int | Database ID | No |
| `artist_name` | String | Artist name | No |
| `album_title` | String | Album title | No |
| `release_year` | Int | Year released | Yes |
| `release_date` | String | Full date | Yes |
| `musicbrainz_id` | String | MusicBrainz GUID | Yes |
| `category_id` | Int | Magento category | Yes |
| `artwork_url` | String | Image URL | Yes |
| `cached_image_path` | String | Local cache path | Yes |

**Note:** `release_year` is NULL for Phish albums - data needs enrichment.

---

## Next Steps

### Frontend Component
Create `frontend/components/StudioAlbums.tsx`:
```typescript
import { useQuery } from '@apollo/client';
import { GET_STUDIO_ALBUMS } from '@/graphql/queries/studioAlbums';

export const StudioAlbums: React.FC<{ artistName: string }> = ({ artistName }) => {
  const { data, loading, error } = useQuery(GET_STUDIO_ALBUMS, {
    variables: { artistName }
  });

  if (loading) return <LoadingSpinner />;
  if (error) return <ErrorMessage error={error} />;

  return (
    <div className="grid grid-cols-4 gap-4">
      {data.studioAlbums.items.map(album => (
        <AlbumCard key={album.entity_id} album={album} />
      ))}
    </div>
  );
};
```

### Data Enrichment
Populate missing `release_year` data:
```bash
# Option 1: Re-run album artwork command with year extraction
bin/magento archivedotorg:download-album-art "Phish" --force

# Option 2: Manual SQL update
UPDATE archivedotorg_studio_albums
SET release_year = 1992
WHERE album_title = 'A Picture of Nectar';
```

---

## FIXES.md Status Update

**Fix #N/A (GraphQL):** ✅ **COMPLETE**
- Was: ⚠️ Schema not registered
- Now: ✅ Query working, 15 albums returned

**Overall Progress:**
- Before: 25/48 (52%)
- After: **26/48 (54%)** (counting GraphQL as bonus fix)

---

## Files Changed

1. ✅ `src/app/code/ArchiveDotOrg/Core/etc/schema.graphqls` - Added Query and StudioAlbum types
2. ✅ Synced to container
3. ✅ Cache flushed

**Existing files (already working):**
- `Model/Resolver/StudioAlbums.php` - GraphQL resolver (no changes needed)
- `archivedotorg_studio_albums` table - 236 albums cached

---

**Status:** ✅ GraphQL WORKING
**Tested:** 2026-01-29
**Result:** 15 Phish albums with artwork returned successfully
