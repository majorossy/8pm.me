'use client';

import Link from 'next/link';
import { Artist, Album } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
import AlbumCarousel from '@/components/AlbumCarousel';

interface ArtistWithAlbums extends Artist {
  albums: Album[];
}

interface ArtistsPageContentProps {
  artists: ArtistWithAlbums[];
}

export default function ArtistsPageContent({ artists }: ArtistsPageContentProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <div className="pt-[80px] px-8 max-w-[1400px] mx-auto">
        {/* Breadcrumb */}
        <div className="text-xs text-[#6b6b6b] mb-6">
          <Link href="/" className="hover:text-[#e85d04] transition-colors">Home</Link>
          {' / '}
          <span className="text-[#1a1a1a]">Artists</span>
        </div>

        {/* Page title */}
        <div className="mb-10">
          <h1 className="font-display text-4xl font-extrabold text-[#1a1a1a]">
            All Artists
          </h1>
        </div>

        {/* Artists list */}
        <div className="space-y-12">
          {artists.map((artist) => (
            <section key={artist.id} className="relative">
              {/* Artist header */}
              <div className="flex items-center gap-4 mb-6 pb-4 border-b border-[#d4d0c8]">
                <Link href={`/artists/${artist.slug}`} className="flex items-center gap-4 group">
                  {/* Artist avatar */}
                  <div className="w-14 h-14 rounded-full overflow-hidden bg-[#e8e4dc] border border-[#d4d0c8]">
                    {artist.image && !artist.image.includes('default') ? (
                      <img
                        src={artist.image}
                        alt={artist.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <span className="font-display text-xl font-bold text-[#6b6b6b]">
                          {artist.name.charAt(0)}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Artist name and stats */}
                  <div>
                    <h2 className="font-display text-xl font-bold text-[#1a1a1a] group-hover:text-[#e85d04] transition-colors">
                      {artist.name}
                    </h2>
                    <p className="text-sm text-[#6b6b6b]">
                      <span className="text-[#e85d04] font-medium">{artist.albums.length}</span>
                      {' '}{artist.albums.length === 1 ? 'album' : 'albums'}
                      {artist.songCount && (
                        <>
                          {' · '}
                          <span className="text-[#e85d04] font-medium">{artist.songCount}</span>
                          {' '}recordings
                        </>
                      )}
                    </p>
                  </div>
                </Link>

                {/* View all link */}
                <Link
                  href={`/artists/${artist.slug}`}
                  className="ml-auto text-sm text-[#6b6b6b] hover:text-[#e85d04] transition-colors"
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
    <div className="pt-24 px-6 lg:px-12 max-w-[1400px] mx-auto">
      {/* Breadcrumb */}
      <div className="text-[0.65rem] text-text-dim uppercase tracking-[0.1em] mb-8">
        <Link href="/" className="hover:text-neon-cyan transition-colors">Home</Link>
        {' / '}
        <span className="text-white">Artists</span>
      </div>

      {/* Page title */}
      <div className="mb-12">
        <div className="font-display text-[0.6rem] text-neon-cyan uppercase tracking-[0.4em] mb-4 flex items-center gap-2">
          <span className="opacity-50">//</span>
          Browse Collection
        </div>
        <h1 className="font-display text-4xl md:text-5xl font-black gradient-text-title">
          ALL ARTISTS
        </h1>
      </div>

      {/* Artists list */}
      <div className="space-y-16">
        {artists.map((artist) => (
          <section key={artist.id} className="relative">
            {/* Artist header */}
            <div className="flex items-center gap-4 mb-6 pb-4 border-b border-neon-cyan/10">
              <Link href={`/artists/${artist.slug}`} className="flex items-center gap-4 group">
                {/* Artist avatar */}
                <div className="w-16 h-16 rounded-full overflow-hidden album-frame p-[2px]">
                  <div className="w-full h-full rounded-full overflow-hidden bg-dark-900">
                    {artist.image && !artist.image.includes('default') ? (
                      <img
                        src={artist.image}
                        alt={artist.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center">
                        <span className="font-display text-2xl font-bold text-neon-cyan/50">
                          {artist.name.charAt(0)}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Artist name and stats */}
                <div>
                  <h2 className="font-display text-xl font-bold text-white group-hover:text-neon-cyan transition-colors">
                    {artist.name}
                  </h2>
                  <p className="text-xs text-text-dim">
                    <span className="text-neon-orange">{artist.albums.length}</span>
                    {' '}{artist.albums.length === 1 ? 'album' : 'albums'}
                    {artist.songCount && (
                      <>
                        {' · '}
                        <span className="text-neon-orange">{artist.songCount}</span>
                        {' '}recordings
                      </>
                    )}
                  </p>
                </div>
              </Link>

              {/* View all link */}
              <Link
                href={`/artists/${artist.slug}`}
                className="ml-auto text-xs text-neon-cyan hover:neon-text-cyan transition-all uppercase tracking-wider"
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
