import { getArtist, getArtists } from '@/lib/api';
import AlbumCard from '@/components/AlbumCard';
import Link from 'next/link';
import { notFound } from 'next/navigation';

interface ArtistPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateStaticParams() {
  const artists = await getArtists();
  return artists.map((artist) => ({
    slug: artist.slug,
  }));
}

export default async function ArtistPage({ params }: ArtistPageProps) {
  const { slug } = await params;
  const artist = await getArtist(slug);

  if (!artist) {
    notFound();
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-6">
        <ol className="flex items-center gap-2 text-sm">
          <li>
            <Link href="/" className="text-gray-400 hover:text-white transition-colors">
              Home
            </Link>
          </li>
          <li className="text-gray-600">/</li>
          <li>
            <Link href="/artists" className="text-gray-400 hover:text-white transition-colors">
              Artists
            </Link>
          </li>
          <li className="text-gray-600">/</li>
          <li className="text-white">{artist.name}</li>
        </ol>
      </nav>

      {/* Artist header */}
      <section className="flex flex-col md:flex-row gap-8 mb-12">
        <div className="w-48 h-48 md:w-64 md:h-64 bg-dark-700 rounded-lg overflow-hidden flex-shrink-0">
          {artist.image ? (
            <img
              src={artist.image}
              alt={artist.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center">
              <span className="text-8xl font-bold text-white/20">
                {artist.name.charAt(0)}
              </span>
            </div>
          )}
        </div>
        <div className="flex flex-col justify-end">
          <p className="text-sm text-gray-400 uppercase tracking-wider mb-2">Artist</p>
          <h1 className="text-4xl md:text-6xl font-bold text-white mb-4">{artist.name}</h1>
          <p className="text-gray-300 max-w-2xl">{artist.bio}</p>
          <p className="text-gray-400 mt-4">
            {artist.albums.length} {artist.albums.length === 1 ? 'album' : 'albums'} &bull;{' '}
            {artist.songCount} {artist.songCount === 1 ? 'recording' : 'recordings'}
          </p>
        </div>
      </section>

      {/* Albums Grid */}
      <section>
        <h2 className="text-2xl font-bold text-white mb-6">Discography</h2>
        {artist.albums.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            {artist.albums.map((album) => (
              <AlbumCard key={album.id} album={album} />
            ))}
          </div>
        ) : (
          <p className="text-gray-400">No albums available.</p>
        )}
      </section>
    </div>
  );
}
