'use client';

// Queue drawer - displays cart items (Magento cart = playback queue)

import { usePlayer } from '@/context/PlayerContext';
import { useCart } from '@/context/CartContext';
import { formatDuration } from '@/lib/api';

export default function Queue() {
  const {
    queue,
    currentSong,
    isQueueOpen,
    toggleQueue,
    playFromQueue,
  } = usePlayer();

  const { cart, removeFromCart, clearCart } = useCart();

  if (!isQueueOpen) return null;

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/50 z-40"
        onClick={toggleQueue}
      />

      {/* Drawer */}
      <div className="fixed right-0 top-0 bottom-20 w-80 bg-dark-800 border-l border-dark-600 z-50 flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-dark-600">
          <h2 className="text-lg font-semibold text-white">Queue</h2>
          <div className="flex items-center gap-2">
            {queue.length > 0 && (
              <button
                onClick={clearCart}
                className="text-sm text-gray-400 hover:text-white transition-colors"
              >
                Clear
              </button>
            )}
            <button
              onClick={toggleQueue}
              className="p-2 text-gray-400 hover:text-white transition-colors"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        {/* Song list */}
        <div className="flex-1 overflow-y-auto">
          {queue.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-gray-400">
              <svg className="w-12 h-12 mb-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
              </svg>
              <p>Your queue is empty</p>
              <p className="text-sm">Add songs to get started</p>
            </div>
          ) : (
            <ul className="p-2">
              {queue.map((song, index) => {
                // Find the cart item ID for this song
                const cartItem = cart.items.find(item => item.song.id === song.id);
                return (
                  <li
                    key={song.id}
                    className={`flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors ${
                      currentSong?.id === song.id
                        ? 'bg-dark-600'
                        : 'hover:bg-dark-700'
                    }`}
                    onClick={() => playFromQueue(index)}
                  >
                    <div className="w-10 h-10 bg-dark-600 rounded overflow-hidden flex-shrink-0">
                      <div className="w-full h-full bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center">
                        {currentSong?.id === song.id ? (
                          <svg className="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                          </svg>
                        ) : (
                          <span className="text-sm text-white/40">{index + 1}</span>
                        )}
                      </div>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className={`text-sm font-medium truncate ${
                        currentSong?.id === song.id ? 'text-primary' : 'text-white'
                      }`}>
                        {song.title}
                      </p>
                      <p className="text-xs text-gray-400 truncate">{song.artistName}</p>
                    </div>
                    <span className="text-xs text-gray-400">
                      {formatDuration(song.duration)}
                    </span>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        if (cartItem) {
                          removeFromCart(cartItem.id);
                        }
                      }}
                      className="p-1 text-gray-400 hover:text-white transition-colors"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </li>
                );
              })}
            </ul>
          )}
        </div>

        {/* Footer */}
        {queue.length > 0 && (
          <div className="p-4 border-t border-dark-600">
            <p className="text-sm text-gray-400 text-center">
              {queue.length} {queue.length === 1 ? 'song' : 'songs'} in queue
            </p>
          </div>
        )}
      </div>
    </>
  );
}
