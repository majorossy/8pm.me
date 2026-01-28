'use client';

// JamifyTopBar - Spotify-style top navigation bar with back/forward buttons (desktop only)

import { useRouter } from 'next/navigation';

interface JamifyTopBarProps {
  transparent?: boolean;
}

export default function JamifyTopBar({ transparent = false }: JamifyTopBarProps) {
  const router = useRouter();

  return (
    <header
      className={`sticky top-0 z-30 flex items-center justify-between px-8 py-4 transition-colors ${
        transparent ? 'bg-transparent' : 'bg-[#1c1a17]/95 backdrop-blur-sm'
      }`}
    >
      {/* Navigation buttons */}
      <div className="flex items-center gap-2">
        <button
          onClick={() => router.back()}
          className="w-8 h-8 rounded-full bg-black/70 flex items-center justify-center text-white hover:bg-black transition-colors"
          aria-label="Go back"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          onClick={() => router.forward()}
          className="w-8 h-8 rounded-full bg-black/70 flex items-center justify-center text-white hover:bg-black transition-colors"
          aria-label="Go forward"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      {/* Right side - User menu */}
      <div className="flex items-center gap-4">
        <button className="w-8 h-8 rounded-full bg-[#2d2a26] flex items-center justify-center text-white hover:bg-[#3a3632] transition-colors">
          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
          </svg>
        </button>
      </div>
    </header>
  );
}
