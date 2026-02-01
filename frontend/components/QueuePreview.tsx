'use client';

import { useMemo } from 'react';
import { useQueue } from '@/context/QueueContext';
import { formatDuration } from '@/lib/api';
import { getSelectedSong } from '@/lib/queueTypes';
import { Song } from '@/lib/types';

interface QueuePreviewProps {
  className?: string;
}

export default function QueuePreview({ className = '' }: QueuePreviewProps) {
  const { queue, hasAlbum, hasUpNext } = useQueue();

  // Get all upcoming tracks (combining album tracks and upNext)
  const upcomingTracks = useMemo(() => {
    const tracks: { song: Song; source: 'album' | 'upnext' }[] = [];

    // Add remaining album tracks
    if (hasAlbum) {
      const remainingAlbumTracks = queue.tracks.slice(queue.currentTrackIndex + 1);
      for (const track of remainingAlbumTracks) {
        const song = getSelectedSong(track);
        if (song) {
          tracks.push({ song, source: 'album' });
        }
      }
    }

    // Add all upNext items
    if (hasUpNext) {
      for (const item of queue.upNext) {
        tracks.push({ song: item.song, source: 'upnext' });
      }
    }

    return tracks;
  }, [queue.tracks, queue.currentTrackIndex, queue.upNext, hasAlbum, hasUpNext]);

  if (upcomingTracks.length === 0) {
    return (
      <div className={`p-3 ${className}`}>
        <p className="text-xs text-[#8a8478] text-center">No upcoming tracks</p>
      </div>
    );
  }

  return (
    <div className={`p-2 ${className}`}>
      <p className="text-[10px] text-[#8a8478] uppercase tracking-wider mb-2 px-2">
        Up Next ({upcomingTracks.length} tracks)
      </p>
      <ul className="space-y-1 max-h-80 overflow-y-auto">
        {upcomingTracks.map(({ song, source }, index) => (
          <li
            key={`${song.id}-${index}`}
            className="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-[#2d2a26] transition-colors"
          >
            <span className="text-xs text-[#6a6458] w-5 text-center font-mono">
              {index + 1}
            </span>
            <div className="flex-1 min-w-0">
              <p className="text-sm text-white truncate">{song.title}</p>
              <p className="text-xs text-[#8a8478] truncate">{song.artistName}</p>
            </div>
            <span className="text-xs text-[#6a6458] font-mono flex-shrink-0">
              {formatDuration(song.duration)}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
