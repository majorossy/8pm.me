import { getTrack, getArtist, getArtists, formatDuration } from '@/lib/api';
import SongCard from '@/components/SongCard';
import Link from 'next/link';
import { notFound } from 'next/navigation';

interface TrackPageProps {
  params: Promise<{ slug: string; album: string; track: string }>;
}

export async function generateStaticParams() {
  const artists = await getArtists();
  const params: { slug: string; album: string; track: string }[] = [];

  for (const artist of artists) {
    const artistDetail = await getArtist(artist.slug);
    if (artistDetail) {
      artistDetail.albums.forEach(album => {
        album.tracks.forEach(track => {
          params.push({
            slug: artist.slug,
            album: album.slug,
            track: track.slug,
          });
        });
      });
    }
  }

  return params;
}

export default async function TrackPage({ params }: TrackPageProps) {
  const { slug, album: albumSlug, track: trackSlug } = await params;
  const track = await getTrack(slug, albumSlug, trackSlug);

  if (!track) {
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
            <Link href={`/artists/${track.artistSlug}`} className="text-gray-400 hover:text-white transition-colors">
              {track.artistName}
            </Link>
          </li>
          <li className="text-gray-600">/</li>
          <li>
            <Link href={`/artists/${track.artistSlug}/album/${track.albumIdentifier}`} className="text-gray-400 hover:text-white transition-colors truncate max-w-[150px]">
              {track.albumName}
            </Link>
          </li>
          <li className="text-gray-600">/</li>
          <li className="text-white truncate max-w-[150px]">{track.title}</li>
        </ol>
      </nav>

      {/* Track header */}
      <section className="mb-12">
        <p className="text-sm text-gray-400 uppercase tracking-wider mb-2">Track</p>
        <h1 className="text-3xl md:text-5xl font-bold text-white mb-4">{track.title}</h1>
        <p className="text-gray-300">
          From{' '}
          <Link
            href={`/artists/${track.artistSlug}/album/${track.albumIdentifier}`}
            className="text-primary hover:underline"
          >
            {track.albumName}
          </Link>
          {' '}by{' '}
          <Link
            href={`/artists/${track.artistSlug}`}
            className="text-primary hover:underline"
          >
            {track.artistName}
          </Link>
        </p>
        <p className="text-gray-400 mt-4">
          {track.songCount} {track.songCount === 1 ? 'recording' : 'recordings'} available
          {track.songCount > 0 && ` (${formatDuration(track.totalDuration)} each)`}
        </p>
      </section>

      {/* Song variants */}
      <section>
        <h2 className="text-2xl font-bold text-white mb-6">Available Recordings</h2>
        <div className="bg-dark-800 rounded-lg overflow-hidden">
          {track.songs.length > 0 ? (
            track.songs.map((song, index) => (
              <SongCard key={song.id} song={song} index={index + 1} />
            ))
          ) : (
            <p className="text-gray-400 p-4">No recordings available.</p>
          )}
        </div>
      </section>
    </div>
  );
}
