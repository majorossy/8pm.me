import { getArtists, getSongs } from '@/lib/api';
import HomePageContent from '@/components/HomePageContent';

export default async function HomePage() {
  const artists = await getArtists();
  const songs = await getSongs(20);

  return <HomePageContent artists={artists} songs={songs} />;
}
