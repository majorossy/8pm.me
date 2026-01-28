'use client';

import { useEffect, useMemo } from 'react';
import { ArtistDetail } from '@/lib/api';
import { BandMemberData } from '@/lib/types';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import { useWishlist } from '@/context/WishlistContext';
import { useHaptic } from '@/hooks/useHaptic';
import AlbumCard from '@/components/AlbumCard';
import BandMembersTimeline from '@/components/artist/BandMembersTimeline';
import BandStatistics from '@/components/artist/BandStatistics';
import BandLinks from '@/components/artist/BandLinks';
import DetailedCassette from '@/components/artist/DetailedCassette';
import PolaroidCard from '@/components/artist/PolaroidCard';

interface ArtistWithAlbums extends ArtistDetail {
  albums: any[];
}

interface ArtistPageContentProps {
  artist: ArtistWithAlbums;
  bandData?: BandMemberData | null;
}

export default function ArtistPageContent({ artist, bandData }: ArtistPageContentProps) {
  const { setBreadcrumbs } = useBreadcrumbs();
  const { followArtist, unfollowArtist, isArtistFollowed } = useWishlist();
  const { vibrate, BUTTON_PRESS } = useHaptic();

  const isFollowed = isArtistFollowed(artist.slug);

  useEffect(() => {
    setBreadcrumbs([{ label: artist.name, type: 'artist' }]);
    return () => setBreadcrumbs([]);
  }, [setBreadcrumbs, artist.name]);

  const handleFollowToggle = () => {
    vibrate(BUTTON_PRESS);
    if (isFollowed) {
      unfollowArtist(artist.slug);
    } else {
      followArtist(artist.slug);
    }
  };

  // Calculate real statistics from album data
  const calculatedStats = useMemo(() => {
    if (!artist.albums || artist.albums.length === 0) return null;

    // Total recordings and runtime
    let totalRecordings = 0;
    let totalDuration = 0;
    const venueCounts: Record<string, number> = {};
    const trackCounts: Record<string, number> = {};
    const years: number[] = [];
    const uniqueShows = new Set<string>(); // Track unique show identifiers
    const uniqueVenues = new Set<string>(); // Track unique venues

    artist.albums.forEach((album: any) => {
      // Count recordings and duration
      if (album.tracks) {
        album.tracks.forEach((track: any) => {
          // Track song title frequency
          const title = track.title;
          if (title) {
            trackCounts[title] = (trackCounts[title] || 0) + (track.songCount || 1);
          }

          // Count unique shows and sum durations from individual song versions
          if (track.songs && Array.isArray(track.songs)) {
            track.songs.forEach((song: any) => {
              // Count recordings
              totalRecordings++;

              // Sum duration from each song version
              totalDuration += song.duration || 0;

              // Track unique shows by extracting date from identifier
              // Identifier format: "db2021-10-15.dpa4006.flac16" or "bisco2009-02-06.flac16mk4v"
              // We want to extract just the date portion to count unique shows
              if (song.albumIdentifier) {
                // Extract date from identifier (remove recording source/format)
                const match = song.albumIdentifier.match(/(\d{4}-\d{2}-\d{2})/);
                const showDate = match ? match[1] : song.albumIdentifier;
                uniqueShows.add(showDate);
              }

              // Track unique venues
              if (song.showVenue) {
                uniqueVenues.add(song.showVenue);
                // Also count for top venues
                venueCounts[song.showVenue] = (venueCounts[song.showVenue] || 0) + 1;
              }
            });
          }
        });
      }

      // Collect years
      if (album.showDate) {
        const year = parseInt(album.showDate.split('-')[0], 10);
        if (!isNaN(year)) years.push(year);
      }
    });

    // Total shows = count of unique show identifiers from all songs
    const totalShows = uniqueShows.size;

    // Total venues = count of unique venues from all songs
    const totalVenues = uniqueVenues.size;

    console.log('[calculatedStats] Stats:', {
      totalRecordings,
      totalShows: uniqueShows.size,
      totalVenues: uniqueVenues.size,
      totalHours: Math.floor(totalDuration / 3600),
      albumCategories: artist.albums.length
    });

    // Get top venues
    const topVenues = Object.entries(venueCounts)
      .sort(([, a], [, b]) => b - a)
      .slice(0, 3)
      .map(([name, showCount]) => ({ name, showCount }));

    // Get most played track
    const mostPlayedEntry = Object.entries(trackCounts)
      .sort(([, a], [, b]) => b - a)[0];
    const mostPlayedTrack = mostPlayedEntry
      ? { title: mostPlayedEntry[0], playCount: mostPlayedEntry[1] }
      : undefined;

    // Get year range
    const yearsActive = years.length > 0
      ? { first: Math.min(...years), last: Math.max(...years) }
      : undefined;

    // Format total runtime
    const totalHours = Math.floor(totalDuration / 3600);

    return {
      totalShows,
      totalRecordings,
      totalHours,
      totalVenues,
      yearsActive,
      topVenues: topVenues.length > 0 ? topVenues : undefined,
      mostPlayedTrack,
    };
  }, [artist.albums]);

  // Combine all bio images (artist web images)
  const allImages = [];
  if (artist.wikipediaSummary?.thumbnail) {
    allImages.push({
      url: artist.wikipediaSummary.thumbnail.source,
      caption: `${artist.wikipediaSummary.title} (Wikipedia)`,
      credit: 'Wikipedia',
    });
  }
  if (bandData?.images) {
    allImages.push(...bandData.images);
  }

  // Extract year from bandData or use a default
  const formationYear = bandData?.recordingStats?.yearsActive?.split('-')[0] || '';

  // Get a short excerpt for the quote callout
  const quoteExcerpt = artist.wikipediaSummary?.extract
    ? artist.wikipediaSummary.extract.slice(0, 200) + (artist.wikipediaSummary.extract.length > 200 ? '...' : '')
    : null;

  return (
    <div className="min-h-screen">
      {/* Hero Section with Cassette Tape Design */}
      <section className="relative px-4 md:px-8 pt-4 md:pt-6 pb-8 md:pb-12 overflow-hidden">
        {/* Ambient background layers */}
        <div className="absolute inset-0 pointer-events-none">
          {/* Organic blob gradients */}
          <div
            className="absolute top-10 left-[5%] w-[400px] h-[350px] rounded-full opacity-[0.08]"
            style={{
              background: 'radial-gradient(ellipse, rgba(90,138,122,0.6) 0%, transparent 70%)',
              filter: 'blur(40px)',
            }}
          />
          <div
            className="absolute top-20 right-[10%] w-[300px] h-[400px] rounded-full opacity-[0.06]"
            style={{
              background: 'radial-gradient(ellipse, rgba(212,160,96,0.6) 0%, transparent 70%)',
              filter: 'blur(50px)',
            }}
          />
          <div
            className="absolute bottom-0 left-[30%] w-[500px] h-[300px] rounded-full opacity-[0.05]"
            style={{
              background: 'radial-gradient(ellipse, rgba(168,90,56,0.5) 0%, transparent 70%)',
              filter: 'blur(60px)',
            }}
          />

          {/* Fireflies */}
          <div
            className="firefly"
            style={{ top: '15%', left: '20%', animationDelay: '0s' }}
          />
          <div
            className="firefly firefly-2"
            style={{ top: '40%', right: '25%', animationDelay: '1.2s' }}
          />
          <div
            className="firefly firefly-3"
            style={{ bottom: '30%', left: '60%', animationDelay: '2.5s' }}
          />
          <div
            className="firefly"
            style={{ top: '60%', left: '10%', animationDelay: '0.8s' }}
          />
          <div
            className="firefly firefly-2"
            style={{ bottom: '20%', right: '15%', animationDelay: '1.8s' }}
          />
        </div>

        {/* Main hero content - cassette left, info right on desktop */}
        <div className="relative z-10 flex flex-col lg:flex-row items-center gap-8 lg:gap-16 max-w-[1000px] mx-auto">
          {/* Cassette stack */}
          <div className="relative w-[320px] h-[280px] flex-shrink-0 cassette-stack-float">
            {/* Stack of 4 cassettes with slight offsets */}
            <DetailedCassette
              artistName={artist.name}
              artistFullName={artist.wikipediaSummary?.title}
              year={formationYear}
              rotation={-8}
              offsetX={-15}
              offsetY={60}
              isTop={false}
            />
            <DetailedCassette
              artistName={artist.name}
              artistFullName={artist.wikipediaSummary?.title}
              year={formationYear}
              rotation={5}
              offsetX={25}
              offsetY={40}
              isTop={false}
            />
            <DetailedCassette
              artistName={artist.name}
              artistFullName={artist.wikipediaSummary?.title}
              year={formationYear}
              rotation={-3}
              offsetX={5}
              offsetY={20}
              isTop={false}
            />
            <DetailedCassette
              artistName={artist.name}
              artistFullName={artist.wikipediaSummary?.title}
              year={formationYear}
              rotation={2}
              offsetX={15}
              offsetY={0}
              isTop={true}
            />

            {/* Fire glow beneath cassettes */}
            <div
              className="absolute -bottom-4 left-1/2 -translate-x-1/2 w-[250px] h-[60px] opacity-40"
              style={{
                background: 'radial-gradient(ellipse at 50% 100%, rgba(212,120,50,0.5) 0%, rgba(180,100,40,0.2) 40%, transparent 70%)',
                filter: 'blur(12px)',
                animation: 'flicker 3s ease-in-out infinite',
              }}
            />
          </div>

          {/* Artist info */}
          <div className="flex-1 text-center lg:text-left">
            {/* Artist badge */}
            <span className="inline-block px-3 py-1 text-[10px] font-bold tracking-[0.2em] uppercase text-[#d4a060] bg-[#d4a060]/10 rounded-full mb-4">
              Artist
            </span>

            {/* Artist name - large Georgia serif */}
            <h1
              className="text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-2"
              style={{ fontFamily: 'Georgia, serif' }}
            >
              {artist.name}
            </h1>

            {/* Full name subtitle if different */}
            {artist.wikipediaSummary?.title && artist.wikipediaSummary.title !== artist.name && (
              <p className="text-base md:text-lg text-[#8a8478] mb-4">
                {artist.wikipediaSummary.title}
              </p>
            )}

            {/* Stats line */}
            <p className="text-sm md:text-base text-[#6a6458] mb-6">
              {artist.albums.length} {artist.albums.length === 1 ? 'album' : 'albums'} &bull; {artist.songCount} {artist.songCount === 1 ? 'recording' : 'recordings'}
              {artist.originLocation && (
                <> &bull; {artist.originLocation}</>
              )}
            </p>

            {/* Action buttons */}
            <div className="flex items-center justify-center lg:justify-start gap-3 mb-8">
              {/* Follow/Heart button */}
              <button
                onClick={handleFollowToggle}
                className="p-3 border border-[#3a3632] hover:border-[#d4a060] rounded-full transition-all hover:scale-105"
                aria-label={isFollowed ? 'Unfollow artist' : 'Follow artist'}
              >
                <svg
                  className="w-5 h-5"
                  fill={isFollowed ? '#d4a060' : 'none'}
                  stroke={isFollowed ? '#d4a060' : '#e8e0d4'}
                  viewBox="0 0 24 24"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </button>
            </div>

            {/* Quote callout box */}
            {quoteExcerpt && (
              <div className="relative pl-4 border-l-2 border-[#d4a060]/40 max-w-3xl lg:-ml-32">
                <p className="text-sm md:text-base text-[#a8a098] italic leading-relaxed">
                  &ldquo;{quoteExcerpt}&rdquo;
                </p>
                <p className="text-xs text-[#6a6458] mt-2">
                  &mdash; Wikipedia
                </p>
              </div>
            )}
          </div>

          {/* Polaroid Card - Right side */}
          <div className="hidden lg:flex flex-shrink-0 items-start pt-8">
            <PolaroidCard
              imageUrl={artist.image}
              artistName={artist.name}
              caption={formationYear ? `Est. ${formationYear}` : artist.name}
              socialLinks={{
                website: bandData?.socialLinks?.website,
                youtube: bandData?.socialLinks?.youtube,
                facebook: bandData?.socialLinks?.facebook,
                instagram: bandData?.socialLinks?.instagram,
                twitter: bandData?.socialLinks?.twitter,
              }}
            />
          </div>
        </div>
      </section>

      {/* Discography - Carousel */}
      <section className="pb-8 max-w-[1000px] mx-auto px-4 md:px-8">
        <h2 className="text-xl md:text-2xl font-bold text-white mb-4 text-center">Discography</h2>
        {artist.albums.length > 0 ? (
          <div className="flex justify-center gap-4 md:gap-6 overflow-x-auto pb-4 scrollbar-thin scrollbar-thumb-[#3a3632] scrollbar-track-transparent">
            {artist.albums.map((album) => (
              <div key={album.id} className="flex-shrink-0 w-[160px] sm:w-[180px] md:w-[200px]">
                <AlbumCard album={album} />
              </div>
            ))}
          </div>
        ) : (
          <p className="text-[#8a8478] text-center">No albums available.</p>
        )}
      </section>

      {/* Two column: content left, images right */}
      <section className="max-w-[1000px] mx-auto px-4 md:px-8 pb-8">
        <div className="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 lg:gap-12">
          {/* Left Column: bio + members + stats */}
          <div className="space-y-8 md:space-y-12">

            {/* Biography Text Only */}
            <div>
              <h2 className="text-xl md:text-2xl font-bold text-white mb-6">Biography</h2>
              <div className="space-y-4">
                {artist.wikipediaSummary?.extract && (
                  <div className="text-[#8a8478] text-sm md:text-base leading-relaxed">
                    <p>{artist.wikipediaSummary.extract}</p>
                    {artist.wikipediaSummary.url && (
                      <p className="mt-2 text-xs">
                        <a
                          href={artist.wikipediaSummary.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-[#d4a060] hover:underline"
                        >
                          Read more on Wikipedia →
                        </a>
                      </p>
                    )}
                  </div>
                )}
                {artist.extendedBio && artist.extendedBio.split('\n\n').map((paragraph, index) => (
                  <p key={index} className="text-[#8a8478] text-sm md:text-base leading-relaxed">
                    {paragraph}
                  </p>
                ))}
              </div>
            </div>

            {/* Band Statistics - from real API data */}
            {calculatedStats && (
              <BandStatistics
                statistics={{
                  totalShows: calculatedStats.totalShows,
                  totalHours: calculatedStats.totalHours,
                  totalVenues: calculatedStats.totalVenues,
                  recordingStats: { total: calculatedStats.totalRecordings },
                  yearsActive: formationYear ? { first: parseInt(formationYear, 10), last: new Date().getFullYear() } : calculatedStats.yearsActive,
                  topVenues: calculatedStats.topVenues,
                  mostPlayedTrack: calculatedStats.mostPlayedTrack,
                }}
              />
            )}

            {/* Band Members */}
            <BandMembersTimeline
              members={bandData?.members?.current}
              formerMembers={bandData?.members?.former}
              foundedYear={formationYear ? parseInt(formationYear, 10) : undefined}
            />
          </div>

          {/* Right Column: Artist web images only (sticky) */}
          {allImages.length > 0 && (
            <div className="mx-auto lg:mx-0 lg:sticky lg:top-24 lg:self-start space-y-4">
              {allImages.slice(0, 3).map((image, index) => (
                <div key={index} className="bg-[#252220] rounded-lg overflow-hidden">
                  <div className="relative aspect-square">
                    <img
                      src={image.url}
                      alt={image.caption || 'Band photo'}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  {image.caption && (
                    <div className="p-3">
                      <p className="text-xs text-[#8a8478]">{image.caption}</p>
                      {image.credit && (
                        <p className="text-[0.6rem] text-[#6a6a6a] mt-1">
                          Credit: {image.credit}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
}
