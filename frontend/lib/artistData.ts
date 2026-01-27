/**
 * Static artist data loader
 * Loads band member data from JSON files in /data/artists/
 */

import { promises as fs } from 'fs';
import path from 'path';
import { BandMemberData } from './types';

/**
 * Loads artist band member data from static JSON file
 * @param slug - The artist slug (e.g., "sts9")
 * @returns BandMemberData object or null if file doesn't exist
 */
export async function getArtistBandData(
  slug: string
): Promise<BandMemberData | null> {
  try {
    const filePath = path.join(process.cwd(), 'data', 'artists', `${slug}.json`);
    const fileContent = await fs.readFile(filePath, 'utf-8');
    const data = JSON.parse(fileContent) as BandMemberData;
    return data;
  } catch (error) {
    // File doesn't exist or other read error
    if (error instanceof Error) {
      if ('code' in error && error.code === 'ENOENT') {
        console.info(`No band data file found for artist: ${slug}`);
        return null;
      }
      console.error(`Error loading artist data for ${slug}:`, error.message);
    }
    return null;
  }
}
