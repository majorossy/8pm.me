import { useState, useEffect } from 'react';

const STORAGE_KEY = 'jamify-recent-searches';
const MAX_RECENT_SEARCHES = 10;

export function useRecentSearches() {
  const [recentSearches, setRecentSearches] = useState<string[]>([]);

  // Load from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed)) {
          setRecentSearches(parsed);
        }
      }
    } catch (error) {
      console.error('Failed to load recent searches:', error);
    }
  }, []);

  // Save to localStorage whenever recentSearches changes
  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(recentSearches));
    } catch (error) {
      console.error('Failed to save recent searches:', error);
    }
  }, [recentSearches]);

  const addSearch = (query: string) => {
    const trimmedQuery = query.trim();
    if (!trimmedQuery) return;

    setRecentSearches((prev) => {
      // Remove if already exists
      const filtered = prev.filter((s) => s.toLowerCase() !== trimmedQuery.toLowerCase());
      // Add to front
      const updated = [trimmedQuery, ...filtered];
      // Keep only top MAX_RECENT_SEARCHES
      return updated.slice(0, MAX_RECENT_SEARCHES);
    });
  };

  const removeSearch = (query: string) => {
    setRecentSearches((prev) => prev.filter((s) => s !== query));
  };

  const clearSearches = () => {
    setRecentSearches([]);
  };

  return {
    recentSearches,
    addSearch,
    removeSearch,
    clearSearches,
  };
}
