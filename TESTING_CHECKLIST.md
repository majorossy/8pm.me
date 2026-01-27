# Jamify Mobile Testing Checklist

**Version:** 1.0.0
**Last Updated:** 2026-01-27
**Status:** Ready for QA

---

## 🎯 Quick Start

1. **Enable Jamify Theme:** Settings → Select "Jamify" theme
2. **Resize Browser:** Width < 767px (mobile breakpoint)
3. **Play a Song:** Start playback to test player features
4. **Open DevTools:** Chrome DevTools → Performance tab

---

## ✅ Core Gestures & Animations

### Player Swipe Gestures

**Test Cases:**

- [ ] **Swipe up from mini player**
  - Mini player visible at bottom of screen
  - Swipe up 50px or more
  - Full player slides up smoothly (300ms)
  - Mini player disappears during transition

- [ ] **Swipe down from full player**
  - Full player is open
  - Swipe down 50px or more
  - Player slides down smoothly (300ms)
  - Mini player reappears at bottom

- [ ] **Partial swipe snap-back**
  - Swipe up/down less than 50px
  - Release touch
  - Player snaps back to original position
  - No jank or stuttering

- [ ] **Fast flick gesture**
  - Quick swipe (high velocity)
  - Even if < 50px distance
  - Player should expand/collapse
  - Feels responsive and natural

- [ ] **Visual drag tracking**
  - Touch and drag (don't release)
  - Player follows finger position
  - Transform updates smoothly
  - No lag or delay

- [ ] **Drag hint pill visibility**
  - Full player shows pill at top (36px x 5px)
  - Mini player shows pill at top
  - White/semi-transparent color
  - Visible against background

**Performance:**

- [ ] Open Chrome DevTools → Performance
- [ ] Start recording
- [ ] Perform swipe gesture
- [ ] Stop recording
- [ ] Verify: 60 FPS maintained (green bars)
- [ ] No long tasks (> 50ms)

---

### Touch Feedback

**Test Cases:**

- [ ] **All buttons respond to touch**
  - Play/pause button
  - Next/previous buttons
  - Shuffle/repeat buttons
  - Like button
  - Queue button
  - All buttons in full player

- [ ] **Visual feedback timing**
  - Press any button
  - Scale down to 95% within < 100ms
  - Release button
  - Returns to 100% smoothly

- [ ] **Ripple effect (main play button)**
  - Tap large play button in full player
  - White ripple expands from center
  - Fades out smoothly (600ms)

- [ ] **No lag or delay**
  - Rapid tapping feels responsive
  - No stuck states
  - No visual glitches

---

### Animations

**Test Cases:**

- [ ] **Track change pulse animation**
  - Play a song
  - Skip to next track
  - Mini player glows green for 800ms
  - Animation is smooth and subtle

- [ ] **Slide-up animation (full player)**
  - Expand player
  - Slides from bottom to top (300ms)
  - Cubic-bezier easing (0.4, 0.0, 0.2, 1)
  - No jank or stutter

- [ ] **Album art fade-in**
  - Clear browser cache
  - Load a page with album art
  - Images fade from 0 → 100% opacity (300ms)
  - No layout shift during load

- [ ] **No scroll interference**
  - Scroll vertically during horizontal swipe
  - Vertical scroll should be prevented
  - Horizontal swipe on queue items works
  - Vertical scroll on queue drawer works

---

## ✅ Queue Swipe-to-Delete

**Test Cases:**

- [ ] **Swipe left on queue track**
  - Open queue drawer
  - Swipe left on a track
  - Red background reveals underneath
  - Trash icon becomes visible

- [ ] **Delete threshold (80px)**
  - Swipe left 80px or more
  - Release touch
  - Confirmation prompt appears (browser confirm)
  - Track is removed after confirmation

- [ ] **Snap-back on incomplete swipe**
  - Swipe left < 80px
  - Release touch
  - Track snaps back to original position
  - Red background hides smoothly

- [ ] **Delete animation**
  - Confirm delete
  - Track fades out (opacity)
  - Track slides left
  - List reflows smoothly

- [ ] **Prevent vertical scroll during swipe**
  - Start horizontal swipe on queue item
  - Vertical scroll is prevented
  - Swipe feels natural

- [ ] **Desktop: Traditional X button**
  - Resize window to > 767px (desktop)
  - Queue items show X button
  - No swipe functionality
  - Click X to delete

---

## ✅ Search Overlay

**Test Cases:**

- [ ] **Open search overlay**
  - Tap Search tab in bottom nav
  - Overlay slides up from bottom (300ms)
  - Input auto-focuses
  - Mobile keyboard appears

- [ ] **Recent searches display**
  - First time: No recent searches shown
  - Perform a search
  - Close overlay
  - Reopen overlay
  - Recent search chip appears

- [ ] **Search input debounce**
  - Type quickly: "Grateful Dead"
  - Search should wait 300ms after last keystroke
  - Loading spinner appears during search
  - Results replace recent searches

- [ ] **Recent search chips**
  - Tap a recent search chip
  - Performs that search immediately
  - Loading spinner shows
  - Results display

- [ ] **Delete recent search**
  - Hover over recent search chip (desktop) or long-press (mobile)
  - Delete button appears
  - Click/tap delete button
  - Chip is removed
  - localStorage updated

- [ ] **Clear all recent searches**
  - Click "Clear all" button
  - All chips disappear
  - localStorage cleared
  - Confirmed via console: `localStorage.getItem('jamify-recent-searches')`

- [ ] **Close overlay**
  - Close via X button → Slides down smoothly
  - Close via backdrop click → Slides down smoothly
  - Input loses focus
  - Keyboard hides

- [ ] **Empty state**
  - Search for nonsense: "asdfghjkl"
  - "No results found" message appears
  - Try again prompt shows

---

## ✅ Performance Optimizations

### Lazy Loading

**Test Cases:**

- [ ] **Images load on-demand**
  - Open DevTools → Network tab
  - Filter: Img
  - Scroll down page
  - Images load only when near viewport
  - Verify via Network waterfall

- [ ] **Fade-in on load**
  - Clear cache
  - Load page with images
  - Images start at 0% opacity
  - Fade to 100% over 300ms
  - No layout shift (check Cumulative Layout Shift in Lighthouse)

- [ ] **All image components**
  - Album cards: Lazy loading ✓
  - Artist cards: Lazy loading ✓
  - Mini player album art: Lazy loading ✓
  - Full player album art: Lazy loading ✓
  - Queue items: Lazy loading ✓

---

### Battery Optimization

**Test Cases:**

- [ ] **Low battery detection (< 20%)**
  - Drain device battery to < 20%
  - **OR** Simulate via DevTools:
    ```javascript
    // Chrome DevTools Console
    navigator.getBattery().then(battery => {
      Object.defineProperty(battery, 'level', { value: 0.15 });
      Object.defineProperty(battery, 'charging', { value: false });
    });
    ```
  - Reload page
  - Animations should be disabled
  - Verify via console: `reducedMotion` should be `true`

- [ ] **Reduced motion accessibility**
  - OS Settings → Accessibility → Reduce Motion → ON
  - Reload page
  - All animations disabled
  - Player transitions instant
  - No pulse glow
  - No slide animations

- [ ] **Animations disabled correctly**
  - When `reducedMotion` is true:
    - No player slide-up/down
    - No pulse glow on track change
    - No ripple effects
    - No skeleton pulse
    - No spinner rotation
    - EQ bars static

- [ ] **Graceful fallback (Battery API unsupported)**
  - Test in Safari (Battery API not supported)
  - No errors in console
  - Reduced motion still respects OS setting
  - Page functions normally

---

### GPU Acceleration

**Test Cases:**

- [ ] **will-change during drag**
  - Inspect mini player element during swipe
  - `will-change: transform` applied during drag
  - Removed after drag completes
  - Verify in DevTools → Computed Styles

- [ ] **Memory usage**
  - Open DevTools → Performance Monitor
  - Start recording
  - Perform 10 swipe gestures
  - Stop recording
  - Memory should not continuously increase
  - Layers should be promoted/demoted properly

- [ ] **60 FPS maintained**
  - CPU throttling 6x (DevTools → Performance)
  - Perform swipe gestures
  - Should still feel smooth (aim for 30+ FPS under throttle)
  - Green bars in timeline (no red drops)

---

## ✅ Polish & Final Touches

### Mini Player Design

**Test Cases:**

- [ ] **Enhanced shadow visible**
  - Mini player has depth perception
  - Layered shadow (dark to light)
  - Shadow: `0 -6px 16px rgba(0,0,0,0.6), 0 -2px 8px rgba(0,0,0,0.4)`
  - Visible against background

- [ ] **Gradient background**
  - Mini player background is gradient
  - Gradient: `#6b5d4f` → `#5c4d3d`
  - Subtle but noticeable

- [ ] **Pulse glow enhancement**
  - Track change triggers pulse
  - Green glow: `rgba(29, 185, 84, 0.6)`
  - Pulsing shadow visible
  - Returns to default shadow after 800ms

- [ ] **WCAG AA contrast**
  - White text on dark background
  - Contrast ratio ≥ 4.5:1
  - Test with: https://webaim.org/resources/contrastchecker/

- [ ] **Touch targets ≥ 44x44px**
  - All buttons tappable
  - No mis-taps
  - Comfortable on small screens (iPhone SE)

---

### Touch-Action & Overscroll

**Test Cases:**

- [ ] **Vertical scroll in full player**
  - Full player open
  - Lyrics or additional content (if present)
  - Vertical scroll works smoothly
  - Horizontal swipe disabled

- [ ] **Horizontal swipe on queue items**
  - Open queue
  - Swipe left on track
  - Horizontal swipe works
  - Vertical scroll disabled during swipe

- [ ] **No rubber band overscroll**
  - Scroll to top of queue
  - Try to scroll further up
  - No rubber band bounce
  - Contained within drawer

- [ ] **No scroll conflicts**
  - Swipe up on mini player
  - Page scroll doesn't interfere
  - Gesture feels natural

---

### iOS Safe Areas & Dynamic Viewport

**Test Cases (iOS Device Required):**

- [ ] **iPhone X/11/12/13/14 (Notch)**
  - Full player respects notch
  - Content not hidden behind notch
  - Top padding applied correctly

- [ ] **iPhone 14 Pro/15 Pro (Dynamic Island)**
  - Full player respects Dynamic Island
  - Content visible below island
  - Top padding applied correctly

- [ ] **Home Indicator (All modern iPhones)**
  - Full player respects home indicator
  - Bottom controls visible above indicator
  - Bottom padding applied correctly

- [ ] **Rounded Corners (iPhone X+)**
  - Content doesn't touch rounded corners
  - Left/right padding applied
  - Visible in landscape orientation

- [ ] **Dynamic Viewport (Safari UI collapse)**
  - Open full player
  - Scroll down in Safari (UI hides)
  - Player height adjusts dynamically
  - Uses 100dvh correctly

- [ ] **Status Bar Styling (PWA Mode)**
  - Add to Home Screen (iOS)
  - Launch as PWA
  - Status bar uses `black-translucent` style
  - Content visible behind status bar

---

### Loading States

**Test Cases:**

- [ ] **Skeleton loaders**
  - Slow down network (DevTools → Network → Slow 3G)
  - Load queue
  - Skeleton loaders pulse while loading
  - Fade to real content on load

- [ ] **Search loading spinner**
  - Open search overlay
  - Type query
  - Spinner appears (24px, green)
  - Rotates smoothly
  - Disappears when results load

- [ ] **Image lazy loading spinners**
  - Clear cache
  - Slow network (Slow 3G)
  - Scroll to images
  - Fade from transparent to visible
  - No layout shift

---

## 🌐 Device-Specific Tests

### iOS Safari (Required)

**Devices:**
- iPhone SE (smallest screen: 375px)
- iPhone 14 Pro (Dynamic Island)
- iPad (tablet layout - should use desktop)

**Test Cases:**

- [ ] **Gestures work smoothly**
  - No delay or lag
  - Touch events register correctly
  - Swipe feels native

- [ ] **Safe areas respected**
  - Notch/Dynamic Island
  - Home indicator
  - Rounded corners

- [ ] **Dynamic viewport adjusts**
  - Safari UI collapse/expand
  - Player height adjusts
  - No content clipping

- [ ] **No scroll bounce interference**
  - Swipe gestures work
  - Rubber band doesn't trigger
  - Overscroll prevented

- [ ] **Battery API graceful fallback**
  - No console errors
  - Reduced motion still works (OS setting)

---

### Chrome Android (Required)

**Devices:**
- Samsung Galaxy (mid-range)
- Google Pixel (flagship)
- Low-end device (< 2GB RAM if available)

**Test Cases:**

- [ ] **Battery API detection**
  - Drain battery to < 20%
  - Animations disabled automatically
  - Console shows `reducedMotion: true`

- [ ] **Touch events responsive**
  - No delay
  - Gestures feel natural
  - 60 FPS maintained

- [ ] **Navigation bar**
  - Gestures work with Android nav bar
  - No conflicts with back gesture

- [ ] **Low-end device performance**
  - Animations still smooth
  - No crashes
  - Reduced motion helps

---

### Firefox Mobile (Optional)

**Test Cases:**

- [ ] **Gestures work**
- [ ] **Animations smooth**
- [ ] **Battery API fallback** (not supported)
- [ ] **No console errors**

---

### Samsung Internet (Optional)

**Test Cases:**

- [ ] **All features work**
- [ ] **No visual glitches**
- [ ] **Gestures responsive**

---

## 📊 Lighthouse Audit

**Target Scores:**
- Performance: > 90
- Accessibility: 100
- Best Practices: > 95
- SEO: > 90

**Run Audit:**

1. Open DevTools → Lighthouse
2. Device: Mobile
3. Categories: All
4. Click "Analyze page load"

**Check Metrics:**

- [ ] **FCP (First Contentful Paint):** < 1.8s
- [ ] **LCP (Largest Contentful Paint):** < 2.5s
- [ ] **TBT (Total Blocking Time):** < 200ms
- [ ] **CLS (Cumulative Layout Shift):** < 0.1
- [ ] **Speed Index:** < 3.4s

**Accessibility Checks:**

- [ ] Contrast ratios ≥ 4.5:1
- [ ] Touch targets ≥ 44x44px
- [ ] ARIA labels present
- [ ] Keyboard navigation works
- [ ] Screen reader compatible

---

## 🐛 Known Issues & Edge Cases

### Test Edge Cases:

- [ ] **No internet connection**
  - Album art fails to load
  - Fallback icon shows
  - No console errors

- [ ] **Queue with 100+ tracks**
  - Swipe still works smoothly
  - List renders without lag
  - Consider virtualization if issues

- [ ] **Rapid gestures (10 swipes/second)**
  - No stuck states
  - Transitions complete
  - isTransitioning prevents rapid toggling

- [ ] **Orientation change (portrait ↔ landscape)**
  - Player adjusts layout
  - Safe areas recalculated
  - No visual glitches

- [ ] **Browser back button**
  - Full player open
  - Press back button
  - Player should close (or navigate depending on implementation)

- [ ] **Multiple tabs open**
  - Playback syncs (if implemented)
  - localStorage persists
  - No conflicts

---

## ✅ Final Sign-Off

**QA Lead:**
- [ ] All critical test cases pass
- [ ] No P0/P1 bugs found
- [ ] Performance targets met
- [ ] Accessibility requirements met

**Product Owner:**
- [ ] Meets design requirements
- [ ] User experience is polished
- [ ] Ready for production

**Engineer:**
- [ ] Code reviewed and approved
- [ ] No console errors
- [ ] Browser support verified
- [ ] Documentation complete

---

## 📝 Bug Report Template

**Title:** [Component] Brief description

**Severity:** P0 (Critical) | P1 (High) | P2 (Medium) | P3 (Low)

**Device/Browser:**
- Device: iPhone 14 Pro
- OS: iOS 17.2
- Browser: Safari 17.0

**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Screenshot/Video:**
Attach if possible

**Console Errors:**
```
Copy any errors here
```

---

**Testing Completed:** [ ] Yes [ ] No
**Date:** __________
**Tester:** __________
**Approved For Production:** [ ] Yes [ ] No
