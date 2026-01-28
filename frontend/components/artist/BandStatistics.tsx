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
    totalVenues?: number;
    mostPlayedTrack?: {
      title: string;
      playCount: number;
    };
    recordingStats?: RecordingStats;
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
    totalVenues,
    recordingStats,
    totalHours,
    yearsActive,
  } = statistics;

  // Build stats array for ember display
  const stats = [];

  if (recordingStats && recordingStats.total > 0) {
    stats.push({
      value: recordingStats.total.toLocaleString(),
      label: 'recordings',
    });
  }

  if (totalHours !== undefined && totalHours > 0) {
    stats.push({
      value: `${totalHours.toLocaleString()}+`,
      label: 'hours',
    });
  }

  if (totalShows !== undefined) {
    stats.push({
      value: totalShows.toLocaleString(),
      label: 'shows',
    });
  }

  if (totalVenues !== undefined && totalVenues > 0) {
    stats.push({
      value: totalVenues.toLocaleString(),
      label: 'venues',
    });
  }

  if (yearsActive?.first) {
    // Format year as '99 instead of 1999
    const yearStr = yearsActive.first.toString();
    const shortYear = `'${yearStr.slice(-2)}`;

    stats.push({
      value: shortYear,
      label: 'since year',
    });
  }

  // Return null if no stats to display
  if (stats.length === 0) {
    return null;
  }

  return (
    <section className="py-6">
      <h2 className="text-xl md:text-2xl font-bold text-white mb-6 text-center">Archive Stats</h2>

      <div style={{ position: 'relative', overflow: 'hidden' }}>
        {/* Ambient glow */}
        <div
          style={{
            position: 'absolute',
            bottom: '-50px',
            left: '50%',
            transform: 'translateX(-50%)',
            width: '80%',
            height: '100px',
            background: 'radial-gradient(ellipse, rgba(255,100,30,0.15) 0%, transparent 70%)',
            filter: 'blur(20px)',
          }}
        />

        <div
          style={{
            display: 'flex',
            gap: '20px',
            justifyContent: 'center',
            flexWrap: 'nowrap',
            position: 'relative',
            zIndex: 1,
          }}
        >
          {stats.map((stat, i) => (
            <div key={i} style={{ textAlign: 'center', minWidth: '60px' }}>
              <div
                className={`ember-value ember-${i}`}
                style={{
                  fontFamily: 'Georgia, serif',
                  fontSize: '36px',
                  fontWeight: 'bold',
                  background:
                    'linear-gradient(180deg, #fff8e7 0%, #ffb347 30%, #ff6b35 60%, #cc3300 100%)',
                  WebkitBackgroundClip: 'text',
                  WebkitTextFillColor: 'transparent',
                  backgroundClip: 'text',
                  filter: 'drop-shadow(0 0 10px rgba(255,150,50,0.4))',
                  lineHeight: 1,
                }}
              >
                {stat.value}
              </div>
              <div
                style={{
                  fontFamily: 'Georgia, serif',
                  fontSize: '10px',
                  color: '#8b6914',
                  letterSpacing: '1.5px',
                  textTransform: 'uppercase',
                  marginTop: '6px',
                }}
              >
                {stat.label}
              </div>
            </div>
          ))}
        </div>

        <style>{`
          .ember-0 { animation: ember-flicker-0 2s ease-in-out infinite; }
          .ember-1 { animation: ember-flicker-1 2.3s ease-in-out infinite; }
          .ember-2 { animation: ember-flicker-2 1.8s ease-in-out infinite; }
          .ember-3 { animation: ember-flicker-3 2.1s ease-in-out infinite; }
          .ember-4 { animation: ember-flicker-4 2.4s ease-in-out infinite; }

          @keyframes ember-flicker-0 {
            0%, 100% { opacity: 1; filter: drop-shadow(0 0 10px rgba(255,150,50,0.4)); }
            50% { opacity: 0.85; filter: drop-shadow(0 0 15px rgba(255,150,50,0.6)); }
          }
          @keyframes ember-flicker-1 {
            0%, 100% { opacity: 0.9; filter: drop-shadow(0 0 12px rgba(255,150,50,0.5)); }
            50% { opacity: 1; filter: drop-shadow(0 0 8px rgba(255,150,50,0.3)); }
          }
          @keyframes ember-flicker-2 {
            0%, 100% { opacity: 0.95; filter: drop-shadow(0 0 8px rgba(255,150,50,0.4)); }
            50% { opacity: 0.8; filter: drop-shadow(0 0 18px rgba(255,150,50,0.7)); }
          }
          @keyframes ember-flicker-3 {
            0%, 100% { opacity: 0.85; filter: drop-shadow(0 0 14px rgba(255,150,50,0.5)); }
            50% { opacity: 1; filter: drop-shadow(0 0 6px rgba(255,150,50,0.3)); }
          }
          @keyframes ember-flicker-4 {
            0%, 100% { opacity: 1; filter: drop-shadow(0 0 9px rgba(255,150,50,0.45)); }
            50% { opacity: 0.9; filter: drop-shadow(0 0 16px rgba(255,150,50,0.55)); }
          }
        `}</style>
      </div>
    </section>
  );
};

export default BandStatistics;
