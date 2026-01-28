'use client';

// Breadcrumb - Navigation breadcrumbs for the top bar

import Link from 'next/link';
import { useBreadcrumbs } from '@/context/BreadcrumbContext';

export default function Breadcrumb() {
  const { breadcrumbs } = useBreadcrumbs();

  // Show "Home" when no breadcrumbs are set
  if (breadcrumbs.length === 0) {
    return (
      <nav aria-label="Breadcrumb" className="flex items-center text-sm">
        <span className="text-[#e8e0d4] font-medium">Home</span>
      </nav>
    );
  }

  return (
    <nav aria-label="Breadcrumb" className="flex items-center text-sm overflow-hidden">
      <ol className="flex items-center gap-1 min-w-0">
        {/* Always show Home link first */}
        <li className="flex items-center shrink-0">
          <Link
            href="/"
            className="text-[#8a8478] hover:text-[#e8e0d4] transition-colors"
          >
            Home
          </Link>
        </li>

        {breadcrumbs.map((crumb, index) => {
          const isLast = index === breadcrumbs.length - 1;

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
                  title={crumb.label}
                >
                  {crumb.label}
                </span>
              ) : (
                // Clickable link
                <Link
                  href={crumb.href}
                  className="text-[#8a8478] hover:text-[#e8e0d4] transition-colors truncate max-w-[200px]"
                  title={crumb.label}
                >
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
