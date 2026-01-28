'use client';

// useSyncedState - Hook for managing state that syncs between localStorage and Supabase
// Provides optimistic updates with server reconciliation

import { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '@/context/AuthContext';
import { isSupabaseConfigured } from '@/lib/supabase';
import { SyncStatus } from '@/lib/syncService';

interface SyncedStateOptions<T> {
  storageKey: string;
  defaultValue: T;
  fetchFromServer?: (userId: string) => Promise<T>;
  syncToServer?: (userId: string, data: T) => Promise<boolean>;
  subscribeToChanges?: (userId: string, onUpdate: (data: T) => void) => () => void;
  parseStored?: (stored: string) => T;
  stringify?: (data: T) => string;
}

interface SyncedStateReturn<T> {
  data: T;
  setData: (updater: T | ((prev: T) => T)) => void;
  syncStatus: SyncStatus;
  lastSyncedAt: Date | null;
  forceSync: () => Promise<void>;
  isLoading: boolean;
}

export function useSyncedState<T>({
  storageKey,
  defaultValue,
  fetchFromServer,
  syncToServer,
  subscribeToChanges,
  parseStored = JSON.parse,
  stringify = JSON.stringify,
}: SyncedStateOptions<T>): SyncedStateReturn<T> {
  const { user, isAuthenticated } = useAuth();
  const [data, setDataInternal] = useState<T>(defaultValue);
  const [syncStatus, setSyncStatus] = useState<SyncStatus>('idle');
  const [lastSyncedAt, setLastSyncedAt] = useState<Date | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const pendingSyncRef = useRef<T | null>(null);
  const syncTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  // Load from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(storageKey);
      if (stored) {
        const parsed = parseStored(stored);
        setDataInternal(parsed);
      }
    } catch (error) {
      console.error(`Failed to load ${storageKey} from localStorage:`, error);
    }
    setIsLoading(false);
  }, [storageKey, parseStored]);

  // Save to localStorage whenever data changes
  useEffect(() => {
    if (!isLoading) {
      try {
        const serialized = stringify(data);
        if (serialized === '[]' || serialized === '{}' || serialized === stringify(defaultValue)) {
          localStorage.removeItem(storageKey);
        } else {
          localStorage.setItem(storageKey, serialized);
        }
      } catch (error) {
        console.error(`Failed to save ${storageKey} to localStorage:`, error);
      }
    }
  }, [data, isLoading, storageKey, stringify, defaultValue]);

  // Fetch from server when user authenticates
  useEffect(() => {
    if (!isAuthenticated || !user || !fetchFromServer || !isSupabaseConfigured()) {
      return;
    }

    const fetchData = async () => {
      setSyncStatus('syncing');
      try {
        const serverData = await fetchFromServer(user.id);
        setDataInternal(serverData);
        setLastSyncedAt(new Date());
        setSyncStatus('synced');
      } catch (error) {
        console.error(`Failed to fetch ${storageKey} from server:`, error);
        setSyncStatus('error');
      }
    };

    fetchData();
  }, [isAuthenticated, user, fetchFromServer, storageKey]);

  // Subscribe to realtime changes
  useEffect(() => {
    if (!isAuthenticated || !user || !subscribeToChanges || !isSupabaseConfigured()) {
      return;
    }

    const unsubscribe = subscribeToChanges(user.id, (newData) => {
      setDataInternal(newData);
      setLastSyncedAt(new Date());
    });

    return unsubscribe;
  }, [isAuthenticated, user, subscribeToChanges]);

  // Debounced sync to server
  const syncToServerDebounced = useCallback((newData: T) => {
    if (!isAuthenticated || !user || !syncToServer || !isSupabaseConfigured()) {
      return;
    }

    // Clear existing timeout
    if (syncTimeoutRef.current) {
      clearTimeout(syncTimeoutRef.current);
    }

    // Store pending data
    pendingSyncRef.current = newData;
    setSyncStatus('syncing');

    // Set new timeout
    syncTimeoutRef.current = setTimeout(async () => {
      if (pendingSyncRef.current === null) return;

      try {
        const success = await syncToServer(user.id, pendingSyncRef.current);
        if (success) {
          setLastSyncedAt(new Date());
          setSyncStatus('synced');
        } else {
          setSyncStatus('error');
        }
      } catch (error) {
        console.error(`Failed to sync ${storageKey} to server:`, error);
        setSyncStatus('error');
      }

      pendingSyncRef.current = null;
    }, 500);
  }, [isAuthenticated, user, syncToServer, storageKey]);

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (syncTimeoutRef.current) {
        clearTimeout(syncTimeoutRef.current);
      }
    };
  }, []);

  // Set data function with optimistic updates
  const setData = useCallback((updater: T | ((prev: T) => T)) => {
    setDataInternal(prev => {
      const newData = typeof updater === 'function'
        ? (updater as (prev: T) => T)(prev)
        : updater;

      // Trigger server sync
      syncToServerDebounced(newData);

      return newData;
    });
  }, [syncToServerDebounced]);

  // Force sync function
  const forceSync = useCallback(async () => {
    if (!isAuthenticated || !user || !syncToServer || !isSupabaseConfigured()) {
      return;
    }

    setSyncStatus('syncing');
    try {
      const success = await syncToServer(user.id, data);
      if (success) {
        setLastSyncedAt(new Date());
        setSyncStatus('synced');
      } else {
        setSyncStatus('error');
      }
    } catch (error) {
      console.error(`Failed to force sync ${storageKey}:`, error);
      setSyncStatus('error');
    }
  }, [isAuthenticated, user, syncToServer, data, storageKey]);

  return {
    data,
    setData,
    syncStatus,
    lastSyncedAt,
    forceSync,
    isLoading,
  };
}

// Simplified hook for data that only syncs on-demand (not automatically)
export function useLocalStorageState<T>(
  storageKey: string,
  defaultValue: T
): [T, React.Dispatch<React.SetStateAction<T>>, boolean] {
  const [data, setData] = useState<T>(defaultValue);
  const [isLoading, setIsLoading] = useState(true);

  // Load from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(storageKey);
      if (stored) {
        setData(JSON.parse(stored));
      }
    } catch (error) {
      console.error(`Failed to load ${storageKey} from localStorage:`, error);
    }
    setIsLoading(false);
  }, [storageKey]);

  // Save to localStorage whenever data changes
  useEffect(() => {
    if (!isLoading) {
      try {
        const serialized = JSON.stringify(data);
        if (serialized === '[]' || serialized === '{}') {
          localStorage.removeItem(storageKey);
        } else {
          localStorage.setItem(storageKey, serialized);
        }
      } catch (error) {
        console.error(`Failed to save ${storageKey} to localStorage:`, error);
      }
    }
  }, [data, isLoading, storageKey]);

  return [data, setData, isLoading];
}
