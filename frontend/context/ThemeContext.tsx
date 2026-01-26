'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

export type ThemeType = 'tron' | 'metro' | 'minimal' | 'classic' | 'forest';

interface ThemeConfig {
  name: string;
  label: string;
  description: string;
}

export const THEMES: Record<ThemeType, ThemeConfig> = {
  tron: {
    name: 'tron',
    label: 'Tron',
    description: 'Neon grid vibes',
  },
  metro: {
    name: 'metro',
    label: 'Metro',
    description: 'Light & clean',
  },
  minimal: {
    name: 'minimal',
    label: 'Minimal',
    description: 'Clean & modern',
  },
  classic: {
    name: 'classic',
    label: 'Classic',
    description: 'Warm vintage',
  },
  forest: {
    name: 'forest',
    label: 'Forest',
    description: 'Natural greens',
  },
};

interface ThemeContextType {
  theme: ThemeType;
  setTheme: (theme: ThemeType) => void;
  themes: typeof THEMES;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setThemeState] = useState<ThemeType>('tron');
  const [mounted, setMounted] = useState(false);

  // Load theme from localStorage on mount
  useEffect(() => {
    const savedTheme = localStorage.getItem('8pm-theme') as ThemeType;
    if (savedTheme && THEMES[savedTheme]) {
      setThemeState(savedTheme);
    }
    setMounted(true);
  }, []);

  // Apply theme class to document
  useEffect(() => {
    if (!mounted) return;

    // Remove all theme classes
    document.documentElement.classList.remove('theme-tron', 'theme-metro', 'theme-minimal', 'theme-classic', 'theme-forest');
    // Add current theme class
    document.documentElement.classList.add(`theme-${theme}`);
  }, [theme, mounted]);

  const setTheme = (newTheme: ThemeType) => {
    setThemeState(newTheme);
    localStorage.setItem('8pm-theme', newTheme);
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
      theme: 'tron' as ThemeType,
      setTheme: () => {},
      themes: THEMES,
    };
  }
  return context;
}
