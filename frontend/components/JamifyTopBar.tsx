'use client';

// JamifyTopBar - Top navigation bar with breadcrumbs

import Breadcrumb from './Breadcrumb';
import QualitySelector from './QualitySelector';

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
      {/* Breadcrumb navigation (left) */}
      <Breadcrumb />

      {/* Quality selector (right) */}
      <QualitySelector />
    </header>
  );
}
