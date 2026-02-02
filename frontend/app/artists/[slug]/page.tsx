import { Metadata } from 'next';
import { getArtist, getArtists } from '@/lib/api';
import { getArtistBandData } from '@/lib/artistData';
import { notFound } from 'next/navigation';
import ArtistPageContent from '@/components/ArtistPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';
import {
  generateMusicGroupSchema,
  generateBreadcrumbSchema,
  generateArtistFAQSchema,
  combineSchemas,
} from '@/lib/schema';
import { getRelatedArtistSlugs } from '@/lib/relatedArtists';
import { RelatedArtist } from '@/components/RelatedArtists';

interface ArtistPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: ArtistPageProps): Promise<Metadata> {
  const { slug } = await params;
  const artist = await getArtist(slug);

  if (!artist) {
    return { title: 'Artist Not Found' };
  }

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
  const artist = await getArtist(slug);

  if (!artist) {
    notFound();
  }

  // Load band member data from static JSON file
  const bandData = await getArtistBandData(slug);
  const baseUrl = getBaseUrl();

  // Get all artists for related artists lookup
  const allArtists = await getArtists();

  // Get related artist slugs from the configuration
  const relatedSlugs = getRelatedArtistSlugs(slug, 4);

  // Build related artists data with full details
  const relatedArtists: RelatedArtist[] = relatedSlugs
    .map((relatedSlug): RelatedArtist | null => {
      const relatedArtist = allArtists.find((a) => a.slug === relatedSlug);
      if (!relatedArtist) return null;
      return {
        slug: relatedArtist.slug,
        name: relatedArtist.name,
        image: relatedArtist.bandImageUrl || relatedArtist.image,
        showCount: relatedArtist.totalShows || relatedArtist.albumCount,
        genres: relatedArtist.genres,
      };
    })
    .filter((a): a is RelatedArtist => a !== null);

  // Generate Schema.org structured data using centralized utilities
  const musicGroupSchema = generateMusicGroupSchema(artist, baseUrl, bandData);

  // Breadcrumb schema for navigation
  const breadcrumbSchema = generateBreadcrumbSchema([
    { name: 'Home', url: baseUrl },
    { name: 'Artists', url: `${baseUrl}/artists` },
    { name: artist.name, url: `${baseUrl}/artists/${slug}` },
  ]);

  // FAQ schema for voice search optimization
  const faqSchema = generateArtistFAQSchema(
    artist.name,
    artist.totalShows,
    artist.yearsActive,
    artist.originLocation,
    artist.mostPlayedTrack
  );

  // Combine schemas using @graph (Google's preferred format)
  const combinedSchema = combineSchemas(musicGroupSchema, breadcrumbSchema, faqSchema);

  return (
    <>
      <StructuredData data={combinedSchema} />
      <ArtistPageContent artist={artist} bandData={bandData} relatedArtists={relatedArtists} />
    </>
  );
}
