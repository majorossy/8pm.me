import { getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import ArtistPageContent from '@/components/ArtistPageContent';

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

  return <ArtistPageContent artist={artist} />;
}
