'use client';

// PlaylistContext - Manage user playlists
// Uses localStorage for persistence (Magento integration can be added later)

import React, { createContext, useContext, useState, useCallback, useEffect } from 'react';
import { Song } from '@/lib/types';

export interface Playlist {
  id: string;
  name: string;
  description?: string;
  songs: Song[];
  coverArt?: string;
  createdAt: string;
  updatedAt: string;
}

interface PlaylistContextType {
  playlists: Playlist[];
  isLoading: boolean;
  createPlaylist: (name: string, description?: string) => Playlist;
  deletePlaylist: (playlistId: string) => void;
  addToPlaylist: (playlistId: string, song: Song) => void;
  removeFromPlaylist: (playlistId: string, songId: string) => void;
  updatePlaylist: (playlistId: string, updates: Partial<Pick<Playlist, 'name' | 'description'>>) => void;
  getPlaylist: (playlistId: string) => Playlist | undefined;
}

const PlaylistContext = createContext<PlaylistContextType | null>(null);

const PLAYLISTS_STORAGE_KEY = 'jamify_playlists';

// Generate unique playlist ID
let playlistCounter = Date.now();
const generatePlaylistId = () => `playlist-${++playlistCounter}`;

export function PlaylistProvider({ children }: { children: React.ReactNode }) {
  const [playlists, setPlaylists] = useState<Playlist[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  // Load playlists from localStorage on mount
  useEffect(() => {
    const stored = localStorage.getItem(PLAYLISTS_STORAGE_KEY);
    if (stored) {
      try {
        const parsed = JSON.parse(stored);
        setPlaylists(parsed);
      } catch (error) {
        console.error('Failed to load playlists from localStorage:', error);
      }
    }
    setIsLoading(false);
  }, []);

  // Save playlists to localStorage whenever they change
  useEffect(() => {
    if (!isLoading) {
      localStorage.setItem(PLAYLISTS_STORAGE_KEY, JSON.stringify(playlists));
    }
  }, [playlists, isLoading]);

  const createPlaylist = useCallback((name: string, description?: string): Playlist => {
    const newPlaylist: Playlist = {
      id: generatePlaylistId(),
      name,
      description,
      songs: [],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };

    setPlaylists(prev => [...prev, newPlaylist]);
    return newPlaylist;
  }, []);

  const deletePlaylist = useCallback((playlistId: string) => {
    setPlaylists(prev => prev.filter(p => p.id !== playlistId));
  }, []);

  const addToPlaylist = useCallback((playlistId: string, song: Song) => {
    setPlaylists(prev => prev.map(playlist => {
      if (playlist.id !== playlistId) return playlist;

      // Check if song already exists
      if (playlist.songs.some(s => s.id === song.id)) {
        return playlist;
      }

      return {
        ...playlist,
        songs: [...playlist.songs, song],
        updatedAt: new Date().toISOString(),
        // Use first song's album art as playlist cover if not set
        coverArt: playlist.coverArt || song.albumArt,
      };
    }));
  }, []);

  const removeFromPlaylist = useCallback((playlistId: string, songId: string) => {
    setPlaylists(prev => prev.map(playlist => {
      if (playlist.id !== playlistId) return playlist;

      return {
        ...playlist,
        songs: playlist.songs.filter(s => s.id !== songId),
        updatedAt: new Date().toISOString(),
      };
    }));
  }, []);

  const updatePlaylist = useCallback((
    playlistId: string,
    updates: Partial<Pick<Playlist, 'name' | 'description'>>
  ) => {
    setPlaylists(prev => prev.map(playlist => {
      if (playlist.id !== playlistId) return playlist;

      return {
        ...playlist,
        ...updates,
        updatedAt: new Date().toISOString(),
      };
    }));
  }, []);

  const getPlaylist = useCallback((playlistId: string) => {
    return playlists.find(p => p.id === playlistId);
  }, [playlists]);

  return (
    <PlaylistContext.Provider
      value={{
        playlists,
        isLoading,
        createPlaylist,
        deletePlaylist,
        addToPlaylist,
        removeFromPlaylist,
        updatePlaylist,
        getPlaylist,
      }}
    >
      {children}
    </PlaylistContext.Provider>
  );
}

export function usePlaylists() {
  const context = useContext(PlaylistContext);
  if (!context) {
    throw new Error('usePlaylists must be used within a PlaylistProvider');
  }
  return context;
}
