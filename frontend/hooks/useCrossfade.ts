import { useState, useEffect, useCallback, useRef } from 'react';

export interface UseCrossfadeOptions {
  crossfadeDuration: number;
  onTrackEnd: () => void;
  getCurrentTime: () => number;
  getDuration: () => number;
}

export interface CrossfadeState {
  activeElement: 'A' | 'B';
  isCrossfading: boolean;
  crossfadeProgress: number;
}

export interface UseCrossfadeReturn {
  audioRefA: React.MutableRefObject<HTMLAudioElement | null>;
  audioRefB: React.MutableRefObject<HTMLAudioElement | null>;
  activeAudioRef: React.MutableRefObject<HTMLAudioElement | null>;
  inactiveAudioRef: React.MutableRefObject<HTMLAudioElement | null>;
  state: CrossfadeState;
  preloadNextTrack: (src: string) => void;
  swapActiveElement: () => void;
  stopCrossfade: () => void;
  clearPreload: () => void;
  getCurrentAudio: () => HTMLAudioElement | null;
}

/**
 * useCrossfade - Manages dual audio elements for smooth crossfading between tracks
 *
 * Features:
 * - Dual audio element management (A/B switching)
 * - Automatic preloading of next track
 * - Volume-based crossfade (fade out active, fade in next)
 * - Handles edge cases: user skips, no next track, crossfade disabled
 */
export function useCrossfade({
  crossfadeDuration,
  onTrackEnd,
  getCurrentTime,
  getDuration,
}: UseCrossfadeOptions): UseCrossfadeReturn {
  const audioRefA = useRef<HTMLAudioElement | null>(null);
  const audioRefB = useRef<HTMLAudioElement | null>(null);
  const activeAudioRef = useRef<HTMLAudioElement | null>(null);
  const inactiveAudioRef = useRef<HTMLAudioElement | null>(null);

  const [state, setState] = useState<CrossfadeState>({
    activeElement: 'A',
    isCrossfading: false,
    crossfadeProgress: 0,
  });

  const crossfadeIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const preloadedSrcRef = useRef<string | null>(null);
  const originalVolumeRef = useRef<number>(0.7);

  useEffect(() => {
    audioRefA.current = new Audio();
    audioRefB.current = new Audio();

    // CRITICAL: Set crossOrigin for Web Audio API compatibility with archive.org
    audioRefA.current.crossOrigin = 'anonymous';
    audioRefB.current.crossOrigin = 'anonymous';

    activeAudioRef.current = audioRefA.current;
    inactiveAudioRef.current = audioRefB.current;

    return () => {
      if (crossfadeIntervalRef.current) {
        clearInterval(crossfadeIntervalRef.current);
      }
      audioRefA.current?.pause();
      audioRefB.current?.pause();
    };
  }, []);

  useEffect(() => {
    if (state.activeElement === 'A') {
      activeAudioRef.current = audioRefA.current;
      inactiveAudioRef.current = audioRefB.current;
    } else {
      activeAudioRef.current = audioRefB.current;
      inactiveAudioRef.current = audioRefA.current;
    }
  }, [state.activeElement]);

  useEffect(() => {
    if (!activeAudioRef.current) return;

    const checkCrossfadeTime = () => {
      const currentTime = getCurrentTime();
      const duration = getDuration();

      if (!duration || duration <= 0 || crossfadeDuration === 0) return;

      const timeRemaining = duration - currentTime;

      if (timeRemaining <= crossfadeDuration && timeRemaining > 0 && !state.isCrossfading) {
        if (preloadedSrcRef.current && inactiveAudioRef.current?.src) {
          startCrossfade();
        }
      }
    };

    const interval = setInterval(checkCrossfadeTime, 100);

    return () => clearInterval(interval);
  }, [crossfadeDuration, state.isCrossfading, getCurrentTime, getDuration]);

  const startCrossfade = useCallback(() => {
    if (!activeAudioRef.current || !inactiveAudioRef.current) return;

    setState(prev => ({ ...prev, isCrossfading: true, crossfadeProgress: 0 }));

    inactiveAudioRef.current.currentTime = 0;
    inactiveAudioRef.current.volume = 0;
    inactiveAudioRef.current.play().catch(console.error);

    originalVolumeRef.current = activeAudioRef.current.volume;

    let startTime = Date.now();

    crossfadeIntervalRef.current = setInterval(() => {
      const elapsed = (Date.now() - startTime) / 1000;
      const progress = Math.min(elapsed / crossfadeDuration, 1);

      if (!activeAudioRef.current || !inactiveAudioRef.current) return;

      activeAudioRef.current.volume = Math.max(0, originalVolumeRef.current * (1 - progress));
      inactiveAudioRef.current.volume = Math.min(originalVolumeRef.current, originalVolumeRef.current * progress);

      setState(prev => ({ ...prev, crossfadeProgress: progress }));

      if (progress >= 1) {
        if (crossfadeIntervalRef.current) {
          clearInterval(crossfadeIntervalRef.current);
          crossfadeIntervalRef.current = null;
        }

        if (activeAudioRef.current) {
          activeAudioRef.current.pause();
          activeAudioRef.current.currentTime = 0;
        }

        swapActiveElement();

        preloadedSrcRef.current = null;

        setState(prev => ({
          ...prev,
          isCrossfading: false,
          crossfadeProgress: 0,
        }));
      }
    }, 50);
  }, [crossfadeDuration]);

  const preloadNextTrack = useCallback((src: string) => {
    if (!inactiveAudioRef.current) return;

    // Allow preloading even when crossfade is disabled for instant playback
    if (preloadedSrcRef.current === src) return;

    inactiveAudioRef.current.src = src;
    inactiveAudioRef.current.preload = 'auto';
    inactiveAudioRef.current.load();
    preloadedSrcRef.current = src;
  }, []);

  const swapActiveElement = useCallback(() => {
    setState(prev => ({
      ...prev,
      activeElement: prev.activeElement === 'A' ? 'B' : 'A',
    }));
  }, []);

  const stopCrossfade = useCallback(() => {
    if (crossfadeIntervalRef.current) {
      clearInterval(crossfadeIntervalRef.current);
      crossfadeIntervalRef.current = null;
    }

    if (activeAudioRef.current) {
      activeAudioRef.current.volume = originalVolumeRef.current;
    }
    if (inactiveAudioRef.current) {
      inactiveAudioRef.current.pause();
      inactiveAudioRef.current.volume = 0;
    }

    setState(prev => ({
      ...prev,
      isCrossfading: false,
      crossfadeProgress: 0,
    }));

    preloadedSrcRef.current = null;
  }, []);

  const clearPreload = useCallback(() => {
    if (inactiveAudioRef.current && preloadedSrcRef.current) {
      inactiveAudioRef.current.src = '';
      preloadedSrcRef.current = null;
    }
  }, []);

  const getCurrentAudio = useCallback(() => {
    return activeAudioRef.current;
  }, [state.activeElement]);

  return {
    audioRefA,
    audioRefB,
    activeAudioRef,
    inactiveAudioRef,
    state,
    preloadNextTrack,
    swapActiveElement,
    stopCrossfade,
    clearPreload,
    getCurrentAudio,
  };
}
