/**
 * Festival Lineup Sorting Algorithms
 *
 * Pure functions for sorting artists by different metrics.
 * All functions return new arrays without mutating inputs.
 */

export type SortAlgorithm = 'songVersions' | 'shows' | 'hours';

export interface ArtistWithStats {
  slug: string;
  name: string;
  songCount: number;
  albumCount: number;
  totalRecordings?: number;
  totalShows?: number;
  totalHours?: number;
  [key: string]: any;
}

/**
 * Song Versions Algorithm: Sort by songCount descending
 * Who has the most individual track recordings?
 */
export function sortBySongVersions(artists: ArtistWithStats[]): ArtistWithStats[] {
  if (artists.length === 0) return [];
  if (artists.length === 1) return [...artists];

  return [...artists].sort((a, b) => {
    return (b.songCount || 0) - (a.songCount || 0);
  });
}

/**
 * Shows Algorithm: Sort by totalShows descending
 * Who has the most recorded live shows?
 */
export function sortByShows(artists: ArtistWithStats[]): ArtistWithStats[] {
  if (artists.length === 0) return [];
  if (artists.length === 1) return [...artists];

  return [...artists].sort((a, b) => {
    return (b.totalShows || 0) - (a.totalShows || 0);
  });
}

/**
 * Hours Algorithm: Sort by totalHours descending
 * Who has the most hours of recorded music?
 */
export function sortByHours(artists: ArtistWithStats[]): ArtistWithStats[] {
  if (artists.length === 0) return [];
  if (artists.length === 1) return [...artists];

  return [...artists].sort((a, b) => {
    return (b.totalHours || 0) - (a.totalHours || 0);
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
    case 'songVersions':
      return sortBySongVersions(artists);
    case 'shows':
      return sortByShows(artists);
    case 'hours':
      return sortByHours(artists);
    default:
      return sortBySongVersions(artists); // Default fallback
  }
}

/**
 * Validate algorithm string
 */
export function isValidAlgorithm(value: string): value is SortAlgorithm {
  return ['songVersions', 'shows', 'hours'].includes(value);
}
