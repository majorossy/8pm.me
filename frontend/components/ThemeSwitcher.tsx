'use client';

import { useState, useRef, useEffect } from 'react';
import { useTheme, ThemeType, THEMES } from '@/context/ThemeContext';

export default function ThemeSwitcher() {
  const { theme, setTheme } = useTheme();
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const themeColors: Record<ThemeType, string> = {
    tron: 'bg-gradient-to-r from-[#00f0ff] to-[#ff2d95]',
    metro: 'bg-gradient-to-r from-[#f8f6f1] to-[#e85d04]',
    minimal: 'bg-gradient-to-r from-gray-400 to-gray-600',
    classic: 'bg-gradient-to-r from-amber-500 to-orange-600',
    forest: 'bg-gradient-to-r from-emerald-500 to-teal-600',
  };

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Toggle Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 text-text-dim text-xs uppercase tracking-[0.2em] hover:text-white transition-all group"
        aria-label="Switch theme"
      >
        <span className={`w-3 h-3 rounded-full ${themeColors[theme]} shadow-lg`} />
        <span className="hidden sm:inline">Theme</span>
        <svg
          className={`w-3 h-3 transition-transform ${isOpen ? 'rotate-180' : ''}`}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      {/* Dropdown Menu */}
      {isOpen && (
        <div className="absolute right-0 mt-3 w-48 py-2 bg-dark-800 border border-white/10 shadow-xl z-50">
          <div className="px-3 py-2 text-[10px] text-text-dim uppercase tracking-wider border-b border-white/10">
            Select Template
          </div>
          {(Object.keys(THEMES) as ThemeType[]).map((themeKey) => {
            const config = THEMES[themeKey];
            const isActive = theme === themeKey;
            return (
              <button
                key={themeKey}
                onClick={() => {
                  setTheme(themeKey);
                  setIsOpen(false);
                }}
                className={`w-full px-3 py-2.5 flex items-center gap-3 text-left transition-all ${
                  isActive
                    ? 'bg-white/5 text-white'
                    : 'text-text-dim hover:bg-white/5 hover:text-white'
                }`}
              >
                <span className={`w-3 h-3 rounded-full ${themeColors[themeKey]} flex-shrink-0`} />
                <div className="flex-1 min-w-0">
                  <div className="text-xs font-medium">{config.label}</div>
                  <div className="text-[10px] text-text-dim">{config.description}</div>
                </div>
                {isActive && (
                  <svg className="w-4 h-4 text-neon-cyan flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                  </svg>
                )}
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
