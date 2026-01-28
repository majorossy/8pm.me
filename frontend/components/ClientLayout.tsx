'use client';

import { ReactNode, useState, useCallback } from 'react';
import { CartProvider } from '@/context/CartContext';
import { WishlistProvider, useWishlist } from '@/context/WishlistContext';
import { PlaylistProvider } from '@/context/PlaylistContext';
import { QueueProvider, useQueue } from '@/context/QueueContext';
import { PlayerProvider, usePlayer } from '@/context/PlayerContext';
import { RecentlyPlayedProvider } from '@/context/RecentlyPlayedContext';
import { ThemeProvider } from '@/context/ThemeContext';
import { BreadcrumbProvider } from '@/context/BreadcrumbContext';
import { MobileUIProvider, useMobileUI } from '@/context/MobileUIContext';
import { AuthProvider } from '@/context/AuthContext';
import { MagentoAuthProvider } from '@/context/MagentoAuthContext';
import { UnifiedAuthProvider } from '@/context/UnifiedAuthContext';
import BottomPlayer from '@/components/BottomPlayer';
import Queue from '@/components/Queue';
import JamifyTopBar from '@/components/JamifyTopBar';
import JamifyMobileNav from '@/components/JamifyMobileNav';
import JamifyFullPlayer from '@/components/JamifyFullPlayer';
import { JamifySearchOverlay } from '@/components/JamifySearchOverlay';
import KeyboardShortcutsHelp from '@/components/KeyboardShortcutsHelp';
import { useKeyboardShortcuts } from '@/hooks/useKeyboardShortcuts';
import ErrorBoundary from '@/components/ErrorBoundary';
import { ToastProvider } from '@/components/ToastContainer';
import InstallPrompt from '@/components/InstallPrompt';
import OfflineIndicator from '@/components/OfflineIndicator';
import LoadingBar from '@/components/LoadingBar';

// Inner layout that can access player state and contexts
function InnerLayout({ children }: { children: ReactNode }) {
  const { isMobile } = useMobileUI();
  const player = usePlayer();
  const queue = useQueue();
  const wishlist = useWishlist();
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const [isHelpOpen, setIsHelpOpen] = useState(false);

  // Volume control helpers
  const handleVolumeUp = useCallback(() => {
    const newVolume = Math.min(1, player.volume + 0.1);
    player.setVolume(newVolume);
  }, [player]);

  const handleVolumeDown = useCallback(() => {
    const newVolume = Math.max(0, player.volume - 0.1);
    player.setVolume(newVolume);
  }, [player]);

  // Repeat cycle: off → all → one → off
  const handleCycleRepeat = useCallback(() => {
    const modes: Array<'off' | 'all' | 'one'> = ['off', 'all', 'one'];
    const currentIndex = modes.indexOf(queue.queue.repeat);
    const nextIndex = (currentIndex + 1) % modes.length;
    queue.setRepeat(modes[nextIndex]);
  }, [queue]);

  // Toggle like for current song
  const handleToggleLike = useCallback(() => {
    if (!player.currentSong) return;

    if (wishlist.isInWishlist(player.currentSong.id)) {
      const item = wishlist.wishlist.items.find(i => i.song.id === player.currentSong?.id);
      if (item) {
        wishlist.removeFromWishlist(item.id);
      }
    } else {
      wishlist.addToWishlist(player.currentSong);
    }
  }, [player.currentSong, wishlist]);

  // Initialize keyboard shortcuts
  useKeyboardShortcuts({
    onPlayPause: player.togglePlay,
    onNext: player.playNext,
    onPrevious: player.playPrev,
    onVolumeUp: handleVolumeUp,
    onVolumeDown: handleVolumeDown,
    onToggleShuffle: () => queue.setShuffle(!queue.queue.shuffle),
    onCycleRepeat: handleCycleRepeat,
    onToggleLike: handleToggleLike,
    onToggleQueue: player.toggleQueue,
    onOpenSearch: () => setIsSearchOpen(true),
    onShowHelp: () => setIsHelpOpen(true),
    isQueueOpen: player.isQueueOpen,
  });

  // Jamify layout (only theme now)
  return (
    <>
      {/* Top loading bar for navigation */}
      <LoadingBar />

      {/* Skip to main content link for keyboard users */}
      <a href="#main-content" className="skip-to-main">
        Skip to main content
      </a>

      {/* Organic blob background */}
      <div className="blob-bg" />

      {/* Fire glow effect */}
      <div className="fire-glow" />

      {/* Main content area */}
      <main
        id="main-content"
        className="min-h-screen bg-[#1c1a17] relative z-10 pb-[120px]"
      >
        {/* Top bar with breadcrumbs */}
        <JamifyTopBar />
        {children}

        {/* Footer */}
        <footer className="px-4 md:px-8 py-6 mt-8 border-t border-[#3a3632]/30">
          <div className="flex flex-col md:flex-row items-center justify-between gap-2 text-center md:text-left">
            <p className="text-[10px] text-[#6a6458] uppercase tracking-wider font-sans">
              Please copy freely - never sell
            </p>
            <p className="text-[9px] text-[#4a4640] uppercase tracking-wider font-sans">
              Powered by Archive.org
            </p>
          </div>
        </footer>
      </main>

      {/* Bottom navigation tabs (all screen sizes) */}
      <JamifyMobileNav />

      {/* Mini player (mobile) or full player bar (desktop) */}
      <BottomPlayer />

      {/* Mobile: Full-screen player (expands from mini player) */}
      {isMobile && <JamifyFullPlayer />}

      {/* Queue drawer */}
      <Queue />

      {/* Search overlay */}
      <JamifySearchOverlay
        isOpen={isSearchOpen}
        onClose={() => setIsSearchOpen(false)}
      />

      {/* Keyboard shortcuts help modal */}
      <KeyboardShortcutsHelp
        isOpen={isHelpOpen}
        onClose={() => setIsHelpOpen(false)}
      />

      {/* PWA Install Prompt */}
      <InstallPrompt />

      {/* Offline Indicator */}
      <OfflineIndicator />
    </>
  );
}

function LayoutContent({ children }: { children: ReactNode }) {
  return (
    <AuthProvider>
      <MagentoAuthProvider>
        <UnifiedAuthProvider>
          <ToastProvider>
            <CartProvider>
              <WishlistProvider>
                <PlaylistProvider>
                  <QueueProvider>
                    <RecentlyPlayedProvider>
                      <PlayerProvider>
                        <BreadcrumbProvider>
                          <MobileUIProvider>
                            <InnerLayout>{children}</InnerLayout>
                          </MobileUIProvider>
                        </BreadcrumbProvider>
                      </PlayerProvider>
                    </RecentlyPlayedProvider>
                  </QueueProvider>
                </PlaylistProvider>
              </WishlistProvider>
            </CartProvider>
          </ToastProvider>
        </UnifiedAuthProvider>
      </MagentoAuthProvider>
    </AuthProvider>
  );
}

export default function ClientLayout({ children }: { children: ReactNode }) {
  return (
    <ErrorBoundary>
      <ThemeProvider>
        <LayoutContent>{children}</LayoutContent>
      </ThemeProvider>
    </ErrorBoundary>
  );
}
