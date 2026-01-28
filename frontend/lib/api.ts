// API layer - Magento 2 GraphQL integration
import { unstable_cache } from 'next/cache';
import { Song, Artist, ArtistDetail, Album, Track } from './types';
import { fetchWikipediaSummary } from './wikipedia';

export type { Song, Artist, ArtistDetail, Album, Track } from './types';

// Cache duration in seconds
const CACHE_DURATION = 60 * 5; // 5 minutes

// ============================================
// Retry Configuration
// ============================================
const RETRY_CONFIG = {
  maxRetries: 3,
  baseDelayMs: 1000, // 1 second base delay
  maxDelayMs: 10000, // 10 seconds max delay
  // Status codes that should NOT be retried
  nonRetryableStatuses: [400, 401, 403, 404, 422],
};

// Error types that indicate network/timeout issues (should retry)
const RETRYABLE_ERROR_MESSAGES = [
  'fetch failed',
  'network error',
  'timeout',
  'ECONNREFUSED',
  'ENOTFOUND',
  'ETIMEDOUT',
  'ECONNRESET',
  'socket hang up',
];

/**
 * Check if an error is retryable based on its type/message
 */
function isRetryableError(error: unknown): boolean {
  if (error instanceof Error) {
    const message = error.message.toLowerCase();
    return RETRYABLE_ERROR_MESSAGES.some(msg => message.includes(msg.toLowerCase()));
  }
  return false;
}

/**
 * Check if an HTTP status code is retryable
 */
function isRetryableStatus(status: number): boolean {
  // Don't retry client errors (4xx) except for rate limiting (429) and server overload (503)
  if (RETRY_CONFIG.nonRetryableStatuses.includes(status)) {
    return false;
  }
  // Retry 5xx server errors and 429 rate limiting
  return status >= 500 || status === 429;
}

/**
 * Calculate delay with exponential backoff and jitter
 */
function getRetryDelay(attempt: number): number {
  // Exponential backoff: 1s, 2s, 4s...
  const exponentialDelay = RETRY_CONFIG.baseDelayMs * Math.pow(2, attempt);
  // Add jitter (0-25% of the delay) to prevent thundering herd
  const jitter = exponentialDelay * 0.25 * Math.random();
  // Cap at max delay
  return Math.min(exponentialDelay + jitter, RETRY_CONFIG.maxDelayMs);
}

/**
 * Sleep for a specified number of milliseconds
 */
function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Fetch with retry logic
 */
async function fetchWithRetry(
  url: string,
  options: RequestInit,
  context: string = 'fetch'
): Promise<Response> {
  let lastError: Error | null = null;

  for (let attempt = 0; attempt <= RETRY_CONFIG.maxRetries; attempt++) {
    try {
      const response = await fetch(url, options);

      // Check if response status indicates we should retry
      if (!response.ok && isRetryableStatus(response.status)) {
        if (attempt < RETRY_CONFIG.maxRetries) {
          const delay = getRetryDelay(attempt);
          console.warn(
            `[${context}] Request failed with status ${response.status}, retrying in ${delay}ms (attempt ${attempt + 1}/${RETRY_CONFIG.maxRetries})`
          );
          await sleep(delay);
          continue;
        }
      }

      return response;
    } catch (error) {
      lastError = error instanceof Error ? error : new Error(String(error));

      // Check if error is retryable
      if (isRetryableError(error) && attempt < RETRY_CONFIG.maxRetries) {
        const delay = getRetryDelay(attempt);
        console.warn(
          `[${context}] Request failed with error: ${lastError.message}, retrying in ${delay}ms (attempt ${attempt + 1}/${RETRY_CONFIG.maxRetries})`
        );
        await sleep(delay);
        continue;
      }

      // Non-retryable error or max retries reached
      throw lastError;
    }
  }

  // Should not reach here, but just in case
  throw lastError || new Error(`[${context}] Request failed after ${RETRY_CONFIG.maxRetries} retries`);
}

// GraphQL endpoint - uses Docker service name internally, external URL for client
const MAGENTO_GRAPHQL_URL = process.env.MAGENTO_GRAPHQL_URL || 'https://app:8443/graphql';
console.log('[API] Using GraphQL URL:', MAGENTO_GRAPHQL_URL);

// Magento media URL for images (browser-accessible)
const MAGENTO_MEDIA_URL = process.env.NEXT_PUBLIC_MAGENTO_MEDIA_URL || 'https://magento.test/media';

// Parent category ID for artists
const ARTISTS_PARENT_CATEGORY_ID = '48';

// Local album art mapping (slug -> filename in /images/albums/)
const LOCAL_ALBUM_ART: Record<string, string> = {
  // STS9
  'artifact': '/images/albums/artifact.jpg',
  'interplanetaryescapevehicle': '/images/albums/interplanetary-escape-vehicle.jpg',
  'offeredschematicssuggestingpeace': '/images/albums/offered-schematics-suggesting-peace.jpg',
  // String Cheese Incident
  'bornonthewrongplanet': '/images/albums/born-on-the-wrong-planet.jpg',
  'astringcheeseincident': '/images/albums/a-string-cheese-incident.jpg',
  'roundthewheel': '/images/albums/round-the-wheel.jpg',
  'carnival99': '/images/albums/carnival-99.jpg',
  'outsideinside': '/images/albums/outside-inside.jpg',
  'untyingthenot': '/images/albums/untying-the-not.jpg',
  'onestepcloser': '/images/albums/one-step-closer.jpg',
  'trickortreat': '/images/albums/trick-or-treat.jpg',
  'songinmyhead': '/images/albums/song-in-my-head.jpg',
  'believe': '/images/albums/believe.jpg',
  // Tea Leaf Green
  'tealeafgreenalbum': '/images/albums/tea-leaf-green.jpg',
  'taughttobeproud': '/images/albums/taught-to-be-proud.jpg',
  'raiseupthetent': '/images/albums/raise-up-the-tent.jpg',
  // Grace Potter
  'originalsoul': '/images/albums/original-soul.jpg',
  'midnight': '/images/albums/midnight.jpg',
  // O.A.R.
  'inbetweennowandthen': '/images/albums/in-between-now-and-then.jpg',
  'soulsaflame': '/images/albums/souls-aflame.jpg',
  'thewanderer': '/images/albums/the-wanderer.jpg',
  'risen': '/images/albums/risen.jpg',
};

// Get album cover art - check local first, then fallback to Magento
function getAlbumCoverArt(urlKey: string): string | undefined {
  const slug = urlKey.toLowerCase();
  console.log('[getAlbumCoverArt] urlKey:', urlKey, '-> slug:', slug, '-> match:', LOCAL_ALBUM_ART[slug] || 'none');
  if (LOCAL_ALBUM_ART[slug]) {
    return LOCAL_ALBUM_ART[slug];
  }
  // Fallback to Magento media (may not exist)
  return `${MAGENTO_MEDIA_URL}/catalog/category/${urlKey}.jpg`;
}

// Helper to construct category image URL from url_key (workaround for GraphQL placeholder issue)
function getCategoryImageUrl(urlKey: string): string {
  return `${MAGENTO_MEDIA_URL}/catalog/category/${urlKey}.jpg`;
}

interface GraphQLResponse<T> {
  data?: T;
  errors?: Array<{ message: string }>;
}

interface FetchOptions {
  cache?: boolean;
  revalidate?: number;
}

async function graphqlFetch<T>(
  query: string,
  variables?: Record<string, unknown>,
  options: FetchOptions = { cache: true, revalidate: CACHE_DURATION }
): Promise<T> {
  const fetchOptions: RequestInit = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ query, variables }),
  };

  // Add Next.js caching options (these are non-standard fetch options)
  if (options.cache) {
    (fetchOptions as any).next = { revalidate: options.revalidate };
  } else {
    fetchOptions.cache = 'no-store';
  }

  // Use fetch with retry for network resilience
  const response = await fetchWithRetry(
    MAGENTO_GRAPHQL_URL,
    fetchOptions,
    'GraphQL'
  );

  const result: GraphQLResponse<T> = await response.json();

  if (result.errors) {
    console.error('[GraphQL] Errors:', JSON.stringify(result.errors, null, 2));
    throw new Error(result.errors.map(e => e.message).join(', '));
  }

  if (!result.data) {
    console.error('[GraphQL] No data returned:', result);
    throw new Error('No data returned from GraphQL');
  }

  return result.data;
}

// GraphQL Queries
// Use categories query with pagination to get all artists (categoryList has ~20 item limit)
const GET_ARTISTS_QUERY = `
  query GetArtists($parentId: String!, $pageSize: Int!, $currentPage: Int!) {
    categories(filters: { parent_id: { eq: $parentId } }, pageSize: $pageSize, currentPage: $currentPage) {
      total_count
      items {
        uid
        name
        url_key
        description
        image
        product_count
      }
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
      band_formation_date
      band_origin_location
      band_years_active
      band_extended_bio
      band_genres
      band_official_website
      band_youtube_channel
      band_facebook
      band_instagram
      band_twitter
      band_total_shows
      band_most_played_track
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
        show_venue
        show_location
        show_taper
        show_source
        lineage
        notes
        archive_avg_rating
        archive_num_reviews
        archive_downloads
        archive_downloads_week
        archive_downloads_month
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
        show_venue
        show_location
        show_taper
        show_source
        lineage
        notes
        archive_avg_rating
        archive_num_reviews
        archive_downloads
        archive_downloads_week
        archive_downloads_month
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
        show_venue
        show_location
        show_taper
        show_source
        lineage
        notes
        archive_avg_rating
        archive_num_reviews
        archive_downloads
        archive_downloads_week
        archive_downloads_month
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
        show_venue
        show_location
        show_taper
        show_source
        lineage
        notes
        archive_avg_rating
        archive_num_reviews
        archive_downloads
        archive_downloads_week
        archive_downloads_month
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
  band_formation_date?: string;
  band_origin_location?: string;
  band_years_active?: string;
  band_extended_bio?: string;
  band_genres?: string;
  band_official_website?: string;
  band_youtube_channel?: string;
  band_facebook?: string;
  band_instagram?: string;
  band_twitter?: string;
  band_total_shows?: number;
  band_most_played_track?: string;
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
  show_venue?: string;          // Archive.org: venue
  show_location?: string;       // Archive.org: coverage (city/state)
  show_taper?: string;          // Archive.org: taper (who recorded)
  show_source?: string;         // Archive.org: source (recording equipment)
  lineage?: string;             // Archive.org: lineage (transfer chain)
  notes?: string;               // Performance notes, guests, covers
  archive_avg_rating?: number;  // Archive.org average rating (1-5)
  archive_num_reviews?: number; // Archive.org review count
  archive_downloads?: number;   // Archive.org total downloads
  archive_downloads_week?: number;  // Archive.org downloads this week
  archive_downloads_month?: number; // Archive.org downloads this month
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
    formationDate: category.band_formation_date || undefined,
    originLocation: category.band_origin_location || undefined,
    yearsActive: category.band_years_active || undefined,
    extendedBio: category.band_extended_bio || undefined,
    genres: category.band_genres ? category.band_genres.split(',').map(g => g.trim()) : undefined,
    officialWebsite: category.band_official_website || undefined,
    youtubeChannel: category.band_youtube_channel || undefined,
    facebook: category.band_facebook || undefined,
    instagram: category.band_instagram || undefined,
    twitter: category.band_twitter || undefined,
    totalShows: category.band_total_shows || undefined,
    mostPlayedTrack: category.band_most_played_track || undefined,
  };
}

// Normalize a URL - fix missing colons, handle double protocols
function normalizeUrl(url: string): string {
  if (!url) return '';

  // Fix "https//" or "http//" (missing colon)
  url = url.replace(/^(https?)\/\//, '$1://');

  // If URL already has a valid protocol, return as-is
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }

  // Otherwise, prepend https://
  return `https://${url}`;
}

// Generate URL-safe slug from string
function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '');
}

// Parse show_name to extract venue and date
// Format: "{Artist} Live at {Venue} on {YYYY-MM-DD}"
function parseShowName(showName: string): { venue?: string; date?: string } {
  const result: { venue?: string; date?: string } = {};

  // Extract date (YYYY-MM-DD format)
  const dateMatch = showName.match(/(\d{4}-\d{2}-\d{2})/);
  if (dateMatch) {
    result.date = dateMatch[1];
  }

  // Extract venue (between "Live at " and " on ")
  const venueMatch = showName.match(/Live at (.+?) on \d{4}-\d{2}-\d{2}/);
  if (venueMatch) {
    result.venue = venueMatch[1].trim();
  }

  return result;
}

// Extract taper/source info from identifier
// Format: "artist-YYYY-MM-DD.source.format" e.g. "sts9-2006-10-31.sonyecm.pumpkin.flac16"
function parseIdentifier(identifier: string): { source?: string } {
  const result: { source?: string } = {};

  // Get everything after the date
  const sourceMatch = identifier.match(/\d{4}-\d{2}-\d{2}\.(.+?)(?:\.flac|$)/i);
  if (sourceMatch) {
    // Clean up the source info (replace dots with spaces, format nicely)
    result.source = sourceMatch[1].replace(/\./g, ' ').trim();
  }

  return result;
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

  // Parse venue and date from show_name
  const showInfo = parseShowName(albumName);

  // Parse source/taper from identifier
  const identifierInfo = parseIdentifier(identifier);

  return {
    id: product.uid,
    sku: product.sku,
    title: trackTitle,
    artistId: artistCategory?.uid || '',
    artistName: artistCategory?.name || 'Unknown Artist',
    artistSlug: artistCategory?.url_key || '',
    duration: product.song_duration || 0, // API returns seconds from Archive.org
    streamUrl: product.song_url ? normalizeUrl(product.song_url) : '',
    albumArt: '/images/songs/default.jpg',
    // Album/track context
    albumIdentifier: identifier,
    albumName,
    trackTitle,
    // Show info (prefer direct fields, fallback to parsed)
    showDate: showInfo.date,
    showVenue: product.show_venue || showInfo.venue,
    showLocation: product.show_location || undefined,
    // Recording metadata (prefer direct fields, fallback to parsed)
    taper: product.show_taper || undefined,
    source: product.show_source || identifierInfo.source,
    lineage: product.lineage || undefined,
    notes: product.notes || undefined,
    // Archive.org ratings
    avgRating: product.archive_avg_rating || undefined,
    numReviews: product.archive_num_reviews || undefined,
    // Archive.org download stats
    downloads: product.archive_downloads || undefined,
    downloadsWeek: product.archive_downloads_week || undefined,
    downloadsMonth: product.archive_downloads_month || undefined,
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
    const PAGE_SIZE = 50; // Fetch 50 artists per page
    let allArtists: MagentoCategory[] = [];
    let currentPage = 1;
    let totalCount = 0;

    // Fetch first page to get total count
    const firstPageData = await graphqlFetch<{
      categories: { items: MagentoCategory[]; total_count: number };
    }>(GET_ARTISTS_QUERY, {
      parentId: ARTISTS_PARENT_CATEGORY_ID,
      pageSize: PAGE_SIZE,
      currentPage: 1,
    });

    allArtists = firstPageData.categories.items || [];
    totalCount = firstPageData.categories.total_count || 0;
    console.log(`[getArtists] Page 1: got ${allArtists.length} artists, total: ${totalCount}`);

    // Fetch remaining pages if needed
    const totalPages = Math.ceil(totalCount / PAGE_SIZE);
    for (currentPage = 2; currentPage <= totalPages; currentPage++) {
      const pageData = await graphqlFetch<{
        categories: { items: MagentoCategory[]; total_count: number };
      }>(GET_ARTISTS_QUERY, {
        parentId: ARTISTS_PARENT_CATEGORY_ID,
        pageSize: PAGE_SIZE,
        currentPage,
      });

      const pageArtists = pageData.categories.items || [];
      allArtists = allArtists.concat(pageArtists);
      console.log(`[getArtists] Page ${currentPage}: got ${pageArtists.length} more artists`);
    }

    console.log(`[getArtists] Total artists fetched: ${allArtists.length}`);
    return allArtists.map(categoryToArtist);
  } catch (error) {
    console.error('Failed to fetch artists:', error);
    return [];
  }
}

export async function getArtist(slug: string): Promise<ArtistDetail | null> {
  console.log('[getArtist] Fetching artist:', slug);
  try {
    // Get artist category by slug
    const artistData = await graphqlFetch<{ categoryList: MagentoCategory[] }>(
      GET_ARTIST_BY_SLUG_QUERY,
      { urlKey: slug }
    );
    console.log('[getArtist] Got artist data:', artistData.categoryList.length, 'categories');

    if (!artistData.categoryList.length) {
      console.log('[getArtist] No categories found for slug:', slug);
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
        console.log('[getArtist] Album:', albumCat.name, '-> Track categories:', trackCategories.length);
        let tracks: Track[] = [];

        if (trackCategories.length > 0) {
          console.log('[getArtist] Album has', trackCategories.length, 'track categories');
          // For each track category, get its products (song versions)
          tracks = await Promise.all(
            trackCategories.map(async (trackCat) => {
              console.log('[getArtist] Querying track:', trackCat.name, 'UID:', trackCat.uid);
              const productsData = await graphqlFetch<{ products: { items: MagentoProduct[]; total_count: number } }>(
                GET_SONGS_BY_CATEGORY_QUERY,
                { categoryUid: trackCat.uid, pageSize: 100 }
              );

              const products = productsData.products.items || [];
              console.log('[getArtist] Track:', trackCat.name, 'UID:', trackCat.uid, '-> Products:', products.length, 'Total:', productsData.products.total_count);
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

          // Check if any tracks have products
          const totalProducts = tracks.reduce((sum, t) => sum + t.songs.length, 0);

          // Fallback: if track categories exist but have no products, try fetching from album directly
          if (totalProducts === 0) {
            console.log('[getArtist] Track categories have 0 products, falling back to album-level products');
            const productsData = await graphqlFetch<{ products: { items: MagentoProduct[]; total_count: number } }>(
              GET_SONGS_BY_CATEGORY_QUERY,
              { categoryUid: albumCat.uid, pageSize: 500 }
            );

            const products = productsData.products.items || [];
            console.log('[getArtist] Found', products.length, 'products at album level');

            if (products.length > 0) {
              // Group products by song_title to create tracks
              tracks = groupProductsIntoTracks(
                products,
                albumCat.url_key,
                albumCat.name,
                category.uid,
                category.name,
                category.url_key
              );
            }
          }
        } else {
          // No track subcategories - products are directly under album
          // Fetch products from album category and group by song_title
          console.log('[getArtist] No track categories for album:', albumCat.name, '- fetching products directly');
          const productsData = await graphqlFetch<{ products: { items: MagentoProduct[]; total_count: number } }>(
            GET_SONGS_BY_CATEGORY_QUERY,
            { categoryUid: albumCat.uid, pageSize: 500 }
          );

          const products = productsData.products.items || [];
          console.log('[getArtist] Found', products.length, 'products directly under album');

          // Group products by song_title to create tracks
          tracks = groupProductsIntoTracks(
            products,
            albumCat.url_key,
            albumCat.name,
            category.uid,
            category.name,
            category.url_key
          );
        }

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
          coverArt: getAlbumCoverArt(albumCat.url_key),
        };
      })
    );

    // Flatten all songs from all albums for backwards compatibility
    const songs: Song[] = albums.flatMap(album =>
      album.tracks.flatMap(track => track.songs)
    );

    // Fetch Wikipedia summary using the artist name
    // Convert spaces to underscores for Wikipedia page title format
    const wikipediaPageTitle = artist.name.replace(/ /g, '_');
    const wikipediaSummary = await fetchWikipediaSummary(wikipediaPageTitle);

    return {
      ...artist,
      albums,
      songs,
      albumCount: albums.length,
      songCount: songs.length,
      wikipediaSummary,
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

// Lightweight function to get artist albums (no tracks/products)
// Used by the artists listing page for better performance
export async function getArtistAlbums(slug: string): Promise<{ artist: Artist; albums: Album[] } | null> {
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

    // Get child categories (albums) - just metadata, no products
    const albumCategoriesData = await graphqlFetch<{ categoryList: MagentoCategory[] }>(
      GET_CHILD_CATEGORIES_QUERY,
      { parentUid: category.uid }
    );

    const albumCategories = albumCategoriesData.categoryList || [];

    // Transform to lightweight Album objects (no tracks, no products)
    const albums: Album[] = albumCategories.map((albumCat) => ({
      id: albumCat.uid,
      identifier: albumCat.url_key,
      name: albumCat.name,
      slug: albumCat.url_key,
      artistId: category.uid,
      artistName: category.name,
      artistSlug: category.url_key,
      tracks: [], // Empty - not fetched
      totalTracks: albumCat.product_count || 0, // Use category product count as estimate
      totalSongs: 0,
      totalDuration: 0,
      coverArt: getAlbumCoverArt(albumCat.url_key),
    }));

    return { artist, albums };
  } catch (error) {
    console.error('Failed to fetch artist albums:', error);
    return null;
  }
}

// Get a specific album by artist slug and album identifier/slug
export async function getAlbum(
  artistSlug: string,
  albumIdentifier: string
): Promise<Album | null> {
  console.log('[getAlbum] Fetching album:', artistSlug, albumIdentifier);
  const artist = await getArtist(artistSlug);
  if (!artist) {
    console.log('[getAlbum] Artist not found:', artistSlug);
    return null;
  }

  console.log('[getAlbum] Artist has', artist.albums.length, 'albums');

  // Match by slug or original identifier
  const album = artist.albums.find(
    a => a.slug === albumIdentifier || a.identifier === albumIdentifier
  );

  if (album) {
    console.log('[getAlbum] Found album:', album.name, 'with', album.tracks.length, 'tracks');
  } else {
    console.log('[getAlbum] Album not found:', albumIdentifier);
  }

  return album || null;
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

// Search across artists, albums, and tracks
export async function search(query: string): Promise<{
  artists: Artist[];
  albums: Album[];
  tracks: Song[];
}> {
  console.log('[search] Starting search for:', query);

  if (!query.trim()) {
    return { artists: [], albums: [], tracks: [] };
  }

  try {
    // Search artists - get all and filter client-side
    // (Magento categoryList doesn't support name search/match)
    console.log('[search] Fetching all artists...');
    const allArtists = await getArtists();
    console.log('[search] Got artists:', allArtists.length);

    const searchLower = query.toLowerCase();
    const artists = allArtists.filter(artist =>
      artist.name.toLowerCase().includes(searchLower)
    ).slice(0, 10); // Limit to 10 results

    console.log('[search] Filtered artists:', artists.length);

    // For now, just return artists
    // Product search has issues with Magento GraphQL - will fix separately
    console.log('[search] Returning results:', { artists: artists.length, albums: 0, tracks: 0 });
    return { artists, albums: [], tracks: [] };
  } catch (error) {
    console.error('[search] Search failed with error:', error);
    return { artists: [], albums: [], tracks: [] };
  }
}
