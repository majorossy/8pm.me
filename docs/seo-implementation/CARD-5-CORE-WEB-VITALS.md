# CARD-5: Core Web Vitals Optimization

**Priority:** 🟡 Medium - Improves search ranking and UX
**Estimated Time:** 6-8 hours (updated: more accurate memoization estimates)
**Assignee:** Frontend Developer
**Dependencies:** CARD-4 (Image Optimization) recommended

---

## 📋 Objective

Optimize Interaction to Next Paint (INP) and Cumulative Layout Shift (CLS) to meet Core Web Vitals thresholds and improve search rankings.

---

## ✅ Acceptance Criteria

- [ ] LCP < 2.5s (75th percentile) - Covered in CARD-4
- [ ] CLS < 0.1 (75th percentile)
- [ ] INP < 200ms (75th percentile)
- [ ] Performance score > 90 on Lighthouse
- [ ] web-vitals monitoring implemented
- [ ] No layout shifts during page load

---

## 🎯 Target Metrics

| Metric | Current | Target | Impact |
|--------|---------|--------|--------|
| LCP | ~4-6s | <2.5s | ✅ CARD-4 |
| CLS | ~0.05 | <0.1 | Already good, maintain |
| INP | ~250ms | <200ms | Focus area |
| Performance Score | ~70 | >90 | Overall goal |

---

## 🔧 Implementation Steps

### Step 1: Add web-vitals Monitoring (30 min)

**Install library:**
```bash
cd frontend
npm install web-vitals
```

**File:** `frontend/app/layout.tsx`

Add to your ClientLayout or create a new component:

```tsx
'use client';

import { useEffect } from 'react';
import { onCLS, onLCP, onINP, onFCP, onTTFB } from 'web-vitals';

export function WebVitalsMonitor() {
  useEffect(() => {
    const sendToAnalytics = (metric: any) => {
      // Log to console in development
      if (process.env.NODE_ENV === 'development') {
        console.log(`[Web Vitals] ${metric.name}:`, metric.value, metric);
      }

      // Send to analytics in production
      if (process.env.NODE_ENV === 'production') {
        // Example: Google Analytics
        // window.gtag?.('event', metric.name, {
        //   value: Math.round(metric.value),
        //   metric_id: metric.id,
        //   metric_delta: metric.delta,
        // });

        // Or custom endpoint
        fetch('/api/analytics', {
          method: 'POST',
          body: JSON.stringify(metric),
          headers: { 'Content-Type': 'application/json' },
        }).catch(console.error);
      }
    };

    onCLS(sendToAnalytics);
    onLCP(sendToAnalytics);
    onINP(sendToAnalytics);
    onFCP(sendToAnalytics);
    onTTFB(sendToAnalytics);
  }, []);

  return null;
}
```

**Add to layout:**
```tsx
// app/layout.tsx
import { WebVitalsMonitor } from '@/components/WebVitalsMonitor';

export default function RootLayout({ children }) {
  return (
    <html>
      <body>
        <WebVitalsMonitor />
        {children}
      </body>
    </html>
  );
}
```

### Step 2: Memoize Audio Visualizations (60 min)

Audio visualizations can cause expensive re-renders. Memoize them:

**File:** `frontend/components/AudioVisualizations.tsx`

```tsx
import { memo } from 'react';

export const VUMeter = memo(
  ({ volume, size }: VUMeterProps) => {
    // Component logic unchanged
  },
  (prevProps, nextProps) => {
    // Only re-render if volume changed significantly
    return Math.abs(prevProps.volume - nextProps.volume) < 0.01;
  }
);

export const SpinningReel = memo(
  ({ isPlaying, size }: SpinningReelProps) => {
    // Component logic unchanged
  },
  (prevProps, nextProps) => {
    // Only re-render if playing state changes
    return prevProps.isPlaying === nextProps.isPlaying;
  }
);

export const Waveform = memo(
  ({ data, width, height, color }: WaveformProps) => {
    // Component logic unchanged
  },
  (prevProps, nextProps) => {
    // Only re-render if data array changed significantly
    if (!prevProps.data || !nextProps.data) return false;
    const threshold = 5; // Allow small differences
    for (let i = 0; i < prevProps.data.length; i += 10) {
      if (Math.abs(prevProps.data[i] - nextProps.data[i]) > threshold) {
        return false; // Data changed, re-render
      }
    }
    return true; // Data similar, skip re-render
  }
);

// Repeat for EQBars, PulsingDot
```

### Step 3: Lazy Load Heavy Components (45 min)

Defer loading of components not needed immediately:

**File:** `frontend/components/LazyComponents.tsx`

```tsx
import dynamic from 'next/dynamic';

// Audio visualizations - only load when player is active
export const VUMeter = dynamic(
  () => import('./AudioVisualizations').then(mod => ({ default: mod.VUMeter })),
  {
    ssr: false, // Client-only (needs Web Audio API)
    loading: () => <div className="w-9 h-5 bg-[#2d2a26] animate-pulse rounded" />,
  }
);

export const SpinningReel = dynamic(
  () => import('./AudioVisualizations').then(mod => ({ default: mod.SpinningReel })),
  { ssr: false }
);

// Queue drawer - only load when opened
export const Queue = dynamic(() => import('./Queue'), {
  ssr: false,
  loading: () => <div className="h-screen bg-[#1c1a17] animate-pulse" />,
});

// Search overlay - only load when activated
export const JamifySearchOverlay = dynamic(() => import('./JamifySearchOverlay'), {
  ssr: false,
});

// Full player (mobile) - only load when opened
export const JamifyFullPlayer = dynamic(() => import('./JamifyFullPlayer'), {
  ssr: false,
});
```

**Update imports:**
```tsx
// Before:
import { VUMeter } from '@/components/AudioVisualizations';
import Queue from '@/components/Queue';

// After:
import { VUMeter, Queue } from '@/components/LazyComponents';
```

### Step 4: Throttle Audio Analyzer Updates (30 min)

Reduce CPU usage by throttling visualizations:

**File:** `frontend/hooks/useAudioAnalyzer.ts`

Find the `analyze` function and add throttling:

```typescript
const analyze = useCallback(() => {
  if (!analyzerRef.current || !audioContextRef.current) {
    return;
  }

  // Throttle updates from 60fps to 30fps
  const now = Date.now();
  const throttleMs = 33; // ~30fps (1000ms / 30fps = 33ms)

  if (now - lastUpdateRef.current < throttleMs) {
    animationRef.current = requestAnimationFrame(analyze);
    return;
  }
  lastUpdateRef.current = now;

  // Existing analysis logic...
  const bufferLength = analyzerRef.current.frequencyBinCount;
  const waveformDataArray = new Uint8Array(bufferLength);
  const frequencyDataArray = new Uint8Array(bufferLength);

  analyzerRef.current.getByteTimeDomainData(waveformDataArray);
  analyzerRef.current.getByteFrequencyData(frequencyDataArray);

  // Calculate volume (unchanged)
  const sum = waveformDataArray.reduce((acc, val) => acc + Math.abs(val - 128), 0);
  const volume = sum / bufferLength / 128;

  setAnalyzerData({
    waveform: Array.from(waveformDataArray),
    volume,
    frequencyData: Array.from(frequencyDataArray),
  });

  animationRef.current = requestAnimationFrame(analyze);
}, []);

// Add ref for throttling
const lastUpdateRef = useRef(0);
```

**Impact:** Reduces CPU usage by ~50% with imperceptible visual difference.

### Step 5: Prevent Layout Shift (CLS) (45 min)

**A. Reserve Space for Bottom Player**

Already done (fixed positioning), but ensure content padding:

**File:** `frontend/components/ClientLayout.tsx`

```tsx
<main className="pb-[120px] md:pb-[100px]">
  {/* Content here - padding prevents overlap with fixed player */}
</main>
```

**B. Image Skeletons**

Add skeleton loaders for images:

```tsx
{isLoading ? (
  <div className="animate-pulse bg-[#2d2a26] w-14 h-14 rounded" />
) : (
  <Image src={coverArt} alt={album} width={56} height={56} />
)}
```

**C. Font Loading Optimization**

Your fonts are already optimized with `display: 'swap'`, but ensure fallback fonts match:

**File:** `frontend/app/layout.tsx`

```tsx
const orbitron = Orbitron({
  subsets: ['latin'],
  variable: '--font-orbitron',
  display: 'swap',
  adjustFontFallback: true, // Add this
});

const spaceMono = Space_Mono({
  subsets: ['latin'],
  weight: ['400', '700'],
  variable: '--font-space-mono',
  display: 'swap',
  adjustFontFallback: true, // Add this
});
```

### Step 6: Context Providers Are Already Optimal

**Current State:** Your 8 nested context providers are actually well-structured.

⚠️ **DO NOT consolidate context providers.** The current implementation is optimal because:
- Each context serves a distinct purpose (Audio, Queue, Theme, etc.)
- No performance issues detected in profiling
- Consolidation would add complexity without measurable benefit
- React Context re-renders only subscribers, not all children

**Why the original plan suggested consolidation:**
- Common advice for poorly-structured apps with many contexts
- Your app is NOT poorly structured - contexts are focused and minimal

**What to do instead:**
- ✅ Keep existing context provider structure
- ✅ Focus on memoizing expensive components (Steps 2-5)
- ✅ Use lazy loading for heavy components (Step 3)

### Step 7: Add CSS Containment (15 min)

Isolate audio visualizations from document reflow:

**File:** `frontend/components/AudioVisualizations.tsx`

```tsx
<div
  style={{
    width: `${width}px`,
    height: `${height}px`,
    contain: 'layout style paint', // CSS containment
  }}
>
  {/* Visualization canvas/SVG */}
</div>
```

---

## 🧪 Testing & Validation

### Manual Testing

1. **Open Chrome DevTools → Performance**
2. **Record page load**
3. **Check for:**
   - [ ] Long tasks (>50ms) - should be minimal
   - [ ] Layout shifts - highlighted in red
   - [ ] Main thread blocking - should be gaps between tasks

### Lighthouse Testing

```bash
# Desktop
npx lighthouse http://localhost:3001/artists/phish \
  --preset=desktop \
  --only-categories=performance \
  --view

# Mobile (throttled)
npx lighthouse http://localhost:3001/artists/phish \
  --preset=mobile \
  --throttling.cpuSlowdownMultiplier=4 \
  --only-categories=performance \
  --view
```

**Target Scores:**
- Performance: >90
- LCP: <2.5s
- CLS: <0.1
- INP: <200ms

### Real User Monitoring (Production)

After deploying, monitor in Google Search Console:
1. Navigate to **Core Web Vitals** report
2. Check 75th percentile for LCP, CLS, INP
3. Filter by page type (artist, album, track)
4. Track improvements over 28-day rolling window

---

## 📊 Performance Benchmarks

Run before/after tests and document:

```bash
# Create benchmark script
cat > benchmark.sh << 'EOF'
#!/bin/bash
echo "Running performance benchmarks..."

# Desktop
npx lighthouse http://localhost:3001/artists/phish \
  --preset=desktop \
  --output=json \
  --output-path=lighthouse-desktop.json

# Mobile
npx lighthouse http://localhost:3001/artists/phish \
  --preset=mobile \
  --output=json \
  --output-path=lighthouse-mobile.json

# Extract scores
node -e "
const desktop = require('./lighthouse-desktop.json');
const mobile = require('./lighthouse-mobile.json');

console.log('=== Desktop ===');
console.log('Performance:', desktop.categories.performance.score * 100);
console.log('LCP:', desktop.audits['largest-contentful-paint'].numericValue, 'ms');
console.log('CLS:', desktop.audits['cumulative-layout-shift'].numericValue);
console.log('INP:', desktop.audits['interaction-to-next-paint']?.numericValue || 'N/A', 'ms');

console.log('\n=== Mobile ===');
console.log('Performance:', mobile.categories.performance.score * 100);
console.log('LCP:', mobile.audits['largest-contentful-paint'].numericValue, 'ms');
console.log('CLS:', mobile.audits['cumulative-layout-shift'].numericValue);
console.log('INP:', mobile.audits['interaction-to-next-paint']?.numericValue || 'N/A', 'ms');
"
EOF

chmod +x benchmark.sh
./benchmark.sh
```

---

## 🐛 Troubleshooting

**Issue:** INP still high (>200ms)

**Solution:**
- Check for synchronous operations in click handlers
- Use `startTransition` for non-urgent updates
- Profile with Chrome DevTools to find slow interactions

**Issue:** CLS increased after changes

**Solution:**
- Ensure all images have explicit dimensions
- Check for dynamic content insertion
- Verify skeleton loaders match final content size

**Issue:** web-vitals not logging

**Solution:**
- Ensure component is client-side (`'use client'`)
- Check browser console for errors
- Verify web-vitals library is installed

---

## 📚 References

- [Core Web Vitals Guide](https://web.dev/vitals/)
- [Optimize LCP](https://web.dev/lcp/)
- [Optimize CLS](https://web.dev/cls/)
- [Optimize INP](https://web.dev/inp/)
- [web-vitals Library](https://github.com/GoogleChrome/web-vitals)
- [React.memo Documentation](https://react.dev/reference/react/memo)
- [Next.js Dynamic Imports](https://nextjs.org/docs/app/building-your-application/optimizing/lazy-loading)

---

## ✋ Success Criteria

**Week 1 After Deployment:**
- [ ] All Core Web Vitals in "Good" range (green) in Search Console
- [ ] Lighthouse performance score >90
- [ ] No user complaints about sluggish interactions

**Month 1:**
- [ ] 75th percentile LCP <2.5s
- [ ] 75th percentile CLS <0.1
- [ ] 75th percentile INP <200ms
- [ ] Search Console shows improved Core Web Vitals assessment
