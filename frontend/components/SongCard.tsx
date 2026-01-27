'use client';

// SongCard - displays a song with play and add-to-queue buttons (theme-aware)

import { useState } from 'react';
import { Song, formatDuration } from '@/lib/api';
import { usePlayer } from '@/context/PlayerContext';
import { useCart } from '@/context/CartContext';
import { useWishlist } from '@/context/WishlistContext';
import { useTheme } from '@/context/ThemeContext';
import { AddToPlaylistModal } from '@/components/Playlists/AddToPlaylistModal';

interface SongCardProps {
  song: Song;
  index?: number;
}

export default function SongCard({ song, index }: SongCardProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';
  const { currentSong, isPlaying, playSong, togglePlay } = usePlayer();
  const { addToCart, isInCart } = useCart();
  const { addToWishlist, isInWishlist } = useWishlist();
  const [showPlaylistModal, setShowPlaylistModal] = useState(false);

  const isCurrentSong = currentSong?.id === song.id;
  const inQueue = isInCart(song.id);
  const inFavorites = isInWishlist(song.id);

  const handlePlayClick = () => {
    if (isCurrentSong) {
      togglePlay();
    } else {
      playSong(song);
    }
  };

  const handleAddToQueue = (e: React.MouseEvent) => {
    e.stopPropagation();
    addToCart(song);
  };

  const handleAddToFavorites = (e: React.MouseEvent) => {
    e.stopPropagation();
    addToWishlist(song);
  };

  // Jamify/Spotify style - row-based with hover background
  if (isJamify) {
    return (
      <div
        className={`
          group grid grid-cols-[16px_1fr_auto_auto_auto_auto] gap-4 items-center px-4 py-2 rounded-md transition-all
          ${isCurrentSong ? 'bg-[#282828]' : 'hover:bg-[#282828]'}
        `}
      >
        {/* Index or play icon */}
        <div className="w-4 flex items-center justify-center">
          <span className={`text-sm group-hover:hidden ${isCurrentSong ? 'text-[#1DB954]' : 'text-[#a7a7a7]'}`}>
            {isCurrentSong && isPlaying ? (
              <svg className="w-4 h-4 text-[#1DB954]" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
              </svg>
            ) : (
              index !== undefined ? String(index).padStart(2, '0') : ''
            )}
          </span>
          <button
            onClick={handlePlayClick}
            className="hidden group-hover:block text-white"
          >
            {isCurrentSong && isPlaying ? (
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
        </div>

        {/* Song info */}
        <div className="flex flex-col min-w-0">
          <p className={`text-base truncate ${isCurrentSong ? 'text-[#1DB954]' : 'text-white'}`}>
            {song.title}
          </p>
          <p className="text-sm text-[#a7a7a7] truncate">
            {song.artistName}
          </p>
        </div>

        {/* Duration */}
        <span className="text-sm text-[#a7a7a7]">
          {formatDuration(song.duration)}
        </span>

        {/* Favorite button */}
        <button
          onClick={handleAddToFavorites}
          disabled={inFavorites}
          className={`transition-colors opacity-0 group-hover:opacity-100 ${
            inFavorites ? 'text-[#1DB954] opacity-100' : 'text-[#a7a7a7] hover:text-white'
          }`}
          title={inFavorites ? 'In favorites' : 'Add to favorites'}
        >
          <svg
            className="w-5 h-5"
            fill={inFavorites ? 'currentColor' : 'none'}
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
          </svg>
        </button>

        {/* Add to playlist */}
        <button
          onClick={(e) => {
            e.stopPropagation();
            setShowPlaylistModal(true);
          }}
          className="transition-colors opacity-0 group-hover:opacity-100 text-[#a7a7a7] hover:text-white"
          title="Add to playlist"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </button>

        {/* Add to queue */}
        <button
          onClick={handleAddToQueue}
          disabled={inQueue}
          className={`transition-colors opacity-0 group-hover:opacity-100 ${
            inQueue ? 'text-[#1DB954] opacity-100' : 'text-[#a7a7a7] hover:text-white'
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

        {/* Add to Playlist Modal */}
        <AddToPlaylistModal
          isOpen={showPlaylistModal}
          onClose={() => setShowPlaylistModal(false)}
          song={song}
        />
      </div>
    );
  }

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div
        className={`
          group flex items-center gap-4 p-4 transition-all border
          ${isCurrentSong
            ? 'bg-white border-[#e85d04]'
            : 'bg-white border-[#d4d0c8] hover:border-[#e85d04]'
          }
        `}
      >
        {/* Play button */}
        <div className="w-10 flex items-center justify-center">
          <button
            onClick={handlePlayClick}
            className={`
              w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
              ${isCurrentSong && isPlaying
                ? 'bg-[#e85d04] border-[#e85d04] text-white'
                : 'border-[#e85d04] text-[#e85d04] hover:bg-[#e85d04] hover:text-white'
              }
            `}
          >
            {isCurrentSong && isPlaying ? (
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
        </div>

        {/* Index number */}
        {index !== undefined && (
          <span className="w-8 font-display text-sm text-[#6b6b6b] hidden sm:block">
            {String(index).padStart(2, '0')}
          </span>
        )}

        {/* Song info */}
        <div className="flex-1 min-w-0">
          <p className={`font-display text-sm truncate ${isCurrentSong ? 'text-[#e85d04]' : 'text-[#1a1a1a]'}`}>
            {song.title}
          </p>
          <p className="text-xs text-[#6b6b6b] truncate">
            {song.artistName}
          </p>
        </div>

        {/* Duration */}
        <span className="text-xs text-[#6b6b6b] font-mono hidden sm:block">
          {formatDuration(song.duration)}
        </span>

        {/* Favorite button */}
        <button
          onClick={handleAddToFavorites}
          disabled={inFavorites}
          className={`p-2 transition-colors ${
            inFavorites
              ? 'text-[#e85d04] cursor-default'
              : 'text-[#6b6b6b] hover:text-[#e85d04]'
          }`}
          title={inFavorites ? 'In favorites' : 'Add to favorites'}
        >
          <svg
            className="w-5 h-5"
            fill={inFavorites ? 'currentColor' : 'none'}
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
            />
          </svg>
        </button>

        {/* Add to queue button */}
        <button
          onClick={handleAddToQueue}
          disabled={inQueue}
          className={`p-2 transition-colors ${
            inQueue
              ? 'text-[#e85d04] cursor-default'
              : 'text-[#6b6b6b] hover:text-[#e85d04]'
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
    );
  }

  // Default Tron/Synthwave style
  return (
    <div
      className={`
        group flex items-center gap-4 p-4 transition-all border
        ${isCurrentSong
          ? 'bg-dark-800 border-neon-cyan/30'
          : 'bg-dark-800/50 border-transparent hover:bg-dark-800 hover:border-neon-cyan/20'
        }
      `}
    >
      {/* Play button */}
      <div className="w-10 flex items-center justify-center">
        <button
          onClick={handlePlayClick}
          className={`
            w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all
            ${isCurrentSong && isPlaying
              ? 'bg-neon-pink border-neon-pink text-dark-900 shadow-[0_0_20px_var(--neon-pink)]'
              : 'border-neon-pink text-neon-pink hover:bg-neon-pink hover:text-dark-900 hover:shadow-[0_0_20px_var(--neon-pink)]'
            }
          `}
        >
          {isCurrentSong && isPlaying ? (
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
      </div>

      {/* Index number */}
      {index !== undefined && (
        <span className="w-8 font-display text-sm text-text-dim hidden sm:block">
          {String(index).padStart(2, '0')}
        </span>
      )}

      {/* Song info */}
      <div className="flex-1 min-w-0">
        <p className={`font-display text-sm truncate ${isCurrentSong ? 'text-neon-cyan' : 'text-white'}`}>
          {song.title}
        </p>
        <p className="text-[10px] text-text-dim truncate uppercase tracking-wider">
          {song.artistName}
        </p>
      </div>

      {/* Duration */}
      <span className="text-xs text-text-dim font-mono hidden sm:block">
        {formatDuration(song.duration)}
      </span>

      {/* Favorite button */}
      <button
        onClick={handleAddToFavorites}
        disabled={inFavorites}
        className={`p-2 transition-colors ${
          inFavorites
            ? 'text-neon-pink cursor-default'
            : 'text-text-dim hover:text-neon-pink'
        }`}
        title={inFavorites ? 'In favorites' : 'Add to favorites'}
      >
        <svg
          className="w-5 h-5"
          fill={inFavorites ? 'currentColor' : 'none'}
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
          />
        </svg>
      </button>

      {/* Add to queue button */}
      <button
        onClick={handleAddToQueue}
        disabled={inQueue}
        className={`p-2 transition-colors ${
          inQueue
            ? 'text-neon-cyan cursor-default'
            : 'text-text-dim hover:text-neon-cyan'
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
  );
}
