'use client';

// AlbumCard - displays an album card (theme-aware)

import Link from 'next/link';
import { Album, formatDuration } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';

interface AlbumCardProps {
  album: Album;
}

export default function AlbumCard({ album }: AlbumCardProps) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';

  if (isMetro) {
    // Metro/Time Machine style - clean white cards
    return (
      <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
        <div className="group bg-white p-4 border border-[#d4d0c8] hover:border-[#e85d04] transition-all duration-200 cursor-pointer hover:shadow-lg">
          {/* Album artwork */}
          <div className="aspect-square mb-4 overflow-hidden bg-[#e8e4dc]">
            <div className="w-full h-full relative">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="absolute inset-0 flex items-center justify-center">
                  <svg className="w-16 h-16 text-[#d4d0c8]" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
            </div>
          </div>

          {/* Album info */}
          <h3 className="font-display text-base font-semibold text-[#1a1a1a] truncate group-hover:text-[#e85d04] transition-colors">
            {album.name}
          </h3>
          <p className="text-xs text-[#6b6b6b] mt-1">
            {album.showDate && <span>{album.showDate} · </span>}
            <span className="text-[#e85d04] font-medium">{album.totalTracks}</span>
            {' '}{album.totalTracks === 1 ? 'track' : 'tracks'}
          </p>
          {album.showVenue && (
            <p className="text-xs text-[#6b6b6b] truncate mt-1">{album.showVenue}</p>
          )}
        </div>
      </Link>
    );
  }

  // Default Tron/Synthwave style
  return (
    <Link href={`/artists/${album.artistSlug}/album/${album.slug}`}>
      <div className="group bg-dark-800 p-4 border border-white/5 hover:border-neon-cyan/30 transition-all duration-300 cursor-pointer hover:-translate-y-1 hover:shadow-[0_0_30px_rgba(0,240,255,0.1)]">
        {/* Album artwork with neon frame */}
        <div className="aspect-square mb-4 overflow-hidden album-frame p-[2px]">
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
        <h3 className="font-display text-sm text-white truncate group-hover:text-neon-cyan transition-colors">
          {album.name}
        </h3>
        <p className="text-[10px] text-text-dim uppercase tracking-wider mt-1">
          {album.showDate && <span className="text-neon-purple">{album.showDate} </span>}
          <span className="text-neon-orange">{album.totalTracks}</span>
          {' '}{album.totalTracks === 1 ? 'track' : 'tracks'}
        </p>
        {album.showVenue && (
          <p className="text-[10px] text-text-dim truncate mt-1">{album.showVenue}</p>
        )}
        <p className="text-[10px] text-text-dim font-mono mt-1">
          {formatDuration(album.totalDuration)}
        </p>
      </div>
    </Link>
  );
}
