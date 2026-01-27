'use client';

import { useEffect } from 'react';
import { ArtistDetail } from '@/lib/api';
import { BandMemberData } from '@/lib/types';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';
import { useWishlist } from '@/context/WishlistContext';
import { useHaptic } from '@/hooks/useHaptic';
import AlbumCard from '@/components/AlbumCard';
import BandMembers from '@/components/artist/BandMembers';
import BandStatistics from '@/components/artist/BandStatistics';
import BandLinks from '@/components/artist/BandLinks';

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

  return (
    <div className="min-h-screen">
      {/* Artist header - name left, main image right */}
      <section className="px-4 md:px-8 pt-8 md:pt-16 pb-6 md:pb-8">
        <div className="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 lg:gap-12">
          {/* Left: Artist name and stats */}
          <div className="text-center lg:text-left">
            <p className="text-[0.65rem] md:text-xs font-bold text-white uppercase tracking-wider mb-2">Artist</p>
            <h1 className="text-3xl md:text-5xl lg:text-7xl xl:text-8xl font-black text-white mb-2 md:mb-4">
              {artist.name}
            </h1>
            <p className="text-sm md:text-base text-[#a7a7a7]">
              {artist.albums.length} {artist.albums.length === 1 ? 'album' : 'albums'} &bull; {artist.songCount} {artist.songCount === 1 ? 'recording' : 'recordings'}
            </p>

            {/* Action bar */}
            <div className="flex items-center justify-center lg:justify-start gap-4 md:gap-6 mt-6">
              <button className="w-12 h-12 md:w-14 md:h-14 bg-[#1DB954] rounded-full flex items-center justify-center hover:scale-105 hover:bg-[#1ed760] transition-all shadow-lg">
                <svg className="w-5 h-5 md:w-6 md:h-6 text-black ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </button>
              <button
                onClick={handleFollowToggle}
                className="p-2 text-[#a7a7a7] hover:text-white transition-colors"
                aria-label={isFollowed ? 'Unfollow artist' : 'Follow artist'}
              >
                <svg className="w-7 h-7 md:w-8 md:h-8" fill={isFollowed ? '#1DB954' : 'none'} stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </button>
            </div>
          </div>

          {/* Right: Main artist image */}
          <div className="mx-auto lg:mx-0">
            <div className="w-48 h-48 md:w-64 md:h-64 lg:w-[280px] lg:h-[280px] rounded-full overflow-hidden shadow-2xl">
              {artist.image && !artist.image.includes('default') ? (
                <img
                  src={artist.image}
                  alt={artist.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <div className="w-full h-full bg-[#282828] flex items-center justify-center">
                  <span className="font-bold text-5xl md:text-8xl text-[#535353]">
                    {artist.name.charAt(0)}
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      </section>

      {/* Discography - Full width */}
      <section className="px-4 md:px-8 pb-8">
        <h2 className="text-xl md:text-2xl font-bold text-white mb-4">Discography</h2>
        {artist.albums.length > 0 ? (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 md:gap-6">
            {artist.albums.map((album) => (
              <AlbumCard key={album.id} album={album} />
            ))}
          </div>
        ) : (
          <p className="text-[#a7a7a7]">No albums available.</p>
        )}
      </section>

      {/* Two column: content left, images right */}
      <section className="px-4 md:px-8 pb-8">
        <div className="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6 lg:gap-12">
          {/* Left Column: bio + members + stats + links */}
          <div className="space-y-8 md:space-y-12">

            {/* Biography Text Only */}
            <div>
              <h2 className="text-xl md:text-2xl font-bold text-white mb-6">Biography</h2>
              <div className="space-y-4">
                {artist.wikipediaSummary?.extract && (
                  <div className="text-[#b3b3b3] text-sm md:text-base leading-relaxed">
                    <p>{artist.wikipediaSummary.extract}</p>
                    {artist.wikipediaSummary.url && (
                      <p className="mt-2 text-xs">
                        <a
                          href={artist.wikipediaSummary.url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-[#1DB954] hover:underline"
                        >
                          Read more on Wikipedia →
                        </a>
                      </p>
                    )}
                  </div>
                )}
                {artist.extendedBio && artist.extendedBio.split('\n\n').map((paragraph, index) => (
                  <p key={index} className="text-[#b3b3b3] text-sm md:text-base leading-relaxed">
                    {paragraph}
                  </p>
                ))}
              </div>
            </div>

            {/* Band Members */}
            <BandMembers
              members={bandData?.members?.current}
              formerMembers={bandData?.members?.former}
            />

            {/* Band Statistics */}
            <BandStatistics
              statistics={{
                totalShows: bandData?.recordingStats?.totalShows,
                recordingStats: bandData?.recordingStats ? {
                  total: bandData.recordingStats.totalShows || 0
                } : undefined
              }}
            />

            {/* Band Links */}
            <BandLinks
              links={{
                website: bandData?.socialLinks?.website,
                youtube: bandData?.socialLinks?.youtube,
                facebook: bandData?.socialLinks?.facebook,
                instagram: bandData?.socialLinks?.instagram,
                twitter: bandData?.socialLinks?.twitter
              }}
              artistName={artist.name}
            />
          </div>

          {/* Right Column: Artist web images only (sticky) */}
          {allImages.length > 0 && (
            <div className="mx-auto lg:mx-0 lg:sticky lg:top-24 lg:self-start space-y-4">
              {allImages.slice(0, 3).map((image, index) => (
                <div key={index} className="bg-[#181818] rounded-lg overflow-hidden">
                  <div className="relative aspect-square">
                    <img
                      src={image.url}
                      alt={image.caption || 'Band photo'}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  {image.caption && (
                    <div className="p-3">
                      <p className="text-xs text-[#b3b3b3]">{image.caption}</p>
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
