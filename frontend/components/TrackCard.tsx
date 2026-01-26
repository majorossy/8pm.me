'use client';

// TrackCard - displays a track with play button and version carousel (theme-aware)

import { useState } from 'react';
import { Track, Song, formatDuration } from '@/lib/api';
import { usePlayer } from '@/context/PlayerContext';
import { useCart } from '@/context/CartContext';
import { useTheme } from '@/context/ThemeContext';
import VersionCarousel from './VersionCarousel';

interface TrackCardProps {
  track: Track;
  index?: number;
}

export default function TrackCard({ track, index }: TrackCardProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const { currentSong, isPlaying, playSong, togglePlay } = usePlayer();
  const { addToCart, isInCart } = useCart();
  const [isExpanded, setIsExpanded] = useState(false);

  // Track which version is selected (default to first)
  const [selectedIndex, setSelectedIndex] = useState(0);

  // Selected song based on carousel selection
  const selectedSong = track.songs[selectedIndex] || track.songs[0];
  const isCurrentTrack = track.songs.some(s => s.id === currentSong?.id);
  const isSelectedPlaying = currentSong?.id === selectedSong?.id;
  const inQueue = selectedSong ? isInCart(selectedSong.id) : false;
  const hasMultipleVersions = track.songCount > 1;

  const handlePlayClick = () => {
    if (!selectedSong) return;

    if (isSelectedPlaying) {
      togglePlay();
    } else {
      playSong(selectedSong);
    }
  };

  const handleAddToQueue = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (selectedSong) {
      addToCart(selectedSong);
    }
  };

  const handleVersionSelect = (index: number) => {
    setSelectedIndex(index);
  };

  const handleVersionPlay = (song: Song) => {
    if (currentSong?.id === song.id && isPlaying) {
      togglePlay();
    } else {
      playSong(song);
    }
  };

  const toggleExpanded = () => {
    setIsExpanded(!isExpanded);
  };

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div
        className={`
          bg-white border overflow-hidden transition-all
          ${isCurrentTrack ? 'border-[#e85d04]' : 'border-[#d4d0c8]'}
          ${isExpanded ? 'border-[#e85d04]' : ''}
          hover:border-[#e85d04]
        `}
      >
        {/* Main track row */}
        <div
          className="grid grid-cols-[60px_50px_1fr_auto_auto] items-center px-6 py-4 cursor-pointer"
          onClick={toggleExpanded}
        >
          {/* Play button */}
          <button
            onClick={(e) => {
              e.stopPropagation();
              handlePlayClick();
            }}
            className={`
              w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
              ${isSelectedPlaying && isPlaying
                ? 'bg-[#e85d04] border-[#e85d04] text-white'
                : 'border-[#e85d04] text-[#e85d04] hover:bg-[#e85d04] hover:text-white'
              }
            `}
          >
            {isSelectedPlaying && isPlaying ? (
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" />
                <rect x="14" y="4" width="4" height="16" />
              </svg>
            ) : (
              <svg className="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            )}
          </button>

          {/* Track number */}
          <span className="font-display text-sm text-[#6b6b6b] font-semibold">
            {index !== undefined ? String(index).padStart(2, '0') : ''}
          </span>

          {/* Track info */}
          <div className="flex flex-col gap-1 min-w-0">
            <span className={`font-display text-base font-semibold ${isCurrentTrack ? 'text-[#e85d04]' : 'text-[#1a1a1a]'}`}>
              {track.title}
            </span>
            {hasMultipleVersions && (
              <span className="inline-flex items-center gap-2 text-xs text-[#e85d04]">
                <span className="w-1.5 h-1.5 bg-[#e85d04] rounded-full" />
                {track.songCount} versions
              </span>
            )}
          </div>

          {/* Duration */}
          <span className="text-sm text-[#6b6b6b] mr-6 hidden sm:block">
            {formatDuration(selectedSong?.duration || track.totalDuration)}
          </span>

          {/* Expand arrow */}
          <div
            className={`
              text-[#6b6b6b] transition-all
              ${isExpanded ? 'text-[#e85d04] rotate-180' : ''}
            `}
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>

        {/* Versions panel */}
        {isExpanded && (
          <div className="px-6 pb-6 bg-[#f8f6f1]">
            <VersionCarousel
              songs={track.songs}
              selectedIndex={selectedIndex}
              onSelect={handleVersionSelect}
              onPlay={handleVersionPlay}
              onAddToQueue={addToCart}
              currentSongId={currentSong?.id}
              isPlaying={isPlaying}
              isInQueue={isInCart}
            />
          </div>
        )}
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div
      className={`
        bg-dark-800 border border-white/5 overflow-hidden transition-all track-card-hover
        ${isCurrentTrack ? 'border-neon-cyan/30' : ''}
        ${isExpanded ? 'border-neon-cyan/20' : ''}
      `}
    >
      {/* Main track row */}
      <div
        className="grid grid-cols-[60px_50px_1fr_auto_auto] items-center px-6 py-4 cursor-pointer"
        onClick={toggleExpanded}
      >
        {/* Play button */}
        <button
          onClick={(e) => {
            e.stopPropagation();
            handlePlayClick();
          }}
          className={`
            w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
            ${isSelectedPlaying && isPlaying
              ? 'bg-neon-pink border-neon-pink text-dark-900 shadow-[0_0_20px_var(--neon-pink)]'
              : 'border-neon-pink text-neon-pink hover:bg-neon-pink hover:text-dark-900 hover:shadow-[0_0_20px_var(--neon-pink)]'
            }
          `}
        >
          {isSelectedPlaying && isPlaying ? (
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <rect x="6" y="4" width="4" height="16" />
              <rect x="14" y="4" width="4" height="16" />
            </svg>
          ) : (
            <svg className="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z" />
            </svg>
          )}
        </button>

        {/* Track number */}
        <span className="font-display text-sm text-text-dim">
          {index !== undefined ? String(index).padStart(2, '0') : ''}
        </span>

        {/* Track info */}
        <div className="flex flex-col gap-1 min-w-0">
          <span className={`font-display text-base ${isCurrentTrack ? 'text-neon-cyan' : 'text-white'}`}>
            {track.title}
          </span>
          {hasMultipleVersions && (
            <span className="inline-flex items-center gap-2 text-[0.6rem] text-neon-cyan uppercase tracking-[0.1em]">
              <span className="w-1.5 h-1.5 bg-neon-cyan rounded-full blink-dot" />
              {track.songCount} versions
            </span>
          )}
        </div>

        {/* Duration */}
        <span className="text-sm text-text-dim mr-6 hidden sm:block">
          {formatDuration(selectedSong?.duration || track.totalDuration)}
        </span>

        {/* Expand arrow */}
        <div
          className={`
            text-text-dim transition-all
            ${isExpanded ? 'text-neon-cyan rotate-180' : ''}
          `}
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      {/* Versions panel */}
      {isExpanded && (
        <div className="px-6 pb-6 bg-black/30">
          <VersionCarousel
            songs={track.songs}
            selectedIndex={selectedIndex}
            onSelect={handleVersionSelect}
            onPlay={handleVersionPlay}
            onAddToQueue={addToCart}
            currentSongId={currentSong?.id}
            isPlaying={isPlaying}
            isInQueue={isInCart}
          />
        </div>
      )}
    </div>
  );
}
