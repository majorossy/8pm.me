'use client';

// VersionCarousel - horizontal scrolling carousel of song version cards
// Features: navigation arrows, fade indicators, expand/collapse, queue, sort, dots, auto-scroll

import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { Song, formatDuration } from '@/lib/api';

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
  isExpanded: boolean;
  isInQueue: boolean;
  onSelect: () => void;
  onPlay: () => void;
  onAddToQueue: () => void;
}

type SortOrder = 'newest' | 'oldest';

// Star rating display component
function StarRating({ rating, count }: { rating?: number; count?: number }) {
  if (!rating || !count) return null;

  // Round rating to nearest half
  const roundedRating = Math.round(rating * 2) / 2;
  const fullStars = Math.floor(roundedRating);
  const hasHalfStar = roundedRating % 1 !== 0;
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

  return (
    <div className="flex items-center gap-1" title={`${rating.toFixed(1)} out of 5 stars (${count} reviews)`}>
      <div className="flex text-yellow-500">
        {/* Full stars */}
        {Array.from({ length: fullStars }).map((_, i) => (
          <svg key={`full-${i}`} className="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        ))}
        {/* Half star */}
        {hasHalfStar && (
          <svg className="w-3 h-3" viewBox="0 0 24 24">
            <defs>
              <linearGradient id="halfStar">
                <stop offset="50%" stopColor="currentColor" />
                <stop offset="50%" stopColor="#374151" />
              </linearGradient>
            </defs>
            <path fill="url(#halfStar)" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        )}
        {/* Empty stars */}
        {Array.from({ length: emptyStars }).map((_, i) => (
          <svg key={`empty-${i}`} className="w-3 h-3 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        ))}
      </div>
      <span className="text-gray-400 text-[10px]">({count})</span>
    </div>
  );
}

function VersionCard({
  song,
  isSelected,
  isPlaying,
  isExpanded,
  isInQueue,
  onSelect,
  onPlay,
  onAddToQueue,
}: VersionCardProps) {
  // Use parsed data from API (showDate, showVenue, source are now parsed)
  const venue = song.showVenue || song.albumName || 'Unknown Venue';
  const date = song.showDate;

  // Format date for display (YYYY-MM-DD -> MM/DD/YY)
  let formattedDate = date;
  if (date && date.match(/^\d{4}-\d{2}-\d{2}$/)) {
    const [year, month, day] = date.split('-');
    formattedDate = `${month}/${day}/${year.slice(-2)}`;
  }

  // Truncate text helpers
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

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onPlay();
    }
  };

  return (
    <div
      onClick={onSelect}
      onKeyDown={handleKeyDown}
      tabIndex={0}
      role="button"
      aria-selected={isSelected}
      aria-label={`Version from ${formattedDate || 'unknown date'} at ${venue}`}
      className={`
        relative flex-shrink-0 p-3 rounded-lg cursor-pointer
        transition-all duration-200 snap-start outline-none
        focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-dark-800
        ${isExpanded ? 'w-64' : 'w-48'}
        ${isSelected
          ? 'bg-dark-600 border-2 border-primary shadow-lg shadow-primary/20'
          : 'bg-dark-700 border-2 border-transparent hover:bg-dark-600 hover:border-dark-500'
        }
        ${isPlaying ? 'ring-2 ring-primary ring-opacity-50 animate-pulse-subtle' : ''}
      `}
    >
      {/* Selected indicator */}
      {isSelected && (
        <div className="absolute top-2 right-2">
          <svg className="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
          </svg>
        </div>
      )}

      {/* Card content */}
      {isExpanded ? (
        // Expanded view - order: Year, Venue, Location, Taper, Source, Length, Date, Rating, Reviews, Lineage, Notes
        <div className="space-y-1.5 text-[11px]">
          {/* Year */}
          <div>
            <span className="text-gray-500 uppercase tracking-wide">Year: </span>
            {date ? (
              <span className="text-primary font-semibold">{date.split('-')[0]}</span>
            ) : (
              <span className="text-gray-500 italic">Unknown</span>
            )}
          </div>

          {/* Venue */}
          <div>
            <span className="text-gray-500 uppercase tracking-wide">Venue: </span>
            <span className="text-white font-medium" title={venue}>{truncate(venue, 25)}</span>
          </div>

          {/* Location */}
          {song.showLocation && (
            <div>
              <span className="text-gray-500 uppercase tracking-wide">Location: </span>
              <span className="text-gray-300" title={song.showLocation}>{truncate(song.showLocation, 25)}</span>
            </div>
          )}

          {/* Taper */}
          {song.taper && (
            <div>
              <span className="text-gray-500 uppercase tracking-wide">Taper: </span>
              <span className="text-gray-300" title={song.taper}>{truncate(song.taper, 25)}</span>
            </div>
          )}

          {/* Source */}
          {song.source && (
            <div>
              <span className="text-gray-500 uppercase tracking-wide">Source: </span>
              <span className="text-gray-300 text-[10px]" title={song.source}>{truncate(song.source, 30)}</span>
            </div>
          )}

          {/* Length */}
          <div>
            <span className="text-gray-500 uppercase tracking-wide">Length: </span>
            <span className="text-gray-400 font-mono">{formatDuration(song.duration)}</span>
          </div>

          {/* Date */}
          <div>
            <span className="text-gray-500 uppercase tracking-wide">Date: </span>
            {formattedDate ? (
              <span className="text-gray-300">{formattedDate}</span>
            ) : (
              <span className="text-gray-500 italic">Unknown</span>
            )}
          </div>

          {/* Rating */}
          {song.avgRating ? (
            <div className="flex items-center gap-1.5">
              <span className="text-gray-500 uppercase tracking-wide">Rating: </span>
              <StarRating rating={song.avgRating} count={song.numReviews} />
            </div>
          ) : null}

          {/* Reviews */}
          {song.numReviews ? (
            <div>
              <span className="text-gray-500 uppercase tracking-wide">Reviews: </span>
              <span className="text-gray-300">{song.numReviews}</span>
            </div>
          ) : null}

          {/* Lineage */}
          {song.lineage && (
            <div>
              <span className="text-gray-500 uppercase tracking-wide">Lineage: </span>
              <span className="text-gray-400 font-mono text-[10px]" title={song.lineage}>
                {truncate(song.lineage, 35)}
              </span>
            </div>
          )}

          {/* Notes */}
          {song.notes && (
            <div className="pt-1 border-t border-dark-500">
              <span className="text-gray-500 uppercase tracking-wide">Notes: </span>
              <span className="text-gray-300" title={song.notes}>
                {truncate(song.notes, 50)}
              </span>
            </div>
          )}
        </div>
      ) : (
        // Compact view - date, venue, duration, rating
        <div className="space-y-1 text-[11px]">
          <div className="flex items-center justify-between gap-2">
            {formattedDate ? (
              <span className="text-primary font-semibold whitespace-nowrap">{formattedDate}</span>
            ) : (
              <span className="text-gray-500 italic">Unknown</span>
            )}
            <span className="text-gray-400 font-mono whitespace-nowrap">
              {formatDuration(song.duration)}
            </span>
          </div>
          <div className="text-white text-xs truncate" title={venue}>
            {truncate(venue, 22)}
          </div>
          {/* Rating in compact view */}
          {song.avgRating && song.numReviews ? (
            <StarRating rating={song.avgRating} count={song.numReviews} />
          ) : null}
        </div>
      )}

      {/* Action buttons */}
      <div className="flex gap-2 mt-3">
        {/* Play button */}
        <button
          onClick={handlePlayClick}
          className={`
            flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded
            transition-colors text-xs font-medium
            ${isPlaying
              ? 'bg-primary text-white'
              : 'bg-dark-500 text-gray-300 hover:bg-primary hover:text-white'
            }
          `}
        >
          {isPlaying ? (
            <>
              <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" />
                <rect x="14" y="4" width="4" height="16" />
              </svg>
              Playing
            </>
          ) : (
            <>
              <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
              Play
            </>
          )}
        </button>

        {/* Queue button */}
        <button
          onClick={handleQueueClick}
          disabled={isInQueue}
          className={`
            flex items-center justify-center gap-1 px-3 py-1.5 rounded
            transition-colors text-xs font-medium
            ${isInQueue
              ? 'bg-dark-500 text-primary cursor-default'
              : 'bg-dark-500 text-gray-300 hover:bg-dark-400 hover:text-white'
            }
          `}
          title={isInQueue ? 'In queue' : 'Add to queue'}
        >
          {isInQueue ? (
            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
          ) : (
            <>
              <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              <span className="hidden sm:inline">Queue</span>
            </>
          )}
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
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);
  const [sortOrder, setSortOrder] = useState<SortOrder>('newest');
  const [isExpanded, setIsExpanded] = useState(true); // Default to expanded

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
    const cardWidth = 240; // Approximate card width + gap
    const scrollAmount = direction === 'left' ? -cardWidth : cardWidth;
    scrollRef.current.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  };

  // Handle card selection - need to map back to original index
  const handleSelect = (sortedIdx: number) => {
    const song = sortedSongs[sortedIdx];
    const originalIndex = songs.findIndex(s => s.id === song.id);
    onSelect(originalIndex);
  };

  // Handle keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'ArrowLeft' && sortedSelectedIndex > 0) {
      e.preventDefault();
      handleSelect(sortedSelectedIndex - 1);
    } else if (e.key === 'ArrowRight' && sortedSelectedIndex < sortedSongs.length - 1) {
      e.preventDefault();
      handleSelect(sortedSelectedIndex + 1);
    }
  };

  const handleAddToQueue = (song: Song) => {
    if (onAddToQueue) {
      onAddToQueue(song);
    }
  };

  return (
    <div className="mt-2 -mx-2" onKeyDown={handleKeyDown}>
      {/* Controls bar */}
      {sortedSongs.length > 1 && (
        <div className="flex items-center justify-between px-2 mb-2">
          <div className="flex items-center gap-3">
            {/* Sort dropdown */}
            <div className="flex items-center gap-2">
              <label htmlFor="sort-order" className="text-xs text-gray-500">Sort:</label>
              <select
                id="sort-order"
                value={sortOrder}
                onChange={(e) => setSortOrder(e.target.value as SortOrder)}
                className="bg-dark-600 text-gray-300 text-xs rounded px-2 py-1 border border-dark-500 focus:border-primary focus:outline-none cursor-pointer"
              >
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
              </select>
            </div>

            {/* Expand/Collapse toggle */}
            <button
              onClick={() => setIsExpanded(!isExpanded)}
              className="flex items-center gap-1 text-xs text-gray-400 hover:text-white transition-colors"
              title={isExpanded ? 'Collapse cards' : 'Expand cards'}
            >
              <svg
                className={`w-3.5 h-3.5 transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
              </svg>
              <span>{isExpanded ? 'Less' : 'More'}</span>
            </button>
          </div>

          {/* Card counter */}
          <span className="text-xs text-gray-500">
            {sortedSelectedIndex + 1} of {sortedSongs.length}
          </span>
        </div>
      )}

      {/* Carousel container with navigation */}
      <div className="relative group">
        {/* Left arrow */}
        {sortedSongs.length > 2 && (
          <button
            onClick={() => scrollByCard('left')}
            disabled={!canScrollLeft}
            aria-label="Scroll left"
            className={`
              absolute left-0 top-1/2 -translate-y-1/2 z-10
              w-8 h-8 flex items-center justify-center
              bg-dark-700/90 rounded-full border border-dark-500
              transition-all duration-200
              ${canScrollLeft
                ? 'opacity-0 group-hover:opacity-100 hover:bg-dark-600 hover:border-primary cursor-pointer'
                : 'opacity-0 cursor-default'
              }
            `}
          >
            <svg className="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}

        {/* Left fade indicator */}
        {canScrollLeft && (
          <div className="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-dark-800 to-transparent pointer-events-none z-[5]" />
        )}

        {/* Scrollable container */}
        <div
          ref={scrollRef}
          className="overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin"
        >
          <div className="flex gap-3 px-2">
            {sortedSongs.map((song, idx) => {
              const originalIndex = songs.findIndex(s => s.id === song.id);
              return (
                <VersionCard
                  key={song.id}
                  song={song}
                  isSelected={originalIndex === selectedIndex}
                  isPlaying={currentSongId === song.id && isPlaying}
                  isExpanded={isExpanded}
                  isInQueue={isInQueue ? isInQueue(song.id) : false}
                  onSelect={() => handleSelect(idx)}
                  onPlay={() => onPlay(song)}
                  onAddToQueue={() => handleAddToQueue(song)}
                />
              );
            })}
          </div>
        </div>

        {/* Right fade indicator */}
        {canScrollRight && (
          <div className="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-dark-800 to-transparent pointer-events-none z-[5]" />
        )}

        {/* Right arrow */}
        {sortedSongs.length > 2 && (
          <button
            onClick={() => scrollByCard('right')}
            disabled={!canScrollRight}
            aria-label="Scroll right"
            className={`
              absolute right-0 top-1/2 -translate-y-1/2 z-10
              w-8 h-8 flex items-center justify-center
              bg-dark-700/90 rounded-full border border-dark-500
              transition-all duration-200
              ${canScrollRight
                ? 'opacity-0 group-hover:opacity-100 hover:bg-dark-600 hover:border-primary cursor-pointer'
                : 'opacity-0 cursor-default'
              }
            `}
          >
            <svg className="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        )}
      </div>

      {/* Pagination dots */}
      {sortedSongs.length > 1 && sortedSongs.length <= 10 && (
        <div className="flex justify-center gap-1.5 mt-2">
          {sortedSongs.map((song, idx) => {
            const originalIndex = songs.findIndex(s => s.id === song.id);
            const isActive = originalIndex === selectedIndex;
            return (
              <button
                key={song.id}
                onClick={() => handleSelect(idx)}
                aria-label={`Go to version ${idx + 1}`}
                className={`
                  w-2 h-2 rounded-full transition-all duration-200
                  ${isActive
                    ? 'bg-primary w-4'
                    : 'bg-dark-500 hover:bg-dark-400'
                  }
                `}
              />
            );
          })}
        </div>
      )}
    </div>
  );
}
