'use client';

// AlbumCard - displays an album card (Jamify/Spotify style only)

import { useState } from 'react';
import Link from 'next/link';
import { Album } from '@/lib/api';

interface AlbumCardProps {
  album: Album;
}

export default function AlbumCard({ album }: AlbumCardProps) {
  const [imageLoaded, setImageLoaded] = useState(false);

  // Jamify/Spotify style - rounded cards with hover play button
  return (
    <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
      <div className="group p-4 bg-[#181818] rounded-lg hover:bg-[#282828] transition-all duration-300 cursor-pointer">
        {/* Album artwork with play button overlay */}
        <div className="relative aspect-square mb-4 rounded-md overflow-hidden shadow-lg">
          {album.coverArt ? (
            <img
              src={album.coverArt}
              alt={album.name}
              loading="lazy"
              onLoad={() => setImageLoaded(true)}
              className={`w-full h-full object-cover transition-opacity duration-300 ${
                imageLoaded ? 'opacity-100' : 'opacity-0'
              }`}
            />
          ) : (
            <div className="w-full h-full bg-[#282828] flex items-center justify-center">
              <svg className="w-16 h-16 text-[#535353]" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
              </svg>
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

        {/* Album info */}
        <h3 className="font-semibold text-white truncate mb-1">
          {album.name}
        </h3>
        <p className="text-sm text-[#a7a7a7] truncate">
          {album.showDate && <span>{album.showDate} </span>}
          <span>{album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}</span>
        </p>
      </div>
    </Link>
  );
}
