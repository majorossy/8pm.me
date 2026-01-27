'use client';

// TrackCard - displays a track with play button and version carousel (theme-aware)

import { useState } from 'react';
import { Track, Song, Album, formatDuration } from '@/lib/api';
import { usePlayer } from '@/context/PlayerContext';
import { useQueue } from '@/context/QueueContext';
import { useTheme } from '@/context/ThemeContext';
import VersionCarousel from './VersionCarousel';

interface TrackCardProps {
  track: Track;
  index?: number;
  album?: Album;
}

export default function TrackCard({ track, index, album }: TrackCardProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';
  const { currentSong, isPlaying, playSong, togglePlay, playAlbum } = usePlayer();
  const { queue, addToUpNext } = useQueue();
  const [isExpanded, setIsExpanded] = useState(false);

  // Track which version is selected (default to first)
  const [selectedIndex, setSelectedIndex] = useState(0);

  // Selected song based on carousel selection
  const selectedSong = track.songs[selectedIndex] || track.songs[0];
  const isCurrentTrack = track.songs.some(s => s.id === currentSong?.id);
  const isSelectedPlaying = currentSong?.id === selectedSong?.id;
  const hasMultipleVersions = track.songCount > 1;

  const handlePlayClick = () => {
    if (!selectedSong) return;

    // If this track is from an album and we have album context, load the album
    if (album && index !== undefined) {
      // Load the album starting at this track
      playAlbum(album, index - 1); // index is 1-based, playAlbum expects 0-based
    } else if (isSelectedPlaying) {
      togglePlay();
    } else {
      playSong(selectedSong);
    }
  };

  const handleAddToQueue = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (selectedSong) {
      addToUpNext(selectedSong);
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

  // Jamify/Spotify style - row-based with hover background
  if (isJamify) {
    return (
      <div
        className={`
          rounded-lg overflow-hidden transition-all
          ${isCurrentTrack
            ? 'bg-[#282828] border-l-2 border-[#1DB954]'
            : 'hover:bg-[#282828] border-l-2 border-transparent'
          }
          ${isExpanded ? 'bg-[#282828]' : ''}
        `}
      >
        {/* Main track row */}
        <div
          className="grid grid-cols-[24px_1fr_auto_auto] gap-4 items-center px-4 py-2 cursor-pointer group"
          onClick={toggleExpanded}
        >
          {/* Track number or play icon / EQ bars */}
          <div className="w-6 flex items-center justify-center">
            {isSelectedPlaying && isPlaying ? (
              /* Animated EQ bars for playing track */
              <span className="jamify-eq-bars">
                <span /><span /><span />
              </span>
            ) : (
              <>
                <span className={`text-sm group-hover:hidden ${isCurrentTrack ? 'text-[#1DB954]' : 'text-[#a7a7a7]'}`}>
                  {index !== undefined ? index : ''}
                </span>
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    handlePlayClick();
                  }}
                  className="hidden group-hover:block text-white focus:outline-none"
                  aria-label={isSelectedPlaying && isPlaying ? 'Pause' : 'Play'}
                >
                  {isSelectedPlaying && isPlaying ? (
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <rect x="6" y="4" width="4" height="16" />
                      <rect x="14" y="4" width="4" height="16" />
                    </svg>
                  ) : (
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z" />
                    </svg>
                  )}
                </button>
              </>
            )}
          </div>

          {/* Track info */}
          <div className="flex flex-col min-w-0">
            <span className={`text-base truncate ${isCurrentTrack ? 'text-[#1DB954]' : 'text-white'}`}>
              {track.title}
            </span>
            {hasMultipleVersions && (
              <span className="text-xs text-[#a7a7a7] flex items-center gap-1">
                <span className="w-1.5 h-1.5 bg-[#1DB954] rounded-full" />
                {track.songCount} versions available
              </span>
            )}
          </div>

          {/* Add to queue */}
          <button
            onClick={handleAddToQueue}
            className="text-[#a7a7a7] hover:text-white opacity-0 group-hover:opacity-100 transition-all focus:outline-none focus:opacity-100"
            title="Add to queue"
            aria-label="Add to queue"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
          </button>

          {/* Duration and expand chevron */}
          <div className="flex items-center gap-4">
            <span className="text-sm text-[#a7a7a7]">
              {formatDuration(selectedSong?.duration || track.totalDuration)}
            </span>
            {/* Always show expand chevron to indicate expandability */}
            <div className={`text-[#a7a7a7] transition-transform ${isExpanded ? 'rotate-180 text-white' : ''} ${hasMultipleVersions ? '' : 'opacity-0'}`}>
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>
        </div>

        {/* Versions panel */}
        {isExpanded && (
          <div className="px-4 pb-4 bg-[#181818]">
            {track.songs.length > 0 ? (
              <VersionCarousel
                songs={track.songs}
                selectedIndex={selectedIndex}
                onSelect={handleVersionSelect}
                onPlay={handleVersionPlay}
                onAddToQueue={addToUpNext}
                currentSongId={currentSong?.id}
                isPlaying={isPlaying}
                isInQueue={() => false}
              />
            ) : (
              /* Empty State - No Live Recordings for this track */
              <div className="flex flex-col items-center justify-center py-8 px-4">
                <svg className="w-16 h-16 text-[#535353] mb-4" viewBox="0 0 64 64" fill="none">
                  {/* Microphone with X */}
                  <path
                    d="M32 8C26.477 8 22 12.477 22 18V28C22 33.523 26.477 38 32 38C37.523 38 42 33.523 42 28V18C42 12.477 37.523 8 32 8Z"
                    fill="currentColor"
                    opacity="0.6"
                  />
                  <rect x="30" y="38" width="4" height="12" fill="currentColor" opacity="0.4" />
                  <rect x="24" y="50" width="16" height="3" rx="1.5" fill="currentColor" opacity="0.4" />
                  {/* X overlay */}
                  <path d="M20 16L44 40M44 16L20 40" stroke="#535353" strokeWidth="3" strokeLinecap="round" />
                </svg>
                <p className="text-sm text-[#a7a7a7] text-center">
                  No live recordings found for this track
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    );
  }

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

          {/* Duration & Add to Queue */}
          <div className="flex items-center gap-3 mr-6">
            <button
              onClick={handleAddToQueue}
              className="text-xs text-[#6b6b6b] hover:text-[#e85d04] transition-colors hidden sm:block"
              title="Add to Up Next"
            >
              + Queue
            </button>
            <span className="text-sm text-[#6b6b6b] hidden sm:block">
              {formatDuration(selectedSong?.duration || track.totalDuration)}
            </span>
          </div>

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
            {track.songs.length > 0 ? (
              <VersionCarousel
                songs={track.songs}
                selectedIndex={selectedIndex}
                onSelect={handleVersionSelect}
                onPlay={handleVersionPlay}
                onAddToQueue={addToUpNext}
                currentSongId={currentSong?.id}
                isPlaying={isPlaying}
                isInQueue={() => false} // Simplified - Up Next can have duplicates
              />
            ) : (
              /* Empty State - No Live Recordings for this track (Metro) */
              <div className="flex flex-col items-center justify-center py-8 px-4">
                <svg className="w-16 h-16 text-[#d4d0c8] mb-4" viewBox="0 0 64 64" fill="none">
                  {/* Microphone with X */}
                  <path
                    d="M32 8C26.477 8 22 12.477 22 18V28C22 33.523 26.477 38 32 38C37.523 38 42 33.523 42 28V18C42 12.477 37.523 8 32 8Z"
                    fill="currentColor"
                  />
                  <rect x="30" y="38" width="4" height="12" fill="currentColor" />
                  <rect x="24" y="50" width="16" height="3" rx="1.5" fill="currentColor" />
                  {/* X overlay */}
                  <path d="M20 16L44 40M44 16L20 40" stroke="#6b6b6b" strokeWidth="3" strokeLinecap="round" />
                </svg>
                <p className="text-sm text-[#6b6b6b] text-center">
                  No live recordings found for this track
                </p>
              </div>
            )}
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

        {/* Duration & Add to Queue */}
        <div className="flex items-center gap-3 mr-6">
          <button
            onClick={handleAddToQueue}
            className="text-[10px] text-text-dim hover:text-neon-pink transition-colors uppercase tracking-wider hidden sm:block"
            title="Add to Up Next"
          >
            + Queue
          </button>
          <span className="text-sm text-text-dim hidden sm:block">
            {formatDuration(selectedSong?.duration || track.totalDuration)}
          </span>
        </div>

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
          {track.songs.length > 0 ? (
            <VersionCarousel
              songs={track.songs}
              selectedIndex={selectedIndex}
              onSelect={handleVersionSelect}
              onPlay={handleVersionPlay}
              onAddToQueue={addToUpNext}
              currentSongId={currentSong?.id}
              isPlaying={isPlaying}
              isInQueue={() => false} // Simplified - Up Next can have duplicates
            />
          ) : (
            /* Empty State - No Live Recordings for this track (Tron/Synthwave) */
            <div className="flex flex-col items-center justify-center py-8 px-4">
              <svg className="w-16 h-16 mb-4" viewBox="0 0 64 64" fill="none">
                <defs>
                  <filter id="neon-glow-small" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="2" result="blur" />
                    <feMerge>
                      <feMergeNode in="blur" />
                      <feMergeNode in="SourceGraphic" />
                    </feMerge>
                  </filter>
                </defs>
                {/* Microphone with X */}
                <path
                  d="M32 8C26.477 8 22 12.477 22 18V28C22 33.523 26.477 38 32 38C37.523 38 42 33.523 42 28V18C42 12.477 37.523 8 32 8Z"
                  className="fill-neon-pink/40"
                  filter="url(#neon-glow-small)"
                />
                <rect x="30" y="38" width="4" height="12" className="fill-neon-cyan/30" />
                <rect x="24" y="50" width="16" height="3" rx="1.5" className="fill-neon-cyan/30" />
                {/* X overlay */}
                <path d="M20 16L44 40M44 16L20 40" className="stroke-neon-pink" strokeWidth="2" strokeLinecap="round" filter="url(#neon-glow-small)" />
              </svg>
              <p className="text-sm text-text-dim text-center">
                No live recordings found for this track
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
