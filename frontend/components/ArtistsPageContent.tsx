'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Artist, Album } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import AlbumCarousel from '@/components/AlbumCarousel';

interface ArtistWithAlbums extends Artist {
  albums: Album[];
}

interface ArtistsPageContentProps {
  artists: ArtistWithAlbums[];
}

export default function ArtistsPageContent({ artists }: ArtistsPageContentProps) {
  const { setBreadcrumbs } = useBreadcrumbs();

  useEffect(() => {
    // Home page - no breadcrumbs needed
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
