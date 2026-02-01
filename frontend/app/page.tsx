import { Metadata } from 'next';
import { getArtists, getArtistAlbums } from '@/lib/api';
import ArtistsPageContent from '@/components/ArtistsPageContent';
import StructuredData from '@/components/StructuredData';
import { getBaseUrl } from '@/lib/seo';

export const metadata: Metadata = {
  title: 'EIGHTPM - Stream Live Concert Recordings',
  description: 'Discover and stream thousands of live concert recordings from Archive.org. Featuring Grateful Dead, Phish, Widespread Panic, and more.',
  alternates: {
    canonical: '/',
  },
};

export default async function HomePage() {
  const artists = await getArtists();

  // Fetch albums for each artist using lightweight endpoint (no tracks/products)
  const artistsWithAlbums = await Promise.all(
    artists.map(async (artist) => {
      const result = await getArtistAlbums(artist.slug);
      return {
        ...artist,
        albums: result?.albums || [],
      };
    })
  );

  const baseUrl = getBaseUrl();

  // Schema.org WebSite + SearchAction structured data
  const webSiteSchema = {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'EIGHTPM',
    url: baseUrl,
    description: 'Stream live concert recordings from Archive.org',
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${baseUrl}/search?q={search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  };

  return (
    <>
      <StructuredData data={webSiteSchema} />
      <ArtistsPageContent artists={artistsWithAlbums} />
    </>
  );
}
