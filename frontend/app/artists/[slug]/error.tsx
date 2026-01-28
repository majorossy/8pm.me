'use client';

import ErrorFallback from '@/components/ErrorFallback';

export default function ArtistError({
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
      title="Couldn't load artist"
      description="We had trouble loading this artist's page. Please try again."
    />
  );
}
