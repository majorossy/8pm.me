/**
 * Convert seconds to ISO 8601 duration format (PT12M34S)
 * Required for Schema.org duration fields
 */
export function formatDuration(seconds: number | undefined | null): string {
  if (!seconds || seconds <= 0) return 'PT0S';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `PT${mins}M${secs}S`;
}

/**
 * Convert seconds to human-readable duration (12:34)
 * For display purposes
 */
export function formatDurationDisplay(seconds: number | undefined | null): string {
  if (!seconds || seconds <= 0) return '0:00';
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}
