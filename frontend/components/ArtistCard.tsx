'use client';

// ArtistCard - displays an artist card

import { useState } from 'react';
import Link from 'next/link';
import { Artist } from '@/lib/api';
import { useIntersectionObserver } from '@/hooks/useIntersectionObserver';

interface ArtistCardProps {
  artist: Artist;
}

export default function ArtistCard({ artist }: ArtistCardProps) {
  const [imageLoaded, setImageLoaded] = useState(false);
  const { ref, isIntersecting } = useIntersectionObserver({
    rootMargin: '100px', // Start loading 100px before visible
    freezeOnceVisible: true,
  });

  const hasValidImage = artist.image && !artist.image.includes('default');

  return (
    <Link href={`/artists/${artist.slug}`}>
      <div className="group p-4 bg-[#252220] rounded-lg hover:bg-[#2d2a26] transition-all duration-300 cursor-pointer">
        {/* Artist avatar - circular */}
        <div
          ref={ref as React.RefObject<HTMLDivElement>}
          className="relative aspect-square mb-4 rounded-full overflow-hidden shadow-lg"
        >
          {hasValidImage ? (
            <>
              {/* Blur placeholder - shown until image loads */}
              <div
                className={`absolute inset-0 bg-[#2d2a26] transition-opacity duration-500 ${
                  imageLoaded ? 'opacity-0' : 'opacity-100'
                }`}
              >
                {/* Animated shimmer effect for dark theme */}
                <div className="absolute inset-0 bg-gradient-to-r from-transparent via-[#333333] to-transparent animate-shimmer" />
              </div>

              {/* Actual image - only load when in viewport */}
              {isIntersecting && (
                <img
                  src={artist.image}
                  alt={artist.name}
                  onLoad={() => setImageLoaded(true)}
                  className={`w-full h-full object-cover transition-opacity duration-500 ${
                    imageLoaded ? 'opacity-100' : 'opacity-0'
                  }`}
                />
              )}
            </>
          ) : (
            <div className="w-full h-full bg-[#2d2a26] flex items-center justify-center">
              <span className="font-bold text-5xl text-[#3a3632]">
                {artist.name.charAt(0)}
              </span>
            </div>
          )}
          {/* Play button overlay */}
          <div className="absolute bottom-2 right-2 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
            <button className="w-12 h-12 bg-[#d4a060] rounded-full flex items-center justify-center shadow-xl hover:scale-105 hover:bg-[#c08a40] transition-all">
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
        <p className="text-sm text-[#8a8478]">
          Artist
        </p>
      </div>
    </Link>
  );
}
