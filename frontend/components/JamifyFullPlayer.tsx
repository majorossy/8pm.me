'use client';

// JamifyFullPlayer - Spotify-style full-screen mobile player

import { useState, useEffect } from 'react';
import { usePlayer } from '@/context/PlayerContext';
import { useQueue } from '@/context/QueueContext';
import { useWishlist } from '@/context/WishlistContext';
import { useMobileUI } from '@/context/MobileUIContext';
import { useSwipeGesture } from '@/hooks/useSwipeGesture';
import { useBatteryOptimization } from '@/hooks/useBatteryOptimization';
import { formatDuration } from '@/lib/api';
import Link from 'next/link';

export default function JamifyFullPlayer() {
  const { isPlayerExpanded, collapsePlayer, isTransitioning } = useMobileUI();
  const { reducedMotion } = useBatteryOptimization();
  const {
    currentSong,
    isPlaying,
    volume,
    currentTime,
    duration,
    togglePlay,
    playNext,
    playPrev,
    setVolume,
    seek,
    toggleQueue,
    isQueueOpen,
  } = usePlayer();

  const {
    queue,
    isFirstTrack,
    isLastTrack,
    hasUpNext,
    setRepeat,
    setShuffle,
  } = useQueue();

  const { addToWishlist, removeFromWishlist, isInWishlist, wishlist } = useWishlist();

  // Animation state
  const [isAnimating, setIsAnimating] = useState(false);

  // Image loading state for lazy loading
  const [imageLoaded, setImageLoaded] = useState(false);

  // Swipe down gesture to collapse player
  const swipeHandlers = useSwipeGesture({
    onSwipeDown: () => {
      if (!isTransitioning) {
        collapsePlayer();
      }
    },
    threshold: 50,
    velocityThreshold: 0.5,
    direction: 'vertical',
  });

  // Trigger slide-up animation on mount - skip if reduced motion
  useEffect(() => {
    if (isPlayerExpanded && !reducedMotion) {
      setIsAnimating(true);
    }
  }, [isPlayerExpanded, reducedMotion]);

  if (!currentSong || !isPlayerExpanded) return null;

  const progress = duration > 0 ? (currentTime / duration) * 100 : 0;

  return (
    <div
      {...swipeHandlers}
      className={`fixed inset-0 z-50 bg-gradient-to-b from-[#535353] to-[#121212] flex flex-col md:hidden safe-top safe-bottom full-screen-player prevent-overscroll touch-action-pan-y ${
        isAnimating && !reducedMotion ? 'player-slide-up' : ''
      } ${swipeHandlers.isDragging ? 'dragging' : ''} ${reducedMotion ? 'reduce-motion' : ''}`}
      style={{
        transform: swipeHandlers.isDragging
          ? `translateY(${Math.max(0, swipeHandlers.dragOffset.y)}px)`
          : undefined,
        willChange: swipeHandlers.isDragging ? 'transform' : 'auto',
      }}
    >
      {/* Drag hint pill */}
      <div className="drag-hint" />

      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3">
        {/* Collapse button */}
        <button
          onClick={collapsePlayer}
          className="p-2 -ml-2 text-white btn-touch"
          aria-label="Minimize player"
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        {/* Now playing from */}
        <div className="text-center">
          <p className="text-[10px] text-[#b3b3b3] uppercase tracking-wider">Playing from</p>
          <p className="text-xs text-white font-medium truncate max-w-[200px]">
            {queue.album?.name || 'Unknown'}
          </p>
        </div>

        {/* More options */}
        <button className="p-2 -mr-2 text-white btn-touch" aria-label="More options">
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
          </svg>
        </button>
      </div>

      {/* Album Art */}
      <div className="flex-1 flex items-center justify-center px-8 py-4">
        <div className="w-full max-w-[320px] aspect-square rounded-lg overflow-hidden shadow-2xl">
          {queue.album?.coverArt ? (
            <img
              src={queue.album.coverArt}
              alt={queue.album.name}
              loading="lazy"
              onLoad={() => setImageLoaded(true)}
              className={`w-full h-full object-cover transition-opacity duration-300 ${
                imageLoaded ? 'opacity-100' : 'opacity-0'
              }`}
            />
          ) : (
            <div className="w-full h-full bg-[#282828] flex items-center justify-center">
              <svg className="w-24 h-24 text-[#535353]" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
              </svg>
            </div>
          )}
        </div>
      </div>

      {/* Song Info */}
      <div className="px-8 mb-4">
        <div className="flex items-start justify-between">
          <div className="flex-1 min-w-0 mr-4">
            <h2 className="text-xl font-bold text-white truncate">
              {currentSong.title}
            </h2>
            <Link
              href={`/artists/${currentSong.artistSlug || ''}`}
              onClick={collapsePlayer}
              className="text-[#b3b3b3] hover:text-white hover:underline truncate block"
            >
              {currentSong.artistName}
            </Link>
          </div>
          {/* Like button */}
          <button
            onClick={() => {
              if (currentSong) {
                if (isInWishlist(currentSong.id)) {
                  const item = wishlist.items.find(i => i.song.id === currentSong.id);
                  if (item) removeFromWishlist(item.id);
                } else {
                  addToWishlist(currentSong);
                }
              }
            }}
            className={`p-2 btn-touch btn-ripple ${
              currentSong && isInWishlist(currentSong.id) ? 'text-[#1DB954]' : 'text-[#b3b3b3]'
            }`}
            aria-label={currentSong && isInWishlist(currentSong.id) ? 'Remove from favorites' : 'Add to favorites'}
          >
            {currentSong && isInWishlist(currentSong.id) ? (
              <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
              </svg>
            ) : (
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            )}
          </button>
        </div>
      </div>

      {/* Progress Bar */}
      <div className="px-8 mb-4">
        <div
          className="w-full h-1 bg-[#535353] rounded-full cursor-pointer group"
          onClick={(e) => {
            const rect = e.currentTarget.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            seek(percent * duration);
          }}
        >
          <div
            className="h-full bg-white rounded-full relative"
            style={{ width: `${progress}%` }}
          >
            <div className="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full opacity-0 group-active:opacity-100" />
          </div>
        </div>
        <div className="flex justify-between mt-1">
          <span className="text-[11px] text-[#b3b3b3] font-mono">
            {formatDuration(Math.floor(currentTime))}
          </span>
          <span className="text-[11px] text-[#b3b3b3] font-mono">
            {formatDuration(Math.floor(duration))}
          </span>
        </div>
      </div>

      {/* Main Controls */}
      <div className="px-8 mb-6">
        <div className="flex items-center justify-between">
          {/* Shuffle */}
          <button
            onClick={() => setShuffle(!queue.shuffle)}
            className={`p-3 btn-touch ${queue.shuffle ? 'text-[#1DB954]' : 'text-[#b3b3b3]'}`}
            aria-label={queue.shuffle ? 'Disable shuffle' : 'Enable shuffle'}
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M10.59 9.17L5.41 4 4 5.41l5.17 5.17 1.42-1.41zM14.5 4l2.04 2.04L4 18.59 5.41 20 17.96 7.46 20 9.5V4h-5.5zm.33 9.41l-1.41 1.41 3.13 3.13L14.5 20H20v-5.5l-2.04 2.04-3.13-3.13z" />
            </svg>
          </button>

          {/* Previous */}
          <button
            onClick={playPrev}
            disabled={isFirstTrack && !hasUpNext}
            className="p-3 text-white disabled:opacity-30 btn-touch"
            aria-label="Previous track"
          >
            <svg className="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
              <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z" />
            </svg>
          </button>

          {/* Play/Pause */}
          <button
            onClick={togglePlay}
            className="w-16 h-16 rounded-full bg-white flex items-center justify-center text-black btn-touch btn-ripple"
            aria-label={isPlaying ? 'Pause' : 'Play'}
          >
            {isPlaying ? (
              <svg className="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" />
                <rect x="14" y="4" width="4" height="16" />
              </svg>
            ) : (
              <svg className="w-8 h-8 ml-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            )}
          </button>

          {/* Next */}
          <button
            onClick={playNext}
            disabled={isLastTrack && !hasUpNext}
            className="p-3 text-white disabled:opacity-30 btn-touch"
            aria-label="Next track"
          >
            <svg className="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
              <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z" />
            </svg>
          </button>

          {/* Repeat */}
          <button
            onClick={() => {
              const modes: Array<'off' | 'all' | 'one'> = ['off', 'all', 'one'];
              const currentIndex = modes.indexOf(queue.repeat);
              const nextIndex = (currentIndex + 1) % modes.length;
              setRepeat(modes[nextIndex]);
            }}
            className={`p-3 btn-touch ${queue.repeat === 'off' ? 'text-[#b3b3b3]' : 'text-[#1DB954]'}`}
            aria-label={`Repeat: ${queue.repeat}`}
          >
            {queue.repeat === 'one' ? (
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4zm-4-2V9h-1l-2 1v1h1.5v4H13z" />
              </svg>
            ) : (
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z" />
              </svg>
            )}
          </button>
        </div>
      </div>

      {/* Bottom Actions */}
      <div className="px-8 pb-6">
        <div className="flex items-center justify-between">
          {/* Device */}
          <button className="p-2 text-[#b3b3b3] btn-touch" aria-label="Devices">
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z" />
            </svg>
          </button>

          {/* Queue */}
          <button
            onClick={() => {
              collapsePlayer();
              setTimeout(() => toggleQueue(), 100);
            }}
            className={`p-2 btn-touch ${isQueueOpen ? 'text-[#1DB954]' : 'text-[#b3b3b3]'}`}
            aria-label="Queue"
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
}
