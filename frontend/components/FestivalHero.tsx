'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import Link from 'next/link';
import { motion } from 'framer-motion';
import { useLineStartDetection } from '@/hooks/useLineStartDetection';
import { useFestivalSort } from '@/hooks/useFestivalSort';
import AlgorithmSelector from '@/components/AlgorithmSelector';

interface LineupArtist {
  name: string;
  slug: string;
  songCount: number;
  albumCount: number;
  totalShows?: number;
  mostPlayedTrack?: string;
  totalRecordings?: number;
  totalHours?: number;
  totalVenues?: number;
  formationYear?: number;
}

interface FestivalHeroProps {
  artists: LineupArtist[];
  onStartListening?: () => void;
}

interface ArtistStatsTooltipProps {
  totalShows?: number;
  mostPlayedTrack?: string;
  totalRecordings?: number;
  totalHours?: number;
  totalVenues?: number;
  formationYear?: number;
}

function ArtistStatsTooltip({
  totalShows,
  mostPlayedTrack,
  totalRecordings,
  totalHours,
  totalVenues,
  formationYear
}: ArtistStatsTooltipProps) {
  // Debug logging
  console.log('[ArtistStatsTooltip]', {
    totalShows,
    mostPlayedTrack,
    totalRecordings,
    totalHours,
    totalVenues,
    formationYear
  });

  // Don't render if no stats available
  const hasStats = totalShows || mostPlayedTrack || totalRecordings || totalHours || totalVenues || formationYear;
  if (!hasStats) {
    console.log('[ArtistStatsTooltip] No stats, not rendering');
    return null;
  }

  return (
    <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 ease-out pointer-events-none z-50 hidden md:block">
      <div className="bg-[var(--bg)] border border-[var(--neon-pink)]/60 rounded-lg px-4 py-3 shadow-2xl w-max">
        {/* Grid layout: 2 columns x 3 rows */}
        <div className="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
          {/* Row 1 */}
          {totalRecordings !== undefined && (
            <div className="flex items-center gap-1.5 whitespace-nowrap">
              <span className="text-[var(--neon-pink)]">🎵</span>
              <span className="text-[var(--text)]">{totalRecordings.toLocaleString()} Recordings</span>
            </div>
          )}
          {totalHours !== undefined && (
            <div className="flex items-center gap-1.5 whitespace-nowrap">
              <span className="text-[var(--neon-pink)]">⏱️</span>
              <span className="text-[var(--text)]">{totalHours.toLocaleString()} Hours</span>
            </div>
          )}

          {/* Row 2 */}
          {totalShows !== undefined && (
            <div className="flex items-center gap-1.5 whitespace-nowrap">
              <span className="text-[var(--neon-pink)]">⭐</span>
              <span className="text-[var(--text)]">{totalShows.toLocaleString()} Shows</span>
            </div>
          )}
          {totalVenues !== undefined && (
            <div className="flex items-center gap-1.5 whitespace-nowrap">
              <span className="text-[var(--neon-pink)]">🏛️</span>
              <span className="text-[var(--text)]">{totalVenues.toLocaleString()} Venues</span>
            </div>
          )}

          {/* Row 3 */}
          {formationYear !== undefined && (
            <div className="flex items-center gap-1.5 whitespace-nowrap">
              <span className="text-[var(--neon-pink)]">📅</span>
              <span className="text-[var(--text)]">Since &apos;{formationYear.toString().slice(-2)}</span>
            </div>
          )}
          {mostPlayedTrack && (
            <div className="flex items-center gap-1.5 whitespace-nowrap">
              <span className="text-[var(--neon-pink)]">🎸</span>
              <span className="text-[var(--text)] truncate max-w-[120px]" title={mostPlayedTrack}>
                {mostPlayedTrack}
              </span>
            </div>
          )}
        </div>
      </div>
      {/* Arrow pointer */}
      <div className="absolute top-full left-1/2 -translate-x-1/2 w-0 h-0 border-l-[6px] border-l-transparent border-r-[6px] border-r-transparent border-t-[6px] border-t-[var(--neon-pink)]/60"></div>
    </div>
  );
}

export default function FestivalHero({ artists, onStartListening }: FestivalHeroProps) {
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

  // Get sorted artists and algorithm from context
  const { sortedArtists, algorithm } = useFestivalSort();

  // Use sortedArtists from context instead of local sorting
  const lineupArtists = sortedArtists.length > 0 ? sortedArtists : artists;

  // Detect line starts to hide star separators via direct DOM manipulation (no flicker)
  const { containerRef, setItemRef, setStarRef, detectAndHideLineStarts } = useLineStartDetection(lineupArtists.length);

  // Debounce star detection after animations
  const detectionTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const handleLayoutAnimationComplete = useCallback(() => {
    // Clear any pending detection
    if (detectionTimeoutRef.current) {
      clearTimeout(detectionTimeoutRef.current);
    }

    // Debounce: wait for all animations to complete before detecting
    detectionTimeoutRef.current = setTimeout(() => {
      // Use double RAF to ensure DOM is fully settled
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          detectAndHideLineStarts();
          detectionTimeoutRef.current = null;
        });
      });
    }, 50); // Small delay after last animation completes
  }, [detectAndHideLineStarts]);

  const getFontSize = (artist: LineupArtist) => {
    let value: number;
    let allValues: number[];

    switch (algorithm) {
      case 'songVersions':
        value = artist.songCount || 0;
        allValues = lineupArtists.map(a => a.songCount || 0);
        break;
      case 'shows':
        value = artist.totalShows || 0;
        allValues = lineupArtists.map(a => a.totalShows || 0);
        break;
      case 'hours':
        value = artist.totalHours || 0;
        allValues = lineupArtists.map(a => a.totalHours || 0);
        break;
      default:
        value = artist.songCount || 0;
        allValues = lineupArtists.map(a => a.songCount || 0);
    }

    // Special case: zero values get their own smallest tier
    if (value === 0) {
      const mobileSize = 0.5;
      const desktopSize = 0.9;
      const slope = (desktopSize - mobileSize) / 0.6;
      const base = mobileSize - (slope * 0.2);
      return {
        min: mobileSize,
        max: desktopSize,
        slope: slope,
        base: base,
      };
    }

    // Filter out zeros for tier calculation (they have their own tier)
    const nonZeroValues = allValues.filter(v => v > 0);
    const sortedValues = [...nonZeroValues].sort((a, b) => b - a);
    const totalArtists = sortedValues.length || 1;

    // Find artist's rank (0 = highest)
    const rank = sortedValues.indexOf(value);

    // Calculate tier (0-3) based on rank quartiles
    // Tier 0: Headliner (top 10%)
    // Tier 1: Main Stage (10-35%)
    // Tier 2: Supporting (35-65%)
    // Tier 3: Opener (bottom 35%)
    let tier: number;
    const percentile = rank / totalArtists;

    if (percentile < 0.10) tier = 0;       // Headliner
    else if (percentile < 0.35) tier = 1;  // Main Stage
    else if (percentile < 0.65) tier = 2;  // Supporting
    else tier = 3;                          // Opener

    // Fluid size ranges - constrained to fit container
    // Mobile (320px):  0.65rem to 1.4rem
    // Desktop (1280px): 1.4rem to 3.5rem
    const tierSizes = {
      mobile:  [1.4, 1.05, 0.8, 0.65],   // Headliner -> Opener
      desktop: [3.5, 2.6, 1.9, 1.4],     // Fits within max-w-6xl
    };

    // Get artists in the same tier to calculate within-tier variation
    const tierArtists = nonZeroValues.filter(v => {
      const p = sortedValues.indexOf(v) / totalArtists;
      if (tier === 0) return p < 0.10;
      if (tier === 1) return p >= 0.10 && p < 0.35;
      if (tier === 2) return p >= 0.35 && p < 0.65;
      return p >= 0.65;
    });

    const tierMin = Math.min(...tierArtists);
    const tierMax = Math.max(...tierArtists);
    const tierRange = tierMax - tierMin || 1;
    const withinTierRatio = (value - tierMin) / tierRange;

    // Interpolate within tier (top of tier to bottom of tier)
    const nextTier = Math.min(tier + 1, 3);
    const mobileSize = tierSizes.mobile[tier] -
      (tierSizes.mobile[tier] - tierSizes.mobile[nextTier]) * (1 - withinTierRatio) * 0.5;
    const desktopSize = tierSizes.desktop[tier] -
      (tierSizes.desktop[tier] - tierSizes.desktop[nextTier]) * (1 - withinTierRatio) * 0.5;

    // Fluid typography: clamp(min, calc(baseRem + slopeVw), max)
    // At 320px: 1vw = 3.2px = 0.2rem (assuming 16px base)
    // At 1280px: 1vw = 12.8px = 0.8rem
    // We want: base + slope * 0.2 = mobileSize
    //          base + slope * 0.8 = desktopSize
    // Solving: slope = (desktop - mobile) / 0.6
    //          base = mobile - slope * 0.2
    const slope = (desktopSize - mobileSize) / 0.6;
    const base = mobileSize - (slope * 0.2);

    return {
      min: Math.max(0.65, mobileSize),
      max: Math.max(1.4, desktopSize),
      slope: slope,
      base: base,
    };
  };

  return (
    <section
      className="festival-hero-section flex flex-col items-center relative overflow-hidden pt-0.5 pb-4 px-4 md:pt-1 md:pb-6 md:px-10"
    >
      {/* Decorative stars */}
      <span className="absolute top-[15%] left-[10%] text-4xl md:text-6xl text-[var(--neon-pink)] opacity-40 select-none hidden sm:block">
        &#9733;
      </span>
      <span className="absolute top-[20%] right-[15%] text-3xl md:text-5xl text-[var(--neon-pink)] opacity-30 select-none">
        &#9733;
      </span>
      <span className="absolute top-[60%] left-[5%] text-2xl md:text-4xl text-[var(--neon-pink)] opacity-25 select-none hidden md:block">
        &#9733;
      </span>
      <span className="absolute top-[70%] right-[8%] text-3xl md:text-5xl text-[var(--neon-pink)] opacity-35 select-none hidden sm:block">
        &#9733;
      </span>
      <span className="absolute top-[40%] left-[85%] text-xl md:text-3xl text-[var(--neon-pink)] opacity-20 select-none hidden lg:block">
        &#9733;
      </span>
      <span className="absolute top-[85%] left-[20%] text-2xl md:text-4xl text-[var(--neon-pink)] opacity-30 select-none hidden md:block">
        &#9733;
      </span>

      {/* Main content */}
      <div className="flex flex-col items-center text-center z-10 max-w-6xl">
        {/* Top decoration */}
        <div className="text-[var(--neon-pink)] text-base md:text-xl tracking-[4px] md:tracking-[8px] uppercase mb-2 md:mb-3">
          &#9733; Live Jamband Music &#9733;
        </div>

        {/* Main title */}
        <h1 className="text-5xl sm:text-7xl md:text-8xl lg:text-[96px] font-bold text-[var(--text)] tracking-[3px] md:tracking-[6px] uppercase leading-none mb-2 md:mb-3">
          8pm.me
        </h1>

        {/* Subtitle */}
        <div className="text-[var(--text-dim)] text-sm md:text-lg tracking-[2px] md:tracking-[4px] uppercase mb-4 md:mb-6 px-4">
          The Idea is Archive.org by Album
        </div>

        {/* Algorithm Selector */}
        <div className="mb-6 flex justify-center">
          <AlgorithmSelector />
        </div>

        {/* Tonight's lineup */}
        <div className="mb-4 md:mb-6 w-full px-2">
          <div className="text-[var(--neon-pink)] text-xs md:text-sm tracking-[3px] md:tracking-[6px] uppercase mb-3 md:mb-4">
            The Lineup
          </div>
          <div
            ref={containerRef}
            className="flex flex-wrap items-baseline justify-center gap-x-2 md:gap-x-4 gap-y-2 text-[var(--text)] font-bold uppercase tracking-[1px] md:tracking-[2px]"
          >
            {lineupArtists.map((artist, index) => {
              const fontSize = getFontSize(artist);
              return (
                <motion.span
                  key={artist.slug}
                  ref={setItemRef(index)}
                  layout
                  transition={{
                    layout: {
                      duration: prefersReducedMotion ? 0 : 0.4,
                      ease: 'easeOut',
                    },
                  }}
                  onLayoutAnimationComplete={handleLayoutAnimationComplete}
                  className="flex items-baseline whitespace-nowrap"
                >
                  {/* Star separator - starts invisible, JS reveals appropriate ones after measurement */}
                  {index > 0 && (
                    <span
                      ref={setStarRef(index)}
                      className="text-[var(--neon-pink)] mr-2 md:mr-4 text-base invisible"
                    >
                      &#9733;
                    </span>
                  )}
                  <span className="relative group inline-block">
                    <Link
                      href={`/artists/${artist.slug}`}
                      className="hover:text-[var(--neon-pink)] transition-colors duration-200"
                      style={{
                        fontSize: `clamp(${fontSize.min}rem, calc(${fontSize.base.toFixed(3)}rem + ${fontSize.slope.toFixed(3)}vw), ${fontSize.max}rem)`,
                      }}
                    >
                      {artist.name}
                    </Link>
                    <ArtistStatsTooltip
                      totalShows={artist.totalShows}
                      mostPlayedTrack={artist.mostPlayedTrack}
                      totalRecordings={artist.totalRecordings}
                      totalHours={artist.totalHours}
                      totalVenues={artist.totalVenues}
                      formationYear={artist.formationYear}
                    />
                  </span>
                </motion.span>
              );
            })}
          </div>
        </div>

        {/* Stats */}
        <div className="flex flex-wrap justify-center gap-4 md:gap-8 text-center">
          <div>
            <div className="text-xl md:text-3xl font-bold text-[var(--text)]">10,000+</div>
            <div className="text-xs text-[var(--text-dim)] uppercase tracking-[1px] md:tracking-[2px]">
              Live Shows
            </div>
          </div>
          <div>
            <div className="text-xl md:text-3xl font-bold text-[var(--text)]">50+</div>
            <div className="text-xs text-[var(--text-dim)] uppercase tracking-[1px] md:tracking-[2px]">
              Years of Music
            </div>
          </div>
          <div>
            <div className="text-xl md:text-3xl font-bold text-[var(--text)]">Free</div>
            <div className="text-xs text-[var(--text-dim)] uppercase tracking-[1px] md:tracking-[2px]">
              Forever
            </div>
          </div>
        </div>
      </div>

      {/* Bottom decoration */}
      <div className="absolute bottom-3 md:bottom-4 text-[var(--text-dim)] text-xs tracking-[2px] md:tracking-[4px] uppercase opacity-60">
        Powered by Archive.org
      </div>
    </section>
  );
}
