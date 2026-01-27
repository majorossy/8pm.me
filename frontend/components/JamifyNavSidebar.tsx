'use client';

// JamifyNavSidebar - Spotify-style left navigation sidebar (desktop only)

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useHaptic } from '@/hooks/useHaptic';

export default function JamifyNavSidebar() {
  const pathname = usePathname();
  const { vibrate, BUTTON_PRESS } = useHaptic();

  const isActive = (path: string) => {
    if (path === '/') return pathname === '/';
    return pathname.startsWith(path);
  };

  return (
    <aside className="fixed left-0 top-0 bottom-0 w-[240px] bg-black z-40 flex flex-col">
      {/* Logo */}
      <div className="p-6">
        <Link href="/" onClick={() => vibrate(BUTTON_PRESS)} className="flex items-center gap-2">
          <svg className="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z" />
          </svg>
          <span className="font-display text-xl font-bold text-white">8pm</span>
        </Link>
      </div>

      {/* Main Navigation */}
      <nav className="px-3 space-y-1">
        <Link
          href="/"
          onClick={() => vibrate(BUTTON_PRESS)}
          className={`flex items-center gap-4 px-3 py-3 rounded-md transition-all focus:outline-none focus:ring-2 focus:ring-[#1DB954] ${
            isActive('/') && pathname === '/'
              ? 'bg-[#282828] text-white'
              : 'text-[#b3b3b3] hover:text-white hover:bg-[#282828]'
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
          className={`flex items-center gap-4 px-3 py-3 rounded-md transition-all focus:outline-none focus:ring-2 focus:ring-[#1DB954] ${
            isActive('/search')
              ? 'bg-[#282828] text-white'
              : 'text-[#b3b3b3] hover:text-white hover:bg-[#282828]'
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
          className={`flex items-center gap-4 px-3 py-3 rounded-md transition-all focus:outline-none focus:ring-2 focus:ring-[#1DB954] ${
            isActive('/library')
              ? 'bg-[#282828] text-white'
              : 'text-[#b3b3b3] hover:text-white hover:bg-[#282828]'
          }`}
        >
          <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z" />
          </svg>
          <span className="font-medium">Your Library</span>
        </Link>
      </nav>

      {/* Divider */}
      <div className="mx-6 my-4 border-t border-[#282828]" />

      {/* Playlist Section */}
      <div className="px-6 flex-1 overflow-y-auto">
        <p className="text-[#b3b3b3] text-xs uppercase tracking-wider mb-4">
          Featured Artists
        </p>
        <div className="space-y-3">
          <Link
            href="/artists/grateful-dead"
            onClick={() => vibrate(BUTTON_PRESS)}
            className={`block text-sm transition-colors truncate ${
              pathname.startsWith('/artists/grateful-dead')
                ? 'text-white'
                : 'text-[#b3b3b3] hover:text-white'
            }`}
          >
            Grateful Dead
          </Link>
          <Link
            href="/artists/phish"
            onClick={() => vibrate(BUTTON_PRESS)}
            className={`block text-sm transition-colors truncate ${
              pathname.startsWith('/artists/phish')
                ? 'text-white'
                : 'text-[#b3b3b3] hover:text-white'
            }`}
          >
            Phish
          </Link>
        </div>
      </div>

      {/* Bottom Section */}
      <div className="p-6 border-t border-[#282828]">
        <p className="text-[10px] text-[#a7a7a7] uppercase tracking-wider">
          Powered by Archive.org
        </p>
      </div>
    </aside>
  );
}
