'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { useRecentSearches } from '@/hooks/useRecentSearches';
import { useRouter } from 'next/navigation';
import { type Artist, type Album, type Song } from '@/lib/api';
import { useQueue } from '@/context/QueueContext';

interface JamifySearchOverlayProps {
  isOpen: boolean;
  onClose: () => void;
}

interface SearchResults {
  artists: Artist[];
  albums: Album[];
  tracks: Song[];
}

export function JamifySearchOverlay({ isOpen, onClose }: JamifySearchOverlayProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [debouncedQuery, setDebouncedQuery] = useState('');
  const [results, setResults] = useState<SearchResults>({ artists: [], albums: [], tracks: [] });
  const [isSearching, setIsSearching] = useState(false);
  const [isAnimating, setIsAnimating] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);
  const { recentSearches, addSearch, removeSearch, clearSearches } = useRecentSearches();
  const router = useRouter();
  const { addToUpNext } = useQueue();

  // Handle animation state
  useEffect(() => {
    if (isOpen) {
      setIsAnimating(true);
      setTimeout(() => {
        inputRef.current?.focus();
      }, 300); // Wait for slide animation
    } else {
      // Clear search when closing
      setSearchQuery('');
      setDebouncedQuery('');
      setResults({ artists: [], albums: [], tracks: [] });
    }
  }, [isOpen]);

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
        console.error('Search failed:', error);
        setResults({ artists: [], albums: [], tracks: [] });
      } finally {
        setIsSearching(false);
      }
    };

    performSearch();
  }, [debouncedQuery]);

  const handleSearch = useCallback((query: string) => {
    setSearchQuery(query);
    if (query.trim()) {
      addSearch(query.trim());
    }
  }, [addSearch]);

  const handleRecentSearchClick = (query: string) => {
    setSearchQuery(query);
    setDebouncedQuery(query);
  };

  const handleArtistClick = (artist: Artist) => {
    addSearch(artist.name);
    onClose();
    router.push(`/artists/${artist.slug}`);
  };

  const handleAlbumClick = (album: Album) => {
    addSearch(album.name);
    onClose();
    // Navigate to artist page (albums don't have dedicated pages yet)
    router.push(`/artists/${album.artistSlug}`);
  };

  const handleTrackClick = (track: Song) => {
    addSearch(track.title);
    addToUpNext(track);
    onClose();
  };

  const handleClearInput = () => {
    setSearchQuery('');
    setDebouncedQuery('');
    inputRef.current?.focus();
  };

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <>
      {/* Backdrop */}
      <div
        className={`fixed inset-0 bg-black/80 z-[9998] transition-opacity duration-300 ${
          isAnimating ? 'opacity-100' : 'opacity-0'
        }`}
        onClick={handleBackdropClick}
      />

      {/* Overlay */}
      <div
        className={`fixed inset-0 z-[9999] bg-[#1c1a17] overflow-hidden transition-transform duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] ${
          isAnimating ? 'translate-y-0' : 'translate-y-full'
        }`}
      >
        <div className="flex flex-col h-full safe-top">
          {/* Header */}
          <div className="flex items-center gap-3 p-4 border-b border-white/10">
            <button
              onClick={onClose}
              className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors btn-touch"
              aria-label="Close search"
            >
              <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <div className="flex-1 relative">
              <input
                ref={inputRef}
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="What do you want to listen to?"
                className="w-full bg-[#2d2a26] text-white placeholder-gray-400 rounded-full px-4 py-3 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#d4a060]"
              />
              {searchQuery && (
                <button
                  onClick={handleClearInput}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
                  aria-label="Clear search"
                >
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z" />
                  </svg>
                </button>
              )}
            </div>
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto">
            {!debouncedQuery ? (
              /* Recent Searches */
              recentSearches.length > 0 && (
                <div className="p-4">
                  <div className="flex items-center justify-between mb-4">
                    <h2 className="text-white font-bold text-lg">Recent searches</h2>
                    <button
                      onClick={clearSearches}
                      className="text-gray-400 hover:text-white text-sm transition-colors btn-touch"
                    >
                      Clear all
                    </button>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    {recentSearches.map((search, index) => (
                      <button
                        key={index}
                        onClick={() => handleRecentSearchClick(search)}
                        className="group flex items-center gap-2 bg-[#2d2a26] hover:bg-[#3a3632] text-white px-4 py-2 rounded-full text-sm transition-colors btn-touch"
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
              <div className="p-4 space-y-6">
                {results.artists.length === 0 && results.albums.length === 0 && results.tracks.length === 0 ? (
                  <div className="text-center py-12">
                    <p className="text-gray-400 text-sm">No results found for &quot;{debouncedQuery}&quot;</p>
                    <p className="text-gray-500 text-xs mt-2">Try searching for artists, albums, or tracks</p>
                  </div>
                ) : (
                  <>
                    {/* Artists */}
                    {results.artists.length > 0 && (
                      <div>
                        <h3 className="text-white font-bold text-lg mb-3 flex items-center gap-2">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                          </svg>
                          Artists
                        </h3>
                        <div className="space-y-2">
                          {results.artists.map((artist) => (
                            <button
                              key={artist.id}
                              onClick={() => handleArtistClick(artist)}
                              className="w-full flex items-center gap-3 p-3 bg-[#2d2a26] hover:bg-[#3a3632] rounded-lg cursor-pointer transition-colors btn-touch text-left"
                            >
                              <div className="w-12 h-12 bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0">
                                {artist.image ? (
                                  <img src={artist.image} alt={artist.name} className="w-full h-full rounded-full object-cover" />
                                ) : (
                                  <svg className="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                  </svg>
                                )}
                              </div>
                              <div className="flex-1 min-w-0">
                                <p className="text-white font-medium truncate">{artist.name}</p>
                                <p className="text-gray-400 text-sm">Artist • {artist.songCount} songs</p>
                              </div>
                            </button>
                          ))}
                        </div>
                      </div>
                    )}

                    {/* Albums */}
                    {results.albums.length > 0 && (
                      <div>
                        <h3 className="text-white font-bold text-lg mb-3 flex items-center gap-2">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 14.5c-2.49 0-4.5-2.01-4.5-4.5S9.51 7.5 12 7.5s4.5 2.01 4.5 4.5-2.01 4.5-4.5 4.5zm0-5.5c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z" />
                          </svg>
                          Albums
                        </h3>
                        <div className="space-y-2">
                          {results.albums.map((album) => (
                            <button
                              key={album.id}
                              onClick={() => handleAlbumClick(album)}
                              className="w-full flex items-center gap-3 p-3 bg-[#2d2a26] hover:bg-[#3a3632] rounded-lg cursor-pointer transition-colors btn-touch text-left"
                            >
                              <div className="w-12 h-12 bg-gray-700 rounded flex items-center justify-center flex-shrink-0">
                                {album.coverArt ? (
                                  <img src={album.coverArt} alt={album.name} className="w-full h-full rounded object-cover" />
                                ) : (
                                  <svg className="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 14.5c-2.49 0-4.5-2.01-4.5-4.5S9.51 7.5 12 7.5s4.5 2.01 4.5 4.5-2.01 4.5-4.5 4.5zm0-5.5c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z" />
                                  </svg>
                                )}
                              </div>
                              <div className="flex-1 min-w-0">
                                <p className="text-white font-medium truncate">{album.name}</p>
                                <p className="text-gray-400 text-sm truncate">{album.artistName} • {album.totalTracks} tracks</p>
                              </div>
                            </button>
                          ))}
                        </div>
                      </div>
                    )}

                    {/* Tracks */}
                    {results.tracks.length > 0 && (
                      <div>
                        <h3 className="text-white font-bold text-lg mb-3 flex items-center gap-2">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                          </svg>
                          Tracks
                        </h3>
                        <div className="space-y-2">
                          {results.tracks.map((track) => (
                            <button
                              key={track.id}
                              onClick={() => handleTrackClick(track)}
                              className="w-full flex items-center gap-3 p-3 bg-[#2d2a26] hover:bg-[#3a3632] rounded-lg cursor-pointer transition-colors btn-touch text-left"
                            >
                              <div className="w-12 h-12 bg-gray-700 rounded flex items-center justify-center flex-shrink-0">
                                <svg className="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
                                </svg>
                              </div>
                              <div className="flex-1 min-w-0">
                                <p className="text-white font-medium truncate">{track.title}</p>
                                <p className="text-gray-400 text-sm truncate">{track.artistName}</p>
                              </div>
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
