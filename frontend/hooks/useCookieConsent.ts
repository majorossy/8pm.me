'use client';

import { useState, useEffect, useCallback } from 'react';

export type CookieCategory = 'necessary' | 'functional' | 'analytics';

export interface CookieConsent {
  necessary: boolean; // Always true - required for site functionality
  functional: boolean; // Preferences, playlists sync
  analytics: boolean; // Google Analytics, Web Vitals reporting
  timestamp: number; // When consent was given
  version: string; // Consent version for future policy updates
}

const CONSENT_KEY = '8pm_cookie_consent';
const CONSENT_VERSION = '1.0';

const defaultConsent: CookieConsent = {
  necessary: true,
  functional: false,
  analytics: false,
  timestamp: 0,
  version: CONSENT_VERSION,
};

/**
 * Hook to manage cookie consent state
 *
 * Usage:
 * ```tsx
 * const { consent, hasConsented, acceptAll, declineNonEssential, updateConsent } = useCookieConsent();
 *
 * if (consent.analytics) {
 *   // Load Google Analytics
 * }
 * ```
 */
export function useCookieConsent() {
  const [consent, setConsent] = useState<CookieConsent>(defaultConsent);
  const [hasConsented, setHasConsented] = useState<boolean | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Load consent from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(CONSENT_KEY);
      if (stored) {
        const parsed = JSON.parse(stored) as CookieConsent;
        // Check if consent version matches (for future policy updates)
        if (parsed.version === CONSENT_VERSION) {
          setConsent(parsed);
          setHasConsented(true);
        } else {
          // Version mismatch - user needs to re-consent
          setHasConsented(false);
        }
      } else {
        setHasConsented(false);
      }
    } catch {
      setHasConsented(false);
    }
    setIsLoading(false);
  }, []);

  // Save consent to localStorage
  const saveConsent = useCallback((newConsent: CookieConsent) => {
    try {
      localStorage.setItem(CONSENT_KEY, JSON.stringify(newConsent));
      setConsent(newConsent);
      setHasConsented(true);

      // Dispatch custom event for other components to react
      window.dispatchEvent(new CustomEvent('cookieConsentUpdate', { detail: newConsent }));
    } catch (error) {
      console.error('Failed to save cookie consent:', error);
    }
  }, []);

  // Accept all cookies
  const acceptAll = useCallback(() => {
    const newConsent: CookieConsent = {
      necessary: true,
      functional: true,
      analytics: true,
      timestamp: Date.now(),
      version: CONSENT_VERSION,
    };
    saveConsent(newConsent);
  }, [saveConsent]);

  // Decline non-essential cookies (GDPR-compliant default)
  const declineNonEssential = useCallback(() => {
    const newConsent: CookieConsent = {
      necessary: true,
      functional: false,
      analytics: false,
      timestamp: Date.now(),
      version: CONSENT_VERSION,
    };
    saveConsent(newConsent);
  }, [saveConsent]);

  // Custom consent selection
  const updateConsent = useCallback((categories: Partial<Omit<CookieConsent, 'timestamp' | 'version'>>) => {
    const newConsent: CookieConsent = {
      ...consent,
      ...categories,
      necessary: true, // Always required
      timestamp: Date.now(),
      version: CONSENT_VERSION,
    };
    saveConsent(newConsent);
  }, [consent, saveConsent]);

  // Reset consent (for testing or user request)
  const resetConsent = useCallback(() => {
    try {
      localStorage.removeItem(CONSENT_KEY);
      setConsent(defaultConsent);
      setHasConsented(false);
    } catch (error) {
      console.error('Failed to reset cookie consent:', error);
    }
  }, []);

  // Check if a specific category is consented
  const hasConsentFor = useCallback((category: CookieCategory): boolean => {
    return consent[category] ?? false;
  }, [consent]);

  return {
    consent,
    hasConsented,
    isLoading,
    acceptAll,
    declineNonEssential,
    updateConsent,
    resetConsent,
    hasConsentFor,
  };
}

/**
 * Utility to check consent outside of React components
 * Useful for analytics initialization
 */
export function getStoredConsent(): CookieConsent | null {
  if (typeof window === 'undefined') return null;

  try {
    const stored = localStorage.getItem(CONSENT_KEY);
    if (stored) {
      const parsed = JSON.parse(stored) as CookieConsent;
      if (parsed.version === CONSENT_VERSION) {
        return parsed;
      }
    }
  } catch {
    // Ignore errors
  }
  return null;
}

/**
 * Check if analytics consent has been given
 * Use this before loading GA4 or other analytics
 */
export function hasAnalyticsConsent(): boolean {
  const consent = getStoredConsent();
  return consent?.analytics ?? false;
}
