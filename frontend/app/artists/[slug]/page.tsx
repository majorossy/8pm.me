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

  const description = artist.extendedBio
    ? artist.extendedBio.substring(0, 155) + '...'
    : `Stream ${artist.totalShows || artist.albumCount || 0} live shows from ${artist.name}. High-quality concert recordings from Archive.org.`;

  return generateSeoMetadata({
    title: `${artist.name} - Live Concert Recordings`,
    description,
    keywords: [artist.name, ...(artist.genres || []), 'live concerts', 'archive.org'].join(', '),
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
