'use client';

// AlbumCarousel - horizontal scrolling carousel of album cards

import Link from 'next/link';
import { Album } from '@/lib/api';

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
      <div className="group flex-shrink-0 w-48 p-4 bg-[#252220] rounded-lg hover:bg-[#2d2a26] transition-all duration-300 cursor-pointer snap-start">
        {/* Album artwork with play button overlay */}
        <div className="relative aspect-square mb-4 rounded-md overflow-hidden shadow-lg">
          {album.coverArt ? (
            <img
              src={album.coverArt}
              alt={album.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full bg-[#2d2a26] flex items-center justify-center">
              <svg className="w-12 h-12 text-[#3a3632]" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
              </svg>
            </div>
          )}
          {/* Play button overlay */}
          <div className="absolute bottom-2 right-2 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
            <button className="w-10 h-10 bg-[#d4a060] rounded-full flex items-center justify-center shadow-xl hover:scale-105 hover:bg-[#c08a40] transition-all">
              <svg className="w-4 h-4 text-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </button>
          </div>
        </div>

        {/* Album info */}
        <h4 className="font-semibold text-white text-sm truncate">
          {album.name}
        </h4>
        <p className="text-xs text-[#8a8478] mt-1 truncate">
          {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}
        </p>
      </div>
    </Link>
  );
}

export default function AlbumCarousel({ albums, artistSlug }: AlbumCarouselProps) {
  if (albums.length === 0) {
    return (
      <p className="text-sm text-[#8a8478]">No albums available</p>
    );
  }

  return (
    <div className="relative">
      {/* Carousel container */}
      <div className="overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin">
        <div className="flex gap-6 pr-4">
          {albums.map((album) => (
            <AlbumCarouselCard key={album.id} album={album} />
          ))}
        </div>
      </div>
    </div>
  );
}
