import React from 'react';
import Link from 'next/link';

export default function HowItWorksPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] mb-8 tracking-tight">
        How It Works
      </h1>

      <div className="space-y-8 text-[#8a8478] leading-relaxed">
        <p className="text-lg">
          8PM makes it easy to discover and enjoy thousands of live concert recordings
          from Archive.org. Here's how to get started:
        </p>

        <div className="space-y-6">
          <div className="border-l-2 border-[#d4a060] pl-6">
            <h2 className="text-xl font-semibold text-[#d4a060] mb-2">
              1. Browse Artists
            </h2>
            <p>
              Start by exploring our collection of jam bands and live music artists.
              Each artist page shows their available recordings, organized by date
              and venue.
            </p>
          </div>

          <div className="border-l-2 border-[#d4a060] pl-6">
            <h2 className="text-xl font-semibold text-[#d4a060] mb-2">
              2. Search Shows
            </h2>
            <p>
              Looking for something specific? Use the search feature to find shows
              by artist, date, venue, or even specific songs. Our advanced search
              helps you narrow down exactly what you're looking for.
            </p>
          </div>

          <div className="border-l-2 border-[#d4a060] pl-6">
            <h2 className="text-xl font-semibold text-[#d4a060] mb-2">
              3. Create Playlists
            </h2>
            <p>
              Found a great show or track? Add it to your library or create custom
              playlists. Your playlists are saved locally in your browser, so they're
              always available when you return.
            </p>
          </div>

          <div className="border-l-2 border-[#d4a060] pl-6">
            <h2 className="text-xl font-semibold text-[#d4a060] mb-2">
              4. Listen & Enjoy
            </h2>
            <p>
              Stream recordings directly from Archive.org. Use keyboard shortcuts
              for quick controls, enable crossfade for seamless transitions, or
              set a sleep timer for late-night listening.
            </p>
          </div>

          <div className="border-l-2 border-[#d4a060] pl-6">
            <h2 className="text-xl font-semibold text-[#d4a060] mb-2">
              5. Share the Love
            </h2>
            <p>
              All recordings on 8PM are freely shareable. Send links to friends,
              share on social media, or download tracks for offline listening.
              Remember: please copy freely — never sell.
            </p>
          </div>
        </div>

        <div className="pt-8 border-t border-[#3a3632]/30 mt-8">
          <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
            Tips & Tricks
          </h2>
          <ul className="space-y-2 list-disc list-inside">
            <li>Press <kbd className="px-2 py-1 bg-[#2a2825] rounded text-sm">Space</kbd> to play/pause</li>
            <li>Use <kbd className="px-2 py-1 bg-[#2a2825] rounded text-sm">N</kbd> and <kbd className="px-2 py-1 bg-[#2a2825] rounded text-sm">P</kbd> for next/previous track</li>
            <li>Like songs to quickly find them later in your library</li>
            <li>Enable crossfade for smooth transitions between tracks</li>
            <li>Check recently played to pick up where you left off</li>
          </ul>
        </div>

        <div className="pt-12 text-center">
          <Link
            href="/faq"
            className="inline-block px-6 py-3 bg-[#d4a060] text-[#1c1a17] font-semibold rounded hover:bg-[#e8a050] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-offset-2 focus-visible:ring-offset-[#1c1a17]"
          >
            Read the FAQ
          </Link>
        </div>

        <div className="pt-8 text-center">
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
