'use client';

import { useEffect } from 'react';
import Link from 'next/link';
import { Track, formatDuration } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import SongCard from '@/components/SongCard';

interface TrackPageContentProps {
  track: Track;
}

export default function TrackPageContent({ track }: TrackPageContentProps) {
  const { setBreadcrumbs } = useBreadcrumbs();

  useEffect(() => {
    setBreadcrumbs([
      { label: track.artistName, href: `/artists/${track.artistSlug}`, type: 'artist' },
      { label: track.albumName, href: `/artists/${track.artistSlug}/album/${track.albumIdentifier}`, type: 'album' },
      { label: track.title, type: 'track' }
    ]);
    return () => setBreadcrumbs([]);
  }, [setBreadcrumbs, track]);

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Track header */}
      <section className="mb-12">
        <p className="text-sm text-gray-400 uppercase tracking-wider mb-2">Track</p>
        <h1 className="text-3xl md:text-5xl font-bold text-white mb-4">{track.title}</h1>
        <p className="text-gray-300">
          From{' '}
          <Link
            href={`/artists/${track.artistSlug}/album/${track.albumIdentifier}`}
            className="text-primary hover:underline"
          >
            {track.albumName}
          </Link>
          {' '}by{' '}
          <Link
            href={`/artists/${track.artistSlug}`}
            className="text-primary hover:underline"
          >
            {track.artistName}
          </Link>
        </p>
        <p className="text-gray-400 mt-4">
          {track.songCount} {track.songCount === 1 ? 'recording' : 'recordings'} available
          {track.songCount > 0 && ` (${formatDuration(track.totalDuration)} each)`}
        </p>
      </section>

      {/* Song variants */}
      <section>
        <h2 className="text-2xl font-bold text-white mb-6">Available Recordings</h2>
        <div className="bg-dark-800 rounded-lg overflow-hidden">
          {track.songs.length > 0 ? (
            track.songs.map((song, index) => (
              <SongCard key={song.id} song={song} index={index + 1} />
            ))
          ) : (
            <p className="text-gray-400 p-4">No recordings available.</p>
          )}
        </div>
      </section>
    </div>
  );
}
