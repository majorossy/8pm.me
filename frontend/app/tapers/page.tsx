'use client';

import React, { useState, useEffect, useMemo } from 'react';
import Link from 'next/link';
import {
  MicrophoneIcon,
  HeartIcon,
  SearchIcon,
  ExternalLinkIcon,
} from '@/components/icons/FooterIcons';

interface Taper {
  name: string;
  count: number;
}

interface TapersResponse {
  tapers: Taper[];
  totalTapers: number;
  totalRecordings: number;
  cached: boolean;
  error?: string;
}

type SortField = 'name' | 'count';
type SortDirection = 'asc' | 'desc';

export default function TapersPage() {
  const [tapers, setTapers] = useState<Taper[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [sortField, setSortField] = useState<SortField>('count');
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc');
  const [totalRecordings, setTotalRecordings] = useState(0);

  // Fetch tapers on mount
  useEffect(() => {
    async function fetchTapers() {
      try {
        const response = await fetch('/api/tapers');
        const data: TapersResponse = await response.json();

        if (data.error) {
          setError(data.error);
        } else {
          setTapers(data.tapers);
          setTotalRecordings(data.totalRecordings);
        }
      } catch (err) {
        setError('Failed to load tapers');
        console.error('Failed to fetch tapers:', err);
      } finally {
        setLoading(false);
      }
    }

    fetchTapers();
  }, []);

  // Filter and sort tapers
  const filteredTapers = useMemo(() => {
    let result = tapers;

    // Apply search filter
    if (search.trim()) {
      const searchLower = search.toLowerCase();
      result = result.filter(t => t.name.toLowerCase().includes(searchLower));
    }

    // Apply sort
    result = [...result].sort((a, b) => {
      let comparison = 0;
      if (sortField === 'name') {
        comparison = a.name.localeCompare(b.name);
      } else {
        comparison = a.count - b.count;
      }
      return sortDirection === 'desc' ? -comparison : comparison;
    });

    return result;
  }, [tapers, search, sortField, sortDirection]);

  // Handle column header click
  const handleSort = (field: SortField) => {
    if (sortField === field) {
      // Toggle direction
      setSortDirection(prev => (prev === 'asc' ? 'desc' : 'asc'));
    } else {
      // New field, default to desc for count, asc for name
      setSortField(field);
      setSortDirection(field === 'count' ? 'desc' : 'asc');
    }
  };

  // Sort indicator
  const SortIndicator = ({ field }: { field: SortField }) => {
    if (sortField !== field) return null;
    return (
      <span className="ml-1">
        {sortDirection === 'asc' ? '▲' : '▼'}
      </span>
    );
  };

  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      {/* Header with icon */}
      <div className="flex items-center gap-4 mb-8">
        <div className="p-3 bg-[#2a2825] rounded-lg border border-[#3a3632]">
          <MicrophoneIcon className="w-8 h-8 text-[#d4a060]" />
        </div>
        <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] tracking-tight">
          Our Tapers
        </h1>
      </div>

      <div className="space-y-8 text-[#8a8478] leading-relaxed">
        {/* Intro card */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 md:p-8">
          <div className="flex items-start gap-4">
            <HeartIcon className="w-8 h-8 text-[#d4a060] flex-shrink-0 mt-1" />
            <div>
              <p className="text-lg mb-4">
                Thank you to the {loading ? '...' : tapers.length.toLocaleString()}+ tapers who
                recorded these shows and shared them with the world.
              </p>
              <p className="text-[#8a8478]">
                Without their dedication, countless concerts would be lost to time.
                These volunteer archivists capture the magic of live music, preserving
                {loading ? '' : ` ${totalRecordings.toLocaleString()}`} recordings for future generations.
              </p>
            </div>
          </div>
        </div>

        {/* Search */}
        <div className="relative">
          <SearchIcon className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#6a6458]" />
          <input
            type="text"
            placeholder="Search tapers..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="w-full pl-12 pr-4 py-3 bg-[#2a2825] border border-[#3a3632] rounded-lg text-[#e8e0d4] placeholder-[#6a6458] focus:outline-none focus:border-[#d4a060] transition-colors"
          />
        </div>

        {/* Table card */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg overflow-hidden">
          {loading ? (
            <div className="p-8 text-center">
              <div className="inline-block animate-spin w-8 h-8 border-2 border-[#d4a060] border-t-transparent rounded-full mb-4" />
              <p className="text-[#8a8478]">Loading tapers...</p>
            </div>
          ) : error ? (
            <div className="p-8 text-center text-red-400">
              {error}
            </div>
          ) : (
            <>
              {/* Results count */}
              <div className="px-4 py-3 border-b border-[#3a3632] text-sm text-[#6a6458]">
                {search ? (
                  <>Showing {filteredTapers.length.toLocaleString()} of {tapers.length.toLocaleString()} tapers</>
                ) : (
                  <>{tapers.length.toLocaleString()} tapers</>
                )}
              </div>

              {/* Table container for horizontal scroll on mobile */}
              <div className="overflow-x-auto">
                <table className="w-full min-w-[400px]">
                  <thead>
                    <tr className="border-b border-[#3a3632] bg-[#1c1a17]">
                      <th
                        onClick={() => handleSort('name')}
                        className="text-left px-4 py-3 text-sm font-semibold text-[#d4a060] uppercase tracking-wider cursor-pointer hover:bg-[#2a2825] transition-colors select-none"
                      >
                        Taper Name
                        <SortIndicator field="name" />
                      </th>
                      <th
                        onClick={() => handleSort('count')}
                        className="text-right px-4 py-3 text-sm font-semibold text-[#d4a060] uppercase tracking-wider cursor-pointer hover:bg-[#2a2825] transition-colors select-none"
                      >
                        Recordings
                        <SortIndicator field="count" />
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredTapers.length === 0 ? (
                      <tr>
                        <td colSpan={2} className="px-4 py-8 text-center text-[#6a6458]">
                          No tapers found matching "{search}"
                        </td>
                      </tr>
                    ) : (
                      filteredTapers.map((taper, index) => (
                        <tr
                          key={taper.name}
                          className={`border-b border-[#3a3632]/50 hover:bg-[#1c1a17]/50 transition-colors ${
                            index % 2 === 0 ? 'bg-[#2a2825]' : 'bg-[#252320]'
                          }`}
                        >
                          <td className="px-4 py-3">
                            <a
                              href={`https://archive.org/search?query=taper%3A%22${encodeURIComponent(taper.name)}%22+AND+collection%3Aetree`}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="text-[#e8e0d4] hover:text-[#d4a060] transition-colors inline-flex items-center gap-1.5 group"
                            >
                              {taper.name}
                              <ExternalLinkIcon className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity text-[#6a6458]" />
                            </a>
                          </td>
                          <td className="px-4 py-3 text-right text-[#8a8478] tabular-nums">
                            {taper.count.toLocaleString()}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </>
          )}
        </div>

        {/* Archive.org resources */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-4 flex items-center gap-2">
            <ExternalLinkIcon className="w-5 h-5" />
            Archive.org Taping Resources
          </h2>
          <ul className="space-y-3">
            <li>
              <a
                href="https://archive.org/about/terms.php"
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#8a8478] hover:text-[#d4a060] transition-colors inline-flex items-center gap-2"
              >
                Archive.org Terms of Use
                <ExternalLinkIcon className="w-4 h-4" />
              </a>
            </li>
            <li>
              <a
                href="https://archive.org/details/etree"
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#8a8478] hover:text-[#d4a060] transition-colors inline-flex items-center gap-2"
              >
                Live Music Archive (etree)
                <ExternalLinkIcon className="w-4 h-4" />
              </a>
            </li>
            <li>
              <a
                href="https://wiki.etree.org/index.php?page=TapersFaq"
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#8a8478] hover:text-[#d4a060] transition-colors inline-flex items-center gap-2"
              >
                Taper's FAQ
                <ExternalLinkIcon className="w-4 h-4" />
              </a>
            </li>
          </ul>
        </div>

        {/* Back link */}
        <div className="pt-4 text-center">
          <Link
            href="/"
            className="text-sm text-[#8a8478] hover:text-[#d4a060] transition-colors duration-200"
          >
            ← Back to Home
          </Link>
        </div>
      </div>
    </div>
  );
}
