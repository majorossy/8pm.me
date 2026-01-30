'use client';

import { useState, useEffect, useRef } from 'react';
import { useRecentSearches } from '@/hooks/useRecentSearches';
import { useRouter } from 'next/navigation';
import { type Artist, type AlbumCategory } from '@/lib/api';
import { SearchTrackResult } from '@/components/SearchTrackResult';

interface TrackCategory {
  uid: string;
  name: string;
  url_key: string;
  product_count: number;
}

interface SearchResults {
  artists: Artist[];
  albums: AlbumCategory[];
  tracks: TrackCategory[];
}

export default function SearchPage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [results, setResults] = useState<SearchResults>({ artists: [], albums: [], tracks: [] });
  const [isSearching, setIsSearching] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);
  const { recentSearches, addSearch, removeSearch, clearSearches } = useRecentSearches();
  const router = useRouter();

  // Auto-focus input on mount
  useEffect(() => {
    inputRef.current?.focus();
  }, []);

  // Debounce search query
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedQuery(searchQuery);
    }, 300);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Perform search when debounced query changes
  useEffect(() => {
    if (!debouncedQuery.trim()) {
      setResults({ artists: [], albums: [], tracks: [] });
      setIsSearching(false);
      return;
    }

    const performSearch = async () => {
      setIsSearching(true);
      try {
        // Call our API route instead of direct Magento (avoids CORS)
        const response = await fetch(`/api/search?q=${encodeURIComponent(debouncedQuery)}`);
        if (!response.ok) {
          throw new Error(`Search API returned ${response.status}`);
        }
        const searchResults = await response.json();
        setResults(searchResults);
      } catch (error) {
        setResults({ artists: [], albums: [], tracks: [] });
      } finally {
        setIsSearching(false);
      }
    };

    performSearch();
  }, [debouncedQuery]);

  const handleRecentSearchClick = (query: string) => {
    setSearchQuery(query);
    setDebouncedQuery(query);
  };

  const handleArtistClick = (artist: Artist) => {
    addSearch(artist.name);
    router.push(`/artists/${artist.slug}`);
  };

  const handleAlbumClick = (album: AlbumCategory) => {
    addSearch(album.name);
    // Extract artist slug from breadcrumbs (first breadcrumb is the artist)
    const artistSlug = album.breadcrumbs?.[0]?.category_url_key || '';
    if (artistSlug) {
      router.push(`/artists/${artistSlug}/album/${album.url_key}`);
    }
  };


  return (
    <div className="min-h-screen bg-[#1c1a17] pb-[140px] md:pb-[90px] safe-top">
      <div className="max-w-[1000px] mx-auto">
        {/* Header */}
        <div className="p-6 md:p-8 border-b border-white/10">
        <h1 className="text-3xl md:text-4xl font-bold text-white mb-4">Search</h1>

        {/* Search input */}
        <div className="relative max-w-2xl">
          <input
            ref={inputRef}
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="What do you want to listen to?"
            className="w-full bg-[#2d2a26] text-white placeholder-gray-400 rounded-full px-6 py-4 pr-12 text-base focus:outline-none focus:ring-2 focus:ring-[#d4a060]"
          />
          {searchQuery && (
            <button
              onClick={() => {
                setSearchQuery('');
                setDebouncedQuery('');
                inputRef.current?.focus();
              }}
              className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
              aria-label="Clear search"
            >
              <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z" />
              </svg>
            </button>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="px-6 md:px-8 py-6">
        {!debouncedQuery ? (
          /* Recent Searches */
          recentSearches.length > 0 && (
            <div>
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-white font-bold text-xl">Recent searches</h2>
                <button
                  onClick={clearSearches}
                  className="text-gray-400 hover:text-white text-sm transition-colors"
                >
                  Clear all
                </button>
              </div>
              <div className="flex flex-wrap gap-2">
                {recentSearches.map((search, index) => (
                  <button
                    key={index}
                    onClick={() => handleRecentSearchClick(search)}
                    className="group flex items-center gap-2 bg-[#2d2a26] hover:bg-[#3a3632] text-white px-4 py-2 rounded-full text-sm transition-colors"
                  >
                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{search}</span>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        removeSearch(search);
                      }}
                      className="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-white transition-opacity"
                      aria-label={`Remove ${search}`}
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </button>
                ))}
              </div>
            </div>
          )
        ) : isSearching ? (
          /* Loading State */
          <div className="flex items-center justify-center py-12">
            <div className="spinner" />
          </div>
        ) : (
          /* Search Results */
          <div className="space-y-8">
            {results.artists.length === 0 && results.albums.length === 0 && results.tracks.length === 0 ? (
              <div className="text-center py-12">
                <p className="text-gray-400 text-base">No results found for &quot;{debouncedQuery}&quot;</p>
                <p className="text-gray-500 text-sm mt-2">Try searching for artists, albums, or tracks</p>
              </div>
            ) : (
              <>
                {/* Artists */}
                {results.artists.length > 0 && (
                  <div>
                    <h3 className="text-white font-bold text-xl mb-4">Artists</h3>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                      {results.artists.map((artist) => (
                        <button
                          key={artist.id}
                          onClick={() => handleArtistClick(artist)}
                          className="flex flex-col items-center p-4 bg-[#252220] hover:bg-[#2d2a26] rounded-lg transition-colors text-left"
                        >
                          <div className="w-32 h-32 bg-gray-700 rounded-full flex items-center justify-center mb-3 overflow-hidden">
                            {artist.image ? (
                              <img src={artist.image} alt={artist.name} className="w-full h-full object-cover" />
                            ) : (
                              <svg className="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                              </svg>
                            )}
                          </div>
                          <p className="text-white font-medium text-center w-full truncate">{artist.name}</p>
                          <p className="text-gray-400 text-sm">Artist</p>
                        </button>
                      ))}
                    </div>
                  </div>
                )}

                {/* Albums */}
                {results.albums.length > 0 && (
                  <div>
                    <h3 className="text-white font-bold text-xl mb-4">Albums</h3>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                      {results.albums.map((album) => {
                        const artistName = album.breadcrumbs?.[0]?.category_name || 'Unknown Artist';
                        return (
                          <button
                            key={album.uid}
                            onClick={() => handleAlbumClick(album)}
                            className="flex flex-col p-4 bg-[#252220] hover:bg-[#2d2a26] rounded-lg transition-colors text-left"
                          >
                            <div className="w-full aspect-square bg-gray-700 rounded mb-3 overflow-hidden">
                              {album.wikipedia_artwork_url ? (
                                <img src={album.wikipedia_artwork_url} alt={album.name} className="w-full h-full object-cover" />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#3a3632] to-[#252220]">
                                  <svg className="w-12 h-12 text-[#d4a060]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 14.5c-2.49 0-4.5-2.01-4.5-4.5S9.51 7.5 12 7.5s4.5 2.01 4.5 4.5-2.01 4.5-4.5 4.5zm0-5.5c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z" />
                                  </svg>
                                </div>
                              )}
                            </div>
                            <p className="text-white font-medium truncate" title={album.name}>{album.name}</p>
                            <p className="text-gray-400 text-sm truncate">
                              {artistName} • {album.product_count || 0} tracks
                            </p>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                )}

                {/* Tracks Section */}
                {results.tracks.length > 0 && (
                  <section className="mb-8">
                    <h2 className="text-xl font-semibold text-[#e8dcc8] mb-4">Tracks</h2>
                    <div className="space-y-2">
                      {results.tracks.map((track) => (
                        <SearchTrackResult
                          key={track.uid}
                          track={track}
                        />
                      ))}
                    </div>
                  </section>
                )}
              </>
            )}
          </div>
        )}
      </div>
      </div>
    </div>
  );
}

