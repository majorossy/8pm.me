'use client';

import { useState, useCallback, useEffect } from 'react';

export interface ContactSubmission {
  id: string;
  name: string;
  email: string;
  subject: string;
  message: string;
  timestamp: number;
  read?: boolean;
}

interface ContactSubmissionsState {
  submissions: ContactSubmission[];
}

const STORAGE_KEY = '8pm-contact-submissions';

// Generate unique ID
const generateId = () => `contact-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

// Load from localStorage
const loadFromStorage = (): ContactSubmissionsState => {
  if (typeof window === 'undefined') {
    return { submissions: [] };
  }
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      return JSON.parse(stored);
    }
  } catch (e) {
    console.error('Error loading contact submissions from localStorage:', e);
  }
  return { submissions: [] };
};

// Save to localStorage
const saveToStorage = (state: ContactSubmissionsState): void => {
  if (typeof window === 'undefined') return;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch (e) {
    console.error('Error saving contact submissions to localStorage:', e);
  }
};

export function useContactSubmissions() {
  const [state, setState] = useState<ContactSubmissionsState>({ submissions: [] });
  const [isLoaded, setIsLoaded] = useState(false);

  // Load from localStorage on mount
  useEffect(() => {
    const loaded = loadFromStorage();
    setState(loaded);
    setIsLoaded(true);
  }, []);

  // Add a new submission
  const addSubmission = useCallback((
    data: Omit<ContactSubmission, 'id' | 'timestamp' | 'read'>
  ): ContactSubmission => {
    const newSubmission: ContactSubmission = {
      ...data,
      id: generateId(),
      timestamp: Date.now(),
      read: false,
    };

    setState(prev => {
      const newState = {
        submissions: [newSubmission, ...prev.submissions],
      };
      saveToStorage(newState);
      return newState;
    });

    return newSubmission;
  }, []);

  // Get all submissions
  const getSubmissions = useCallback((): ContactSubmission[] => {
    return state.submissions;
  }, [state.submissions]);

  // Get submission count
  const getSubmissionCount = useCallback((): number => {
    return state.submissions.length;
  }, [state.submissions]);

  // Mark submission as read
  const markAsRead = useCallback((id: string): void => {
    setState(prev => {
      const newState = {
        submissions: prev.submissions.map(sub =>
          sub.id === id ? { ...sub, read: true } : sub
        ),
      };
      saveToStorage(newState);
      return newState;
    });
  }, []);

  // Delete a submission
  const deleteSubmission = useCallback((id: string): void => {
    setState(prev => {
      const newState = {
        submissions: prev.submissions.filter(sub => sub.id !== id),
      };
      saveToStorage(newState);
      return newState;
    });
  }, []);

  // Clear all submissions
  const clearAll = useCallback((): void => {
    const newState = { submissions: [] };
    saveToStorage(newState);
    setState(newState);
  }, []);

  return {
    submissions: state.submissions,
    isLoaded,
    addSubmission,
    getSubmissions,
    getSubmissionCount,
    markAsRead,
    deleteSubmission,
    clearAll,
  };
}

export default useContactSubmissions;
