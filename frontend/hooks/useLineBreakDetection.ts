import { useEffect, useRef, useState, useCallback } from 'react';

/**
 * Hook to detect which items in a flex-wrapped container share the same visual row.
 * Returns refs to attach to items and a function to check if a separator should appear after an item.
 */
export function useLineBreakDetection<T>(items: T[]) {
  const [itemsOnSameRow, setItemsOnSameRow] = useState<Map<number, boolean>>(new Map());
  const itemRefs = useRef<(HTMLElement | null)[]>([]);
  const isCalculatingRef = useRef(false);

  const calculateRows = useCallback(() => {
    // Prevent concurrent calculations
    if (isCalculatingRef.current) return;
    isCalculatingRef.current = true;

    // Use setTimeout to break out of render cycle
    setTimeout(() => {
      const positions = itemRefs.current.map(el =>
        el ? el.getBoundingClientRect().top : null
      );

      const sameRow = new Map<number, boolean>();
      positions.forEach((pos, idx) => {
        const nextPos = positions[idx + 1];
        // Items on same row if their top positions match (within 1px tolerance)
        if (pos !== null && nextPos !== null) {
          sameRow.set(idx, Math.abs(pos - nextPos) < 1);
        } else {
          sameRow.set(idx, false);
        }
      });

      setItemsOnSameRow(sameRow);
      isCalculatingRef.current = false;
    }, 0);
  }, []);

  useEffect(() => {
    // Initial calculation after mount
    const mountTimer = setTimeout(calculateRows, 200);

    // Debounced resize handler
    let resizeTimer: NodeJS.Timeout;
    const handleResize = () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        if (!isCalculatingRef.current) {
          calculateRows();
        }
      }, 300);
    };

    window.addEventListener('resize', handleResize);

    return () => {
      clearTimeout(mountTimer);
      clearTimeout(resizeTimer);
      window.removeEventListener('resize', handleResize);
      isCalculatingRef.current = false;
    };
  }, [calculateRows]);

  return {
    itemRefs,
    shouldShowSeparator: (index: number) => itemsOnSameRow.get(index) ?? false
  };
}
