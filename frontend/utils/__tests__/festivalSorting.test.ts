import {
  sortBalanced,
  sortBySongs,
  sortByCatalog,
  sortArtistsByAlgorithm,
  isValidAlgorithm,
  ArtistWithStats,
} from '../festivalSorting';

describe('festivalSorting', () => {
  const mockArtists: ArtistWithStats[] = [
    { slug: 'phish', name: 'Phish', songCount: 1000, albumCount: 50, totalRecordings: 1500 },
    { slug: 'dead', name: 'Grateful Dead', songCount: 800, albumCount: 100, totalRecordings: 2000 },
    { slug: 'sts9', name: 'STS9', songCount: 500, albumCount: 30, totalRecordings: 700 },
    { slug: 'panic', name: 'Widespread Panic', songCount: 600, albumCount: 40 }, // No totalRecordings
  ];

  describe('sortBalanced', () => {
    it('should sort by weighted combination of songs (75%) and albums (25%)', () => {
      const sorted = sortBalanced(mockArtists);
      // Phish: High songs (1000), moderate albums (50)
      // Dead: Moderate songs (800), high albums (100) - should boost this one
      // Expected order: Phish, Dead, Panic, STS9
      expect(sorted[0].slug).toBe('phish');
      expect(sorted[1].slug).toBe('dead');
    });

    it('should return empty array for empty input', () => {
      expect(sortBalanced([])).toEqual([]);
    });

    it('should return same array for single artist', () => {
      const single = [mockArtists[0]];
      const sorted = sortBalanced(single);
      expect(sorted).toEqual(single);
      expect(sorted).not.toBe(single); // Should be new array
    });

    it('should not mutate original array', () => {
      const original = [...mockArtists];
      const sorted = sortBalanced(mockArtists);
      expect(mockArtists).toEqual(original);
      expect(sorted).not.toBe(mockArtists);
    });
  });

  describe('sortBySongs', () => {
    it('should sort by pure song count descending', () => {
      const sorted = sortBySongs(mockArtists);
      expect(sorted[0].slug).toBe('phish'); // 1000
      expect(sorted[1].slug).toBe('dead');  // 800
      expect(sorted[2].slug).toBe('panic'); // 600
      expect(sorted[3].slug).toBe('sts9');  // 500
    });

    it('should return empty array for empty input', () => {
      expect(sortBySongs([])).toEqual([]);
    });

    it('should not mutate original array', () => {
      const original = [...mockArtists];
      sortBySongs(mockArtists);
      expect(mockArtists).toEqual(original);
    });
  });

  describe('sortByCatalog', () => {
    it('should use totalRecordings when available', () => {
      const sorted = sortByCatalog(mockArtists);
      expect(sorted[0].slug).toBe('dead');  // 2000
      expect(sorted[1].slug).toBe('phish'); // 1500
      expect(sorted[2].slug).toBe('sts9');  // 700
    });

    it('should fall back to songCount when totalRecordings missing', () => {
      const sorted = sortByCatalog(mockArtists);
      // Panic has no totalRecordings, should use songCount (600)
      expect(sorted[3].slug).toBe('panic'); // 600 (fallback)
    });

    it('should return empty array for empty input', () => {
      expect(sortByCatalog([])).toEqual([]);
    });

    it('should not mutate original array', () => {
      const original = [...mockArtists];
      sortByCatalog(mockArtists);
      expect(mockArtists).toEqual(original);
    });
  });

  describe('sortArtistsByAlgorithm', () => {
    it('should call sortBalanced for "balanced" algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'balanced');
      const expected = sortBalanced(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });

    it('should call sortBySongs for "songs" algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'songs');
      const expected = sortBySongs(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });

    it('should call sortByCatalog for "catalog" algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'catalog');
      const expected = sortByCatalog(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });

    it('should fall back to balanced for invalid algorithm', () => {
      const sorted = sortArtistsByAlgorithm(mockArtists, 'invalid' as any);
      const expected = sortBalanced(mockArtists);
      expect(sorted.map(a => a.slug)).toEqual(expected.map(a => a.slug));
    });
  });

  describe('isValidAlgorithm', () => {
    it('should return true for valid algorithms', () => {
      expect(isValidAlgorithm('balanced')).toBe(true);
      expect(isValidAlgorithm('songs')).toBe(true);
      expect(isValidAlgorithm('catalog')).toBe(true);
    });

    it('should return false for invalid algorithms', () => {
      expect(isValidAlgorithm('invalid')).toBe(false);
      expect(isValidAlgorithm('')).toBe(false);
      expect(isValidAlgorithm('BALANCED')).toBe(false);
    });
  });

  describe('normalization edge cases', () => {
    it('should handle all artists with same songCount', () => {
      const sameArtists = [
        { slug: 'a', name: 'A', songCount: 100, albumCount: 10 },
        { slug: 'b', name: 'B', songCount: 100, albumCount: 20 },
        { slug: 'c', name: 'C', songCount: 100, albumCount: 30 },
      ];
      const sorted = sortBalanced(sameArtists);
      // When songCount is same, albumCount should dominate
      expect(sorted[0].slug).toBe('c'); // Highest albumCount
      expect(sorted[2].slug).toBe('a'); // Lowest albumCount
    });

    it('should handle all artists with same albumCount', () => {
      const sameArtists = [
        { slug: 'a', name: 'A', songCount: 100, albumCount: 10 },
        { slug: 'b', name: 'B', songCount: 200, albumCount: 10 },
        { slug: 'c', name: 'C', songCount: 300, albumCount: 10 },
      ];
      const sorted = sortBalanced(sameArtists);
      // When albumCount is same, songCount should dominate
      expect(sorted[0].slug).toBe('c'); // Highest songCount
      expect(sorted[2].slug).toBe('a'); // Lowest songCount
    });
  });
});
