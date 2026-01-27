'use client';

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
  // Return null if no biography content available
  if (!biography && !wikipediaSummary?.extract && !extendedBio) {
    return null;
  }

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
