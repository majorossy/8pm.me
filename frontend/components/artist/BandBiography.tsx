'use client';

import { useTheme } from '@/context/ThemeContext';
import Image from 'next/image';

interface BandBiographyProps {
  biography?: string;
  wikipediaSummary?: {
    title: string;
    extract: string;
    description: string | null;
    thumbnail: {
      source: string;
      width: number;
      height: number;
    } | null;
    url: string | null;
  } | null;
  extendedBio?: string;
  images?: Array<{
    url: string;
    caption?: string;
    credit?: string;
  }>;
}

export default function BandBiography({
  biography,
  wikipediaSummary,
  extendedBio,
  images,
}: BandBiographyProps) {
  const { theme } = useTheme();

  // Return null if no biography content available
  if (!biography && !wikipediaSummary?.extract && !extendedBio) {
    return null;
  }

  const isTron = theme === 'tron';
  const isMetro = theme === 'metro';
  const isJamify = theme === 'jamify';

  // Split extended bio into paragraphs if it exists
  const extendedBioParagraphs = extendedBio
    ? extendedBio.split('\n\n').filter(p => p.trim().length > 0)
    : [];

  // Combine all images (Wikipedia thumbnail + custom images)
  const allImages = [];
  if (wikipediaSummary?.thumbnail) {
    allImages.push({
      url: wikipediaSummary.thumbnail.source,
      caption: `${wikipediaSummary.title} (Wikipedia)`,
      credit: 'Wikipedia',
    });
  }
  if (images) {
    allImages.push(...images);
  }

  // Jamify/Spotify style
  if (isJamify) {
    return (
      <section className="px-4 md:px-8 pb-8">
        <h2 className="text-xl md:text-2xl font-bold text-white mb-6">Biography</h2>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main biography text */}
          <div className="lg:col-span-2 space-y-4">
            {/* Wikipedia extract */}
            {wikipediaSummary?.extract && (
              <div className="text-[#b3b3b3] text-sm md:text-base leading-relaxed">
                <p>{wikipediaSummary.extract}</p>
                {wikipediaSummary.url && (
                  <p className="mt-2 text-xs">
                    <a
                      href={wikipediaSummary.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-[#1DB954] hover:underline"
                    >
                      Read more on Wikipedia →
                    </a>
                  </p>
                )}
              </div>
            )}

            {/* Extended bio paragraphs */}
            {extendedBioParagraphs.map((paragraph, index) => (
              <p
                key={index}
                className="text-[#b3b3b3] text-sm md:text-base leading-relaxed"
              >
                {paragraph}
              </p>
            ))}

            {/* Short bio fallback */}
            {!wikipediaSummary?.extract && !extendedBio && biography && (
              <p className="text-[#b3b3b3] text-sm md:text-base leading-relaxed">
                {biography}
              </p>
            )}
          </div>

          {/* Images sidebar */}
          {allImages.length > 0 && (
            <div className="space-y-4">
              {allImages.slice(0, 3).map((image, index) => (
                <div key={index} className="bg-[#181818] rounded-lg overflow-hidden">
                  <div className="relative aspect-square">
                    <img
                      src={image.url}
                      alt={image.caption || 'Band photo'}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  {image.caption && (
                    <div className="p-3">
                      <p className="text-xs text-[#b3b3b3]">{image.caption}</p>
                      {image.credit && (
                        <p className="text-[0.6rem] text-[#6a6a6a] mt-1">
                          Credit: {image.credit}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </section>
    );
  }

  // Metro/Time Machine style
  if (isMetro) {
    return (
      <section className="mb-8 md:mb-12">
        <div className="flex justify-between items-center mb-4 md:mb-6 pb-3 md:pb-4 border-b border-[#d4d0c8]">
          <h2 className="font-display text-base md:text-lg font-bold text-[#1a1a1a]">
            Biography
          </h2>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main biography text */}
          <div className="lg:col-span-2 space-y-4">
            {wikipediaSummary?.extract && (
              <div className="text-[#6b6b6b] text-sm md:text-base leading-relaxed">
                <p>{wikipediaSummary.extract}</p>
                {wikipediaSummary.url && (
                  <p className="mt-2 text-xs">
                    <a
                      href={wikipediaSummary.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-[#e85d04] hover:underline"
                    >
                      Read more on Wikipedia →
                    </a>
                  </p>
                )}
              </div>
            )}

            {extendedBioParagraphs.map((paragraph, index) => (
              <p
                key={index}
                className="text-[#6b6b6b] text-sm md:text-base leading-relaxed"
              >
                {paragraph}
              </p>
            ))}

            {!wikipediaSummary?.extract && !extendedBio && biography && (
              <p className="text-[#6b6b6b] text-sm md:text-base leading-relaxed">
                {biography}
              </p>
            )}
          </div>

          {/* Images sidebar */}
          {allImages.length > 0 && (
            <div className="space-y-4">
              {allImages.slice(0, 3).map((image, index) => (
                <div
                  key={index}
                  className="bg-white border border-[#d4d0c8] overflow-hidden"
                >
                  <div className="relative aspect-square">
                    <img
                      src={image.url}
                      alt={image.caption || 'Band photo'}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  {image.caption && (
                    <div className="p-3 bg-[#f5f5f5]">
                      <p className="text-xs text-[#6b6b6b] font-display">
                        {image.caption}
                      </p>
                      {image.credit && (
                        <p className="text-[0.6rem] text-[#999] mt-1">
                          Credit: {image.credit}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </section>
    );
  }

  // Tron/Synthwave style (default)
  return (
    <section className="mb-10 md:mb-16">
      <div className="flex justify-between items-center mb-6 md:mb-8 pb-3 md:pb-4 section-border">
        <h2 className="font-display text-[0.65rem] md:text-xs uppercase tracking-[0.2em] md:tracking-[0.3em] text-text-dim">
          <span className="text-neon-cyan">//</span> Biography
        </h2>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Main biography text */}
        <div className="lg:col-span-2 space-y-4">
          {wikipediaSummary?.extract && (
            <div className="text-text-dim text-sm md:text-base leading-relaxed">
              <p>{wikipediaSummary.extract}</p>
              {wikipediaSummary.url && (
                <p className="mt-3 text-xs">
                  <a
                    href={wikipediaSummary.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-neon-cyan hover:text-neon-orange transition-colors"
                  >
                    Read more on Wikipedia →
                  </a>
                </p>
              )}
            </div>
          )}

          {extendedBioParagraphs.map((paragraph, index) => (
            <p
              key={index}
              className="text-text-dim text-sm md:text-base leading-relaxed"
            >
              {paragraph}
            </p>
          ))}

          {!wikipediaSummary?.extract && !extendedBio && biography && (
            <p className="text-text-dim text-sm md:text-base leading-relaxed">
              {biography}
            </p>
          )}
        </div>

        {/* Images sidebar */}
        {allImages.length > 0 && (
          <div className="space-y-4">
            {allImages.slice(0, 3).map((image, index) => (
              <div key={index} className="album-frame p-[2px]">
                <div className="bg-dark-900 overflow-hidden">
                  <div className="relative aspect-square">
                    <img
                      src={image.url}
                      alt={image.caption || 'Band photo'}
                      className="w-full h-full object-cover"
                    />
                  </div>
                  {image.caption && (
                    <div className="p-3 bg-dark-800">
                      <p className="text-[0.6rem] md:text-xs text-neon-cyan uppercase tracking-wider">
                        {image.caption}
                      </p>
                      {image.credit && (
                        <p className="text-[0.55rem] text-text-dim mt-1">
                          Credit: {image.credit}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}
