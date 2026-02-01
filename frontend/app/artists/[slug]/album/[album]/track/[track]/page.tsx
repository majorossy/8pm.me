import { Metadata } from 'next';
import { getTrack, getArtist, getArtists } from '@/lib/api';
import { notFound } from 'next/navigation';
import TrackPageContent from '@/components/TrackPageContent';
import StructuredData from '@/components/StructuredData';
import { generateSeoMetadata, getBaseUrl } from '@/lib/seo';
import { formatDuration } from '@/lib/formatDuration';

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

  // Get the first recording for audio and rating data
  const primarySong = track.songs?.[0];

  // Schema.org MusicRecording structured data
  const musicRecordingSchema = {
    '@type': 'MusicRecording',
    name: track.title,
    url: `${baseUrl}/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,
    byArtist: {
      '@type': 'MusicGroup',
      name: track.artistName,
      url: `${baseUrl}/artists/${slug}`,
    },
    recordingOf: {
      '@type': 'MusicComposition',
      name: track.title,
      composer: {
        '@type': 'MusicGroup',
        name: track.artistName,
      },
    },
    inAlbum: {
      '@type': 'MusicAlbum',
      name: track.albumName,
      datePublished: primarySong?.showDate,
      url: `${baseUrl}/artists/${slug}/album/${albumSlug}`,
    },
    duration: formatDuration(track.totalDuration),
    recordedAt: primarySong?.showVenue ? {
      '@type': 'Place',
      name: primarySong.showVenue,
      address: primarySong.showLocation,
    } : undefined,
    datePublished: primarySong?.showDate,
    aggregateRating: primarySong?.numReviews && primarySong.numReviews >= 2 ? {
      '@type': 'AggregateRating',
      ratingValue: primarySong.avgRating?.toFixed(1),
      reviewCount: primarySong.numReviews,
      bestRating: 5,
      worstRating: 1,
    } : undefined,
    audio: primarySong?.streamUrl ? {
      '@type': 'AudioObject',
      contentUrl: primarySong.streamUrl,
      encodingFormat: 'audio/mpeg',
      duration: formatDuration(primarySong.duration),
    } : undefined,
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
        name: track.artistName,
        item: `${baseUrl}/artists/${slug}`,
      },
      {
        '@type': 'ListItem',
        position: 3,
        name: track.albumName,
        item: `${baseUrl}/artists/${slug}/album/${albumSlug}`,
      },
      {
        '@type': 'ListItem',
        position: 4,
        name: track.title,
        item: `${baseUrl}/artists/${slug}/album/${albumSlug}/track/${trackSlug}`,
      },
    ],
  };

  // Combine schemas using @graph
  const combinedSchema = {
    '@context': 'https://schema.org',
    '@graph': [musicRecordingSchema, breadcrumbSchema],
  };

  return (
    <>
      <StructuredData data={combinedSchema} />
      <TrackPageContent track={track} />
    </>
  );
}
