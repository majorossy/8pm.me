/**
 * Analytics utilities for Google Analytics 4 event tracking.
 *
 * Events are tracked for user engagement metrics:
 * - Song plays (most important - measures content consumption)
 * - Playlist interactions (measures engagement depth)
 * - Search activity (measures discovery patterns)
 * - Share actions (measures viral potential)
 */

import type { Song, Album, Artist } from './types';

// Extend Window interface for gtag
declare global {
  interface Window {
    gtag?: (
      command: 'event' | 'config' | 'set',
      action: string,
      params?: Record<string, unknown>
    ) => void;
  }
}

/**
 * Core event tracking function
 */
export function trackEvent(
  action: string,
  category: string,
  label?: string,
  value?: number
): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', action, {
      event_category: category,
      event_label: label,
      value: value,
    });
  }
}

// ============================================
// Audio Events
// ============================================

/**
 * Track when a song starts playing
 */
export function trackSongPlay(song: Song): void {
  trackEvent('play', 'Audio', `${song.artistName} - ${song.trackTitle}`);

  // Also track with structured params for GA4 analysis
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'song_play', {
      artist_name: song.artistName,
      track_title: song.trackTitle,
      album_name: song.albumName,
      show_venue: song.showVenue,
      show_date: song.showDate,
    });
  }
}

/**
 * Track when a song completes (listened to >90%)
 */
export function trackSongComplete(song: Song): void {
  trackEvent('complete', 'Audio', `${song.artistName} - ${song.trackTitle}`);
}

/**
 * Track seeking within a song
 */
export function trackSeek(song: Song, seekToPercent: number): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'seek', {
      event_category: 'Audio',
      artist_name: song.artistName,
      track_title: song.trackTitle,
      seek_percent: Math.round(seekToPercent),
    });
  }
}

// ============================================
// Playlist Events
// ============================================

/**
 * Track adding a song to a playlist
 */
export function trackAddToPlaylist(song: Song, playlistName?: string): void {
  trackEvent('add_to_playlist', 'Engagement', song.trackTitle);

  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'add_to_playlist', {
      artist_name: song.artistName,
      track_title: song.trackTitle,
      playlist_name: playlistName || 'default',
    });
  }
}

/**
 * Track adding a song to the queue
 */
export function trackAddToQueue(song: Song): void {
  trackEvent('add_to_queue', 'Engagement', song.trackTitle);
}

/**
 * Track liking/favoriting a song
 */
export function trackLike(song: Song): void {
  trackEvent('like', 'Engagement', `${song.artistName} - ${song.trackTitle}`);
}

/**
 * Track unliking a song
 */
export function trackUnlike(song: Song): void {
  trackEvent('unlike', 'Engagement', `${song.artistName} - ${song.trackTitle}`);
}

// ============================================
// Search Events
// ============================================

/**
 * Track search queries and result counts
 */
export function trackSearch(query: string, resultsCount: number): void {
  trackEvent('search', 'Discovery', query, resultsCount);

  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'search', {
      search_term: query,
      results_count: resultsCount,
    });
  }
}

/**
 * Track when user clicks a search result
 */
export function trackSearchResultClick(
  query: string,
  resultType: 'artist' | 'album' | 'track' | 'song',
  resultName: string,
  position: number
): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'search_result_click', {
      search_term: query,
      result_type: resultType,
      result_name: resultName,
      position: position,
    });
  }
}

// ============================================
// Share Events
// ============================================

/**
 * Track share actions
 */
export function trackShare(
  contentType: 'song' | 'album' | 'artist' | 'playlist',
  contentName: string,
  method?: 'copy_link' | 'native_share' | 'twitter' | 'facebook'
): void {
  trackEvent('share', 'Social', `${contentType}: ${contentName}`);

  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'share', {
      content_type: contentType,
      item_id: contentName,
      method: method || 'unknown',
    });
  }
}

// ============================================
// Navigation Events
// ============================================

/**
 * Track artist page views
 */
export function trackArtistView(artist: Artist): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'view_artist', {
      artist_name: artist.name,
      artist_id: artist.id,
      album_count: artist.albumCount,
    });
  }
}

/**
 * Track album page views
 */
export function trackAlbumView(album: Album): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'view_album', {
      artist_name: album.artistName,
      album_name: album.name,
      show_venue: album.showVenue,
      show_date: album.showDate,
      track_count: album.totalTracks,
    });
  }
}

// ============================================
// Error Events
// ============================================

/**
 * Track playback errors
 */
export function trackPlaybackError(song: Song, errorType: string): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'playback_error', {
      event_category: 'Error',
      artist_name: song.artistName,
      track_title: song.trackTitle,
      stream_url: song.streamUrl,
      error_type: errorType,
    });
  }
}

/**
 * Track general errors
 */
export function trackError(errorType: string, errorMessage: string): void {
  if (typeof window !== 'undefined' && window.gtag) {
    window.gtag('event', 'exception', {
      description: `${errorType}: ${errorMessage}`,
      fatal: false,
    });
  }
}
