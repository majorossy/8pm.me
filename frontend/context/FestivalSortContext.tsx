'use client';

import React, {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react';
import {
  ArtistWithStats,
  SortAlgorithm,
  isValidAlgorithm,
  sortArtistsByAlgorithm,
} from '@/utils/festivalSorting';

const STORAGE_KEY = 'festivalSortAlgorithm';
const DEFAULT_ALGORITHM: SortAlgorithm = 'songVersions';

interface FestivalSortContextValue {
  sortedArtists: ArtistWithStats[];
  algorithm: SortAlgorithm;
  setAlgorithm: (algo: SortAlgorithm) => void;
  isLoading: boolean;
}

const FestivalSortContext = createContext<FestivalSortContextValue | undefined>(
  undefined
);

interface FestivalSortProviderProps {
  artists: ArtistWithStats[];
  children: React.ReactNode;
}

export function FestivalSortProvider({
  artists,
  children,
}: FestivalSortProviderProps) {
  const [algorithm, setAlgorithmState] = useState<SortAlgorithm>(DEFAULT_ALGORITHM);
  const [isHydrated, setIsHydrated] = useState(false);

  // SSR-safe hydration: Load from localStorage after mount
  useEffect(() => {
    if (typeof window === 'undefined') return;

    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored && isValidAlgorithm(stored)) {
        setAlgorithmState(stored);
      }
    } catch (error) {
      // localStorage quota exceeded or disabled - gracefully degrade
      console.warn('Failed to load festival sort algorithm from localStorage:', error);
    } finally {
      setIsHydrated(true);
    }
  }, []);

  // Memoized sorted artists - only recompute when artists or algorithm changes
  const sortedArtists = useMemo(() => {
    // During SSR or before hydration, return unsorted to match server
    if (!isHydrated) return artists;

    return sortArtistsByAlgorithm(artists, algorithm);
  }, [artists, algorithm, isHydrated]);

  // Memoized setter - persists to localStorage
  const setAlgorithm = useCallback((algo: SortAlgorithm) => {
    if (!isValidAlgorithm(algo)) {
      console.warn(`Invalid algorithm: ${algo}, falling back to songVersions`);
      return;
    }

    setAlgorithmState(algo);

    // Persist to localStorage
    if (typeof window !== 'undefined') {
      try {
        localStorage.setItem(STORAGE_KEY, algo);
      } catch (error) {
        // Quota exceeded - app keeps working, just loses persistence
        console.warn('Failed to save festival sort algorithm to localStorage:', error);
      }
    }
  }, []);

  // Memoize context value to prevent unnecessary re-renders
  const value = useMemo<FestivalSortContextValue>(
    () => ({
      sortedArtists,
      algorithm,
      setAlgorithm,
      isLoading: !isHydrated,
    }),
    [sortedArtists, algorithm, setAlgorithm, isHydrated]
  );

  return (
    <FestivalSortContext.Provider value={value}>
      {children}
    </FestivalSortContext.Provider>
  );
}

export function useFestivalSort(): FestivalSortContextValue {
  const context = React.useContext(FestivalSortContext);
  if (context === undefined) {
    throw new Error('useFestivalSort must be used within a FestivalSortProvider');
  }
  return context;
}
