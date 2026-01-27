# Jamify Mobile Testing Checklist

## Pre-Testing Setup

### Required Devices:
- [ ] iPhone 13+ (iOS 15+) - for notch testing
- [ ] iPhone 14 Pro+ (iOS 16+) - for Dynamic Island testing
- [ ] Android device (Chrome 80+) - for cross-platform testing
- [ ] iPad - for tablet testing

### Required Browsers:
- [ ] Safari iOS (latest)
- [ ] Chrome Android (latest)
- [ ] Samsung Internet (latest)
- [ ] Firefox Android (optional)

---

## 1. Visual Design Tests

### Mini Player (Bottom Bar)

- [ ] **Shadow/Elevation**
  - Mini player has visible shadow above nav bar
  - Shadow depth appears correct (not too subtle, not too harsh)
  - Shadow color: `rgba(0, 0, 0, 0.6)` with `0 -6px 16px` offset

- [ ] **Drag Hint Pill**
  - Pill is visible at top of mini player
  - Size: 36px wide x 5px tall
  - Color: `rgba(255, 255, 255, 0.5)` (semi-transparent white)
  - Has subtle shadow for depth

- [ ] **Background Gradient**
  - Smooth gradient from `#6b5d4f` (top) to `#5c4d3d` (bottom)
  - No banding or color steps visible
  - Gradient looks natural on device

- [ ] **Pulse Animation on Track Change**
  - When track changes, mini player pulses green
  - Pulse color: `rgba(29, 185, 84, 0.6)` (Spotify green)
  - Animation duration: 0.8s
  - Smooth ease-out transition

### Full Player

- [ ] **Drag Hint Pill**
  - Same visibility test as mini player
  - Centered at top of full player

- [ ] **Safe Areas (iOS)**
  - Content doesn't overlap notch (iPhone 13+)
  - Content doesn't overlap Dynamic Island (iPhone 14 Pro+)
  - Bottom content respects home indicator
  - Rounded corners have appropriate padding

- [ ] **Dynamic Viewport Height**
  - Full player uses `100dvh` (not `100vh`)
  - Player height adjusts when Safari toolbar collapses
  - No white space at bottom when scrolling

### WCAG AA Compliance

- [ ] **Contrast Ratios**
  - White text on `#5c4d3d`: contrast ≥ 4.5:1 ✓
  - Gray text `#a7a7a7` on `#121212`: contrast ≥ 4.5:1 ✓
  - Green `#1DB954` on `#121212`: contrast ≥ 3:1 (large text) ✓

- [ ] **Touch Targets**
  - All buttons are ≥ 44x44px
  - Swipe areas have adequate hit zones
  - No overlapping touch targets

---

## 2. Gesture & Interaction Tests

### Swipe Up (Mini → Full Player)

- [ ] Swipe up on mini player expands to full player
- [ ] Swipe needs to travel ≥ 50px to trigger
- [ ] Velocity threshold works (fast swipe = lower distance needed)
- [ ] Animation is smooth (300ms cubic-bezier)
- [ ] No jank or stuttering during expansion

### Swipe Down (Full → Mini Player)

- [ ] Swipe down on full player collapses to mini
- [ ] Same threshold/velocity as swipe up
- [ ] Animation is smooth (300ms)
- [ ] Drag hint pill follows finger during gesture

### Scroll Prevention

- [ ] **Full Player**
  - Vertical scroll doesn't trigger page scroll
  - Horizontal swipes don't interfere with controls
  - `touch-action: pan-y` is applied

- [ ] **Queue Drawer**
  - Queue scrolls vertically without triggering player gesture
  - Scrolling queue doesn't expand/collapse player
  - `prevent-overscroll` prevents rubber band effect

- [ ] **Mini Player**
  - Swipe up doesn't trigger page scroll
  - `touch-action: pan-y` prevents horizontal scroll

### Touch Feedback

- [ ] **Scale Down Effect**
  - Buttons scale to 0.95 on press
  - Opacity reduces to 0.8
  - Animation duration: 100ms
  - Applied to all `.btn-touch` elements

- [ ] **Ripple Effect** (optional)
  - Buttons with `.btn-ripple` show ripple on tap
  - Ripple expands from tap point
  - Ripple duration: 600ms
  - Ripple color: `rgba(255, 255, 255, 0.3)`

---

## 3. Loading States Tests

### Skeleton Loaders (Jamify Theme)

- [ ] **Queue Skeleton**
  - Shows 5 skeleton items when loading
  - Skeleton animates with pulse (1.5s)
  - Pulse transitions smoothly between `#282828` and `#383838`

- [ ] **Skeleton Components**
  - `.skeleton-text`: 1em height, full width
  - `.skeleton-title`: 1.5em height, 70% width
  - `.skeleton-subtitle`: 1em height, 50% width

### Fade-In Animation

- [ ] Content fades in smoothly when loaded
- [ ] Fade-in duration: 300ms
- [ ] No flash of unstyled content (FOUC)

### Loading Spinner

- [ ] Spinner appears for async operations
- [ ] Spinner is centered
- [ ] Spinner color: `#1DB954` (Spotify green)
- [ ] Rotation is smooth (0.8s linear infinite)

---

## 4. iOS-Specific Tests

### Safe Area Insets

- [ ] **Top Safe Area**
  - Content below notch/Dynamic Island
  - `.safe-top` class adds `env(safe-area-inset-top)`

- [ ] **Bottom Safe Area**
  - Content above home indicator
  - `.safe-bottom` class adds `env(safe-area-inset-bottom)`

- [ ] **Landscape Orientation**
  - Left/right safe areas respected
  - No content cut off by rounded corners

### Dynamic Viewport

- [ ] **Safari Toolbar**
  - Full player uses `100dvh`
  - Height adjusts when toolbar collapses
  - No jarring resize

- [ ] **Home Indicator**
  - Mini player positioned above home indicator
  - Full player respects bottom safe area

### Web App Mode

- [ ] **Add to Home Screen**
  - App opens in standalone mode
  - Status bar is `black-translucent`
  - No Safari UI visible

---

## 5. Performance Tests

### Animation Performance

- [ ] **Frame Rate**
  - All animations run at 60 FPS
  - Use Chrome DevTools → Performance → Record
  - Check for dropped frames during swipe gestures

- [ ] **GPU Acceleration**
  - Animations use `transform` and `opacity` only
  - No layout thrashing
  - `will-change: transform` on dragging elements

### Reduced Motion

- [ ] **Prefers Reduced Motion**
  - Open Settings → Accessibility → Motion → Reduce Motion
  - All animations disabled
  - Transitions instant (0.01ms)
  - Glow orbs hidden

- [ ] **Low Battery Mode**
  - Battery level < 20% and not charging
  - `useBatteryOptimization` hook detects low battery
  - Animations disabled automatically

### Battery Optimization

- [ ] **Battery API**
  - `useBatteryOptimization` hook works
  - `isLowBattery` flag detects < 20% battery
  - `isCharging` flag detects charging state
  - Graceful fallback if Battery API unsupported

---

## 6. Cross-Browser Tests

### iOS Safari

- [ ] All gestures work
- [ ] Safe areas respected
- [ ] Dynamic viewport works
- [ ] Animations smooth

### Chrome Android

- [ ] All gestures work
- [ ] Touch feedback works
- [ ] Animations smooth
- [ ] No safe area issues (Android has no notch)

### Samsung Internet

- [ ] All gestures work
- [ ] Touch feedback works
- [ ] Animations smooth

### Firefox Android (Optional)

- [ ] Basic functionality works
- [ ] Gestures may have limited support

---

## 7. Haptic Feedback (Manual Implementation Required)

### JavaScript Implementation Needed:

Add to relevant component files:

```javascript
const playHaptic = (pattern) => {
  if ('vibrate' in navigator) {
    navigator.vibrate(pattern);
  }
};

// Button press
button.addEventListener('click', () => playHaptic(10));

// Swipe complete
onSwipeComplete(() => playHaptic([10, 50, 10]));

// Delete/remove
onDelete(() => playHaptic(20));

// Play/pause
onPlayPause(() => playHaptic(15));
```

### Testing:

- [ ] Button press: 10ms vibration (light tap)
- [ ] Swipe complete: [10, 50, 10]ms pattern (double tap)
- [ ] Delete/remove: 20ms vibration (medium tap)
- [ ] Play/pause: 15ms vibration (medium-light tap)

---

## 8. Accessibility Tests

### Keyboard Navigation

- [ ] Tab through all interactive elements
- [ ] Focus indicators visible
- [ ] Enter/Space trigger actions

### Screen Reader (VoiceOver/TalkBack)

- [ ] All buttons have `aria-label`
- [ ] Player state announced correctly
- [ ] Queue items announced with track info

### Color Contrast

Use axe DevTools or Lighthouse:

- [ ] All text meets WCAG AA (4.5:1 for normal, 3:1 for large)
- [ ] Interactive elements distinguishable

---

## 9. Edge Cases

### Network Conditions

- [ ] Skeleton loaders appear on slow connections
- [ ] Graceful degradation if images fail to load
- [ ] Spinner shows for long-running operations

### Empty States

- [ ] Empty queue shows friendly message
- [ ] No album loaded shows placeholder
- [ ] No track playing hides mini player

### Rapid Interactions

- [ ] Rapid swipes don't break player state
- [ ] Multiple taps don't cause race conditions
- [ ] Gesture transitions are debounced

---

## 10. Regression Tests

### Theme Switching

- [ ] Switching from Jamify to other themes works
- [ ] No Jamify-specific CSS leaks to other themes
- [ ] Mobile UI disabled on non-Jamify themes

### Desktop View

- [ ] Full player not accessible on desktop
- [ ] Mini player not shown on desktop
- [ ] Desktop player (3-column layout) works

### Tablet/iPad

- [ ] Mobile UI behavior on tablets (optional)
- [ ] Or desktop layout on large tablets

---

## Test Results Template

```
Device: [iPhone 14 Pro / Samsung Galaxy S21 / etc.]
OS: [iOS 17.2 / Android 13 / etc.]
Browser: [Safari / Chrome / etc.]
Date: [YYYY-MM-DD]

Visual Design: ✅ PASS / ❌ FAIL
Gestures: ✅ PASS / ❌ FAIL
Loading States: ✅ PASS / ❌ FAIL
iOS Support: ✅ PASS / ❌ FAIL / N/A
Performance: ✅ PASS / ❌ FAIL
Accessibility: ✅ PASS / ❌ FAIL

Notes:
- [Any issues or observations]
```

---

## Known Limitations

1. **Haptic Feedback**: Requires manual JavaScript implementation (CSS preparation only)
2. **Battery API**: Only supported on Chrome (gracefully degrades)
3. **Dynamic Viewport (dvh)**: iOS 15.4+, Android Chrome 108+ (fallback to vh)
4. **Touch-action**: Full support iOS 13+, Android Chrome 36+

---

## Automated Testing (Future)

### Playwright Tests:

```javascript
// Example test
test('mini player expands on swipe up', async ({ page }) => {
  await page.goto('/');

  // Swipe up on mini player
  await page.locator('.mini-player').swipe('up', { distance: 100 });

  // Assert full player is visible
  await expect(page.locator('.full-screen-player')).toBeVisible();
});
```

### Lighthouse Checks:

- [ ] Performance score ≥ 90
- [ ] Accessibility score ≥ 95
- [ ] Best Practices score ≥ 90

---

## Sign-Off

- [ ] All critical tests passed
- [ ] Known issues documented
- [ ] Ready for production deployment

**Tester:** _________________
**Date:** _________________
**Approved:** ✅ / ❌
