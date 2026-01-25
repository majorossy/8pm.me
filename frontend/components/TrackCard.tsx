'use client';

// TrackCard - displays a track with play button and version carousel

import { useState } from 'react';
import { Track, Song, formatDuration } from '@/lib/api';
import { usePlayer } from '@/context/PlayerContext';
import { useCart } from '@/context/CartContext';
import VersionCarousel from './VersionCarousel';

interface TrackCardProps {
  track: Track;
  index?: number;
}

export default function TrackCard({ track, index }: TrackCardProps) {
  const { currentSong, isPlaying, playSong, togglePlay } = usePlayer();
  const { addToCart, isInCart } = useCart();

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

  return (
    <div
      className={`group rounded-lg transition-colors ${
        isCurrentTrack ? 'bg-dark-600' : 'hover:bg-dark-600'
      }`}
    >
      {/* Main track row */}
      <div className="flex items-center gap-4 p-3">
        {/* Track number / Play button */}
        <div className="w-10 flex items-center justify-center">
          <button
            onClick={handlePlayClick}
            className="w-10 h-10 flex items-center justify-center rounded-full bg-primary hover:bg-primary-dark transition-colors"
          >
            {isSelectedPlaying && isPlaying ? (
              <svg className="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" />
                <rect x="14" y="4" width="4" height="16" />
              </svg>
            ) : (
              <svg className="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            )}
          </button>
        </div>

        {/* Track number */}
        {index !== undefined && (
          <span className="w-6 text-sm text-gray-500 hidden sm:block">{index}</span>
        )}

        {/* Track info */}
        <div className="flex-1 min-w-0">
          <p className={`font-medium truncate ${isCurrentTrack ? 'text-primary' : 'text-white'}`}>
            {track.title}
          </p>
          <p className="text-sm text-gray-400 truncate">
            {track.artistName}
            {hasMultipleVersions && (
              <span className="ml-2 text-xs text-primary">
                {track.songCount} versions
              </span>
            )}
          </p>
        </div>

        {/* Duration (of selected version) */}
        <span className="text-sm text-gray-400 hidden sm:block min-w-[45px] text-right">
          {formatDuration(selectedSong?.duration || track.totalDuration)}
        </span>

        {/* Add to queue button */}
        <button
          onClick={handleAddToQueue}
          disabled={inQueue}
          className={`p-2 rounded-full transition-colors ${
            inQueue
              ? 'text-primary cursor-default'
              : 'text-gray-400 hover:text-white hover:bg-dark-500'
          }`}
          title={inQueue ? 'In queue' : 'Add to queue'}
        >
          {inQueue ? (
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
          ) : (
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
          )}
        </button>
      </div>

      {/* Version carousel - shown when track has multiple versions */}
      {hasMultipleVersions && (
        <div className="px-3 pb-3">
          <VersionCarousel
            songs={track.songs}
            selectedIndex={selectedIndex}
            onSelect={handleVersionSelect}
            onPlay={handleVersionPlay}
            currentSongId={currentSong?.id}
            isPlaying={isPlaying}
          />
        </div>
      )}
    </div>
  );
}
