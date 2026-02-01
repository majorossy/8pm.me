'use client';

import Image from 'next/image';
import Link from 'next/link';

export interface RelatedArtist {
  slug: string;
  name: string;
  image?: string;
  showCount?: number;
  genres?: string[];
}

interface RelatedArtistsProps {
  currentArtistName: string;
  relatedArtists: RelatedArtist[];
}

/**
 * RelatedArtists - SEO-optimized internal linking section
 *
 * Purpose: Improve crawl depth and user engagement by suggesting similar artists
 * SEO Benefits:
 * - Internal linking helps search engines discover related content
 * - Increases dwell time as users explore related artists
 * - Keyword-rich heading for "similar artists" queries
 */
export default function RelatedArtists({
  currentArtistName,
  relatedArtists,
}: RelatedArtistsProps) {
  if (!relatedArtists || relatedArtists.length === 0) {
    return null;
  }

  return (
    <section className="mt-12 pt-8 border-t border-[#3a3632]/30">
      {/* Keyword-rich heading for SEO */}
      <h2 className="text-xl md:text-2xl font-bold text-white mb-2">
        Similar Artists to {currentArtistName}
      </h2>
      <p className="text-sm text-[#8a8478] mb-6">
        If you enjoy {currentArtistName}&apos;s live recordings, explore these related jam bands and improvisational artists
      </p>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {relatedArtists.map((artist) => (
          <Link
            key={artist.slug}
            href={`/artists/${artist.slug}`}
            className="group block p-4 bg-[#2d2a26] rounded-lg hover:bg-[#3d3a36] transition-all duration-200 hover:scale-[1.02]"
          >
            {/* Artist image */}
            <div className="relative w-full aspect-square rounded-md overflow-hidden mb-3 bg-[#1c1a17]">
              {artist.image ? (
                <Image
                  src={artist.image}
                  alt={`${artist.name} live recordings`}
                  fill
                  sizes="(max-width: 768px) 50vw, 25vw"
                  className="object-cover group-hover:scale-105 transition-transform duration-300"
                />
              ) : (
                <div className="w-full h-full flex items-center justify-center">
                  <svg
                    className="w-12 h-12 text-[#4a4640]"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9h10v2H7v-2z" />
                  </svg>
                </div>
              )}
            </div>

            {/* Artist name */}
            <p className="font-semibold text-[#e8e0d4] group-hover:text-[#d4a060] transition-colors truncate">
              {artist.name}
            </p>

            {/* Show count */}
            {artist.showCount !== undefined && artist.showCount > 0 && (
              <p className="text-xs text-[#8a8478] mt-1">
                {artist.showCount.toLocaleString()} live recordings
              </p>
            )}

            {/* Genres */}
            {artist.genres && artist.genres.length > 0 && (
              <p className="text-xs text-[#6a6458] mt-1 truncate">
                {artist.genres.slice(0, 2).join(', ')}
              </p>
            )}
          </Link>
        ))}
      </div>

      {/* SEO text for keyword relevance */}
      <p className="text-xs text-[#6a6458] mt-6 text-center">
        Discover more jam bands and live concert recordings from Archive.org on EIGHTPM
      </p>
    </section>
  );
}
