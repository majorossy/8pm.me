import React from 'react';
import Link from 'next/link';

export default function AboutPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] mb-8 tracking-tight">
        About 8PM
      </h1>

      <div className="space-y-6 text-[#8a8478] leading-relaxed">
        <p className="text-lg">
          8PM is your gateway to thousands of live concert recordings from Archive.org.
          Explore, listen, and discover legendary performances from jam bands and beyond.
        </p>

        <p>
          Built on the foundation of the Internet Archive's vast collection of legally
          shareable live music, 8PM brings together decades of incredible performances
          from artists like the Grateful Dead, Phish, String Cheese Incident, and many more.
        </p>

        <p>
          Whether you're reliving a show you attended or discovering a performance from
          before you were born, 8PM makes it easy to browse, search, and enjoy these
          cultural treasures.
        </p>

        <div className="pt-8 border-t border-[#3a3632]/30 mt-8">
          <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
            Our Mission
          </h2>
          <p>
            To preserve and share the joy of live music. To honor the taping community
            and the artists who encourage it. To keep the music freely accessible to all.
          </p>
        </div>

        <div className="pt-8 border-t border-[#3a3632]/30 mt-8">
          <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
            Built With
          </h2>
          <ul className="space-y-2 list-disc list-inside">
            <li>Archive.org's vast live music collection</li>
            <li>Next.js and React for a modern web experience</li>
            <li>Magento/Mage-OS for robust backend infrastructure</li>
            <li>GraphQL for efficient data queries</li>
            <li>Love for live music and the taping community</li>
          </ul>
        </div>

        <div className="pt-12 text-center">
          <Link
            href="/artists"
            className="inline-block px-6 py-3 bg-[#d4a060] text-[#1c1a17] font-semibold rounded hover:bg-[#e8a050] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-offset-2 focus-visible:ring-offset-[#1c1a17]"
          >
            Browse Artists
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
