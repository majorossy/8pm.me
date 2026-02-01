/**
 * Related Artists Configuration
 *
 * Purpose: Define artist relationships for internal linking and discovery
 * SEO Benefits:
 * - Internal linking improves crawl depth
 * - Related suggestions increase user engagement and dwell time
 * - Helps search engines understand content relationships
 */

export interface RelatedArtistConfig {
  slug: string;
  name: string;
  genres?: string[];
}

/**
 * Curated related artists mapping
 * Each key is an artist slug, values are arrays of related artist slugs
 *
 * Relationships are based on:
 * - Musical style and genre
 * - Historical connections (band members, collaborations)
 * - Fan overlap and festival appearances
 */
const RELATED_ARTISTS: Record<string, string[]> = {
  // Grateful Dead family tree
  'grateful-dead': ['phish', 'widespread-panic', 'furthur', 'ratdog', 'phil-lesh-and-friends', 'dead-and-company'],
  'furthur': ['grateful-dead', 'phil-lesh-and-friends', 'ratdog', 'dead-and-company', 'bob-weir'],
  'phil-lesh-and-friends': ['grateful-dead', 'furthur', 'ratdog', 'dead-and-company'],
  'ratdog': ['grateful-dead', 'furthur', 'phil-lesh-and-friends', 'bob-weir'],

  // Phish and related
  'phish': ['grateful-dead', 'umphreys-mcgee', 'moe', 'thestringcheeseincident', 'thediscobiscuits', 'goose'],
  'goose': ['phish', 'billy-strings', 'umphreys-mcgee', 'moe', 'pigeons-playing-ping-pong'],
  'moe': ['phish', 'umphreys-mcgee', 'thestringcheeseincident', 'thediscobiscuits', 'lettuce'],

  // String Cheese / Bluegrass-jam fusion
  'thestringcheeseincident': ['leftover-salmon', 'yonder-mountain-string-band', 'railroad-earth', 'greensky-bluegrass', 'phish'],
  'railroadearth': ['thestringcheeseincident', 'leftover-salmon', 'yonder-mountain-string-band', 'greensky-bluegrass', 'billy-strings'],
  'leftover-salmon': ['thestringcheeseincident', 'railroadearth', 'yonder-mountain-string-band', 'greensky-bluegrass'],
  'yonder-mountain-string-band': ['thestringcheeseincident', 'railroadearth', 'leftover-salmon', 'greensky-bluegrass'],
  'billy-strings': ['railroadearth', 'greensky-bluegrass', 'goose', 'grateful-dead'],

  // Widespread Panic family
  'widespread-panic': ['grateful-dead', 'govtmule', 'tedeschi-trucks-band', 'allman-brothers-band', 'col-bruce-hampton'],
  'govtmule': ['widespread-panic', 'allman-brothers-band', 'tedeschi-trucks-band', 'phish'],
  'tedeschi-trucks-band': ['allman-brothers-band', 'widespread-panic', 'govtmule', 'derek-trucks-band'],

  // Electronic/Dance-oriented jam
  'sts9': ['lotus', 'disco-biscuits', 'papadosio', 'umphreys-mcgee'],
  'thediscobiscuits': ['sts9', 'lotus', 'papadosio', 'phish', 'moe'],
  'lotus': ['sts9', 'thediscobiscuits', 'papadosio'],

  // Funky jam bands
  'lettuce': ['soulive', 'galactic', 'medeski-martin-wood', 'moe'],
  'galactic': ['lettuce', 'soulive', 'trombone-shorty'],
  'soulive': ['lettuce', 'galactic', 'medeski-martin-wood'],

  // Modern jam scene
  'umphreys-mcgee': ['phish', 'moe', 'thediscobiscuits', 'goose', 'sts9'],

  // Grace Potter
  'gracepotterandthenocturnals': ['tedeschi-trucks-band', 'my-morning-jacket', 'widespread-panic'],
  'my-morning-jacket': ['gracepotterandthenocturnals', 'wilco', 'band-of-horses'],

  // Cabinet / folk-jam
  'cabinet': ['railroadearth', 'greensky-bluegrass', 'yonder-mountain-string-band'],
  'keller-williams': ['leftover-salmon', 'thestringcheeseincident', 'moe'],

  // Matisyahu
  'matisyahu': ['311', 'slightly-stoopid', 'rebelution'],

  // Dogs in a Pile / newer jam bands
  'dogs-in-a-pile': ['goose', 'pigeons-playing-ping-pong', 'eggy'],

  // Default fallbacks for unspecified artists - popular jam bands
  '_default': ['grateful-dead', 'phish', 'widespread-panic', 'thestringcheeseincident'],
};

/**
 * Get related artist slugs for a given artist
 * @param artistSlug - The artist's URL slug
 * @param limit - Maximum number of related artists to return (default: 4)
 * @returns Array of related artist slugs
 */
export function getRelatedArtistSlugs(artistSlug: string, limit: number = 4): string[] {
  const related = RELATED_ARTISTS[artistSlug] || RELATED_ARTISTS['_default'];

  // Filter out the current artist (in case of circular refs) and limit
  return related
    .filter(slug => slug !== artistSlug)
    .slice(0, limit);
}

/**
 * Check if an artist has related artists configured
 * @param artistSlug - The artist's URL slug
 * @returns Boolean indicating if relationships exist
 */
export function hasRelatedArtists(artistSlug: string): boolean {
  return artistSlug in RELATED_ARTISTS;
}

/**
 * Get all artists that reference a given artist
 * Useful for finding "bidirectional" relationships
 * @param artistSlug - The artist's URL slug
 * @returns Array of artist slugs that reference this artist
 */
export function getArtistsRelatedTo(artistSlug: string): string[] {
  const relatedTo: string[] = [];

  for (const [slug, related] of Object.entries(RELATED_ARTISTS)) {
    if (slug !== '_default' && related.includes(artistSlug)) {
      relatedTo.push(slug);
    }
  }

  return relatedTo;
}
