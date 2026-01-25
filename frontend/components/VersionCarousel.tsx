'use client';

// VersionCarousel - horizontal scrolling carousel of song version cards

import { Song, formatDuration } from '@/lib/api';

interface VersionCarouselProps {
  songs: Song[];
  selectedIndex: number;
  onSelect: (index: number) => void;
  onPlay: (song: Song) => void;
  currentSongId?: string;
  isPlaying: boolean;
}

interface VersionCardProps {
  song: Song;
  isSelected: boolean;
  isPlaying: boolean;
  onSelect: () => void;
  onPlay: () => void;
}

function VersionCard({ song, isSelected, isPlaying, onSelect, onPlay }: VersionCardProps) {
  // Parse album name for display - try to extract date/venue
  const albumName = song.albumName || 'Unknown Album';
  const showDate = song.showDate;
  const showVenue = song.showVenue;

  // Try to extract date from album name if not explicitly set
  // Common format: "2022-12-31 Red Rocks" or "Red Rocks 2022-12-31"
  let displayDate = showDate;
  let displayVenue = showVenue;

  if (!displayDate || !displayVenue) {
    // Try to parse from albumName
    const dateMatch = albumName.match(/(\d{4}-\d{2}-\d{2})/);
    if (dateMatch) {
      displayDate = displayDate || dateMatch[1];
      // Venue is the part without the date
      const venuePart = albumName.replace(dateMatch[0], '').trim().replace(/^[-\s]+|[-\s]+$/g, '');
      displayVenue = displayVenue || venuePart || albumName;
    } else {
      displayVenue = displayVenue || albumName;
    }
  }

  // Format date for display (YYYY-MM-DD -> MM/DD/YY)
  let formattedDate = displayDate;
  if (displayDate && displayDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
    const [year, month, day] = displayDate.split('-');
    formattedDate = `${month}/${day}/${year.slice(-2)}`;
  }

  // Truncate lineage for display (show first part of recording chain)
  const truncatedLineage = song.lineage
    ? song.lineage.length > 60
      ? song.lineage.substring(0, 60) + '...'
      : song.lineage
    : null;

  // Truncate notes for display
  const truncatedNotes = song.notes
    ? song.notes.length > 80
      ? song.notes.substring(0, 80) + '...'
      : song.notes
    : null;

  const handlePlayClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    onPlay();
  };

  return (
    <div
      onClick={onSelect}
      className={`
        relative flex-shrink-0 w-56 p-3 rounded-lg cursor-pointer
        transition-all duration-200 snap-start
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
      <div className="space-y-1.5">
        {/* Date & Duration row */}
        <div className="flex items-center justify-between">
          {formattedDate && (
            <p className="text-xs font-semibold text-primary">
              {formattedDate}
            </p>
          )}
          <p className="text-xs text-gray-400">
            {formatDuration(song.duration)}
          </p>
        </div>

        {/* Venue/Album name */}
        <p className="text-sm font-medium text-white truncate" title={displayVenue}>
          {displayVenue}
        </p>

        {/* Lineage (recording source) */}
        {truncatedLineage && (
          <div className="pt-1">
            <p className="text-[10px] uppercase tracking-wide text-gray-500 mb-0.5">Source</p>
            <p className="text-xs text-gray-400 font-mono leading-tight" title={song.lineage}>
              {truncatedLineage}
            </p>
          </div>
        )}

        {/* Notes */}
        {truncatedNotes && (
          <div className="pt-1">
            <p className="text-[10px] uppercase tracking-wide text-gray-500 mb-0.5">Notes</p>
            <p className="text-xs text-gray-300 leading-tight" title={song.notes}>
              {truncatedNotes}
            </p>
          </div>
        )}
      </div>

      {/* Play button */}
      <button
        onClick={handlePlayClick}
        className={`
          mt-3 w-full flex items-center justify-center gap-2 py-1.5 rounded
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
    </div>
  );
}

export default function VersionCarousel({
  songs,
  selectedIndex,
  onSelect,
  onPlay,
  currentSongId,
  isPlaying,
}: VersionCarouselProps) {
  return (
    <div className="mt-2 -mx-2">
      {/* Carousel container with horizontal scroll */}
      <div className="overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin">
        <div className="flex gap-3 px-2">
          {songs.map((song, idx) => (
            <VersionCard
              key={song.id}
              song={song}
              isSelected={idx === selectedIndex}
              isPlaying={currentSongId === song.id && isPlaying}
              onSelect={() => onSelect(idx)}
              onPlay={() => onPlay(song)}
            />
          ))}
        </div>
      </div>

      {/* Scroll hint */}
      {songs.length > 2 && (
        <p className="text-xs text-gray-500 text-center mt-1">
          {songs.length} versions available - scroll to see more
        </p>
      )}
    </div>
  );
}
