'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { Song } from '@/lib/types';

interface RecentlyPlayedItem {
  songId: string;
  song: Song;
  playedAt: string; // ISO date string
  playCount: number;
}

interface RecentlyPlayedContextType {
  recentlyPlayed: RecentlyPlayedItem[];
  trackPlay: (song: Song) => void;
  clearRecentlyPlayed: () => void;
}

const RecentlyPlayedContext = createContext<RecentlyPlayedContextType | null>(null);

const STORAGE_KEY = 'jamify_recently_played';
const MAX_ITEMS = 50;

export function RecentlyPlayedProvider({ children }: { children: React.ReactNode }) {
  const [recentlyPlayed, setRecentlyPlayed] = useState<RecentlyPlayedItem[]>([]);

  // Load from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        setRecentlyPlayed(parsed);
      }
    } catch (error) {
      console.error('Failed to load recently played:', error);
    }
  }, []);

  // Save to localStorage whenever recentlyPlayed changes
  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(recentlyPlayed));
    } catch (error) {
      console.error('Failed to save recently played:', error);
    }
  }, [recentlyPlayed]);

  // Track a song as played
  const trackPlay = useCallback((song: Song) => {
    console.log('[RecentlyPlayed] 📝 trackPlay called for:', song.title);
    setRecentlyPlayed(prev => {
      // Check if song already exists in history
      const existingIndex = prev.findIndex(item => item.songId === song.id);

      if (existingIndex !== -1) {
        // Song exists - update playedAt and increment playCount
        console.log('[RecentlyPlayed] Song exists, incrementing play count');
        const updated = [...prev];
        updated[existingIndex] = {
          ...updated[existingIndex],
          playedAt: new Date().toISOString(),
          playCount: updated[existingIndex].playCount + 1,
        };

        // Move to front (most recent)
        const [item] = updated.splice(existingIndex, 1);
        return [item, ...updated];
      } else {
        // New song - add to front
        console.log('[RecentlyPlayed] New song, adding to history');
        const newItem: RecentlyPlayedItem = {
          songId: song.id,
          song,
          playedAt: new Date().toISOString(),
          playCount: 1,
        };

        // Add to front and keep only MAX_ITEMS
        return [newItem, ...prev].slice(0, MAX_ITEMS);
      }
    });
  }, []);

  // Clear all recently played history
  const clearRecentlyPlayed = useCallback(() => {
    setRecentlyPlayed([]);
    localStorage.removeItem(STORAGE_KEY);
  }, []);

  return (
    <RecentlyPlayedContext.Provider
      value={{
        recentlyPlayed,
        trackPlay,
        clearRecentlyPlayed,
      }}
    >
      {children}
    </RecentlyPlayedContext.Provider>
  );
}

export function useRecentlyPlayed() {
  const context = useContext(RecentlyPlayedContext);
  if (!context) {
    throw new Error('useRecentlyPlayed must be used within a RecentlyPlayedProvider');
  }
  return context;
}
