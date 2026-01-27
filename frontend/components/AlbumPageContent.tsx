'use client';

import { useState, useMemo, useEffect } from 'react';
import Link from 'next/link';
import { Album, Track, Song, formatDuration } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import { usePlayer } from '@/context/PlayerContext';
import { useQueue } from '@/context/QueueContext';
import { useCart } from '@/context/CartContext';
import TrackCard from '@/components/TrackCard';

interface AlbumWithTracks extends Album {
  tracks: Track[];
}

interface AlbumPageContentProps {
  album: AlbumWithTracks;
}

// Format hours from seconds
function formatHours(seconds: number): string {
  const hours = Math.floor(seconds / 3600);
  const mins = Math.floor((seconds % 3600) / 60);
  if (hours > 0) {
    return `${hours}:${String(mins).padStart(2, '0')}`;
  }
  return `0:${String(mins).padStart(2, '0')}`;
}

// Group songs by year
function groupByYear(songs: Song[]): Map<string, Song[]> {
  const groups = new Map<string, Song[]>();
  songs.forEach(song => {
    const year = song.showDate?.split('-')[0] || 'Unknown';
    if (!groups.has(year)) {
      groups.set(year, []);
    }
    groups.get(year)!.push(song);
  });
  return groups;
}

export default function AlbumPageContent({ album }: AlbumPageContentProps) {
  // Debug logging
  console.log('[AlbumPageContent] Album:', album.name, 'Tracks:', album.tracks?.length || 0);
  if (album.tracks?.length > 0) {
    console.log('[AlbumPageContent] First track:', album.tracks[0].title, 'Songs:', album.tracks[0].songs?.length || 0);
  }

  const { setBreadcrumbs } = useBreadcrumbs();
  const { currentSong, isPlaying, playSong, togglePlay, playAlbum } = usePlayer();
  const { queue, addToUpNext } = useQueue();
  const { addToCart, isInCart } = useCart();

  // Check if this album is currently loaded in the queue
  const isCurrentAlbum = queue.album?.identifier === album.identifier;

  // Selected track index for Metro timeline view
  const [selectedTrackIndex, setSelectedTrackIndex] = useState(0);
  const [sortOrder, setSortOrder] = useState<'newest' | 'oldest'>('newest');

  useEffect(() => {
    setBreadcrumbs([
      { label: album.artistName, href: `/artists/${album.artistSlug}`, type: 'artist' },
      { label: album.name, type: 'album' }
    ]);
    return () => setBreadcrumbs([]);
  }, [setBreadcrumbs, album.artistName, album.artistSlug, album.name]);

  // Calculate total live versions
  const totalVersions = album.tracks.reduce((acc, track) => acc + track.songCount, 0);

  // Get selected track
  const selectedTrack = album.tracks[selectedTrackIndex];

  // Group versions by year and sort
  const yearGroups = useMemo(() => {
    if (!selectedTrack) return [];
    const groups = groupByYear(selectedTrack.songs);
    const sorted = Array.from(groups.entries()).sort((a, b) => {
      return sortOrder === 'newest'
        ? b[0].localeCompare(a[0])
        : a[0].localeCompare(b[0]);
    });
    return sorted;
  }, [selectedTrack, sortOrder]);

  const handlePlayVersion = (song: Song) => {
    if (currentSong?.id === song.id && isPlaying) {
      togglePlay();
    } else {
      playSong(song);
    }
  };

  // Jamify/Spotify style
  return (
      <div className="min-h-screen">
        {/* Album Hero */}
        <section className="relative px-4 md:px-8 pt-8 md:pt-16 pb-4 md:pb-6 bg-gradient-to-b from-[#535353] to-[#121212]">
          <div className="flex flex-col md:flex-row md:items-end gap-4 md:gap-6">
            {/* Album artwork */}
            <div className="w-40 h-40 md:w-48 md:h-48 lg:w-56 lg:h-56 flex-shrink-0 shadow-2xl mx-auto md:mx-0">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full h-full object-cover rounded"
                />
              ) : (
                <div className="w-full h-full bg-[#282828] rounded flex items-center justify-center">
                  <svg className="w-16 md:w-20 h-16 md:h-20 text-[#535353]" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
            </div>

            {/* Album info */}
            <div className="pb-2 text-center md:text-left">
              <p className="text-[0.65rem] md:text-xs font-bold text-white uppercase tracking-wider mb-2">
                {album.showDate ? 'Live Recording' : 'Album'}
              </p>
              <h1 className="text-2xl md:text-4xl lg:text-6xl xl:text-7xl font-black text-white mb-2 md:mb-4">
                {album.name}
              </h1>
              <div className="flex flex-wrap items-center justify-center md:justify-start gap-1 md:gap-2 text-xs md:text-sm text-white">
                <Link href={`/artists/${album.artistSlug}`} className="font-bold hover:underline">
                  {album.artistName}
                </Link>
                <span className="text-[#a7a7a7]">&bull;</span>
                {album.showDate && (
                  <>
                    <span className="text-[#a7a7a7]">{album.showDate}</span>
                    <span className="text-[#a7a7a7]">&bull;</span>
                  </>
                )}
                <span className="text-[#a7a7a7]">{album.totalTracks} tracks</span>
                <span className="hidden sm:inline text-[#a7a7a7]">&bull;</span>
                <span className="hidden sm:inline text-[#a7a7a7]">{formatDuration(album.totalDuration)}</span>
              </div>
            </div>
          </div>
        </section>

        {/* Action bar */}
        <section className="px-4 md:px-8 py-4 md:py-6 bg-gradient-to-b from-[#121212]/60 to-[#121212]">
          <div className="flex items-center justify-center md:justify-start gap-4 md:gap-6">
            <button
              onClick={() => playAlbum(album, 0)}
              className={`w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center transition-all shadow-lg ${
                isCurrentAlbum
                  ? 'bg-[#1DB954] hover:scale-105 hover:bg-[#1ed760]'
                  : 'bg-[#1DB954] hover:scale-105 hover:bg-[#1ed760]'
              }`}
            >
              {isCurrentAlbum && isPlaying ? (
                <svg className="w-5 h-5 md:w-6 md:h-6 text-black" fill="currentColor" viewBox="0 0 24 24">
                  <rect x="6" y="4" width="4" height="16" />
                  <rect x="14" y="4" width="4" height="16" />
                </svg>
              ) : (
                <svg className="w-5 h-5 md:w-6 md:h-6 text-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              )}
            </button>
            <button className="text-[#a7a7a7] hover:text-white transition-colors">
              <svg className="w-7 h-7 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </button>
          </div>
        </section>

        {/* Track List */}
        <section className="px-4 md:px-8 pb-8">
          {/* Header row - simplified on mobile */}
          <div className="hidden md:grid grid-cols-[16px_1fr_auto_auto] gap-4 px-4 py-2 border-b border-[#282828] text-[#a7a7a7] text-xs uppercase tracking-wider mb-4">
            <span>#</span>
            <span>Title</span>
            <span></span>
            <span className="flex items-center">
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
              </svg>
            </span>
          </div>
          <div className="md:hidden border-b border-[#282828] mb-4" />

          {/* Tracks */}
          <div className="space-y-1">
            {album.tracks.length > 0 ? (
              album.tracks.map((track, index) => (
                <TrackCard key={track.id} track={track} index={index + 1} album={album} />
              ))
            ) : (
              /* Empty State - No Live Recordings */
              <div className="flex flex-col items-center justify-center py-16 px-8">
                {/* Microphone with waves illustration */}
                <div className="relative mb-8">
                  <svg className="w-32 h-32 text-[#535353]" viewBox="0 0 128 128" fill="none">
                    {/* Microphone stand */}
                    <rect x="60" y="80" width="8" height="32" rx="2" fill="currentColor" opacity="0.5" />
                    <rect x="48" y="108" width="32" height="6" rx="3" fill="currentColor" opacity="0.5" />

                    {/* Microphone head */}
                    <path
                      d="M64 16C52.954 16 44 24.954 44 36V56C44 67.046 52.954 76 64 76C75.046 76 84 67.046 84 56V36C84 24.954 75.046 16 64 16Z"
                      fill="currentColor"
                      opacity="0.8"
                    />

                    {/* Microphone grille lines */}
                    <line x1="52" y1="28" x2="76" y2="28" stroke="#282828" strokeWidth="2" />
                    <line x1="52" y1="36" x2="76" y2="36" stroke="#282828" strokeWidth="2" />
                    <line x1="52" y1="44" x2="76" y2="44" stroke="#282828" strokeWidth="2" />
                    <line x1="52" y1="52" x2="76" y2="52" stroke="#282828" strokeWidth="2" />
                    <line x1="52" y1="60" x2="76" y2="60" stroke="#282828" strokeWidth="2" />

                    {/* Sound waves - fading out */}
                    <path
                      d="M28 46C28 46 24 51 24 56C24 61 28 66 28 66"
                      stroke="currentColor"
                      strokeWidth="3"
                      strokeLinecap="round"
                      opacity="0.3"
                    />
                    <path
                      d="M18 40C18 40 12 48 12 56C12 64 18 72 18 72"
                      stroke="currentColor"
                      strokeWidth="3"
                      strokeLinecap="round"
                      opacity="0.15"
                    />
                    <path
                      d="M100 46C100 46 104 51 104 56C104 61 100 66 100 66"
                      stroke="currentColor"
                      strokeWidth="3"
                      strokeLinecap="round"
                      opacity="0.3"
                    />
                    <path
                      d="M110 40C110 40 116 48 116 56C116 64 110 72 110 72"
                      stroke="currentColor"
                      strokeWidth="3"
                      strokeLinecap="round"
                      opacity="0.15"
                    />
                  </svg>

                  {/* Subtle pulse animation ring */}
                  <div className="absolute inset-0 flex items-center justify-center">
                    <div className="w-20 h-20 rounded-full border-2 border-[#1DB954]/20 animate-ping" style={{ animationDuration: '3s' }} />
                  </div>
                </div>

                <h3 className="text-2xl font-bold text-white mb-3">No Live Recordings Found</h3>
                <p className="text-[#a7a7a7] text-center max-w-md mb-6">
                  We couldn't find any live recordings for this album in the Archive.org collection.
                  Check back later or explore other albums by this artist.
                </p>

                <Link
                  href={`/artists/${album.artistSlug}`}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-[#282828] text-white rounded-full hover:bg-[#3e3e3e] transition-colors font-medium"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                  </svg>
                  Browse Other Albums
                </Link>
              </div>
            )}
          </div>
        </section>

        {/* Album info */}
        {album.showVenue && (
          <section className="px-8 pb-8">
            <p className="text-xs text-[#a7a7a7] uppercase tracking-wider mb-2">Venue</p>
            <p className="text-sm text-white">{album.showVenue}</p>
          </section>
        )}
      </div>
    );
}
