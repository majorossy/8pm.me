'use client';

import ErrorFallback from '@/components/ErrorFallback';

export default function ArtistsError({
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
      title="Couldn't load artists"
      description="We had trouble loading the artists. Please try again."
    />
  );
}
