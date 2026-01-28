# Animation Widget Options for Music Player

## Context
These animations indicate "now playing" state on a retro cassette tape-themed music player. The color palette is warm/earthy: gold accent (#e8a050), cream text (#e8d8c8), dark brown background (#1a1410).

---

## Option A: EQ Bars (Equalizer)
**Description:** Three vertical bars of varying heights that animate up and down at staggered intervals, simulating an audio level meter.

- 3 rectangular bars, side by side with small gaps
- Each bar is 3-4px wide, heights vary from 4px to 16px
- Bars are golden/amber colored (#e8a050)
- Animation: Each bar bounces up and down independently with different timing
- Bar 1: 0.8s cycle, Bar 2: 1.1s cycle, Bar 3: 0.9s cycle
- Creates a "music is playing" visualization effect

**Compact version (for cards):** Same but smaller, 2-3px wide bars, max height 12px

---

## Option B: VU Meter Needle
**Description:** A classic analog VU meter with a needle that bounces between low and high, like vintage cassette deck recording level meters.

- Semi-circular meter base (like a speedometer arc)
- Thin needle pivots from center bottom
- Background arc could have gradient from green to yellow to red (or just golden tones)
- Needle is dark/black with a small circular pivot point
- Animation: Needle swings back and forth between ~20° and ~70° with slight randomness
- Bouncy, organic movement (ease-in-out with slight overshoot)

**Mini version (for cards):** Simplified to just the needle arc, ~20px wide

---

## Option C: Mini Spinning Reel
**Description:** A tiny representation of a cassette tape reel that spins continuously while audio plays.

- Circular reel, 16-20px diameter
- Outer ring (dark brown/black)
- Inner spokes/teeth visible (6 radial lines from center)
- Center hub (small circle)
- Color: Dark browns (#2a2520) with subtle highlights
- Animation: Continuous clockwise rotation, 2 seconds per full rotation
- Smooth linear spin (not bouncy)

**For cards:** Same but 12-14px diameter

---

## Option D: Waveform / Oscilloscope
**Description:** An animated sine wave or audio waveform that pulses and moves horizontally, like a vintage oscilloscope display.

- Horizontal line that oscillates as a wave
- ~60px wide, wave amplitude of ~8px up and down
- Golden/amber colored line (#e8a050) with slight glow
- Animation: Wave moves horizontally (scrolling effect) while amplitude subtly varies
- Creates flowing, organic "audio signal" visualization
- Could have slight phosphor glow effect (green or amber)

**Compact version:** Narrower (~30px), lower amplitude

---

## Option E: Pulsing Dot
**Description:** A simple glowing dot that pulses with a soft glow, similar to a power LED or recording indicator.

- Single circular dot, 8-10px diameter
- Golden/amber color (#e8a050)
- Animation: Opacity and glow radius pulse in and out
- Glow expands from 0px to ~10px shadow radius
- Opacity varies from 0.6 to 1.0
- Cycle time: ~1.5 seconds, smooth ease-in-out
- Subtle and minimal

---

## Visual Placement Reference

**Track Row (accordion header):**
```
┌────────────────────────────────────────────────────┐
│  [ANIMATION]  1.  Song Title Here                  │
│               1972 • Red Rocks • ★★★★☆ • 8 rec.   │
└────────────────────────────────────────────────────┘
```

**Recording Card:**
```
┌─────────────────────────┐
│  1985        [ANIM] ⚡  │  ← Animation in header
├─────────────────────────┤
│  The Fillmore           │
│  San Francisco, CA      │
│  ...                    │
└─────────────────────────┘
```

---

## Request
Please render visual mockups showing each of these 5 animation options (A through E) in both the Track Row and Recording Card contexts, using the warm cassette tape color scheme described above.
