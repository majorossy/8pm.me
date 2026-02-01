'use client';

import { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { useFestivalSort } from '@/hooks/useFestivalSort';
import { useHaptic } from '@/hooks/useHaptic';
import type { SortAlgorithm } from '@/utils/festivalSorting';

interface AlgorithmOption {
  id: SortAlgorithm;
  icon: string;
  label: string;
  description: string;
}

const ALGORITHMS: AlgorithmOption[] = [
  {
    id: 'songVersions',
    icon: '🎵',
    label: 'Song Versions',
    description: 'Sort by number of song versions',
  },
  {
    id: 'shows',
    icon: '⭐',
    label: 'Shows',
    description: 'Sort by number of shows',
  },
  {
    id: 'hours',
    icon: '⏱️',
    label: 'Hours',
    description: 'Sort by hours of music',
  },
];

export default function AlgorithmSelector() {
  const { algorithm, setAlgorithm } = useFestivalSort();
  const { vibrate, BUTTON_PRESS } = useHaptic();
  const [prefersReducedMotion, setPrefersReducedMotion] = useState(false);

  // Check for reduced motion preference
  useEffect(() => {
    if (typeof window !== 'undefined') {
      const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
      setPrefersReducedMotion(mediaQuery.matches);

      const handler = (e: MediaQueryListEvent) => setPrefersReducedMotion(e.matches);
      mediaQuery.addEventListener('change', handler);
      return () => mediaQuery.removeEventListener('change', handler);
    }
  }, []);

  const handleSelect = (algo: SortAlgorithm) => {
    vibrate(BUTTON_PRESS);
    setAlgorithm(algo);
  };

  const handleKeyDown = (e: React.KeyboardEvent, currentIndex: number) => {
    let newIndex = currentIndex;

    switch (e.key) {
      case 'ArrowLeft':
      case 'ArrowUp':
        e.preventDefault();
        newIndex = currentIndex > 0 ? currentIndex - 1 : ALGORITHMS.length - 1;
        break;
      case 'ArrowRight':
      case 'ArrowDown':
        e.preventDefault();
        newIndex = currentIndex < ALGORITHMS.length - 1 ? currentIndex + 1 : 0;
        break;
      case 'Enter':
      case ' ':
        e.preventDefault();
        handleSelect(ALGORITHMS[currentIndex].id);
        return;
      default:
        return;
    }

    // Focus the new button
    const buttons = document.querySelectorAll('[role="radio"]');
    (buttons[newIndex] as HTMLElement)?.focus();
  };

  return (
    <div
      role="radiogroup"
      aria-label="Sort algorithm selector"
      className="flex flex-col md:flex-row gap-2"
    >
      {ALGORITHMS.map((algo, index) => {
        const isSelected = algorithm === algo.id;

        return (
          <motion.button
            key={algo.id}
            role="radio"
            aria-checked={isSelected}
            aria-label={`${algo.label}: ${algo.description}`}
            onClick={() => handleSelect(algo.id)}
            onKeyDown={(e) => handleKeyDown(e, index)}
            tabIndex={isSelected ? 0 : -1}
            className={`
              relative px-4 py-3 md:px-6 md:py-2 rounded-full text-sm
              border transition-all duration-200
              focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--neon-pink)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--bg)]
              active:scale-95
              ${
                isSelected
                  ? 'border-[var(--neon-pink)] text-[var(--bg)] font-semibold'
                  : 'border-[var(--bg-elevated)] text-[var(--text)] bg-[var(--bg-card)] hover:border-[var(--neon-pink)] hover:bg-[var(--bg-elevated)]'
              }
            `}
            style={{
              minWidth: '120px',
            }}
            whileTap={prefersReducedMotion ? {} : { scale: 0.95 }}
          >
            {/* Animated background for selected state */}
            {isSelected && (
              <motion.div
                layoutId="selectedBackground"
                className="absolute inset-0 bg-[var(--neon-pink)] rounded-full"
                transition={
                  prefersReducedMotion
                    ? { duration: 0 }
                    : { type: 'spring', stiffness: 300, damping: 30 }
                }
              />
            )}

            {/* Content */}
            <span className="relative z-10 flex items-center justify-center gap-2">
              <span className="text-base" aria-hidden="true">
                {algo.icon}
              </span>
              <span>{algo.label}</span>
              {isSelected && (
                <motion.span
                  initial={prefersReducedMotion ? false : { scale: 0, opacity: 0 }}
                  animate={{ scale: 1, opacity: 1 }}
                  transition={{ duration: prefersReducedMotion ? 0 : 0.2 }}
                  className="text-base"
                  aria-label="Selected"
                >
                  ✓
                </motion.span>
              )}
            </span>
          </motion.button>
        );
      })}
    </div>
  );
}
