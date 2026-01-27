# Magento Product Search Fix - Implementation Plan

## Problem

Product search via GraphQL returns "Internal server error":

```
[GraphQL] Errors: [{
  "message": "Internal server error",
  "locations": [{"line": 3, "column": 5}],
  "path": ["products"]
}]
```

**Query that fails:**
```graphql
query GetSongsBySearch($search: String!, $pageSize: Int!) {
  products(search: $search, pageSize: $pageSize) {
    items {
      uid
      sku
      name
      song_title
      song_duration
      # ... etc
    }
  }
}
```

**Variables:** `{ search: "sts9", pageSize: 50 }`

---

## Root Cause Analysis

### Check These in Order:

1. **Magento Exception Logs**
   ```bash
   bin/bash
   tail -100 var/log/exception.log
   tail -100 var/log/system.log
   ```
   Look for GraphQL errors matching the error hash.

2. **Test Simplified Query**
   ```bash
   curl -X POST https://magento.test/graphql \
     -H "Content-Type: application/json" \
     -k \
     --data '{
       "query": "{ products(search: \"sts9\", pageSize: 10) { items { sku name } total_count } }"
     }'
   ```
   If this fails → Basic search is broken
   If this works → Custom attributes are the issue

3. **Test Without Custom Attributes**
   Try query with ONLY Magento core fields:
   ```graphql
   query GetSongsBySearch($search: String!, $pageSize: Int!) {
     products(search: $search, pageSize: $pageSize) {
       items {
         uid
         sku
         name
       }
       total_count
     }
   }
   ```

4. **Check Elasticsearch/OpenSearch**
   ```bash
   bin/bash
   curl -k https://opensearch:9200/_cat/indices?v
   curl -k https://opensearch:9200/magento2_product_1/_search?q=sts9
   ```
   Verify products are indexed.

5. **Reindex Products**
   ```bash
   bin/magento indexer:reindex catalogsearch_fulltext
   bin/magento cache:flush
   ```

---

## Likely Causes

### Cause 1: Custom Attributes Not in Schema (Most Likely)

**Symptom:** Query works without custom fields, fails with them

**Fields to check:**
- `song_title`
- `song_duration`
- `song_url`
- `show_name`
- `identifier`
- `show_venue`
- `archive_avg_rating`
- etc.

**Fix:** Add these to GraphQL schema
```bash
bin/bash
# Check if attributes exist
bin/magento catalog:product:attributes:list | grep -i song
```

**Solution:** Verify all custom attributes are:
- Added to product attribute set
- Marked as "Used in GraphQL" in Magento admin
- Schema regenerated: `bin/magento setup:upgrade`

---

### Cause 2: Search Index Corrupted

**Symptom:** Simplified query also fails

**Fix:**
```bash
bin/magento indexer:reset catalogsearch_fulltext
bin/magento indexer:reindex catalogsearch_fulltext
```

---

### Cause 3: OpenSearch/Elasticsearch Down

**Symptom:** Search returns 500, but direct ES query fails

**Fix:**
```bash
docker-compose ps opensearch
docker-compose restart opensearch
bin/magento indexer:reindex catalogsearch_fulltext
```

---

## Implementation Steps

### Step 1: Diagnose (5 minutes)

```bash
# 1. Check Magento logs
bin/bash
tail -200 var/log/exception.log | grep -i "products\|search"

# 2. Test basic query
curl -X POST https://magento.test/graphql \
  -H "Content-Type: application/json" \
  -k \
  --data '{"query":"{ products(search: \"sts9\", pageSize: 10) { items { sku name } total_count } }"}'

# 3. Check OpenSearch
curl -k https://opensearch:9200/_cluster/health
```

### Step 2: Test Without Custom Attributes (10 minutes)

Temporarily modify `frontend/lib/api.ts`:

```typescript
const GET_SONGS_BY_SEARCH_QUERY = `
  query GetSongsBySearch($search: String!, $pageSize: Int!) {
    products(search: $search, pageSize: $pageSize) {
      items {
        uid
        sku
        name
        # Comment out custom attributes
        # song_title
        # song_duration
        # ...
      }
      total_count
    }
  }
`;
```

Test search again. If it works → Custom attributes are the issue.

### Step 3: Fix Custom Attributes (30 minutes)

```bash
# 1. List all product attributes
bin/magento catalog:product:attributes:list > /tmp/attrs.txt
grep -i "song\|show\|archive\|identifier" /tmp/attrs.txt

# 2. For each missing attribute, check if it's in GraphQL schema:
bin/bash
grep -r "song_title" app/code/*/GraphQl/

# 3. If not in schema, add to etc/schema.graphqls:
type ProductInterface {
  song_title: String @doc(description: "Song title")
  song_duration: Int @doc(description: "Song duration in seconds")
  # ... etc
}

# 4. Regenerate schema
bin/magento setup:upgrade
bin/magento cache:flush
```

### Step 4: Reindex (5 minutes)

```bash
bin/magento indexer:reindex catalogsearch_fulltext
bin/magento cache:flush
```

### Step 5: Test Search (5 minutes)

```bash
# Test via curl
curl -X POST https://magento.test/graphql \
  -H "Content-Type: application/json" \
  -k \
  --data '{
    "query": "query { products(search: \"sts9\", pageSize: 10) { items { uid sku name song_title } } }",
    "variables": {}
  }'

# Test via frontend
# Go to http://localhost:3001/search
# Search for "sts9"
# Should return results
```

---

## Alternative: Client-Side Search (Quick Fix)

If Magento search is too broken, implement client-side search:

```typescript
// In lib/api.ts
export async function search(query: string) {
  const searchLower = query.toLowerCase();

  // 1. Filter artists (already working)
  const allArtists = await getArtists();
  const artists = allArtists.filter(a =>
    a.name.toLowerCase().includes(searchLower)
  ).slice(0, 10);

  // 2. Get all songs and filter client-side
  const allSongs = await getSongs(500); // Get more songs
  const tracks = allSongs.filter(s =>
    s.title.toLowerCase().includes(searchLower) ||
    s.artistName.toLowerCase().includes(searchLower) ||
    s.albumName.toLowerCase().includes(searchLower)
  ).slice(0, 20);

  // 3. Group into albums
  const albumsMap = new Map();
  tracks.forEach(track => {
    if (!albumsMap.has(track.albumIdentifier)) {
      albumsMap.set(track.albumIdentifier, []);
    }
    albumsMap.get(track.albumIdentifier).push(track);
  });

  const albums = Array.from(albumsMap.entries()).map(([id, songs]) => ({
    id,
    identifier: id,
    name: songs[0].albumName,
    slug: slugify(id),
    artistId: songs[0].artistId,
    artistName: songs[0].artistName,
    artistSlug: songs[0].artistSlug,
    tracks: [],
    totalTracks: songs.length,
    totalSongs: songs.length,
    totalDuration: songs.reduce((sum, s) => sum + s.duration, 0),
  })).slice(0, 10);

  return { artists, albums, tracks };
}
```

**Pros:** Works immediately, no Magento changes
**Cons:** Slower, limited to loaded products, not scalable

---

## Files to Check

- `frontend/lib/api.ts:815-888` - Search function
- `src/app/code/*/etc/schema.graphqls` - GraphQL schema definitions
- `var/log/exception.log` - Magento error logs
- `compose.yaml` - OpenSearch service config

---

## Success Criteria

- ✅ Search for "sts9" returns STS9 artist
- ✅ Search for "sts9" returns tracks/albums from STS9
- ✅ Search for "Grateful Dead" returns artist + tracks
- ✅ No GraphQL errors in logs

---

## Estimated Time

- Diagnosis: 5-10 minutes
- Fix custom attributes: 30-45 minutes
- Testing: 10 minutes

**Total: 45-65 minutes**

**Alternative (client-side): 15-20 minutes** ← Recommended for quick fix
