'use client';

import Link from 'next/link';

interface ExpandedBiographyProps {
  artistName: string;
  extendedBio?: string;
  wikipediaExtract?: string;
  wikipediaUrl?: string | null;
  totalShows?: number;
  yearsActive?: string;
  genres?: string[];
  originLocation?: string;
  formationDate?: string;
}

/**
 * ExpandedBiography - SEO-optimized biography section
 *
 * Purpose: Expand artist bios to 300+ words for better SEO
 * SEO Benefits:
 * - Longer content increases dwell time
 * - Keyword-rich supplementary content
 * - Natural keyword placement for long-tail searches
 */
export default function ExpandedBiography({
  artistName,
  extendedBio,
  wikipediaExtract,
  wikipediaUrl,
  totalShows,
  yearsActive,
  genres,
  originLocation,
  formationDate,
}: ExpandedBiographyProps) {
  // Calculate if bio is short (less than 300 words)
  const bioText = extendedBio || wikipediaExtract || '';
  const wordCount = bioText.split(/\s+/).filter(Boolean).length;
  const needsExpansion = wordCount < 300;

  // Generate supplementary SEO content
  const generateSupplementaryContent = () => {
    const parts: string[] = [];

    // Archive.org collection info
    if (totalShows && totalShows > 0) {
      parts.push(
        `Explore ${totalShows.toLocaleString()} live ${artistName} recordings in the EIGHTPM archive. ` +
        `Each concert captures the unique energy and improvisational spirit that makes ${artistName} performances legendary.`
      );
    }

    // Years active and formation context
    if (yearsActive || formationDate) {
      const yearInfo = yearsActive ? `spanning ${yearsActive}` : (formationDate ? `since ${formationDate}` : '');
      if (yearInfo) {
        parts.push(
          `With a career ${yearInfo}, ${artistName} has developed a devoted following among fans of live music and ` +
          `improvisational performances. The band's evolution over the years is documented through these carefully preserved recordings.`
        );
      }
    }

    // Genre context for keyword targeting
    if (genres && genres.length > 0) {
      const genreList = genres.slice(0, 4).join(', ');
      parts.push(
        `${artistName}'s sound draws from ${genreList}, blending these influences into extended improvisational jams ` +
        `that make each live performance a unique experience.`
      );
    }

    // Origin location for local SEO
    if (originLocation) {
      parts.push(
        `Hailing from ${originLocation}, ${artistName} has become a cornerstone of the live music community, ` +
        `known for performances that reward repeated listening.`
      );
    }

    // Call to action
    parts.push(
      `Stream ${artistName} concerts for free on EIGHTPM, featuring high-quality recordings from Archive.org. ` +
      `No subscription required - dive into the complete archive of live performances and discover why fans return to these recordings again and again.`
    );

    return parts;
  };

  const supplementaryParagraphs = needsExpansion ? generateSupplementaryContent() : [];

  return (
    <div className="space-y-4">
      {/* Wikipedia extract - primary bio */}
      {wikipediaExtract && (
        <div className="text-[#8a8478] text-sm md:text-base leading-relaxed">
          <p>{wikipediaExtract}</p>
          {wikipediaUrl && (
            <p className="mt-2 text-xs">
              <a
                href={wikipediaUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="text-[#d4a060] hover:underline"
              >
                Read more on Wikipedia
              </a>
            </p>
          )}
        </div>
      )}

      {/* Extended bio paragraphs */}
      {extendedBio &&
        extendedBio.split('\n\n').map((paragraph, index) => (
          <p
            key={index}
            className="text-[#8a8478] text-sm md:text-base leading-relaxed"
          >
            {paragraph}
          </p>
        ))}

      {/* Supplementary SEO content - only shown if bio is short */}
      {needsExpansion && supplementaryParagraphs.length > 0 && (
        <div className="mt-6 pt-4 border-t border-[#3a3632]/30">
          <h3 className="text-sm font-semibold text-[#a8a098] mb-3">
            About {artistName} on EIGHTPM
          </h3>
          {supplementaryParagraphs.map((paragraph, index) => (
            <p
              key={index}
              className="text-[#8a8478] text-sm leading-relaxed mb-3 last:mb-0"
            >
              {paragraph}
            </p>
          ))}
        </div>
      )}

      {/* Browse all shows CTA */}
      {totalShows && totalShows > 0 && (
        <div className="mt-4 pt-4 border-t border-[#3a3632]/30">
          <p className="text-sm text-[#6a6458]">
            Browse all {totalShows.toLocaleString()} {artistName} live recordings in the archive above.
          </p>
        </div>
      )}
    </div>
  );
}
