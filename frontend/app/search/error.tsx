'use client';

import ErrorFallback from '@/components/ErrorFallback';

export default function SearchError({
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
      title="Search failed"
      description="We had trouble with your search. Please try again."
    />
  );
}
