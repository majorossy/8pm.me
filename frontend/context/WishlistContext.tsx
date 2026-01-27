'use client';

// WishlistContext = Favorites (Magento Wishlist)
// Requires customer authentication in Magento

import React, { createContext, useContext, useState, useCallback } from 'react';
import { Song, Wishlist, WishlistItem } from '@/lib/types';

interface WishlistContextType {
  wishlist: Wishlist;
  isLoading: boolean;
  addToWishlist: (song: Song) => void;
  removeFromWishlist: (itemId: string) => void;
  isInWishlist: (songId: string) => boolean;
  // For auth state (Magento requires login for wishlist)
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
}

const WishlistContext = createContext<WishlistContextType | null>(null);

// Generate mock wishlist item ID
let mockItemId = 0;
const generateItemId = () => `wishlist-item-${++mockItemId}`;

const WISHLIST_STORAGE_KEY = 'jamify_wishlist';
const FOLLOWED_ARTISTS_STORAGE_KEY = 'jamify_followed_artists';
const FOLLOWED_ALBUMS_STORAGE_KEY = 'jamify_followed_albums';

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  // TODO: Wire up to actual auth state from Magento customer token
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [followedArtists, setFollowedArtists] = useState<string[]>([]);
  const [followedAlbums, setFollowedAlbums] = useState<string[]>([]);

  // Load wishlist from localStorage on mount
  React.useEffect(() => {
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
  }, []);

  // Save wishlist to localStorage whenever it changes
  React.useEffect(() => {
    if (items.length > 0) {
      localStorage.setItem(WISHLIST_STORAGE_KEY, JSON.stringify(items));
    } else {
      localStorage.removeItem(WISHLIST_STORAGE_KEY);
    }
  }, [items]);

  // Save followed artists to localStorage
  React.useEffect(() => {
    if (followedArtists.length > 0) {
      localStorage.setItem(FOLLOWED_ARTISTS_STORAGE_KEY, JSON.stringify(followedArtists));
    } else {
      localStorage.removeItem(FOLLOWED_ARTISTS_STORAGE_KEY);
    }
  }, [followedArtists]);

  // Save followed albums to localStorage
  React.useEffect(() => {
    if (followedAlbums.length > 0) {
      localStorage.setItem(FOLLOWED_ALBUMS_STORAGE_KEY, JSON.stringify(followedAlbums));
    } else {
      localStorage.removeItem(FOLLOWED_ALBUMS_STORAGE_KEY);
    }
  }, [followedAlbums]);

  const wishlist: Wishlist = {
    id: 'mock-wishlist-123',
    items,
    itemCount: items.length,
  };

  const addToWishlist = useCallback((song: Song) => {
    // TODO: Replace with Magento addProductsToWishlist mutation
    // Note: Requires customer authentication
    setItems(prev => {
      if (prev.some(item => item.song.id === song.id)) {
        return prev; // Already in wishlist
      }
      return [...prev, {
        id: generateItemId(),
        song,
        addedAt: new Date().toISOString(),
      }];
    });
  }, []);

  const removeFromWishlist = useCallback((itemId: string) => {
    // TODO: Replace with Magento removeProductsFromWishlist mutation
    setItems(prev => prev.filter(item => item.id !== itemId));
  }, []);

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
  }, []);

  const unfollowArtist = useCallback((slug: string) => {
    setFollowedArtists(prev => prev.filter(s => s !== slug));
  }, []);

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
  }, []);

  const unfollowAlbum = useCallback((artistSlug: string, albumTitle: string) => {
    const identifier = `${artistSlug}::${albumTitle}`;
    setFollowedAlbums(prev => prev.filter(s => s !== identifier));
  }, []);

  const isAlbumFollowed = useCallback((artistSlug: string, albumTitle: string) => {
    const identifier = `${artistSlug}::${albumTitle}`;
    return followedAlbums.includes(identifier);
  }, [followedAlbums]);

  return (
    <WishlistContext.Provider
      value={{
        wishlist,
        isLoading,
        addToWishlist,
        removeFromWishlist,
        isInWishlist,
        isAuthenticated,
        followedArtists,
        followedAlbums,
        followArtist,
        unfollowArtist,
        isArtistFollowed,
        followAlbum,
        unfollowAlbum,
        isAlbumFollowed,
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
