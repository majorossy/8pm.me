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
}

const WishlistContext = createContext<WishlistContextType | null>(null);

// Generate mock wishlist item ID
let mockItemId = 0;
const generateItemId = () => `wishlist-item-${++mockItemId}`;

export function WishlistProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<WishlistItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  // TODO: Wire up to actual auth state from Magento customer token
  const [isAuthenticated, setIsAuthenticated] = useState(false);

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

  return (
    <WishlistContext.Provider
      value={{
        wishlist,
        isLoading,
        addToWishlist,
        removeFromWishlist,
        isInWishlist,
        isAuthenticated,
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
