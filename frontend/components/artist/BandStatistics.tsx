'use client';

import React from 'react';

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
    totalHours?: number;
  };
}

const BandStatistics: React.FC<BandStatisticsProps> = ({ statistics }) => {
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
    totalHours,
  } = statistics;

  // Jamify/Spotify styles
  const styles = {
    container: 'bg-[#252220] border border-[#2d2a26]',
    card: 'bg-[#252220] border border-[#2d2a26] hover:bg-[#2d2a26] transition-colors rounded-lg',
    title: 'text-green-400 font-bold',
    value: 'text-white font-semibold',
    label: 'text-gray-400',
    accent: 'text-green-500',
    glow: '',
  };

  return (
    <div className={`${styles.container} rounded-lg p-3 md:p-4 mb-6`}>
      <h2 className={`text-lg md:text-xl ${styles.title} mb-3`}>Archive Stats</h2>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-3">
        {/* Shows in Archive */}
        {totalShows !== undefined && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-3 md:p-4`}>
            <div className={`text-xs ${styles.label} uppercase tracking-wider mb-1`}>
              Shows in Archive
            </div>
            <div className={`text-2xl md:text-3xl ${styles.value} mb-0.5`}>
              {totalShows.toLocaleString()}
            </div>
            {yearsActive && (
              <div className={`text-[10px] ${styles.label}`}>
                {yearsActive.first} - {yearsActive.last}
              </div>
            )}
          </div>
        )}

        {/* Total Hours */}
        {totalHours !== undefined && totalHours > 0 && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-3 md:p-4`}>
            <div className={`text-xs ${styles.label} uppercase tracking-wider mb-1`}>
              Hours of Music
            </div>
            <div className={`text-2xl md:text-3xl ${styles.value} mb-0.5`}>
              {totalHours.toLocaleString()}+
            </div>
            <div className={`text-[10px] ${styles.label}`}>
              of live recordings
            </div>
          </div>
        )}

        {/* Most Played Track */}
        {mostPlayedTrack && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-3 md:p-4`}>
            <div className={`text-xs ${styles.label} uppercase tracking-wider mb-1`}>
              Most Played Track
            </div>
            <div className={`text-base md:text-lg ${styles.value} mb-0.5 truncate`}>
              {mostPlayedTrack.title}
            </div>
            <div className={`text-xs ${styles.accent}`}>
              {mostPlayedTrack.playCount.toLocaleString()} performances
            </div>
          </div>
        )}

        {/* Recording Stats */}
        {recordingStats && recordingStats.total > 0 && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-3 md:p-4`}>
            <div className={`text-xs ${styles.label} uppercase tracking-wider mb-1`}>
              Total Recordings
            </div>
            <div className={`text-2xl md:text-3xl ${styles.value} mb-0.5`}>
              {recordingStats.total.toLocaleString()}
            </div>
            {recordingStats.sources && Object.keys(recordingStats.sources).length > 0 && (
              <div className={`text-[10px] ${styles.label} space-y-0.5 mt-1`}>
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
          <div className={`${styles.card} ${styles.glow} rounded-lg p-3 md:p-4`}>
            <div className={`text-xs ${styles.label} uppercase tracking-wider mb-1`}>
              Avg. Set Length
            </div>
            <div className={`text-2xl md:text-3xl ${styles.value} mb-0.5`}>
              {averageSetLength}
            </div>
            <div className={`text-[10px] ${styles.label}`}>songs per show</div>
          </div>
        )}

        {/* Top Venues */}
        {topVenues && topVenues.length > 0 && (
          <div className={`${styles.card} ${styles.glow} rounded-lg p-3 md:p-4 md:col-span-2`}>
            <div className={`text-xs ${styles.label} uppercase tracking-wider mb-2`}>
              Top Venues
            </div>
            <div className="space-y-1.5">
              {topVenues.slice(0, 3).map((venue, index) => (
                <div key={index} className="flex justify-between items-center">
                  <span className={`${styles.value} text-xs md:text-sm truncate mr-4`}>
                    {venue.name}
                  </span>
                  <span className={`${styles.accent} text-xs md:text-sm font-semibold`}>
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
