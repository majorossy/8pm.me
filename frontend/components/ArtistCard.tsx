'use client';

import Link from 'next/link';
import { Artist } from '@/lib/api';

interface ArtistCardProps {
  artist: Artist;
}

export default function ArtistCard({ artist }: ArtistCardProps) {
  return (
    <Link href={`/artists/${artist.slug}`}>
      <div className="group bg-dark-700 rounded-lg p-4 hover:bg-dark-600 transition-all duration-300 cursor-pointer">
        <div className="aspect-square bg-dark-600 rounded-lg mb-4 overflow-hidden relative">
          <div className="absolute inset-0 bg-gradient-to-br from-primary/20 to-accent/20 flex items-center justify-center">
            <span className="text-6xl font-bold text-white/20">
              {artist.name.charAt(0)}
            </span>
          </div>
          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors" />
        </div>
        <h3 className="font-semibold text-white truncate">{artist.name}</h3>
        <p className="text-sm text-gray-400">
          {artist.songCount} {artist.songCount === 1 ? 'song' : 'songs'}
        </p>
      </div>
    </Link>
  );
}
