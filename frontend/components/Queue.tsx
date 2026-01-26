'use client';

// Queue drawer - displays cart items (theme-aware)

import { usePlayer } from '@/context/PlayerContext';
import { useCart } from '@/context/CartContext';
import { useTheme } from '@/context/ThemeContext';
import { formatDuration } from '@/lib/api';

export default function Queue() {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const {
    queue,
    currentSong,
    isQueueOpen,
    toggleQueue,
    playFromQueue,
  } = usePlayer();

  const { cart, removeFromCart, clearCart } = useCart();

  if (!isQueueOpen) return null;

  if (isMetro) {
    // Metro/Time Machine style - Queue slides from right, accounts for left sidebar and header
    return (
      <>
        {/* Backdrop - starts after header and left sidebar */}
        <div
          className="fixed top-[60px] right-0 bottom-0 left-[280px] bg-black/30 z-40"
          onClick={toggleQueue}
        />

        {/* Drawer - starts below header, full height to bottom */}
        <div className="fixed right-0 top-[60px] bottom-0 w-80 bg-white border-l border-[#d4d0c8] z-50 flex flex-col shadow-lg">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-base font-semibold text-[#1a1a1a]">Queue</h2>
            <div className="flex items-center gap-2">
              {queue.length > 0 && (
                <button
                  onClick={clearCart}
                  className="text-xs text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
                >
                  Clear
                </button>
              )}
              <button
                onClick={toggleQueue}
                className="p-2 text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
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
              <div className="flex flex-col items-center justify-center h-full text-[#6b6b6b]">
                <svg className="w-12 h-12 mb-4 text-[#d4d0c8]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
                </svg>
                <p className="font-display text-sm font-semibold">Queue Empty</p>
                <p className="text-xs mt-1">Add songs to get started</p>
              </div>
            ) : (
              <ul className="p-2 space-y-1">
                {queue.map((song, index) => {
                  const cartItem = cart.items.find(item => item.song.id === song.id);
                  const isCurrentSong = currentSong?.id === song.id;
                  return (
                    <li
                      key={song.id}
                      className={`
                        flex items-center gap-3 p-3 cursor-pointer transition-all rounded
                        ${isCurrentSong
                          ? 'bg-[#e85d04]/10 border border-[#e85d04]'
                          : 'hover:bg-[#f8f6f1] border border-transparent'
                        }
                      `}
                      onClick={() => playFromQueue(index)}
                    >
                      <div className={`
                        w-8 h-8 flex items-center justify-center text-xs font-display font-semibold
                        ${isCurrentSong ? 'text-[#e85d04]' : 'text-[#6b6b6b]'}
                      `}>
                        {isCurrentSong ? (
                          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                          </svg>
                        ) : (
                          String(index + 1).padStart(2, '0')
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className={`text-sm font-display truncate ${
                          isCurrentSong ? 'text-[#e85d04] font-semibold' : 'text-[#1a1a1a]'
                        }`}>
                          {song.title}
                        </p>
                        <p className="text-xs text-[#6b6b6b] truncate">
                          {song.artistName}
                        </p>
                      </div>
                      <span className="text-xs text-[#6b6b6b] font-mono">
                        {formatDuration(song.duration)}
                      </span>
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          if (cartItem) {
                            removeFromCart(cartItem.id);
                          }
                        }}
                        className="p-1 text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
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
            <div className="p-4 border-t border-[#d4d0c8]">
              <p className="text-xs text-[#6b6b6b] text-center">
                {queue.length} {queue.length === 1 ? 'song' : 'songs'} in queue
              </p>
            </div>
          )}
        </div>
      </>
    );
  }

  // Default Tron/Synthwave style
  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
        onClick={toggleQueue}
      />

      {/* Drawer */}
      <div className="fixed right-0 top-0 bottom-20 w-80 bg-dark-800/95 border-l border-neon-cyan/20 z-50 flex flex-col backdrop-blur-sm">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-neon-cyan/20">
          <h2 className="font-display text-sm uppercase tracking-[0.2em] text-white">Queue</h2>
          <div className="flex items-center gap-2">
            {queue.length > 0 && (
              <button
                onClick={clearCart}
                className="text-xs text-text-dim hover:text-neon-pink transition-colors uppercase tracking-wider"
              >
                Clear
              </button>
            )}
            <button
              onClick={toggleQueue}
              className="p-2 text-text-dim hover:text-neon-cyan transition-colors"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        {/* Song list */}
        <div className="flex-1 overflow-y-auto scrollbar-thin">
          {queue.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-text-dim">
              <svg className="w-12 h-12 mb-4 text-neon-cyan/30" fill="currentColor" viewBox="0 0 24 24">
                <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
              </svg>
              <p className="font-display text-sm uppercase tracking-wider">Queue Empty</p>
              <p className="text-xs mt-1">Add songs to get started</p>
            </div>
          ) : (
            <ul className="p-2 space-y-1">
              {queue.map((song, index) => {
                const cartItem = cart.items.find(item => item.song.id === song.id);
                const isCurrentSong = currentSong?.id === song.id;
                return (
                  <li
                    key={song.id}
                    className={`
                      flex items-center gap-3 p-3 cursor-pointer transition-all
                      ${isCurrentSong
                        ? 'bg-neon-cyan/10 border border-neon-cyan/30'
                        : 'hover:bg-white/5 border border-transparent'
                      }
                    `}
                    onClick={() => playFromQueue(index)}
                  >
                    <div className={`
                      w-8 h-8 flex items-center justify-center text-xs font-display
                      ${isCurrentSong ? 'text-neon-cyan' : 'text-text-dim'}
                    `}>
                      {isCurrentSong ? (
                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                        </svg>
                      ) : (
                        String(index + 1).padStart(2, '0')
                      )}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className={`text-sm font-display truncate ${
                        isCurrentSong ? 'text-neon-cyan' : 'text-white'
                      }`}>
                        {song.title}
                      </p>
                      <p className="text-[10px] text-text-dim truncate uppercase tracking-wider">
                        {song.artistName}
                      </p>
                    </div>
                    <span className="text-[10px] text-text-dim font-mono">
                      {formatDuration(song.duration)}
                    </span>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        if (cartItem) {
                          removeFromCart(cartItem.id);
                        }
                      }}
                      className="p-1 text-text-dim hover:text-neon-pink transition-colors"
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
          <div className="p-4 border-t border-neon-cyan/20">
            <p className="text-[10px] text-text-dim text-center uppercase tracking-[0.2em]">
              {queue.length} {queue.length === 1 ? 'song' : 'songs'} in queue
            </p>
          </div>
        )}
      </div>
    </>
  );
}
