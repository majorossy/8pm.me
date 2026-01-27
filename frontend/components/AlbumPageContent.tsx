'use client';

import { useState, useMemo, useEffect } from 'react';
import Link from 'next/link';
import { Album, Track, Song, formatDuration } from '@/lib/api';
import { useTheme } from '@/context/ThemeContext';
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
  const { theme } = useTheme();
  const { setBreadcrumbs } = useBreadcrumbs();
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';
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
  if (isJamify) {
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

  if (isMetro) {
    // Metro/Time Machine style - Sidebar + Timeline layout (desktop) / Stacked layout (mobile)
    return (
      <div className="min-h-screen bg-[#f8f6f1]">
        {/* Mobile: Album header (shows above content) */}
        <div className="lg:hidden pt-[70px] px-4 pb-4 bg-white border-b border-[#d4d0c8]">
          <div className="flex gap-4">
            {/* Album Cover */}
            <div className="relative w-28 h-28 flex-shrink-0 overflow-hidden">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full bg-[#e8e4dc] flex items-center justify-center">
                  <svg className="w-12 h-12 text-[#d4d0c8]" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
            </div>

            {/* Album Info */}
            <div className="flex-1 min-w-0">
              <h1 className="font-display text-lg font-extrabold text-[#1a1a1a] mb-1 truncate">{album.name}</h1>
              <p className="text-sm text-[#6b6b6b] mb-2">
                <Link href={`/artists/${album.artistSlug}`} className="text-[#e85d04] hover:underline">
                  {album.artistName}
                </Link>
              </p>
              <div className="flex gap-4 text-center text-xs mb-3">
                <div>
                  <div className="font-display font-bold text-[#e85d04]">{album.totalTracks}</div>
                  <div className="text-[#6b6b6b]">Tracks</div>
                </div>
                <div>
                  <div className="font-display font-bold text-[#e85d04]">{totalVersions}</div>
                  <div className="text-[#6b6b6b]">Versions</div>
                </div>
              </div>
              <button
                onClick={() => playAlbum(album, 0)}
                className={`w-full py-2 font-display text-xs font-medium transition-colors flex items-center justify-center gap-2 ${
                  isCurrentAlbum
                    ? 'bg-[#e85d04] text-white'
                    : 'bg-[#1a1a1a] text-white hover:bg-[#e85d04]'
                }`}
              >
                <span>{isCurrentAlbum && isPlaying ? '❚❚' : '▶'}</span>
                {isCurrentAlbum ? 'Now Playing' : 'Play Album'}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile: Track selector dropdown */}
        <div className="lg:hidden sticky top-[70px] bg-[#f8f6f1] z-20 px-4 py-3 border-b border-[#d4d0c8]">
          <select
            value={selectedTrackIndex}
            onChange={(e) => setSelectedTrackIndex(Number(e.target.value))}
            className="w-full text-sm bg-white border border-[#d4d0c8] px-3 py-2 text-[#1a1a1a] font-display font-semibold"
          >
            {album.tracks.map((track, index) => (
              <option key={track.id} value={index}>
                {String(index + 1).padStart(2, '0')}. {track.title} ({track.songCount} versions)
              </option>
            ))}
          </select>
        </div>

        {/* Desktop: Left Sidebar - Album Context */}
        <aside className="hidden lg:block fixed left-0 top-[60px] bottom-0 w-[320px] bg-white border-r border-[#d4d0c8] overflow-y-auto z-30">
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

            {/* Play Album Button */}
            <button
              onClick={() => playAlbum(album, 0)}
              className={`w-full py-3 font-display text-sm font-medium transition-colors flex items-center justify-center gap-2 mb-6 ${
                isCurrentAlbum
                  ? 'bg-[#e85d04] text-white'
                  : 'bg-[#1a1a1a] text-white hover:bg-[#e85d04]'
              }`}
            >
              <span>{isCurrentAlbum && isPlaying ? '❚❚' : '▶'}</span>
              {isCurrentAlbum ? 'Now Playing' : 'Play Album'}
            </button>

            {/* Track Navigation */}
            <nav>
              <div className="text-[0.65rem] uppercase tracking-[0.15em] text-[#6b6b6b] mb-3">Tracks</div>
              {album.tracks.length > 0 ? (
                album.tracks.map((track, index) => (
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
                ))
              ) : (
                <p className="text-sm text-[#6b6b6b] italic py-2">No tracks available</p>
              )}
            </nav>
          </div>
        </aside>

        {/* Main Content - Timeline */}
        <div className="lg:ml-[320px] lg:pt-[60px] min-h-screen">
          {album.tracks.length > 0 ? (
            <>
              {/* Timeline Header - Sticky (desktop only) */}
              <div className="hidden lg:block sticky top-[60px] bg-[#f8f6f1] z-20 px-8 py-6 border-b border-[#d4d0c8]">
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
              <div className="px-4 lg:px-8 pb-12">
                {yearGroups.map(([year, songs]) => (
              <div key={year} className="relative pl-0 lg:pl-[100px]">
                {/* Year Marker */}
                <div className="lg:absolute lg:left-0 lg:top-8 font-display text-3xl lg:text-5xl font-extrabold text-[#d4d0c8] mb-4 lg:mb-0 pt-4 lg:pt-0">
                  {year}
                </div>

                {/* Timeline Line - desktop only */}
                <div className="hidden lg:block absolute left-[85px] top-0 bottom-0 w-[2px] bg-[#d4d0c8]">
                  <div className="absolute top-10 left-[-4px] w-[10px] h-[10px] bg-[#e85d04] rounded-full" />
                </div>

                {/* Version Cards */}
                <div className="py-2 lg:py-6 lg:pl-8 space-y-4">
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
                              onClick={(e) => { e.stopPropagation(); addToUpNext(song); }}
                              className="px-4 py-2 text-xs font-medium border border-[#d4d0c8] text-[#1a1a1a] hover:border-[#1a1a1a] transition-colors"
                            >
                              + Up Next
                            </button>
                          </div>
                        </div>

                        {/* Details - same format as VersionCarousel */}
                        <div className="text-xs text-[#6b6b6b] space-y-1 pt-4 border-t border-[#d4d0c8]">
                          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
                            <span className="text-[#e85d04] font-medium">Taper</span>
                            <span className="text-[#1a1a1a]" title={song.taper || undefined}>{song.taper || '—'}</span>
                          </div>
                          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
                            <span className="text-[#e85d04] font-medium">Rating</span>
                            <span className="text-[#1a1a1a]">
                              {song.avgRating ? `${song.avgRating.toFixed(1)}★ (${song.numReviews || 0} reviews)` : '—'}
                            </span>
                          </div>
                          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
                            <span className="text-[#e85d04] font-medium">Source</span>
                            <span className="text-[#1a1a1a]" title={song.source || undefined}>{song.source || '—'}</span>
                          </div>
                          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
                            <span className="text-[#e85d04] font-medium">Length</span>
                            <span className="text-[#1a1a1a]">{formatDuration(song.duration)}</span>
                          </div>
                          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
                            <span className="text-[#e85d04] font-medium">Lineage</span>
                            <span className="text-[#1a1a1a] font-mono text-[10px]" title={song.lineage || undefined}>{song.lineage || '—'}</span>
                          </div>
                          <div className="flex justify-between py-1 border-b border-dotted border-[#d4d0c8]">
                            <span className="text-[#e85d04] font-medium">Notes</span>
                            <span className="text-[#1a1a1a]" title={song.notes || undefined}>{song.notes || '—'}</span>
                          </div>
                          <div className="flex justify-between py-1">
                            <span className="text-[#e85d04] font-medium">Identifier</span>
                            <span className="text-[#999] font-mono text-[10px]">{song.albumIdentifier}</span>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ))}
              </div>
            </>
          ) : (
            /* Empty State - No Live Recordings (Metro) */
            <div className="flex flex-col items-center justify-center py-24 px-8">
              {/* Vintage microphone illustration */}
              <div className="relative mb-8">
                <svg className="w-32 h-32 text-[#d4d0c8]" viewBox="0 0 128 128" fill="none">
                  {/* Microphone stand */}
                  <rect x="60" y="80" width="8" height="32" rx="2" fill="currentColor" />
                  <rect x="48" y="108" width="32" height="6" rx="3" fill="currentColor" />

                  {/* Microphone head */}
                  <path
                    d="M64 16C52.954 16 44 24.954 44 36V56C44 67.046 52.954 76 64 76C75.046 76 84 67.046 84 56V36C84 24.954 75.046 16 64 16Z"
                    fill="#e8e4dc"
                    stroke="currentColor"
                    strokeWidth="2"
                  />

                  {/* Microphone grille lines */}
                  <line x1="52" y1="28" x2="76" y2="28" stroke="currentColor" strokeWidth="2" />
                  <line x1="52" y1="36" x2="76" y2="36" stroke="currentColor" strokeWidth="2" />
                  <line x1="52" y1="44" x2="76" y2="44" stroke="currentColor" strokeWidth="2" />
                  <line x1="52" y1="52" x2="76" y2="52" stroke="currentColor" strokeWidth="2" />
                  <line x1="52" y1="60" x2="76" y2="60" stroke="currentColor" strokeWidth="2" />

                  {/* Sound waves - vintage style */}
                  <path
                    d="M28 46C28 46 24 51 24 56C24 61 28 66 28 66"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.5"
                  />
                  <path
                    d="M18 40C18 40 12 48 12 56C12 64 18 72 18 72"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.3"
                  />
                  <path
                    d="M100 46C100 46 104 51 104 56C104 61 100 66 100 66"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.5"
                  />
                  <path
                    d="M110 40C110 40 116 48 116 56C116 64 110 72 110 72"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.3"
                  />
                </svg>
              </div>

              <h3 className="font-display text-2xl font-extrabold text-[#1a1a1a] mb-3">No Live Recordings Found</h3>
              <p className="text-[#6b6b6b] text-center max-w-md mb-6">
                We couldn't find any live recordings for this album in the Archive.org collection.
                Check back later or explore other albums by this artist.
              </p>

              <Link
                href={`/artists/${album.artistSlug}`}
                className="inline-flex items-center gap-2 px-6 py-3 bg-[#1a1a1a] text-white hover:bg-[#e85d04] transition-colors font-display text-sm font-medium"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Browse Other Albums
              </Link>
            </div>
          )}
        </div>
      </div>
    );
  }

  // Default Tron/Synthwave style
  return (
    <div className="min-h-screen">
      {/* Breadcrumb */}
      <div className="pt-20 md:pt-24 px-4 md:px-6 lg:px-12 text-[0.6rem] md:text-[0.65rem] text-text-dim uppercase tracking-[0.1em]">
        <Link href="/" className="hover:text-neon-cyan transition-colors">Home</Link>
        {' / '}
        <Link href="/artists" className="hover:text-neon-cyan transition-colors">Artists</Link>
        {' / '}
        <Link href={`/artists/${album.artistSlug}`} className="hover:text-neon-cyan transition-colors">
          {album.artistName}
        </Link>
        {' / '}
        <span className="text-white truncate">{album.name}</span>
      </div>

      {/* Hero Section */}
      <section className="px-4 md:px-6 lg:px-12 py-6 md:py-8 max-w-[1400px] mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-[400px_1fr] gap-6 lg:gap-16 items-start">
          {/* Album Artwork */}
          <div className="relative max-w-[280px] md:max-w-[400px] mx-auto lg:mx-0">
            <div className="album-frame">
              {album.coverArt ? (
                <img
                  src={album.coverArt}
                  alt={album.name}
                  className="w-full aspect-square object-cover"
                />
              ) : (
                <div className="w-full aspect-square bg-dark-800 flex items-center justify-center">
                  <svg className="w-16 md:w-24 h-16 md:h-24 text-white/20" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                    <circle cx="12" cy="12" r="6" stroke="currentColor" strokeWidth="0.5" fill="none" opacity="0.5"/>
                    <circle cx="12" cy="12" r="3" fill="currentColor"/>
                  </svg>
                </div>
              )}
            </div>
            {/* Now Available Badge */}
            <div className="absolute -bottom-4 left-1/2 -translate-x-1/2 now-playing-badge font-display whitespace-nowrap text-[0.5rem] md:text-[0.6rem]">
              <span className="inline-block w-1.5 h-1.5 md:w-2 md:h-2 bg-neon-pink rounded-full mr-1.5 md:mr-2 blink-dot" />
              Now Available
            </div>
          </div>

          {/* Album Info */}
          <div className="pt-6 lg:pt-4 text-center lg:text-left">
            {/* Album type label */}
            <div className="font-display text-[0.55rem] md:text-[0.6rem] text-neon-cyan uppercase tracking-[0.3em] md:tracking-[0.4em] mb-3 md:mb-4 flex items-center justify-center lg:justify-start gap-2">
              <span className="opacity-50">//</span>
              {album.showDate ? 'Live Recording' : 'Studio Album'}
            </div>

            {/* Album title */}
            <h1 className="font-display text-2xl md:text-4xl lg:text-5xl xl:text-6xl font-black leading-[0.95] mb-2 gradient-text-title">
              {album.name.toUpperCase()}
            </h1>

            {/* Artist name */}
            <p className="text-base md:text-lg text-text-dim mb-4 md:mb-6">
              <Link href={`/artists/${album.artistSlug}`} className="text-neon-cyan hover:neon-text-cyan transition-all">
                {album.artistName}
              </Link>
              {album.showVenue && (
                <span className="hidden sm:inline"> — {album.showVenue}</span>
              )}
            </p>

            {/* Show date if available */}
            {album.showDate && (
              <p className="text-sm md:text-base text-text-dim mb-3 md:mb-4">{album.showDate}</p>
            )}

            {/* Stats Bar */}
            <div className="flex justify-center lg:justify-start gap-6 md:gap-12 py-4 md:py-6 stats-border mb-6 md:mb-8">
              <div className="flex flex-col gap-1">
                <span className="font-display text-2xl md:text-3xl font-bold text-neon-orange">
                  {album.totalTracks}
                </span>
                <span className="text-[0.5rem] md:text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                  Tracks
                </span>
              </div>
              <div className="flex flex-col gap-1">
                <span className="font-display text-2xl md:text-3xl font-bold text-neon-orange">
                  {formatDuration(album.totalDuration)}
                </span>
                <span className="text-[0.5rem] md:text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                  Duration
                </span>
              </div>
              {totalVersions > album.totalTracks && (
                <div className="flex flex-col gap-1">
                  <span className="font-display text-2xl md:text-3xl font-bold text-neon-orange">
                    {totalVersions}
                  </span>
                  <span className="text-[0.5rem] md:text-[0.55rem] text-text-dim uppercase tracking-[0.2em]">
                    Live Versions
                  </span>
                </div>
              )}
            </div>

            {/* Action Buttons */}
            <div className="flex justify-center lg:justify-start gap-4">
              <button
                onClick={() => playAlbum(album, 0)}
                className={`btn-neon px-6 md:px-8 py-3 md:py-4 font-display text-[0.65rem] md:text-xs uppercase tracking-[0.1em] ${
                  isCurrentAlbum ? 'shadow-[0_0_30px_var(--neon-cyan)]' : ''
                }`}
              >
                <span className="relative z-10 flex items-center gap-2">
                  {isCurrentAlbum && isPlaying ? (
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <rect x="6" y="4" width="4" height="16" />
                      <rect x="14" y="4" width="4" height="16" />
                    </svg>
                  ) : (
                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z" />
                    </svg>
                  )}
                  {isCurrentAlbum ? 'Now Playing' : 'Play Album'}
                </span>
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Tracks Section */}
      <section className="px-4 md:px-6 lg:px-12 py-6 md:py-8 max-w-[1400px] mx-auto">
        {/* Section Header */}
        <div className="flex justify-between items-center mb-6 md:mb-8 pb-3 md:pb-4 section-border">
          <h2 className="font-display text-[0.65rem] md:text-xs uppercase tracking-[0.2em] md:tracking-[0.3em] text-text-dim">
            <span className="text-neon-cyan">//</span> Track Listing
          </h2>
        </div>

        {/* Track List */}
        <div className="space-y-2">
          {album.tracks.length > 0 ? (
            album.tracks.map((track, index) => (
              <TrackCard key={track.id} track={track} index={index + 1} album={album} />
            ))
          ) : (
            /* Empty State - No Live Recordings (Tron/Synthwave) */
            <div className="flex flex-col items-center justify-center py-16 px-8 border border-neon-cyan/10">
              {/* Glowing microphone illustration */}
              <div className="relative mb-8">
                <svg className="w-32 h-32" viewBox="0 0 128 128" fill="none">
                  {/* Glow filter */}
                  <defs>
                    <filter id="neon-glow" x="-50%" y="-50%" width="200%" height="200%">
                      <feGaussianBlur stdDeviation="3" result="blur" />
                      <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                      </feMerge>
                    </filter>
                  </defs>

                  {/* Microphone stand */}
                  <rect x="60" y="80" width="8" height="32" rx="2" className="fill-neon-cyan/30" />
                  <rect x="48" y="108" width="32" height="6" rx="3" className="fill-neon-cyan/30" />

                  {/* Microphone head */}
                  <path
                    d="M64 16C52.954 16 44 24.954 44 36V56C44 67.046 52.954 76 64 76C75.046 76 84 67.046 84 56V36C84 24.954 75.046 16 64 16Z"
                    className="fill-neon-pink/60"
                    filter="url(#neon-glow)"
                  />

                  {/* Microphone grille lines */}
                  <line x1="52" y1="28" x2="76" y2="28" className="stroke-dark-900" strokeWidth="2" />
                  <line x1="52" y1="36" x2="76" y2="36" className="stroke-dark-900" strokeWidth="2" />
                  <line x1="52" y1="44" x2="76" y2="44" className="stroke-dark-900" strokeWidth="2" />
                  <line x1="52" y1="52" x2="76" y2="52" className="stroke-dark-900" strokeWidth="2" />
                  <line x1="52" y1="60" x2="76" y2="60" className="stroke-dark-900" strokeWidth="2" />

                  {/* Sound waves - neon style */}
                  <path
                    d="M28 46C28 46 24 51 24 56C24 61 28 66 28 66"
                    className="stroke-neon-cyan"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.6"
                    filter="url(#neon-glow)"
                  />
                  <path
                    d="M18 40C18 40 12 48 12 56C12 64 18 72 18 72"
                    className="stroke-neon-cyan"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.3"
                  />
                  <path
                    d="M100 46C100 46 104 51 104 56C104 61 100 66 100 66"
                    className="stroke-neon-cyan"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.6"
                    filter="url(#neon-glow)"
                  />
                  <path
                    d="M110 40C110 40 116 48 116 56C116 64 110 72 110 72"
                    className="stroke-neon-cyan"
                    strokeWidth="2"
                    strokeLinecap="round"
                    opacity="0.3"
                  />
                </svg>

                {/* Pulse ring */}
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="w-20 h-20 rounded-full border border-neon-pink/30 animate-ping" style={{ animationDuration: '3s' }} />
                </div>
              </div>

              <h3 className="font-display text-2xl font-bold text-white mb-3 gradient-text-title">
                NO LIVE RECORDINGS FOUND
              </h3>
              <p className="text-text-dim text-center max-w-md mb-6">
                We couldn't find any live recordings for this album in the Archive.org collection.
                Check back later or explore other albums by this artist.
              </p>

              <Link
                href={`/artists/${album.artistSlug}`}
                className="btn-neon px-6 py-3 font-display text-xs uppercase tracking-[0.1em] inline-flex items-center gap-2"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span className="relative z-10">Browse Other Albums</span>
              </Link>
            </div>
          )}
        </div>
      </section>
    </div>
  );
}
