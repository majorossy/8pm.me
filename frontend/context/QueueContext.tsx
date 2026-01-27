'use client';

// QueueContext - Album-centric queue management
// Manages which album is loaded, which versions are selected, and Up Next list

import React, { createContext, useContext, useReducer, useCallback, useMemo } from 'react';
import { Song, Album } from '@/lib/types';
import {
  AlbumQueue,
  QueueTrack,
  UpNextItem,
  initialQueueState,
  albumToQueue,
  getSelectedSong,
  getCurrentSong,
  createUpNextItem,
} from '@/lib/queueTypes';

// =============================================================================
// Action Types
// =============================================================================

type QueueAction =
  | { type: 'LOAD_ALBUM'; album: Album; startTrackIndex?: number }
  | { type: 'SELECT_VERSION'; trackIndex: number; versionId: string }
  | { type: 'SET_CURRENT_TRACK'; index: number }
  | { type: 'NEXT_TRACK' }
  | { type: 'PREV_TRACK' }
  | { type: 'REMOVE_TRACK'; trackIndex: number }
  | { type: 'ADD_TO_UP_NEXT'; song: Song; source?: 'manual' | 'autoplay' }
  | { type: 'REMOVE_FROM_UP_NEXT'; itemId: string }
  | { type: 'REORDER_UP_NEXT'; fromIndex: number; toIndex: number }
  | { type: 'CLEAR_UP_NEXT' }
  | { type: 'POP_UP_NEXT' } // Remove first item from up next (for advancing)
  | { type: 'SET_SHUFFLE'; enabled: boolean }
  | { type: 'SET_REPEAT'; mode: 'off' | 'all' | 'one' }
  | { type: 'CLEAR_QUEUE' };

// =============================================================================
// Reducer
// =============================================================================

function queueReducer(state: AlbumQueue, action: QueueAction): AlbumQueue {
  switch (action.type) {
    case 'LOAD_ALBUM': {
      const newQueue = albumToQueue(action.album);
      // Preserve repeat and shuffle settings
      return {
        ...newQueue,
        currentTrackIndex: action.startTrackIndex ?? 0,
        shuffle: state.shuffle,
        repeat: state.repeat,
        // Preserve up-next when loading new album
        upNext: state.upNext,
      };
    }

    case 'SELECT_VERSION': {
      const { trackIndex, versionId } = action;
      if (trackIndex < 0 || trackIndex >= state.tracks.length) {
        return state;
      }

      const track = state.tracks[trackIndex];
      // Verify version exists
      if (!track.availableVersions.some(v => v.id === versionId)) {
        return state;
      }

      const updatedTracks = [...state.tracks];
      updatedTracks[trackIndex] = {
        ...track,
        selectedVersionId: versionId,
      };

      return {
        ...state,
        tracks: updatedTracks,
      };
    }

    case 'SET_CURRENT_TRACK': {
      const { index } = action;
      if (index < -1 || index >= state.tracks.length) {
        return state;
      }
      return {
        ...state,
        currentTrackIndex: index,
      };
    }

    case 'NEXT_TRACK': {
      // If repeat one, stay on current track (audio will restart)
      if (state.repeat === 'one') {
        return state;
      }

      const nextIndex = state.currentTrackIndex + 1;

      // If we're past the end of album tracks
      if (nextIndex >= state.tracks.length) {
        // Check up next
        if (state.upNext.length > 0) {
          // Don't advance index, player will handle up next
          return state;
        }

        // If repeat all, go back to start
        if (state.repeat === 'all') {
          return {
            ...state,
            currentTrackIndex: 0,
          };
        }

        // Otherwise stop (index stays at end)
        return state;
      }

      return {
        ...state,
        currentTrackIndex: nextIndex,
      };
    }

    case 'PREV_TRACK': {
      const prevIndex = state.currentTrackIndex - 1;

      if (prevIndex < 0) {
        // If repeat all, go to last track
        if (state.repeat === 'all') {
          return {
            ...state,
            currentTrackIndex: state.tracks.length - 1,
          };
        }
        // Otherwise stay at start
        return state;
      }

      return {
        ...state,
        currentTrackIndex: prevIndex,
      };
    }

    case 'REMOVE_TRACK': {
      const { trackIndex } = action;
      if (trackIndex < 0 || trackIndex >= state.tracks.length) {
        return state;
      }

      const updatedTracks = state.tracks.filter((_, idx) => idx !== trackIndex);

      // Adjust current track index if needed
      let newCurrentIndex = state.currentTrackIndex;
      if (trackIndex < state.currentTrackIndex) {
        // Removed track was before current, shift index down
        newCurrentIndex = Math.max(0, state.currentTrackIndex - 1);
      } else if (trackIndex === state.currentTrackIndex) {
        // Removed the current track, keep same index (will play next track)
        newCurrentIndex = Math.min(state.currentTrackIndex, updatedTracks.length - 1);
      }

      return {
        ...state,
        tracks: updatedTracks,
        currentTrackIndex: updatedTracks.length > 0 ? newCurrentIndex : -1,
      };
    }

    case 'ADD_TO_UP_NEXT': {
      const newItem = createUpNextItem(action.song, action.source || 'manual');
      return {
        ...state,
        upNext: [...state.upNext, newItem],
      };
    }

    case 'REMOVE_FROM_UP_NEXT': {
      return {
        ...state,
        upNext: state.upNext.filter(item => item.id !== action.itemId),
      };
    }

    case 'REORDER_UP_NEXT': {
      const { fromIndex, toIndex } = action;
      if (
        fromIndex < 0 ||
        fromIndex >= state.upNext.length ||
        toIndex < 0 ||
        toIndex >= state.upNext.length
      ) {
        return state;
      }

      const newUpNext = [...state.upNext];
      const [removed] = newUpNext.splice(fromIndex, 1);
      newUpNext.splice(toIndex, 0, removed);

      return {
        ...state,
        upNext: newUpNext,
      };
    }

    case 'CLEAR_UP_NEXT': {
      return {
        ...state,
        upNext: [],
      };
    }

    case 'POP_UP_NEXT': {
      if (state.upNext.length === 0) {
        return state;
      }
      return {
        ...state,
        upNext: state.upNext.slice(1),
      };
    }

    case 'SET_SHUFFLE': {
      return {
        ...state,
        shuffle: action.enabled,
      };
    }

    case 'SET_REPEAT': {
      return {
        ...state,
        repeat: action.mode,
      };
    }

    case 'CLEAR_QUEUE': {
      return {
        ...initialQueueState,
        shuffle: state.shuffle,
        repeat: state.repeat,
      };
    }

    default:
      return state;
  }
}

// =============================================================================
// Context Type
// =============================================================================

interface QueueContextType {
  // State
  queue: AlbumQueue;

  // Computed values
  currentTrack: QueueTrack | null;
  currentSong: Song | null;
  hasAlbum: boolean;
  isLastTrack: boolean;
  isFirstTrack: boolean;
  totalTracks: number;
  hasUpNext: boolean;

  // Actions
  loadAlbum: (album: Album, startTrackIndex?: number) => void;
  selectVersion: (trackIndex: number, versionId: string) => void;
  setCurrentTrack: (index: number) => void;
  nextTrack: () => Song | null; // Returns next song to play (could be from up-next)
  peekNextTrack: () => Song | null; // Returns next song without advancing queue
  prevTrack: () => void;
  removeTrack: (trackIndex: number) => void;
  addToUpNext: (song: Song, source?: 'manual' | 'autoplay') => void;
  removeFromUpNext: (itemId: string) => void;
  reorderUpNext: (fromIndex: number, toIndex: number) => void;
  clearUpNext: () => void;
  setShuffle: (enabled: boolean) => void;
  setRepeat: (mode: 'off' | 'all' | 'one') => void;
  clearQueue: () => void;

  // Helper to get song at a specific track index
  getSongAtTrack: (index: number) => Song | null;
}

const QueueContext = createContext<QueueContextType | null>(null);

// =============================================================================
// Provider
// =============================================================================

export function QueueProvider({ children }: { children: React.ReactNode }) {
  const [queue, dispatch] = useReducer(queueReducer, initialQueueState);

  // Computed values
  const currentTrack = useMemo(() => {
    if (queue.currentTrackIndex < 0 || queue.currentTrackIndex >= queue.tracks.length) {
      return null;
    }
    return queue.tracks[queue.currentTrackIndex];
  }, [queue.tracks, queue.currentTrackIndex]);

  const currentSong = useMemo(() => {
    return getCurrentSong(queue);
  }, [queue]);

  const hasAlbum = queue.album !== null;
  const isLastTrack = queue.currentTrackIndex >= queue.tracks.length - 1;
  const isFirstTrack = queue.currentTrackIndex <= 0;
  const totalTracks = queue.tracks.length;
  const hasUpNext = queue.upNext.length > 0;

  // Actions
  const loadAlbum = useCallback((album: Album, startTrackIndex?: number) => {
    dispatch({ type: 'LOAD_ALBUM', album, startTrackIndex });
  }, []);

  const selectVersion = useCallback((trackIndex: number, versionId: string) => {
    dispatch({ type: 'SELECT_VERSION', trackIndex, versionId });
  }, []);

  const setCurrentTrack = useCallback((index: number) => {
    dispatch({ type: 'SET_CURRENT_TRACK', index });
  }, []);

  const nextTrack = useCallback((): Song | null => {
    // If repeat one, return current song (let player restart)
    if (queue.repeat === 'one') {
      return currentSong;
    }

    const nextIndex = queue.currentTrackIndex + 1;

    // Still have album tracks
    if (nextIndex < queue.tracks.length) {
      dispatch({ type: 'NEXT_TRACK' });
      const nextTrackObj = queue.tracks[nextIndex];
      return getSelectedSong(nextTrackObj);
    }

    // Album finished - check up next
    if (queue.upNext.length > 0) {
      const nextSong = queue.upNext[0].song;
      dispatch({ type: 'POP_UP_NEXT' });
      return nextSong;
    }

    // If repeat all, go back to start
    if (queue.repeat === 'all') {
      dispatch({ type: 'NEXT_TRACK' });
      const firstTrack = queue.tracks[0];
      return firstTrack ? getSelectedSong(firstTrack) : null;
    }

    // Nothing more to play
    return null;
  }, [queue, currentSong]);

  const peekNextTrack = useCallback((): Song | null => {
    // If repeat one, return current song
    if (queue.repeat === 'one') {
      return currentSong;
    }

    const nextIndex = queue.currentTrackIndex + 1;

    // Still have album tracks
    if (nextIndex < queue.tracks.length) {
      const nextTrackObj = queue.tracks[nextIndex];
      return getSelectedSong(nextTrackObj);
    }

    // Album finished - check up next
    if (queue.upNext.length > 0) {
      return queue.upNext[0].song;
    }

    // If repeat all, go back to start
    if (queue.repeat === 'all') {
      const firstTrack = queue.tracks[0];
      return firstTrack ? getSelectedSong(firstTrack) : null;
    }

    // Nothing more to play
    return null;
  }, [queue, currentSong]);

  const prevTrack = useCallback(() => {
    dispatch({ type: 'PREV_TRACK' });
  }, []);

  const removeTrack = useCallback((trackIndex: number) => {
    dispatch({ type: 'REMOVE_TRACK', trackIndex });
  }, []);

  const addToUpNext = useCallback((song: Song, source?: 'manual' | 'autoplay') => {
    dispatch({ type: 'ADD_TO_UP_NEXT', song, source });
  }, []);

  const removeFromUpNext = useCallback((itemId: string) => {
    dispatch({ type: 'REMOVE_FROM_UP_NEXT', itemId });
  }, []);

  const reorderUpNext = useCallback((fromIndex: number, toIndex: number) => {
    dispatch({ type: 'REORDER_UP_NEXT', fromIndex, toIndex });
  }, []);

  const clearUpNext = useCallback(() => {
    dispatch({ type: 'CLEAR_UP_NEXT' });
  }, []);

  const setShuffle = useCallback((enabled: boolean) => {
    dispatch({ type: 'SET_SHUFFLE', enabled });
  }, []);

  const setRepeat = useCallback((mode: 'off' | 'all' | 'one') => {
    dispatch({ type: 'SET_REPEAT', mode });
  }, []);

  const clearQueue = useCallback(() => {
    dispatch({ type: 'CLEAR_QUEUE' });
  }, []);

  const getSongAtTrack = useCallback((index: number): Song | null => {
    if (index < 0 || index >= queue.tracks.length) {
      return null;
    }
    return getSelectedSong(queue.tracks[index]);
  }, [queue.tracks]);

  const value: QueueContextType = {
    queue,
    currentTrack,
    currentSong,
    hasAlbum,
    isLastTrack,
    isFirstTrack,
    totalTracks,
    hasUpNext,
    loadAlbum,
    selectVersion,
    setCurrentTrack,
    nextTrack,
    peekNextTrack,
    prevTrack,
    removeTrack,
    addToUpNext,
    removeFromUpNext,
    reorderUpNext,
    clearUpNext,
    setShuffle,
    setRepeat,
    clearQueue,
    getSongAtTrack,
  };

  return (
    <QueueContext.Provider value={value}>
      {children}
    </QueueContext.Provider>
  );
}

// =============================================================================
// Hook
// =============================================================================

export function useQueue() {
  const context = useContext(QueueContext);
  if (!context) {
    throw new Error('useQueue must be used within a QueueProvider');
  }
  return context;
}
