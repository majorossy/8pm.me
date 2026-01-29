'use client';

// Queue drawer - displays album tracks + up-next (Jamify/Spotify theme)

import { useState } from 'react';
import { usePlayer } from '@/context/PlayerContext';
import { useQueue } from '@/context/QueueContext';
import { usePlaylists } from '@/context/PlaylistContext';
import { useMobileUI } from '@/context/MobileUIContext';
import { formatDuration } from '@/lib/api';
import { getSelectedSong } from '@/lib/queueTypes';
import SwipeableQueueItem from '@/components/SwipeableQueueItem';

export default function Queue() {
  const { isMobile } = useMobileUI();
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

  const { createPlaylist, addToPlaylist } = usePlaylists();

  const [showSaveModal, setShowSaveModal] = useState(false);
  const [playlistName, setPlaylistName] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);

  if (!isQueueOpen) return null;

  const handleSaveQueue = async () => {
    if (!playlistName.trim()) return;

    setIsSaving(true);

    try {
      // Create the playlist
      const newPlaylist = createPlaylist(playlistName.trim(), 'Saved from queue');

      // Collect all songs from queue
      const allSongs = [
        ...queue.tracks.map(track => getSelectedSong(track)).filter(Boolean),
        ...queue.upNext.map(item => item.song)
      ];

      // Add all songs to the new playlist
      allSongs.forEach(song => {
        if (song) {
          addToPlaylist(newPlaylist.id, song);
        }
      });

      // Show success message
      setSaveSuccess(true);
      setTimeout(() => {
        setShowSaveModal(false);
        setPlaylistName('');
        setSaveSuccess(false);
        setIsSaving(false);
        toggleQueue(); // Close queue drawer
      }, 1500);
    } catch (error) {
      console.error('Failed to save playlist:', error);
      setIsSaving(false);
    }
  };

  const totalSongsInQueue = queue.tracks.length + queue.upNext.length;

  // Jamify/Spotify style - slides from right, positioned above bottom player
  return (
    <>
      {/* Backdrop */}
      <div
        className={`fixed z-[60] bg-black/60 ${
          isMobile ? 'inset-0' : 'inset-0 bottom-[90px]'
        }`}
        onClick={toggleQueue}
        aria-hidden="true"
      />

      {/* Drawer - Full screen on mobile */}
      <aside
        className={`fixed z-[70] flex flex-col ${
          isMobile
            ? 'inset-0 bg-gradient-to-b from-[#3a3632] to-[#1c1a17] safe-top safe-bottom'
            : 'left-0 top-0 bottom-[90px] w-96 bg-[#1c1a17] border-r border-[#2d2a26]'
        }`}
        role="dialog"
        aria-modal="true"
        aria-label="Queue"
      >
          {/* Mobile drag indicator */}
          {isMobile && (
            <div className="flex justify-center pt-3 pb-1">
              <div className="w-10 h-1 bg-white/30 rounded-full" />
            </div>
          )}

          {/* Header */}
          <div className={`flex items-center justify-between px-4 ${isMobile ? 'py-3' : 'p-4 border-b border-[#2d2a26]'}`}>
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
                      className="text-xs text-[#8a8478] hover:text-white transition-colors"
                      aria-label="Clear entire queue"
                    >
                      Clear all
                    </button>
                  )}
                  <button
                    onClick={toggleQueue}
                    className="p-2 text-[#8a8478] hover:text-white transition-colors"
                    aria-label="Close queue"
                  >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </>
            )}
          </div>

          {/* Save as Playlist Button */}
          {(hasAlbum || hasUpNext) && totalSongsInQueue > 0 && (
            <div className="px-4 py-3 border-b border-[#2d2a26]">
              <button
                onClick={() => setShowSaveModal(true)}
                className="w-full py-2 px-4 bg-[#d4a060] hover:bg-[#c08a40] text-white text-sm font-semibold rounded-full transition-colors"
                aria-label={`Save queue with ${totalSongsInQueue} songs as a new playlist`}
              >
                Save as Playlist
              </button>
            </div>
          )}

          {/* Content */}
          <div className="flex-1 overflow-y-auto prevent-overscroll" role="region" aria-label="Queue tracks">
            {!hasAlbum && !hasUpNext ? (
              <div className="flex flex-col items-center justify-center h-full text-[#8a8478]">
                <svg className="w-12 h-12 mb-4 text-[#3a3632]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
                </svg>
                <p className="font-semibold">Queue is empty</p>
                <p className="text-sm mt-1">Play an album to get started</p>
              </div>
            ) : (
              <>
                {/* Now Playing */}
                {currentTrack && (
                  <div className="p-4 border-b border-[#2d2a26]">
                    <p className="text-xs text-[#8a8478] uppercase tracking-wider mb-3">Now Playing</p>
                    <div className="flex items-center gap-3">
                      {queue.album?.coverArt ? (
                        <img
                          src={queue.album.coverArt}
                          alt={queue.album.name}
                          className="w-12 h-12 object-cover rounded"
                        />
                      ) : (
                        <div className="w-12 h-12 bg-[#2d2a26] rounded flex items-center justify-center">
                          <svg className="w-6 h-6 text-[#3a3632]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                          </svg>
                        </div>
                      )}
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-[#d4a060] truncate">
                          {getSelectedSong(currentTrack)?.title || currentTrack.title}
                        </p>
                        <p className="text-xs text-[#8a8478] truncate">
                          {queue.album?.artistName}
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Album Tracks */}
                {hasAlbum && queue.album && (
                  <div className="border-b border-[#2d2a26]">
                    <div className="p-4">
                      <p className="text-xs text-[#8a8478] uppercase tracking-wider">
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
                            <span className={`w-5 text-sm ${isCurrentlyPlaying ? 'text-[#d4a060]' : 'text-[#8a8478]'}`}>
                              {isCurrentlyPlaying && isPlaying ? (
                                <span className="jamify-eq-bars">
                                  <span /><span /><span />
                                </span>
                              ) : (
                                actualIndex + 1
                              )}
                            </span>
                            <div className="flex-1 min-w-0">
                              <p className={`text-sm truncate ${isCurrentlyPlaying ? 'text-[#d4a060]' : 'text-white'}`}>
                                {track.title}
                              </p>
                              <p className="text-xs text-[#8a8478] truncate">
                                {queue.album?.artistName}
                              </p>
                            </div>
                            {song && (
                              <span className="text-xs text-[#8a8478] font-mono">
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
                                isCurrentlyPlaying ? 'bg-[#2d2a26]' : ''
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
                            className={`px-4 py-2 hover:bg-[#2d2a26] cursor-pointer transition-colors ${
                              isCurrentlyPlaying ? 'bg-[#2d2a26]' : ''
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
                      <p className="text-xs text-[#8a8478] uppercase tracking-wider">
                        Up Next ({queue.upNext.length})
                      </p>
                      <button
                        onClick={clearUpNext}
                        className="text-xs text-[#8a8478] hover:text-white transition-colors"
                        aria-label={`Clear up next queue (${queue.upNext.length} songs)`}
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
                              <p className="text-xs text-[#8a8478] truncate">
                                {item.song.artistName}
                              </p>
                            </div>
                            <span className="text-xs text-[#8a8478] font-mono">
                              {formatDuration(item.song.duration)}
                            </span>
                            {!isMobile && (
                              <button
                                onClick={(e) => {
                                  e.stopPropagation();
                                  removeFromUpNext(item.id);
                                }}
                                className="p-1 text-[#8a8478] hover:text-white transition-colors"
                                aria-label={`Remove ${item.song.title} from queue`}
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
                            className="px-4 py-2 hover:bg-[#2d2a26] transition-colors"
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

      {/* Save Playlist Modal */}
      {showSaveModal && (
        <>
          {/* Modal Backdrop */}
          <div
            className="fixed inset-0 z-[60] bg-black/80"
            onClick={() => !isSaving && setShowSaveModal(false)}
          />

          {/* Modal Content */}
          <div className="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div className="bg-[#2d2a26] rounded-lg max-w-md w-full p-6">
              {saveSuccess ? (
                <div className="text-center">
                  <div className="w-16 h-16 bg-[#d4a060] rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <h3 className="text-xl font-bold text-white mb-2">Playlist Created!</h3>
                  <p className="text-[#8a8478] text-sm">
                    {totalSongsInQueue} {totalSongsInQueue === 1 ? 'song' : 'songs'} saved to "{playlistName}"
                  </p>
                </div>
              ) : (
                <>
                  <h3 className="text-xl font-bold text-white mb-4">Save Queue as Playlist</h3>
                  <p className="text-[#8a8478] text-sm mb-4">
                    {totalSongsInQueue} {totalSongsInQueue === 1 ? 'song' : 'songs'} will be saved
                  </p>
                  <input
                    type="text"
                    value={playlistName}
                    onChange={(e) => setPlaylistName(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' && playlistName.trim()) {
                        handleSaveQueue();
                      }
                    }}
                    placeholder="Playlist name"
                    className="w-full px-4 py-3 bg-[#3a3632] text-white rounded border border-[#3a3632] focus:border-[#d4a060] focus:outline-none mb-6"
                    autoFocus
                    disabled={isSaving}
                  />
                  <div className="flex gap-3">
                    <button
                      onClick={() => {
                        setShowSaveModal(false);
                        setPlaylistName('');
                      }}
                      disabled={isSaving}
                      className="flex-1 py-3 px-4 bg-transparent border border-[#3a3632] text-white text-sm font-semibold rounded-full hover:border-white transition-colors disabled:opacity-50"
                    >
                      Cancel
                    </button>
                    <button
                      onClick={handleSaveQueue}
                      disabled={!playlistName.trim() || isSaving}
                      className="flex-1 py-3 px-4 bg-[#d4a060] hover:bg-[#c08a40] text-white text-sm font-semibold rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {isSaving ? 'Saving...' : 'Save'}
                    </button>
                  </div>
                </>
              )}
            </div>
          </div>
        </>
      )}
    </aside>
  </>
  );
}
