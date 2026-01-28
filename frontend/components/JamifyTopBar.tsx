'use client';

// JamifyTopBar - Top navigation bar with breadcrumbs

import Breadcrumb from './Breadcrumb';

interface JamifyTopBarProps {
  transparent?: boolean;
}

export default function JamifyTopBar({ transparent = false }: JamifyTopBarProps) {
  return (
    <header
      className={`sticky top-0 z-30 flex items-center justify-between px-4 md:px-8 py-4 transition-colors ${
        transparent ? 'bg-transparent' : 'bg-[#1c1a17]/95 backdrop-blur-sm'
      }`}
    >
      {/* Breadcrumb navigation */}
      <Breadcrumb />

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
