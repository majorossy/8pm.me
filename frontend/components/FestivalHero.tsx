'use client';

import Link from 'next/link';
import { useLineStartDetection } from '@/hooks/useLineStartDetection';

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
    <span className="absolute bottom-full left-1/2 -translate-x-1/2 mb-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 ease-out pointer-events-none z-50 hidden md:inline">
      <div className="bg-[#1c1a17] border border-[#d4a060]/60 rounded-md px-3 py-2 shadow-2xl">
        {/* Grid layout: 2 columns x 3 rows */}
        <div className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
          {/* Row 1 */}
          {totalRecordings !== undefined && (
            <div className="flex items-center gap-1.5">
              <span className="text-[#d4a060]">🎵</span>
              <span className="text-[#e8dcc4]">{totalRecordings.toLocaleString()} Recordings</span>
            </div>
          )}
          {totalHours !== undefined && (
            <div className="flex items-center gap-1.5">
              <span className="text-[#d4a060]">⏱️</span>
              <span className="text-[#e8dcc4]">{totalHours.toLocaleString()} Hours</span>
            </div>
          )}

          {/* Row 2 */}
          {totalShows !== undefined && (
            <div className="flex items-center gap-1.5">
              <span className="text-[#d4a060]">⭐</span>
              <span className="text-[#e8dcc4]">{totalShows.toLocaleString()} Shows</span>
            </div>
          )}
          {totalVenues !== undefined && (
            <div className="flex items-center gap-1.5">
              <span className="text-[#d4a060]">🏛️</span>
              <span className="text-[#e8dcc4]">{totalVenues.toLocaleString()} Venues</span>
            </div>
          )}

          {/* Row 3 */}
          {formationYear !== undefined && (
            <div className="flex items-center gap-1.5">
              <span className="text-[#d4a060]">📅</span>
              <span className="text-[#e8dcc4]">Since &apos;{formationYear.toString().slice(-2)}</span>
            </div>
          )}
          {mostPlayedTrack && (
            <div className="flex items-center gap-1.5">
              <span className="text-[#d4a060]">🎸</span>
              <span className="text-[#e8dcc4] truncate max-w-[120px]" title={mostPlayedTrack}>
                {mostPlayedTrack}
              </span>
            </div>
          )}
        </div>
      </div>
      {/* Arrow pointer */}
      <div className="absolute top-full left-1/2 -translate-x-1/2 w-0 h-0 border-l-[6px] border-l-transparent border-r-[6px] border-r-transparent border-t-[6px] border-t-[#d4a060]/60"></div>
    </span>
  );
}

export default function FestivalHero({ artists, onStartListening }: FestivalHeroProps) {
  // Calculate min/max for both songs and albums
  const maxSongs = Math.max(...artists.map(a => a.songCount || 0));
  const minSongs = Math.min(...artists.map(a => a.songCount || 0));
  const maxAlbums = Math.max(...artists.map(a => a.albumCount || 0));
  const minAlbums = Math.min(...artists.map(a => a.albumCount || 0));
  const songRange = maxSongs - minSongs || 1;
  const albumRange = maxAlbums - minAlbums || 1;

  // Sort by combined score (75% products, 25% albums)
  const lineupArtists = [...artists].sort((a, b) => {
    const scoreA = ((a.songCount || 0) * 0.75) + ((a.albumCount || 0) * 0.25);
    const scoreB = ((b.songCount || 0) * 0.75) + ((b.albumCount || 0) * 0.25);
    return scoreB - scoreA;
  });

  // Detect line starts to hide star separators via direct DOM manipulation (no flicker)
  const { containerRef, setItemRef, setStarRef } = useLineStartDetection(lineupArtists.length);

  const getFontSize = (songCount: number, albumCount: number) => {
    // Normalize both metrics to 0-1 range
    const songRatio = (songCount - minSongs) / songRange;
    const albumRatio = (albumCount - minAlbums) / albumRange;

    // Weighted combination: 75% products, 25% albums
    const combinedScore = (songRatio * 0.75) + (albumRatio * 0.25);

    // Scale from 0.6rem (smallest) to 1.8rem (largest) on mobile
    // Scale from 0.8rem (smallest) to 2.4rem (largest) on desktop
    return {
      mobile: 0.6 + combinedScore * 1.2,    // 0.6rem to 1.8rem (3x difference)
      desktop: 0.8 + combinedScore * 1.6,   // 0.8rem to 2.4rem (3x difference)
    };
  };

  return (
    <section
      className="flex flex-col items-center relative overflow-hidden pt-0.5 pb-4 px-4 md:pt-1 md:pb-6 md:px-10"
      style={{
        background: `
          radial-gradient(ellipse at 50% 120%, rgba(212,120,60,0.12) 0%, transparent 50%),
          radial-gradient(ellipse at 30% 80%, rgba(212,100,40,0.06) 0%, transparent 40%),
          radial-gradient(ellipse at 70% 90%, rgba(180,100,40,0.05) 0%, transparent 35%),
          linear-gradient(180deg, #1c1a17 0%, #1e1c19 100%)
        `,
      }}
    >
      {/* Decorative stars */}
      <span className="absolute top-[15%] left-[10%] text-4xl md:text-6xl text-[#d4a060] opacity-40 select-none hidden sm:block">
        &#9733;
      </span>
      <span className="absolute top-[20%] right-[15%] text-3xl md:text-5xl text-[#d4a060] opacity-30 select-none">
        &#9733;
      </span>
      <span className="absolute top-[60%] left-[5%] text-2xl md:text-4xl text-[#d4a060] opacity-25 select-none hidden md:block">
        &#9733;
      </span>
      <span className="absolute top-[70%] right-[8%] text-3xl md:text-5xl text-[#d4a060] opacity-35 select-none hidden sm:block">
        &#9733;
      </span>
      <span className="absolute top-[40%] left-[85%] text-xl md:text-3xl text-[#d4a060] opacity-20 select-none hidden lg:block">
        &#9733;
      </span>
      <span className="absolute top-[85%] left-[20%] text-2xl md:text-4xl text-[#d4a060] opacity-30 select-none hidden md:block">
        &#9733;
      </span>

      {/* Main content */}
      <div className="flex flex-col items-center text-center z-10 max-w-4xl">
        {/* Top decoration */}
        <div className="text-[#d4a060] text-base md:text-xl tracking-[4px] md:tracking-[8px] uppercase mb-2 md:mb-3">
          &#9733; Live From The Archive &#9733;
        </div>

        {/* Main title */}
        <h1 className="text-5xl sm:text-7xl md:text-8xl lg:text-[96px] font-bold text-[#e8dcc4] tracking-[3px] md:tracking-[6px] uppercase leading-none mb-2 md:mb-3">
          Campfire
          <br />
          Tapes
        </h1>

        {/* Subtitle */}
        <div className="text-[#8a8478] text-sm md:text-lg tracking-[2px] md:tracking-[4px] uppercase mb-4 md:mb-6 px-4">
          Streaming The Grateful Dead &amp; Beyond
        </div>

        {/* Tonight's lineup */}
        <div className="mb-4 md:mb-6 w-full px-2">
          <div className="text-[#d4a060] text-xs md:text-sm tracking-[3px] md:tracking-[6px] uppercase mb-3 md:mb-4">
            Tonight&apos;s Lineup
          </div>
          <div
            ref={containerRef}
            className="flex flex-wrap items-baseline justify-center gap-x-2 md:gap-x-4 gap-y-2 text-[#e8dcc4] font-bold uppercase tracking-[1px] md:tracking-[2px]"
          >
            {lineupArtists.map((artist, index) => {
              const fontSize = getFontSize(artist.songCount || 0, artist.albumCount || 0);
              return (
                <span
                  key={artist.slug}
                  ref={setItemRef(index)}
                  className="flex items-baseline whitespace-nowrap"
                >
                  {/* Star separator - starts invisible, JS reveals appropriate ones after measurement */}
                  {index > 0 && (
                    <span
                      ref={setStarRef(index)}
                      className="text-[#d4a060] mr-2 md:mr-4 text-base invisible"
                    >
                      &#9733;
                    </span>
                  )}
                  <span className="relative group">
                    <Link
                      href={`/artists/${artist.slug}`}
                      className="hover:text-[#d4a060] transition-colors duration-200"
                      style={{
                        fontSize: `clamp(${fontSize.mobile}rem, ${fontSize.mobile + (fontSize.desktop - fontSize.mobile) * 0.5}rem, ${fontSize.desktop}rem)`,
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
                    {artist.name === 'STS9' && (
                      <span className="text-red-500 text-xs" style={{display: 'none'}}>
                        DEBUG: {JSON.stringify({
                          totalShows: artist.totalShows,
                          totalRecordings: artist.totalRecordings,
                          totalHours: artist.totalHours,
                          totalVenues: artist.totalVenues
                        })}
                      </span>
                    )}
                  </span>
                </span>
              );
            })}
          </div>
        </div>

        {/* CTA buttons */}
        <div className="flex flex-col sm:flex-row gap-3 md:gap-4 mb-4 md:mb-6 w-full sm:w-auto px-4 sm:px-0">
          <button
            onClick={onStartListening}
            className="px-6 md:px-10 py-3 md:py-4 bg-[#d4a060] text-[#1c1a17] font-bold text-sm md:text-base uppercase tracking-[2px] md:tracking-[3px] rounded-full hover:bg-[#e8b470] transition-colors duration-200"
          >
            Start Listening
          </button>
          <Link
            href="/artists"
            className="px-6 md:px-10 py-3 md:py-4 border-2 border-[#d4a060] text-[#d4a060] font-bold text-sm md:text-base uppercase tracking-[2px] md:tracking-[3px] rounded-full hover:bg-[#d4a060] hover:text-[#1c1a17] transition-colors duration-200 text-center"
          >
            Browse Artists
          </Link>
        </div>

        {/* Stats */}
        <div className="flex flex-wrap justify-center gap-4 md:gap-8 text-center">
          <div>
            <div className="text-xl md:text-3xl font-bold text-[#e8dcc4]">10,000+</div>
            <div className="text-xs text-[#8a8478] uppercase tracking-[1px] md:tracking-[2px]">
              Live Shows
            </div>
          </div>
          <div>
            <div className="text-xl md:text-3xl font-bold text-[#e8dcc4]">50+</div>
            <div className="text-xs text-[#8a8478] uppercase tracking-[1px] md:tracking-[2px]">
              Years of Music
            </div>
          </div>
          <div>
            <div className="text-xl md:text-3xl font-bold text-[#e8dcc4]">Free</div>
            <div className="text-xs text-[#8a8478] uppercase tracking-[1px] md:tracking-[2px]">
              Forever
            </div>
          </div>
        </div>
      </div>

      {/* Bottom decoration */}
      <div className="absolute bottom-3 md:bottom-4 text-[#8a8478] text-xs tracking-[2px] md:tracking-[4px] uppercase opacity-60">
        Powered by Archive.org
      </div>
    </section>
  );
}
