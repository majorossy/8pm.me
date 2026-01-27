// Queue types for album-centric playback model
// Queue = Album with selectable versions per track + Up Next extras

import { Song, Album, Track } from './types';

// A track slot in the queue with selectable version
export interface QueueTrack {
  trackId: string;              // Unique identifier for this track slot
  title: string;                // Track title (e.g., "Dark Star")
  trackSlug: string;            // URL-safe track identifier
  albumIdentifier: string;      // Parent album's Archive.org identifier
  artistSlug: string;           // Artist URL slug
  availableVersions: Song[];    // All available recordings of this track
  selectedVersionId: string;    // Currently selected version's ID
  albumTrackIndex: number;      // Position in album (0-based)
}

// Extra songs queued after the album finishes
export interface UpNextItem {
  id: string;                   // Unique ID for this queue entry
  song: Song;                   // The actual song
  addedAt: number;              // Timestamp when added
  source: 'manual' | 'autoplay'; // How it was added
}

// Album metadata stored in queue
export interface QueueAlbum {
  id: string;
  identifier: string;
  name: string;
  artistSlug: string;
  artistName: string;
  coverArt?: string;
}

// The complete queue state
export interface AlbumQueue {
  album: QueueAlbum | null;     // Current album being played
  tracks: QueueTrack[];         // Album tracks with version selection
  currentTrackIndex: number;    // Which track is currently playing (-1 if none)
  upNext: UpNextItem[];         // Songs queued after album
  shuffle: boolean;             // Shuffle mode
  repeat: 'off' | 'all' | 'one'; // Repeat mode
}

// Initial empty queue state
export const initialQueueState: AlbumQueue = {
  album: null,
  tracks: [],
  currentTrackIndex: -1,
  upNext: [],
  shuffle: false,
  repeat: 'off',
};

// =============================================================================
// Utility Functions
// =============================================================================

/**
 * Get the best version of a track (highest rated by avgRating)
 * Falls back to first version if all ratings are null
 */
export function getBestVersion(songs: Song[]): Song {
  if (songs.length === 0) {
    throw new Error('Cannot get best version from empty songs array');
  }

  if (songs.length === 1) {
    return songs[0];
  }

  // Sort by avgRating descending, treating null/undefined as -1
  const sorted = [...songs].sort((a, b) => {
    const ratingA = a.avgRating ?? -1;
    const ratingB = b.avgRating ?? -1;
    return ratingB - ratingA;
  });

  return sorted[0];
}

/**
 * Convert an Album to AlbumQueue state with best versions auto-selected
 */
export function albumToQueue(album: Album): AlbumQueue {
  const queueAlbum: QueueAlbum = {
    id: album.id,
    identifier: album.identifier,
    name: album.name,
    artistSlug: album.artistSlug,
    artistName: album.artistName,
    coverArt: album.coverArt,
  };

  // Filter out tracks with no available recordings and map to queue tracks
  const tracks: QueueTrack[] = album.tracks
    .filter(track => track.songs && track.songs.length > 0) // Skip tracks with no recordings
    .map((track, index) => {
      const bestVersion = getBestVersion(track.songs);

      return {
        trackId: track.id,
        title: track.title,
        trackSlug: track.slug,
        albumIdentifier: album.identifier,
        artistSlug: album.artistSlug,
        availableVersions: track.songs,
        selectedVersionId: bestVersion.id,
        albumTrackIndex: index,
      };
    });

  return {
    album: queueAlbum,
    tracks,
    currentTrackIndex: 0, // Start at first track
    upNext: [],
    shuffle: false,
    repeat: 'off',
  };
}

/**
 * Get the currently selected Song for a QueueTrack
 */
export function getSelectedSong(track: QueueTrack): Song | null {
  return track.availableVersions.find(s => s.id === track.selectedVersionId) || null;
}

/**
 * Get the current song being played from queue state
 */
export function getCurrentSong(queue: AlbumQueue): Song | null {
  if (queue.currentTrackIndex < 0 || queue.currentTrackIndex >= queue.tracks.length) {
    // No track playing from album - check up next
    return null;
  }

  const currentTrack = queue.tracks[queue.currentTrackIndex];
  return getSelectedSong(currentTrack);
}

/**
 * Generate a unique ID for up-next items
 */
export function generateUpNextId(): string {
  return `upnext-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Create an UpNextItem from a Song
 */
export function createUpNextItem(song: Song, source: 'manual' | 'autoplay' = 'manual'): UpNextItem {
  return {
    id: generateUpNextId(),
    song,
    addedAt: Date.now(),
    source,
  };
}
