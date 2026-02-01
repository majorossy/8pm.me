'use client';

// PlaylistContext - Manage user playlists
// Uses localStorage for persistence with Supabase sync when authenticated

import React, { createContext, useContext, useState, useCallback, useEffect, useRef } from 'react';
import { Song } from '@/lib/types';
import { useAuth } from '@/context/AuthContext';
import {
  fetchUserPlaylists,
  syncPlaylistToServer,
  deletePlaylistFromServer,
  subscribeToPlaylistChanges,
  SyncStatus,
} from '@/lib/syncService';
import { isSupabaseConfigured } from '@/lib/supabase';
import { trackPlaylistCreate, trackPlaylistDelete, trackAddToPlaylist, trackRemoveFromPlaylist } from '@/lib/analytics';

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
  syncStatus: SyncStatus;
  createPlaylist: (name: string, description?: string) => Playlist;
  deletePlaylist: (playlistId: string) => void;
  addToPlaylist: (playlistId: string, song: Song) => void;
  removeFromPlaylist: (playlistId: string, songId: string) => void;
  updatePlaylist: (playlistId: string, updates: Partial<Pick<Playlist, 'name' | 'description'>>) => void;
  getPlaylist: (playlistId: string) => Playlist | undefined;
  forceSync: () => Promise<void>;
}

const PlaylistContext = createContext<PlaylistContextType | null>(null);

const PLAYLISTS_STORAGE_KEY = 'jamify_playlists';

// Generate unique playlist ID
let playlistCounter = Date.now();
const generatePlaylistId = () => `playlist-${++playlistCounter}`;

export function PlaylistProvider({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated } = useAuth();
  const [playlists, setPlaylists] = useState<Playlist[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [syncStatus, setSyncStatus] = useState<SyncStatus>('idle');
  const syncTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const hasFetchedFromServerRef = useRef(false);

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

  // Fetch from server when authenticated
  useEffect(() => {
    if (!isAuthenticated || !user || !isSupabaseConfigured() || hasFetchedFromServerRef.current) {
      return;
    }

    const fetchFromServer = async () => {
      setSyncStatus('syncing');
      try {
        const serverPlaylists = await fetchUserPlaylists(user.id);
        if (serverPlaylists.length > 0) {
          // Merge with local playlists (server takes precedence for conflicts)
          setPlaylists(prev => {
            const serverIds = new Set(serverPlaylists.map(p => p.id));
            const localOnly = prev.filter(p => !serverIds.has(p.id));
            return [...serverPlaylists, ...localOnly];
          });
        }
        setSyncStatus('synced');
        hasFetchedFromServerRef.current = true;
      } catch (error) {
        console.error('Failed to fetch playlists from server:', error);
        setSyncStatus('error');
      }
    };

    fetchFromServer();
  }, [isAuthenticated, user]);

  // Subscribe to realtime changes
  useEffect(() => {
    if (!isAuthenticated || !user || !isSupabaseConfigured()) {
      return;
    }

    const unsubscribe = subscribeToPlaylistChanges(user.id, (serverPlaylists) => {
      setPlaylists(serverPlaylists);
      setSyncStatus('synced');
    });

    return unsubscribe;
  }, [isAuthenticated, user]);

  // Debounced sync to server
  const syncPlaylistDebounced = useCallback((playlist: Playlist) => {
    if (!isAuthenticated || !user || !isSupabaseConfigured()) return;

    if (syncTimeoutRef.current) {
      clearTimeout(syncTimeoutRef.current);
    }

    setSyncStatus('syncing');
    syncTimeoutRef.current = setTimeout(async () => {
      try {
        await syncPlaylistToServer(user.id, playlist);
        setSyncStatus('synced');
      } catch (error) {
        console.error('Failed to sync playlist:', error);
        setSyncStatus('error');
      }
    }, 500);
  }, [isAuthenticated, user]);

  // Force sync all playlists
  const forceSync = useCallback(async () => {
    if (!isAuthenticated || !user || !isSupabaseConfigured()) return;

    setSyncStatus('syncing');
    try {
      for (const playlist of playlists) {
        await syncPlaylistToServer(user.id, playlist);
      }
      setSyncStatus('synced');
    } catch (error) {
      console.error('Failed to force sync playlists:', error);
      setSyncStatus('error');
    }
  }, [isAuthenticated, user, playlists]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (syncTimeoutRef.current) {
        clearTimeout(syncTimeoutRef.current);
      }
    };
  }, []);

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

    // Track analytics event
    trackPlaylistCreate(name);

    // Sync to server
    if (isAuthenticated && user && isSupabaseConfigured()) {
      syncPlaylistDebounced(newPlaylist);
    }

    return newPlaylist;
  }, [isAuthenticated, user, syncPlaylistDebounced]);

  const deletePlaylist = useCallback((playlistId: string) => {
    // Find playlist name for analytics before deleting
    const playlistToDelete = playlists.find(p => p.id === playlistId);
    if (playlistToDelete) {
      trackPlaylistDelete(playlistToDelete.name);
    }

    setPlaylists(prev => prev.filter(p => p.id !== playlistId));

    // Delete from server
    if (isAuthenticated && user && isSupabaseConfigured()) {
      deletePlaylistFromServer(playlistId).catch(error => {
        console.error('Failed to delete playlist from server:', error);
      });
    }
  }, [isAuthenticated, user, playlists]);

  const addToPlaylist = useCallback((playlistId: string, song: Song) => {
    let updatedPlaylist: Playlist | null = null;
    let playlistName: string | undefined;

    setPlaylists(prev => prev.map(playlist => {
      if (playlist.id !== playlistId) return playlist;

      // Check if song already exists
      if (playlist.songs.some(s => s.id === song.id)) {
        return playlist;
      }

      playlistName = playlist.name;
      updatedPlaylist = {
        ...playlist,
        songs: [...playlist.songs, song],
        updatedAt: new Date().toISOString(),
        // Use first song's album art as playlist cover if not set
        coverArt: playlist.coverArt || song.albumArt,
      };
      return updatedPlaylist;
    }));

    // Track analytics event (only if song was actually added)
    if (updatedPlaylist && playlistName) {
      trackAddToPlaylist(song, playlistName);
    }

    // Sync to server
    if (updatedPlaylist && isAuthenticated && user && isSupabaseConfigured()) {
      syncPlaylistDebounced(updatedPlaylist);
    }
  }, [isAuthenticated, user, syncPlaylistDebounced]);

  const removeFromPlaylist = useCallback((playlistId: string, songId: string) => {
    let updatedPlaylist: Playlist | null = null;
    let removedSong: Song | undefined;
    let playlistName: string | undefined;

    setPlaylists(prev => prev.map(playlist => {
      if (playlist.id !== playlistId) return playlist;

      playlistName = playlist.name;
      removedSong = playlist.songs.find(s => s.id === songId);
      updatedPlaylist = {
        ...playlist,
        songs: playlist.songs.filter(s => s.id !== songId),
        updatedAt: new Date().toISOString(),
      };
      return updatedPlaylist;
    }));

    // Track analytics event
    if (removedSong && playlistName) {
      trackRemoveFromPlaylist(removedSong, playlistName);
    }

    // Sync to server
    if (updatedPlaylist && isAuthenticated && user && isSupabaseConfigured()) {
      syncPlaylistDebounced(updatedPlaylist);
    }
  }, [isAuthenticated, user, syncPlaylistDebounced]);

  const updatePlaylist = useCallback((
    playlistId: string,
    updates: Partial<Pick<Playlist, 'name' | 'description'>>
  ) => {
    let updatedPlaylist: Playlist | null = null;

    setPlaylists(prev => prev.map(playlist => {
      if (playlist.id !== playlistId) return playlist;

      updatedPlaylist = {
        ...playlist,
        ...updates,
        updatedAt: new Date().toISOString(),
      };
      return updatedPlaylist;
    }));

    // Sync to server
    if (updatedPlaylist && isAuthenticated && user && isSupabaseConfigured()) {
      syncPlaylistDebounced(updatedPlaylist);
    }
  }, [isAuthenticated, user, syncPlaylistDebounced]);

  const getPlaylist = useCallback((playlistId: string) => {
    return playlists.find(p => p.id === playlistId);
  }, [playlists]);

  return (
    <PlaylistContext.Provider
      value={{
        playlists,
        isLoading,
        syncStatus,
        createPlaylist,
        deletePlaylist,
        addToPlaylist,
        removeFromPlaylist,
        updatePlaylist,
        getPlaylist,
        forceSync,
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
