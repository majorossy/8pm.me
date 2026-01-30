/**
 * Festival Lineup Sorting Algorithms
 *
 * Pure functions for sorting artists by different metrics.
 * All functions return new arrays without mutating inputs.
 */

export type SortAlgorithm = 'balanced' | 'songs' | 'catalog';

export interface ArtistWithStats {
  slug: string;
  name: string;
  songCount: number;
  albumCount: number;
  totalRecordings?: number;
  [key: string]: any;
}

/**
 * Normalize a value to 0-1 range using min-max scaling
 */
function normalize(value: number, min: number, max: number): number {
  if (max === min) return 0;
  return (value - min) / (max - min);
}

/**
 * Balanced Algorithm: 75% songCount + 25% albumCount (normalized)
 * Current default behavior - prevents catalog size from dominating
 */
export function sortBalanced(artists: ArtistWithStats[]): ArtistWithStats[] {
  if (artists.length === 0) return [];
  if (artists.length === 1) return [...artists];

  // Find min/max for normalization
  const songCounts = artists.map(a => a.songCount);
  const albumCounts = artists.map(a => a.albumCount);

  const minSongs = Math.min(...songCounts);
  const maxSongs = Math.max(...songCounts);
  const minAlbums = Math.min(...albumCounts);
  const maxAlbums = Math.max(...albumCounts);

  // Calculate weighted scores and sort
  return [...artists].sort((a, b) => {
    const aSongNorm = normalize(a.songCount, minSongs, maxSongs);
    const aAlbumNorm = normalize(a.albumCount, minAlbums, maxAlbums);
    const aScore = (aSongNorm * 0.75) + (aAlbumNorm * 0.25);

    const bSongNorm = normalize(b.songCount, minSongs, maxSongs);
    const bAlbumNorm = normalize(b.albumCount, minAlbums, maxAlbums);
    const bScore = (bSongNorm * 0.75) + (bAlbumNorm * 0.25);

    return bScore - aScore; // Descending
  });
}

/**
 * Songs Algorithm: 100% songCount (normalized)
 * Pure track count - who has the most individual recordings?
 */
export function sortBySongs(artists: ArtistWithStats[]): ArtistWithStats[] {
  if (artists.length === 0) return [];
  if (artists.length === 1) return [...artists];

  const songCounts = artists.map(a => a.songCount);
  const minSongs = Math.min(...songCounts);
  const maxSongs = Math.max(...songCounts);

  return [...artists].sort((a, b) => {
    const aNorm = normalize(a.songCount, minSongs, maxSongs);
    const bNorm = normalize(b.songCount, minSongs, maxSongs);
    return bNorm - aNorm; // Descending
  });
}

/**
 * Catalog Algorithm: totalRecordings (or songCount fallback)
 * Largest catalog size - prioritizes prolific artists
 */
export function sortByCatalog(artists: ArtistWithStats[]): ArtistWithStats[] {
  if (artists.length === 0) return [];
  if (artists.length === 1) return [...artists];

  // Use totalRecordings if available, otherwise fall back to songCount
  const getCatalogSize = (artist: ArtistWithStats) =>
    artist.totalRecordings ?? artist.songCount;

  const catalogSizes = artists.map(getCatalogSize);
  const minCatalog = Math.min(...catalogSizes);
  const maxCatalog = Math.max(...catalogSizes);

  return [...artists].sort((a, b) => {
    const aNorm = normalize(getCatalogSize(a), minCatalog, maxCatalog);
    const bNorm = normalize(getCatalogSize(b), minCatalog, maxCatalog);
    return bNorm - aNorm; // Descending
  });
}

/**
 * Main entry point - sort by specified algorithm
 */
export function sortArtistsByAlgorithm(
  artists: ArtistWithStats[],
  algorithm: SortAlgorithm
): ArtistWithStats[] {
  switch (algorithm) {
    case 'balanced':
      return sortBalanced(artists);
    case 'songs':
      return sortBySongs(artists);
    case 'catalog':
      return sortByCatalog(artists);
    default:
      // Fallback to balanced for invalid algorithms
      return sortBalanced(artists);
  }
}

/**
 * Validate algorithm string
 */
export function isValidAlgorithm(value: string): value is SortAlgorithm {
  return ['balanced', 'songs', 'catalog'].includes(value);
}
