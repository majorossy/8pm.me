import {
  sortBySongVersions,
  sortByShows,
  sortByHours,
  sortArtistsByAlgorithm,
  isValidAlgorithm,
  ArtistWithStats,
} from '../festivalSorting';

describe('festivalSorting', () => {
  const mockArtists: ArtistWithStats[] = [
    { slug: 'phish', name: 'Phish', songCount: 1000, albumCount: 50, totalRecordings: 1500, totalShows: 200, totalHours: 500 },
    { slug: 'dead', name: 'Grateful Dead', songCount: 800, albumCount: 100, totalRecordings: 2000, totalShows: 300, totalHours: 800 },
    { slug: 'sts9', name: 'STS9', songCount: 500, albumCount: 30, totalRecordings: 700, totalShows: 150, totalHours: 350 },
    { slug: 'panic', name: 'Widespread Panic', songCount: 600, albumCount: 40, totalShows: 250, totalHours: 600 }, // No totalRecordings
  ];

  describe('sortBySongVersions', () => {
    it('should sort by pure song count descending', () => {
      const sorted = sortBySongVersions(mockArtists);
      expect(sorted[0].slug).toBe('phish'); // 1000
      expect(sorted[1].slug).toBe('dead');  // 800
      expect(sorted[2].slug).toBe('panic'); // 600
      expect(sorted[3].slug).toBe('sts9');  // 500
    });

    it('should return empty array for empty input', () => {
      expect(sortBySongVersions([])).toEqual([]);
    });

    it('should not mutate original array', () => {
      const original = [...mockArtists];
      sortBySongVersions(mockArtists);
      expect(mockArtists).toEqual(original);
    });
  });

  describe('sortByShows', () => {
    it('should sort by totalShows descending', () => {
      const sorted = sortByShows(mockArtists);
      expect(sorted[0].slug).toBe('dead');  // 300 shows
      expect(sorted[1].slug).toBe('panic'); // 250 shows
      expect(sorted[2].slug).toBe('phish'); // 200 shows
      expect(sorted[3].slug).toBe('sts9');  // 150 shows
    });

    it('should handle missing totalShows gracefully', () => {
      const artistsWithMissing: ArtistWithStats[] = [
        { slug: 'a', name: 'A', songCount: 100, albumCount: 10, totalShows: 50 },
        { slug: 'b', name: 'B', songCount: 100, albumCount: 10 }, // No totalShows
        { slug: 'c', name: 'C', songCount: 100, albumCount: 10, totalShows: 100 },
      ];
      const sorted = sortByShows(artistsWithMissing);
      expect(sorted[0].slug).toBe('c'); // 100
      expect(sorted[1].slug).toBe('a'); // 50
      expect(sorted[2].slug).toBe('b'); // 0 (fallback)
    });

    it('should return empty array for empty input', () => {
      expect(sortByShows([])).toEqual([]);
    });

    it('should not mutate original array', () => {
      const original = [...mockArtists];
      sortByShows(mockArtists);
      expect(mockArtists).toEqual(original);
    });
  });

  describe('sortByHours', () => {
    it('should sort by totalHours descending', () => {
      const sorted = sortByHours(mockArtists);
      expect(sorted[0].slug).toBe('dead');  // 800 hours
      expect(sorted[1].slug).toBe('panic'); // 600 hours
      expect(sorted[2].slug).toBe('phish'); // 500 hours
      expect(sorted[3].slug).toBe('sts9');  // 350 hours
    });

    it('should handle missing totalHours gracefully', () => {
      const artistsWithMissing: ArtistWithStats[] = [
        { slug: 'a', name: 'A', songCount: 100, albumCount: 10, totalHours: 50 },
        { slug: 'b', name: 'B', songCount: 100, albumCount: 10 }, // No totalHours
        { slug: 'c', name: 'C', songCount: 100, albumCount: 10, totalHours: 100 },
      ];
      const sorted = sortByHours(artistsWithMissing);
      expect(sorted[0].slug).toBe('c'); // 100
      expect(sorted[1].slug).toBe('a'); // 50
      expect(sorted[2].slug).toBe('b'); // 0 (fallback)
    });

    it('should return empty array for empty input', () => {
      expect(sortByHours([])).toEqual([]);
    });

    it('should not mutate original array', () => {
      const original = [...mockArtists];
      sortByHours(mockArtists);
      expect(mockArtists).toEqual(original);
    });
  });

  describe('sortArtistsByAlgorithm', () => {
    it('should call sortBySongVersions for "songVersions" algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'songVersions');
      const expected = sortBySongVersions(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });

    it('should call sortByShows for "shows" algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'shows');
      const expected = sortByShows(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });

    it('should call sortByHours for "hours" algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'hours');
      const expected = sortByHours(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });

    it('should fall back to songVersions for invalid algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'invalid' as any);
      const expected = sortBySongVersions(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });
  });

  describe('isValidAlgorithm', () => {
    it('should return true for valid algorithms', () => {
      expect(isValidAlgorithm('songVersions')).toBe(true);
      expect(isValidAlgorithm('shows')).toBe(true);
      expect(isValidAlgorithm('hours')).toBe(true);
    });

    it('should return false for invalid algorithms', () => {
      expect(isValidAlgorithm('invalid')).toBe(false);
      expect(isValidAlgorithm('')).toBe(false);
      expect(isValidAlgorithm('balanced')).toBe(false); // Old algorithm
      expect(isValidAlgorithm('songs')).toBe(false); // Old algorithm
      expect(isValidAlgorithm('catalog')).toBe(false); // Old algorithm
    });
  });

  describe('edge cases', () => {
    it('should maintain stable order when all values are the same', () => {
      const sameArtists: ArtistWithStats[] = [
        { slug: 'a', name: 'A', songCount: 100, albumCount: 10, totalShows: 50, totalHours: 100 },
        { slug: 'b', name: 'B', songCount: 100, albumCount: 10, totalShows: 50, totalHours: 100 },
        { slug: 'c', name: 'C', songCount: 100, albumCount: 10, totalShows: 50, totalHours: 100 },
      ];

      // When all values are the same, order should be stable
      const sortedBySongs = sortBySongVersions(sameArtists);
      const sortedByShows = sortByShows(sameArtists);
      const sortedByHours = sortByHours(sameArtists);

      // All should have same length
      expect(sortedBySongs.length).toBe(3);
      expect(sortedByShows.length).toBe(3);
      expect(sortedByHours.length).toBe(3);
    });
  });
});
