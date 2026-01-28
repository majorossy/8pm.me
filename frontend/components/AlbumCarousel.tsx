'use client';

// AlbumCarousel - horizontal scrolling carousel of album cards with navigation

import { useRef, useState, useEffect } from 'react';
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
      <div className="group flex-shrink-0 w-36 sm:w-40 md:w-44 lg:w-48 p-3 md:p-4 bg-[#252220] rounded-lg hover:bg-[#2d2a26] transition-all duration-300 cursor-pointer snap-start">
        {/* Album artwork with play button overlay */}
        <div className="relative aspect-square mb-3 md:mb-4 rounded-md overflow-hidden shadow-lg">
          {album.coverArt ? (
            <img
              src={album.coverArt}
              alt={album.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full bg-[#2d2a26] flex items-center justify-center">
              <svg className="w-10 md:w-12 h-10 md:h-12 text-[#3a3632]" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
              </svg>
            </div>
          )}
          {/* Play button overlay */}
          <div className="absolute bottom-2 right-2 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
            <button className="w-8 md:w-10 h-8 md:h-10 bg-[#d4a060] rounded-full flex items-center justify-center shadow-xl hover:scale-105 hover:bg-[#c08a40] transition-all">
              <svg className="w-3 md:w-4 h-3 md:h-4 text-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </button>
          </div>
        </div>

        {/* Album info */}
        <h4 className="font-semibold text-white text-xs md:text-sm truncate">
          {album.name}
        </h4>
        <p className="text-[10px] md:text-xs text-[#8a8478] mt-1 truncate">
          {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}
        </p>
      </div>
    </Link>
  );
}

export default function AlbumCarousel({ albums, artistSlug }: AlbumCarouselProps) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);

  const checkScrollButtons = () => {
    if (scrollRef.current) {
      const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
      setCanScrollLeft(scrollLeft > 0);
      setCanScrollRight(scrollLeft < scrollWidth - clientWidth - 10);
    }
  };

  useEffect(() => {
    checkScrollButtons();
    const scrollEl = scrollRef.current;
    if (scrollEl) {
      scrollEl.addEventListener('scroll', checkScrollButtons);
      window.addEventListener('resize', checkScrollButtons);
      return () => {
        scrollEl.removeEventListener('scroll', checkScrollButtons);
        window.removeEventListener('resize', checkScrollButtons);
      };
    }
  }, [albums]);

  const scroll = (direction: 'left' | 'right') => {
    if (scrollRef.current) {
      const scrollAmount = scrollRef.current.clientWidth * 0.75;
      scrollRef.current.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth',
      });
    }
  };

  if (albums.length === 0) {
    return (
      <p className="text-sm text-[#8a8478]">No albums available</p>
    );
  }

  return (
    <div className="relative group/carousel w-full">
      {/* Left arrow - hidden on mobile, shown on desktop when scrollable */}
      {canScrollLeft && (
        <button
          onClick={() => scroll('left')}
          className="absolute left-0 top-1/2 -translate-y-1/2 z-10 hidden md:flex items-center justify-center w-10 h-10 bg-[#1c1a17]/90 hover:bg-[#252220] border border-[#3a3632] rounded-full shadow-lg opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-200"
          aria-label="Scroll left"
        >
          <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
        </button>
      )}

      {/* Carousel container */}
      <div
        ref={scrollRef}
        className="overflow-x-auto scroll-smooth snap-x snap-mandatory pb-2 scrollbar-thin scrollbar-thumb-[#3a3632] scrollbar-track-transparent"
        style={{ scrollbarWidth: 'thin' }}
      >
        <div className="flex justify-center gap-3 md:gap-4 lg:gap-6 px-1">
          {albums.map((album) => (
            <AlbumCarouselCard key={album.id} album={album} />
          ))}
        </div>
      </div>

      {/* Right arrow - hidden on mobile, shown on desktop when scrollable */}
      {canScrollRight && (
        <button
          onClick={() => scroll('right')}
          className="absolute right-0 top-1/2 -translate-y-1/2 z-10 hidden md:flex items-center justify-center w-10 h-10 bg-[#1c1a17]/90 hover:bg-[#252220] border border-[#3a3632] rounded-full shadow-lg opacity-0 group-hover/carousel:opacity-100 transition-opacity duration-200"
          aria-label="Scroll right"
        >
          <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
          </svg>
        </button>
      )}

      {/* Gradient fade edges for visual polish */}
      <div className="absolute left-0 top-0 bottom-2 w-4 bg-gradient-to-r from-[#1c1a17] to-transparent pointer-events-none opacity-0 group-hover/carousel:opacity-100 transition-opacity" />
      <div className="absolute right-0 top-0 bottom-2 w-4 bg-gradient-to-l from-[#1c1a17] to-transparent pointer-events-none opacity-0 group-hover/carousel:opacity-100 transition-opacity" />
    </div>
  );
}
