'use client';

// Queue drawer - displays album tracks + up-next (theme-aware)

import { usePlayer } from '@/context/PlayerContext';
import { useQueue } from '@/context/QueueContext';
import { useTheme } from '@/context/ThemeContext';
import { useMobileUI } from '@/context/MobileUIContext';
import { formatDuration } from '@/lib/api';
import { getSelectedSong } from '@/lib/queueTypes';
import SwipeableQueueItem from '@/components/SwipeableQueueItem';

export default function Queue() {
  const { theme } = useTheme();
  const { isMobile } = useMobileUI();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';
  const {
    currentSong,
    isPlaying,
    isQueueOpen,
    toggleQueue,
    playFromQueue,
  } = usePlayer();

  const {
    queue,
    currentTrack,
    hasAlbum,
    totalTracks,
    hasUpNext,
    selectVersion,
    removeTrack,
    removeFromUpNext,
    clearUpNext,
    clearQueue,
  } = useQueue();

  if (!isQueueOpen) return null;

  // Jamify theme - slides from right, positioned above bottom player
  if (isJamify) {
    return (
      <>
        {/* Backdrop */}
        <div
          className={`fixed z-40 bg-black/60 ${
            isMobile ? 'inset-0' : 'top-0 right-0 bottom-[90px] left-[240px]'
          }`}
          onClick={toggleQueue}
        />

        {/* Drawer - Full screen on mobile */}
        <div className={`fixed z-50 flex flex-col ${
          isMobile
            ? 'inset-0 bg-gradient-to-b from-[#404040] to-[#121212] safe-top safe-bottom'
            : 'right-0 top-0 bottom-[90px] w-96 bg-[#121212] border-l border-[#282828]'
        }`}>
          {/* Mobile drag indicator */}
          {isMobile && (
            <div className="flex justify-center pt-3 pb-1">
              <div className="w-10 h-1 bg-white/30 rounded-full" />
            </div>
          )}

          {/* Header */}
          <div className={`flex items-center justify-between px-4 ${isMobile ? 'py-3' : 'p-4 border-b border-[#282828]'}`}>
            {isMobile ? (
              <>
                <button
                  onClick={toggleQueue}
                  className="p-2 -ml-2 text-white"
                  aria-label="Close queue"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <h2 className="text-base font-bold text-white">Queue</h2>
                <div className="w-10" /> {/* Spacer for centering */}
              </>
            ) : (
              <>
                <h2 className="text-base font-bold text-white">Queue</h2>
                <div className="flex items-center gap-2">
                  {(hasAlbum || hasUpNext) && (
                    <button
                      onClick={clearQueue}
                      className="text-xs text-[#a7a7a7] hover:text-white transition-colors"
                    >
                      Clear all
                    </button>
                  )}
                  <button
                    onClick={toggleQueue}
                    className="p-2 text-[#a7a7a7] hover:text-white transition-colors"
                  >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </>
            )}
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto prevent-overscroll">
            {!hasAlbum && !hasUpNext ? (
              <div className="flex flex-col items-center justify-center h-full text-[#a7a7a7]">
                <svg className="w-12 h-12 mb-4 text-[#535353]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
                </svg>
                <p className="font-semibold">Queue is empty</p>
                <p className="text-sm mt-1">Play an album to get started</p>
              </div>
            ) : (
              <>
                {/* Now Playing */}
                {currentTrack && (
                  <div className="p-4 border-b border-[#282828]">
                    <p className="text-xs text-[#a7a7a7] uppercase tracking-wider mb-3">Now Playing</p>
                    <div className="flex items-center gap-3">
                      {queue.album?.coverArt ? (
                        <img
                          src={queue.album.coverArt}
                          alt={queue.album.name}
                          className="w-12 h-12 object-cover rounded"
                        />
                      ) : (
                        <div className="w-12 h-12 bg-[#282828] rounded flex items-center justify-center">
                          <svg className="w-6 h-6 text-[#535353]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                          </svg>
                        </div>
                      )}
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-[#1DB954] truncate">
                          {getSelectedSong(currentTrack)?.title || currentTrack.title}
                        </p>
                        <p className="text-xs text-[#a7a7a7] truncate">
                          {queue.album?.artistName}
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Album Tracks */}
                {hasAlbum && queue.album && (
                  <div className="border-b border-[#282828]">
                    <div className="p-4">
                      <p className="text-xs text-[#a7a7a7] uppercase tracking-wider">
                        Next from: {queue.album.name}
                      </p>
                    </div>
                    <ul>
                      {queue.tracks.slice(queue.currentTrackIndex + 1).map((track, idx) => {
                        const song = getSelectedSong(track);
                        const actualIndex = queue.currentTrackIndex + 1 + idx;
                        const isCurrentlyPlaying = currentSong?.id === song?.id;

                        const trackContent = (
                          <div className="flex items-center gap-3">
                            <span className={`w-5 text-sm ${isCurrentlyPlaying ? 'text-[#1DB954]' : 'text-[#a7a7a7]'}`}>
                              {isCurrentlyPlaying && isPlaying ? (
                                <span className="jamify-eq-bars">
                                  <span /><span /><span />
                                </span>
                              ) : (
                                actualIndex + 1
                              )}
                            </span>
                            <div className="flex-1 min-w-0">
                              <p className={`text-sm truncate ${isCurrentlyPlaying ? 'text-[#1DB954]' : 'text-white'}`}>
                                {track.title}
                              </p>
                              <p className="text-xs text-[#a7a7a7] truncate">
                                {queue.album?.artistName}
                              </p>
                            </div>
                            {song && (
                              <span className="text-xs text-[#a7a7a7] font-mono">
                                {formatDuration(song.duration)}
                              </span>
                            )}
                          </div>
                        );

                        // Use swipeable wrapper on mobile
                        if (isMobile) {
                          return (
                            <SwipeableQueueItem
                              key={track.trackId}
                              onDelete={() => removeTrack(actualIndex)}
                              className={`px-4 py-2 cursor-pointer ${
                                isCurrentlyPlaying ? 'bg-[#282828]' : ''
                              }`}
                            >
                              <div onClick={() => playFromQueue(actualIndex)}>
                                {trackContent}
                              </div>
                            </SwipeableQueueItem>
                          );
                        }

                        return (
                          <li
                            key={track.trackId}
                            className={`px-4 py-2 hover:bg-[#282828] cursor-pointer transition-colors ${
                              isCurrentlyPlaying ? 'bg-[#282828]' : ''
                            }`}
                            onClick={() => playFromQueue(actualIndex)}
                          >
                            {trackContent}
                          </li>
                        );
                      })}
                    </ul>
                  </div>
                )}

                {/* Up Next Section */}
                {hasUpNext && (
                  <div>
                    <div className="flex items-center justify-between p-4">
                      <p className="text-xs text-[#a7a7a7] uppercase tracking-wider">
                        Up Next ({queue.upNext.length})
                      </p>
                      <button
                        onClick={clearUpNext}
                        className="text-xs text-[#a7a7a7] hover:text-white transition-colors"
                      >
                        Clear
                      </button>
                    </div>
                    <ul>
                      {queue.upNext.map((item) => {
                        const upNextContent = (
                          <div className="flex items-center gap-3">
                            <div className="flex-1 min-w-0">
                              <p className="text-sm text-white truncate">
                                {item.song.title}
                              </p>
                              <p className="text-xs text-[#a7a7a7] truncate">
                                {item.song.artistName}
                              </p>
                            </div>
                            <span className="text-xs text-[#a7a7a7] font-mono">
                              {formatDuration(item.song.duration)}
                            </span>
                            {!isMobile && (
                              <button
                                onClick={(e) => {
                                  e.stopPropagation();
                                  removeFromUpNext(item.id);
                                }}
                                className="p-1 text-[#a7a7a7] hover:text-white transition-colors"
                                aria-label="Remove from queue"
                              >
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                              </button>
                            )}
                          </div>
                        );

                        // Use swipeable wrapper on mobile
                        if (isMobile) {
                          return (
                            <SwipeableQueueItem
                              key={item.id}
                              onDelete={() => removeFromUpNext(item.id)}
                              className="px-4 py-2"
                            >
                              {upNextContent}
                            </SwipeableQueueItem>
                          );
                        }

                        return (
                          <li
                            key={item.id}
                            className="px-4 py-2 hover:bg-[#282828] transition-colors"
                          >
                            {upNextContent}
                          </li>
                        );
                      })}
                    </ul>
                  </div>
                )}
              </>
            )}
          </div>
        </div>
      </>
    );
  }

  if (isMetro) {
    // Metro/Time Machine style - Queue slides from right
    return (
      <>
        {/* Backdrop */}
        <div
          className={`fixed z-40 bg-black/30 ${
            isMobile ? 'inset-0' : 'top-[60px] right-0 bottom-0 left-[280px]'
          }`}
          onClick={toggleQueue}
        />

        {/* Drawer - Full screen on mobile */}
        <div className={`fixed z-50 flex flex-col bg-white shadow-lg ${
          isMobile
            ? 'inset-0 safe-top safe-bottom'
            : 'right-0 top-[60px] bottom-0 w-96 border-l border-[#d4d0c8]'
        }`}>
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-base font-semibold text-[#1a1a1a]">Queue</h2>
            <div className="flex items-center gap-2">
              {(hasAlbum || hasUpNext) && (
                <button
                  onClick={clearQueue}
                  className="text-xs text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
                >
                  Clear All
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

          {/* Content */}
          <div className="flex-1 overflow-y-auto prevent-overscroll">
            {!hasAlbum && !hasUpNext ? (
              <div className="flex flex-col items-center justify-center h-full text-[#6b6b6b]">
                <svg className="w-12 h-12 mb-4 text-[#d4d0c8]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
                </svg>
                <p className="font-display text-sm font-semibold">Queue Empty</p>
                <p className="text-xs mt-1">Play an album to get started</p>
              </div>
            ) : (
              <>
                {/* Album Section */}
                {hasAlbum && queue.album && (
                  <div className="border-b border-[#d4d0c8]">
                    {/* Album Header */}
                    <div className="p-4 bg-[#f8f6f1]">
                      <div className="flex items-center gap-3">
                        {queue.album.coverArt ? (
                          <img
                            src={queue.album.coverArt}
                            alt={queue.album.name}
                            className="w-12 h-12 object-cover"
                          />
                        ) : (
                          <div className="w-12 h-12 bg-[#e8e4dc] flex items-center justify-center">
                            <svg className="w-6 h-6 text-[#d4d0c8]" viewBox="0 0 24 24" fill="currentColor">
                              <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                              <circle cx="12" cy="12" r="3" fill="currentColor"/>
                            </svg>
                          </div>
                        )}
                        <div className="flex-1 min-w-0">
                          <p className="font-display text-sm font-semibold text-[#1a1a1a] truncate">
                            {queue.album.name}
                          </p>
                          <p className="text-xs text-[#6b6b6b] truncate">
                            {queue.album.artistName}
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* Track List */}
                    <div className="text-[0.65rem] uppercase tracking-[0.1em] text-[#6b6b6b] px-4 py-2 border-b border-[#d4d0c8]">
                      Tracklist ({totalTracks} tracks)
                    </div>
                    <ul className="divide-y divide-[#d4d0c8]">
                      {queue.tracks.map((track, index) => {
                        const song = getSelectedSong(track);
                        const isCurrentTrack = queue.currentTrackIndex === index;
                        const hasVersions = track.availableVersions.length > 1;

                        return (
                          <li
                            key={track.trackId}
                            className={`
                              px-4 py-3 cursor-pointer transition-all
                              ${isCurrentTrack
                                ? 'bg-[#ffecd9]'
                                : 'hover:bg-[#f8f6f1]'
                              }
                            `}
                            onClick={() => playFromQueue(index)}
                          >
                            <div className="flex items-center gap-3">
                              <div className={`
                                w-6 text-xs font-display font-semibold
                                ${isCurrentTrack ? 'text-[#e85d04]' : 'text-[#6b6b6b]'}
                              `}>
                                {isCurrentTrack ? (
                                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                                  </svg>
                                ) : (
                                  String(index + 1).padStart(2, '0')
                                )}
                              </div>
                              <div className="flex-1 min-w-0">
                                <p className={`text-sm font-display truncate ${
                                  isCurrentTrack ? 'text-[#e85d04] font-semibold' : 'text-[#1a1a1a]'
                                }`}>
                                  {track.title}
                                </p>
                                {song && (
                                  <p className="text-[10px] text-[#6b6b6b] truncate">
                                    {song.showDate} {song.showVenue && `• ${song.showVenue}`}
                                  </p>
                                )}
                              </div>
                              <div className="flex items-center gap-2">
                                {song?.avgRating && (
                                  <span className="text-[10px] text-[#e85d04]">
                                    ★{song.avgRating.toFixed(1)}
                                  </span>
                                )}
                                {song && (
                                  <span className="text-xs text-[#6b6b6b] font-mono">
                                    {formatDuration(song.duration)}
                                  </span>
                                )}
                                {hasVersions && (
                                  <select
                                    onClick={(e) => e.stopPropagation()}
                                    value={track.selectedVersionId}
                                    onChange={(e) => {
                                      e.stopPropagation();
                                      selectVersion(index, e.target.value);
                                    }}
                                    className="text-[10px] bg-white border border-[#d4d0c8] rounded px-1 py-0.5 text-[#1a1a1a] max-w-[80px]"
                                    title="Select version"
                                  >
                                    {track.availableVersions.map((v) => (
                                      <option key={v.id} value={v.id}>
                                        {v.showDate?.split('-')[0] || 'Unknown'}
                                        {v.avgRating ? ` ★${v.avgRating.toFixed(1)}` : ''}
                                      </option>
                                    ))}
                                  </select>
                                )}
                              </div>
                            </div>
                          </li>
                        );
                      })}
                    </ul>
                  </div>
                )}

                {/* Up Next Section */}
                {hasUpNext && (
                  <div>
                    <div className="flex items-center justify-between px-4 py-2 border-b border-[#d4d0c8] bg-[#f8f6f1]">
                      <span className="text-[0.65rem] uppercase tracking-[0.1em] text-[#6b6b6b]">
                        Up Next ({queue.upNext.length})
                      </span>
                      <button
                        onClick={clearUpNext}
                        className="text-[10px] text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
                      >
                        Clear
                      </button>
                    </div>
                    <ul className="divide-y divide-[#d4d0c8]">
                      {queue.upNext.map((item) => (
                        <li
                          key={item.id}
                          className="px-4 py-3 hover:bg-[#f8f6f1] transition-all"
                        >
                          <div className="flex items-center gap-3">
                            <div className="flex-1 min-w-0">
                              <p className="text-sm font-display text-[#1a1a1a] truncate">
                                {item.song.title}
                              </p>
                              <p className="text-[10px] text-[#6b6b6b] truncate">
                                {item.song.artistName} • {item.song.showDate}
                              </p>
                            </div>
                            <span className="text-xs text-[#6b6b6b] font-mono">
                              {formatDuration(item.song.duration)}
                            </span>
                            <button
                              onClick={() => removeFromUpNext(item.id)}
                              className="p-1 text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
                            >
                              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                              </svg>
                            </button>
                          </div>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </>
            )}
          </div>

          {/* Footer - Progress */}
          {hasAlbum && currentTrack && (
            <div className="p-4 border-t border-[#d4d0c8] bg-[#f8f6f1]">
              <p className="text-xs text-[#6b6b6b] text-center">
                Track {queue.currentTrackIndex + 1} of {totalTracks}
                {hasUpNext && ` • ${queue.upNext.length} up next`}
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
        className={`fixed z-40 bg-black/60 backdrop-blur-sm ${
          isMobile ? 'inset-0' : 'top-0 right-0 bottom-0 left-[280px]'
        }`}
        onClick={toggleQueue}
      />

      {/* Drawer - Full screen on mobile */}
      <div className={`fixed z-50 flex flex-col bg-dark-800/95 backdrop-blur-sm ${
        isMobile
          ? 'inset-0 safe-top safe-bottom'
          : 'right-0 top-0 bottom-0 w-96 border-l border-neon-cyan/20'
      }`}>
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-neon-cyan/20">
          <h2 className="font-display text-sm uppercase tracking-[0.2em] text-white">Queue</h2>
          <div className="flex items-center gap-2">
            {(hasAlbum || hasUpNext) && (
              <button
                onClick={clearQueue}
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

        {/* Content */}
        <div className="flex-1 overflow-y-auto scrollbar-thin">
          {!hasAlbum && !hasUpNext ? (
            <div className="flex flex-col items-center justify-center h-full text-text-dim">
              <svg className="w-12 h-12 mb-4 text-neon-cyan/30" fill="currentColor" viewBox="0 0 24 24">
                <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
              </svg>
              <p className="font-display text-sm uppercase tracking-wider">Queue Empty</p>
              <p className="text-xs mt-1">Play an album to get started</p>
            </div>
          ) : (
            <>
              {/* Album Section */}
              {hasAlbum && queue.album && (
                <div className="border-b border-neon-cyan/20">
                  {/* Album Header */}
                  <div className="p-4 bg-black/30">
                    <div className="flex items-center gap-3">
                      {queue.album.coverArt ? (
                        <img
                          src={queue.album.coverArt}
                          alt={queue.album.name}
                          className="w-12 h-12 object-cover album-frame"
                        />
                      ) : (
                        <div className="w-12 h-12 bg-dark-700 flex items-center justify-center album-frame">
                          <svg className="w-6 h-6 text-neon-cyan/40" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor"/>
                          </svg>
                        </div>
                      )}
                      <div className="flex-1 min-w-0">
                        <p className="font-display text-sm text-white truncate">
                          {queue.album.name}
                        </p>
                        <p className="text-[10px] text-text-dim truncate uppercase tracking-wider">
                          {queue.album.artistName}
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Track List */}
                  <div className="text-[0.55rem] uppercase tracking-[0.15em] text-neon-cyan px-4 py-2 border-b border-neon-cyan/10">
                    // Tracklist
                  </div>
                  <ul className="divide-y divide-white/5">
                    {queue.tracks.map((track, index) => {
                      const song = getSelectedSong(track);
                      const isCurrentTrack = queue.currentTrackIndex === index;
                      const hasVersions = track.availableVersions.length > 1;

                      return (
                        <li
                          key={track.trackId}
                          className={`
                            px-4 py-3 cursor-pointer transition-all
                            ${isCurrentTrack
                              ? 'bg-neon-cyan/10 border-l-2 border-neon-cyan'
                              : 'hover:bg-white/5 border-l-2 border-transparent'
                            }
                          `}
                          onClick={() => playFromQueue(index)}
                        >
                          <div className="flex items-center gap-3">
                            <div className={`
                              w-6 text-xs font-display
                              ${isCurrentTrack ? 'text-neon-cyan' : 'text-text-dim'}
                            `}>
                              {isCurrentTrack ? (
                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                                </svg>
                              ) : (
                                String(index + 1).padStart(2, '0')
                              )}
                            </div>
                            <div className="flex-1 min-w-0">
                              <p className={`text-sm font-display truncate ${
                                isCurrentTrack ? 'text-neon-cyan' : 'text-white'
                              }`}>
                                {track.title}
                              </p>
                              {song && (
                                <p className="text-[10px] text-text-dim truncate">
                                  {song.showDate} {song.showVenue && `• ${song.showVenue}`}
                                </p>
                              )}
                            </div>
                            <div className="flex items-center gap-2">
                              {song?.avgRating && (
                                <span className="text-[10px] text-neon-orange">
                                  ★{song.avgRating.toFixed(1)}
                                </span>
                              )}
                              {song && (
                                <span className="text-[10px] text-text-dim font-mono">
                                  {formatDuration(song.duration)}
                                </span>
                              )}
                              {hasVersions && (
                                <select
                                  onClick={(e) => e.stopPropagation()}
                                  value={track.selectedVersionId}
                                  onChange={(e) => {
                                    e.stopPropagation();
                                    selectVersion(index, e.target.value);
                                  }}
                                  className="text-[10px] bg-dark-700 border border-neon-cyan/30 rounded px-1 py-0.5 text-white max-w-[80px] focus:border-neon-cyan focus:outline-none"
                                  title="Select version"
                                >
                                  {track.availableVersions.map((v) => (
                                    <option key={v.id} value={v.id}>
                                      {v.showDate?.split('-')[0] || '?'}
                                      {v.avgRating ? ` ★${v.avgRating.toFixed(1)}` : ''}
                                    </option>
                                  ))}
                                </select>
                              )}
                            </div>
                          </div>
                        </li>
                      );
                    })}
                  </ul>
                </div>
              )}

              {/* Up Next Section */}
              {hasUpNext && (
                <div>
                  <div className="flex items-center justify-between px-4 py-2 border-b border-neon-cyan/10 bg-black/30">
                    <span className="text-[0.55rem] uppercase tracking-[0.15em] text-neon-pink">
                      // Up Next ({queue.upNext.length})
                    </span>
                    <button
                      onClick={clearUpNext}
                      className="text-[10px] text-text-dim hover:text-neon-pink transition-colors uppercase tracking-wider"
                    >
                      Clear
                    </button>
                  </div>
                  <ul className="divide-y divide-white/5">
                    {queue.upNext.map((item) => (
                      <li
                        key={item.id}
                        className="px-4 py-3 hover:bg-white/5 transition-all"
                      >
                        <div className="flex items-center gap-3">
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-display text-white truncate">
                              {item.song.title}
                            </p>
                            <p className="text-[10px] text-text-dim truncate uppercase tracking-wider">
                              {item.song.artistName} • {item.song.showDate}
                            </p>
                          </div>
                          <span className="text-[10px] text-text-dim font-mono">
                            {formatDuration(item.song.duration)}
                          </span>
                          <button
                            onClick={() => removeFromUpNext(item.id)}
                            className="p-1 text-text-dim hover:text-neon-pink transition-colors"
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </>
          )}
        </div>

        {/* Footer */}
        {hasAlbum && currentTrack && (
          <div className="p-4 border-t border-neon-cyan/20">
            <p className="text-[10px] text-text-dim text-center uppercase tracking-[0.2em]">
              Track {queue.currentTrackIndex + 1} of {totalTracks}
              {hasUpNext && ` • ${queue.upNext.length} up next`}
            </p>
          </div>
        )}
      </div>
    </>
  );
}
