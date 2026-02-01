'use client';

import React from 'react';
import Link from 'next/link';
import { ContactForm } from '@/components/ContactForm';
import {
  MailIcon,
  ExternalLinkIcon,
  HeartIcon,
  CodeIcon,
  BugIcon,
} from '@/components/icons/FooterIcons';

export default function ContactPage() {
  return (
    <div className="max-w-[800px] mx-auto px-4 py-12 md:py-16">
      {/* Header with icon */}
      <div className="flex items-center gap-4 mb-8">
        <div className="p-3 bg-[#2a2825] rounded-lg border border-[#3a3632]">
          <MailIcon className="w-8 h-8 text-[#d4a060]" />
        </div>
        <h1 className="text-4xl md:text-5xl font-bold text-[#d4a060] tracking-tight">
          Contact
        </h1>
      </div>

      <p className="text-[#8a8478] text-lg mb-8">
        Have questions, feedback, or found an issue? We'd love to hear from you!
      </p>

      {/* Contact Form */}
      <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 md:p-8 mb-8">
        <div className="flex items-center gap-3 mb-6">
          <MailIcon className="w-6 h-6 text-[#d4a060]" />
          <h2 className="text-2xl font-semibold text-[#d4a060]">
            Send Us a Message
          </h2>
        </div>
        <ContactForm />
      </div>

      {/* Alternative Contact Methods */}
      <div className="grid md:grid-cols-2 gap-6 mb-8">
        {/* Report Issues */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6">
          <div className="flex items-center gap-3 mb-4">
            <BugIcon className="w-6 h-6 text-[#d4a060]" />
            <h3 className="text-lg font-semibold text-[#d4a060]">
              Report Issues
            </h3>
          </div>
          <p className="text-[#8a8478] mb-4">
            Found a bug or technical problem? Report it on GitHub for faster resolution.
          </p>
          <a
            href="https://github.com/yourusername/8pm/issues"
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 text-[#d4a060] hover:text-[#e8a050] transition-colors duration-200"
          >
            Open GitHub Issues
            <ExternalLinkIcon className="w-4 h-4" />
          </a>
        </div>

        {/* About This Project */}
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6">
          <div className="flex items-center gap-3 mb-4">
            <CodeIcon className="w-6 h-6 text-[#d4a060]" />
            <h3 className="text-lg font-semibold text-[#d4a060]">
              About This Project
            </h3>
          </div>
          <p className="text-[#8a8478] mb-4">
            8pm.me is a student project demonstrating headless Magento/Mage-OS with
            Next.js and React.
          </p>
          <Link
            href="/about"
            className="inline-flex items-center gap-2 text-[#d4a060] hover:text-[#e8a050] transition-colors duration-200"
          >
            Learn more about 8pm.me
            <span className="text-sm">→</span>
          </Link>
        </div>
      </div>

      {/* Support Archive.org Section */}
      <div className="bg-[#2a2825] border border-[#3a3632] rounded-lg p-6 md:p-8">
        <div className="flex items-center gap-3 mb-4">
          <HeartIcon className="w-6 h-6 text-[#d4a060]" />
          <h2 className="text-2xl font-semibold text-[#d4a060]">
            Support Archive.org
          </h2>
        </div>
        <p className="text-[#8a8478] mb-6">
          All recordings on 8pm.me are hosted by the Internet Archive, a non-profit
          digital library that preserves cultural artifacts for free public access.
          If you love what they do, consider supporting them.
        </p>
        <a
          href="https://archive.org/donate"
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-2 px-6 py-3 bg-[#d4a060] text-[#1c1a17] font-semibold rounded hover:bg-[#e8a050] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d4a060] focus-visible:ring-offset-2 focus-visible:ring-offset-[#1c1a17]"
        >
          <HeartIcon className="w-5 h-5" />
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
  );
}
