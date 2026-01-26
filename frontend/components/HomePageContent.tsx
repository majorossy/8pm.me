'use client';

import Link from 'next/link';
import { Artist, Song } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
import ArtistCard from '@/components/ArtistCard';
import SongCard from '@/components/SongCard';

interface HomePageContentProps {
  artists: Artist[];
  songs: Song[];
}

export default function HomePageContent({ artists, songs }: HomePageContentProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div className="pt-[80px] px-8 max-w-[1400px] mx-auto">
        {/* Hero */}
        <section className="mb-12">
          <div className="relative overflow-hidden py-12 px-8 bg-white border border-[#d4d0c8]">
            <div className="relative z-10">
              <h1 className="font-display text-4xl md:text-5xl font-extrabold mb-4 text-[#1a1a1a]">
                Welcome to EightPM
              </h1>
              <p className="text-[#6b6b6b] max-w-xl mb-6">
                Explore thousands of live recordings from your favorite artists.
                Stream high-quality audio from Archive.org's vast collection.
              </p>
              <Link
                href="/artists"
                className="inline-block px-6 py-3 bg-[#e85d04] text-white font-display font-semibold text-sm hover:bg-[#d44d00] transition-colors"
              >
                Browse Artists
              </Link>
            </div>
          </div>
        </section>

        {/* Featured Artists */}
        <section className="mb-12">
          <div className="flex items-center justify-between mb-6 pb-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-lg font-bold text-[#1a1a1a]">
              Featured Artists
            </h2>
            <Link href="/artists" className="text-sm text-[#6b6b6b] hover:text-[#e85d04] transition-colors">
              View all →
            </Link>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            {artists.slice(0, 4).map((artist) => (
              <ArtistCard key={artist.id} artist={artist} />
            ))}
          </div>
        </section>

        {/* Latest Recordings */}
        <section className="mb-12">
          <div className="flex items-center justify-between mb-6 pb-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-lg font-bold text-[#1a1a1a]">
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
    <div className="pt-24 px-6 lg:px-12 max-w-[1400px] mx-auto">
      {/* Hero */}
      <section className="mb-16">
        <div className="relative overflow-hidden py-16 px-8 md:px-12 border border-neon-cyan/20">
          {/* Decorative elements */}
          <div className="absolute top-0 right-0 w-64 h-64 bg-neon-pink/10 blur-[100px]" />
          <div className="absolute bottom-0 left-0 w-64 h-64 bg-neon-cyan/10 blur-[100px]" />

          <div className="relative z-10">
            <div className="font-display text-[0.6rem] text-neon-cyan uppercase tracking-[0.4em] mb-4 flex items-center gap-2">
              <span className="opacity-50">//</span>
              Live Music Archive
            </div>
            <h1 className="font-display text-4xl md:text-6xl font-black mb-4 gradient-text-title">
              WELCOME TO<br />EIGHTPM
            </h1>
            <p className="text-text-dim max-w-xl mb-8">
              Explore thousands of live recordings from your favorite artists.
              Stream high-quality audio from Archive.org's vast collection.
            </p>
            <Link href="/artists" className="btn-neon px-8 py-4 font-display text-xs uppercase tracking-[0.1em] inline-block">
              <span className="relative z-10">Browse Artists</span>
            </Link>
          </div>
        </div>
      </section>

      {/* Featured Artists */}
      <section className="mb-16">
        <div className="flex items-center justify-between mb-8 pb-4 section-border">
          <h2 className="font-display text-xs uppercase tracking-[0.3em] text-text-dim">
            <span className="text-neon-cyan">//</span> Featured Artists
          </h2>
          <Link href="/artists" className="text-xs text-neon-cyan hover:neon-text-cyan transition-all uppercase tracking-wider">
            View all →
          </Link>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {artists.slice(0, 4).map((artist) => (
            <ArtistCard key={artist.id} artist={artist} />
          ))}
        </div>
      </section>

      {/* Latest Recordings */}
      <section className="mb-16">
        <div className="flex items-center justify-between mb-8 pb-4 section-border">
          <h2 className="font-display text-xs uppercase tracking-[0.3em] text-text-dim">
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
