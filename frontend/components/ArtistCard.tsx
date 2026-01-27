'use client';

// ArtistCard - displays an artist card (theme-aware)

import { useState } from 'react';
import Link from 'next/link';
import { Artist } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';

interface ArtistCardProps {
  artist: Artist;
}

export default function ArtistCard({ artist }: ArtistCardProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';
  const [imageLoaded, setImageLoaded] = useState(false);

  // Jamify/Spotify style - rounded cards with circular artist image
  if (isJamify) {
    return (
      <Link href={`/artists/${artist.slug}`}>
        <div className="group p-4 bg-[#181818] rounded-lg hover:bg-[#282828] transition-all duration-300 cursor-pointer">
          {/* Artist avatar - circular */}
          <div className="relative aspect-square mb-4 rounded-full overflow-hidden shadow-lg">
            {artist.image && !artist.image.includes('default') ? (
              <img
                src={artist.image}
                alt={artist.name}
                loading="lazy"
                onLoad={() => setImageLoaded(true)}
                className={`w-full h-full object-cover transition-opacity duration-300 ${
                  imageLoaded ? 'opacity-100' : 'opacity-0'
                }`}
              />
            ) : (
              <div className="w-full h-full bg-[#282828] flex items-center justify-center">
                <span className="font-bold text-5xl text-[#535353]">
                  {artist.name.charAt(0)}
                </span>
              </div>
            )}
            {/* Play button overlay */}
            <div className="absolute bottom-2 right-2 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
              <button className="w-12 h-12 bg-[#1DB954] rounded-full flex items-center justify-center shadow-xl hover:scale-105 hover:bg-[#1ed760] transition-all">
                <svg className="w-5 h-5 text-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </button>
            </div>
          </div>

          {/* Artist info */}
          <h3 className="font-semibold text-white truncate mb-1">
            {artist.name}
          </h3>
          <p className="text-sm text-[#a7a7a7]">
            Artist
          </p>
        </div>
      </Link>
    );
  }

  if (isMetro) {
    // Metro/Time Machine style
    return (
      <Link href={`/artists/${artist.slug}`}>
        <div className="group bg-white p-4 border border-[#d4d0c8] hover:border-[#e85d04] transition-all duration-200 cursor-pointer hover:shadow-lg">
          {/* Artist avatar */}
          <div className="aspect-square mb-4 overflow-hidden bg-[#e8e4dc] rounded-full">
            <div className="w-full h-full flex items-center justify-center">
              {artist.image && !artist.image.includes('default') ? (
                <img
                  src={artist.image}
                  alt={artist.name}
                  loading="lazy"
                  onLoad={() => setImageLoaded(true)}
                  className={`w-full h-full object-cover transition-opacity duration-300 ${
                    imageLoaded ? 'opacity-100' : 'opacity-0'
                  }`}
                />
              ) : (
                <span className="font-display text-4xl font-bold text-[#6b6b6b]">
                  {artist.name.charAt(0)}
                </span>
              )}
            </div>
          </div>

          {/* Artist info */}
          <h3 className="font-display text-base font-semibold text-[#1a1a1a] truncate group-hover:text-[#e85d04] transition-colors">
            {artist.name}
          </h3>
          <p className="text-xs text-[#6b6b6b] mt-1">
            <span className="text-[#e85d04] font-medium">{artist.songCount}</span>
            {' '}{artist.songCount === 1 ? 'recording' : 'recordings'}
          </p>
        </div>
      </Link>
    );
  }

  // Default Tron/Synthwave style
  return (
    <Link href={`/artists/${artist.slug}`}>
      <div className="group bg-dark-800 p-4 border border-white/5 hover:border-neon-cyan/30 transition-all duration-300 cursor-pointer hover:-translate-y-1 hover:shadow-[0_0_30px_rgba(0,240,255,0.1)]">
        {/* Artist avatar */}
        <div className="aspect-square mb-4 overflow-hidden album-frame p-[2px]">
          <div className="w-full h-full bg-dark-900 flex items-center justify-center">
            {artist.image && !artist.image.includes('default') ? (
              <img
                src={artist.image}
                alt={artist.name}
                loading="lazy"
                onLoad={() => setImageLoaded(true)}
                className={`w-full h-full object-cover transition-opacity duration-300 ${
                  imageLoaded ? 'opacity-100' : 'opacity-0'
                }`}
              />
            ) : (
              <span className="font-display text-5xl font-bold gradient-text">
                {artist.name.charAt(0)}
              </span>
            )}
          </div>
        </div>

        {/* Artist info */}
        <h3 className="font-display text-sm text-white truncate group-hover:text-neon-cyan transition-colors">
          {artist.name}
        </h3>
        <p className="text-[10px] text-text-dim uppercase tracking-wider mt-1">
          <span className="text-neon-orange">{artist.songCount}</span>
          {' '}{artist.songCount === 1 ? 'recording' : 'recordings'}
        </p>
      </div>
    </Link>
  );
}
