import { getAlbum, getArtist, getArtists, formatDuration } from '@/lib/api';
import TrackCard from '@/components/TrackCard';
import Link from 'next/link';
import { notFound } from 'next/navigation';

interface AlbumPageProps {
  params: Promise<{ slug: string; album: string }>;
}

export async function generateStaticParams() {
  const artists = await getArtists();
  const params: { slug: string; album: string }[] = [];

  for (const artist of artists) {
    const artistDetail = await getArtist(artist.slug);
    if (artistDetail) {
      artistDetail.albums.forEach(album => {
        params.push({ slug: artist.slug, album: album.slug });
      });
    }
  }

  return params;
}

export default async function AlbumPage({ params }: AlbumPageProps) {
  const { slug, album: albumSlug } = await params;
  const album = await getAlbum(slug, albumSlug);

  if (!album) {
    notFound();
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Breadcrumb */}
      <nav className="mb-6">
        <ol className="flex items-center gap-2 text-sm flex-wrap">
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
          <li>
            <Link href={`/artists/${album.artistSlug}`} className="text-gray-400 hover:text-white transition-colors">
              {album.artistName}
            </Link>
          </li>
          <li className="text-gray-600">/</li>
          <li className="text-white truncate max-w-[200px]">{album.name}</li>
        </ol>
      </nav>

      {/* Album header */}
      <section className="flex flex-col md:flex-row gap-8 mb-12">
        <div className="w-48 h-48 md:w-64 md:h-64 bg-dark-700 rounded-lg overflow-hidden flex-shrink-0">
          {album.coverArt ? (
            <img
              src={album.coverArt}
              alt={album.name}
              className="w-full h-full object-cover"
            />
          ) : (
            <div className="w-full h-full bg-gradient-to-br from-primary/30 to-accent/30 flex items-center justify-center">
              {/* Vinyl record icon */}
              <svg className="w-24 h-24 text-white/20" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="1.5" fill="none"/>
                <circle cx="12" cy="12" r="6" stroke="currentColor" strokeWidth="0.5" fill="none" opacity="0.5"/>
                <circle cx="12" cy="12" r="3" fill="currentColor"/>
              </svg>
            </div>
          )}
        </div>
        <div className="flex flex-col justify-end">
          <p className="text-sm text-gray-400 uppercase tracking-wider mb-2">Album</p>
          <h1 className="text-3xl md:text-5xl font-bold text-white mb-4">{album.name}</h1>
          <Link href={`/artists/${album.artistSlug}`} className="text-gray-300 hover:text-primary transition-colors">
            {album.artistName}
          </Link>
          {album.showDate && (
            <p className="text-gray-400 mt-2">{album.showDate}</p>
          )}
          {album.showVenue && (
            <p className="text-gray-400">{album.showVenue}</p>
          )}
          <p className="text-gray-400 mt-4">
            {album.totalTracks} {album.totalTracks === 1 ? 'track' : 'tracks'} &bull;{' '}
            {formatDuration(album.totalDuration)}
          </p>
        </div>
      </section>

      {/* Tracks list */}
      <section>
        <h2 className="text-2xl font-bold text-white mb-6">Tracks</h2>
        <div className="bg-dark-800 rounded-lg overflow-hidden">
          {album.tracks.length > 0 ? (
            album.tracks.map((track, index) => (
              <TrackCard key={track.id} track={track} index={index + 1} />
            ))
          ) : (
            <p className="text-gray-400 p-4">No tracks available.</p>
          )}
        </div>
      </section>
    </div>
  );
}
