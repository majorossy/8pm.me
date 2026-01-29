# CARD-4: Next.js Image Optimization

**Priority:** 🟡 Medium - Improves LCP (Core Web Vitals)
**Estimated Time:** 5-7 hours (updated: +PWA cache fix +Archive.org domains)
**Assignee:** Frontend Developer
**Dependencies:** None (can run in parallel with other cards)

---

## 📋 Objective

Replace all `<img>` tags with Next.js `<Image>` component and enable automatic image optimization to improve Largest Contentful Paint (LCP) scores.

---

## ✅ Acceptance Criteria

- [ ] `unoptimized: false` in next.config.js
- [ ] All album artwork uses `<Image>` component
- [ ] Hero images have `priority` prop
- [ ] Images serve WebP/AVIF formats automatically
- [ ] Lazy loading enabled for below-fold images
- [ ] LCP improves by 1-2 seconds (measured with Lighthouse)
- [ ] No image-related console errors

---

## 🎯 Expected Impact

**Before:** LCP ~4-6s (unoptimized images, large file sizes)
**After:** LCP <2.5s (optimized formats, lazy loading, preloading)

**File Size Reduction:**
- JPEG → WebP: ~25-30% smaller
- WebP → AVIF: ~20% smaller
- Responsive srcset: Only loads appropriate size

---

## 🔧 Implementation Steps

### Step 1: Enable Image Optimization (15 min)

**File:** `frontend/next.config.js`

⚠️ **Current State:** Image optimization is DISABLED (`unoptimized: true`). You must enable it:

```javascript
const nextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'localhost',
      },
      {
        protocol: 'https',
        hostname: 'magento.test',
      },
      {
        protocol: 'https',
        hostname: '**.us.archive.org',  // Wildcard for all Archive.org CDN subdomains
        pathname: '/**',
      },
    ],
    // REMOVE THIS LINE: unoptimized: true,
    formats: ['image/avif', 'image/webp'],
    deviceSizes: [640, 750, 828, 1080, 1200],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
    minimumCacheTTL: 60 * 60 * 24 * 30, // 30 days
  },
};
```

**Why `remotePatterns` instead of `domains`?**
- `remotePatterns` is the modern Next.js approach (domains is deprecated)
- Supports wildcards like `**.us.archive.org` for all CDN subdomains
- More secure (can restrict protocols and paths)

**Also update PWA cache configuration** to cache optimized images:

```javascript
// In next.config.js, update next-pwa configuration:
const withPWA = require('next-pwa')({
  dest: 'public',
  runtimeCaching: [
    // ... existing caching rules
    {
      urlPattern: /^\/_next\/image\?/,  // Add this to cache Next.js optimized images
      handler: 'CacheFirst',
      options: {
        cacheName: 'next-images',
        expiration: {
          maxEntries: 100,
          maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
        },
      },
    },
  ],
});
```

**Why this matters:** Without this, the service worker caches original images BEFORE Next.js optimization, preventing WebP/AVIF from ever being served.

**Restart dev server:**
```bash
cd frontend
bin/refresh
```

### Step 2: Update BottomPlayer Component (45 min)

**File:** `frontend/components/BottomPlayer.tsx`

**Current code (lines ~128-137):**
```tsx
{queue.album?.coverArt ? (
  <img
    src={queue.album.coverArt}
    alt={queue.album.name}
    loading="lazy"
    onLoad={() => setImageLoaded(true)}
    className={`w-full h-full object-cover transition-opacity duration-300 ${
      imageLoaded ? 'opacity-100' : 'opacity-0'
    }`}
  />
) : (/* fallback */)}
```

**Updated code:**
```tsx
import Image from 'next/image';

{queue.album?.coverArt ? (
  <Image
    src={queue.album.coverArt}
    alt={`${queue.album.name} by ${currentSong.artistName}`}
    width={56}
    height={56}
    quality={85}
    onLoad={() => setImageLoaded(true)}
    className={`rounded object-cover transition-opacity duration-300 ${
      imageLoaded ? 'opacity-100' : 'opacity-0'
    }`}
  />
) : (/* fallback */)}
```

**Key changes:**
- Import `Image` from `next/image`
- Add `width` and `height` props (prevents CLS)
- Remove `loading="lazy"` (Image component handles this)
- Add `quality={85}` for balance between quality and file size

### Step 3: Update JamifyFullPlayer (Mobile) (30 min)

**File:** `frontend/components/JamifyFullPlayer.tsx`

Find album artwork rendering (likely around line 150-200) and replace:

```tsx
// Before:
<img src={currentSong.albumArt} alt="Album" className="w-full h-full..." />

// After:
<Image
  src={currentSong.albumArt}
  alt={`${currentSong.albumName} by ${currentSong.artistName}`}
  width={300}
  height={300}
  priority  // Above-the-fold on mobile full player
  quality={90}
  className="w-full h-full object-cover"
/>
```

### Step 4: Update Artist Page Hero Image (30 min)

**File:** `frontend/app/artists/[slug]/page.tsx`

Find artist hero image:

```tsx
// Before:
<img src={artist.wikipedia_artwork_url} alt={artist.name} />

// After:
<Image
  src={artist.wikipedia_artwork_url || '/images/default-artist.jpg'}
  alt={`${artist.name} artist photo`}
  width={1200}
  height={630}
  priority  // Hero image, above-the-fold
  quality={90}
  className="w-full h-auto"
/>
```

### Step 5: Update Album Grid Items (45 min)

**File:** `frontend/components/AlbumGrid.tsx` (or wherever album cards are rendered)

```tsx
// Before:
<img src={album.coverArt} alt={album.name} loading="lazy" />

// After:
<div className="relative w-full aspect-square">
  <Image
    src={album.coverArt || '/images/default-album.jpg'}
    alt={`${album.name} album cover`}
    fill
    sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
    quality={80}
    className="object-cover rounded"
  />
</div>
```

**Why `fill` and `sizes`?**
- `fill`: Image takes full size of parent container
- `sizes`: Tells Next.js what size to load based on viewport
  - Mobile: 50% of viewport width
  - Tablet: 33% of viewport width
  - Desktop: 25% of viewport width

### Step 6: Update Queue Component (30 min)

**File:** `frontend/components/Queue.tsx`

```tsx
// Queue item artwork
<Image
  src={item.albumArt}
  alt={`${item.albumName} cover`}
  width={48}
  height={48}
  quality={75}
  className="rounded"
/>
```

### Step 7: Add Fallback Image (15 min)

Create default images for missing artwork:

**File:** `frontend/public/images/default-album.jpg`
**File:** `frontend/public/images/default-artist.jpg`

Use a 1200x630 placeholder or create with this service:
https://placeholder.com/1200x630

Update all `Image` components to use fallback:
```tsx
src={album.coverArt || '/images/default-album.jpg'}
```

### Step 8: Handle External Images (Archive.org) (Already Done)

✅ Archive.org images are handled by the wildcard pattern in Step 1:

```javascript
remotePatterns: [{
  protocol: 'https',
  hostname: '**.us.archive.org',  // Matches all subdomains
}]
```

This covers:
- `ia600200.us.archive.org`
- `ia800200.us.archive.org`
- `ia600900.us.archive.org`
- All other Archive.org CDN subdomains

**Note:** If Archive.org rate-limits image optimization requests, you may need to fallback to original images. Add error handling:

```tsx
<Image
  src={album.coverArt}
  alt={album.name}
  width={300}
  height={300}
  onError={(e) => {
    // Fallback to original image if optimization fails
    e.currentTarget.src = album.coverArt;
  }}
/>
```

---

## 🧪 Testing Checklist

### Visual Testing

- [ ] Album artwork displays correctly on desktop
- [ ] Album artwork displays correctly on mobile
- [ ] No layout shift when images load (CLS check)
- [ ] Images maintain aspect ratio
- [ ] Placeholder/loading state looks good
- [ ] Full player artwork is crisp and clear

### Performance Testing

```bash
# Run Lighthouse
npx lighthouse http://localhost:3001/artists/phish --view

# Check LCP specifically
npx lighthouse http://localhost:3001/artists/phish \
  --only-categories=performance \
  --chrome-flags="--headless" \
  | grep "Largest Contentful Paint"
```

**Target metrics:**
- LCP < 2.5s (desktop)
- LCP < 3.5s (mobile on throttled connection)

### Network Testing

1. Open Chrome DevTools → Network tab
2. Filter by "Img"
3. Verify:
   - [ ] Images are WebP or AVIF format
   - [ ] Correct sizes loaded (not loading 1200px image for 48px display)
   - [ ] Above-fold images have `priority` (no lazy loading delay)
   - [ ] Below-fold images lazy load (check "Initiator" column)

### Browser Testing

- [ ] Chrome (WebP + AVIF support)
- [ ] Safari (WebP support, AVIF partial)
- [ ] Firefox (WebP + AVIF support)
- [ ] Mobile Safari
- [ ] Mobile Chrome

---

## 📊 Performance Comparison

Run before and after Lighthouse tests:

```bash
# Before optimization
npx lighthouse http://localhost:3001/artists/phish --output=json --output-path=before.json

# After optimization
npx lighthouse http://localhost:3001/artists/phish --output=json --output-path=after.json

# Compare
node -e "
const before = require('./before.json');
const after = require('./after.json');
console.log('LCP Before:', before.audits['largest-contentful-paint'].numericValue, 'ms');
console.log('LCP After:', after.audits['largest-contentful-paint'].numericValue, 'ms');
console.log('Improvement:', (before.audits['largest-contentful-paint'].numericValue - after.audits['largest-contentful-paint'].numericValue) / 1000, 's');
"
```

---

## 🐛 Troubleshooting

**Issue:** "Invalid src prop" error

**Solution:** Ensure domain is in `next.config.js` → `images.domains`. For Archive.org, add all CDN subdomains.

**Issue:** Images not optimizing (still serving original format)

**Solution:**
1. Check `unoptimized: false` in config
2. Restart dev server: `bin/refresh`
3. Clear `.next/` cache: `rm -rf .next`

**Issue:** Blurry images

**Solution:** Increase `quality` prop (default is 75):
```tsx
<Image src={...} quality={90} />
```

**Issue:** Layout shift when images load

**Solution:** Always specify dimensions:
```tsx
// Fixed dimensions
<Image width={300} height={300} />

// Or use fill with container sizing
<div className="relative w-64 h-64">
  <Image fill />
</div>
```

**Issue:** "Image with src X is missing alt property"

**Solution:** Always provide descriptive alt text:
```tsx
<Image alt={`${album.name} by ${artist.name} album cover`} />
```

---

## 🎯 Files to Update

**Priority Order:**

1. ✅ **next.config.js** - Enable optimization
2. ✅ **BottomPlayer.tsx** - Most visible component
3. ✅ **JamifyFullPlayer.tsx** - Mobile hero
4. ✅ **Artist page** - Hero images
5. ✅ **Album grids** - Multiple images
6. ✅ **Queue.tsx** - List items
7. ✅ **Search results** - Dynamic lists

**Find all images:**
```bash
cd frontend
grep -r '<img' app/ components/ --exclude-dir=node_modules | grep -v 'next/image'
```

---

## 📚 References

- [Next.js Image Component](https://nextjs.org/docs/app/api-reference/components/image)
- [Image Optimization Guide](https://nextjs.org/docs/app/building-your-application/optimizing/images)
- [AVIF vs WebP Comparison](https://www.smashingmagazine.com/2021/09/modern-image-formats-avif-webp/)
- [Core Web Vitals: LCP](https://web.dev/lcp/)

---

## ✋ Next Steps

After image optimization:
- Monitor LCP in production with Google Search Console
- Use CARD-5 to optimize other Core Web Vitals (CLS, INP)
- Consider blur placeholders for premium experience:
  ```tsx
  <Image
    src={coverArt}
    placeholder="blur"
    blurDataURL="data:image/jpeg;base64,..."
  />
  ```
