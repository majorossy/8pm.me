import { Metadata } from 'next';
import { getArtistAlbums, getArtists } from '@/lib/api';
import { getArtistBandData } from '@/lib/artistData';
import { notFound } from 'next/navigation';
import ArtistPageContent from '@/components/ArtistPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';
import {
  generateMusicGroupSchema,
  generateBreadcrumbSchema,
  combineSchemas,
} from '@/lib/schema';

interface ArtistPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: ArtistPageProps): Promise<Metadata> {
  const { slug } = await params;
  const result = await getArtistAlbums(slug);

  if (!result) {
    return { title: 'Artist Not Found' };
  }

  const { artist } = result;

  // SEO-optimized title: "{Artist} Live Recordings & Concert Downloads | 8PM Archive"
  const title = `${artist.name} Live Recordings & Concert Downloads | 8PM Archive`;

  // SEO-optimized description with show count and USPs
  const showCount = artist.totalShows || artist.albumCount || 0;
  const showCountText = showCount > 100 ? `${Math.floor(showCount / 100) * 100}+` : showCount.toString();
  const description = `Stream and download ${artist.name} live recordings from ${showCountText} shows. High-quality soundboard and audience recordings. Free streaming, no signup required.`;

  // Build keywords from artist data
  const keywords = [
    artist.name,
    `${artist.name} live recordings`,
    `${artist.name} concert downloads`,
    `${artist.name} soundboard`,
    ...(artist.genres || []),
    'live concerts',
    'free streaming',
  ].join(', ');

  return generateSeoMetadata({
    title,
    description,
    keywords,
    path: `/artists/${slug}`,
    image: artist.bandImageUrl || artist.image,
    type: 'profile',
  });
}

export async function generateStaticParams() {
  const artists = await getArtists();
  return artists.map((artist) => ({
    slug: artist.slug,
  }));
}

export default async function ArtistPage({ params }: ArtistPageProps) {
  const { slug } = await params;
  const result = await getArtistAlbums(slug);

  if (!result) {
    notFound();
  }

  // Combine artist with albums for the page content
  const { artist: artistData, albums } = result;
  const artist = { ...artistData, albums };

  // Load band member data from static JSON file
  const bandData = await getArtistBandData(slug);
  const baseUrl = getBaseUrl();

  // Generate Schema.org structured data using centralized utilities
  const musicGroupSchema = generateMusicGroupSchema(artistData, baseUrl, bandData);

  // Breadcrumb schema for navigation
  const breadcrumbSchema = generateBreadcrumbSchema([
    { name: 'Home', url: baseUrl },
    { name: 'Artists', url: `${baseUrl}/artists` },
    { name: artist.name, url: `${baseUrl}/artists/${slug}` },
  ]);

  // Combine schemas using @graph (Google's preferred format)
  const combinedSchema = combineSchemas(musicGroupSchema, breadcrumbSchema);

  return (
    <>
      <StructuredData data={combinedSchema} />
      <ArtistPageContent artist={artist} bandData={bandData} />
    </>
  );
}
