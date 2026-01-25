'use client';

// CartContext = Queue (Magento Cart)
// Adding to cart = adding song to playback queue

import React, { createContext, useContext, useState, useCallback } from 'react';
import { Song, Cart, CartItem } from '@/lib/types';

interface CartContextType {
  cart: Cart;
  isLoading: boolean;
  addToCart: (song: Song) => void;
  removeFromCart: (itemId: string) => void;
  clearCart: () => void;
  isInCart: (songId: string) => boolean;
  // For Magento integration later
  cartId: string | null;
}

const CartContext = createContext<CartContextType | null>(null);

// Generate mock cart item ID
let mockItemId = 0;
const generateItemId = () => `cart-item-${++mockItemId}`;

export function CartProvider({ children }: { children: React.ReactNode }) {
  // In production, cartId comes from Magento createEmptyCart mutation
  const [cartId, setCartId] = useState<string | null>('mock-cart-123');
  const [items, setItems] = useState<CartItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  const cart: Cart = {
    id: cartId || '',
    items,
    itemCount: items.length,
  };

  const addToCart = useCallback((song: Song) => {
    // TODO: Replace with Magento addProductsToCart mutation
    setItems(prev => {
      if (prev.some(item => item.song.id === song.id)) {
        return prev; // Already in cart
      }
      return [...prev, {
        id: generateItemId(),
        song,
        quantity: 1,
      }];
    });
  }, []);

  const removeFromCart = useCallback((itemId: string) => {
    // TODO: Replace with Magento removeItemFromCart mutation
    setItems(prev => prev.filter(item => item.id !== itemId));
  }, []);

  const clearCart = useCallback(() => {
    // TODO: Replace with Magento mutation or create new cart
    setItems([]);
  }, []);

  const isInCart = useCallback((songId: string) => {
    return items.some(item => item.song.id === songId);
  }, [items]);

  return (
    <CartContext.Provider
      value={{
        cart,
        isLoading,
        addToCart,
        removeFromCart,
        clearCart,
        isInCart,
        cartId,
      }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
}
