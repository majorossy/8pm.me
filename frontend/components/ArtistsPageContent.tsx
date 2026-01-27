'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Artist, Album } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import AlbumCarousel from '@/components/AlbumCarousel';

interface ArtistWithAlbums extends Artist {
  albums: Album[];
}

interface ArtistsPageContentProps {
  artists: ArtistWithAlbums[];
}

export default function ArtistsPageContent({ artists }: ArtistsPageContentProps) {
  const { theme } = useTheme();
  const { setBreadcrumbs } = useBreadcrumbs();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';

  useEffect(() => {
    // Home page - no breadcrumbs needed
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
        <section className="relative mb-6 md:mb-8 px-4 md:px-8 pt-6 md:pt-8 pb-8 md:pb-12 bg-gradient-to-b from-[#1DB954]/30 via-[#121212] to-[#121212]">
          <h1 className="text-2xl md:text-3xl font-bold text-white mb-2">
            {getGreeting()}
          </h1>
          <p className="text-sm md:text-base text-[#a7a7a7]">
            Stream live recordings from the Archive.org collection
          </p>
        </section>

        {/* Artists sections */}
        <div className="space-y-8 md:space-y-12 px-4 md:px-8">
          {artists.map((artist) => (
            <section key={artist.id}>
              {/* Artist header */}
              <div className="flex items-center gap-3 md:gap-4 mb-4">
                <Link href={`/artists/${artist.slug}`} className="flex items-center gap-3 md:gap-4 group">
                  {/* Artist avatar */}
                  <div className="w-10 h-10 md:w-12 md:h-12 rounded-full overflow-hidden bg-[#282828] flex-shrink-0">
                    {artist.image && !artist.image.includes('default') ? (
                      <img
                        src={artist.image}
                        alt={artist.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <span className="font-bold text-base md:text-lg text-[#535353]">
                          {artist.name.charAt(0)}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Artist name */}
                  <div className="min-w-0">
                    <h2 className="text-lg md:text-xl font-bold text-white group-hover:underline truncate">
                      {artist.name}
                    </h2>
                    <p className="text-xs md:text-sm text-[#a7a7a7]">
                      {artist.albums.length} {artist.albums.length === 1 ? 'album' : 'albums'}
                    </p>
                  </div>
                </Link>

                {/* View all link */}
                <Link
                  href={`/artists/${artist.slug}`}
                  className="ml-auto text-xs md:text-sm font-bold text-[#a7a7a7] hover:underline uppercase tracking-wider flex-shrink-0"
                >
                  Show all
                </Link>
              </div>

              {/* Albums carousel */}
              <AlbumCarousel albums={artist.albums} artistSlug={artist.slug} />
            </section>
          ))}
        </div>
      </div>
    );
  }

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div className="pt-[70px] md:pt-[80px] px-4 md:px-8 max-w-[1400px] mx-auto">
        {/* Artists list */}
        <div className="space-y-8 md:space-y-12">
          {artists.map((artist) => (
            <section key={artist.id} className="relative">
              {/* Artist header */}
              <div className="flex items-center gap-3 md:gap-4 mb-4 md:mb-6 pb-3 md:pb-4 border-b border-[#d4d0c8]">
                <Link href={`/artists/${artist.slug}`} className="flex items-center gap-3 md:gap-4 group">
                  {/* Artist avatar */}
                  <div className="w-11 h-11 md:w-14 md:h-14 rounded-full overflow-hidden bg-[#e8e4dc] border border-[#d4d0c8] flex-shrink-0">
                    {artist.image && !artist.image.includes('default') ? (
                      <img
                        src={artist.image}
                        alt={artist.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <span className="font-display text-lg md:text-xl font-bold text-[#6b6b6b]">
                          {artist.name.charAt(0)}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Artist name and stats */}
                  <div className="min-w-0">
                    <h2 className="font-display text-lg md:text-xl font-bold text-[#1a1a1a] group-hover:text-[#e85d04] transition-colors truncate">
                      {artist.name}
                    </h2>
                    <p className="text-xs md:text-sm text-[#6b6b6b]">
                      <span className="text-[#e85d04] font-medium">{artist.albums.length}</span>
                      {' '}{artist.albums.length === 1 ? 'album' : 'albums'}
                      {artist.songCount && (
                        <span className="hidden sm:inline">
                          {' · '}
                          <span className="text-[#e85d04] font-medium">{artist.songCount}</span>
                          {' '}recordings
                        </span>
                      )}
                    </p>
                  </div>
                </Link>

                {/* View all link */}
                <Link
                  href={`/artists/${artist.slug}`}
                  className="ml-auto text-xs md:text-sm text-[#6b6b6b] hover:text-[#e85d04] transition-colors flex-shrink-0"
                >
                  View all →
                </Link>
              </div>

              {/* Albums carousel */}
              <AlbumCarousel albums={artist.albums} artistSlug={artist.slug} />
            </section>
          ))}
        </div>
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div className="pt-20 md:pt-24 px-4 md:px-6 lg:px-12 max-w-[1400px] mx-auto">
      {/* Breadcrumb */}
      <div className="text-[0.6rem] md:text-[0.65rem] text-text-dim uppercase tracking-[0.1em] mb-6 md:mb-8">
        <Link href="/" className="hover:text-neon-cyan transition-colors">Home</Link>
        {' / '}
        <span className="text-white">Artists</span>
      </div>

      {/* Page title */}
      <div className="mb-8 md:mb-12">
        <div className="font-display text-[0.55rem] md:text-[0.6rem] text-neon-cyan uppercase tracking-[0.3em] md:tracking-[0.4em] mb-3 md:mb-4 flex items-center gap-2">
          <span className="opacity-50">//</span>
          Browse Collection
        </div>
        <h1 className="font-display text-3xl md:text-4xl lg:text-5xl font-black gradient-text-title">
          ALL ARTISTS
        </h1>
      </div>

      {/* Artists list */}
      <div className="space-y-10 md:space-y-16">
        {artists.map((artist) => (
          <section key={artist.id} className="relative">
            {/* Artist header */}
            <div className="flex items-center gap-3 md:gap-4 mb-4 md:mb-6 pb-3 md:pb-4 border-b border-neon-cyan/10">
              <Link href={`/artists/${artist.slug}`} className="flex items-center gap-3 md:gap-4 group">
                {/* Artist avatar */}
                <div className="w-12 h-12 md:w-16 md:h-16 rounded-full overflow-hidden album-frame p-[2px] flex-shrink-0">
                  <div className="w-full h-full rounded-full overflow-hidden bg-dark-900">
                    {artist.image && !artist.image.includes('default') ? (
                      <img
                        src={artist.image}
                        alt={artist.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <span className="font-display text-lg md:text-2xl font-bold text-neon-cyan/50">
                          {artist.name.charAt(0)}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Artist name and stats */}
                <div className="min-w-0">
                  <h2 className="font-display text-lg md:text-xl font-bold text-white group-hover:text-neon-cyan transition-colors truncate">
                    {artist.name}
                  </h2>
                  <p className="text-[0.65rem] md:text-xs text-text-dim">
                    <span className="text-neon-orange">{artist.albums.length}</span>
                    {' '}{artist.albums.length === 1 ? 'album' : 'albums'}
                    {artist.songCount && (
                      <span className="hidden sm:inline">
                        {' · '}
                        <span className="text-neon-orange">{artist.songCount}</span>
                        {' '}recordings
                      </span>
                    )}
                  </p>
                </div>
              </Link>

              {/* View all link */}
              <Link
                href={`/artists/${artist.slug}`}
                className="ml-auto text-[0.65rem] md:text-xs text-neon-cyan hover:neon-text-cyan transition-all uppercase tracking-wider flex-shrink-0"
              >
                View all →
              </Link>
            </div>

            {/* Albums carousel */}
            <AlbumCarousel albums={artist.albums} artistSlug={artist.slug} />
          </section>
        ))}
      </div>
    </div>
  );
}
