'use client';

import { useRef, useEffect, useCallback, CSSProperties, ComponentType } from 'react';
import { List } from 'react-window';
import { Song } from '@/lib/types';
import { formatDuration } from '@/lib/api';
import { useHaptic } from '@/hooks/useHaptic';

// Props for list row components
interface RowProps {
  index: number;
  style: CSSProperties;
}

interface VirtualizedSongListProps {
  songs: Array<{ song: Song; id: string }>;
  onPlay: (song: Song) => void;
  onRemove?: (id: string) => void;
  onAddToQueue?: (song: Song) => void;
  containerHeight?: number;
  rowHeight?: number;
  scrollKey?: string; // For persisting scroll position across tab changes
}

// Store scroll positions by key
const scrollPositions = new Map<string, number>();

export function VirtualizedSongList({
  songs,
  onPlay,
  onRemove,
  onAddToQueue,
  containerHeight = 500,
  rowHeight = 72,
  scrollKey,
}: VirtualizedSongListProps) {
  const listRef = useRef<any>(null);
  const { vibrate, BUTTON_PRESS, DELETE_ACTION } = useHaptic();

  // Restore scroll position on mount
  useEffect(() => {
    if (scrollKey && listRef.current) {
      const savedPosition = scrollPositions.get(scrollKey);
      if (savedPosition !== undefined) {
        listRef.current.scrollTo(savedPosition);
      }
    }
  }, [scrollKey]);

  // Save scroll position on unmount
  useEffect(() => {
    return () => {
      if (scrollKey && listRef.current) {
        // Access internal state for scroll offset
        const scrollOffset = (listRef.current as any)?.state?.scrollOffset ?? 0;
        scrollPositions.set(scrollKey, scrollOffset);
      }
    };
  }, [scrollKey]);

  const Row = useCallback(({ index, style }: RowProps): JSX.Element => {
    const item = songs[index];
    if (!item) return <div style={style} />;

    const { song, id } = item;

    return (
      <div
        style={style}
        className="flex items-center gap-3 px-4 py-2 hover:bg-white/5 group transition-colors"
      >
        {/* Track number */}
        <span className="w-6 text-sm text-[#a7a7a7] text-right flex-shrink-0">
          {index + 1}
        </span>

        {/* Play button / Song info */}
        <button
          onClick={() => {
            vibrate(BUTTON_PRESS);
            onPlay(song);
          }}
          className="flex-1 min-w-0 text-left flex items-center gap-3"
        >
          {/* Album art thumbnail */}
          {song.albumArt && (
            <img
              src={song.albumArt}
              alt=""
              className="w-10 h-10 rounded flex-shrink-0 bg-[#282828]"
              loading="lazy"
            />
          )}
          <div className="min-w-0 flex-1">
            <p className="text-white text-sm font-medium truncate group-hover:text-[#1DB954] transition-colors">
              {song.title}
            </p>
            <p className="text-[#a7a7a7] text-xs truncate">
              {song.artistName}
            </p>
          </div>
        </button>

        {/* Duration */}
        <span className="text-sm text-[#a7a7a7] flex-shrink-0 w-12 text-right">
          {formatDuration(song.duration)}
        </span>

        {/* Actions */}
        <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          {onAddToQueue && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                vibrate(BUTTON_PRESS);
                onAddToQueue(song);
              }}
              className="p-2 text-[#a7a7a7] hover:text-white transition-colors"
              title="Add to queue"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M15 6H3v2h12V6zm0 4H3v2h12v-2zM3 16h8v-2H3v2zM17 6v8.18c-.31-.11-.65-.18-1-.18-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3V8h3V6h-5z" />
              </svg>
            </button>
          )}
          {onRemove && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                vibrate(DELETE_ACTION);
                onRemove(id);
              }}
              className="p-2 text-[#a7a7a7] hover:text-red-500 transition-colors"
              title="Remove"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          )}
        </div>
      </div>
    );
  }, [songs, onPlay, onRemove, onAddToQueue, vibrate, BUTTON_PRESS, DELETE_ACTION]);

  if (songs.length === 0) {
    return (
      <div className="flex items-center justify-center h-48 text-[#a7a7a7]">
        No songs found
      </div>
    );
  }

  return (
    <List
      ref={listRef}
      height={containerHeight}
      itemCount={songs.length}
      itemSize={rowHeight}
      width="100%"
      className="scrollbar-thin scrollbar-thumb-[#535353] scrollbar-track-transparent"
    >
      {Row}
    </List>
  );
}

// Recently played variant with timestamp
interface RecentlyPlayedItem {
  song: Song;
  playedAt: number;
}

interface VirtualizedRecentListProps {
  items: RecentlyPlayedItem[];
  onPlay: (song: Song) => void;
  containerHeight?: number;
  rowHeight?: number;
  scrollKey?: string;
}

export function VirtualizedRecentList({
  items,
  onPlay,
  containerHeight = 500,
  rowHeight = 72,
  scrollKey,
}: VirtualizedRecentListProps) {
  const listRef = useRef<any>(null);
  const { vibrate, BUTTON_PRESS } = useHaptic();

  useEffect(() => {
    if (scrollKey && listRef.current) {
      const savedPosition = scrollPositions.get(scrollKey);
      if (savedPosition !== undefined) {
        listRef.current.scrollTo(savedPosition);
      }
    }
  }, [scrollKey]);

  useEffect(() => {
    return () => {
      if (scrollKey && listRef.current) {
        const scrollOffset = (listRef.current as any)?.state?.scrollOffset ?? 0;
        scrollPositions.set(scrollKey, scrollOffset);
      }
    };
  }, [scrollKey]);

  const formatTimeAgo = (timestamp: number): string => {
    const now = Date.now();
    const diff = now - timestamp;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return new Date(timestamp).toLocaleDateString();
  };

  const Row = useCallback(({ index, style }: RowProps): JSX.Element => {
    const item = items[index];
    if (!item) return <div style={style} />;

    const { song, playedAt } = item;

    return (
      <div
        style={style}
        className="flex items-center gap-3 px-4 py-2 hover:bg-white/5 group transition-colors"
      >
        <button
          onClick={() => {
            vibrate(BUTTON_PRESS);
            onPlay(song);
          }}
          className="flex-1 min-w-0 text-left flex items-center gap-3"
        >
          {song.albumArt && (
            <img
              src={song.albumArt}
              alt=""
              className="w-10 h-10 rounded flex-shrink-0 bg-[#282828]"
              loading="lazy"
            />
          )}
          <div className="min-w-0 flex-1">
            <p className="text-white text-sm font-medium truncate group-hover:text-[#1DB954] transition-colors">
              {song.title}
            </p>
            <p className="text-[#a7a7a7] text-xs truncate">
              {song.artistName}
            </p>
          </div>
        </button>

        <span className="text-xs text-[#a7a7a7] flex-shrink-0">
          {formatTimeAgo(playedAt)}
        </span>

        <span className="text-sm text-[#a7a7a7] flex-shrink-0 w-12 text-right">
          {formatDuration(song.duration)}
        </span>
      </div>
    );
  }, [items, onPlay, vibrate, BUTTON_PRESS]);

  if (items.length === 0) {
    return (
      <div className="flex items-center justify-center h-48 text-[#a7a7a7]">
        No recently played tracks
      </div>
    );
  }

  return (
    <List
      ref={listRef}
      height={containerHeight}
      itemCount={items.length}
      itemSize={rowHeight}
      width="100%"
      className="scrollbar-thin scrollbar-thumb-[#535353] scrollbar-track-transparent"
    >
      {Row}
    </List>
  );
}
