'use client';

import { createContext, useContext, useState, useEffect, useCallback, ReactNode } from 'react';
import { usePathname } from 'next/navigation';

interface MobileUIState {
  isSidebarOpen: boolean;
  isMobile: boolean;
  isPlayerExpanded: boolean;
  isTransitioning: boolean;
  dragOffset: number;
}

interface MobileUIContextType extends MobileUIState {
  toggleSidebar: () => void;
  closeSidebar: () => void;
  openSidebar: () => void;
  expandPlayer: () => void;
  collapsePlayer: () => void;
  togglePlayer: () => void;
  setDragOffset: (offset: number) => void;
}

const MobileUIContext = createContext<MobileUIContextType | undefined>(undefined);

const MOBILE_BREAKPOINT = 767;

export function MobileUIProvider({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  const [isHydrated, setIsHydrated] = useState(false);
  const [isPlayerExpanded, setIsPlayerExpanded] = useState(false);
  const [isTransitioning, setIsTransitioning] = useState(false);
  const [dragOffset, setDragOffset] = useState(0);

  // Handle hydration and media query
  useEffect(() => {
    setIsHydrated(true);

    const mediaQuery = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT}px)`);
    setIsMobile(mediaQuery.matches);

    const handleChange = (e: MediaQueryListEvent) => {
      setIsMobile(e.matches);
      // Auto-close sidebar when switching to desktop
      if (!e.matches) {
        setIsSidebarOpen(false);
      }
    };

    mediaQuery.addEventListener('change', handleChange);
    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  // Auto-close sidebar on route change (mobile only)
  useEffect(() => {
    if (isMobile && isSidebarOpen) {
      setIsSidebarOpen(false);
    }
    // Also collapse player on route change
    if (isMobile && isPlayerExpanded) {
      setIsPlayerExpanded(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pathname]);

  const toggleSidebar = useCallback(() => {
    setIsSidebarOpen((prev) => !prev);
  }, []);

  const closeSidebar = useCallback(() => {
    setIsSidebarOpen(false);
  }, []);

  const openSidebar = useCallback(() => {
    setIsSidebarOpen(true);
  }, []);

  const expandPlayer = useCallback(() => {
    if (isTransitioning) return;
    setIsTransitioning(true);
    setIsPlayerExpanded(true);
    // Reset transition state after animation completes (300ms)
    setTimeout(() => setIsTransitioning(false), 300);
  }, [isTransitioning]);

  const collapsePlayer = useCallback(() => {
    if (isTransitioning) return;
    setIsTransitioning(true);
    setIsPlayerExpanded(false);
    // Reset transition state after animation completes (300ms)
    setTimeout(() => setIsTransitioning(false), 300);
  }, [isTransitioning]);

  const togglePlayer = useCallback(() => {
    if (isTransitioning) return;
    setIsTransitioning(true);
    setIsPlayerExpanded((prev) => !prev);
    setTimeout(() => setIsTransitioning(false), 300);
  }, [isTransitioning]);

  // SSR: default to desktop (isMobile = false) until hydrated
  const value: MobileUIContextType = {
    isSidebarOpen,
    isMobile: isHydrated ? isMobile : false,
    isPlayerExpanded,
    isTransitioning,
    dragOffset,
    toggleSidebar,
    closeSidebar,
    openSidebar,
    expandPlayer,
    collapsePlayer,
    togglePlayer,
    setDragOffset,
  };

  return (
    <MobileUIContext.Provider value={value}>
      {children}
    </MobileUIContext.Provider>
  );
}

export function useMobileUI() {
  const context = useContext(MobileUIContext);
  if (context === undefined) {
    throw new Error('useMobileUI must be used within a MobileUIProvider');
  }
  return context;
}
