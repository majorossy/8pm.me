'use client';

// JamifyNavSidebar - Spotify-style left navigation sidebar (desktop only)

import { useState } from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useHaptic } from '@/hooks/useHaptic';
import ProfileMenu from '@/components/ProfileMenu';
import AuthModal from '@/components/AuthModal';

export default function JamifyNavSidebar() {
  const pathname = usePathname();
  const { vibrate, BUTTON_PRESS } = useHaptic();
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);

  const isActive = (path: string) => {
    if (path === '/') return pathname === '/';
    return pathname.startsWith(path);
  };

  return (
    <aside className="fixed left-0 top-0 bottom-0 w-[240px] bg-[#1c1a17] z-40 flex flex-col border-r border-[#3a3632]/30">
      {/* Logo */}
      <div className="p-6">
        <Link href="/" onClick={() => vibrate(BUTTON_PRESS)} className="flex items-center gap-2">
          <span className="text-2xl text-[#d4a060]">⚡</span>
          <span className="font-serif text-xl text-[#e8e0d4]">Campfire Tapes</span>
        </Link>
      </div>

      {/* Main Navigation */}
      <nav className="px-3 space-y-1">
        <Link
          href="/"
          onClick={() => vibrate(BUTTON_PRESS)}
          className={`flex items-center gap-4 px-3 py-3 rounded-md transition-all focus:outline-none focus:ring-2 focus:ring-[#d4a060] ${
            isActive('/') && pathname === '/'
              ? 'bg-[#2d2a26] text-[#e8e0d4] border-l-2 border-[#d4a060]'
              : 'text-[#8a8478] hover:text-[#e8e0d4] hover:bg-[#2d2a26]'
          }`}
        >
          <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
          </svg>
          <span className="font-medium">Home</span>
        </Link>

        <Link
          href="/search"
          onClick={() => vibrate(BUTTON_PRESS)}
          className={`flex items-center gap-4 px-3 py-3 rounded-md transition-all focus:outline-none focus:ring-2 focus:ring-[#d4a060] ${
            isActive('/search')
              ? 'bg-[#2d2a26] text-[#e8e0d4] border-l-2 border-[#d4a060]'
              : 'text-[#8a8478] hover:text-[#e8e0d4] hover:bg-[#2d2a26]'
          }`}
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <span className="font-medium">Search</span>
        </Link>

        <Link
          href="/library"
          onClick={() => vibrate(BUTTON_PRESS)}
          className={`flex items-center gap-4 px-3 py-3 rounded-md transition-all focus:outline-none focus:ring-2 focus:ring-[#d4a060] ${
            isActive('/library')
              ? 'bg-[#2d2a26] text-[#e8e0d4] border-l-2 border-[#d4a060]'
              : 'text-[#8a8478] hover:text-[#e8e0d4] hover:bg-[#2d2a26]'
          }`}
        >
          <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z" />
          </svg>
          <span className="font-medium">Your Library</span>
        </Link>
      </nav>

      {/* Divider */}
      <div className="mx-6 my-4 border-t border-[#3a3632]" />

      {/* Playlist Section */}
      <div className="px-6 flex-1 overflow-y-auto">
        <p className="text-[#6a6458] text-xs uppercase tracking-wider mb-4 font-sans">
          Featured Artists
        </p>
        <div className="space-y-3">
          <Link
            href="/artists/grateful-dead"
            onClick={() => vibrate(BUTTON_PRESS)}
            className={`block text-sm font-serif transition-colors truncate ${
              pathname.startsWith('/artists/grateful-dead')
                ? 'text-[#d4a060]'
                : 'text-[#8a8478] hover:text-[#e8e0d4]'
            }`}
          >
            Grateful Dead
          </Link>
          <Link
            href="/artists/phish"
            onClick={() => vibrate(BUTTON_PRESS)}
            className={`block text-sm font-serif transition-colors truncate ${
              pathname.startsWith('/artists/phish')
                ? 'text-[#d4a060]'
                : 'text-[#8a8478] hover:text-[#e8e0d4]'
            }`}
          >
            Phish
          </Link>
        </div>
      </div>

      {/* Profile Section */}
      <div className="p-4 border-t border-[#3a3632]">
        <ProfileMenu onSignInClick={() => setIsAuthModalOpen(true)} />
      </div>

      {/* Bottom Section */}
      <div className="p-6 pt-2 border-t border-[#3a3632]">
        <p className="text-[10px] text-[#6a6458] uppercase tracking-wider font-sans">
          ☮ Please copy freely — never sell
        </p>
        <p className="text-[9px] text-[#4a4640] uppercase tracking-wider mt-1 font-sans">
          Powered by Archive.org
        </p>
      </div>

      {/* Auth Modal */}
      <AuthModal
        isOpen={isAuthModalOpen}
        onClose={() => setIsAuthModalOpen(false)}
      />
    </aside>
  );
}
