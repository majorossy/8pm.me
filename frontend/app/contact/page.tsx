import React from 'react';
import Link from 'next/link';

export default function ContactPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] mb-8 tracking-tight">
        Contact
      </h1>

      <div className="space-y-6 text-[#8a8478] leading-relaxed">
        <p className="text-lg">
          Have questions, feedback, or found an issue? We'd love to hear from you!
        </p>

        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-8 my-8">
          <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
            Get in Touch
          </h2>

          <div className="space-y-6">
            <div>
              <h3 className="text-lg font-semibold text-[#d4a060] mb-2">
                Report Issues
              </h3>
              <p className="mb-2">
                Found a bug or technical problem? Please report it through our
                GitHub repository.
              </p>
              <a
                href="https://github.com/yourusername/8pm/issues"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-block text-[#d4a060] hover:text-[#e8a050] underline transition-colors duration-200"
              >
                GitHub Issues →
              </a>
            </div>

            <div className="border-t border-[#3a3632]/30 pt-6">
              <h3 className="text-lg font-semibold text-[#d4a060] mb-2">
                General Inquiries
              </h3>
              <p className="mb-2">
                For general questions, suggestions, or feedback:
              </p>
              <a
                href="mailto:contact@8pm.example.com"
                className="inline-block text-[#d4a060] hover:text-[#e8a050] underline transition-colors duration-200"
              >
                contact@8pm.example.com
              </a>
            </div>

            <div className="border-t border-[#3a3632]/30 pt-6">
              <h3 className="text-lg font-semibold text-[#d4a060] mb-2">
                Missing Shows or Metadata Issues
              </h3>
              <p>
                If you notice missing shows or incorrect metadata, please let us
                know. Include the artist name, show date, and venue in your message.
              </p>
            </div>
          </div>
        </div>

        <div className="border-l-2 border-[#d4a060] pl-6 my-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            About This Project
          </h2>
          <p>
            8PM is a student project demonstrating headless Magento/Mage-OS with
            Next.js and React. It's built with love for the live music community
            and the Internet Archive.
          </p>
        </div>

        <div className="border-l-2 border-[#d4a060] pl-6 my-8">
          <h2 className="text-xl font-semibold text-[#d4a060] mb-3">
            Contributing
          </h2>
          <p>
            Interested in contributing code, design, or ideas? We welcome
            contributions from the community! Check out our GitHub repository
            for open issues and contribution guidelines.
          </p>
        </div>

        <div className="pt-8 border-t border-[#3a3632]/30 mt-8">
          <h2 className="text-2xl font-semibold text-[#d4a060] mb-4">
            Support Archive.org
          </h2>
          <p className="mb-4">
            All recordings on 8PM are hosted by the Internet Archive, a non-profit
            digital library. If you love what they do, consider supporting them:
          </p>
          <a
            href="https://archive.org/donate"
            target="_blank"
            rel="noopener noreferrer"
            className="inline-block px-6 py-3 bg-[#d4a060] text-[#1c1a17] font-semibold rounded hover:bg-[#e8a050] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-offset-2 focus-visible:ring-offset-[#1c1a17]"
          >
            Donate to Archive.org
          </a>
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
