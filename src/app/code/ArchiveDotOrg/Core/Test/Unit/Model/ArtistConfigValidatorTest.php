<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Model\ArtistConfigValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArtistConfigValidator
 *
 * @covers \ArchiveDotOrg\Core\Model\ArtistConfigValidator
 */
class ArtistConfigValidatorTest extends TestCase
{
    private ArtistConfigValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ArtistConfigValidator();
    }

    public function testValidConfigPasses(): void
    {
        $config = [
            'artist' => [
                'name' => 'Lettuce',
                'collection_id' => 'Lettuce',
                'url_key' => 'lettuce'
            ],
            'albums' => [
                [
                    'key' => 'outta-here',
                    'name' => 'Outta Here',
                    'year' => 2002,
                    'type' => 'studio'
                ]
            ],
            'tracks' => [
                [
                    'key' => 'phyllis',
                    'name' => 'Phyllis',
                    'albums' => ['outta-here'],
                    'canonical_album' => 'outta-here',
                    'type' => 'original'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testMissingArtistSectionFails(): void
    {
        $config = [
            'tracks' => []
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('Missing required section: artist', $result['errors']);
    }

    public function testMissingArtistNameFails(): void
    {
        $config = [
            'artist' => [
                'collection_id' => 'Test'
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('artist.name is required', $result['errors']);
    }

    public function testMissingCollectionIdFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test Artist'
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('artist.collection_id is required', $result['errors']);
    }

    public function testInvalidUrlKeyFormatFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test',
                'url_key' => 'Invalid_URL_Key!'  // Uppercase and special chars
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'artist.url_key must contain only lowercase letters, numbers, and hyphens',
            $result['errors']
        );
    }

    public function testValidUrlKeyFormats(): void
    {
        $validKeys = [
            'lowercase',
            'with-hyphens',
            'with123numbers',
            'abc-123-xyz'
        ];

        foreach ($validKeys as $urlKey) {
            $config = [
                'artist' => [
                    'name' => 'Test',
                    'collection_id' => 'Test',
                    'url_key' => $urlKey
                ]
            ];

            $result = $this->validator->validate($config);
            $this->assertTrue($result['valid'], "URL key '$urlKey' should be valid");
        }
    }

    public function testFuzzyEnabledFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'matching' => [
                'fuzzy_enabled' => true
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, array_filter($result['errors'], function ($error) {
            return str_contains($error, 'fuzzy_enabled is true');
        }));
    }

    public function testInvalidFuzzyThresholdFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'matching' => [
                'fuzzy_threshold' => 150  // Invalid: > 100
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('matching.fuzzy_threshold must be between 0 and 100', $result['errors']);
    }

    public function testMissingAlbumKeyFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'albums' => [
                [
                    'name' => 'Album Without Key'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('albums[0].key is required', $result['errors']);
    }

    public function testMissingAlbumNameFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'albums' => [
                [
                    'key' => 'album-key'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('albums[0].name is required', $result['errors']);
    }

    public function testDuplicateAlbumKeysFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'albums' => [
                [
                    'key' => 'same-key',
                    'name' => 'Album 1'
                ],
                [
                    'key' => 'same-key',
                    'name' => 'Album 2'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('Duplicate album key: same-key', $result['errors']);
    }

    public function testInvalidAlbumTypeFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'albums' => [
                [
                    'key' => 'album',
                    'name' => 'Album',
                    'type' => 'invalid-type'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'albums[0].type must be one of: studio, live, compilation, virtual',
            $result['errors']
        );
    }

    public function testMissingTrackKeyFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'name' => 'Track Without Key'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('tracks[0].key is required', $result['errors']);
    }

    public function testMissingTrackNameFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track-key'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('tracks[0].name is required', $result['errors']);
    }

    public function testDuplicateTrackKeysFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'same-key',
                    'name' => 'Track 1'
                ],
                [
                    'key' => 'same-key',
                    'name' => 'Track 2'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('Duplicate track key: same-key', $result['errors']);
    }

    public function testEmptyAliasesArrayFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track',
                    'aliases' => []  // Empty array
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'tracks[0].aliases is empty - remove the aliases field or add values',
            $result['errors']
        );
    }

    public function testInvalidTrackTypeFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track',
                    'type' => 'invalid-type'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'tracks[0].type must be one of: original, cover, jam',
            $result['errors']
        );
    }

    public function testAlbumsFieldMustBeArray(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track',
                    'albums' => 'not-an-array'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains('tracks[0].albums must be an array', $result['errors']);
    }

    public function testMissingCanonicalAlbumFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track',
                    'albums' => ['album-1', 'album-2']
                    // Missing canonical_album
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertContains(
            'tracks[0].canonical_album is required when albums are defined',
            $result['errors']
        );
    }

    public function testCanonicalAlbumNotInAlbumsFails(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track',
                    'albums' => ['album-1', 'album-2'],
                    'canonical_album' => 'album-3'  // Not in albums array
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, array_filter($result['errors'], function ($error) {
            return str_contains($error, 'canonical_album "album-3" must be one of');
        }));
    }

    public function testNoAlbumsGeneratesWarning(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'tracks' => [
                [
                    'key' => 'track',
                    'name' => 'Track'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertTrue($result['valid']);
        $this->assertContains(
            'No albums defined - tracks will not have album context',
            $result['warnings']
        );
    }

    public function testNoTracksGeneratesWarning(): void
    {
        $config = [
            'artist' => [
                'name' => 'Test',
                'collection_id' => 'Test'
            ],
            'albums' => [
                [
                    'key' => 'album',
                    'name' => 'Album'
                ]
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertTrue($result['valid']);
        $this->assertContains(
            'No tracks defined - matching will rely on Archive.org metadata only',
            $result['warnings']
        );
    }

    public function testMultipleErrorsReturned(): void
    {
        $config = [
            'artist' => [
                // Missing name and collection_id
                'url_key' => 'INVALID_KEY'
            ],
            'matching' => [
                'fuzzy_enabled' => true,
                'fuzzy_threshold' => 150
            ]
        ];

        $result = $this->validator->validate($config);

        $this->assertFalse($result['valid']);
        $this->assertGreaterThanOrEqual(4, count($result['errors']));
        $this->assertContains('artist.name is required', $result['errors']);
        $this->assertContains('artist.collection_id is required', $result['errors']);
    }

    public function testValidAlbumTypes(): void
    {
        $validTypes = ['studio', 'live', 'compilation', 'virtual'];

        foreach ($validTypes as $type) {
            $config = [
                'artist' => [
                    'name' => 'Test',
                    'collection_id' => 'Test'
                ],
                'albums' => [
                    [
                        'key' => 'album',
                        'name' => 'Album',
                        'type' => $type
                    ]
                ]
            ];

            $result = $this->validator->validate($config);
            $this->assertTrue($result['valid'], "Album type '$type' should be valid");
        }
    }

    public function testValidTrackTypes(): void
    {
        $validTypes = ['original', 'cover', 'jam'];

        foreach ($validTypes as $type) {
            $config = [
                'artist' => [
                    'name' => 'Test',
                    'collection_id' => 'Test'
                ],
                'tracks' => [
                    [
                        'key' => 'track',
                        'name' => 'Track',
                        'type' => $type
                    ]
                ]
            ];

            $result = $this->validator->validate($config);
            $this->assertTrue($result['valid'], "Track type '$type' should be valid");
        }
    }
}
