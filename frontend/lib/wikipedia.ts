/**
 * Wikipedia API client for fetching artist summaries
 * Uses Wikipedia REST API v1 with 7-day caching
 */

import { WikipediaSummary } from './types';

/**
 * Fetches a Wikipedia summary for the given page title
 * @param pageTitle - The Wikipedia page title (e.g., "Grateful_Dead")
 * @returns WikipediaSummary object or null if not found/error
 */
export async function fetchWikipediaSummary(
  pageTitle: string
): Promise<WikipediaSummary | null> {
  try {
    // Wikipedia REST API endpoint
    const url = `https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(pageTitle)}`;

    const response = await fetch(url, {
      next: { revalidate: 604800 }, // 7 days in seconds
      headers: {
        'User-Agent': '8pm-music-app/1.0',
      },
    } as RequestInit);

    if (!response.ok) {
      if (response.status === 404) {
        console.warn(`Wikipedia page not found: ${pageTitle}`);
        return null;
      }
      throw new Error(`Wikipedia API error: ${response.status}`);
    }

    const data = await response.json();

    // Map Wikipedia response to our interface
    const summary: WikipediaSummary = {
      title: data.title,
      extract: data.extract,
      description: data.description || null,
      thumbnail: data.thumbnail
        ? {
            source: data.thumbnail.source,
            width: data.thumbnail.width,
            height: data.thumbnail.height,
          }
        : null,
      url: data.content_urls?.desktop?.page || null,
    };

    return summary;
  } catch (error) {
    console.error(`Error fetching Wikipedia summary for ${pageTitle}:`, error);
    return null;
  }
}
