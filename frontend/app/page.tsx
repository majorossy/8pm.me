import { getArtists, getArtistAlbums } from '@/lib/api';
import ArtistsPageContent from '@/components/ArtistsPageContent';

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

  return <ArtistsPageContent artists={artistsWithAlbums} />;
}
