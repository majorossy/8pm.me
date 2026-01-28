'use client';

import Link from 'next/link';

interface LineupArtist {
  name: string;
  slug: string;
  songCount: number;
}

interface FestivalHeroProps {
  artists: LineupArtist[];
  onStartListening?: () => void;
}

export default function FestivalHero({ artists, onStartListening }: FestivalHeroProps) {
  // Sort by song count (most songs first)
  const lineupArtists = [...artists].sort((a, b) => b.songCount - a.songCount);

  // Calculate font size based on relative song count
  const maxSongs = Math.max(...lineupArtists.map(a => a.songCount));
  const minSongs = Math.min(...lineupArtists.map(a => a.songCount));
  const range = maxSongs - minSongs || 1;

  const getFontSize = (songCount: number) => {
    // Scale from 0.75rem (smallest) to 1.5rem (largest) on mobile
    // Scale from 1rem (smallest) to 2.25rem (largest) on desktop
    const ratio = (songCount - minSongs) / range;
    return {
      mobile: 0.75 + ratio * 0.75, // 0.75rem to 1.5rem
      desktop: 1 + ratio * 1.25,   // 1rem to 2.25rem
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
          <div className="flex flex-wrap items-baseline justify-center gap-x-2 md:gap-x-4 gap-y-2 text-[#e8dcc4] font-bold uppercase tracking-[1px] md:tracking-[2px]">
            {lineupArtists.map((artist, index) => {
              const fontSize = getFontSize(artist.songCount);
              return (
                <span key={artist.slug} className="flex items-baseline">
                  <Link
                    href={`/artists/${artist.slug}`}
                    className="hover:text-[#d4a060] transition-colors duration-200"
                    style={{
                      fontSize: `clamp(${fontSize.mobile}rem, ${fontSize.mobile + (fontSize.desktop - fontSize.mobile) * 0.5}rem, ${fontSize.desktop}rem)`,
                    }}
                  >
                    {artist.name}
                  </Link>
                  {index < lineupArtists.length - 1 && (
                    <span className="text-[#d4a060] ml-2 md:ml-4 text-base">&#9733;</span>
                  )}
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
