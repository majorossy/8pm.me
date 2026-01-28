'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Artist, Album } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import AlbumCarousel from '@/components/AlbumCarousel';
import FestivalHero from '@/components/FestivalHero';

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

  const scrollToArtists = () => {
    document.getElementById('artists-content')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div className="pb-8 max-w-[1800px]">
      {/* Festival Hero */}
      <FestivalHero
        artists={artists.map(a => ({
          name: a.name,
          slug: a.slug,
          songCount: a.songCount ?? a.albums.reduce((sum, album) => sum + album.totalSongs, 0),
        }))}
        onStartListening={scrollToArtists}
      />

      {/* Artists sections */}
      <div id="artists-content" className="space-y-8 md:space-y-12 px-4 md:px-8 pt-8 md:pt-12 mx-auto">
        {artists.map((artist) => (
          <section key={artist.id} className="flex flex-col items-center">
            {/* Artist header */}
            <div className="flex items-center gap-3 md:gap-4 mb-4">
              <Link href={`/artists/${artist.slug}`} className="flex items-center gap-3 md:gap-4 group">
                {/* Artist avatar */}
                <div className="w-10 h-10 md:w-12 md:h-12 rounded-full overflow-hidden bg-[#2d2a26] flex-shrink-0">
                  {artist.image && !artist.image.includes('default') ? (
                    <img
                      src={artist.image}
                      alt={artist.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <span className="font-bold text-base md:text-lg text-[#3a3632]">
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
                  <p className="text-xs md:text-sm text-[#8a8478]">
                    {artist.albums.length} {artist.albums.length === 1 ? 'album' : 'albums'}
                  </p>
                </div>
              </Link>

              {/* Show all link - next to name */}
              <Link
                href={`/artists/${artist.slug}`}
                className="text-xs md:text-sm font-bold text-[#8a8478] hover:underline uppercase tracking-wider flex-shrink-0"
              >
                Show all
              </Link>
            </div>

            {/* Albums carousel */}
            <div className="w-full">
              <AlbumCarousel albums={artist.albums} artistSlug={artist.slug} />
            </div>
          </section>
        ))}
      </div>
    </div>
  );
}
