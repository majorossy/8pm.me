import { getArtists, getArtist } from '@/lib/api';
import AlbumCarousel from '@/components/AlbumCarousel';
import Link from 'next/link';

export default async function ArtistsPage() {
  const artists = await getArtists();

  // Fetch details for each artist to get their albums
  const artistsWithAlbums = await Promise.all(
    artists.map(async (artist) => {
      const details = await getArtist(artist.slug);
      return {
        ...artist,
        albums: details?.albums || [],
      };
    })
  );

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold text-white mb-8">All Artists</h1>

      <div className="space-y-10">
        {artistsWithAlbums.map((artist) => (
          <section key={artist.id} className="relative">
            {/* Artist header */}
            <div className="flex items-center gap-4 mb-4">
              <Link href={`/artists/${artist.slug}`} className="flex items-center gap-4 group">
                {/* Artist avatar */}
                <div className="w-16 h-16 bg-dark-700 rounded-full overflow-hidden flex-shrink-0">
                  {artist.image && !artist.image.includes('default') ? (
                    <img
                      src={artist.image}
                      alt={artist.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center">
                      <span className="text-2xl font-bold text-white/50">
                        {artist.name.charAt(0)}
                      </span>
                    </div>
                  )}
                </div>

                {/* Artist name and stats */}
                <div>
                  <h2 className="text-xl font-bold text-white group-hover:text-primary transition-colors">
                    {artist.name}
                  </h2>
                  <p className="text-sm text-gray-400">
                    {artist.albums.length} {artist.albums.length === 1 ? 'album' : 'albums'}
                    {artist.songCount ? ` · ${artist.songCount} recordings` : ''}
                  </p>
                </div>
              </Link>

              {/* View all link */}
              <Link
                href={`/artists/${artist.slug}`}
                className="ml-auto text-sm text-primary hover:text-primary-light transition-colors"
              >
                View all →
              </Link>
            </div>

            {/* Albums carousel */}
            <AlbumCarousel albums={artist.albums} artistSlug={artist.slug} />
          </section>
        ))}
      </div>
    </div>
  );
}
