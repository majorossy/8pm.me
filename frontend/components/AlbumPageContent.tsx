'use client';

import { useState, useMemo, useEffect } from 'react';
import Link from 'next/link';
import { Album, Track, Song, formatDuration } from '@/lib/api';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import { usePlayer } from '@/context/PlayerContext';
import { useQueue } from '@/context/QueueContext';
import { useWishlist } from '@/context/WishlistContext';
import { useHaptic } from '@/hooks/useHaptic';

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
  const secs = Math.floor(seconds % 60);
  if (hours > 0) {
    return `${hours}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  }
  return `${mins}:${String(secs).padStart(2, '0')}`;
}

// Star rating component
function StarRating({ rating, count }: { rating?: number; count?: number }) {
  if (!rating) return <span className="text-[#6a5a48]">—</span>;
  const stars = Math.round(rating);
  return (
    <span className="text-[#e8a050]" title={`${rating.toFixed(1)} (${count || 0} reviews)`}>
      {'★'.repeat(stars)}{'☆'.repeat(5 - stars)}
      {count && <span className="text-[#8a7a68] ml-1">({count})</span>}
    </span>
  );
}

// Cassette tape component
function CassetteTape({
  album,
  isPlaying
}: {
  album: AlbumWithTracks;
  isPlaying: boolean;
}) {
  const year = album.showDate?.split('-')[0] || '';
  const formattedDate = album.showDate
    ? new Date(album.showDate).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: '2-digit' })
    : '';

  return (
    <div className="relative flex-shrink-0">
      {/* Decorative rose */}
      <div className="absolute -top-4 -right-5 text-3xl transform rotate-15 opacity-70 drop-shadow-md hidden md:block">
        🌹
      </div>

      {/* Main cassette body */}
      <div
        className="w-[280px] sm:w-[340px] h-[180px] sm:h-[220px] relative rounded-xl shadow-2xl"
        style={{
          background: 'linear-gradient(180deg, #2e2825 0%, #1e1a17 50%, #181512 100%)',
          transform: 'rotate(-1deg)'
        }}
      >
        {/* Corner screws */}
        {[[12, 12], [268, 12], [12, 168], [268, 168]].map(([x, y], i) => (
          <div
            key={i}
            className="absolute w-3 h-3 rounded-full hidden sm:block"
            style={{
              left: x - 6,
              top: y - 6,
              background: 'radial-gradient(circle at 35% 35%, #3a3530, #0a0808)'
            }}
          >
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-1.5 h-0.5 bg-[#0a0908]" />
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rotate-90 w-1.5 h-0.5 bg-[#0a0908]" />
          </div>
        ))}

        {/* Label area - cream colored */}
        <div
          className="absolute top-3 left-5 right-5 h-20 sm:h-24 rounded overflow-hidden shadow-lg"
          style={{
            background: 'linear-gradient(180deg, #faf4e8 0%, #f5ebda 50%, #efe1cc 100%)'
          }}
        >
          {/* Red header band */}
          <div
            className="h-5 sm:h-6 flex items-center justify-between px-3 text-[8px] sm:text-[9px] font-bold text-white tracking-wider"
            style={{ background: 'linear-gradient(180deg, #c85028 0%, #b84020 100%)' }}
          >
            <span>⚡ LIVE RECORDING ⚡</span>
            <span className="opacity-80 font-normal">Type II XL 90</span>
          </div>

          {/* Label content */}
          <div className="p-2 sm:p-3 relative">
            {/* Ruled lines */}
            <div className="absolute top-10 left-3 right-3 h-px bg-[#8b5a2b]/10" />
            <div className="absolute top-14 left-3 right-3 h-px bg-[#8b5a2b]/5" />

            <div className="flex justify-between items-start">
              <div>
                <div className="text-[#1a0f08] text-base sm:text-lg font-semibold font-serif truncate max-w-[180px] sm:max-w-[220px]">
                  {album.name} ☮
                </div>
                <div className="text-[#4a3020] text-[10px] sm:text-xs italic truncate max-w-[180px] sm:max-w-[220px]">
                  {album.artistName} — {album.showVenue || 'Live'}
                </div>
              </div>
              <div className="text-[#8a6a50] text-sm sm:text-base italic font-serif">
                '{year.slice(-2)}
              </div>
            </div>

            {/* Stealie doodle */}
            <div className="absolute bottom-1 right-3 text-base opacity-30">💀</div>
          </div>

          {/* Bottom bar */}
          <div
            className="absolute bottom-0 left-0 right-0 h-4 sm:h-5 flex items-center justify-center gap-3 text-[8px] sm:text-[9px] text-[#6a5040] border-t border-[#8b5a2b]/10"
            style={{ background: 'rgba(0,0,0,0.04)' }}
          >
            <span>{album.totalTracks} tracks</span>
            <span className="text-[#c85028]">✦</span>
            <span>{formatHours(album.totalDuration)}</span>
            <span className="text-[#c85028]">✦</span>
            <span>archive</span>
          </div>
        </div>

        {/* Tape window */}
        <div
          className="absolute top-[100px] sm:top-[118px] left-8 sm:left-10 right-8 sm:right-10 h-14 sm:h-[75px] rounded-md border-2 border-[#2a2520] overflow-hidden"
          style={{ background: '#080605' }}
        >
          {/* Glass shine */}
          <div
            className="absolute top-0 left-0 right-0 h-2/5"
            style={{ background: 'linear-gradient(180deg, rgba(255,255,255,0.03), transparent)' }}
          />

          {/* Left reel */}
          <div
            className={`absolute left-4 sm:left-6 top-1/2 -translate-y-1/2 w-10 sm:w-[52px] h-10 sm:h-[52px] rounded-full border-2 border-[#2a2520] ${isPlaying ? 'reel-spin-left' : 'reel-spin-left reel-paused'}`}
            style={{ background: 'radial-gradient(circle at 40% 40%, #2a2520, #0a0808)' }}
          >
            <div
              className="absolute inset-1 rounded-full"
              style={{ background: 'radial-gradient(circle, #4a3828, #2a1808)' }}
            />
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0a0808] border-2 border-[#2a2520]">
              {[0, 60, 120, 180, 240, 300].map(deg => (
                <div
                  key={deg}
                  className="absolute top-1/2 left-1/2 w-[7px] h-[1.5px] bg-[#3a3530]"
                  style={{ transform: `translate(-50%, -50%) rotate(${deg}deg)` }}
                />
              ))}
            </div>
          </div>

          {/* Right reel */}
          <div
            className={`absolute right-4 sm:right-6 top-1/2 -translate-y-1/2 w-10 sm:w-[52px] h-10 sm:h-[52px] rounded-full border-2 border-[#2a2520] ${isPlaying ? 'reel-spin-right' : 'reel-spin-right reel-paused'}`}
            style={{ background: 'radial-gradient(circle at 40% 40%, #2a2520, #0a0808)' }}
          >
            <div
              className="absolute inset-3 sm:inset-[14px] rounded-full"
              style={{ background: 'radial-gradient(circle, #4a3828, #2a1808)' }}
            />
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-[#0a0808] border-2 border-[#2a2520]">
              {[0, 60, 120, 180, 240, 300].map(deg => (
                <div
                  key={deg}
                  className="absolute top-1/2 left-1/2 w-[7px] h-[1.5px] bg-[#3a3530]"
                  style={{ transform: `translate(-50%, -50%) rotate(${deg}deg)` }}
                />
              ))}
            </div>
          </div>

          {/* Tape band */}
          <div
            className="absolute top-1/2 -translate-y-1/2 left-16 sm:left-[77px] right-16 sm:right-[77px] h-[3px]"
            style={{ background: 'linear-gradient(180deg, #5a4030, #3a2818, #5a4030)' }}
          />
        </div>

        {/* Side indicators */}
        <div className="absolute bottom-2 left-5 sm:left-7 flex items-center gap-1.5 text-[8px] sm:text-[9px] text-[#5a5048] tracking-wider">
          <div
            className={`w-2 h-2 rounded-full transition-all ${isPlaying ? 'bg-[#e8a050] shadow-[0_0_10px_rgba(232,160,80,0.6)]' : 'bg-[#4a4038]'}`}
          />
          SIDE A
        </div>
        <div className="absolute bottom-2 right-5 sm:right-7 flex items-center gap-1.5 text-[8px] sm:text-[9px] text-[#3a3530] tracking-wider">
          SIDE B
          <div className="w-2 h-2 rounded-full border border-[#3a3530]" />
        </div>
      </div>

      {/* Fire glow under cassette */}
      <div className="cassette-glow" />

      {/* Dancing bear decoration */}
      <div className="absolute -bottom-2 -left-6 text-2xl opacity-60 transform -rotate-10 hidden md:block">
        🐻
      </div>
    </div>
  );
}

// Recording card component
function RecordingCard({
  song,
  isSelected,
  isPlaying,
  onSelect,
  onPlay,
  onQueue,
}: {
  song: Song;
  isSelected: boolean;
  isPlaying: boolean;
  onSelect: () => void;
  onPlay: () => void;
  onQueue: () => void;
}) {
  const year = song.showDate?.split('-')[0] || '—';
  const formattedDate = song.showDate
    ? new Date(song.showDate + 'T00:00:00').toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: '2-digit' })
    : '—';

  const truncate = (text: string | undefined, max: number) => {
    if (!text) return '—';
    return text.length > max ? text.slice(0, max) + '...' : text;
  };

  return (
    <div
      onClick={onSelect}
      className={`
        min-w-[240px] cursor-pointer transition-all duration-200 rounded-xl overflow-hidden
        ${isSelected
          ? 'bg-gradient-to-b from-[#faf4e8] to-[#f0e4d0] border-2 border-[#e8a050] shadow-lg transform -translate-y-0.5'
          : 'bg-gradient-to-b from-[#2a2520] to-[#1e1a15] border border-[#3a3028] hover:shadow-xl hover:-translate-y-0.5'
        }
      `}
    >
      {/* Card header */}
      <div className={`px-4 pt-4 pb-3 border-b ${isSelected ? 'border-[#8b5a2b]/15' : 'border-[#a88060]/8'}`}>
        <div className="flex justify-between items-start">
          <span className={`text-4xl font-bold font-serif leading-none ${isSelected ? 'text-[#1a0f08]' : 'text-[#a89080]'}`}>
            {year}
          </span>
          {isPlaying && (
            <span
              className="text-[8px] font-bold px-2.5 py-1.5 rounded tracking-wider"
              style={{ background: 'linear-gradient(135deg, #c85028, #a84020)' }}
            >
              ⚡ PLAYING
            </span>
          )}
        </div>
      </div>

      {/* Card body */}
      <div className="px-4 py-3">
        <div className={`text-sm font-medium mb-1 ${isSelected ? 'text-[#2a1810]' : 'text-[#c8b8a8]'}`}>
          {truncate(song.showVenue, 24)}
        </div>
        <div className={`text-xs italic mb-3 ${isSelected ? 'text-[#5a4030]' : 'text-[#8a7a68]'}`}>
          {truncate(song.showLocation, 28)}
        </div>

        {/* Meta rows */}
        <div className="space-y-1.5 text-[11px]">
          {[
            ['Date', formattedDate],
            ['Rating', song.avgRating ? `★★★★★ (${song.numReviews || 0})` : '—'],
            ['Length', formatDuration(song.duration)],
            ['Taper', truncate(song.taper, 18)],
          ].map(([label, value]) => (
            <div key={label} className="flex justify-between">
              <span className={isSelected ? 'text-[#8a6a50]' : 'text-[#6a5a48]'}>{label}</span>
              <span
                className={`max-w-[130px] truncate ${
                  label === 'Rating'
                    ? (isSelected ? 'text-[#c85028]' : 'text-[#e8a050]')
                    : (isSelected ? 'text-[#2a1810]' : 'text-[#b8a898]')
                }`}
              >
                {value}
              </span>
            </div>
          ))}
        </div>
      </div>

      {/* Card buttons */}
      <div className={`flex gap-2.5 px-4 py-3 border-t ${isSelected ? 'border-[#8b5a2b]/10' : 'border-[#a88060]/5'}`}>
        <button
          onClick={(e) => { e.stopPropagation(); onPlay(); }}
          className={`
            flex-1 py-2.5 rounded-md text-xs font-semibold transition-all
            ${isSelected
              ? 'bg-gradient-to-r from-[#e8a050] to-[#c88030] text-[#1a1410]'
              : 'bg-gradient-to-r from-[#3a3028] to-[#2a2520] text-[#a89080] hover:from-[#4a4038] hover:to-[#3a3028]'
            }
          `}
        >
          ▶ Play
        </button>
        <button
          onClick={(e) => { e.stopPropagation(); onQueue(); }}
          className={`
            flex-1 py-2.5 rounded-md text-xs transition-all border
            ${isSelected
              ? 'border-[#c8a070] text-[#6a5040] hover:bg-[#e8d8c8]'
              : 'border-[#4a3a28] text-[#8a7a68] hover:border-[#6a5a48]'
            }
          `}
        >
          + Queue
        </button>
      </div>
    </div>
  );
}

// Track row component
function TrackRow({
  track,
  index,
  album,
  isExpanded,
  onToggle,
  onPlay,
  currentSong,
  isPlaying,
}: {
  track: Track;
  index: number;
  album: AlbumWithTracks;
  isExpanded: boolean;
  onToggle: () => void;
  onPlay: (song: Song) => void;
  currentSong: Song | null;
  isPlaying: boolean;
}) {
  const { addToUpNext } = useQueue();
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [sortOrder, setSortOrder] = useState<'newest' | 'oldest'>('newest');
  const isCurrentTrack = track.songs.some(s => s.id === currentSong?.id);

  // Sort recordings
  const sortedSongs = useMemo(() => {
    return [...track.songs].sort((a, b) => {
      const dateA = a.showDate || '0000-00-00';
      const dateB = b.showDate || '0000-00-00';
      return sortOrder === 'newest' ? dateB.localeCompare(dateA) : dateA.localeCompare(dateB);
    });
  }, [track.songs, sortOrder]);

  // Get the selected recording for header display
  const selectedSong = sortedSongs[selectedIndex];
  const selectedYear = selectedSong?.showDate?.split('-')[0] || '';
  const selectedVenue = selectedSong?.showVenue || '';
  const selectedRating = selectedSong?.avgRating;
  const selectedReviews = selectedSong?.numReviews;

  // Truncate venue for header
  const truncateVenue = (venue: string, max: number) => {
    if (!venue) return '';
    return venue.length > max ? venue.slice(0, max) + '…' : venue;
  };

  return (
    <div>
      {/* Track row */}
      <div
        onClick={onToggle}
        className={`
          grid grid-cols-[44px_1fr_auto] items-center px-4 py-4 cursor-pointer transition-all
          ${isExpanded
            ? 'bg-[rgba(232,160,80,0.06)] border-l-[3px] border-[#e8a050] rounded-tl-lg'
            : 'border-l-[3px] border-transparent hover:bg-[rgba(232,160,80,0.04)]'
          }
          ${!isExpanded ? 'border-b border-[#a88060]/8' : ''}
        `}
      >
        <div className={`text-base ${isExpanded || isCurrentTrack ? 'text-[#e8a050]' : 'text-[#6a5a48]'}`}>
          {isExpanded ? '▶' : `${index}.`}
        </div>
        <div className="min-w-0 flex-1">
          <div className={`text-base font-serif mb-1 ${isExpanded || isCurrentTrack ? 'text-[#e8d8c8]' : 'text-[#c8b8a8]'}`}>
            {track.title}
          </div>
          {/* Selected version info */}
          <div className="flex items-center gap-2 flex-wrap text-xs">
            {selectedYear && (
              <span className="text-[#e8a050] font-semibold">{selectedYear}</span>
            )}
            {selectedVenue && (
              <>
                <span className="text-[#4a3a28]">•</span>
                <span className="text-[#8a7a68] truncate max-w-[180px] sm:max-w-[280px]">
                  {truncateVenue(selectedVenue, 35)}
                </span>
              </>
            )}
            {selectedRating && (
              <>
                <span className="text-[#4a3a28]">•</span>
                <span className="text-[#e8a050]">
                  {'★'.repeat(Math.round(selectedRating))}
                  <span className="text-[#6a5a48] ml-0.5">({selectedReviews || 0})</span>
                </span>
              </>
            )}
            <span className="text-[#4a3a28]">•</span>
            <span className="text-[#4a9a8a] flex items-center gap-1">
              <span className="w-1.5 h-1.5 rounded-full bg-[#4a9a8a]" />
              {track.songCount} recordings
            </span>
          </div>
        </div>
        <div className="flex items-center justify-end gap-3 text-[#6a5a48] text-sm pl-3">
          {isExpanded && <span className="text-[#e8a050] text-base">+</span>}
          {formatDuration(track.totalDuration)}
          <span className={`text-[11px] ${isExpanded ? 'text-[#e8a050]' : 'text-[#4a3a28]'}`}>
            {isExpanded ? '▲' : '▼'}
          </span>
        </div>
      </div>

      {/* Expanded recordings panel */}
      {isExpanded && (
        <div className="bg-[rgba(232,160,80,0.03)] border-l-[3px] border-[#e8a050] border-b border-[#a88060]/8 rounded-bl-lg px-5 py-5">
          {/* Controls bar */}
          <div className="flex justify-between items-center mb-5">
            <div className="text-[#8a7a68] text-sm">
              Choose your recording ✦
            </div>
            <select
              value={sortOrder}
              onChange={(e) => setSortOrder(e.target.value as 'newest' | 'oldest')}
              className="bg-[#2a2520] border border-[#4a3a28] text-[#a89080] px-3 py-2 rounded-md text-xs"
            >
              <option value="newest">Best Rated</option>
              <option value="oldest">Oldest First</option>
            </select>
          </div>

          {/* Recording cards carousel */}
          <div className="flex gap-4 overflow-x-auto pb-2 scrollbar-thin">
            {sortedSongs.map((song, idx) => (
              <RecordingCard
                key={song.id}
                song={song}
                isSelected={idx === selectedIndex}
                isPlaying={currentSong?.id === song.id && isPlaying}
                onSelect={() => setSelectedIndex(idx)}
                onPlay={() => onPlay(song)}
                onQueue={() => addToUpNext(song)}
              />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

// Side divider component
function SideDivider({ side }: { side: 'A' | 'B' }) {
  return (
    <div className="flex items-center gap-4 my-6">
      <div
        className="flex-1 h-px"
        style={{ background: 'linear-gradient(90deg, transparent, rgba(168,128,96,0.25))' }}
      />
      <div className="text-[#8a7a68] text-[11px] tracking-[4px] flex items-center gap-2.5">
        <span className={side === 'A' ? 'text-[#e8a050]' : 'text-[#8a6a9a]'}>
          {side === 'A' ? '✧' : '☽'}
        </span>
        SIDE {side}
        <span className={side === 'A' ? 'text-[#e8a050]' : 'text-[#8a6a9a]'}>
          {side === 'A' ? '✧' : '☽'}
        </span>
      </div>
      <div
        className="flex-1 h-px"
        style={{ background: 'linear-gradient(90deg, rgba(168,128,96,0.25), transparent)' }}
      />
    </div>
  );
}

export default function AlbumPageContent({ album }: AlbumPageContentProps) {
  const { setBreadcrumbs } = useBreadcrumbs();
  const { currentSong, isPlaying, playSong, togglePlay, playAlbum } = usePlayer();
  const { queue, setShuffle } = useQueue();
  const { followAlbum, unfollowAlbum, isAlbumFollowed } = useWishlist();
  const { vibrate, BUTTON_PRESS } = useHaptic();

  const [expandedTrack, setExpandedTrack] = useState<number>(-1);

  // Check if this album is currently loaded in the queue
  const isCurrentAlbum = queue.album?.identifier === album.identifier;
  const albumIsPlaying = isCurrentAlbum && isPlaying;

  // Check if album is followed
  const isFollowed = isAlbumFollowed(album.artistSlug, album.name);

  useEffect(() => {
    setBreadcrumbs([
      { label: album.artistName, href: `/artists/${album.artistSlug}`, type: 'artist' },
      { label: album.name, type: 'album' }
    ]);
    return () => setBreadcrumbs([]);
  }, [setBreadcrumbs, album.artistName, album.artistSlug, album.name]);

  // Split tracks for Side A/B
  const midpoint = Math.ceil(album.tracks.length / 2);
  const sideATracks = album.tracks.slice(0, midpoint);
  const sideBTracks = album.tracks.slice(midpoint);

  const handlePlayPause = () => {
    vibrate(BUTTON_PRESS);
    if (isCurrentAlbum) {
      togglePlay();
    } else {
      playAlbum(album, 0);
    }
  };

  const handleShuffle = () => {
    vibrate(BUTTON_PRESS);
    setShuffle(true);
    playAlbum(album, 0);
  };

  const handleFollowToggle = () => {
    vibrate(BUTTON_PRESS);
    if (isFollowed) {
      unfollowAlbum(album.artistSlug, album.name);
    } else {
      followAlbum(album.artistSlug, album.name);
    }
  };

  const handlePlaySong = (song: Song) => {
    if (currentSong?.id === song.id && isPlaying) {
      togglePlay();
    } else {
      playSong(song);
    }
  };

  // Calculate total versions
  const totalVersions = album.tracks.reduce((acc, track) => acc + track.songCount, 0);

  return (
    <div className="min-h-screen font-serif text-[#e8d8c8] relative">
      {/* Page fireflies */}
      <div className="firefly fixed top-[20%] left-[10%] w-1.5 h-1.5" />
      <div className="firefly-2 fixed top-[60%] left-[85%] w-1 h-1" />
      <div className="firefly-3 fixed top-[40%] left-[75%] w-1.5 h-1.5" />

      {/* Vault header badge */}
      <div className="text-center pt-8 pb-4">
        <div className="text-[#6a5a48] text-[11px] tracking-[4px]">
          ✦ LIVE FROM THE VAULT ✦
        </div>
      </div>

      {/* Main content - max width centered */}
      <div className="max-w-[1000px] mx-auto px-4 sm:px-8 pb-36">

        {/* Hero section */}
        <div className="flex flex-col lg:flex-row gap-8 lg:gap-12 mb-12 items-center lg:items-start justify-center">
          {/* Cassette */}
          <CassetteTape album={album} isPlaying={albumIsPlaying} />

          {/* Album info */}
          <div className="pt-4 max-w-[400px] text-center lg:text-left">
            <div className="text-[#4a9a8a] text-[10px] tracking-[3px] mb-2.5">
              ☮ LIVE ALBUM
            </div>
            <h1 className="text-4xl sm:text-5xl lg:text-6xl text-[#e8d8c8] mb-2 leading-tight">
              {album.name}
            </h1>
            {album.showVenue && (
              <div className="text-xl text-[#a89080] mb-1.5 italic">
                {album.showVenue}
              </div>
            )}
            <div className="text-[#8a7a68] text-sm mb-6">
              <Link href={`/artists/${album.artistSlug}`} className="text-[#e8a050] hover:underline">
                {album.artistName}
              </Link>
              {album.showDate && <> • {album.showDate}</>}
              {' • '}{album.totalTracks} tracks
            </div>

            {/* Quote box */}
            {album.description && (
              <div
                className="text-[#6a5a48] text-sm italic mb-6 px-4 py-3 rounded-lg border-l-[3px] border-[#4a9a8a]"
                style={{ background: 'rgba(74,154,138,0.08)' }}
              >
                "{album.description}"
              </div>
            )}

            {/* Action buttons */}
            <div className="flex gap-3.5 items-center justify-center lg:justify-start">
              <button
                onClick={handlePlayPause}
                className="w-14 h-14 rounded-full flex items-center justify-center text-[#1a1410] text-xl shadow-lg transition-all hover:scale-105"
                style={{
                  background: 'linear-gradient(135deg, #e8a050, #c88030)',
                  boxShadow: '0 4px 20px rgba(232,160,80,0.4)'
                }}
              >
                {albumIsPlaying ? '❚❚' : '▶'}
              </button>
              <button
                onClick={handleShuffle}
                className="px-5 py-3.5 bg-transparent border border-[#4a3a28] rounded-md text-[#a89080] text-sm hover:border-[#6a5a48] hover:text-[#c8b8a8] transition-all"
              >
                ⟲ Shuffle
              </button>
              <button
                onClick={handleFollowToggle}
                className="w-11 h-11 rounded-full border border-[#4a3a28] flex items-center justify-center text-xl text-[#8a7a68] hover:border-[#6a5a48] hover:text-[#c8b8a8] transition-all"
              >
                {isFollowed ? '♥' : '♡'}
              </button>
            </div>
          </div>
        </div>

        {/* Side A divider */}
        <SideDivider side="A" />

        {/* Side A tracks */}
        <div className="mb-8">
          {sideATracks.map((track, idx) => (
            <TrackRow
              key={track.id}
              track={track}
              index={idx + 1}
              album={album}
              isExpanded={expandedTrack === idx}
              onToggle={() => setExpandedTrack(expandedTrack === idx ? -1 : idx)}
              onPlay={handlePlaySong}
              currentSong={currentSong}
              isPlaying={isPlaying}
            />
          ))}
        </div>

        {/* Side B divider */}
        {sideBTracks.length > 0 && (
          <>
            <SideDivider side="B" />

            {/* Side B tracks */}
            <div className="mb-8">
              {sideBTracks.map((track, idx) => {
                const actualIndex = midpoint + idx;
                return (
                  <TrackRow
                    key={track.id}
                    track={track}
                    index={actualIndex + 1}
                    album={album}
                    isExpanded={expandedTrack === actualIndex}
                    onToggle={() => setExpandedTrack(expandedTrack === actualIndex ? -1 : actualIndex)}
                    onPlay={handlePlaySong}
                    currentSong={currentSong}
                    isPlaying={isPlaying}
                  />
                );
              })}
            </div>
          </>
        )}

        {/* Footer */}
        <div className="mt-12 text-center text-[#4a3a28] text-[11px] flex flex-col items-center gap-2">
          <div className="text-[#6a5a48]">☮ Please copy freely — never sell ☮</div>
          <div>POWERED BY ARCHIVE.ORG</div>
        </div>
      </div>
    </div>
  );
}
