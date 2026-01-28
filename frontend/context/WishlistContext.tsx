'use client';

// WishlistContext = Favorites (Magento Wishlist)
// Uses localStorage for persistence with Supabase sync when authenticated

import React, { createContext, useContext, useState, useCallback, useEffect, useRef } from 'react';
import { Song, Wishlist, WishlistItem } from '@/lib/types';
import { useAuth } from '@/context/AuthContext';
import {
  fetchUserWishlist,
  syncWishlistItemToServer,
  removeWishlistItemFromServer,
  syncFollowedArtist,
  syncFollowedAlbum,
  subscribeToWishlistChanges,
  WishlistData,
  SyncStatus,
} from '@/lib/syncService';
import { isSupabaseConfigured } from '@/lib/supabase';

interface WishlistContextType {
  wishlist: Wishlist;
  isLoading: boolean;
  syncStatus: SyncStatus;
  addToWishlist: (song: Song) => void;
  removeFromWishlist: (itemId: string) => void;
  isInWishlist: (songId: string) => boolean;
  // For auth state
  isAuthenticated: boolean;
  // Follow artists/albums
  followedArtists: string[];
  followedAlbums: string[];
  followArtist: (slug: string) => void;
  unfollowArtist: (slug: string) => void;
  isArtistFollowed: (slug: string) => boolean;
  followAlbum: (artistSlug: string, albumTitle: string) => void;
  unfollowAlbum: (artistSlug: string, albumTitle: string) => void;
  isAlbumFollowed: (artistSlug: string, albumTitle: string) => boolean;
  forceSync: () => Promise<void>;
}

const WishlistContext = createContext<WishlistContextType | null>(null);

// Generate mock wishlist item ID
let mockItemId = 0;
const generateItemId = () => `wishlist-item-${++mockItemId}`;

const WISHLIST_STORAGE_KEY = 'jamify_wishlist';
const FOLLOWED_ARTISTS_STORAGE_KEY = 'jamify_followed_artists';
const FOLLOWED_ALBUMS_STORAGE_KEY = 'jamify_followed_albums';

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const { user, isAuthenticated: authIsAuthenticated } = useAuth();
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [syncStatus, setSyncStatus] = useState<SyncStatus>('idle');
  const [followedArtists, setFollowedArtists] = useState<string[]>([]);
  const [followedAlbums, setFollowedAlbums] = useState<string[]>([]);
  const hasFetchedFromServerRef = useRef(false);

  // Load wishlist from localStorage on mount
  useEffect(() => {
    const stored = localStorage.getItem(WISHLIST_STORAGE_KEY);
    if (stored) {
      try {
        const parsed = JSON.parse(stored);
        setItems(parsed);
      } catch (error) {
        console.error('Failed to load wishlist from localStorage:', error);
      }
    }

    const storedArtists = localStorage.getItem(FOLLOWED_ARTISTS_STORAGE_KEY);
    if (storedArtists) {
      try {
        const parsed = JSON.parse(storedArtists);
        setFollowedArtists(parsed);
      } catch (error) {
        console.error('Failed to load followed artists from localStorage:', error);
      }
    }

    const storedAlbums = localStorage.getItem(FOLLOWED_ALBUMS_STORAGE_KEY);
    if (storedAlbums) {
      try {
        const parsed = JSON.parse(storedAlbums);
        setFollowedAlbums(parsed);
      } catch (error) {
        console.error('Failed to load followed albums from localStorage:', error);
      }
    }

    setIsLoading(false);
  }, []);

  // Fetch from server when authenticated
  useEffect(() => {
    if (!authIsAuthenticated || !user || !isSupabaseConfigured() || hasFetchedFromServerRef.current) {
      return;
    }

    const fetchFromServer = async () => {
      setSyncStatus('syncing');
      try {
        const serverData = await fetchUserWishlist(user.id);

        // Merge with local data (server takes precedence)
        if (serverData.items.length > 0) {
          setItems(serverData.items);
        }
        if (serverData.followedArtists.length > 0) {
          setFollowedArtists(serverData.followedArtists);
        }
        if (serverData.followedAlbums.length > 0) {
          setFollowedAlbums(serverData.followedAlbums);
        }

        setSyncStatus('synced');
        hasFetchedFromServerRef.current = true;
      } catch (error) {
        console.error('Failed to fetch wishlist from server:', error);
        setSyncStatus('error');
      }
    };

    fetchFromServer();
  }, [authIsAuthenticated, user]);

  // Subscribe to realtime changes
  useEffect(() => {
    if (!authIsAuthenticated || !user || !isSupabaseConfigured()) {
      return;
    }

    const unsubscribe = subscribeToWishlistChanges(user.id, (serverData: WishlistData) => {
      setItems(serverData.items);
      setFollowedArtists(serverData.followedArtists);
      setFollowedAlbums(serverData.followedAlbums);
      setSyncStatus('synced');
    });

    return unsubscribe;
  }, [authIsAuthenticated, user]);

  // Save wishlist to localStorage whenever it changes
  useEffect(() => {
    if (!isLoading) {
      if (items.length > 0) {
        localStorage.setItem(WISHLIST_STORAGE_KEY, JSON.stringify(items));
      } else {
        localStorage.removeItem(WISHLIST_STORAGE_KEY);
      }
    }
  }, [items, isLoading]);

  // Save followed artists to localStorage
  useEffect(() => {
    if (!isLoading) {
      if (followedArtists.length > 0) {
        localStorage.setItem(FOLLOWED_ARTISTS_STORAGE_KEY, JSON.stringify(followedArtists));
      } else {
        localStorage.removeItem(FOLLOWED_ARTISTS_STORAGE_KEY);
      }
    }
  }, [followedArtists, isLoading]);

  // Save followed albums to localStorage
  useEffect(() => {
    if (!isLoading) {
      if (followedAlbums.length > 0) {
        localStorage.setItem(FOLLOWED_ALBUMS_STORAGE_KEY, JSON.stringify(followedAlbums));
      } else {
        localStorage.removeItem(FOLLOWED_ALBUMS_STORAGE_KEY);
      }
    }
  }, [followedAlbums, isLoading]);

  const wishlist: Wishlist = {
    id: 'mock-wishlist-123',
    items,
    itemCount: items.length,
  };

  const addToWishlist = useCallback((song: Song) => {
    const newItem: WishlistItem = {
      id: generateItemId(),
      song,
      addedAt: new Date().toISOString(),
    };

    setItems(prev => {
      if (prev.some(item => item.song.id === song.id)) {
        return prev; // Already in wishlist
      }
      return [...prev, newItem];
    });

    // Sync to server
    if (authIsAuthenticated && user && isSupabaseConfigured()) {
      syncWishlistItemToServer(user.id, newItem).catch(error => {
        console.error('Failed to sync wishlist item:', error);
      });
    }
  }, [authIsAuthenticated, user]);

  const removeFromWishlist = useCallback((itemId: string) => {
    setItems(prev => prev.filter(item => item.id !== itemId));

    // Sync to server
    if (authIsAuthenticated && user && isSupabaseConfigured()) {
      removeWishlistItemFromServer(itemId).catch(error => {
        console.error('Failed to remove wishlist item from server:', error);
      });
    }
  }, [authIsAuthenticated, user]);

  const isInWishlist = useCallback((songId: string) => {
    return items.some(item => item.song.id === songId);
  }, [items]);

  const followArtist = useCallback((slug: string) => {
    setFollowedArtists(prev => {
      if (prev.includes(slug)) {
        return prev;
      }
      return [...prev, slug];
    });

    // Sync to server
    if (authIsAuthenticated && user && isSupabaseConfigured()) {
      syncFollowedArtist(user.id, slug, true).catch(error => {
        console.error('Failed to sync followed artist:', error);
      });
    }
  }, [authIsAuthenticated, user]);

  const unfollowArtist = useCallback((slug: string) => {
    setFollowedArtists(prev => prev.filter(s => s !== slug));

    // Sync to server
    if (authIsAuthenticated && user && isSupabaseConfigured()) {
      syncFollowedArtist(user.id, slug, false).catch(error => {
        console.error('Failed to sync unfollowed artist:', error);
      });
    }
  }, [authIsAuthenticated, user]);

  const isArtistFollowed = useCallback((slug: string) => {
    return followedArtists.includes(slug);
  }, [followedArtists]);

  const followAlbum = useCallback((artistSlug: string, albumTitle: string) => {
    const identifier = `${artistSlug}::${albumTitle}`;
    setFollowedAlbums(prev => {
      if (prev.includes(identifier)) {
        return prev;
      }
      return [...prev, identifier];
    });

    // Sync to server
    if (authIsAuthenticated && user && isSupabaseConfigured()) {
      syncFollowedAlbum(user.id, artistSlug, albumTitle, true).catch(error => {
        console.error('Failed to sync followed album:', error);
      });
    }
  }, [authIsAuthenticated, user]);

  const unfollowAlbum = useCallback((artistSlug: string, albumTitle: string) => {
    const identifier = `${artistSlug}::${albumTitle}`;
    setFollowedAlbums(prev => prev.filter(s => s !== identifier));

    // Sync to server
    if (authIsAuthenticated && user && isSupabaseConfigured()) {
      syncFollowedAlbum(user.id, artistSlug, albumTitle, false).catch(error => {
        console.error('Failed to sync unfollowed album:', error);
      });
    }
  }, [authIsAuthenticated, user]);

  const isAlbumFollowed = useCallback((artistSlug: string, albumTitle: string) => {
    const identifier = `${artistSlug}::${albumTitle}`;
    return followedAlbums.includes(identifier);
  }, [followedAlbums]);

  // Force sync all data to server
  const forceSync = useCallback(async () => {
    if (!authIsAuthenticated || !user || !isSupabaseConfigured()) return;

    setSyncStatus('syncing');
    try {
      // Sync all wishlist items
      for (const item of items) {
        await syncWishlistItemToServer(user.id, item);
      }

      // Sync all followed artists
      for (const slug of followedArtists) {
        await syncFollowedArtist(user.id, slug, true);
      }

      // Sync all followed albums
      for (const identifier of followedAlbums) {
        const [artistSlug, albumTitle] = identifier.split('::');
        await syncFollowedAlbum(user.id, artistSlug, albumTitle, true);
      }

      setSyncStatus('synced');
    } catch (error) {
      console.error('Failed to force sync wishlist:', error);
      setSyncStatus('error');
    }
  }, [authIsAuthenticated, user, items, followedArtists, followedAlbums]);

  return (
    <WishlistContext.Provider
      value={{
        wishlist,
        isLoading,
        syncStatus,
        addToWishlist,
        removeFromWishlist,
        isInWishlist,
        isAuthenticated: authIsAuthenticated,
        followedArtists,
        followedAlbums,
        followArtist,
        unfollowArtist,
        isArtistFollowed,
        followAlbum,
        unfollowAlbum,
        isAlbumFollowed,
        forceSync,
      }}
    >
      {children}
    </WishlistContext.Provider>
  );
}

export function useWishlist() {
  const context = useContext(WishlistContext);
  if (!context) {
    throw new Error('useWishlist must be used within a WishlistProvider');
  }
  return context;
}
