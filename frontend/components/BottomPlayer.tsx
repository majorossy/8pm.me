'use client';

// BottomPlayer - fixed audio player bar (theme-aware)

import { usePlayer } from '@/context/PlayerContext';
import { useTheme } from '@/context/ThemeContext';
import { formatDuration } from '@/lib/api';

export default function BottomPlayer() {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const {
    currentSong,
    isPlaying,
    volume,
    currentTime,
    duration,
    queue,
    queueIndex,
    togglePlay,
    playNext,
    playPrev,
    setVolume,
    seek,
    toggleQueue,
    isQueueOpen,
  } = usePlayer();

  if (!currentSong) return null;

  const progress = duration > 0 ? (currentTime / duration) * 100 : 0;

  if (isMetro) {
    // Metro/Time Machine style - LEFT SIDEBAR (below header)
    return (
      <div className="fixed left-0 top-[60px] bottom-0 w-[280px] bg-white border-r border-[#d4d0c8] z-40 shadow-lg flex flex-col">
        {/* Header */}
        <div className="p-4 border-b border-[#d4d0c8]">
          <h2 className="font-display text-xs text-[#6b6b6b] uppercase tracking-wider">Now Playing</h2>
        </div>

        {/* Album Art */}
        <div className="p-6">
          <div className="w-full aspect-square bg-[#e8e4dc] border border-[#d4d0c8] flex items-center justify-center">
            <svg className="w-16 h-16 text-[#d4d0c8]" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
            </svg>
          </div>
        </div>

        {/* Song info */}
        <div className="px-6 pb-4 text-center">
          <p className="font-display text-base text-[#1a1a1a] font-semibold truncate">{currentSong.title}</p>
          <p className="text-sm text-[#6b6b6b] truncate mt-1">{currentSong.artistName}</p>
          {currentSong.showVenue && (
            <p className="text-xs text-[#6b6b6b] truncate mt-1">{currentSong.showVenue}</p>
          )}
          {currentSong.showDate && (
            <p className="text-xs text-[#e85d04] mt-1">{currentSong.showDate}</p>
          )}
        </div>

        {/* Progress bar */}
        <div className="px-6 py-4">
          <div
            className="w-full h-2 bg-[#e8e4dc] cursor-pointer group relative rounded-full"
            onClick={(e) => {
              const rect = e.currentTarget.getBoundingClientRect();
              const percent = (e.clientX - rect.left) / rect.width;
              seek(percent * duration);
            }}
          >
            <div
              className="h-full bg-[#e85d04] relative rounded-full transition-all"
              style={{ width: `${progress}%` }}
            >
              <div className="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 bg-[#e85d04] rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-sm" />
            </div>
          </div>
          <div className="flex justify-between mt-2">
            <span className="text-[10px] text-[#6b6b6b] font-mono">
              {formatDuration(Math.floor(currentTime))}
            </span>
            <span className="text-[10px] text-[#6b6b6b] font-mono">
              {formatDuration(Math.floor(duration))}
            </span>
          </div>
        </div>

        {/* Controls */}
        <div className="px-6 py-4 flex items-center justify-center gap-6">
          <button
            onClick={playPrev}
            disabled={queueIndex <= 0}
            className="text-[#6b6b6b] hover:text-[#e85d04] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z" />
            </svg>
          </button>

          <button
            onClick={togglePlay}
            className="w-14 h-14 flex items-center justify-center rounded-full bg-[#e85d04] text-white hover:bg-[#d44d00] transition-all shadow-md"
          >
            {isPlaying ? (
              <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" />
                <rect x="14" y="4" width="4" height="16" />
              </svg>
            ) : (
              <svg className="w-6 h-6 ml-1" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            )}
          </button>

          <button
            onClick={playNext}
            disabled={queueIndex >= queue.length - 1}
            className="text-[#6b6b6b] hover:text-[#e85d04] disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z" />
            </svg>
          </button>
        </div>

        {/* Volume */}
        <div className="px-6 py-4 border-t border-[#d4d0c8]">
          <div className="flex items-center gap-3">
            <button
              onClick={() => setVolume(volume === 0 ? 0.7 : 0)}
              className="text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
            >
              {volume === 0 ? (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                </svg>
              )}
            </button>
            <input
              type="range"
              min="0"
              max="1"
              step="0.01"
              value={volume}
              onChange={(e) => setVolume(parseFloat(e.target.value))}
              className="flex-1 accent-[#e85d04]"
            />
          </div>
        </div>

        {/* Queue toggle */}
        <div className="mt-auto p-4 border-t border-[#d4d0c8]">
          <button
            onClick={toggleQueue}
            className={`
              w-full py-3 px-4 flex items-center justify-center gap-2 transition-all border
              ${isQueueOpen
                ? 'bg-[#e85d04] text-white border-[#e85d04]'
                : 'border-[#d4d0c8] text-[#6b6b6b] hover:border-[#e85d04] hover:text-[#e85d04]'
              }
            `}
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
            </svg>
            <span className="font-display text-xs uppercase tracking-wider">
              Queue ({queue.length})
            </span>
          </button>
        </div>
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div className="fixed bottom-0 left-0 right-0 bg-dark-800/95 backdrop-blur-sm border-t border-neon-cyan/20 p-4 z-40">
      <div className="max-w-7xl mx-auto flex items-center gap-4">
        {/* Song info */}
        <div className="flex items-center gap-3 w-64 flex-shrink-0">
          <div className="w-14 h-14 bg-dark-700 rounded overflow-hidden album-frame p-[2px]">
            <div className="w-full h-full bg-dark-900 flex items-center justify-center">
              <svg className="w-6 h-6 text-neon-cyan/40" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
              </svg>
            </div>
          </div>
          <div className="min-w-0">
            <p className="font-display text-sm text-white truncate">{currentSong.title}</p>
            <p className="text-xs text-text-dim truncate">{currentSong.artistName}</p>
          </div>
        </div>

        {/* Controls */}
        <div className="flex-1 flex flex-col items-center gap-2">
          <div className="flex items-center gap-4">
            <button
              onClick={playPrev}
              disabled={queueIndex <= 0}
              className="text-text-dim hover:text-neon-cyan disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
            >
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 6h2v12H6zm3.5 6l8.5 6V6z" />
              </svg>
            </button>

            <button
              onClick={togglePlay}
              className="w-12 h-12 flex items-center justify-center rounded-full bg-neon-cyan text-dark-900 hover:shadow-[0_0_30px_var(--neon-cyan)] transition-all"
            >
              {isPlaying ? (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <rect x="6" y="4" width="4" height="16" />
                  <rect x="14" y="4" width="4" height="16" />
                </svg>
              ) : (
                <svg className="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              )}
            </button>

            <button
              onClick={playNext}
              disabled={queueIndex >= queue.length - 1}
              className="text-text-dim hover:text-neon-cyan disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
            >
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z" />
              </svg>
            </button>
          </div>

          {/* Progress bar */}
          <div className="w-full max-w-md flex items-center gap-3">
            <span className="text-[10px] text-text-dim font-mono w-12 text-right">
              {formatDuration(Math.floor(currentTime))}
            </span>
            <div
              className="flex-1 h-1 bg-dark-600 cursor-pointer group relative"
              onClick={(e) => {
                const rect = e.currentTarget.getBoundingClientRect();
                const percent = (e.clientX - rect.left) / rect.width;
                seek(percent * duration);
              }}
            >
              <div
                className="h-full bg-neon-cyan relative transition-all group-hover:shadow-[0_0_10px_var(--neon-cyan)]"
                style={{ width: `${progress}%` }}
              >
                <div className="absolute right-0 top-1/2 -translate-y-1/2 w-3 h-3 bg-neon-cyan rounded-full opacity-0 group-hover:opacity-100 shadow-[0_0_10px_var(--neon-cyan)] transition-opacity" />
              </div>
            </div>
            <span className="text-[10px] text-text-dim font-mono w-12">
              {formatDuration(Math.floor(duration))}
            </span>
          </div>
        </div>

        {/* Volume & Queue */}
        <div className="flex items-center gap-4 w-64 justify-end">
          {/* Volume */}
          <div className="flex items-center gap-2">
            <button
              onClick={() => setVolume(volume === 0 ? 0.7 : 0)}
              className="text-text-dim hover:text-neon-cyan transition-colors"
            >
              {volume === 0 ? (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z" />
                </svg>
              )}
            </button>
            <input
              type="range"
              min="0"
              max="1"
              step="0.01"
              value={volume}
              onChange={(e) => setVolume(parseFloat(e.target.value))}
              className="w-20"
            />
          </div>

          {/* Queue toggle */}
          <button
            onClick={toggleQueue}
            className={`
              p-2 rounded transition-all
              ${isQueueOpen
                ? 'bg-neon-pink/20 text-neon-pink shadow-[0_0_15px_rgba(255,45,149,0.3)]'
                : 'text-text-dim hover:text-neon-pink'
              }
            `}
          >
            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  );
}
