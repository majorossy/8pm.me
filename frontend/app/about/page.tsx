import React from 'react';
import Link from 'next/link';
import {
  InfoIcon,
  HeartIcon,
  CodeIcon,
  MusicNoteIcon,
  GlobeIcon,
  ClockIcon,
  ArchiveIcon,
} from '@/components/icons/FooterIcons';

export default function AboutPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      {/* Header with icon */}
      <div className="flex items-center gap-4 mb-8">
        <div className="p-3 bg-[#2a2825] rounded-lg border border-[#3a3632]">
          <InfoIcon className="w-8 h-8 text-[#d4a060]" />
        </div>
        <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] tracking-tight">
          About 8PM
        </h1>
      </div>

      <div className="space-y-8 text-[#8a8478] leading-relaxed">
        {/* Hero description */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 md:p-8">
          <div className="flex items-start gap-4">
            <MusicNoteIcon className="w-8 h-8 text-[#d4a060] flex-shrink-0 mt-1" />
            <div>
              <p className="text-lg mb-4">
                8PM is your gateway to thousands of live concert recordings from Archive.org.
                Explore, listen, and discover legendary performances from jam bands and beyond.
              </p>
              <p className="text-[#8a8478]">
                Built on the foundation of the Internet Archive's vast collection of legally
                shareable live music, 8PM brings together decades of incredible performances
                from artists like the Grateful Dead, Phish, String Cheese Incident, and many more.
              </p>
            </div>
          </div>
        </div>

        {/* Why 8PM card */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6">
          <div className="flex items-center gap-3 mb-4">
            <ClockIcon className="w-6 h-6 text-[#d4a060]" />
            <h2 className="text-xl font-semibold text-[#d4a060]">
              Why "8PM"?
            </h2>
          </div>
          <p>
            8PM represents the magic hour when most concerts begin — that moment of
            anticipation before the lights dim and the music starts. It's our tribute
            to the live music experience and the community that keeps it alive.
          </p>
        </div>

        {/* Mission Section */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 md:p-8">
          <div className="flex items-center gap-3 mb-4">
            <HeartIcon className="w-6 h-6 text-[#d4a060]" />
            <h2 className="text-2xl font-semibold text-[#d4a060]">
              Our Mission
            </h2>
          </div>
          <ul className="space-y-3">
            <li className="flex items-start gap-3">
              <span className="w-2 h-2 rounded-full bg-[#d4a060] mt-2 flex-shrink-0" />
              <span>To preserve and share the joy of live music</span>
            </li>
            <li className="flex items-start gap-3">
              <span className="w-2 h-2 rounded-full bg-[#d4a060] mt-2 flex-shrink-0" />
              <span>To honor the taping community and the artists who encourage it</span>
            </li>
            <li className="flex items-start gap-3">
              <span className="w-2 h-2 rounded-full bg-[#d4a060] mt-2 flex-shrink-0" />
              <span>To keep the music freely accessible to all</span>
            </li>
          </ul>
        </div>

        {/* Built With Section */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 md:p-8">
          <div className="flex items-center gap-3 mb-4">
            <CodeIcon className="w-6 h-6 text-[#d4a060]" />
            <h2 className="text-2xl font-semibold text-[#d4a060]">
              Built With
            </h2>
          </div>
          <div className="grid sm:grid-cols-2 gap-4">
            <div className="flex items-center gap-3 p-3 bg-[#1c1a17] rounded-lg">
              <ArchiveIcon className="w-5 h-5 text-[#d4a060]" />
              <span>Archive.org's live music collection</span>
            </div>
            <div className="flex items-center gap-3 p-3 bg-[#1c1a17] rounded-lg">
              <GlobeIcon className="w-5 h-5 text-[#d4a060]" />
              <span>Next.js and React</span>
            </div>
            <div className="flex items-center gap-3 p-3 bg-[#1c1a17] rounded-lg">
              <CodeIcon className="w-5 h-5 text-[#d4a060]" />
              <span>Magento/Mage-OS backend</span>
            </div>
            <div className="flex items-center gap-3 p-3 bg-[#1c1a17] rounded-lg">
              <HeartIcon className="w-5 h-5 text-[#d4a060]" />
              <span>Love for live music</span>
            </div>
          </div>
        </div>

        {/* Experience Card */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6">
          <p className="text-center text-lg">
            Whether you're reliving a show you attended or discovering a performance from
            before you were born, 8PM makes it easy to browse, search, and enjoy these
            cultural treasures.
          </p>
        </div>

        {/* CTA */}
        <div className="text-center pt-4">
          <Link
            href="/artists"
            className="inline-flex items-center gap-2 px-6 py-3 bg-[#d4a060] text-[#1c1a17] font-semibold rounded hover:bg-[#e8a050] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-offset-2 focus-visible:ring-offset-[#1c1a17]"
          >
            <MusicNoteIcon className="w-5 h-5" />
            Browse Artists
          </Link>
        </div>

        <div className="pt-4 text-center">
          <Link
            href="/"
            className="text-sm text-[#8a8478] hover:text-[#d4a060] transition-colors duration-200"
          >
            ← Back to Home
          </Link>
        </div>
      </div>
    </div>
  );
}
