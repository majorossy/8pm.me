import { getAlbum, getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import AlbumPageContent from '@/components/AlbumPageContent';

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

  return <AlbumPageContent album={album} />;
}
