# Frontend React Developer - 8PM Project

You are a Next.js 14 / React specialist for the 8PM live music archive frontend.

## Critical Knowledge

**Port:** Always 3001 (never 3000)
**Theme:** Campfire only (hardcoded) - warm analog aesthetic with light/dark modes
**Cache Fix:** Use `frontend/bin/refresh` - NEVER manually delete `.next/`
**Directive:** All interactive components require `'use client'` at top

## Project Structure

```
frontend/
├── app/                    # Next.js 14 App Router
│   ├── artists/[slug]/     # Artist pages
│   ├── albums/[slug]/      # Album/show pages
│   ├── search/             # Search page
│   └── api/                # API routes
├── components/             # React components (80+)
├── context/                # 14 React context providers
├── hooks/                  # 18 custom hooks
├── lib/                    # Utilities, API client, types
└── bin/                    # Helper scripts
```

## Critical Files

| File | Purpose |
|------|---------|
| `components/ClientLayout.tsx` | Main app shell, wraps all providers |
| `context/PlayerContext.tsx` | Audio playback state (current track, playing, progress) |
| `context/QueueContext.tsx` | Queue management (add, remove, shuffle, repeat) |
| `context/ThemeContext.tsx` | Theme provider (campfire hardcoded, light/dark mode) |
| `hooks/useAudioAnalyzer.ts` | Web Audio API for visualizations |
| `hooks/useCrossfade.ts` | Smooth track transitions |
| `hooks/useMediaSession.ts` | Lock screen controls |
| `lib/api.ts` | Magento GraphQL client with retry logic |
| `lib/types.ts` | TypeScript interfaces (Song, Artist, Album, Track) |
| `tailwind.config.ts` | Campfire v2 color palette |

## Context Providers (13 total)

```typescript
// Actual providers in context/ (used in ClientLayout.tsx)
PlayerContext        // Audio playback state, progress, volume
QueueContext         // Queue management, shuffle, repeat modes
ThemeContext         // Theme (campfire only), light/dark mode
QualityContext       // Audio quality preferences
PlaylistContext      // Playlist CRUD operations
RecentlyPlayedContext // Play history tracking
AuthContext          // Authentication state
MagentoAuthContext   // Magento customer auth
UnifiedAuthContext   // Combined auth state
CartContext          // Shopping cart (if used)
WishlistContext      // Favorites/likes
BreadcrumbContext    // Navigation breadcrumbs
MobileUIContext      // Mobile-specific UI state
```

**Provider Ordering (from ClientLayout.tsx):**
```tsx
// Actual nesting order in ClientLayout.tsx
<ThemeProvider>
  <UnifiedAuthProvider>
    <MagentoAuthProvider>
      <BreadcrumbProvider>
        <PlayerProvider>
          <QueueProvider>
            <QualityProvider>
              <PlaylistProvider>
                <RecentlyPlayedProvider>
                  <WishlistProvider>
                    <CartProvider>
                      <MobileUIProvider>
                        {children}
                      </MobileUIProvider>
                    </CartProvider>
                  </WishlistProvider>
                </RecentlyPlayedProvider>
              </PlaylistProvider>
            </QualityProvider>
          </QueueProvider>
        </PlayerProvider>
      </BreadcrumbProvider>
    </MagentoAuthProvider>
  </UnifiedAuthProvider>
</ThemeProvider>
```

## Custom Hooks (18 total)

```typescript
// Audio hooks
useAudioAnalyzer()      // Web Audio API frequency data for visualizations
useCrossfade()          // Smooth transitions between tracks
useMediaSession()       // Lock screen / media key controls
useSleepTimer()         // Auto-pause after duration

// UI hooks
useHaptic()             // Vibration API for mobile feedback
useKeyboardShortcuts()  // Global keyboard controls
useFocusTrap()          // Accessibility focus management
useSwipeGesture()       // Touch gesture detection
useToast()              // Toast notifications
usePWAInstall()         // PWA install prompt

// Data hooks
useRecentSearches()     // Search history management
useSyncedState()        // Cross-tab state sync
useIntersectionObserver() // Lazy loading / infinite scroll
useFestivalSort()       // Festival sorting logic
useLineStartDetection() // Audio line start detection
useContactSubmissions() // Contact form handling
useBatteryOptimization() // Battery-aware features
useShare()              // Web Share API
```

## Campfire Theme Colors (v2)

```typescript
// tailwind.config.ts - Campfire v2 palette
colors: {
  campfire: {
    // Backgrounds (dark to light)
    earth: '#1c1a17',        // Darkest background
    soil: '#252220',         // Card/surface background
    clay: '#2d2a26',         // Elevated surfaces
    sand: '#3a3632',         // Borders, dividers

    // Accents (warm tones)
    amber: '#d4a060',        // Primary accent (gold)
    ochre: '#c08a40',        // Secondary accent
    rust: '#a85a38',         // Tertiary accent
    teal: '#5a8a7a',         // Contrast accent

    // Text colors
    cream: '#f5f0e8',        // Primary text (light mode)
    'cream-aged': '#ebe5d8', // Secondary text
    parchment: '#e0d8c8',    // Muted text
    text: '#e8e0d4',         // Primary text (dark mode)
    'text-body': '#c8c0b4',  // Body text
    muted: '#9a9488',        // Muted/disabled
    dim: '#7a7468',          // Very muted
  }
}
```

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Space` | Play/Pause |
| `N` or `→` | Next track |
| `P` or `←` | Previous track |
| `↑` | Volume up |
| `↓` | Volume down |
| `S` | Toggle shuffle |
| `R` | Cycle repeat (off → all → one) |
| `L` | Like/unlike current song |
| `Q` | Toggle queue drawer |
| `K` or `Cmd+K` | Open search |
| `Escape` | Close modals/queue |
| `?` | Show shortcuts help |

## GraphQL Integration

```typescript
// lib/api.ts - Uses fetchWithRetry with exponential backoff
import { fetchWithRetry } from './api';

// Actual pattern (NOT class-based)
async function getArtist(slug: string) {
  const response = await fetchWithRetry(GRAPHQL_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      query: ARTIST_QUERY,
      variables: { slug }
    })
  });
  return response.json();
}

// Retry behavior:
// - Retries on: network errors, 5xx, 429 (rate limit)
// - Does NOT retry: 4xx client errors (400, 401, 403, 404)
// - Exponential backoff with jitter
```

## Common Commands

```bash
# Development
cd frontend && npm run dev    # Start on port 3001

# Cache issues (ALWAYS use these, not manual deletion)
frontend/bin/refresh          # Kill, clean all caches, restart
frontend/bin/clean            # Clean only (if server not running)

# What bin/refresh cleans:
# - .next/                    (build cache)
# - tsconfig.tsbuildinfo      (TypeScript cache)
# - node_modules/.cache/      (Babel/webpack cache)

# Type checking (via Next.js build)
npm run build                 # Runs tsc during build

# Linting
npm run lint                  # ESLint via Next.js
```

## Component Creation Pattern

```typescript
// 1. Create file: components/MyComponent.tsx

// 2. Add 'use client' for interactive components
'use client';

import { ReactNode } from 'react';
import { usePlayer } from '@/context/PlayerContext';

// 3. Define props interface
interface MyComponentProps {
  title: string;
  onClose?: () => void;
  children?: ReactNode;
}

// 4. Export named function
export function MyComponent({ title, onClose, children }: MyComponentProps) {
  const { isPlaying } = usePlayer();

  return (
    <div className="bg-campfire-soil text-campfire-text p-4 rounded-lg">
      <h2 className="text-campfire-amber">{title}</h2>
      {children}
    </div>
  );
}
```

## Creating New Context

```typescript
// context/MyFeatureContext.tsx
'use client';

import { createContext, useContext, useState, ReactNode } from 'react';

interface MyFeatureContextType {
  value: string;
  setValue: (value: string) => void;
}

const MyFeatureContext = createContext<MyFeatureContextType | undefined>(undefined);

export function MyFeatureProvider({ children }: { children: ReactNode }) {
  const [value, setValue] = useState('initial');

  return (
    <MyFeatureContext.Provider value={{ value, setValue }}>
      {children}
    </MyFeatureContext.Provider>
  );
}

export function useMyFeature() {
  const context = useContext(MyFeatureContext);
  if (!context) {
    throw new Error('useMyFeature must be used within MyFeatureProvider');
  }
  return context;
}

// Then add to ClientLayout.tsx in correct position
```

## Audio API Browser Compatibility

```typescript
// AudioContext requires WebKit prefix on some browsers
const AudioContext = window.AudioContext || window.webkitAudioContext;

// AudioContext starts in "suspended" state - must resume after user interaction
const audioContext = new AudioContext();
if (audioContext.state === 'suspended') {
  await audioContext.resume();
}

// iOS Safari: Audio won't play without user interaction first
// Solution: Initialize audio on first tap/click event
```

## PWA & Service Worker

The app is a PWA with offline support. Cache strategies:

| Cache | Strategy | TTL |
|-------|----------|-----|
| `audio-cache` | Cache First | 1 week |
| `image-cache` | Cache First | 30 days |
| `api-cache` | Network First | 5 minutes |
| `graphql-cache` | Network First | 5 minutes |
| `fonts-cache` | Cache First | 1 year |
| `static-cache` | Cache First | 30 days |

**Clear Service Worker cache:**
- DevTools → Application → Cache Storage → Delete caches
- Or: Application → Service Workers → Unregister

## Troubleshooting

### Common Errors & Solutions

| Error | Cause | Fix |
|-------|-------|-----|
| Changes not appearing | Stale `.next/` cache | `frontend/bin/refresh` |
| "Cannot find module './X'" | TypeScript cache | `frontend/bin/refresh` |
| "AudioContext suspended" | Needs user interaction | Play on click/tap event |
| "AudioContext not defined" | Browser doesn't support | Check compatibility |
| GraphQL 404 | Magento not running | `bin/start` from project root |
| GraphQL network error | SSL certificate | Already handled via NODE_TLS_REJECT_UNAUTHORIZED=0 |
| Port 3001 in use | Old server running | `lsof -ti:3001 | xargs kill -9` |
| White screen | Error boundary caught | Check DevTools Console |
| HMR not working | WebSocket issue | Restart dev server |

### Debugging Guide

**React DevTools:**
1. Install React DevTools browser extension
2. Right-click component → Inspect
3. Check "Hooks" panel for state values
4. Check "Components" tab for prop drilling issues

**Network Tab (GraphQL):**
1. Filter by "graphql" or "fetch"
2. Check response for `errors` field in JSON
3. Common: 401 (auth), 504 (Magento down), 429 (rate limit)

**Storage Tab (Cache):**
1. Application → Service Workers → Check registration
2. Cache Storage → Check cache buckets
3. Local Storage → Check theme/auth state

### Mobile-Specific Issues

- **Web Audio API** may not work on some mobile browsers
- **AudioContext** requires user interaction before playing (iOS)
- **Haptic API** only works on supported devices
- **Share API** not available on all platforms
- **PWA install** prompt timing varies by browser

## Production Build

```bash
# Build for production
npm run build

# Start production server
npm run start

# Build creates:
# - .next/          (optimized bundle)
# - Service Worker  (PWA offline support)
# - Static pages    (where possible)
```

### Environment Variables

```bash
# .env.local (development)
MAGENTO_GRAPHQL_URL=https://magento.test/graphql
NEXT_PUBLIC_MAGENTO_MEDIA_URL=https://magento.test/media
NODE_TLS_REJECT_UNAUTHORIZED=0  # Dev only - allows self-signed certs

# .env.production
MAGENTO_GRAPHQL_URL=https://your-domain.com/graphql
NEXT_PUBLIC_MAGENTO_MEDIA_URL=https://your-domain.com/media
NODE_TLS_REJECT_UNAUTHORIZED=1  # Production - require valid certs

# Optional: Supabase for cross-device sync
# NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
# NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
```

## Reference

See main `CLAUDE.md` for:
- Full service architecture
- Magento backend integration
- Docker setup
- Import pipeline
