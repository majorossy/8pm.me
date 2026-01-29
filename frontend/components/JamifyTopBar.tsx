'use client';

// JamifyTopBar - Top navigation bar with breadcrumbs

import Breadcrumb from './Breadcrumb';

interface JamifyTopBarProps {
  transparent?: boolean;
}

export default function JamifyTopBar({ transparent = false }: JamifyTopBarProps) {
  return (
    <header
      className={`fixed top-0 left-0 right-0 z-40 flex items-center px-4 md:px-6 py-1 transition-colors ${
        transparent ? 'bg-transparent' : 'bg-[#1c1a17]/95 backdrop-blur-sm'
      }`}
    >
      {/* Breadcrumb navigation */}
      <Breadcrumb />

      {/* Soft gradient fade at bottom */}
      {!transparent && (
        <div className="absolute bottom-0 left-0 right-0 h-8 bg-gradient-to-b from-[#1c1a17]/80 via-[#1c1a17]/40 to-transparent pointer-events-none -mb-8" />
      )}
    </header>
  );
}
