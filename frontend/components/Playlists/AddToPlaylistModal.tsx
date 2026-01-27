'use client';

import { useState } from 'react';
import { usePlaylists } from '@/context/PlaylistContext';
import { Song } from '@/lib/types';

interface AddToPlaylistModalProps {
  isOpen: boolean;
  onClose: () => void;
  song: Song | null;
}

export function AddToPlaylistModal({ isOpen, onClose, song }: AddToPlaylistModalProps) {
  const { playlists, createPlaylist, addToPlaylist } = usePlaylists();
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newPlaylistName, setNewPlaylistName] = useState('');

  if (!isOpen || !song) return null;

  const handleAddToPlaylist = (playlistId: string) => {
    addToPlaylist(playlistId, song);
    onClose();
  };

  const handleCreateAndAdd = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPlaylistName.trim()) return;

    const playlist = createPlaylist(newPlaylistName.trim());
    addToPlaylist(playlist.id, song);
    setNewPlaylistName('');
    setShowCreateForm(false);
    onClose();
  };

  return (
    <>
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/80 z-[9998] animate-fade-in"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="fixed inset-0 z-[9999] flex items-center justify-center p-4 pointer-events-none">
        <div
          className="bg-[#282828] rounded-lg w-full max-w-md max-h-[80vh] overflow-hidden pointer-events-auto animate-scale-in flex flex-col"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Header */}
          <div className="p-4 border-b border-white/10">
            <div className="flex items-center justify-between mb-2">
              <h2 className="text-white font-bold text-lg">Add to Playlist</h2>
              <button
                onClick={onClose}
                className="p-2 -mr-2 text-[#b3b3b3] hover:text-white transition-colors rounded-full hover:bg-white/10"
                aria-label="Close"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <p className="text-[#b3b3b3] text-sm truncate">{song.title} • {song.artistName}</p>
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto">
            {showCreateForm ? (
              <form onSubmit={handleCreateAndAdd} className="p-4">
                <input
                  type="text"
                  value={newPlaylistName}
                  onChange={(e) => setNewPlaylistName(e.target.value)}
                  placeholder="Playlist name"
                  autoFocus
                  className="w-full bg-[#121212] text-white placeholder-[#b3b3b3] rounded px-4 py-3 mb-3 focus:outline-none focus:ring-2 focus:ring-[#1DB954]"
                />
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => {
                      setShowCreateForm(false);
                      setNewPlaylistName('');
                    }}
                    className="flex-1 px-4 py-2 rounded-full border border-white/20 text-white hover:bg-white/10 transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={!newPlaylistName.trim()}
                    className="flex-1 px-4 py-2 rounded-full bg-[#1DB954] text-black font-medium hover:bg-[#1ed760] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Create
                  </button>
                </div>
              </form>
            ) : (
              <>
                {/* Create new playlist button */}
                <button
                  onClick={() => setShowCreateForm(true)}
                  className="w-full flex items-center gap-3 p-4 hover:bg-white/10 transition-colors text-left"
                >
                  <div className="w-10 h-10 rounded bg-[#121212] flex items-center justify-center flex-shrink-0">
                    <svg className="w-6 h-6 text-[#b3b3b3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                  </div>
                  <span className="text-white font-medium">Create new playlist</span>
                </button>

                {/* Divider */}
                {playlists.length > 0 && (
                  <div className="h-px bg-white/10 mx-4" />
                )}

                {/* Playlist list */}
                <div>
                  {playlists.length === 0 ? (
                    <div className="p-8 text-center">
                      <p className="text-[#b3b3b3] text-sm">No playlists yet</p>
                    </div>
                  ) : (
                    playlists.map((playlist) => (
                      <button
                        key={playlist.id}
                        onClick={() => handleAddToPlaylist(playlist.id)}
                        className="w-full flex items-center gap-3 p-4 hover:bg-white/10 transition-colors text-left"
                      >
                        {/* Cover art */}
                        <div className="w-10 h-10 rounded bg-[#282828] flex-shrink-0 overflow-hidden">
                          {playlist.coverArt ? (
                            <img src={playlist.coverArt} alt={playlist.name} className="w-full h-full object-cover" />
                          ) : (
                            <div className="w-full h-full flex items-center justify-center">
                              <svg className="w-5 h-5 text-[#535353]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                              </svg>
                            </div>
                          )}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-white font-medium truncate">{playlist.name}</p>
                          <p className="text-[#b3b3b3] text-sm">{playlist.songs.length} songs</p>
                        </div>
                      </button>
                    ))
                  )}
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
