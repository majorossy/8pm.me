'use client';

import { useState, useMemo } from 'react';
import Link from 'next/link';
import { Album, Track, Song, formatDuration } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
import { usePlayer } from '@/context/PlayerContext';
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
  const { theme } = useTheme();
  const isMetro = theme === 'metro';
  const { currentSong, isPlaying, playSong, togglePlay } = usePlayer();
  const { addToCart, isInCart } = useCart();

  // Selected track index for Metro timeline view
  const [selectedTrackIndex, setSelectedTrackIndex] = useState(0);
  const [sortOrder, setSortOrder] = useState<'newest' | 'oldest'>('newest');

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

  if (isMetro) {
    // Metro/Time Machine style - Sidebar + Timeline layout
    return (
      <div className="min-h-screen bg-[#f8f6f1]">
        {/* Left Sidebar - Album Context */}
        <aside className="fixed left-0 top-[60px] bottom-0 w-[320px] bg-white border-r border-[#d4d0c8] overflow-y-auto z-30">
          <div className="p-6">
            {/* Album Cover with Now Playing overlay */}
            <div className="relative mb-5 overflow-hidden">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full aspect-square object-cover"
                />
              ) : (
                <div className="w-full aspect-square bg-[#e8e4dc] flex items-center justify-center">
                  <svg className="w-20 h-20 text-[#d4d0c8]" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
              {/* Now Playing overlay */}
              {currentSong && (
                <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
                  <div className="text-[0.55rem] uppercase tracking-[0.15em] text-white/70 mb-1">Now Playing</div>
                  <div className="font-display text-sm font-semibold text-white truncate">{currentSong.title}</div>
                </div>
              )}
            </div>

            {/* Album Title & Artist */}
            <h1 className="font-display text-2xl font-extrabold text-[#1a1a1a] mb-1">{album.name}</h1>
            <p className="text-sm text-[#6b6b6b] mb-5">
              <Link href={`/artists/${album.artistSlug}`} className="text-[#e85d04] hover:underline">
                {album.artistName}
              </Link>
            </p>

            {/* Meta Stats */}
            <div className="grid grid-cols-3 gap-4 py-4 border-y border-[#d4d0c8] mb-5">
              <div className="text-center">
                <div className="font-display text-xl font-bold text-[#e85d04]">{album.totalTracks}</div>
                <div className="text-[0.6rem] text-[#6b6b6b] uppercase tracking-[0.1em]">Tracks</div>
              </div>
              <div className="text-center">
                <div className="font-display text-xl font-bold text-[#e85d04]">{totalVersions}</div>
                <div className="text-[0.6rem] text-[#6b6b6b] uppercase tracking-[0.1em]">Versions</div>
              </div>
              <div className="text-center">
                <div className="font-display text-xl font-bold text-[#e85d04]">{formatHours(album.totalDuration)}</div>
                <div className="text-[0.6rem] text-[#6b6b6b] uppercase tracking-[0.1em]">Hours</div>
              </div>
            </div>

            {/* Play Random Button */}
            <button className="w-full py-3 bg-[#1a1a1a] text-white font-display text-sm font-medium hover:bg-[#e85d04] transition-colors flex items-center justify-center gap-2 mb-6">
              <span>▶</span> Play Random Version
            </button>

            {/* Track Navigation */}
            <nav>
              <div className="text-[0.65rem] uppercase tracking-[0.15em] text-[#6b6b6b] mb-3">Tracks</div>
              {album.tracks.map((track, index) => (
                <div
                  key={track.id}
                  onClick={() => setSelectedTrackIndex(index)}
                  className={`
                    flex items-center gap-3 py-2 px-2 -mx-2 cursor-pointer transition-all border-b border-[#d4d0c8]
                    ${selectedTrackIndex === index
                      ? 'bg-[#ffecd9] text-[#e85d04]'
                      : 'hover:bg-[#f8f6f1]'
                    }
                  `}
                >
                  <span className={`font-display text-xs font-semibold w-5 ${selectedTrackIndex === index ? 'text-[#e85d04]' : 'text-[#6b6b6b]'}`}>
                    {String(index + 1).padStart(2, '0')}
                  </span>
                  <span className="flex-1 text-sm truncate">{track.title}</span>
                  <span className="text-[0.65rem] text-[#6b6b6b] bg-[#f8f6f1] px-2 py-0.5 rounded-full">
                    {track.songCount}
                  </span>
                </div>
              ))}
            </nav>
          </div>
        </aside>

        {/* Main Content - Timeline */}
        <div className="ml-[320px] pt-[60px] min-h-screen">
          {/* Timeline Header - Sticky */}
          <div className="sticky top-[60px] bg-[#f8f6f1] z-20 px-8 py-6 border-b border-[#d4d0c8]">
            <div className="flex items-baseline gap-4 mb-2">
              <h2 className="font-display text-3xl font-extrabold text-[#1a1a1a]">{selectedTrack?.title}</h2>
              <span className="text-sm text-[#6b6b6b]">{selectedTrack?.songCount} recorded versions</span>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-xs text-[#6b6b6b]">Sort by</span>
              <select
                value={sortOrder}
                onChange={(e) => setSortOrder(e.target.value as 'newest' | 'oldest')}
                className="text-xs bg-white border border-[#d4d0c8] px-3 py-1.5 text-[#1a1a1a]"
              >
                <option value="newest">Year (newest)</option>
                <option value="oldest">Year (oldest)</option>
              </select>
            </div>
          </div>

          {/* Timeline */}
          <div className="px-8 pb-12">
            {yearGroups.map(([year, songs]) => (
              <div key={year} className="relative pl-[100px]">
                {/* Year Marker */}
                <div className="absolute left-0 top-8 font-display text-5xl font-extrabold text-[#d4d0c8]">
                  {year}
                </div>

                {/* Timeline Line */}
                <div className="absolute left-[85px] top-0 bottom-0 w-[2px] bg-[#d4d0c8]">
                  <div className="absolute top-10 left-[-4px] w-[10px] h-[10px] bg-[#e85d04] rounded-full" />
                </div>

                {/* Version Cards */}
                <div className="py-6 pl-8 space-y-4">
                  {songs.map((song) => {
                    const isCurrentPlaying = currentSong?.id === song.id && isPlaying;
                    const inQueue = isInCart(song.id);

                    // Format date
                    let formattedDate = song.showDate;
                    if (song.showDate?.match(/^\d{4}-\d{2}-\d{2}$/)) {
                      const [y, m, d] = song.showDate.split('-');
                      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                      formattedDate = `${months[parseInt(m) - 1]} ${parseInt(d)}, ${y}`;
                    }

                    return (
                      <div
                        key={song.id}
                        className={`
                          relative bg-white border p-5 transition-all cursor-pointer
                          ${isCurrentPlaying ? 'border-[#e85d04] border-2' : 'border-[#d4d0c8] hover:border-[#e85d04] hover:shadow-lg'}
                        `}
                      >
                        {/* Connector line */}
                        <div className="absolute left-[-2rem] top-8 w-[1.5rem] h-[2px] bg-[#d4d0c8]" />

                        {/* Top row */}
                        <div className="flex justify-between items-start mb-4">
                          <div>
                            <div className="font-display text-lg font-bold text-[#1a1a1a]">{song.showVenue || 'Unknown Venue'}</div>
                            <div className="text-sm text-[#6b6b6b]">{formattedDate} — {song.showLocation || ''}</div>
                          </div>
                          <div className="flex gap-2">
                            <button
                              type="button"
                              onClick={(e) => { e.stopPropagation(); handlePlayVersion(song); }}
                              className="px-4 py-2 bg-[#1a1a1a] text-white text-xs font-medium hover:bg-[#e85d04] transition-colors"
                            >
                              {isCurrentPlaying ? '❚❚ Pause' : '▶ Play'}
                            </button>
                            <button
                              onClick={(e) => { e.stopPropagation(); addToCart(song); }}
                              disabled={inQueue}
                              className={`px-4 py-2 text-xs font-medium border transition-colors ${
                                inQueue
                                  ? 'border-[#d4d0c8] text-[#6b6b6b] cursor-default'
                                  : 'border-[#d4d0c8] text-[#1a1a1a] hover:border-[#1a1a1a]'
                              }`}
                            >
                              {inQueue ? '✓ Queued' : '+ Queue'}
                            </button>
                          </div>
                        </div>

                        {/* Details Grid */}
                        <div className="grid grid-cols-3 gap-6 pt-4 border-t border-[#d4d0c8]">
                          <div>
                            <div className="text-[0.6rem] text-[#6b6b6b] uppercase tracking-[0.08em] mb-1">Taper</div>
                            <div className="text-xs text-[#1a1a1a]">{song.taper || '—'}</div>
                          </div>
                          <div>
                            <div className="text-[0.6rem] text-[#6b6b6b] uppercase tracking-[0.08em] mb-1">Source</div>
                            <div className="text-xs text-[#1a1a1a]">{song.source || '—'}</div>
                          </div>
                          <div>
                            <div className="text-[0.6rem] text-[#6b6b6b] uppercase tracking-[0.08em] mb-1">Duration</div>
                            <div className="text-xs text-[#1a1a1a]">{formatDuration(song.duration)}</div>
                          </div>
                        </div>

                        {/* Waveform placeholder */}
                        <div className="mt-4 h-10 bg-[#f8f6f1] flex items-center px-2 gap-[2px]">
                          {Array.from({ length: 40 }).map((_, i) => (
                            <div
                              key={i}
                              className={`flex-1 rounded-sm transition-colors ${isCurrentPlaying ? 'bg-[#e85d04]' : 'bg-[#d4d0c8]'}`}
                              style={{ height: `${20 + Math.random() * 60}%` }}
                            />
                          ))}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div className="min-h-screen">
      {/* Breadcrumb */}
      <div className="pt-24 px-6 lg:px-12 text-[0.65rem] text-text-dim uppercase tracking-[0.1em]">
        <Link href="/" className="hover:text-neon-cyan transition-colors">Home</Link>
        {' / '}
        <Link href="/artists" className="hover:text-neon-cyan transition-colors">Artists</Link>
        {' / '}
        <Link href={`/artists/${album.artistSlug}`} className="hover:text-neon-cyan transition-colors">
          {album.artistName}
        </Link>
        {' / '}
        <span className="text-white">{album.name}</span>
      </div>

      {/* Hero Section */}
      <section className="px-6 lg:px-12 py-8 max-w-[1400px] mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-[400px_1fr] gap-8 lg:gap-16 items-start">
          {/* Album Artwork */}
          <div className="relative max-w-[400px] mx-auto lg:mx-0">
            <div className="album-frame">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full aspect-square object-cover"
                />
              ) : (
                <div className="w-full aspect-square bg-dark-800 flex items-center justify-center">
                  <svg className="w-24 h-24 text-white/20" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="6" stroke="currentColor" strokeWidth="0.5" fill="none" opacity="0.5"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
            </div>
            {/* Now Available Badge */}
            <div className="absolute -bottom-4 left-1/2 -translate-x-1/2 now-playing-badge font-display whitespace-nowrap">
              <span className="inline-block w-2 h-2 bg-neon-pink rounded-full mr-2 blink-dot" />
              Now Available
            </div>
          </div>

          {/* Album Info */}
          <div className="pt-4">
            {/* Album type label */}
            <div className="font-display text-[0.6rem] text-neon-cyan uppercase tracking-[0.4em] mb-4 flex items-center gap-2">
              <span className="opacity-50">//</span>
              {album.showDate ? 'Live Recording' : 'Studio Album'}
            </div>

            {/* Album title */}
            <h1 className="font-display text-4xl md:text-5xl lg:text-6xl font-black leading-[0.95] mb-2 gradient-text-title">
              {album.name.toUpperCase()}
            </h1>

            {/* Artist name */}
            <p className="text-lg text-text-dim mb-6">
              <Link href={`/artists/${album.artistSlug}`} className="text-neon-cyan hover:neon-text-cyan transition-all">
                {album.artistName}
              </Link>
              {album.showVenue && (
                <span> — {album.showVenue}</span>
              )}
            </p>

            {/* Show date if available */}
            {album.showDate && (
              <p className="text-text-dim mb-4">{album.showDate}</p>
            )}

            {/* Stats Bar */}
            <div className="flex gap-12 py-6 stats-border mb-8">
              <div className="flex flex-col gap-1">
                <span className="font-display text-3xl font-bold text-neon-orange">
                  {album.totalTracks}
                </span>
                <span className="text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                  Tracks
                </span>
              </div>
              <div className="flex flex-col gap-1">
                <span className="font-display text-3xl font-bold text-neon-orange">
                  {formatDuration(album.totalDuration)}
                </span>
                <span className="text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                  Duration
                </span>
              </div>
              {totalVersions > album.totalTracks && (
                <div className="flex flex-col gap-1">
                  <span className="font-display text-3xl font-bold text-neon-orange">
                    {totalVersions}
                  </span>
                  <span className="text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                    Live Versions
                  </span>
                </div>
              )}
            </div>

            {/* Action Buttons */}
            <div className="flex gap-4">
              <button className="btn-neon px-8 py-4 font-display text-xs uppercase tracking-[0.1em]">
                <span className="relative z-10 flex items-center gap-2">
                  <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                  </svg>
                  Play Album
                </span>
              </button>
              <button className="btn-ghost px-8 py-4 font-display text-xs uppercase tracking-[0.1em]">
                + Add to Queue
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Tracks Section */}
      <section className="px-6 lg:px-12 py-8 max-w-[1400px] mx-auto">
        {/* Section Header */}
        <div className="flex justify-between items-center mb-8 pb-4 section-border">
          <h2 className="font-display text-xs uppercase tracking-[0.3em] text-text-dim">
            <span className="text-neon-cyan">//</span> Track Listing
          </h2>
        </div>

        {/* Track List */}
        <div className="space-y-2">
          {album.tracks.length > 0 ? (
            album.tracks.map((track, index) => (
              <TrackCard key={track.id} track={track} index={index + 1} />
            ))
          ) : (
            <p className="text-text-dim p-4">No tracks available.</p>
          )}
        </div>
      </section>
    </div>
  );
}
