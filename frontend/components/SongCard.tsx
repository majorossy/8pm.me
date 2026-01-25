'use client';

// SongCard - displays a song (Magento product) with play and add-to-queue buttons

import { Song, formatDuration } from '@/lib/api';
import { usePlayer } from '@/context/PlayerContext';
import { useCart } from '@/context/CartContext';
import { useWishlist } from '@/context/WishlistContext';

interface SongCardProps {
  song: Song;
  index?: number;
}

export default function SongCard({ song, index }: SongCardProps) {
  const { currentSong, isPlaying, playSong, togglePlay } = usePlayer();
  const { addToCart, isInCart } = useCart();
  const { addToWishlist, isInWishlist } = useWishlist();

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

  return (
    <div
      className={`group flex items-center gap-4 p-3 rounded-lg hover:bg-dark-600 transition-colors ${
        isCurrentSong ? 'bg-dark-600' : ''
      }`}
    >
      {/* Play button / Index */}
      <div className="w-10 flex items-center justify-center">
        <button
          onClick={handlePlayClick}
          className="w-10 h-10 flex items-center justify-center rounded-full bg-primary hover:bg-primary-dark transition-colors"
        >
          {isCurrentSong && isPlaying ? (
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

      {/* Album art */}
      <div className="w-12 h-12 bg-dark-600 rounded overflow-hidden flex-shrink-0">
        <div className="w-full h-full bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center">
          <svg className="w-6 h-6 text-white/40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
          </svg>
        </div>
      </div>

      {/* Song info */}
      <div className="flex-1 min-w-0">
        <p className={`font-medium truncate ${isCurrentSong ? 'text-primary' : 'text-white'}`}>
          {song.title}
        </p>
        <p className="text-sm text-gray-400 truncate">{song.artistName}</p>
      </div>

      {/* Duration */}
      <span className="text-sm text-gray-400 hidden sm:block">
        {formatDuration(song.duration)}
      </span>

      {/* Favorite button (Wishlist) */}
      <button
        onClick={handleAddToFavorites}
        disabled={inFavorites}
        className={`p-2 rounded-full transition-colors ${
          inFavorites
            ? 'text-accent cursor-default'
            : 'text-gray-400 hover:text-accent hover:bg-dark-500'
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

      {/* Add to queue button (Cart) */}
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
  );
}
