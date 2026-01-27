'use client';

// PlayerContext = Audio playback only
// Queue management is handled by QueueContext

import React, { createContext, useContext, useState, useRef, useEffect, useCallback } from 'react';
import { Song, Album, Track } from '@/lib/types';
import { useQueue } from './QueueContext';
import { useRecentlyPlayed } from './RecentlyPlayedContext';

interface PlayerState {
  isPlaying: boolean;
  volume: number;
  currentTime: number;
  duration: number;
  isQueueOpen: boolean;  // UI state for queue drawer
}

interface PlayerContextType extends PlayerState {
  // Current song comes from QueueContext
  currentSong: Song | null;

  // Playback controls
  playSong: (song: Song) => void;
  togglePlay: () => void;
  pause: () => void;
  setVolume: (volume: number) => void;
  seek: (time: number) => void;
  playNext: () => void;
  playPrev: () => void;

  // Queue UI
  toggleQueue: () => void;

  // Play from queue (by track index in album)
  playFromQueue: (index: number) => void;

  // Album/track playback - delegates to QueueContext
  playAlbum: (album: Album, startIndex?: number) => void;
  playTrack: (track: Track, songIndex?: number) => void;

  // Legacy queue access for compatibility (maps from QueueContext)
  queue: Song[];
  queueIndex: number;
}

const PlayerContext = createContext<PlayerContextType | null>(null);

export function PlayerProvider({ children }: { children: React.ReactNode }) {
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const queueContext = useQueue();
  const { trackPlay } = useRecentlyPlayed();
  const trackedSongsRef = useRef<Set<string>>(new Set()); // Track which songs we've already counted

  // Get current song from QueueContext
  const currentSong = queueContext.currentSong;

  // Build legacy queue from QueueContext for compatibility
  const queue: Song[] = queueContext.queue.tracks.map(track => {
    const version = track.availableVersions.find(v => v.id === track.selectedVersionId);
    return version!;
  }).filter(Boolean);

  const queueIndex = queueContext.queue.currentTrackIndex;

  const [state, setState] = useState<PlayerState>({
    isPlaying: false,
    volume: 0.7,
    currentTime: 0,
    duration: 0,
    isQueueOpen: false,
  });

  // Initialize audio element
  useEffect(() => {
    audioRef.current = new Audio();
    audioRef.current.volume = state.volume;

    const audio = audioRef.current;

    const handleTimeUpdate = () => {
      setState(prev => ({ ...prev, currentTime: audio.currentTime }));
    };

    const handleLoadedMetadata = () => {
      setState(prev => ({ ...prev, duration: audio.duration }));
    };

    const handleEnded = () => {
      // Get next song from queue context
      const nextSong = queueContext.nextTrack();

      if (nextSong) {
        // Play the next song
        if (audioRef.current) {
          audioRef.current.src = nextSong.streamUrl;
          audioRef.current.play().catch(console.error);
        }
      } else {
        // Nothing more to play
        setState(prev => ({ ...prev, isPlaying: false }));
      }
    };

    audio.addEventListener('timeupdate', handleTimeUpdate);
    audio.addEventListener('loadedmetadata', handleLoadedMetadata);
    audio.addEventListener('ended', handleEnded);

    return () => {
      audio.removeEventListener('timeupdate', handleTimeUpdate);
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audio.removeEventListener('ended', handleEnded);
      audio.pause();
    };
  }, []);

  // Update ended handler when queue/repeat changes
  useEffect(() => {
    if (!audioRef.current) return;

    const audio = audioRef.current;
    const handleEnded = () => {
      // Get next song from queue context
      const nextSong = queueContext.nextTrack();

      if (nextSong) {
        audio.src = nextSong.streamUrl;
        audio.play().catch(console.error);
      } else {
        setState(prev => ({ ...prev, isPlaying: false }));
      }
    };

    audio.addEventListener('ended', handleEnded);
    return () => audio.removeEventListener('ended', handleEnded);
  }, [queueContext]);

  // When currentSong changes (from QueueContext), update audio
  useEffect(() => {
    if (!audioRef.current || !currentSong) return;

    // Check if we need to load a new source
    const currentSrc = audioRef.current.src;
    const newSrc = currentSong.streamUrl;

    // Only reload if the source URL changed
    if (!currentSrc.endsWith(new URL(newSrc, window.location.origin).pathname)) {
      audioRef.current.src = newSrc;
      if (state.isPlaying) {
        audioRef.current.play().catch(console.error);
      }
    }
  }, [currentSong]);

  // Track song as "played" after 30 seconds of playback
  useEffect(() => {
    if (!currentSong || !state.isPlaying) return;

    // Check if we've already tracked this song
    if (trackedSongsRef.current.has(currentSong.id)) return;

    // Set a timeout to track the song after 30 seconds
    const trackingTimeout = setTimeout(() => {
      if (state.currentTime >= 30) {
        trackPlay(currentSong);
        trackedSongsRef.current.add(currentSong.id);
      }
    }, 30000); // 30 seconds

    return () => clearTimeout(trackingTimeout);
  }, [currentSong, state.isPlaying, state.currentTime, trackPlay]);

  // Clear tracked songs when song changes
  useEffect(() => {
    if (currentSong) {
      // Reset tracking for new song (allow same song to be tracked again if replayed later)
      trackedSongsRef.current.delete(currentSong.id);
    }
  }, [currentSong]);

  const playSong = useCallback((song: Song) => {
    if (!audioRef.current) return;

    // Add to up-next (this song might not be part of current album)
    queueContext.addToUpNext(song);

    setState(prev => ({
      ...prev,
      isPlaying: true,
    }));

    audioRef.current.src = song.streamUrl;
    audioRef.current.play().catch(err => {
      console.error('Audio play error:', err);
      setState(prev => ({ ...prev, isPlaying: false }));
    });
  }, [queueContext]);

  const pause = useCallback(() => {
    if (!audioRef.current) return;
    audioRef.current.pause();
    setState(prev => ({ ...prev, isPlaying: false }));
  }, []);

  const togglePlay = useCallback(() => {
    if (!audioRef.current || !currentSong) return;

    if (state.isPlaying) {
      audioRef.current.pause();
    } else {
      audioRef.current.play().catch(console.error);
    }
    setState(prev => ({ ...prev, isPlaying: !prev.isPlaying }));
  }, [state.isPlaying, currentSong]);

  const setVolume = useCallback((volume: number) => {
    if (!audioRef.current) return;
    audioRef.current.volume = volume;
    setState(prev => ({ ...prev, volume }));
  }, []);

  const seek = useCallback((time: number) => {
    if (!audioRef.current) return;
    audioRef.current.currentTime = time;
    setState(prev => ({ ...prev, currentTime: time }));
  }, []);

  const playNext = useCallback(() => {
    const nextSong = queueContext.nextTrack();
    if (nextSong && audioRef.current) {
      audioRef.current.src = nextSong.streamUrl;
      audioRef.current.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true }));
    }
  }, [queueContext]);

  const playPrev = useCallback(() => {
    // If more than 3 seconds into song, restart it
    if (audioRef.current && audioRef.current.currentTime > 3) {
      audioRef.current.currentTime = 0;
      return;
    }

    queueContext.prevTrack();

    // Get the new current song after prevTrack
    const newSong = queueContext.getSongAtTrack(queueContext.queue.currentTrackIndex - 1);
    if (newSong && audioRef.current) {
      audioRef.current.src = newSong.streamUrl;
      audioRef.current.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true }));
    }
  }, [queueContext]);

  const toggleQueue = useCallback(() => {
    setState(prev => ({ ...prev, isQueueOpen: !prev.isQueueOpen }));
  }, []);

  const playFromQueue = useCallback((index: number) => {
    queueContext.setCurrentTrack(index);
    const song = queueContext.getSongAtTrack(index);

    if (song && audioRef.current) {
      audioRef.current.src = song.streamUrl;
      audioRef.current.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true }));
    }
  }, [queueContext]);

  // Play all songs from an album, starting at a specific track index
  const playAlbum = useCallback((album: Album, startIndex: number = 0) => {
    if (album.tracks.length === 0) return;

    // Load album into queue context
    queueContext.loadAlbum(album, startIndex);

    // Get the song to play
    const trackToPlay = album.tracks[startIndex];
    if (!trackToPlay || trackToPlay.songs.length === 0) return;

    // Get the best version (will be auto-selected by loadAlbum)
    // Wait a tick for state to update, then play
    setTimeout(() => {
      const song = queueContext.getSongAtTrack(startIndex);
      if (song && audioRef.current) {
        audioRef.current.src = song.streamUrl;
        audioRef.current.play().catch(console.error);
        setState(prev => ({ ...prev, isPlaying: true }));
      }
    }, 0);
  }, [queueContext]);

  // Play a specific track (adds to up next)
  const playTrack = useCallback((track: Track, songIndex: number = 0) => {
    if (track.songs.length === 0) return;

    const startSong = track.songs[songIndex] || track.songs[0];
    if (!startSong) return;

    // Add all song variants to up next
    track.songs.forEach(song => queueContext.addToUpNext(song));

    // Play the start song
    if (audioRef.current) {
      audioRef.current.src = startSong.streamUrl;
      audioRef.current.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true }));
    }
  }, [queueContext]);

  return (
    <PlayerContext.Provider
      value={{
        ...state,
        currentSong,
        playSong,
        togglePlay,
        pause,
        setVolume,
        seek,
        playNext,
        playPrev,
        toggleQueue,
        playFromQueue,
        playAlbum,
        playTrack,
        queue,
        queueIndex,
      }}
    >
      {children}
    </PlayerContext.Provider>
  );
}

export function usePlayer() {
  const context = useContext(PlayerContext);
  if (!context) {
    throw new Error('usePlayer must be used within a PlayerProvider');
  }
  return context;
}
