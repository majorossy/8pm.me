'use client';

import ErrorFallback from '@/components/ErrorFallback';

export default function PlaylistsError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <ErrorFallback
      error={error}
      reset={reset}
      title="Couldn't load playlists"
      description="We had trouble loading your playlists. Please try again."
    />
  );
}
