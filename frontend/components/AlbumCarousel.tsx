'use client';

// AlbumCarousel - horizontal scrolling carousel of album cards (theme-aware)

import Link from 'next/link';
import { Album, formatDuration } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';

interface AlbumCarouselProps {
  albums: Album[];
  artistSlug: string;
}

interface AlbumCarouselCardProps {
  album: Album;
  isMetro: boolean;
}

function AlbumCarouselCard({ album, isMetro }: AlbumCarouselCardProps) {
  if (isMetro) {
    // Metro/Time Machine style
    return (
      <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
        <div className="group flex-shrink-0 w-48 cursor-pointer snap-start transition-all duration-200">
          {/* Album artwork */}
          <div className="aspect-square mb-3 overflow-hidden bg-[#e8e4dc] border border-[#d4d0c8] group-hover:border-[#e85d04]">
            <div className="w-full h-full relative">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="absolute inset-0 flex items-center justify-center">
                  <svg className="w-12 h-12 text-[#d4d0c8]" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
            </div>
          </div>

          {/* Album info */}
          <h4 className="font-display text-sm font-semibold text-[#1a1a1a] truncate group-hover:text-[#e85d04] transition-colors">
            {album.name}
          </h4>
          <p className="text-xs text-[#6b6b6b] mt-1">
            <span className="text-[#e85d04] font-medium">{album.totalTracks}</span>
            {' '}{album.totalTracks === 1 ? 'track' : 'tracks'}
          </p>
        </div>
      </Link>
    );
  }

  // Default Tron/Synthwave style
  return (
    <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
      <div className="group flex-shrink-0 w-48 cursor-pointer snap-start transition-all duration-300 hover:-translate-y-1">
        {/* Album artwork with neon frame */}
        <div className="aspect-square mb-3 overflow-hidden album-frame p-[2px]">
          <div className="w-full h-full bg-dark-900 relative">
            {album.coverArt ? (
              <img
                src={album.coverArt}
                alt={album.name}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
            ) : (
              <div className="absolute inset-0 flex items-center justify-center">
                <svg className="w-16 h-16 text-neon-cyan/20 group-hover:text-neon-cyan/30 transition-colors" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                  <circle cx="12" cy="12" r="6" stroke="currentColor" strokeWidth="0.5" fill="none" opacity="0.5"/>
                  <circle cx="12" cy="12" r="3" fill="currentColor"/>
                </svg>
              </div>
            )}
            {/* Play button overlay on hover */}
            <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/40">
              <div className="w-12 h-12 bg-neon-cyan rounded-full flex items-center justify-center shadow-[0_0_30px_var(--neon-cyan)]">
                <svg className="w-5 h-5 text-dark-900 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        {/* Album info */}
        <h4 className="font-display text-sm text-white truncate group-hover:text-neon-cyan transition-colors">
          {album.name}
        </h4>
        <p className="text-[10px] text-text-dim uppercase tracking-wider mt-1">
          <span className="text-neon-orange">{album.totalTracks}</span>
          {' '}{album.totalTracks === 1 ? 'track' : 'tracks'}
        </p>
      </div>
    </Link>
  );
}

export default function AlbumCarousel({ albums, artistSlug }: AlbumCarouselProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';

  if (albums.length === 0) {
    return (
      <p className={`text-sm ${isMetro ? 'text-[#6b6b6b]' : 'text-text-dim'}`}>No albums available</p>
    );
  }

  return (
    <div className="relative">
      {/* Carousel container */}
      <div className="overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin">
        <div className="flex gap-4 pr-4">
          {albums.map((album) => (
            <AlbumCarouselCard key={album.id} album={album} isMetro={isMetro} />
          ))}
        </div>
      </div>
    </div>
  );
}
