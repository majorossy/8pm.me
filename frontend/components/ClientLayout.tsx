'use client';

import { ReactNode } from 'react';
import { CartProvider } from '@/context/CartContext';
import { WishlistProvider } from '@/context/WishlistContext';
import { PlayerProvider, usePlayer } from '@/context/PlayerContext';
import { ThemeProvider, useTheme } from '@/context/ThemeContext';
import BottomPlayer from '@/components/BottomPlayer';
import Queue from '@/components/Queue';
import ThemeSwitcher from '@/components/ThemeSwitcher';
import Link from 'next/link';

// Inner layout that can access player state
function InnerLayout({ children }: { children: ReactNode }) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';

  // In Metro mode, pages manage their own sidebars (like album page with track nav)
  // The header stays full width, and content padding is handled per-page

  return (
    <>
      {/* Header - different styles for metro */}
      <header className={`site-header fixed top-0 left-0 right-0 z-50 flex justify-between items-center transition-all ${
        isMetro
          ? 'h-[60px] px-8 bg-white border-b border-[#d4d0c8]'
          : 'px-6 lg:px-12 py-6 bg-gradient-to-b from-dark-900 to-transparent'
      }`}>
        <Link href="/" className={`font-display font-black ${
          isMetro
            ? 'text-lg text-[#1a1a1a]'
            : 'text-2xl gradient-text'
        }`}>
          {isMetro ? 'EightPM' : 'EIGHTPM'}
        </Link>
        <nav className="flex items-center gap-8">
          <Link
            href="/"
            className={`transition-all ${
              isMetro
                ? 'text-[#6b6b6b] text-sm hover:text-[#e85d04]'
                : 'text-text-dim text-xs uppercase tracking-[0.2em] hover:text-neon-cyan hover:neon-text-cyan'
            }`}
          >
            Home
          </Link>
          <Link
            href="/artists"
            className={`transition-all ${
              isMetro
                ? 'text-[#6b6b6b] text-sm hover:text-[#e85d04]'
                : 'text-text-dim text-xs uppercase tracking-[0.2em] hover:text-neon-cyan hover:neon-text-cyan'
            }`}
          >
            Artists
          </Link>
          <Link
            href="#"
            className={`transition-all ${
              isMetro
                ? 'text-[#6b6b6b] text-sm hover:text-[#e85d04]'
                : 'text-text-dim text-xs uppercase tracking-[0.2em] hover:text-neon-cyan hover:neon-text-cyan'
            }`}
          >
            Library
          </Link>
          <div className={`w-px h-4 ${isMetro ? 'bg-[#d4d0c8]' : 'bg-white/20'}`} />
          <ThemeSwitcher />
        </nav>
      </header>

      {/* Main content - Metro pages handle their own layout */}
      <main className={`transition-all ${
        isMetro ? '' : 'pb-24'
      }`}>
        {children}
      </main>

      {/* Player components - hidden in Metro (pages integrate player info) */}
      {!isMetro && <BottomPlayer />}
      {!isMetro && <Queue />}
    </>
  );
}

function LayoutContent({ children }: { children: ReactNode }) {
  const { theme } = useTheme();
  const isMetro = theme === 'metro';

  return (
    <>
      {/* Background effects - hidden in metro */}
      {!isMetro && (
        <>
          <div className="grid-bg" />
          <div className="glow-orb pink" />
          <div className="glow-orb cyan" />
        </>
      )}

      <CartProvider>
        <WishlistProvider>
          <PlayerProvider>
            <InnerLayout>{children}</InnerLayout>
          </PlayerProvider>
        </WishlistProvider>
      </CartProvider>
    </>
  );
}

export default function ClientLayout({ children }: { children: ReactNode }) {
  return (
    <ThemeProvider>
      <LayoutContent>{children}</LayoutContent>
    </ThemeProvider>
  );
}
