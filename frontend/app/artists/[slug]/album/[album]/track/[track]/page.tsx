import { Metadata } from 'next';
import { getTrack, getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import TrackPageContent from '@/components/TrackPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';
import {
  generateMusicRecordingSchema,
  generateTrackMusicEventSchema,
  generateBreadcrumbSchema,
  combineSchemas,
} from '@/lib/schema';

interface TrackPageProps {
  params: Promise<{ slug: string; album: string; track: string }>;
}

export async function generateMetadata({ params }: TrackPageProps): Promise<Metadata> {
  const { slug, album: albumSlug, track: trackSlug } = await params;
  const track = await getTrack(slug, albumSlug, trackSlug);

  if (!track) {
    return { title: 'Track Not Found' };
  }

  const description = `${track.title} from ${track.albumName} by ${track.artistName} - ${track.songCount} recording(s) available`;

  return generateSeoMetadata({
    title: `${track.title} - ${track.artistName}`,
    description,
    path: `/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,
    type: 'music.song',
  });
}

export async function generateStaticParams() {
  const artists = await getArtists();
  const params: { slug: string; album: string; track: string }[] = [];

  for (const artist of artists) {
    const artistDetail = await getArtist(artist.slug);
    if (artistDetail) {
      artistDetail.albums.forEach(album => {
        album.tracks.forEach(track => {
          // Ensure all parameters are strings
          if (
            artist.slug && typeof artist.slug === 'string' &&
            album.slug && typeof album.slug === 'string' &&
            track.slug && typeof track.slug === 'string'
          ) {
            params.push({
              slug: artist.slug,
              album: album.slug,
              track: track.slug,
            });
          }
        });
      });
    }
  }

  return params;
}

export default async function TrackPage({ params }: TrackPageProps) {
  const { slug, album: albumSlug, track: trackSlug } = await params;
  const track = await getTrack(slug, albumSlug, trackSlug);

  if (!track) {
    notFound();
  }

  const baseUrl = getBaseUrl();

  // Generate Schema.org structured data using centralized utilities
  // MusicRecording includes AggregateRating and AudioObject when available
  const musicRecordingSchema = generateMusicRecordingSchema(track, slug, albumSlug, baseUrl);

  // MusicEvent schema for local SEO (track was part of a live event)
  // Helps capture venue-based search queries
  const musicEventSchema = generateTrackMusicEventSchema(track, slug, albumSlug, baseUrl);

  // Breadcrumb schema for navigation
  const breadcrumbSchema = generateBreadcrumbSchema([
    { name: 'Home', url: baseUrl },
    { name: track.artistName, url: `${baseUrl}/artists/${slug}` },
    { name: track.albumName, url: `${baseUrl}/artists/${slug}/album/${albumSlug}` },
    { name: track.title, url: `${baseUrl}/artists/${slug}/album/${albumSlug}/track/${trackSlug}` },
  ]);

  // Combine schemas using @graph (Google's preferred format)
  const combinedSchema = combineSchemas(
    musicRecordingSchema,
    breadcrumbSchema,
    musicEventSchema
  );

  return (
    <>
      <StructuredData data={combinedSchema} />
      <TrackPageContent track={track} />
    </>
  );
}
