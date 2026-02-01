import { Metadata } from 'next';
import { getArtists, getArtistAlbums } from '@/lib/api';
import ArtistsPageContent from '@/components/ArtistsPageContent';
import StructuredData from '@/components/StructuredData';
import { getBaseUrl } from '@/lib/seo';
import { generateWebSiteSchema } from '@/lib/schema';

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
  const webSiteSchema = generateWebSiteSchema(baseUrl);

  return (
    <>
      <StructuredData data={webSiteSchema} />
      <ArtistsPageContent artists={artistsWithAlbums} />
    </>
  );
}
