import { Metadata } from 'next';
import { getAlbum, getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import AlbumPageContent from '@/components/AlbumPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';
import {
  generateMusicAlbumSchema,
  generateMusicEventSchema,
  generateBreadcrumbSchema,
  combineSchemas,
  getShowMetadataFromAlbum,
} from '@/lib/schema';

interface AlbumPageProps {
  params: Promise<{ slug: string; album: string }>;
}

export async function generateMetadata({ params }: AlbumPageProps): Promise<Metadata> {
  const { slug, album: albumSlug } = await params;
  const album = await getAlbum(slug, albumSlug);

  if (!album) {
    return { title: 'Album Not Found' };
  }

  // Get show metadata from tracks (albums are categories, show data comes from tracks)
  const showMetadata = getShowMetadataFromAlbum(album);
  const trackList = album.tracks.slice(0, 5).map(t => t.title).join(', ');
  const description = `${album.name} by ${album.artistName} - ${album.totalTracks} tracks${showMetadata.showDate ? ` - ${showMetadata.showDate}` : ''}${showMetadata.showVenue ? ` at ${showMetadata.showVenue}` : ''}. Featuring: ${trackList}`;

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

  // Generate Schema.org structured data using centralized utilities
  // MusicAlbum schema includes AggregateRating if sufficient reviews exist
  const musicAlbumSchema = generateMusicAlbumSchema(album, slug, baseUrl);

  // Breadcrumb schema for navigation
  const breadcrumbSchema = generateBreadcrumbSchema([
    { name: 'Home', url: baseUrl },
    { name: album.artistName, url: `${baseUrl}/artists/${slug}` },
    { name: album.name, url: `${baseUrl}/artists/${slug}/album/${albumSlug}` },
  ]);

  // MusicEvent schema for local SEO (venue-based searches)
  // This helps capture queries like "grateful dead red rocks 1978"
  const musicEventSchema = generateMusicEventSchema(album, slug, baseUrl);

  // Combine schemas using @graph (Google's preferred format)
  const combinedSchema = combineSchemas(
    musicAlbumSchema,
    breadcrumbSchema,
    musicEventSchema
  );

  return (
    <>
      <StructuredData data={combinedSchema} />
      <AlbumPageContent album={album} />
    </>
  );
}
