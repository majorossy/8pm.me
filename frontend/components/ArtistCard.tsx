'use client';

// ArtistCard - displays an artist card

import { useState } from 'react';
import Link from 'next/link';
import { Artist } from '@/lib/api';

interface ArtistCardProps {
  artist: Artist;
}

export default function ArtistCard({ artist }: ArtistCardProps) {
  const [imageLoaded, setImageLoaded] = useState(false);

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
