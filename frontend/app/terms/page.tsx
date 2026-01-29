import React from 'react';
import Link from 'next/link';

export default function TermsPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] mb-8 tracking-tight">
        Terms of Service
      </h1>

      <div className="space-y-6 text-[#8a8478] leading-relaxed">
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 mb-8">
          <p className="text-sm text-[#d4a060] font-semibold">
            Note: This is a student project placeholder. 8PM is an educational
            demonstration and not a commercial service.
          </p>
        </div>

        <p className="text-lg">
          By using 8PM, you agree to these terms. Please read them carefully.
        </p>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Acceptance of Terms
          </h2>
          <p>
            By accessing and using 8PM, you accept and agree to be bound by these
            Terms of Service. If you do not agree to these terms, please do not use
            this service.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Use of Service
          </h2>
          <p>
            8PM provides access to live concert recordings hosted on Archive.org.
            You may:
          </p>
          <ul className="space-y-2 list-disc list-inside mt-2">
            <li>Stream and listen to recordings for personal, non-commercial use</li>
            <li>Create and share playlists</li>
            <li>Share links to shows and tracks with others</li>
            <li>Download recordings directly from Archive.org</li>
          </ul>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Our Ethos: Please Copy Freely — Never Sell
          </h2>
          <p>
            All recordings on 8PM are freely shareable. You may copy, distribute,
            and share these recordings with others. However, you may not:
          </p>
          <ul className="space-y-2 list-disc list-inside mt-2">
            <li>Sell or commercially exploit any recordings</li>
            <li>Claim ownership of recordings you did not create</li>
            <li>Remove or alter credits to tapers or Archive.org</li>
            <li>Use recordings in ways that violate artist permissions</li>
          </ul>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Content Rights
          </h2>
          <p>
            All recordings are hosted on Archive.org and subject to their terms of
            service. Artists featured on 8PM have granted permission for taping and
            non-commercial sharing of their live performances. This permission does
            not extend to studio recordings or commercial use.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            User Conduct
          </h2>
          <p>
            You agree not to:
          </p>
          <ul className="space-y-2 list-disc list-inside mt-2">
            <li>Interfere with or disrupt the service</li>
            <li>Attempt to gain unauthorized access to any systems</li>
            <li>Use automated tools to scrape or download content in bulk</li>
            <li>Impersonate others or misrepresent your affiliation</li>
            <li>Violate any applicable laws or regulations</li>
          </ul>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Disclaimer of Warranties
          </h2>
          <p>
            8PM is provided "as is" without warranties of any kind. As a student
            project, we make no guarantees about service availability, accuracy of
            metadata, or suitability for any particular purpose.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Limitation of Liability
          </h2>
          <p>
            8PM and its creators shall not be liable for any damages arising from
            your use of the service. This includes data loss, service interruptions,
            or any other issues that may arise.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Changes to Terms
          </h2>
          <p>
            We reserve the right to modify these terms at any time. Continued use
            of 8PM after changes constitutes acceptance of the new terms.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Contact
          </h2>
          <p>
            If you have questions about these terms, please contact us through our
            contact page.
          </p>
        </div>

        <div className="pt-8 text-sm text-[#6a6458] italic">
          Last updated: January 29, 2026
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
