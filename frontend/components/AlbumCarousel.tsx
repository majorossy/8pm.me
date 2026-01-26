'use client';

// AlbumCarousel - horizontal scrolling carousel of album cards for an artist

import Link from 'next/link';
import { Album, formatDuration } from '@/lib/api';

interface AlbumCarouselProps {
  albums: Album[];
  artistSlug: string;
}

interface AlbumCarouselCardProps {
  album: Album;
}

function AlbumCarouselCard({ album }: AlbumCarouselCardProps) {
  return (
    <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
      <div className="group flex-shrink-0 w-40 cursor-pointer snap-start">
        {/* Album artwork */}
        <div className="aspect-square bg-dark-600 rounded-lg mb-2 overflow-hidden relative">
          {album.coverArt ? (
            <img
              src={album.coverArt}
              alt={album.name}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="absolute inset-0 bg-gradient-to-br from-primary/20 to-accent/20 flex items-center justify-center">
              <svg className="w-12 h-12 text-white/20 group-hover:text-white/30 transition-colors" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                <circle cx="12" cy="12" r="6" stroke="currentColor" strokeWidth="0.5" fill="none" opacity="0.5"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
              </svg>
            </div>
          )}
          {/* Play button overlay on hover */}
          <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/30">
            <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center shadow-lg">
              <svg className="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
          </div>
        </div>

        {/* Album info */}
        <h4 className="text-sm font-medium text-white truncate group-hover:text-primary transition-colors">
          {album.name}
        </h4>
        <p className="text-xs text-gray-400 mt-0.5">
          {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}
        </p>
      </div>
    </Link>
  );
}

export default function AlbumCarousel({ albums, artistSlug }: AlbumCarouselProps) {
  if (albums.length === 0) {
    return (
      <p className="text-sm text-gray-500">No albums available</p>
    );
  }

  return (
    <div className="relative">
      {/* Carousel container with horizontal scroll */}
      <div className="overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin scrollbar-thumb-dark-500 scrollbar-track-transparent">
        <div className="flex gap-4 pr-4">
          {albums.map((album) => (
            <AlbumCarouselCard key={album.id} album={album} />
          ))}
        </div>
      </div>
    </div>
  );
}
