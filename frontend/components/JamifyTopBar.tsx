'use client';

// JamifyTopBar - Top navigation bar with breadcrumbs

import Breadcrumb from './Breadcrumb';

interface JamifyTopBarProps {
  transparent?: boolean;
}

export default function JamifyTopBar({ transparent = false }: JamifyTopBarProps) {
  return (
    <header
      className={`sticky top-0 z-30 flex items-center justify-between px-4 md:px-8 py-2.5 transition-colors ${
        transparent ? 'bg-transparent' : 'bg-[#1c1a17]/95 backdrop-blur-sm'
      }`}
    >
      {/* Breadcrumb navigation */}
      <Breadcrumb />

      {/* Right side - User menu */}
      <div className="flex items-center gap-4">
        <button
          className="w-8 h-8 rounded-full flex items-center justify-center cursor-pointer transition-colors hover:bg-[rgba(200,180,150,0.2)]"
          style={{ background: 'rgba(200,180,150,0.1)' }}
        >
          <span className="text-sm">👤</span>
        </button>
      </div>
    </header>
  );
}
