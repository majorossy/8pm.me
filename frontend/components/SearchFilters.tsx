'use client';

import { useState, useCallback } from 'react';

export interface VersionFilters {
  year?: number;
  dateFrom?: string;
  dateTo?: string;
  venue?: string;
  isSoundboard?: boolean;
}

interface SearchFiltersProps {
  filters: VersionFilters;
  onFiltersChange: (filters: VersionFilters) => void;
  availableYears?: number[];
  className?: string;
}

export function SearchFilters({
  filters,
  onFiltersChange,
  availableYears = [],
  className = '',
}: SearchFiltersProps) {
  const [isExpanded, setIsExpanded] = useState(false);

  const handleYearChange = useCallback((year: number | undefined) => {
    onFiltersChange({ ...filters, year });
  }, [filters, onFiltersChange]);

  const handleVenueChange = useCallback((venue: string) => {
    onFiltersChange({ ...filters, venue: venue || undefined });
  }, [filters, onFiltersChange]);

  const handleSoundboardToggle = useCallback(() => {
    onFiltersChange({ ...filters, isSoundboard: !filters.isSoundboard });
  }, [filters, onFiltersChange]);

  const handleClearAll = useCallback(() => {
    onFiltersChange({});
  }, [onFiltersChange]);

  const hasActiveFilters = filters.year || filters.venue || filters.isSoundboard;

  // Generate year options (last 60 years)
  const currentYear = new Date().getFullYear();
  const yearOptions = availableYears.length > 0
    ? availableYears.sort((a, b) => b - a)
    : Array.from({ length: 60 }, (_, i) => currentYear - i);

  return (
    <div className={`search-filters ${className}`}>
      {/* Filter Toggle Button (Mobile) */}
      <div className="flex items-center gap-2 mb-2 md:hidden">
        <button
          onClick={() => setIsExpanded(!isExpanded)}
          className="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm
                     bg-[#2a2520] text-[#e8dcc8] hover:bg-[#3a352f] transition-colors"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          Filters
          {hasActiveFilters && (
            <span className="w-2 h-2 rounded-full bg-[#d4a060]" />
          )}
        </button>
        {hasActiveFilters && (
          <button
            onClick={handleClearAll}
            className="text-xs text-[#a09080] hover:text-[#d4a060] transition-colors"
          >
            Clear all
          </button>
        )}
      </div>

      {/* Filter Controls */}
      <div className={`flex flex-wrap items-center gap-2 ${!isExpanded ? 'hidden md:flex' : 'flex'}`}>
        {/* Year Select */}
        <select
          value={filters.year || ''}
          onChange={(e) => handleYearChange(e.target.value ? parseInt(e.target.value) : undefined)}
          className="px-3 py-1.5 rounded-full text-sm bg-[#2a2520] text-[#e8dcc8]
                     border border-[#3a352f] hover:border-[#d4a060] focus:border-[#d4a060]
                     focus:outline-none transition-colors cursor-pointer"
        >
          <option value="">All Years</option>
          {yearOptions.map(year => (
            <option key={year} value={year}>{year}</option>
          ))}
        </select>

        {/* Venue Input */}
        <input
          type="text"
          placeholder="Venue..."
          value={filters.venue || ''}
          onChange={(e) => handleVenueChange(e.target.value)}
          className="px-3 py-1.5 rounded-full text-sm bg-[#2a2520] text-[#e8dcc8]
                     border border-[#3a352f] hover:border-[#d4a060] focus:border-[#d4a060]
                     focus:outline-none transition-colors placeholder-[#6a6050] w-32 md:w-40"
        />

        {/* Soundboard Toggle */}
        <button
          onClick={handleSoundboardToggle}
          className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm transition-colors
                     ${filters.isSoundboard
                       ? 'bg-[#d4a060] text-[#1c1a17] font-medium'
                       : 'bg-[#2a2520] text-[#e8dcc8] border border-[#3a352f] hover:border-[#d4a060]'}`}
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
          SBD Only
        </button>

        {/* Clear Filters (Desktop) */}
        {hasActiveFilters && (
          <button
            onClick={handleClearAll}
            className="hidden md:flex items-center gap-1 px-3 py-1.5 rounded-full text-sm
                       text-[#a09080] hover:text-[#d4a060] transition-colors"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
            Clear
          </button>
        )}
      </div>
    </div>
  );
}

export default SearchFilters;
