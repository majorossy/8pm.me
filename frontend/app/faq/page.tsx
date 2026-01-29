import React from 'react';
import Link from 'next/link';

export default function FAQPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] mb-8 tracking-tight">
        Frequently Asked Questions
      </h1>

      <div className="space-y-8 text-[#8a8478] leading-relaxed">

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Is 8PM really free?
          </h2>
          <p>
            Yes! 8PM is completely free to use. All recordings come from Archive.org,
            which hosts legally shareable live music. There are no subscriptions,
            no ads, and no hidden fees.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Where do the recordings come from?
          </h2>
          <p>
            All recordings are hosted on Archive.org, a non-profit digital library
            dedicated to preserving cultural artifacts. The live music collection
            includes thousands of shows recorded by fans (tapers) and shared with
            permission from the artists.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Can I download shows for offline listening?
          </h2>
          <p>
            While 8PM is designed for streaming, you can visit Archive.org directly
            to download complete shows in various formats (MP3, FLAC, etc.). Each
            show page includes a link to the original Archive.org recording.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Are these recordings legal?
          </h2>
          <p>
            Yes! All artists featured on 8PM allow or encourage taping and sharing
            of their live performances. This is a long-standing tradition in the
            jam band community that helps spread the music and build fan communities.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Do I need to create an account?
          </h2>
          <p>
            No account is required for basic browsing and listening. However, creating
            an account lets you save playlists, track your listening history, and sync
            your library across devices.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Can I share shows with friends?
          </h2>
          <p>
            Absolutely! Every show and playlist has a shareable link. You can also
            share directly to social media. Remember our ethos: please copy freely
            — never sell.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            How can I contribute or report issues?
          </h2>
          <p>
            We welcome feedback! If you notice missing shows, incorrect metadata,
            or technical issues, please contact us. This is a student project and
            we're always looking to improve.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Why is it called "8PM"?
          </h2>
          <p>
            8PM represents the magic hour when most concerts begin — that moment of
            anticipation before the lights dim and the music starts. It's our tribute
            to the live music experience.
          </p>
        </div>

        <div className="pt-8 border-t border-[#3a3632]/30 mt-8">
          <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
            Still have questions?
          </h2>
          <p>
            Feel free to reach out through our contact page. We're here to help!
          </p>
        </div>

        <div className="pt-12 text-center">
          <Link
            href="/contact"
            className="inline-block px-6 py-3 bg-[#d4a060] text-[#1c1a17] font-semibold rounded hover:bg-[#e8a050] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-offset-2 focus-visible:ring-offset-[#1c1a17]"
          >
            Contact Us
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
