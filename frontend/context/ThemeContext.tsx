'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

// Only Jamify theme is supported
export type ThemeType = 'jamify';

interface ThemeConfig {
  name: string;
  label: string;
  description: string;
}

export const THEMES: Record<ThemeType, ThemeConfig> = {
  jamify: {
    name: 'jamify',
    label: 'Jamify',
    description: 'Spotify-style dark',
  },
};

interface ThemeContextType {
  theme: ThemeType;
  setTheme: (theme: ThemeType) => void;
  themes: typeof THEMES;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme] = useState<ThemeType>('jamify');
  const [mounted, setMounted] = useState(false);

  // Clear old theme selections and set to jamify on mount
  useEffect(() => {
    localStorage.setItem('8pm-theme', 'jamify');
    setMounted(true);
  }, []);

  // Apply theme class to document
  useEffect(() => {
    if (!mounted) return;

    // Remove any old theme classes
    document.documentElement.classList.remove('theme-tron', 'theme-metro', 'theme-minimal', 'theme-classic', 'theme-forest');
    // Add jamify theme class
    document.documentElement.classList.add('theme-jamify');
  }, [mounted]);

  const setTheme = () => {
    // No-op: theme is always jamify
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
      theme: 'jamify' as ThemeType,
      setTheme: () => {},
      themes: THEMES,
    };
  }
  return context;
}
