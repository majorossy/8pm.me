'use client';

import { useEffect, useRef, useState, RefObject } from 'react';

interface UseIntersectionObserverOptions {
  threshold?: number | number[];
  root?: Element | null;
  rootMargin?: string;
  freezeOnceVisible?: boolean;
}

interface UseIntersectionObserverReturn {
  ref: RefObject<HTMLElement | null>;
  isIntersecting: boolean;
  entry: IntersectionObserverEntry | null;
}

/**
 * Hook to observe when an element enters the viewport using Intersection Observer API.
 * Useful for lazy loading images and other content.
 *
 * @param options - IntersectionObserver options plus freezeOnceVisible
 * @returns ref to attach to element, isIntersecting boolean, and the entry object
 */
export function useIntersectionObserver({
  threshold = 0,
  root = null,
  rootMargin = '50px', // Load images slightly before they enter viewport
  freezeOnceVisible = true,
}: UseIntersectionObserverOptions = {}): UseIntersectionObserverReturn {
  const ref = useRef<HTMLElement | null>(null);
  const [entry, setEntry] = useState<IntersectionObserverEntry | null>(null);
  const [isIntersecting, setIsIntersecting] = useState(false);

  useEffect(() => {
    const node = ref.current;

    // Skip if no element or if already visible and frozen
    if (!node || (freezeOnceVisible && isIntersecting)) {
      return;
    }

    // Check for IntersectionObserver support
    if (typeof IntersectionObserver === 'undefined') {
      // Fallback: assume visible if IntersectionObserver not supported
      setIsIntersecting(true);
      return;
    }

    const observer = new IntersectionObserver(
      ([observerEntry]) => {
        setEntry(observerEntry);
        setIsIntersecting(observerEntry.isIntersecting);

        // Unobserve if freezeOnceVisible and element is now visible
        if (freezeOnceVisible && observerEntry.isIntersecting) {
          observer.unobserve(node);
        }
      },
      { threshold, root, rootMargin }
    );

    observer.observe(node);

    return () => {
      observer.disconnect();
    };
  }, [threshold, root, rootMargin, freezeOnceVisible, isIntersecting]);

  return { ref, isIntersecting, entry };
}

export default useIntersectionObserver;
