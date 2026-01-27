import { useCallback, useMemo } from 'react';

// Haptic feedback patterns (in milliseconds)
const HAPTIC_PATTERNS = {
  BUTTON_PRESS: 10,
  SWIPE_COMPLETE: [10, 50, 10] as number[],
  DELETE_ACTION: 20,
  LONG_PRESS: 50,
} as const;

/**
 * Custom hook for haptic feedback
 * - Provides vibration patterns for different interactions
 * - Respects user's prefers-reduced-motion preference
 * - Feature detects navigator.vibrate support
 */
export function useHaptic() {
  // Check if vibration API is supported
  const isSupported = useMemo(
    () => typeof navigator !== 'undefined' && 'vibrate' in navigator,
    []
  );

  // Check if user prefers reduced motion
  const prefersReducedMotion = useMemo(() => {
    if (typeof window === 'undefined') return true;
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }, []);

  /**
   * Trigger haptic feedback
   * @param pattern - Vibration pattern (number or array of numbers in ms)
   */
  const vibrate = useCallback(
    (pattern: number | number[]) => {
      // Skip if not supported, reduced motion is preferred, or in SSR
      if (!isSupported || prefersReducedMotion || typeof navigator === 'undefined') {
        return;
      }

      try {
        navigator.vibrate(pattern);
      } catch (error) {
        // Silently fail if vibration fails
        console.debug('Haptic feedback failed:', error);
      }
    },
    [isSupported, prefersReducedMotion]
  );

  return {
    vibrate,
    ...HAPTIC_PATTERNS,
    isSupported,
    prefersReducedMotion,
  };
}
