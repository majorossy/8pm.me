import React from 'react';
import Link from 'next/link';

export default function PrivacyPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] mb-8 tracking-tight">
        Privacy Policy
      </h1>

      <div className="space-y-6 text-[#8a8478] leading-relaxed">
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 mb-8">
          <p className="text-sm text-[#d4a060] font-semibold">
            Note: This is a student project placeholder. 8PM is an educational
            demonstration and not a commercial service.
          </p>
        </div>

        <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
          Our Commitment to Privacy
        </h2>
        <p>
          8PM is designed with privacy in mind. We believe your listening habits
          and personal data should remain yours.
        </p>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            What We Collect
          </h2>
          <ul className="space-y-2 list-disc list-inside">
            <li>Basic account information (email, username) if you create an account</li>
            <li>Playlists and listening preferences (stored locally in your browser)</li>
            <li>Anonymous usage statistics to improve the service</li>
          </ul>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            What We Don't Collect
          </h2>
          <ul className="space-y-2 list-disc list-inside">
            <li>We don't sell your data to third parties</li>
            <li>We don't track you across other websites</li>
            <li>We don't collect personally identifiable information without your consent</li>
            <li>We don't use invasive analytics or advertising trackers</li>
          </ul>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Local Storage
          </h2>
          <p>
            Your playlists, recently played tracks, and app preferences are stored
            locally in your browser using localStorage. This data never leaves your
            device unless you explicitly choose to sync it via an optional account.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Third-Party Services
          </h2>
          <p>
            8PM streams music directly from Archive.org. When you play a recording,
            your browser connects directly to Archive.org's servers. Please refer to
            Archive.org's privacy policy for information about their data practices.
          </p>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Your Rights
          </h2>
          <p>
            You have the right to:
          </p>
          <ul className="space-y-2 list-disc list-inside mt-2">
            <li>Access any data we have about you</li>
            <li>Request deletion of your account and associated data</li>
            <li>Opt out of optional data collection</li>
            <li>Export your playlists and listening history</li>
          </ul>
        </div>

        <div className="border-b border-[#3a3632]/30 pb-6 mt-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Changes to This Policy
          </h2>
          <p>
            We may update this privacy policy from time to time. We will notify you
            of any significant changes by posting a notice on the site.
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
