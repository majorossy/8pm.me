'use client';

import Link from 'next/link';
import { Artist } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
import AlbumCard from '@/components/AlbumCard';

interface ArtistWithAlbums extends Artist {
  albums: any[];
}

interface ArtistPageContentProps {
  artist: ArtistWithAlbums;
}

export default function ArtistPageContent({ artist }: ArtistPageContentProps) {
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
          <Link href="/artists" className="hover:text-[#e85d04] transition-colors">Artists</Link>
          {' / '}
          <span className="text-[#1a1a1a]">{artist.name}</span>
        </div>

        {/* Artist header */}
        <section className="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8 lg:gap-12 mb-12">
          {/* Artist image */}
          <div className="mx-auto lg:mx-0">
            <div className="w-64 h-64 lg:w-[280px] lg:h-[280px] bg-[#e8e4dc] border border-[#d4d0c8] overflow-hidden">
              {artist.image && !artist.image.includes('default') ? (
                <img
                  src={artist.image}
                  alt={artist.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <span className="font-display text-7xl font-bold text-[#6b6b6b]">
                    {artist.name.charAt(0)}
                  </span>
                </div>
              )}
            </div>
          </div>

          {/* Artist info */}
          <div className="pt-4">
            <h1 className="font-display text-3xl md:text-4xl font-extrabold mb-4 text-[#1a1a1a]">
              {artist.name}
            </h1>

            {artist.bio && (
              <p className="text-[#6b6b6b] max-w-2xl mb-6">{artist.bio}</p>
            )}

            {/* Stats */}
            <div className="flex gap-8 py-4 border-y border-[#d4d0c8]">
              <div className="flex flex-col gap-1">
                <span className="font-display text-2xl font-bold text-[#e85d04]">
                  {artist.albums.length}
                </span>
                <span className="text-xs text-[#6b6b6b]">
                  {artist.albums.length === 1 ? 'Album' : 'Albums'}
                </span>
              </div>
              <div className="flex flex-col gap-1">
                <span className="font-display text-2xl font-bold text-[#e85d04]">
                  {artist.songCount}
                </span>
                <span className="text-xs text-[#6b6b6b]">
                  {artist.songCount === 1 ? 'Recording' : 'Recordings'}
                </span>
              </div>
            </div>
          </div>
        </section>

        {/* Albums Grid */}
        <section className="mb-12">
          <div className="flex justify-between items-center mb-6 pb-4 border-b border-[#d4d0c8]">
            <h2 className="font-display text-lg font-bold text-[#1a1a1a]">
              Discography
            </h2>
          </div>

          {artist.albums.length > 0 ? (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
              {artist.albums.map((album) => (
                <AlbumCard key={album.id} album={album} />
              ))}
            </div>
          ) : (
            <p className="text-[#6b6b6b]">No albums available.</p>
          )}
        </section>
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
        <Link href="/artists" className="hover:text-neon-cyan transition-colors">Artists</Link>
        {' / '}
        <span className="text-white">{artist.name}</span>
      </div>

      {/* Artist header */}
      <section className="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-8 lg:gap-16 mb-16">
        {/* Artist image */}
        <div className="mx-auto lg:mx-0">
          <div className="w-64 h-64 lg:w-[300px] lg:h-[300px] album-frame p-[3px]">
            <div className="w-full h-full bg-dark-900 overflow-hidden">
              {artist.image && !artist.image.includes('default') ? (
                <img
                  src={artist.image}
                  alt={artist.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <span className="font-display text-8xl font-bold gradient-text">
                    {artist.name.charAt(0)}
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Artist info */}
        <div className="pt-4">
          <div className="font-display text-[0.6rem] text-neon-cyan uppercase tracking-[0.4em] mb-4 flex items-center gap-2">
            <span className="opacity-50">//</span>
            Artist
          </div>

          <h1 className="font-display text-4xl md:text-5xl lg:text-6xl font-black mb-4 gradient-text-title">
            {artist.name.toUpperCase()}
          </h1>

          {artist.bio && (
            <p className="text-text-dim max-w-2xl mb-6">{artist.bio}</p>
          )}

          {/* Stats */}
          <div className="flex gap-12 py-6 stats-border">
            <div className="flex flex-col gap-1">
              <span className="font-display text-3xl font-bold text-neon-orange">
                {artist.albums.length}
              </span>
              <span className="text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                {artist.albums.length === 1 ? 'Album' : 'Albums'}
              </span>
            </div>
            <div className="flex flex-col gap-1">
              <span className="font-display text-3xl font-bold text-neon-orange">
                {artist.songCount}
              </span>
              <span className="text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                {artist.songCount === 1 ? 'Recording' : 'Recordings'}
              </span>
            </div>
          </div>
        </div>
      </section>

      {/* Albums Grid */}
      <section className="mb-16">
        <div className="flex justify-between items-center mb-8 pb-4 section-border">
          <h2 className="font-display text-xs uppercase tracking-[0.3em] text-text-dim">
            <span className="text-neon-cyan">//</span> Discography
          </h2>
        </div>

        {artist.albums.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            {artist.albums.map((album) => (
              <AlbumCard key={album.id} album={album} />
            ))}
          </div>
        ) : (
          <p className="text-text-dim">No albums available.</p>
        )}
      </section>
    </div>
  );
}
