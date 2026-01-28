'use client';

// PlayerContext = Audio playback only
// Queue management is handled by QueueContext

import React, { createContext, useContext, useState, useRef, useEffect, useCallback } from 'react';
import { Song, Album, Track } from '@/lib/types';
import { useQueue } from './QueueContext';
import { useRecentlyPlayed } from './RecentlyPlayedContext';
import { useMediaSession } from '@/hooks/useMediaSession';
import { useCrossfade } from '@/hooks/useCrossfade';
import { useToast } from '@/hooks/useToast';
import { useAudioAnalyzer, AudioAnalyzerData } from '@/hooks/useAudioAnalyzer';

interface PlayerState {
  isPlaying: boolean;
  volume: number;
  currentTime: number;
  duration: number;
  isQueueOpen: boolean;  // UI state for queue drawer
  crossfadeDuration: number;
  announcement: string; // ARIA live region announcement
  activeSong: Song | null; // Track the actually playing song (for when upNext plays)
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

  // Play a specific version of a track within an album (for album page recording selection)
  playAlbumFromTrack: (album: Album, trackIndex: number, song: Song) => void;

  // Crossfade controls
  setCrossfadeDuration: (duration: number) => void;

  // Audio analyzer data for visualizations
  analyzerData: AudioAnalyzerData;

  // Legacy queue access for compatibility (maps from QueueContext)
  queue: Song[];
  queueIndex: number;
}

const PlayerContext = createContext<PlayerContextType | null>(null);

export function PlayerProvider({ children }: { children: React.ReactNode }) {
  const queueContext = useQueue();
  const { trackPlay } = useRecentlyPlayed();
  const trackedSongsRef = useRef<Set<string>>(new Set()); // Track which songs we've already counted

  // Toast notifications for playback errors
  // Note: useToast may throw if not in ToastProvider - we handle this gracefully
  let toast: ReturnType<typeof useToast> | null = null;
  try {
    toast = useToast();
  } catch {
    // ToastProvider not available yet - this is fine during initial render
  }

  // Build legacy queue from QueueContext for compatibility
  const queue: Song[] = queueContext.queue.tracks.map(track => {
    const version = track.availableVersions.find(v => v.id === track.selectedVersionId);
    return version!;
  }).filter(Boolean);

  const queueIndex = queueContext.queue.currentTrackIndex;

  // Load crossfade duration from localStorage
  const [crossfadeDuration, setCrossfadeDurationState] = useState<number>(() => {
    if (typeof window !== 'undefined') {
      const saved = localStorage.getItem('crossfadeDuration');
      return saved ? parseInt(saved, 10) : 3;
    }
    return 3;
  });

  const [state, setState] = useState<PlayerState>({
    isPlaying: false,
    volume: 0.7,
    currentTime: 0,
    duration: 0,
    isQueueOpen: false,
    crossfadeDuration,
    announcement: '',
    activeSong: null,
  });

  // Get current song from QueueContext, fallback to activeSong for up-next/single plays
  // Must be defined after state is initialized
  const currentSong = queueContext.currentSong || state.activeSong;

  // Crossfade hook setup
  const crossfade = useCrossfade({
    crossfadeDuration,
    onTrackEnd: () => {
      // Handle track end via queue context
      const nextSong = queueContext.nextTrack();
      if (!nextSong) {
        setState(prev => ({ ...prev, isPlaying: false }));
      }
    },
    getCurrentTime: () => state.currentTime,
    getDuration: () => state.duration,
  });

  // Helper to get current audio element
  const getAudio = () => crossfade.getCurrentAudio();

  // Audio analyzer for visualizations
  const { analyzerData, connectAudioElement, setVolume: setAnalyzerVolume } = useAudioAnalyzer();

  // Debug: Log analyzer data when it changes
  useEffect(() => {
    console.log('[PlayerContext] analyzerData:', {
      volume: analyzerData.volume,
      waveformLength: analyzerData.waveform.length,
      waveformSample: analyzerData.waveform.slice(0, 5)
    });
  }, [analyzerData]);

  // Connect analyzer to active audio element when it changes or playback starts
  useEffect(() => {
    const audio = getAudio();
    if (audio && state.isPlaying) {
      console.log('[PlayerContext] Connecting analyzer to audio element');
      connectAudioElement(audio);
      // Set initial volume on the GainNode
      setAnalyzerVolume(state.volume);
    }
  }, [crossfade.state.activeElement, state.isPlaying, connectAudioElement, setAnalyzerVolume, state.volume]);

  // Helper to handle playback errors and skip to next track
  const handlePlaybackError = useCallback((failedSong: Song | null, error: Error | MediaError | Event) => {
    const songTitle = failedSong?.title || 'Unknown track';
    const errorMessage = error instanceof Error
      ? error.message
      : (error as MediaError)?.message || 'Stream unavailable';

    console.error(`[PlayerContext] Playback error for "${songTitle}":`, errorMessage);

    // Show toast notification
    if (toast) {
      const nextSong = queueContext.peekNextTrack();
      if (nextSong) {
        toast.showError(`Couldn't play "${songTitle}", skipped to next`);
      } else {
        toast.showError(`Couldn't play "${songTitle}"`);
      }
    }

    // Try to skip to next track
    const nextSong = queueContext.nextTrack();
    const audio = getAudio();

    if (nextSong && audio) {
      console.log(`[PlayerContext] Skipping to next track: "${nextSong.title}"`);
      audio.src = nextSong.streamUrl;
      audio.play().catch((err) => {
        // If next track also fails, stop playback
        console.error('[PlayerContext] Next track also failed:', err);
        setState(prev => ({ ...prev, isPlaying: false, activeSong: null }));
      });
      setState(prev => ({ ...prev, activeSong: nextSong }));
    } else {
      // No more tracks to play
      setState(prev => ({ ...prev, isPlaying: false }));
    }
  }, [queueContext, toast]);

  // Initialize audio element event handlers
  useEffect(() => {
    const audio = getAudio();
    if (!audio) return;

    audio.volume = state.volume;

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
        const currentAudio = getAudio();
        if (currentAudio) {
          currentAudio.src = nextSong.streamUrl;
          currentAudio.play().catch(console.error);
          setState(prev => ({ ...prev, activeSong: nextSong }));
        }
      } else {
        // Nothing more to play
        setState(prev => ({ ...prev, isPlaying: false }));
      }
    };

    // Handle audio loading/streaming errors
    const handleError = (event: Event) => {
      const audioElement = event.target as HTMLAudioElement;
      const error = audioElement.error;

      // Get the current song that failed
      const failedSong = currentSong;

      // Log detailed error info
      if (error) {
        console.error('[PlayerContext] Audio error:', {
          code: error.code,
          message: error.message,
          // MediaError codes: 1=ABORTED, 2=NETWORK, 3=DECODE, 4=SRC_NOT_SUPPORTED
          type: ['', 'MEDIA_ERR_ABORTED', 'MEDIA_ERR_NETWORK', 'MEDIA_ERR_DECODE', 'MEDIA_ERR_SRC_NOT_SUPPORTED'][error.code] || 'UNKNOWN',
        });
      }

      handlePlaybackError(failedSong, error || new Error('Unknown audio error'));
    };

    // Handle stalled/stuck streams
    const handleStalled = () => {
      console.warn('[PlayerContext] Audio stream stalled');
      // Don't immediately fail - the browser may recover
    };

    // Handle waiting (buffering) - log for debugging
    const handleWaiting = () => {
      console.log('[PlayerContext] Audio buffering...');
    };

    audio.addEventListener('timeupdate', handleTimeUpdate);
    audio.addEventListener('loadedmetadata', handleLoadedMetadata);
    audio.addEventListener('ended', handleEnded);
    audio.addEventListener('error', handleError);
    audio.addEventListener('stalled', handleStalled);
    audio.addEventListener('waiting', handleWaiting);

    return () => {
      audio.removeEventListener('timeupdate', handleTimeUpdate);
      audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
      audio.removeEventListener('ended', handleEnded);
      audio.removeEventListener('error', handleError);
      audio.removeEventListener('stalled', handleStalled);
      audio.removeEventListener('waiting', handleWaiting);
    };
  }, [crossfade.state.activeElement, currentSong, handlePlaybackError]);

  // When currentSong changes (from QueueContext), update audio
  useEffect(() => {
    const audio = getAudio();
    if (!audio || !currentSong) return;

    // Check if we need to load a new source
    const currentSrc = audio.src;
    const newSrc = currentSong.streamUrl;

    // Only reload if the source URL changed
    if (!currentSrc.endsWith(new URL(newSrc, window.location.origin).pathname)) {
      audio.src = newSrc;
      if (state.isPlaying) {
        audio.play().catch(console.error);
      }
    }
  }, [currentSong, crossfade.state.activeElement]);

  // Preload next track when 30 seconds remaining (or immediately if song is short)
  const preloadedSongIdRef = useRef<string | null>(null);

  useEffect(() => {
    // Clear preload if user skips to a different song
    if (currentSong && preloadedSongIdRef.current && preloadedSongIdRef.current !== currentSong.id) {
      crossfade.clearPreload();
      preloadedSongIdRef.current = null;
    }

    // Don't preload if not playing
    if (!currentSong || !state.isPlaying) return;

    const timeRemaining = state.duration - state.currentTime;

    // Preload when 30s remaining OR if song is very short (< 30s)
    const shouldPreload = (timeRemaining <= 30 && timeRemaining > 0) || (state.duration > 0 && state.duration <= 30 && state.currentTime < 1);

    if (shouldPreload) {
      const nextSong = queueContext.peekNextTrack();

      // Only preload if:
      // 1. There is a next track
      // 2. We haven't already preloaded it
      // 3. Next track is different from current (handles repeat one)
      if (nextSong && nextSong.id !== preloadedSongIdRef.current && nextSong.id !== currentSong.id) {
        console.log('[PlayerContext] 🎵 Preloading next track:', nextSong.title);
        // Set ref BEFORE calling preload to prevent duplicate calls
        const songToPreload = nextSong.id;
        preloadedSongIdRef.current = songToPreload;
        crossfade.preloadNextTrack(nextSong.streamUrl);
      }
    }
  }, [state.currentTime, state.duration, state.isPlaying, currentSong, queueContext, crossfade]);

  // Clear preload when queue structure changes (shuffle, repeat, queue cleared)
  useEffect(() => {
    return () => {
      if (preloadedSongIdRef.current) {
        crossfade.clearPreload();
        preloadedSongIdRef.current = null;
      }
    };
  }, [queueContext.queue.shuffle, queueContext.queue.repeat, queueContext.queue.tracks.length, crossfade]);

  // Track song as "played" after 30 seconds of playback
  useEffect(() => {
    if (!currentSong || !state.isPlaying) return;

    // Check if we've already tracked this song
    if (trackedSongsRef.current.has(currentSong.id)) return;

    // Check if playback has reached 30 seconds
    if (state.currentTime >= 30) {
      console.log('[PlayerContext] 🎵 Tracking song:', currentSong.title, 'at', Math.floor(state.currentTime), 'seconds');
      trackPlay(currentSong);
      trackedSongsRef.current.add(currentSong.id);
    }
  }, [currentSong, state.isPlaying, state.currentTime, trackPlay]);

  // Clear tracked songs when song changes
  useEffect(() => {
    if (currentSong) {
      // Reset tracking for new song (allow same song to be tracked again if replayed later)
      trackedSongsRef.current.delete(currentSong.id);
      // Announce song change for screen readers
      setState(prev => ({
        ...prev,
        announcement: `Now playing ${currentSong.title} by ${currentSong.artistName}`
      }));
    }
  }, [currentSong]);

  // Announce playback state changes for screen readers
  useEffect(() => {
    if (currentSong) {
      setState(prev => ({
        ...prev,
        announcement: state.isPlaying ? 'Playing' : 'Paused'
      }));
    }
  }, [state.isPlaying, currentSong]);

  const playSong = useCallback((song: Song) => {
    const audio = getAudio();
    if (!audio) return;

    // Connect to audio analyzer BEFORE playing
    connectAudioElement(audio);
    setAnalyzerVolume(state.volume);

    // Add to up-next (this song might not be part of current album)
    queueContext.addToUpNext(song);

    setState(prev => ({
      ...prev,
      isPlaying: true,
      activeSong: song, // Track what's actually playing
    }));

    audio.src = song.streamUrl;
    audio.play().catch(err => {
      console.error('Audio play error:', err);
      handlePlaybackError(song, err);
    });
  }, [queueContext, crossfade, handlePlaybackError, connectAudioElement, setAnalyzerVolume, state.volume]);

  const pause = useCallback(() => {
    const audio = getAudio();
    if (!audio) return;
    audio.pause();
    setState(prev => ({ ...prev, isPlaying: false }));
  }, [crossfade]);

  const togglePlay = useCallback(() => {
    const audio = getAudio();
    if (!audio || !currentSong) return;

    if (state.isPlaying) {
      audio.pause();
    } else {
      // Ensure audio is connected before playing
      connectAudioElement(audio);
      setAnalyzerVolume(state.volume);
      audio.play().catch(console.error);
    }
    setState(prev => ({ ...prev, isPlaying: !prev.isPlaying }));
  }, [state.isPlaying, currentSong, crossfade, connectAudioElement, setAnalyzerVolume, state.volume]);

  const setVolume = useCallback((volume: number) => {
    // When using Web Audio API, set volume via GainNode instead of audio.volume
    setAnalyzerVolume(volume);
    setState(prev => ({ ...prev, volume }));
  }, [setAnalyzerVolume]);

  const seek = useCallback((time: number) => {
    const audio = getAudio();
    if (!audio) return;
    audio.currentTime = time;
    setState(prev => ({ ...prev, currentTime: time }));
  }, [crossfade]);

  const playNext = useCallback(() => {
    const nextSong = queueContext.nextTrack();
    const audio = getAudio();
    if (nextSong && audio) {
      // Connect audio analyzer before playing
      connectAudioElement(audio);
      setAnalyzerVolume(state.volume);
      audio.src = nextSong.streamUrl;
      audio.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true, activeSong: nextSong }));
    }
  }, [queueContext, crossfade, connectAudioElement, setAnalyzerVolume, state.volume]);

  const playPrev = useCallback(() => {
    const audio = getAudio();

    // If more than 3 seconds into song, restart it
    if (audio && audio.currentTime > 3) {
      audio.currentTime = 0;
      return;
    }

    queueContext.prevTrack();

    // Get the new current song after prevTrack
    const newSong = queueContext.getSongAtTrack(queueContext.queue.currentTrackIndex - 1);
    if (newSong && audio) {
      audio.src = newSong.streamUrl;
      audio.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true, activeSong: newSong }));
    }
  }, [queueContext, crossfade]);

  const toggleQueue = useCallback(() => {
    setState(prev => ({ ...prev, isQueueOpen: !prev.isQueueOpen }));
  }, []);

  const setCrossfadeDuration = useCallback((duration: number) => {
    setCrossfadeDurationState(duration);
    setState(prev => ({ ...prev, crossfadeDuration: duration }));
    if (typeof window !== 'undefined') {
      localStorage.setItem('crossfadeDuration', duration.toString());
    }
  }, []);

  const playFromQueue = useCallback((index: number) => {
    queueContext.setCurrentTrack(index);
    const song = queueContext.getSongAtTrack(index);
    const audio = getAudio();

    if (song && audio) {
      // Connect audio analyzer before playing
      connectAudioElement(audio);
      setAnalyzerVolume(state.volume);
      audio.src = song.streamUrl;
      audio.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true, activeSong: song }));
    }
  }, [queueContext, crossfade, connectAudioElement, setAnalyzerVolume, state.volume]);

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
      const audio = getAudio();
      if (song && audio) {
        // Connect audio analyzer before playing
        connectAudioElement(audio);
        setAnalyzerVolume(state.volume);
        audio.src = song.streamUrl;
        audio.play().catch(console.error);
        setState(prev => ({ ...prev, isPlaying: true, activeSong: song }));
      }
    }, 0);
  }, [queueContext, crossfade, connectAudioElement, setAnalyzerVolume, state.volume]);

  // Play a specific track (adds to up next)
  const playTrack = useCallback((track: Track, songIndex: number = 0) => {
    if (track.songs.length === 0) return;

    const startSong = track.songs[songIndex] || track.songs[0];
    if (!startSong) return;

    // Add all song variants to up next
    track.songs.forEach(song => queueContext.addToUpNext(song));

    // Play the start song
    const audio = getAudio();
    if (audio) {
      audio.src = startSong.streamUrl;
      audio.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true, activeSong: startSong }));
    }
  }, [queueContext, crossfade]);

  // Play a specific version of a track within an album
  // This ensures track advancement works correctly (plays next track, not next version)
  const playAlbumFromTrack = useCallback((album: Album, trackIndex: number, song: Song) => {
    // Load album if not already loaded or if it's a different album
    if (queueContext.queue.album?.identifier !== album.identifier) {
      queueContext.loadAlbum(album, trackIndex);
    } else {
      // Album already loaded, just set the track index
      queueContext.setCurrentTrack(trackIndex);
    }

    // Select the specific version for this track
    queueContext.selectVersion(trackIndex, song.id);

    // Play the song
    const audio = getAudio();
    if (audio) {
      // Connect audio analyzer before playing
      connectAudioElement(audio);
      setAnalyzerVolume(state.volume);
      audio.src = song.streamUrl;
      audio.play().catch(console.error);
      setState(prev => ({ ...prev, isPlaying: true, activeSong: song }));
    }
  }, [queueContext, crossfade, connectAudioElement, setAnalyzerVolume, state.volume]);

  // Media Session API integration for lock screen controls
  useMediaSession({
    currentSong,
    isPlaying: state.isPlaying,
    currentTime: state.currentTime,
    duration: state.duration,
    onPlay: togglePlay,
    onPause: pause,
    onNext: playNext,
    onPrevious: playPrev,
    onSeek: seek,
  });

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
        playAlbumFromTrack,
        setCrossfadeDuration,
        analyzerData,
        queue,
        queueIndex,
      }}
    >
      {/* ARIA live region for screen reader announcements */}
      <div
        role="status"
        aria-live="polite"
        aria-atomic="true"
        className="sr-only"
      >
        {state.announcement}
      </div>
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
