'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Artist, Song } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import ArtistCard from '@/components/ArtistCard';
import SongCard from '@/components/SongCard';

interface HomePageContentProps {
  artists: Artist[];
  songs: Song[];
}

export default function HomePageContent({ artists, songs }: HomePageContentProps) {
  const { setBreadcrumbs } = useBreadcrumbs();

  useEffect(() => {
    setBreadcrumbs([]);
  }, [setBreadcrumbs]);

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
      <section className="relative mb-6 md:mb-8 -mx-4 md:-mx-8 px-4 md:px-8 pt-6 md:pt-8 pb-8 md:pb-12 bg-gradient-to-b from-[#d4a060]/30 via-[#1c1a17] to-[#1c1a17]">
        <h1 className="text-2xl md:text-3xl font-bold text-white mb-2">
          {getGreeting()}
        </h1>
        <p className="text-sm md:text-base text-[#8a8478]">
          Stream live recordings from the Archive.org collection
        </p>
      </section>

      {/* Featured Artists */}
      <section className="mb-8 md:mb-10 px-4 md:px-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl md:text-2xl font-bold text-white">Featured Artists</h2>
          <Link href="/artists" className="text-xs md:text-sm font-bold text-[#8a8478] hover:underline uppercase tracking-wider">
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
        <div className="bg-[#252220] rounded-lg p-3 md:p-4">
          {songs.map((song, index) => (
            <SongCard key={song.id} song={song} index={index + 1} />
          ))}
        </div>
      </section>
    </div>
  );
}
