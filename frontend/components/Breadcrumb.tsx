'use client';

// Breadcrumb - Navigation breadcrumbs for the top bar
// Format: 8pm.me > Artist: {name} > Album: {name} > Track: {name} > Version: {year / venue}

import Link from 'next/link';
import { useBreadcrumbs, BreadcrumbType } from '@/context/BreadcrumbContext';

// Get the prefix for each breadcrumb type
function getTypePrefix(type?: BreadcrumbType): string {
  switch (type) {
    case 'artist':
      return 'Artist: ';
    case 'album':
      return 'Album: ';
    case 'track':
      return 'Track: ';
    case 'version':
      return 'Version: ';
    default:
      return '';
  }
}

export default function Breadcrumb() {
  const { breadcrumbs } = useBreadcrumbs();

  // Show "8pm.me" when no breadcrumbs are set
  if (breadcrumbs.length === 0) {
    return (
      <nav aria-label="Breadcrumb" className="flex items-center text-sm">
        <span className="text-[#e8e0d4] font-medium">8pm.me</span>
      </nav>
    );
  }

  return (
    <nav aria-label="Breadcrumb" className="flex items-center text-sm overflow-hidden">
      <ol className="flex items-center gap-1 min-w-0">
        {/* Always show 8pm.me link first */}
        <li className="flex items-center shrink-0">
          <Link
            href="/"
            className="text-[#8a8478] hover:text-[#e8e0d4] transition-colors"
          >
            8pm.me
          </Link>
        </li>

        {breadcrumbs.map((crumb, index) => {
          const isLast = index === breadcrumbs.length - 1;
          const prefix = getTypePrefix(crumb.type);
          const displayLabel = prefix + crumb.label;

          return (
            <li key={index} className="flex items-center min-w-0">
              {/* Chevron separator */}
              <svg
                className="w-4 h-4 text-[#6a6458] shrink-0 mx-1"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 5l7 7-7 7"
                />
              </svg>

              {isLast || !crumb.href ? (
                // Current page (non-clickable)
                <span
                  className="text-[#e8e0d4] font-medium truncate max-w-[200px]"
                  title={displayLabel}
                >
                  {prefix && <span className="text-[#6a6458]">{prefix}</span>}
                  {crumb.label}
                </span>
              ) : (
                // Clickable link
                <Link
                  href={crumb.href}
                  className="text-[#8a8478] hover:text-[#e8e0d4] transition-colors truncate max-w-[200px]"
                  title={displayLabel}
                >
                  {prefix && <span className="text-[#6a6458]">{prefix}</span>}
                  {crumb.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
