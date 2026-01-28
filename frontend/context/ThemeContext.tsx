'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

// Campfire Tapes theme - earthy, organic, warm analog vibes
export type ThemeType = 'campfire';

interface ThemeConfig {
  name: string;
  label: string;
  description: string;
}

export const THEMES: Record<ThemeType, ThemeConfig> = {
  campfire: {
    name: 'campfire',
    label: 'Campfire Tapes',
    description: 'Earthy, organic, warm analog vibes',
  },
};

interface ThemeContextType {
  theme: ThemeType;
  setTheme: (theme: ThemeType) => void;
  themes: typeof THEMES;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme] = useState<ThemeType>('campfire');
  const [mounted, setMounted] = useState(false);

  // Clear old theme selections and set to campfire on mount
  useEffect(() => {
    localStorage.setItem('8pm-theme', 'campfire');
    setMounted(true);
  }, []);

  // Apply theme class to document
  useEffect(() => {
    if (!mounted) return;

    // Remove any old theme classes
    document.documentElement.classList.remove('theme-tron', 'theme-metro', 'theme-minimal', 'theme-classic', 'theme-forest', 'theme-jamify');
    // Add campfire theme class
    document.documentElement.classList.add('theme-campfire');
  }, [mounted]);

  const setTheme = () => {
    // No-op: theme is always campfire
  };

  // Prevent hydration mismatch by not rendering until mounted
  if (!mounted) {
    return <>{children}</>;
  }

  return (
    <ThemeContext.Provider value={{ theme, setTheme, themes: THEMES }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const context = useContext(ThemeContext);
  if (context === undefined) {
    // Return default values if context not yet available (during SSR/hydration)
    return {
      theme: 'campfire' as ThemeType,
      setTheme: () => {},
      themes: THEMES,
    };
  }
  return context;
}
