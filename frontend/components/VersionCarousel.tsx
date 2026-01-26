'use client';

// VersionCarousel - horizontal scrolling carousel of song version cards (theme-aware)

import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { Song, formatDuration } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';

interface VersionCarouselProps {
  songs: Song[];
  selectedIndex: number;
  onSelect: (index: number) => void;
  onPlay: (song: Song) => void;
  onAddToQueue?: (song: Song) => void;
  currentSongId?: string;
  isPlaying: boolean;
  isInQueue?: (songId: string) => boolean;
}

interface VersionCardProps {
  song: Song;
  isSelected: boolean;
  isPlaying: boolean;
  isInQueue: boolean;
  isMetro: boolean;
  onSelect: () => void;
  onPlay: () => void;
  onAddToQueue: () => void;
}

type SortOrder = 'newest' | 'oldest';

// Star rating display component
function StarRating({ rating, count, isMetro }: { rating?: number; count?: number; isMetro?: boolean }) {
  if (!rating || !count) return null;

  const roundedRating = Math.round(rating * 2) / 2;
  const fullStars = Math.floor(roundedRating);
  const hasHalfStar = roundedRating % 1 !== 0;
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

  const starColor = isMetro ? 'text-[#e85d04]' : 'text-neon-orange';
  const emptyColor = isMetro ? 'text-[#d4d0c8]' : 'text-dark-500';
  const countColor = isMetro ? 'text-[#6b6b6b]' : 'text-text-dim';

  return (
    <div className="flex items-center gap-1" title={`${rating.toFixed(1)} out of 5 stars (${count} reviews)`}>
      <div className={`flex ${starColor}`}>
        {Array.from({ length: fullStars }).map((_, i) => (
          <svg key={`full-${i}`} className="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        ))}
        {hasHalfStar && (
          <svg className="w-3 h-3" viewBox="0 0 24 24">
            <defs>
              <linearGradient id="halfStar">
                <stop offset="50%" stopColor="currentColor" />
                <stop offset="50%" stopColor={isMetro ? '#d4d0c8' : '#374151'} />
              </linearGradient>
            </defs>
            <path fill="url(#halfStar)" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        )}
        {Array.from({ length: emptyStars }).map((_, i) => (
          <svg key={`empty-${i}`} className={`w-3 h-3 ${emptyColor}`} fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        ))}
      </div>
      <span className={`${countColor} text-[10px]`}>({count})</span>
    </div>
  );
}

function VersionCard({
  song,
  isSelected,
  isPlaying,
  isInQueue,
  isMetro,
  onSelect,
  onPlay,
  onAddToQueue,
}: VersionCardProps) {
  const venue = song.showVenue || song.albumName || 'Unknown Venue';
  const date = song.showDate;
  const year = date ? date.split('-')[0] : null;

  // Format date for display (YYYY-MM-DD -> MM/DD/YY)
  let formattedDate = date;
  if (date && date.match(/^\d{4}-\d{2}-\d{2}$/)) {
    const [y, month, day] = date.split('-');
    formattedDate = `${month}/${day}/${y.slice(-2)}`;
  }

  const truncate = (text: string | undefined, maxLen: number) => {
    if (!text) return null;
    return text.length > maxLen ? text.substring(0, maxLen) + '...' : text;
  };

  const handlePlayClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    onPlay();
  };

  const handleQueueClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    onAddToQueue();
  };

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div
        onClick={onSelect}
        tabIndex={0}
        role="button"
        aria-selected={isSelected}
        className={`
          flex-shrink-0 w-[280px] p-6 cursor-pointer transition-all duration-200 snap-start
          bg-white border relative
          ${isSelected
            ? 'border-[#e85d04] shadow-lg'
            : 'border-[#d4d0c8] hover:border-[#e85d04] hover:shadow-md'
          }
        `}
      >
        {/* Header: Year + Selected badge */}
        <div className="flex justify-between items-start mb-4">
          <span className="font-display text-4xl font-bold text-[#1a1a1a]">
            {year || '—'}
          </span>
          {isSelected && (
            <span className="font-display text-[0.5rem] px-2 py-1 bg-[#e85d04] text-white uppercase tracking-[0.1em]">
              Selected
            </span>
          )}
        </div>

        {/* Meta info */}
        <div className="text-xs text-[#6b6b6b] space-y-1">
          {/* Venue */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Venue</span>
            <span className="text-[#1a1a1a]" title={song.showVenue || undefined}>{truncate(venue, 20) || '—'}</span>
          </div>
          {/* Location */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Location</span>
            <span title={song.showLocation || undefined}>{truncate(song.showLocation, 20) || '—'}</span>
          </div>
          {/* Date */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Date</span>
            <span>{formattedDate || '—'}</span>
          </div>
          {/* Taper */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Taper</span>
            <span title={song.taper || undefined}>{truncate(song.taper, 18) || '—'}</span>
          </div>
          {/* Rating */}
          <div className="flex justify-between items-center py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Rating</span>
            {song.avgRating ? <StarRating rating={song.avgRating} count={song.numReviews} isMetro /> : <span>—</span>}
          </div>
          {/* Reviews */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Reviews</span>
            <span>{song.numReviews ?? '—'}</span>
          </div>
          {/* Source */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Source</span>
            <span title={song.source || undefined}>{truncate(song.source, 18) || '—'}</span>
          </div>
          {/* Length */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Length</span>
            <span>{formatDuration(song.duration)}</span>
          </div>
          {/* Lineage */}
          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
            <span className="text-[#e85d04] font-medium">Lineage</span>
            <span className="font-mono text-[10px]" title={song.lineage || undefined}>{truncate(song.lineage, 18) || '—'}</span>
          </div>
          {/* Notes */}
          <div className="flex justify-between py-1">
            <span className="text-[#e85d04] font-medium">Notes</span>
            <span title={song.notes || undefined}>{truncate(song.notes, 18) || '—'}</span>
          </div>
        </div>

        {/* Action buttons */}
        <div className="grid grid-cols-2 gap-2 mt-6">
          <button
            onClick={handlePlayClick}
            className={`
              py-3 font-display text-xs font-semibold transition-all
              ${isPlaying
                ? 'bg-[#e85d04] text-white'
                : 'bg-[#e85d04] text-white hover:bg-[#d44d00]'
              }
            `}
          >
            {isPlaying ? '❚❚ Playing' : '▶ Play'}
          </button>
          <button
            onClick={handleQueueClick}
            disabled={isInQueue}
            className={`
              py-3 font-display text-xs font-semibold transition-all border
              ${isInQueue
                ? 'border-[#e85d04]/50 text-[#e85d04]/50 cursor-default'
                : 'border-[#d4d0c8] text-[#6b6b6b] hover:border-[#e85d04] hover:text-[#e85d04]'
              }
            `}
          >
            {isInQueue ? '✓ Queued' : '+ Queue'}
          </button>
        </div>
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div
      onClick={onSelect}
      tabIndex={0}
      role="button"
      aria-selected={isSelected}
      className={`
        flex-shrink-0 w-[280px] p-6 cursor-pointer transition-all duration-300 snap-start
        bg-dark-900 border relative
        ${isSelected
          ? 'border-neon-pink shadow-[0_0_30px_rgba(255,45,149,0.2)]'
          : 'border-white/10 hover:-translate-y-1 hover:border-neon-cyan/50 hover:shadow-[0_0_30px_rgba(0,240,255,0.15)]'
        }
        ${isPlaying ? 'animate-pulse-subtle' : ''}
      `}
    >
      {/* Header: Year + Selected badge */}
      <div className="flex justify-between items-start mb-4">
        <span className="font-display text-4xl font-bold gradient-text">
          {year || '—'}
        </span>
        {isSelected && (
          <span className="font-display text-[0.5rem] px-2 py-1 bg-neon-pink text-dark-900 uppercase tracking-[0.1em]">
            Selected
          </span>
        )}
      </div>

      {/* Meta info - ordered: Year, Venue, Location, Date, Taper, Rating, Reviews, Source, Length, Lineage, Notes */}
      <div className="text-[0.6rem] text-text-dim space-y-1">
        {/* Venue */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Venue</span>
          <span className="text-white" title={song.showVenue || undefined}>{truncate(venue, 20) || '—'}</span>
        </div>
        {/* Location */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Location</span>
          <span title={song.showLocation || undefined}>{truncate(song.showLocation, 20) || '—'}</span>
        </div>
        {/* Date */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Date</span>
          <span>{formattedDate || '—'}</span>
        </div>
        {/* Taper */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Taper</span>
          <span title={song.taper || undefined}>{truncate(song.taper, 18) || '—'}</span>
        </div>
        {/* Rating */}
        <div className="flex justify-between items-center py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Rating</span>
          {song.avgRating ? <StarRating rating={song.avgRating} count={song.numReviews} /> : <span>—</span>}
        </div>
        {/* Reviews */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Reviews</span>
          <span>{song.numReviews ?? '—'}</span>
        </div>
        {/* Source */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Source</span>
          <span title={song.source || undefined}>{truncate(song.source, 18) || '—'}</span>
        </div>
        {/* Length */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Length</span>
          <span>{formatDuration(song.duration)}</span>
        </div>
        {/* Lineage */}
        <div className="flex justify-between py-1 border-b border-dotted border-white/10">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Lineage</span>
          <span className="font-mono text-[10px]" title={song.lineage || undefined}>{truncate(song.lineage, 18) || '—'}</span>
        </div>
        {/* Notes */}
        <div className="flex justify-between py-1">
          <span className="text-neon-orange uppercase tracking-[0.1em]">Notes</span>
          <span title={song.notes || undefined}>{truncate(song.notes, 18) || '—'}</span>
        </div>
      </div>

      {/* Action buttons */}
      <div className="grid grid-cols-2 gap-2 mt-6">
        <button
          onClick={handlePlayClick}
          className={`
            py-3 font-display text-[0.55rem] uppercase tracking-[0.1em] transition-all
            ${isPlaying
              ? 'bg-neon-cyan text-dark-900 shadow-[0_0_20px_var(--neon-cyan)]'
              : 'bg-neon-cyan text-dark-900 hover:shadow-[0_0_20px_var(--neon-cyan)]'
            }
          `}
        >
          {isPlaying ? '❚❚ Playing' : '▶ Play'}
        </button>
        <button
          onClick={handleQueueClick}
          disabled={isInQueue}
          className={`
            py-3 font-display text-[0.55rem] uppercase tracking-[0.1em] transition-all border
            ${isInQueue
              ? 'border-neon-pink/50 text-neon-pink/50 cursor-default'
              : 'border-text-dim text-text-dim hover:border-neon-pink hover:text-neon-pink'
            }
          `}
        >
          {isInQueue ? '✓ Queued' : '+ Queue'}
        </button>
      </div>
    </div>
  );
}

export default function VersionCarousel({
  songs,
  selectedIndex,
  onSelect,
  onPlay,
  onAddToQueue,
  currentSongId,
  isPlaying,
  isInQueue,
}: VersionCarouselProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  const [sortOrder, setSortOrder] = useState<SortOrder>('newest');

  // Sort songs by date
  const sortedSongs = useMemo(() => {
    return [...songs].sort((a, b) => {
      const dateA = a.showDate || '0000-00-00';
      const dateB = b.showDate || '0000-00-00';
      return sortOrder === 'newest'
        ? dateB.localeCompare(dateA)
        : dateA.localeCompare(dateB);
    });
  }, [songs, sortOrder]);

  // Find the sorted index of the currently selected song
  const selectedSongId = songs[selectedIndex]?.id;
  const sortedSelectedIndex = sortedSongs.findIndex(s => s.id === selectedSongId);

  // Check scroll position to update arrow visibility
  const updateScrollState = useCallback(() => {
    if (!scrollRef.current) return;
    const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
    setCanScrollLeft(scrollLeft > 5);
    setCanScrollRight(scrollLeft < scrollWidth - clientWidth - 5);
  }, []);

  // Initialize scroll state and add listeners
  useEffect(() => {
    const container = scrollRef.current;
    if (!container) return;

    updateScrollState();
    container.addEventListener('scroll', updateScrollState);
    window.addEventListener('resize', updateScrollState);

    return () => {
      container.removeEventListener('scroll', updateScrollState);
      window.removeEventListener('resize', updateScrollState);
    };
  }, [updateScrollState, sortedSongs]);

  // Auto-scroll to selected card when selection changes
  useEffect(() => {
    if (sortedSelectedIndex < 0 || !scrollRef.current) return;
    const container = scrollRef.current;
    const card = container.children[sortedSelectedIndex] as HTMLElement;
    if (card) {
      card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
  }, [sortedSelectedIndex]);

  // Scroll by one card width
  const scrollByCard = (direction: 'left' | 'right') => {
    if (!scrollRef.current) return;
    const cardWidth = 300;
    const scrollAmount = direction === 'left' ? -cardWidth : cardWidth;
    scrollRef.current.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  };

  // Handle card selection - need to map back to original index
  const handleSelect = (sortedIdx: number) => {
    const song = sortedSongs[sortedIdx];
    const originalIndex = songs.findIndex(s => s.id === song.id);
    onSelect(originalIndex);
  };

  const handleAddToQueue = (song: Song) => {
    if (onAddToQueue) {
      onAddToQueue(song);
    }
  };

  return (
    <div className="mt-4">
      {/* Controls bar */}
      {sortedSongs.length > 1 && (
        <div className={`flex items-center justify-between py-4 mb-4 border-b ${isMetro ? 'border-[#d4d0c8]' : 'border-white/5'}`}>
          <span className={`${isMetro ? 'text-xs text-[#6b6b6b]' : 'text-[0.6rem] text-text-dim uppercase tracking-[0.15em]'}`}>
            Available Recordings
          </span>
          <div className={`flex items-center gap-2 ${isMetro ? 'text-xs text-[#6b6b6b]' : 'text-[0.6rem] text-text-dim'}`}>
            <span>Sort:</span>
            <select
              value={sortOrder}
              onChange={(e) => setSortOrder(e.target.value as SortOrder)}
              className={isMetro ? 'metro-select' : 'neon-select'}
            >
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
            </select>
          </div>
        </div>
      )}

      {/* Carousel container */}
      <div className="relative group">
        {/* Left fade + arrow */}
        {canScrollLeft && (
          <>
            <div className={`absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r ${isMetro ? 'from-[#f8f6f1]' : 'from-dark-900'} to-transparent pointer-events-none z-[5]`} />
            <button
              onClick={() => scrollByCard('left')}
              className={`absolute left-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 flex items-center justify-center rounded-full opacity-0 group-hover:opacity-100 transition-all ${
                isMetro
                  ? 'bg-white border border-[#d4d0c8] hover:border-[#e85d04] hover:text-[#e85d04]'
                  : 'bg-dark-800/90 border border-dark-500 hover:border-neon-cyan hover:text-neon-cyan'
              }`}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
          </>
        )}

        {/* Scrollable container */}
        <div
          ref={scrollRef}
          className="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin"
        >
          {sortedSongs.map((song, idx) => {
            const originalIndex = songs.findIndex(s => s.id === song.id);
            return (
              <VersionCard
                key={song.id}
                song={song}
                isSelected={originalIndex === selectedIndex}
                isPlaying={currentSongId === song.id && isPlaying}
                isInQueue={isInQueue ? isInQueue(song.id) : false}
                isMetro={isMetro}
                onSelect={() => handleSelect(idx)}
                onPlay={() => onPlay(song)}
                onAddToQueue={() => handleAddToQueue(song)}
              />
            );
          })}
        </div>

        {/* Right fade + arrow */}
        {canScrollRight && (
          <>
            <div className={`absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l ${isMetro ? 'from-[#f8f6f1]' : 'from-dark-900'} to-transparent pointer-events-none z-[5]`} />
            <button
              onClick={() => scrollByCard('right')}
              className={`absolute right-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 flex items-center justify-center rounded-full opacity-0 group-hover:opacity-100 transition-all ${
                isMetro
                  ? 'bg-white border border-[#d4d0c8] hover:border-[#e85d04] hover:text-[#e85d04]'
                  : 'bg-dark-800/90 border border-dark-500 hover:border-neon-cyan hover:text-neon-cyan'
              }`}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </>
        )}
      </div>
    </div>
  );
}
