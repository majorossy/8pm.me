import { Metadata } from 'next';
import { getArtist, getArtists } from '@/lib/api';
import { getArtistBandData } from '@/lib/artistData';
import { notFound } from 'next/navigation';
import ArtistPageContent from '@/components/ArtistPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';

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

  // Schema.org MusicGroup structured data
  const musicGroupSchema = {
    '@type': 'MusicGroup',
    name: artist.name,
    url: `${baseUrl}/artists/${slug}`,
    image: artist.bandImageUrl || artist.image,
    description: artist.extendedBio || artist.bio,
    genre: artist.genres,
    foundingDate: artist.formationDate,
    foundingLocation: artist.originLocation,
    sameAs: [
      artist.officialWebsite,
      artist.facebook,
      artist.instagram,
      artist.twitter,
      artist.youtubeChannel,
    ].filter(Boolean),
    member: bandData?.members?.current?.map(member => ({
      '@type': 'OrganizationRole',
      member: {
        '@type': 'Person',
        name: member.name,
      },
      roleName: member.role,
      startDate: member.years?.split('-')[0],
      endDate: member.years?.includes('present') ? undefined : member.years?.split('-')[1],
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
        name: 'Artists',
        item: `${baseUrl}/artists`,
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: artist.name,
        item: `${baseUrl}/artists/${slug}`,
      },
    ],
  };

  // Combine schemas using @graph
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicGroupSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      <ArtistPageContent artist={artist} bandData={bandData} />
    </>
  );
}
