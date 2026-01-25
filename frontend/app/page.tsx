import { getArtists, getSongs } from '@/lib/api';
import ArtistCard from '@/components/ArtistCard';
import SongCard from '@/components/SongCard';

export default async function HomePage() {
  const artists = await getArtists();
  const songs = await getSongs(20);

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Hero */}
      <section className="mb-12">
        <div className="bg-gradient-to-r from-primary/20 to-accent/20 rounded-2xl p-8 md:p-12">
          <h1 className="text-4xl md:text-5xl font-bold text-white mb-4">
            Welcome to EightPM
          </h1>
          <p className="text-lg text-gray-300 max-w-2xl">
            Discover amazing artists and build your perfect playlist. Click play on any song to start listening.
          </p>
        </div>
      </section>

      {/* Featured Artists */}
      <section className="mb-12">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-white">Featured Artists</h2>
          <a href="/artists" className="text-primary hover:text-primary-dark transition-colors">
            View all
          </a>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {artists.slice(0, 4).map((artist) => (
            <ArtistCard key={artist.id} artist={artist} />
          ))}
        </div>
      </section>

      {/* All Songs */}
      <section>
        <h2 className="text-2xl font-bold text-white mb-6">All Songs</h2>
        <div className="bg-dark-800 rounded-lg overflow-hidden">
          {songs.map((song, index) => (
            <SongCard key={song.id} song={song} index={index + 1} />
          ))}
        </div>
      </section>
    </div>
  );
}
