import { getTrack, getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import TrackPageContent from '@/components/TrackPageContent';

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
          // Ensure all parameters are strings
          if (
            artist.slug && typeof artist.slug === 'string' &&
            album.slug && typeof album.slug === 'string' &&
            track.slug && typeof track.slug === 'string'
          ) {
            params.push({
              slug: artist.slug,
              album: album.slug,
              track: track.slug,
            });
          }
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

  return <TrackPageContent track={track} />;
}
