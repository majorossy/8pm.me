'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Artist, Song } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import ArtistCard from '@/components/ArtistCard';
import SongCard from '@/components/SongCard';

interface HomePageContentProps {
  artists: Artist[];
  songs: Song[];
}

export default function HomePageContent({ artists, songs }: HomePageContentProps) {
  const { theme } = useTheme();
  const { setBreadcrumbs } = useBreadcrumbs();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';

  useEffect(() => {
    setBreadcrumbs([]);
  }, [setBreadcrumbs]);

  // Jamify/Spotify style
  if (isJamify) {
    // Dynamic greeting based on time of day
    const getGreeting = () => {
      const hour = new Date().getHours();
      if (hour < 12) return 'Good morning';
      if (hour < 18) return 'Good afternoon';
      return 'Good evening';
    };

    return (
      <div className="pb-8 max-w-[1800px]">
        {/* Hero section with gradient */}
        <section className="relative mb-6 md:mb-8 -mx-4 md:-mx-8 px-4 md:px-8 pt-6 md:pt-8 pb-8 md:pb-12 bg-gradient-to-b from-[#1DB954]/30 via-[#121212] to-[#121212]">
          <h1 className="text-2xl md:text-3xl font-bold text-white mb-2">
            {getGreeting()}
          </h1>
          <p className="text-sm md:text-base text-[#a7a7a7]">
            Stream live recordings from the Archive.org collection
          </p>
        </section>

        {/* Featured Artists */}
        <section className="mb-8 md:mb-10 px-4 md:px-8">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl md:text-2xl font-bold text-white">Featured Artists</h2>
            <Link href="/artists" className="text-xs md:text-sm font-bold text-[#a7a7a7] hover:underline uppercase tracking-wider">
              Show all
            </Link>
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 md:gap-6">
            {artists.slice(0, 6).map((artist) => (
              <ArtistCard key={artist.id} artist={artist} />
            ))}
          </div>
        </section>

        {/* Latest Recordings */}
        <section className="mb-8 md:mb-10 px-4 md:px-8">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl md:text-2xl font-bold text-white">Latest Recordings</h2>
          </div>
          <div className="bg-[#181818] rounded-lg p-3 md:p-4">
            {songs.map((song, index) => (
              <SongCard key={song.id} song={song} index={index + 1} />
            ))}
          </div>
        </section>
      </div>
    );
  }

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div className="pt-[70px] md:pt-[80px] px-4 md:px-8 max-w-[1400px] mx-auto">
        {/* Hero */}
        <section className="mb-8 md:mb-12">
          <div className="relative overflow-hidden py-8 md:py-12 px-4 md:px-8 bg-white border border-[#d4d0c8]">
            <div className="relative z-10">
              <h1 className="font-display text-2xl md:text-4xl lg:text-5xl font-extrabold mb-4 text-[#1a1a1a]">
                Welcome to EightPM
              </h1>
              <p className="text-sm md:text-base text-[#6b6b6b] max-w-xl mb-6">
                Explore thousands of live recordings from your favorite artists.
                Stream high-quality audio from Archive.org's vast collection.
              </p>
              <Link
                href="/artists"
                className="inline-block px-5 md:px-6 py-2.5 md:py-3 bg-[#e85d04] text-white font-display font-semibold text-sm hover:bg-[#d44d00] transition-colors"
              >
                Browse Artists
              </Link>
            </div>
          </div>
        </section>

        {/* Featured Artists */}
        <section className="mb-8 md:mb-12">
          <div className="flex items-center justify-between mb-4 md:mb-6 pb-3 md:pb-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-base md:text-lg font-bold text-[#1a1a1a]">
              Featured Artists
            </h2>
            <Link href="/artists" className="text-xs md:text-sm text-[#6b6b6b] hover:text-[#e85d04] transition-colors">
              View all →
            </Link>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            {artists.slice(0, 4).map((artist) => (
              <ArtistCard key={artist.id} artist={artist} />
            ))}
          </div>
        </section>

        {/* Latest Recordings */}
        <section className="mb-8 md:mb-12">
          <div className="flex items-center justify-between mb-4 md:mb-6 pb-3 md:pb-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-base md:text-lg font-bold text-[#1a1a1a]">
              Latest Recordings
            </h2>
          </div>
          <div className="space-y-1">
            {songs.map((song, index) => (
              <SongCard key={song.id} song={song} index={index + 1} />
            ))}
          </div>
        </section>
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div className="pt-20 md:pt-24 px-4 md:px-6 lg:px-12 max-w-[1400px] mx-auto">
      {/* Hero */}
      <section className="mb-10 md:mb-16">
        <div className="relative overflow-hidden py-10 md:py-16 px-4 md:px-8 lg:px-12 border border-neon-cyan/20">
          {/* Decorative elements - hidden on mobile */}
          <div className="hidden md:block absolute top-0 right-0 w-64 h-64 bg-neon-pink/10 blur-[100px]" />
          <div className="hidden md:block absolute bottom-0 left-0 w-64 h-64 bg-neon-cyan/10 blur-[100px]" />

          <div className="relative z-10">
            <div className="font-display text-[0.55rem] md:text-[0.6rem] text-neon-cyan uppercase tracking-[0.3em] md:tracking-[0.4em] mb-3 md:mb-4 flex items-center gap-2">
              <span className="opacity-50">//</span>
              Live Music Archive
            </div>
            <h1 className="font-display text-3xl md:text-4xl lg:text-6xl font-black mb-4 gradient-text-title">
              WELCOME TO<br />EIGHTPM
            </h1>
            <p className="text-sm md:text-base text-text-dim max-w-xl mb-6 md:mb-8">
              Explore thousands of live recordings from your favorite artists.
              Stream high-quality audio from Archive.org's vast collection.
            </p>
            <Link href="/artists" className="btn-neon px-6 md:px-8 py-3 md:py-4 font-display text-[0.65rem] md:text-xs uppercase tracking-[0.1em] inline-block">
              <span className="relative z-10">Browse Artists</span>
            </Link>
          </div>
        </div>
      </section>

      {/* Featured Artists */}
      <section className="mb-10 md:mb-16">
        <div className="flex items-center justify-between mb-6 md:mb-8 pb-3 md:pb-4 section-border">
          <h2 className="font-display text-[0.65rem] md:text-xs uppercase tracking-[0.2em] md:tracking-[0.3em] text-text-dim">
            <span className="text-neon-cyan">//</span> Featured Artists
          </h2>
          <Link href="/artists" className="text-[0.65rem] md:text-xs text-neon-cyan hover:neon-text-cyan transition-all uppercase tracking-wider">
            View all →
          </Link>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
          {artists.slice(0, 4).map((artist) => (
            <ArtistCard key={artist.id} artist={artist} />
          ))}
        </div>
      </section>

      {/* Latest Recordings */}
      <section className="mb-10 md:mb-16">
        <div className="flex items-center justify-between mb-6 md:mb-8 pb-3 md:pb-4 section-border">
          <h2 className="font-display text-[0.65rem] md:text-xs uppercase tracking-[0.2em] md:tracking-[0.3em] text-text-dim">
            <span className="text-neon-cyan">//</span> Latest Recordings
          </h2>
        </div>
        <div className="space-y-1">
          {songs.map((song, index) => (
            <SongCard key={song.id} song={song} index={index + 1} />
          ))}
        </div>
      </section>
    </div>
  );
}
