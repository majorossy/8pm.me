// API layer - Magento 2 GraphQL integration
import { Song, Artist, ArtistDetail, Album, Track } from './types';

export type { Song, Artist, ArtistDetail, Album, Track } from './types';

// GraphQL endpoint - uses Docker service name internally, external URL for client
const MAGENTO_GRAPHQL_URL = process.env.MAGENTO_GRAPHQL_URL || 'https://app:8443/graphql';

// Magento media URL for images (browser-accessible)
const MAGENTO_MEDIA_URL = process.env.NEXT_PUBLIC_MAGENTO_MEDIA_URL || 'https://magento.test/media';

// Parent category ID for artists
const ARTISTS_PARENT_CATEGORY_ID = '48';

// Helper to construct category image URL from url_key (workaround for GraphQL placeholder issue)
function getCategoryImageUrl(urlKey: string): string {
  return `${MAGENTO_MEDIA_URL}/catalog/category/${urlKey}.jpg`;
}

interface GraphQLResponse<T> {
  data?: T;
  errors?: Array<{ message: string }>;
}

async function graphqlFetch<T>(query: string, variables?: Record<string, unknown>): Promise<T> {
  const response = await fetch(MAGENTO_GRAPHQL_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ query, variables }),
    cache: 'no-store',
  });

  const result: GraphQLResponse<T> = await response.json();

  if (result.errors) {
    throw new Error(result.errors.map(e => e.message).join(', '));
  }

  if (!result.data) {
    throw new Error('No data returned from GraphQL');
  }

  return result.data;
}

// GraphQL Queries
const GET_ARTISTS_QUERY = `
  query GetArtists($parentId: String!) {
    categoryList(filters: { parent_id: { eq: $parentId } }) {
      uid
      name
      url_key
      description
      image
      product_count
    }
  }
`;

const GET_ARTIST_BY_SLUG_QUERY = `
  query GetArtistBySlug($urlKey: String!) {
    categoryList(filters: { url_key: { eq: $urlKey } }) {
      uid
      name
      url_key
      description
      image
      product_count
    }
  }
`;

// Get child categories (albums) of an artist category
const GET_CHILD_CATEGORIES_QUERY = `
  query GetChildCategories($parentUid: String!) {
    categoryList(filters: { parent_category_uid: { eq: $parentUid } }) {
      uid
      name
      url_key
      description
      image
      product_count
    }
  }
`;

const GET_SONGS_BY_CATEGORY_QUERY = `
  query GetSongsByCategory($categoryUid: String!, $pageSize: Int!) {
    products(filter: { category_uid: { eq: $categoryUid } }, pageSize: $pageSize) {
      items {
        uid
        sku
        name
        song_title
        song_duration
        song_url
        show_name
        identifier
        lineage
        notes
        categories {
          uid
          name
          url_key
        }
      }
      total_count
    }
  }
`;

const GET_SONGS_BY_SEARCH_QUERY = `
  query GetSongsBySearch($search: String!, $pageSize: Int!) {
    products(search: $search, pageSize: $pageSize) {
      items {
        uid
        sku
        name
        song_title
        song_duration
        song_url
        show_name
        identifier
        lineage
        notes
        categories {
          uid
          name
          url_key
        }
      }
      total_count
    }
  }
`;

const GET_ALL_SONGS_QUERY = `
  query GetAllSongs($pageSize: Int!) {
    products(search: "", pageSize: $pageSize) {
      items {
        uid
        sku
        name
        song_title
        song_duration
        song_url
        show_name
        identifier
        lineage
        notes
        categories {
          uid
          name
          url_key
        }
      }
      total_count
    }
  }
`;

const GET_SONG_BY_ID_QUERY = `
  query GetSongById($uid: String!) {
    products(filter: { uid: { eq: $uid } }) {
      items {
        uid
        sku
        name
        song_title
        song_duration
        song_url
        show_name
        identifier
        lineage
        notes
        categories {
          uid
          name
          url_key
        }
      }
    }
  }
`;

// Type definitions for GraphQL responses
interface MagentoCategory {
  uid: string;
  name: string;
  url_key: string;
  description?: string;
  image?: string;
  product_count?: number;
}

interface MagentoProduct {
  uid: string;
  sku: string;
  name: string;
  song_title?: string;
  song_duration?: number;
  song_url?: string;
  show_name?: string;
  identifier?: string;          // Archive.org album identifier
  lineage?: string;             // Recording chain/source equipment
  notes?: string;               // Performance notes, guests, covers
  categories?: Array<{ uid: string; name: string; url_key: string }>;
}

// Transform Magento category to Artist
function categoryToArtist(category: MagentoCategory): Artist {
  return {
    id: category.uid,
    name: category.name,
    slug: category.url_key,
    image: getCategoryImageUrl(category.url_key),
    bio: category.description || '',
    songCount: category.product_count || 0,
  };
}

// Generate URL-safe slug from string
function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
}

// Transform Magento product to Song (with album context)
function productToSong(product: MagentoProduct, albumIdentifier?: string): Song {
  // Find the main artist category (first category that's not a song-specific one)
  const artistCategory = product.categories?.find(
    c => c.url_key && !c.url_key.includes('-')
  ) || product.categories?.[0];

  const identifier = albumIdentifier || product.identifier || 'unknown-album';
  const albumName = product.show_name || identifier;
  const trackTitle = product.song_title || product.name;

  return {
    id: product.uid,
    sku: product.sku,
    title: trackTitle,
    artistId: artistCategory?.uid || '',
    artistName: artistCategory?.name || 'Unknown Artist',
    artistSlug: artistCategory?.url_key || '',
    duration: product.song_duration || 0,
    streamUrl: product.song_url ? `https://${product.song_url}` : '',
    albumArt: '/images/songs/default.jpg',
    // Album/track context
    albumIdentifier: identifier,
    albumName,
    trackTitle,
    // Recording metadata
    lineage: product.lineage || undefined,
    notes: product.notes || undefined,
  };
}

// Group products within an album into tracks
function groupProductsIntoTracks(
  products: MagentoProduct[],
  albumIdentifier: string,
  albumName: string,
  artistId: string,
  artistName: string,
  artistSlug: string
): Track[] {
  const trackMap = new Map<string, MagentoProduct[]>();

  // Group by song_title
  products.forEach(product => {
    const trackTitle = product.song_title || product.name;
    if (!trackMap.has(trackTitle)) {
      trackMap.set(trackTitle, []);
    }
    trackMap.get(trackTitle)!.push(product);
  });

  return Array.from(trackMap.entries()).map(([title, trackProducts]) => ({
    id: `${albumIdentifier}-${slugify(title)}`,
    title,
    slug: slugify(title),
    albumIdentifier,
    albumName,
    artistId,
    artistName,
    artistSlug,
    songs: trackProducts.map(p => productToSong(p, albumIdentifier)),
    totalDuration: trackProducts[0].song_duration || 0,
    songCount: trackProducts.length,
  }));
}

// Group products into albums by identifier
function groupProductsIntoAlbums(
  products: MagentoProduct[],
  artistId: string,
  artistName: string,
  artistSlug: string
): Album[] {
  const albumMap = new Map<string, MagentoProduct[]>();

  // Group by identifier
  products.forEach(product => {
    const identifier = product.identifier || 'unknown-album';
    if (!albumMap.has(identifier)) {
      albumMap.set(identifier, []);
    }
    albumMap.get(identifier)!.push(product);
  });

  // Transform to Album objects
  return Array.from(albumMap.entries()).map(([identifier, albumProducts]) => {
    const firstProduct = albumProducts[0];
    const albumName = firstProduct.show_name || identifier;
    const tracks = groupProductsIntoTracks(
      albumProducts,
      identifier,
      albumName,
      artistId,
      artistName,
      artistSlug
    );

    return {
      id: identifier,
      identifier,
      name: albumName,
      slug: slugify(identifier),
      artistId,
      artistName,
      artistSlug,
      tracks,
      totalTracks: tracks.length,
      totalSongs: albumProducts.length,
      totalDuration: albumProducts.reduce((sum, p) => sum + (p.song_duration || 0), 0),
    };
  });
}

// API Functions
export async function getArtists(): Promise<Artist[]> {
  try {
    const data = await graphqlFetch<{ categoryList: MagentoCategory[] }>(
      GET_ARTISTS_QUERY,
      { parentId: ARTISTS_PARENT_CATEGORY_ID }
    );
    return data.categoryList.map(categoryToArtist);
  } catch (error) {
    console.error('Failed to fetch artists:', error);
    return [];
  }
}

export async function getArtist(slug: string): Promise<ArtistDetail | null> {
  try {
    // Get artist category by slug
    const artistData = await graphqlFetch<{ categoryList: MagentoCategory[] }>(
      GET_ARTIST_BY_SLUG_QUERY,
      { urlKey: slug }
    );

    if (!artistData.categoryList.length) {
      return null;
    }

    const category = artistData.categoryList[0];
    const artist = categoryToArtist(category);

    // Get child categories (albums) of the artist category
    const albumCategoriesData = await graphqlFetch<{ categoryList: MagentoCategory[] }>(
      GET_CHILD_CATEGORIES_QUERY,
      { parentUid: category.uid }
    );

    const albumCategories = albumCategoriesData.categoryList || [];

    // For each album category, get its track categories and their products
    const albums: Album[] = await Promise.all(
      albumCategories.map(async (albumCat) => {
        // Get track categories (children of album)
        const trackCategoriesData = await graphqlFetch<{ categoryList: MagentoCategory[] }>(
          GET_CHILD_CATEGORIES_QUERY,
          { parentUid: albumCat.uid }
        );

        const trackCategories = trackCategoriesData.categoryList || [];

        // For each track category, get its products (song versions)
        const tracks: Track[] = await Promise.all(
          trackCategories.map(async (trackCat) => {
            const productsData = await graphqlFetch<{ products: { items: MagentoProduct[]; total_count: number } }>(
              GET_SONGS_BY_CATEGORY_QUERY,
              { categoryUid: trackCat.uid, pageSize: 100 }
            );

            const products = productsData.products.items || [];
            const songs = products.map(p => productToSong(p, albumCat.url_key));

            return {
              id: trackCat.uid,
              title: trackCat.name,
              slug: trackCat.url_key,
              albumIdentifier: albumCat.url_key,
              albumName: albumCat.name,
              artistId: category.uid,
              artistName: category.name,
              artistSlug: category.url_key,
              songs,
              totalDuration: songs[0]?.duration || 0,
              songCount: songs.length,
            };
          })
        );

        // Calculate totals
        const totalSongs = tracks.reduce((sum, t) => sum + t.songs.length, 0);
        const totalDuration = tracks.reduce((sum, t) =>
          sum + t.songs.reduce((s, song) => s + song.duration, 0), 0
        );

        return {
          id: albumCat.uid,
          identifier: albumCat.url_key,
          name: albumCat.name,
          slug: albumCat.url_key,
          artistId: category.uid,
          artistName: category.name,
          artistSlug: category.url_key,
          tracks,
          totalTracks: tracks.length,
          totalSongs,
          totalDuration,
          coverArt: getCategoryImageUrl(albumCat.url_key),
        };
      })
    );

    // Flatten all songs from all albums for backwards compatibility
    const songs: Song[] = albums.flatMap(album =>
      album.tracks.flatMap(track => track.songs)
    );

    return {
      ...artist,
      albums,
      songs,
      albumCount: albums.length,
      songCount: songs.length,
    };
  } catch (error) {
    console.error('Failed to fetch artist:', error);
    return null;
  }
}

export async function getSongs(limit: number = 50): Promise<Song[]> {
  try {
    const data = await graphqlFetch<{ products: { items: MagentoProduct[] } }>(
      GET_ALL_SONGS_QUERY,
      { pageSize: limit }
    );
    return data.products.items.map(p => productToSong(p));
  } catch (error) {
    console.error('Failed to fetch songs:', error);
    return [];
  }
}

export async function getSong(id: string): Promise<Song | null> {
  try {
    const data = await graphqlFetch<{ products: { items: MagentoProduct[] } }>(
      GET_SONG_BY_ID_QUERY,
      { uid: id }
    );

    if (!data.products.items.length) {
      return null;
    }

    return productToSong(data.products.items[0]);
  } catch (error) {
    console.error('Failed to fetch song:', error);
    return null;
  }
}

export function formatDuration(seconds: number): string {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// Get a specific album by artist slug and album identifier/slug
export async function getAlbum(
  artistSlug: string,
  albumIdentifier: string
): Promise<Album | null> {
  const artist = await getArtist(artistSlug);
  if (!artist) return null;

  // Match by slug or original identifier
  return artist.albums.find(
    a => a.slug === albumIdentifier || a.identifier === albumIdentifier
  ) || null;
}

// Get a specific track by artist slug, album identifier, and track slug
export async function getTrack(
  artistSlug: string,
  albumIdentifier: string,
  trackSlug: string
): Promise<Track | null> {
  const album = await getAlbum(artistSlug, albumIdentifier);
  if (!album) return null;

  return album.tracks.find(t => t.slug === trackSlug) || null;
}
