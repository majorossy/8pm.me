'use client';

import { useLayoutEffect, useRef, useCallback } from 'react';

/**
 * Detects which flex items start a new line after wrapping.
 * Directly manipulates DOM visibility to avoid React re-render flicker.
 *
 * Uses offsetLeft comparison: In LTR layouts, when an item wraps to a new line,
 * its offsetLeft resets to near the container's left edge.
 */
export function useLineStartDetection(itemCount: number) {
  const containerRef = useRef<HTMLDivElement>(null);
  const itemRefs = useRef<(HTMLSpanElement | null)[]>([]);
  const starRefs = useRef<(HTMLSpanElement | null)[]>([]);

  const detectAndHideLineStarts = useCallback(() => {
    const items = itemRefs.current;
    const stars = starRefs.current;

    for (let i = 1; i < items.length; i++) {
      const current = items[i];
      const previous = items[i - 1];
      const star = stars[i];

      if (!current || !previous || !star) continue;

      // If current item's left is <= previous item's left, it wrapped to a new line
      const isLineStart = current.offsetLeft <= previous.offsetLeft;

      // Stars start invisible via CSS class, reveal the ones that should be shown
      // Use visibility to preserve layout (display:none would cause reflow cascade)
      star.style.visibility = isLineStart ? 'hidden' : 'visible';
    }
  }, []);

  useLayoutEffect(() => {
    // Reset refs array when item count changes
    itemRefs.current = itemRefs.current.slice(0, itemCount);
    starRefs.current = starRefs.current.slice(0, itemCount);

    // Initial measurement - run synchronously before paint
    detectAndHideLineStarts();

    // Re-measure after fonts load
    document.fonts.ready.then(() => {
      requestAnimationFrame(detectAndHideLineStarts);
    });

    // Observe container size changes
    const container = containerRef.current;
    if (!container) return;

    const resizeObserver = new ResizeObserver(() => {
      detectAndHideLineStarts();
    });
    resizeObserver.observe(container);

    return () => resizeObserver.disconnect();
  }, [itemCount, detectAndHideLineStarts]);

  const setItemRef = useCallback(
    (index: number) => (el: HTMLSpanElement | null) => {
      itemRefs.current[index] = el;
    },
    []
  );

  const setStarRef = useCallback(
    (index: number) => (el: HTMLSpanElement | null) => {
      starRefs.current[index] = el;
    },
    []
  );

  return {
    containerRef,
    setItemRef,
    setStarRef,
  };
}
