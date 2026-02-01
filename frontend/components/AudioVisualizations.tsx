'use client';

import { memo, useEffect, useState, useMemo } from 'react';

interface VUMeterProps {
  volume: number;
  size?: 'small' | 'normal';
}

/**
 * VUMeter - Classic analog VU meter visualization
 *
 * Displays a semi-circular meter with a swinging needle that responds to volume.
 * Perfect for cassette tape headers and album art.
 */
export const VUMeter = memo(function VUMeter({ volume, size = 'normal' }: VUMeterProps) {
  const isSmall = size === 'small';
  const width = isSmall ? 24 : 36;
  const height = isSmall ? 14 : 20;

  // Map volume (0-1) to needle angle (-35° to +35°)
  const angle = -35 + (volume * 70);

  return (
    <div
      className="relative"
      style={{ width: `${width}px`, height: `${height}px` }}
    >
      {/* Meter background arc */}
      <div
        className="absolute bottom-0 left-1/2 -translate-x-1/2 rounded-t-full opacity-70"
        style={{
          width: `${width}px`,
          height: `${width / 2}px`,
          background: 'linear-gradient(90deg, #3a5a30 0%, #8a8a30 50%, #8a4030 100%)',
        }}
      />

      {/* Needle */}
      <div
        className="absolute bottom-[2px] left-1/2 w-[2px] bg-[#1a1410] rounded-sm"
        style={{
          height: `${isSmall ? 12 : 16}px`,
          transformOrigin: 'bottom center',
          transform: `translateX(-50%) rotate(${angle}deg)`,
          transition: 'transform 0.08s ease-out',
        }}
      />

      {/* Center pivot */}
      <div
        className="absolute bottom-0 left-1/2 -translate-x-1/2 bg-[#e8a050] rounded-full"
        style={{
          width: `${isSmall ? 4 : 6}px`,
          height: `${isSmall ? 4 : 6}px`,
        }}
      />
    </div>
  );
});

interface SpinningReelProps {
  volume: number;
  size?: 'small' | 'normal';
  isPlaying?: boolean;
}

/**
 * SpinningReel - Animated tape reel visualization
 *
 * A spinning reel icon that rotates faster with higher volume.
 * Perfect for version cards and recording indicators.
 */
export const SpinningReel = memo(function SpinningReel({ volume, size = 'normal', isPlaying = true }: SpinningReelProps) {
  const isSmall = size === 'small';
  const diameter = isSmall ? 14 : 20;
  const [rotation, setRotation] = useState(0);

  useEffect(() => {
    if (!isPlaying) return;

    // Base speed + volume-reactive acceleration
    const speed = 2 + (volume * 8);

    const interval = setInterval(() => {
      setRotation(prev => (prev + speed) % 360);
    }, 50);

    return () => clearInterval(interval);
  }, [volume, isPlaying]);

  return (
    <div
      className="relative"
      style={{ width: `${diameter}px`, height: `${diameter}px` }}
    >
      {/* Outer ring with warm glow */}
      <div
        className="absolute inset-0 rounded-full"
        style={{
          background: 'radial-gradient(circle at 40% 40%, #5a4530, #3a3530)',
          border: `${isSmall ? 2 : 3}px solid #c85028`,
          boxShadow: '0 0 8px rgba(232, 160, 80, 0.3), inset 0 0 4px rgba(232, 160, 80, 0.2)',
        }}
      />

      {/* Spokes */}
      <div
        className="absolute inset-0"
        style={{
          transform: `rotate(${rotation}deg)`,
          transition: 'transform 0.05s linear',
        }}
      >
        {[0, 60, 120, 180, 240, 300].map((deg) => (
          <div
            key={deg}
            className="absolute top-1/2 left-1/2 bg-[#1a1410]"
            style={{
              width: `${diameter * 0.35}px`,
              height: '1px',
              transformOrigin: 'left center',
              transform: `rotate(${deg}deg)`,
            }}
          />
        ))}
      </div>

      {/* Center hub */}
      <div
        className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-[#4a4540] rounded-full border border-[#2a2520]"
        style={{
          width: `${diameter * 0.3}px`,
          height: `${diameter * 0.3}px`,
        }}
      />
    </div>
  );
});

interface WaveformProps {
  waveform: number[];
  size?: 'small' | 'normal';
  color?: string;
}

/**
 * Waveform - Audio waveform visualization
 *
 * Displays a real-time oscilloscope-style waveform from audio data.
 * Perfect for track rows showing "now playing" state.
 */
export const Waveform = memo(function Waveform({ waveform, size = 'normal', color = '#e8a050' }: WaveformProps) {
  const isSmall = size === 'small';
  const width = isSmall ? 30 : 50;
  const height = isSmall ? 12 : 16;

  const path = useMemo(() => {
    if (!waveform || waveform.length === 0) {
      // Flat line when no data
      return `M0,${height / 2} L${width},${height / 2}`;
    }

    const points = waveform.map((val, i) => {
      const x = (i / (waveform.length - 1)) * width;
      const y = (height / 2) + (val * height * 0.4);
      return `${x},${y}`;
    });

    return `M${points.join(' L')}`;
  }, [waveform, width, height]);

  return (
    <div
      className="overflow-hidden"
      style={{ width: `${width}px`, height: `${height}px` }}
    >
      <svg
        width={width}
        height={height}
        style={{ filter: `drop-shadow(0 0 3px ${color}60)` }}
      >
        <path
          d={path}
          fill="none"
          stroke={color}
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>
    </div>
  );
});

interface EQBarsProps {
  frequencyData: number[];
  size?: 'small' | 'normal';
  color?: string;
  barCount?: number;
}

/**
 * EQBars - Equalizer bars visualization
 *
 * Classic bouncing EQ bars that respond to frequency data.
 * Alternative to waveform for "now playing" indicators.
 */
export const EQBars = memo(function EQBars({
  frequencyData,
  size = 'normal',
  color = '#e8a050',
  barCount = 3
}: EQBarsProps) {
  const isSmall = size === 'small';
  const barWidth = isSmall ? 2 : 3;
  const maxHeight = isSmall ? 12 : 16;
  const gap = isSmall ? 1 : 2;

  // Get bar heights from frequency data
  const getBarHeight = (index: number) => {
    if (!frequencyData || frequencyData.length === 0) {
      return maxHeight * 0.3; // Default height
    }

    // Map bar index to frequency band
    const freqIndex = Math.floor((index / barCount) * frequencyData.length);
    const value = frequencyData[freqIndex] || 0;

    // Minimum height + scaled value
    return Math.max(4, value * maxHeight);
  };

  return (
    <div
      className="flex items-end"
      style={{
        height: `${maxHeight}px`,
        gap: `${gap}px`,
      }}
    >
      {Array.from({ length: barCount }).map((_, i) => (
        <div
          key={i}
          className="rounded-sm"
          style={{
            width: `${barWidth}px`,
            height: `${getBarHeight(i)}px`,
            backgroundColor: color,
            transition: 'height 0.08s ease-out',
          }}
        />
      ))}
    </div>
  );
});

interface PulsingDotProps {
  isPlaying: boolean;
  color?: string;
  size?: 'small' | 'normal';
}

/**
 * PulsingDot - Simple pulsing indicator
 *
 * A glowing dot that pulses when audio is playing.
 * Minimal CPU usage compared to waveform/EQ.
 */
export const PulsingDot = memo(function PulsingDot({ isPlaying, color = '#e8a050', size = 'normal' }: PulsingDotProps) {
  const isSmall = size === 'small';
  const diameter = isSmall ? 6 : 8;

  return (
    <div
      className={`rounded-full ${isPlaying ? 'animate-pulse' : ''}`}
      style={{
        width: `${diameter}px`,
        height: `${diameter}px`,
        backgroundColor: isPlaying ? color : '#4a4038',
        boxShadow: isPlaying ? `0 0 10px ${color}90` : 'none',
        transition: 'all 0.3s ease',
      }}
    />
  );
});
