'use client';

import { ReactNode } from 'react';
import { CartProvider } from '@/context/CartContext';
import { WishlistProvider } from '@/context/WishlistContext';
import { PlaylistProvider } from '@/context/PlaylistContext';
import { QueueProvider } from '@/context/QueueContext';
import { PlayerProvider, usePlayer } from '@/context/PlayerContext';
import { ThemeProvider } from '@/context/ThemeContext';
import { BreadcrumbProvider } from '@/context/BreadcrumbContext';
import { MobileUIProvider, useMobileUI } from '@/context/MobileUIContext';
import BottomPlayer from '@/components/BottomPlayer';
import Queue from '@/components/Queue';
import JamifyNavSidebar from '@/components/JamifyNavSidebar';
import JamifyTopBar from '@/components/JamifyTopBar';
import JamifyMobileNav from '@/components/JamifyMobileNav';
import JamifyFullPlayer from '@/components/JamifyFullPlayer';
import Link from 'next/link';

// Inner layout that can access player state
function InnerLayout({ children }: { children: ReactNode }) {
  const { isMobile } = useMobileUI();

  // Jamify layout (only theme now)
  return (
    <>
      {/* Desktop: Left navigation sidebar */}
      {!isMobile && <JamifyNavSidebar />}

      {/* Main content area */}
      <main className={`min-h-screen bg-[#121212] ${
        isMobile
          ? 'pb-[120px]' // Space for mini player + bottom nav
          : 'ml-[240px] pb-[90px]' // Desktop sidebar + player
      }`}>
        {/* Desktop: Top bar with nav buttons */}
        {!isMobile && <JamifyTopBar />}
        {children}
      </main>

      {/* Mobile: Bottom navigation tabs */}
      {isMobile && <JamifyMobileNav />}

      {/* Mini player (mobile) or full player bar (desktop) */}
      <BottomPlayer />

      {/* Mobile: Full-screen player (expands from mini player) */}
      {isMobile && <JamifyFullPlayer />}

      {/* Queue drawer */}
      <Queue />
    </>
  );
}

function LayoutContent({ children }: { children: ReactNode }) {
  return (
    <CartProvider>
      <WishlistProvider>
        <PlaylistProvider>
          <QueueProvider>
            <PlayerProvider>
              <BreadcrumbProvider>
                <MobileUIProvider>
                  <InnerLayout>{children}</InnerLayout>
                </MobileUIProvider>
              </BreadcrumbProvider>
            </PlayerProvider>
          </QueueProvider>
        </PlaylistProvider>
      </WishlistProvider>
    </CartProvider>
  );
}

export default function ClientLayout({ children }: { children: ReactNode }) {
  return (
    <ThemeProvider>
      <LayoutContent>{children}</LayoutContent>
    </ThemeProvider>
  );
}
