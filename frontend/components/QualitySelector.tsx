'use client';

import { useQuality } from '@/context/QualityContext';
import { AudioQuality } from '@/lib/types';
import { useHaptic } from '@/hooks/useHaptic';
import { useState, useRef, useEffect } from 'react';

interface QualityOption {
  value: AudioQuality;
  label: string;
  format: string;
  bitrate: string;
  size: string;
  recommended?: boolean;
}

const QUALITY_OPTIONS: QualityOption[] = [
  {
    value: 'high',
    label: 'High',
    format: 'FLAC',
    bitrate: 'Lossless',
    size: '~45MB'
  },
  {
    value: 'medium',
    label: 'Medium',
    format: 'MP3',
    bitrate: '320kbps',
    size: '~10MB',
    recommended: true
  },
  {
    value: 'low',
    label: 'Low',
    format: 'MP3',
    bitrate: '128kbps',
    size: '~4MB'
  }
];

export default function QualitySelector() {
  const { preferredQuality, setPreferredQuality } = useQuality();
  const { vibrate, BUTTON_PRESS } = useHaptic();
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const selectedOption = QUALITY_OPTIONS.find(opt => opt.value === preferredQuality) || QUALITY_OPTIONS[1];

  // Close dropdown when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [isOpen]);

  // Close on Escape key
  useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('keydown', handleEscape);
      return () => document.removeEventListener('keydown', handleEscape);
    }
  }, [isOpen]);

  const handleSelect = (quality: AudioQuality) => {
    vibrate(BUTTON_PRESS);
    setPreferredQuality(quality);
    setIsOpen(false);
  };

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Trigger Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 bg-[#2a2520] border border-[#4a3a28] text-[#a89080] px-3 py-2 rounded-lg hover:border-[#d4a060] focus:outline-none focus:border-[#d4a060] transition-all duration-200 group"
        aria-label="Select audio quality"
        aria-haspopup="true"
        aria-expanded={isOpen}
      >
        {/* Music icon */}
        <svg
          className="w-4 h-4 text-[#8a8478] group-hover:text-[#d4a060] transition-colors"
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

        {/* Selected quality label */}
        <span className="text-sm font-medium">{selectedOption.label}</span>

        {/* Chevron */}
        <svg
          className={`w-4 h-4 text-[#8a8478] transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {/* Dropdown Menu */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-72 bg-[#1c1a17] border border-[#4a3a28] rounded-lg shadow-2xl overflow-hidden z-50 animate-fadeIn">
          {QUALITY_OPTIONS.map((option, index) => (
            <button
              key={option.value}
              onClick={() => handleSelect(option.value)}
              className={`w-full px-4 py-3 text-left hover:bg-[#2a2520] transition-colors duration-150 ${
                option.value === preferredQuality ? 'bg-[#2a2520]' : ''
              } ${index !== QUALITY_OPTIONS.length - 1 ? 'border-b border-[#2a2520]' : ''}`}
            >
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    {/* Quality name */}
                    <span className="text-sm font-semibold text-[#d4a060]">
                      {option.label}
                    </span>

                    {/* Recommended badge */}
                    {option.recommended && (
                      <span className="px-2 py-0.5 text-xs font-medium bg-[#d4a060] text-[#1c1a17] rounded-full">
                        Recommended
                      </span>
                    )}

                    {/* Selected checkmark */}
                    {option.value === preferredQuality && (
                      <svg
                        className="w-4 h-4 text-[#d4a060] ml-auto"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fillRule="evenodd"
                          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                          clipRule="evenodd"
                        />
                      </svg>
                    )}
                  </div>

                  {/* Format and bitrate */}
                  <div className="text-xs text-[#a89080] space-y-0.5">
                    <div className="flex items-center gap-2">
                      <span className="text-[#8a8478]">Format:</span>
                      <span className="font-medium">{option.format}</span>
                      <span className="text-[#6a6458]">•</span>
                      <span className="text-[#8a8478]">Bitrate:</span>
                      <span className="font-medium">{option.bitrate}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-[#8a8478]">Size:</span>
                      <span className="font-medium">{option.size}</span>
                      <span className="text-[#6a6458]">per track</span>
                    </div>
                  </div>
                </div>
              </div>
            </button>
          ))}
          {/* Notice about quality change timing */}
          <div className="px-4 py-2 bg-[#1c1a17] border-t border-[#2a2520]">
            <p className="text-[10px] text-[#6a6458] text-center italic">
              Quality changes apply to next track
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
