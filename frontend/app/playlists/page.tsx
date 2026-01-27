'use client';

import { useState } from 'react';
import { usePlaylists } from '@/context/PlaylistContext';
import Link from 'next/link';

export default function PlaylistsPage() {
  const { playlists, createPlaylist, deletePlaylist } = usePlaylists();
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newPlaylistName, setNewPlaylistName] = useState('');
  const [newPlaylistDescription, setNewPlaylistDescription] = useState('');

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPlaylistName.trim()) return;

    createPlaylist(newPlaylistName.trim(), newPlaylistDescription.trim() || undefined);
    setNewPlaylistName('');
    setNewPlaylistDescription('');
    setShowCreateForm(false);
  };

  return (
    <div className="min-h-screen bg-[#121212] pb-[140px] md:pb-[90px] safe-top">
      {/* Header */}
      <div className="p-6 md:p-8">
        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">Your Playlists</h1>

        <button
          onClick={() => setShowCreateForm(!showCreateForm)}
          className="px-6 py-3 rounded-full bg-[#1DB954] text-black font-medium hover:bg-[#1ed760] transition-colors"
        >
          Create Playlist
        </button>
      </div>

      {/* Create form */}
      {showCreateForm && (
        <div className="px-6 md:px-8 mb-8">
          <form onSubmit={handleCreate} className="bg-[#282828] rounded-lg p-6 max-w-md">
            <h3 className="text-white font-bold text-lg mb-4">Create new playlist</h3>
            <input
              type="text"
              value={newPlaylistName}
              onChange={(e) => setNewPlaylistName(e.target.value)}
              placeholder="Playlist name"
              autoFocus
              className="w-full bg-[#121212] text-white placeholder-[#b3b3b3] rounded px-4 py-3 mb-3 focus:outline-none focus:ring-2 focus:ring-[#1DB954]"
            />
            <textarea
              value={newPlaylistDescription}
              onChange={(e) => setNewPlaylistDescription(e.target.value)}
              placeholder="Description (optional)"
              rows={3}
              className="w-full bg-[#121212] text-white placeholder-[#b3b3b3] rounded px-4 py-3 mb-4 focus:outline-none focus:ring-2 focus:ring-[#1DB954] resize-none"
            />
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => {
                  setShowCreateForm(false);
                  setNewPlaylistName('');
                  setNewPlaylistDescription('');
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
        </div>
      )}

      {/* Playlists grid */}
      {playlists.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-16 px-4">
          <svg className="w-16 h-16 text-[#535353] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <h3 className="text-white font-bold text-lg mb-2">No playlists yet</h3>
          <p className="text-[#b3b3b3] text-sm text-center mb-4">
            Create your first playlist to organize your favorite songs
          </p>
          <button
            onClick={() => setShowCreateForm(true)}
            className="px-6 py-3 rounded-full bg-[#1DB954] text-black font-medium hover:bg-[#1ed760] transition-colors"
          >
            Create Playlist
          </button>
        </div>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 px-6 md:px-8">
          {playlists.map((playlist) => (
            <Link
              key={playlist.id}
              href={`/playlists/${playlist.id}`}
              className="flex flex-col gap-3 p-4 rounded-lg hover:bg-white/10 transition-colors group"
            >
              {/* Cover art */}
              <div className="w-full aspect-square rounded bg-[#282828] overflow-hidden relative">
                {playlist.coverArt ? (
                  <img src={playlist.coverArt} alt={playlist.name} className="w-full h-full object-cover" />
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <svg className="w-12 h-12 text-[#535353]" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                    </svg>
                  </div>
                )}

                {/* Play button overlay */}
                <button
                  onClick={(e) => {
                    e.preventDefault();
                    // TODO: Play playlist
                  }}
                  className="absolute bottom-2 right-2 w-12 h-12 rounded-full bg-[#1DB954] text-black flex items-center justify-center opacity-0 group-hover:opacity-100 hover:scale-105 transition-all shadow-lg btn-touch"
                  aria-label={`Play ${playlist.name}`}
                >
                  <svg className="w-6 h-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                  </svg>
                </button>
              </div>

              {/* Info */}
              <div>
                <p className="text-white font-medium truncate">{playlist.name}</p>
                <p className="text-[#b3b3b3] text-sm">{playlist.songs.length} songs</p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
