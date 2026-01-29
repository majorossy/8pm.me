'use client';

import { useQuality } from '@/context/QualityContext';
import { AudioQuality } from '@/lib/types';
import { useHaptic } from '@/hooks/useHaptic';

export default function QualitySelector() {
  const { preferredQuality, setPreferredQuality, getQualityLabel } = useQuality();
  const { vibrate, BUTTON_PRESS } = useHaptic();

  const qualities: AudioQuality[] = ['high', 'medium', 'low'];

  const handleChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    vibrate(BUTTON_PRESS);
    setPreferredQuality(e.target.value as AudioQuality);
  };

  return (
    <div className="flex items-center gap-2">
      {/* Quality icon */}
      <svg
        className="w-4 h-4 text-[#8a8478]"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"
        />
      </svg>

      {/* Dropdown selector */}
      <select
        value={preferredQuality}
        onChange={handleChange}
        className="bg-[#2a2520] border border-[#4a3a28] text-[#a89080] px-3 py-1.5 rounded-md text-sm focus:outline-none focus:border-[#d4a060] cursor-pointer hover:border-[#d4a060] transition-colors"
        aria-label="Audio quality preference"
      >
        {qualities.map(quality => (
          <option key={quality} value={quality}>
            {getQualityLabel(quality)}
          </option>
        ))}
      </select>
    </div>
  );
}
