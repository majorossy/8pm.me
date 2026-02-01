/**
 * Venue database for jam band scene - used for local SEO schema enrichment.
 * Contains geocoordinates and standardized addresses for famous venues.
 */

export interface Venue {
  name: string;
  city: string;
  state: string;
  country: string;
  lat: number;
  lon: number;
  aliases: string[];
}

/**
 * Top jam band venues with geocoordinates.
 * Aliases help match various naming conventions from Archive.org metadata.
 */
export const VENUE_DATABASE: Record<string, Venue> = {
  // Colorado
  'red-rocks': {
    name: 'Red Rocks Amphitheatre',
    city: 'Morrison',
    state: 'CO',
    country: 'US',
    lat: 39.6654,
    lon: -105.2057,
    aliases: ['red rocks', 'red rocks amphitheatre', 'red rocks amphitheater', 'morrison'],
  },

  // New York
  'msg': {
    name: 'Madison Square Garden',
    city: 'New York',
    state: 'NY',
    country: 'US',
    lat: 40.7505,
    lon: -73.9934,
    aliases: ['madison square garden', 'msg', 'the garden', 'madison sq'],
  },
  'radio-city': {
    name: 'Radio City Music Hall',
    city: 'New York',
    state: 'NY',
    country: 'US',
    lat: 40.7600,
    lon: -73.9800,
    aliases: ['radio city', 'radio city music hall'],
  },
  'barton-hall': {
    name: 'Barton Hall - Cornell University',
    city: 'Ithaca',
    state: 'NY',
    country: 'US',
    lat: 42.4450,
    lon: -76.4830,
    aliases: ['barton hall', 'cornell', 'cornell university'],
  },
  'beacon': {
    name: 'Beacon Theatre',
    city: 'New York',
    state: 'NY',
    country: 'US',
    lat: 40.7785,
    lon: -73.9814,
    aliases: ['beacon theatre', 'beacon theater', 'the beacon'],
  },

  // California
  'fillmore-sf': {
    name: 'The Fillmore',
    city: 'San Francisco',
    state: 'CA',
    country: 'US',
    lat: 37.7840,
    lon: -122.4330,
    aliases: ['fillmore', 'the fillmore', 'fillmore auditorium', 'fillmore sf', 'san francisco fillmore'],
  },
  'greek-berkeley': {
    name: 'Greek Theatre',
    city: 'Berkeley',
    state: 'CA',
    country: 'US',
    lat: 37.8734,
    lon: -122.2540,
    aliases: ['greek theatre', 'greek theater', 'berkeley greek', 'greek berkeley'],
  },
  'shoreline': {
    name: 'Shoreline Amphitheatre',
    city: 'Mountain View',
    state: 'CA',
    country: 'US',
    lat: 37.4267,
    lon: -122.0806,
    aliases: ['shoreline', 'shoreline amphitheatre', 'shoreline amphitheater', 'mountain view'],
  },
  'hollywood-bowl': {
    name: 'Hollywood Bowl',
    city: 'Los Angeles',
    state: 'CA',
    country: 'US',
    lat: 34.1122,
    lon: -118.3390,
    aliases: ['hollywood bowl'],
  },

  // Washington
  'gorge': {
    name: 'Gorge Amphitheatre',
    city: 'George',
    state: 'WA',
    country: 'US',
    lat: 47.1019,
    lon: -119.9964,
    aliases: ['gorge', 'the gorge', 'gorge amphitheatre', 'gorge amphitheater', 'george wa'],
  },

  // Wisconsin
  'alpine-valley': {
    name: 'Alpine Valley Music Theatre',
    city: 'East Troy',
    state: 'WI',
    country: 'US',
    lat: 42.7489,
    lon: -88.4165,
    aliases: ['alpine valley', 'alpine', 'east troy'],
  },

  // Indiana
  'deer-creek': {
    name: 'Ruoff Music Center',
    city: 'Noblesville',
    state: 'IN',
    country: 'US',
    lat: 40.0456,
    lon: -86.0167,
    aliases: ['deer creek', 'ruoff', 'verizon wireless', 'klipsch', 'noblesville'],
  },

  // Georgia
  'fox-atlanta': {
    name: 'Fox Theatre',
    city: 'Atlanta',
    state: 'GA',
    country: 'US',
    lat: 33.7725,
    lon: -84.3856,
    aliases: ['fox theatre atlanta', 'fox theater atlanta', 'atlanta fox'],
  },

  // Tennessee
  'bonnaroo': {
    name: 'Bonnaroo Music Festival',
    city: 'Manchester',
    state: 'TN',
    country: 'US',
    lat: 35.4792,
    lon: -86.0597,
    aliases: ['bonnaroo', 'manchester tn', 'great stage park'],
  },
  'ryman': {
    name: 'Ryman Auditorium',
    city: 'Nashville',
    state: 'TN',
    country: 'US',
    lat: 36.1613,
    lon: -86.7780,
    aliases: ['ryman', 'ryman auditorium', 'grand ole opry'],
  },

  // Virginia
  'lockn': {
    name: "Lockn' Festival",
    city: 'Arrington',
    state: 'VA',
    country: 'US',
    lat: 37.6281,
    lon: -78.8917,
    aliases: ['lockn', "lockn'", 'arrington va', 'infinity downs'],
  },

  // Pennsylvania
  'mann-center': {
    name: 'Mann Center for the Performing Arts',
    city: 'Philadelphia',
    state: 'PA',
    country: 'US',
    lat: 39.9782,
    lon: -75.2203,
    aliases: ['mann center', 'mann music center', 'fairmount park'],
  },

  // Illinois
  'chicago-theatre': {
    name: 'Chicago Theatre',
    city: 'Chicago',
    state: 'IL',
    country: 'US',
    lat: 41.8854,
    lon: -87.6275,
    aliases: ['chicago theatre', 'chicago theater'],
  },
  'soldier-field': {
    name: 'Soldier Field',
    city: 'Chicago',
    state: 'IL',
    country: 'US',
    lat: 41.8623,
    lon: -87.6167,
    aliases: ['soldier field'],
  },
  'northerly-island': {
    name: 'Huntington Bank Pavilion at Northerly Island',
    city: 'Chicago',
    state: 'IL',
    country: 'US',
    lat: 41.8583,
    lon: -87.6094,
    aliases: ['northerly island', 'charter one', 'firstmerit'],
  },

  // Massachusetts
  'great-woods': {
    name: 'Xfinity Center',
    city: 'Mansfield',
    state: 'MA',
    country: 'US',
    lat: 42.0350,
    lon: -71.2250,
    aliases: ['great woods', 'xfinity center', 'tweeter center', 'comcast center', 'mansfield ma'],
  },

  // New Jersey
  'pnc-arts': {
    name: 'PNC Bank Arts Center',
    city: 'Holmdel',
    state: 'NJ',
    country: 'US',
    lat: 40.3853,
    lon: -74.1797,
    aliases: ['pnc', 'pnc bank arts center', 'garden state arts center', 'holmdel'],
  },

  // Vermont
  'higher-ground': {
    name: 'Higher Ground',
    city: 'South Burlington',
    state: 'VT',
    country: 'US',
    lat: 44.4584,
    lon: -73.1680,
    aliases: ['higher ground', 'south burlington'],
  },

  // Maryland
  'merriweather': {
    name: 'Merriweather Post Pavilion',
    city: 'Columbia',
    state: 'MD',
    country: 'US',
    lat: 39.2095,
    lon: -76.8617,
    aliases: ['merriweather', 'merriweather post', 'columbia md'],
  },

  // Connecticut
  'hartford-civic': {
    name: 'XL Center',
    city: 'Hartford',
    state: 'CT',
    country: 'US',
    lat: 41.7683,
    lon: -72.6824,
    aliases: ['hartford civic center', 'xl center', 'civic center hartford'],
  },
};

/**
 * Look up venue details by name, matching against known aliases.
 * Returns null if venue is not in the database.
 */
export function getVenueDetails(venueName: string | undefined): Venue | null {
  if (!venueName) return null;

  const normalized = venueName.toLowerCase().trim();

  for (const venue of Object.values(VENUE_DATABASE)) {
    if (venue.aliases.some(alias => normalized.includes(alias))) {
      return venue;
    }
  }

  return null;
}

/**
 * Extract city from a location string like "Ithaca, NY" or "San Francisco, CA, USA"
 */
export function extractCity(location: string | undefined): string {
  if (!location) return '';
  const parts = location.split(',');
  return parts[0]?.trim() || '';
}

/**
 * Extract state/region from a location string like "Ithaca, NY" or "San Francisco, CA, USA"
 */
export function extractState(location: string | undefined): string {
  if (!location) return '';
  const parts = location.split(',');
  // Handle both "City, ST" and "City, ST, USA" formats
  if (parts.length >= 2) {
    const statePart = parts[1]?.trim() || '';
    // If it's a 2-letter state code or short region, return it
    if (statePart.length <= 3) return statePart;
    return statePart;
  }
  return '';
}

/**
 * Extract country from a location string, defaulting to "US" for US states
 */
export function extractCountry(location: string | undefined): string {
  if (!location) return 'US';
  const parts = location.split(',');
  if (parts.length >= 3) {
    const country = parts[2]?.trim() || '';
    if (country.toUpperCase() === 'USA' || country.toUpperCase() === 'US') return 'US';
    return country || 'US';
  }
  // Assume US for locations like "City, ST"
  return 'US';
}
