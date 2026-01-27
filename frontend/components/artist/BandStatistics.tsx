'use client';

import React from 'react';
import { useTheme } from '@/context/ThemeContext';

interface RecordingStats {
  total: number;
  sources?: {
    [key: string]: number;
  };
}

interface BandStatisticsProps {
  statistics?: {
    totalShows?: number;
    mostPlayedTrack?: {
      title: string;
      playCount: number;
    };
    recordingStats?: RecordingStats;
    averageSetLength?: number;
    yearsActive?: {
      first: number;
      last: number;
    };
    topVenues?: Array<{
      name: string;
      showCount: number;
    }>;
  };
}

const BandStatistics: React.FC<BandStatisticsProps> = ({ statistics }) => {
  const { theme } = useTheme();

  // Return null if no statistics data or all values are undefined
  if (!statistics || Object.values(statistics).every(val => val === undefined || val === null)) {
    return null;
  }

  const {
    totalShows,
    mostPlayedTrack,
    recordingStats,
    averageSetLength,
    yearsActive,
    topVenues,
  } = statistics;

  // Theme-specific styles
  const getThemeStyles = () => {
    switch (theme) {
      case 'tron':
        return {
          container: 'bg-gray-900 border border-cyan-500/30',
          card: 'bg-black/50 border border-cyan-500/50 shadow-lg shadow-cyan-500/20',
          title: 'text-cyan-400 font-bold',
          value: 'text-cyan-300',
          label: 'text-gray-400',
          accent: 'text-cyan-500',
          glow: 'shadow-[0_0_10px_rgba(6,182,212,0.3)]',
        };
      case 'metro':
        return {
          container: 'bg-white border border-gray-200',
          card: 'bg-white border border-gray-200 shadow-md hover:shadow-lg transition-shadow',
          title: 'text-orange-600 font-bold',
          value: 'text-gray-900 font-semibold',
          label: 'text-gray-600',
          accent: 'text-orange-500',
          glow: '',
        };
      case 'jamify':
        return {
          container: 'bg-[#181818] border border-[#282828]',
          card: 'bg-[#181818] border border-[#282828] hover:bg-[#282828] transition-colors rounded-lg',
          title: 'text-green-400 font-bold',
          value: 'text-white font-semibold',
          label: 'text-gray-400',
          accent: 'text-green-500',
          glow: '',
        };
      default:
        return {
          container: 'bg-gray-900 border border-cyan-500/30',
          card: 'bg-black/50 border border-cyan-500/50',
          title: 'text-cyan-400 font-bold',
          value: 'text-cyan-300',
          label: 'text-gray-400',
          accent: 'text-cyan-500',
          glow: '',
        };
    }
  };

  const styles = getThemeStyles();

  return (
    <div className={`${styles.container} rounded-lg p-6 mb-8`}>
      <h2 className={`text-2xl ${styles.title} mb-6`}>Band Statistics</h2>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {/* Total Shows */}
        {totalShows !== undefined && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-6`}>
            <div className={`text-sm ${styles.label} uppercase tracking-wider mb-2`}>
              Total Shows
            </div>
            <div className={`text-4xl ${styles.value} mb-1`}>
              {totalShows.toLocaleString()}
            </div>
            {yearsActive && (
              <div className={`text-xs ${styles.label}`}>
                {yearsActive.first} - {yearsActive.last}
              </div>
            )}
          </div>
        )}

        {/* Most Played Track */}
        {mostPlayedTrack && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-6`}>
            <div className={`text-sm ${styles.label} uppercase tracking-wider mb-2`}>
              Most Played Track
            </div>
            <div className={`text-xl ${styles.value} mb-1 truncate`}>
              {mostPlayedTrack.title}
            </div>
            <div className={`text-sm ${styles.accent}`}>
              {mostPlayedTrack.playCount.toLocaleString()} performances
            </div>
          </div>
        )}

        {/* Recording Stats */}
        {recordingStats && recordingStats.total > 0 && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-6`}>
            <div className={`text-sm ${styles.label} uppercase tracking-wider mb-2`}>
              Total Recordings
            </div>
            <div className={`text-4xl ${styles.value} mb-1`}>
              {recordingStats.total.toLocaleString()}
            </div>
            {recordingStats.sources && Object.keys(recordingStats.sources).length > 0 && (
              <div className={`text-xs ${styles.label} space-y-1 mt-2`}>
                {Object.entries(recordingStats.sources).map(([source, count]) => (
                  <div key={source} className="flex justify-between">
                    <span className="capitalize">{source}:</span>
                    <span className={styles.accent}>{count}</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Average Set Length */}
        {averageSetLength !== undefined && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-6`}>
            <div className={`text-sm ${styles.label} uppercase tracking-wider mb-2`}>
              Avg. Set Length
            </div>
            <div className={`text-4xl ${styles.value} mb-1`}>
              {averageSetLength}
            </div>
            <div className={`text-xs ${styles.label}`}>songs per show</div>
          </div>
        )}

        {/* Top Venues */}
        {topVenues && topVenues.length > 0 && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-6 md:col-span-2`}>
            <div className={`text-sm ${styles.label} uppercase tracking-wider mb-3`}>
              Top Venues
            </div>
            <div className="space-y-2">
              {topVenues.slice(0, 3).map((venue, index) => (
                <div key={index} className="flex justify-between items-center">
                  <span className={`${styles.value} text-sm truncate mr-4`}>
                    {venue.name}
                  </span>
                  <span className={`${styles.accent} text-sm font-semibold`}>
                    {venue.showCount} shows
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default BandStatistics;
