import { Metadata } from 'next';
import { getAlbum, getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import AlbumPageContent from '@/components/AlbumPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';
import { formatDuration } from '@/lib/formatDuration';

interface AlbumPageProps {
  params: Promise<{ slug: string; album: string }>;
}

export async function generateMetadata({ params }: AlbumPageProps): Promise<Metadata> {
  const { slug, album: albumSlug } = await params;
  const album = await getAlbum(slug, albumSlug);

  if (!album) {
    return { title: 'Album Not Found' };
  }

  const trackList = album.tracks.slice(0, 5).map(t => t.title).join(', ');
  const description = `${album.name} by ${album.artistName} - ${album.totalTracks} tracks${album.showDate ? ` - ${album.showDate}` : ''}${album.showVenue ? ` at ${album.showVenue}` : ''}. Featuring: ${trackList}`;

  return generateSeoMetadata({
    title: `${album.artistName} - ${album.name}`,
    description: description.substring(0, 160),
    path: `/artists/${slug}/album/${albumSlug}`,
    image: album.coverArt || album.wikipediaArtworkUrl,
    type: 'music.album',
  });
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

  const baseUrl = getBaseUrl();

  // Schema.org MusicAlbum structured data
  const musicAlbumSchema = {
    '@type': 'MusicAlbum',
    name: album.name,
    url: `${baseUrl}/artists/${slug}/album/${albumSlug}`,
    image: album.coverArt || album.wikipediaArtworkUrl,
    datePublished: album.showDate,
    byArtist: {
      '@type': 'MusicGroup',
      name: album.artistName,
      url: `${baseUrl}/artists/${slug}`,
    },
    numTracks: album.totalTracks,
    albumProductionType: 'LiveAlbum',
    albumReleaseType: 'AlbumRelease',
    recordedAt: album.showVenue ? {
      '@type': 'Place',
      name: album.showVenue,
      address: album.showLocation,
    } : undefined,
    track: album.tracks.map((track, index) => ({
      '@type': 'MusicRecording',
      position: index + 1,
      name: track.title,
      url: `${baseUrl}/artists/${slug}/album/${albumSlug}/track/${track.slug}`,
      duration: formatDuration(track.totalDuration),
    })),
  };

  // Breadcrumb schema
  const breadcrumbSchema = {
    '@type': 'BreadcrumbList',
    itemListElement: [
      {
        '@type': 'ListItem',
        position: 1,
        name: 'Home',
        item: baseUrl,
      },
      {
        '@type': 'ListItem',
        position: 2,
        name: album.artistName,
        item: `${baseUrl}/artists/${slug}`,
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: album.name,
        item: `${baseUrl}/artists/${slug}/album/${albumSlug}`,
      },
    ],
  };

  // Combine schemas using @graph
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicAlbumSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      <AlbumPageContent album={album} />
    </>
  );
}
