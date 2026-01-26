'use client';

// AlbumCard - displays an album (grouped by Archive.org identifier) with navigation

import Link from 'next/link';
import { Album, formatDuration } from '@/lib/api';

interface AlbumCardProps {
  album: Album;
}

export default function AlbumCard({ album }: AlbumCardProps) {
  return (
    <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
      <div className="group bg-dark-700 rounded-lg p-4 hover:bg-dark-600 transition-all duration-300 cursor-pointer">
        {/* Album artwork */}
        <div className="aspect-square bg-dark-600 rounded-lg mb-4 overflow-hidden relative">
          {album.coverArt ? (
            <img
              src={album.coverArt}
              alt={album.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="absolute inset-0 bg-gradient-to-br from-primary/20 to-accent/20 flex items-center justify-center">
              {/* Vinyl record icon */}
              <svg className="w-16 h-16 text-white/20 group-hover:text-white/30 transition-colors" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                <circle cx="12" cy="12" r="6" stroke="currentColor" strokeWidth="0.5" fill="none" opacity="0.5"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
              </svg>
            </div>
          )}
          {/* Play button overlay on hover */}
          <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
            <div className="w-12 h-12 bg-primary rounded-full flex items-center justify-center shadow-lg">
              <svg className="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
          </div>
        </div>

        {/* Album info */}
        <h3 className="font-semibold text-white truncate group-hover:text-primary transition-colors">
          {album.name}
        </h3>
        <p className="text-sm text-gray-400 mt-1">
          {album.showDate && <span>{album.showDate} &bull; </span>}
          {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}
        </p>
        {album.showVenue && (
          <p className="text-xs text-gray-500 truncate mt-1">{album.showVenue}</p>
        )}
        <p className="text-xs text-gray-500 mt-1">
          {formatDuration(album.totalDuration)}
        </p>
      </div>
    </Link>
  );
}
