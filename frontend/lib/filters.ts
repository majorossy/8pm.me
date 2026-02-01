/**
 * Filter Utilities for Search
 *
 * Functions for filtering song versions by year, venue, date range, and recording type.
 * Used by search to hide tracks with no matching versions.
 */

import { Song } from './types';
import { isSoundboard } from './lineageUtils';

/**
 * Version filters interface
 * Matches the VersionFilters from SearchFilters.tsx
 */
export interface VersionFilters {
  year?: number;
  dateFrom?: string;
  dateTo?: string;
  venue?: string;
  isSoundboard?: boolean;
  artist?: string;      // Artist slug filter
  minRating?: number;   // Minimum rating (1-5)
}

/**
 * Check if any filters are active
 */
export function hasActiveFilters(filters: VersionFilters): boolean {
  return !!(
    filters.year ||
    filters.dateFrom ||
    filters.dateTo ||
    filters.venue ||
    filters.isSoundboard ||
    filters.artist ||
    filters.minRating
  );
}

/**
 * Apply filters to a list of song versions
 * Returns only versions matching ALL active filters
 */
export function applyFilters(versions: Song[], filters: VersionFilters): Song[] {
  if (!hasActiveFilters(filters)) {
    return versions;
  }

  return versions.filter(version => {
    // Year filter
    if (filters.year) {
      const versionYear = extractYear(version.showDate);
      if (versionYear !== filters.year) return false;
    }

    // Date range filter
    if (filters.dateFrom || filters.dateTo) {
      const versionDate = version.showDate || '';
      if (filters.dateFrom && versionDate < filters.dateFrom) return false;
      if (filters.dateTo && versionDate > filters.dateTo) return false;
    }

    // Venue filter (partial match, case-insensitive)
    if (filters.venue) {
      const venue = version.showVenue || '';
      if (!venue.toLowerCase().includes(filters.venue.toLowerCase())) return false;
    }

    // Soundboard filter
    if (filters.isSoundboard) {
      if (!isSoundboard(version.lineage)) return false;
    }

    // Artist filter (exact match on slug)
    if (filters.artist) {
      if (version.artistSlug !== filters.artist) return false;
    }

    // Minimum rating filter
    if (filters.minRating) {
      const rating = version.avgRating || 0;
      if (rating < filters.minRating) return false;
    }

    return true;
  });
}

/**
 * Extract year from show date string
 * Handles formats: "1972-05-11", "May 11, 1972", "1972"
 */
export function extractYear(showDate?: string): number | null {
  if (!showDate) return null;

  // Match 4-digit year (19xx or 20xx)
  const match = showDate.match(/\b(19|20)\d{2}\b/);
  return match ? parseInt(match[0], 10) : null;
}

/**
 * Get unique years from a list of versions
 * Used to populate the year dropdown with available options
 * Returns years in descending order (newest first)
 */
export function getAvailableYears(versions: Song[]): number[] {
  const years = new Set<number>();

  versions.forEach(version => {
    const year = extractYear(version.showDate);
    if (year) years.add(year);
  });

  return Array.from(years).sort((a, b) => b - a);
}

/**
 * Get unique venues from a list of versions
 * Could be used for venue autocomplete in the future
 */
export function getAvailableVenues(versions: Song[]): string[] {
  const venues = new Set<string>();

  versions.forEach(version => {
    if (version.showVenue) {
      venues.add(version.showVenue);
    }
  });

  return Array.from(venues).sort();
}

/**
 * Count soundboard recordings in a list of versions
 */
export function countSoundboards(versions: Song[]): number {
  return versions.filter(v => isSoundboard(v.lineage)).length;
}

/**
 * Get a summary of available filter options from versions
 * Useful for showing filter badges like "5 SBD recordings"
 */
export function getFilterSummary(versions: Song[]): {
  totalVersions: number;
  soundboardCount: number;
  years: number[];
  yearRange: { min: number; max: number } | null;
} {
  const years = getAvailableYears(versions);

  return {
    totalVersions: versions.length,
    soundboardCount: countSoundboards(versions),
    years,
    yearRange: years.length > 0
      ? { min: Math.min(...years), max: Math.max(...years) }
      : null,
  };
}
