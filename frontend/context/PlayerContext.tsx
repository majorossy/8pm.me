'use client';

// PlayerContext = Audio playback only
// Queue management moved to CartContext (Magento cart)

import React, { createContext, useContext, useState, useRef, useEffect, useCallback } from 'react';
import { Song, Album, Track } from '@/lib/types';
import { useCart } from './CartContext';

interface PlayerState {
  currentSong: Song | null;
  isPlaying: boolean;
  volume: number;
  currentTime: number;
  duration: number;
  isQueueOpen: boolean;  // UI state for queue drawer
}

interface PlayerContextType extends PlayerState {
  // Playback controls
  playSong: (song: Song) => void;
  togglePlay: () => void;
  setVolume: (volume: number) => void;
  seek: (time: number) => void;
  playNext: () => void;
  playPrev: () => void;
  // Queue UI
  toggleQueue: () => void;
  // Play from queue (cart)
  playFromQueue: (index: number) => void;
  // Album/track playback
  playAlbum: (album: Album, startIndex?: number) => void;
  playTrack: (track: Track, songIndex?: number) => void;
  // Queue comes from cart
  queue: Song[];
  queueIndex: number;
}

const PlayerContext = createContext<PlayerContextType | null>(null);

export function PlayerProvider({ children }: { children: React.ReactNode }) {
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const { cart, addToCart } = useCart();

  // Queue is the cart items mapped to songs
  const queue = cart.items.map(item => item.song);

  const [state, setState] = useState<PlayerState>({
    currentSong: null,
    isPlaying: false,
    volume: 0.7,
    currentTime: 0,
    duration: 0,
    isQueueOpen: false,
  });

  // Current index in queue
  const queueIndex = state.currentSong
    ? queue.findIndex(s => s.id === state.currentSong?.id)
    : -1;

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
      // Auto-play next in queue
      setState(prev => {
        const currentIdx = queue.findIndex(s => s.id === prev.currentSong?.id);
        if (currentIdx >= 0 && currentIdx < queue.length - 1) {
          const nextSong = queue[currentIdx + 1];
          if (audioRef.current) {
            audioRef.current.src = nextSong.streamUrl;
            audioRef.current.play().catch(console.error);
          }
          return { ...prev, currentSong: nextSong };
        }
        return { ...prev, isPlaying: false };
      });
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

  // Update ended handler when queue changes
  useEffect(() => {
    if (!audioRef.current) return;

    const audio = audioRef.current;
    const handleEnded = () => {
      const currentIdx = queue.findIndex(s => s.id === state.currentSong?.id);
      if (currentIdx >= 0 && currentIdx < queue.length - 1) {
        const nextSong = queue[currentIdx + 1];
        audio.src = nextSong.streamUrl;
        audio.play().catch(console.error);
        setState(prev => ({ ...prev, currentSong: nextSong }));
      } else {
        setState(prev => ({ ...prev, isPlaying: false }));
      }
    };

    audio.addEventListener('ended', handleEnded);
    return () => audio.removeEventListener('ended', handleEnded);
  }, [queue, state.currentSong]);

  const playSong = useCallback((song: Song) => {
    if (!audioRef.current) return;

    // Add to cart (queue) if not already there
    addToCart(song);

    setState(prev => ({
      ...prev,
      currentSong: song,
      isPlaying: true,
    }));

    audioRef.current.src = song.streamUrl;
    audioRef.current.play().catch(err => {
      console.error('Audio play error:', err);
      setState(prev => ({ ...prev, isPlaying: false }));
    });
  }, [addToCart]);

  const togglePlay = useCallback(() => {
    if (!audioRef.current || !state.currentSong) return;

    if (state.isPlaying) {
      audioRef.current.pause();
    } else {
      audioRef.current.play().catch(console.error);
    }
    setState(prev => ({ ...prev, isPlaying: !prev.isPlaying }));
  }, [state.isPlaying, state.currentSong]);

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
    if (queueIndex < 0 || queueIndex >= queue.length - 1) return;
    const nextSong = queue[queueIndex + 1];
    playSong(nextSong);
  }, [queueIndex, queue, playSong]);

  const playPrev = useCallback(() => {
    if (queueIndex <= 0) return;
    const prevSong = queue[queueIndex - 1];
    playSong(prevSong);
  }, [queueIndex, queue, playSong]);

  const toggleQueue = useCallback(() => {
    setState(prev => ({ ...prev, isQueueOpen: !prev.isQueueOpen }));
  }, []);

  const playFromQueue = useCallback((index: number) => {
    if (queue[index]) {
      playSong(queue[index]);
    }
  }, [queue, playSong]);

  // Play all songs from an album, starting at a specific track index
  const playAlbum = useCallback((album: Album, startIndex: number = 0) => {
    // Flatten all songs from all tracks
    const allSongs = album.tracks.flatMap(t => t.songs);
    if (allSongs.length === 0) return;

    // Add all songs to queue
    allSongs.forEach(song => addToCart(song));

    // Start playing from the specified index (or first song)
    const startSong = allSongs[startIndex] || allSongs[0];
    if (startSong) {
      playSong(startSong);
    }
  }, [addToCart, playSong]);

  // Play all recordings of a specific track
  const playTrack = useCallback((track: Track, songIndex: number = 0) => {
    if (track.songs.length === 0) return;

    // Add all song variants to queue
    track.songs.forEach(song => addToCart(song));

    // Start playing from the specified index
    const startSong = track.songs[songIndex] || track.songs[0];
    if (startSong) {
      playSong(startSong);
    }
  }, [addToCart, playSong]);

  return (
    <PlayerContext.Provider
      value={{
        ...state,
        playSong,
        togglePlay,
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
