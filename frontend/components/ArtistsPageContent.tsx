'use client';

import { useEffect, useMemo } from 'react';
import Link from 'next/link';
import { Artist, Album } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import FestivalHero from '@/components/FestivalHero';

interface ArtistWithAlbums extends Artist {
  albums: Album[];
}

interface ArtistsPageContentProps {
  artists: ArtistWithAlbums[];
}

interface AlbumWithArtist extends Album {
  artist: string;
  artistSlug: string;
  artistImage: string;
  color: string;
  isFirst: boolean;
}

// Artist color palette - distinct colors for easy visual identification
const ARTIST_COLORS: Record<string, string> = {
  'sts9': '#ff6b35',                    // Vibrant orange
  'disco-biscuits': '#4a90e2',          // Bright blue
  'railroad-earth': '#50c878',          // Emerald green
  'phish': '#e74c3c',                   // Red
  'grateful-dead': '#9b59b6',           // Purple
  'string-cheese-incident': '#f39c12',  // Golden yellow
  'umphrey-s-mcgee': '#1abc9c',         // Turquoise
  'widespread-panic': '#e91e63',        // Pink/magenta
  'moe': '#3498db',                     // Sky blue
  'leftover-salmon': '#ff8c42',         // Coral
  'yonder-mountain-string-band': '#27ae60', // Green
  'tea-leaf-green': '#16a085',          // Teal
  'keller-williams': '#f1c40f',         // Yellow
  'lotus': '#8e44ad',                   // Dark purple
  'the-new-deal': '#e67e22',            // Orange
  'big-gigantic': '#c0392b',            // Dark red
  'papadosio': '#2ecc71',               // Light green
  'dopapod': '#d35400',                 // Dark orange
  'aqueous': '#3498db',                 // Blue
  'pigeons-playing-ping-pong': '#e84393', // Hot pink
};

// Convert hex color to rgba with opacity
const hexToRgba = (hex: string, opacity: number): string => {
  // Handle both hex (#ff0000) and hsl formats
  if (hex.startsWith('hsl')) {
    return hex.replace('hsl', 'hsla').replace(')', `, ${opacity})`);
  }

  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

// Generate a color based on artist slug if not in palette
const getArtistColor = (slug: string): string => {
  if (ARTIST_COLORS[slug]) {
    return ARTIST_COLORS[slug];
  }

  // Generate a consistent color from the slug
  let hash = 0;
  for (let i = 0; i < slug.length; i++) {
    hash = slug.charCodeAt(i) + ((hash << 5) - hash);
  }

  // Create vibrant colors
  const hue = Math.abs(hash % 360);
  const saturation = 65 + (Math.abs(hash) % 20); // 65-85%
  const lightness = 55 + (Math.abs(hash >> 8) % 15); // 55-70%

  return `hsl(${hue}, ${saturation}%, ${lightness}%)`;
};

export default function ArtistsPageContent({ artists }: ArtistsPageContentProps) {
  const { setBreadcrumbs } = useBreadcrumbs();

  useEffect(() => {
    // Home page - no breadcrumbs needed
    setBreadcrumbs([]);
  }, [setBreadcrumbs]);

  const scrollToArtists = () => {
    document.getElementById('artists-content')?.scrollIntoView({ behavior: 'smooth' });
  };

  // Flatten albums with artist metadata
  const allAlbums = useMemo(() => {
    return artists.flatMap((artist) => {
      const color = getArtistColor(artist.slug);
      return artist.albums.map((album, idx) => ({
        ...album,
        artist: artist.name,
        artistSlug: artist.slug,
        artistImage: artist.image,
        color,
        isFirst: idx === 0,
      } as AlbumWithArtist));
    });
  }, [artists]);

  return (
    <div className="pb-8 max-w-[1800px]">
      {/* Festival Hero */}
      <FestivalHero
        artists={artists.map(a => ({
          name: a.name,
          slug: a.slug,
          songCount: a.songCount ?? a.albums.reduce((sum, album) => sum + album.totalSongs, 0),
        }))}
        onStartListening={scrollToArtists}
      />

      {/* All albums in continuous flow */}
      <div id="artists-content" className="px-4 md:px-8 pt-4 md:pt-6 mx-auto max-w-[1400px]">
        <div className="flex flex-wrap gap-3 md:gap-4 justify-center">
          {allAlbums.map((album) => (
            <Link
              key={`${album.artistSlug}-${album.id}`}
              href={`/artists/${album.artistSlug}/album/${album.slug}`}
              className="group"
            >
              <div
                className="rounded-lg overflow-hidden relative bg-[#1a1410] hover:scale-105 transition-transform duration-200"
                style={{
                  width: '140px',
                  border: `2px solid ${hexToRgba(album.color, 0.6)}`,
                  boxShadow: `0 0 16px ${hexToRgba(album.color, 0.25)}, 0 0 4px ${hexToRgba(album.color, 0.4)}`,
                }}
              >
                {/* Corner badge - Artist avatar */}
                <div
                  className="absolute top-2 left-2 z-10 rounded-full p-1 bg-[#1c1a17] shadow-lg"
                >
                  {album.artistImage && !album.artistImage.includes('default') ? (
                    <img
                      src={album.artistImage}
                      alt={album.artist}
                      className="w-6 h-6 rounded-full object-cover"
                    />
                  ) : (
                    <div className="w-6 h-6 rounded-full bg-[#2d2a26] flex items-center justify-center">
                      <span className="text-[10px] font-bold text-white">
                        {album.artist.charAt(0)}
                      </span>
                    </div>
                  )}
                </div>

                {/* Album artwork */}
                <div className="relative aspect-square">
                  {album.coverArt ? (
                    <img
                      src={album.coverArt}
                      alt={album.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full bg-[#2d2a26] flex items-center justify-center">
                      <svg className="w-10 h-10 text-[#3a3632]" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                        <circle cx="12" cy="12" r="3" fill="currentColor"/>
                      </svg>
                    </div>
                  )}

                  {/* Play button overlay */}
                  <div className="absolute bottom-2 right-2 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                    <button className="w-8 h-8 bg-[#d4a060] rounded-full flex items-center justify-center shadow-xl hover:scale-105 hover:bg-[#c08a40] transition-all">
                      <svg className="w-3 h-3 text-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z" />
                      </svg>
                    </button>
                  </div>

                  {/* Artist name on first album - solid color label */}
                  {album.isFirst && (
                    <div
                      className="absolute bottom-0 left-0 right-0 py-2 px-2 text-center text-[10px] font-bold leading-tight"
                      style={{
                        backgroundColor: album.color,
                        color: 'white',
                        textShadow: '0 1px 3px rgba(0, 0, 0, 0.8)',
                      }}
                    >
                      {album.artist}
                    </div>
                  )}
                </div>

                {/* Album info */}
                <div className="p-2">
                  <div className="text-white text-sm font-medium truncate">{album.name}</div>
                  <div className="text-[#8a8478] text-xs truncate">
                    {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'}
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}
