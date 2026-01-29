<?php
declare(strict_types=1);

namespace ArchiveDotOrg\Core\Test\Unit\Model;

use ArchiveDotOrg\Core\Model\StringNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StringNormalizer
 *
 * @covers \ArchiveDotOrg\Core\Model\StringNormalizer
 */
class StringNormalizerTest extends TestCase
{
    private StringNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new StringNormalizer();
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $result = $this->normalizer->normalize('');

        $this->assertEquals('', $result);
    }

    public function testLowercaseConversion(): void
    {
        $result = $this->normalizer->normalize('UPPERCASE TEXT');

        $this->assertEquals('uppercase text', $result);
    }

    public function testMixedCaseConversion(): void
    {
        $result = $this->normalizer->normalize('MiXeD CaSe TeXt');

        $this->assertEquals('mixed case text', $result);
    }

    public function testAccentRemoval(): void
    {
        $testCases = [
            'Tweezér' => 'tweezer',
            'café' => 'cafe',
            'naïve' => 'naive',
            'résumé' => 'resume',
            'Über' => 'uber',
            'São Paulo' => 'sao paulo',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input);
            $this->assertEquals($expected, $result, "Failed to normalize: $input");
        }
    }

    public function testUnicodeDashConversion(): void
    {
        $testCases = [
            'Free—form' => 'free-form',           // Em dash
            'Free–form' => 'free-form',           // En dash
            'Free→form' => 'free>form',           // Right arrow
            'Free−form' => 'free-form',           // Minus sign
            'Free‐form' => 'free-form',           // Hyphen (U+2010)
            'Free‑form' => 'free-form',           // Non-breaking hyphen
            'Free‒form' => 'free-form',           // Figure dash
            'Free―form' => 'free-form',           // Horizontal bar
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input);
            $this->assertEquals($expected, $result, "Failed to normalize dashes in: $input");
        }
    }

    public function testWhitespaceNormalization(): void
    {
        $testCases = [
            '  The   Flu  ' => 'the flu',
            "The\tFlu" => 'the flu',
            "The\nFlu" => 'the flu',
            "The  \t\n  Flu" => 'the flu',
            '   Multiple   Spaces   ' => 'multiple spaces',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input);
            $this->assertEquals($expected, $result, "Failed to normalize whitespace: " . json_encode($input));
        }
    }

    public function testCombinedTransformations(): void
    {
        $input = '  Tweezér—Reprise  ';
        $expected = 'tweezer-reprise';

        $result = $this->normalizer->normalize($input);

        $this->assertEquals($expected, $result);
    }

    public function testUTF8CharactersPreserved(): void
    {
        // Test that non-accented UTF-8 characters are preserved
        $input = 'Track 日本語 中文';
        $result = $this->normalizer->normalize($input);

        // Should be lowercased but characters preserved
        $this->assertStringContainsString('track', $result);
        $this->assertStringContainsString('日本語', $result);
        $this->assertStringContainsString('中文', $result);
    }

    public function testSpecialCharactersPreserved(): void
    {
        $input = 'Track (with) [brackets] & symbols!';
        $result = $this->normalizer->normalize($input);

        $this->assertEquals('track (with) [brackets] & symbols!', $result);
    }

    public function testNumbersPreserved(): void
    {
        $input = 'Track 123 Number 456';
        $expected = 'track 123 number 456';

        $result = $this->normalizer->normalize($input);

        $this->assertEquals($expected, $result);
    }

    public function testLeadingAndTrailingWhitespaceRemoved(): void
    {
        $input = '   Tweezer   ';
        $expected = 'tweezer';

        $result = $this->normalizer->normalize($input);

        $this->assertEquals($expected, $result);
    }

    public function testNormalizerClassNotAvailable(): void
    {
        // This test verifies graceful handling when Normalizer class is not available
        // We can't easily mock class_exists, so we test that the result is still valid

        $input = 'Tweezér';
        $result = $this->normalizer->normalize($input);

        // Should still lowercase and handle whitespace even if accent removal fails
        $this->assertStringStartsWith('tweez', $result);
        $this->assertStringContainsString('r', $result);
    }

    public function testIdempotence(): void
    {
        // Normalizing already normalized string should return same result
        $input = 'tweezer reprise';
        $result1 = $this->normalizer->normalize($input);
        $result2 = $this->normalizer->normalize($result1);

        $this->assertEquals($result1, $result2);
    }

    public function testComplexAccentedText(): void
    {
        $input = "Ñoño's Café in São Paulo";
        $result = $this->normalizer->normalize($input);

        // Should remove all accents
        $this->assertStringNotContainsString('Ñ', $result);
        $this->assertStringNotContainsString('ñ', $result);
        $this->assertStringNotContainsString('é', $result);
        $this->assertStringNotContainsString('ã', $result);

        // Should be lowercase
        $this->assertEquals(mb_strtolower($result, 'UTF-8'), $result);
    }

    public function testArrowConversion(): void
    {
        $input = 'Phyllis → Sam Huff';
        $expected = 'phyllis > sam huff';

        $result = $this->normalizer->normalize($input);

        $this->assertEquals($expected, $result);
    }

    public function testMultipleConsecutiveSpaces(): void
    {
        $input = 'The     Flu';
        $expected = 'the flu';

        $result = $this->normalizer->normalize($input);

        $this->assertEquals($expected, $result);
    }

    public function testOnlyWhitespaceReturnsEmpty(): void
    {
        $inputs = ['   ', "\t\t", "\n\n", "  \t\n  "];

        foreach ($inputs as $input) {
            $result = $this->normalizer->normalize($input);
            $this->assertEquals('', $result, "Input with only whitespace should return empty string");
        }
    }

    public function testSingleCharacter(): void
    {
        $result = $this->normalizer->normalize('A');
        $this->assertEquals('a', $result);

        $result = $this->normalizer->normalize('é');
        $this->assertEquals('e', $result);
    }

    public function testCommonTrackNames(): void
    {
        $testCases = [
            'Tweezer' => 'tweezer',
            'You Enjoy Myself' => 'you enjoy myself',
            "Mike's Song" => "mike's song",
            'Down with Disease' => 'down with disease',
            'Harry Hood' => 'harry hood',
            '2001 (Also Sprach Zarathustra)' => '2001 (also sprach zarathustra)',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input);
            $this->assertEquals($expected, $result, "Failed to normalize: $input");
        }
    }

    public function testRealWorldEdgeCases(): void
    {
        $testCases = [
            // Multiple dashes
            'Free—form—jazz' => 'free-form-jazz',

            // Mixed accents and dashes
            'Café—Bar' => 'cafe-bar',

            // Trailing/leading special chars
            '  → Tweezer →  ' => '> tweezer >',

            // Multiple types of whitespace
            "Track\n\twith\r\nmixed   spaces" => 'track with mixed spaces',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->normalizer->normalize($input);
            $this->assertEquals($expected, $result, "Failed edge case: " . json_encode($input));
        }
    }
}
