# CAMPFIRE TAPES — Design Theme

## Concept
Inspired by trading bootleg cassette tapes in the parking lot at Dead shows and Blues Traveler tours. The vibe is Shakedown Street at sunset — someone's got a boombox on the tailgate, tapes spread out on a tapestry, fireflies starting to glow, the smell of nag champa and grilled cheese in the air.

This is the aesthetic of the Deadhead and Blues Traveler tape trading community: hand-labeled Maxell XLIIs passed from friend to friend, setlists scrawled in Sharpie, "Please copy freely — never sell" ethos. The Grateful Dead invented this culture, and Blues Traveler carried the torch with their own taper-friendly philosophy.

**Visual direction:** Earthy, organic, gallery-like. Think natural museum exhibit meets record store meets forest floor. Muted earth tones with warm accents. Soft organic blob shapes in the background. The UI breathes — lots of space, natural flow, nothing harsh.

**Key principle:** Centered, focused layouts. No sidebars. Top nav only. Everything flows down the page like a single-page zine, a Grateful Dead poster, or the inside of a gatefold LP sleeve.

---

## Color Palette

### Earthy Backgrounds
- **Deep earth:** `#1c1a17` — almost black with brown undertone
- **Warm soil:** `#252220` — dark brown-gray
- **Clay:** `#2d2a26` — medium earth
- **Sand light:** `#3a3632` — hover states
- **Parchment tint:** `rgba(245,235,220,0.02)` — subtle warm overlay

### Organic Blob Colors (for background shapes)
- **Moss:** `rgba(90,110,85,0.06)` — soft green blob
- **Terracotta:** `rgba(180,120,90,0.05)` — warm orange-brown blob
- **Sage:** `rgba(140,155,130,0.04)` — muted green
- **Clay rose:** `rgba(160,120,115,0.04)` — dusty pink-brown
- **Ochre mist:** `rgba(200,170,120,0.03)` — golden haze

### Cassette Shell
- **Shell gradient:** `linear-gradient(180deg, #383530 0%, #2a2725 50%, #1e1c1a 100%)`
- **Plastic highlight:** `#4a4540`
- **Screw holes:** `radial-gradient(circle at 35% 35%, #4a4540, #151412)`
- **Tape window:** `#0c0b0a`
- **Reel:** `radial-gradient(circle at 40% 40%, #353230, #151412)`
- **Tape brown:** `#5a4a3a`, `#4a3a2a`, `#3a2a1a`

### Label (natural cream paper)
- **Warm white:** `#f5f0e8`
- **Aged cream:** `#ebe5d8`
- **Parchment:** `#e0d8c8`
- **Paper texture lines:** `rgba(120,100,70,0.08)`
- **Text on cream:** `#2a2420` (heading), `#4a4035` (subhead), `#6a5a4a` (body), `#8a7a68` (muted)

### Accent Colors (muted, natural)
- **Amber glow:** `#d4a060` — primary accent (like honey/firelight)
- **Warm ochre:** `#c08a40` — deeper amber
- **Amber gradient:** `linear-gradient(135deg, #d4a060, #b88030)`
- **Rust header:** `linear-gradient(180deg, #a85a38, #8a4828)` — muted red-brown
- **Rust badge:** `linear-gradient(135deg, #a85a38, #7a4020)`
- **Fire glow:** `rgba(220,140,60,0.25)`, `rgba(200,100,40,0.10)`

### Psychedelic Accents (very muted, use sparingly)
- **Forest teal:** `#5a8a7a` — for highlights, version badges
- **Dusty violet:** `#7a6a80` — tertiary accents
- **Lichen green:** `#6a7a5a` — success states
- **Gold shimmer:** `rgba(220,180,100,0.08)` — subtle glows

### Text (on dark backgrounds)
- **Heading:** `#e8e0d4` — warm off-white
- **Body:** `#c8c0b4` — muted cream
- **Muted:** `#8a8478`, `#6a6458`
- **Disabled:** `#4a4640`, `#3a3835`
- **Link/accent:** `#d4a060`

### Borders
- **Card border:** `1px solid #3a3632`
- **Card selected border:** `2px solid #d4a060`
- **Divider:** `1px solid rgba(200,180,150,0.08)`
- **Divider strong:** `1px solid rgba(200,180,150,0.15)`

---

## Organic Background System

### Base Layer
```css
background: #1c1a17;
```

### Blob Overlay (creates organic, gallery feel)
```css
background:
  /* Large moss blob - top left */
  radial-gradient(ellipse 600px 500px at 10% 20%, rgba(90,110,85,0.06) 0%, transparent 70%),
  /* Terracotta blob - right side */
  radial-gradient(ellipse 500px 600px at 85% 60%, rgba(180,120,90,0.05) 0%, transparent 70%),
  /* Sage blob - bottom */
  radial-gradient(ellipse 700px 400px at 50% 90%, rgba(140,155,130,0.04) 0%, transparent 70%),
  /* Clay rose - center */
  radial-gradient(ellipse 400px 400px at 40% 50%, rgba(160,120,115,0.03) 0%, transparent 70%),
  /* Base */
  #1c1a17;
```

### Subtle Texture Overlay (optional)
```css
/* Adds organic grain - very subtle */
background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23noise)'/%3E%3C/svg%3E");
opacity: 0.03;
mix-blend-mode: overlay;
```

---

## Typography

### Font Families
- **Primary (headings, titles):** `Georgia, serif` — warm, classic, has that "hand-lettered tape label" energy
- **Secondary (UI, labels, data):** `system-ui, sans-serif` — clean, readable

### Sizes & Styles
- **Album title:** 48-52px, Georgia, color `#e8e0d4`
- **Track title:** 15-16px, Georgia, color `#c8c0b4` (or `#e8e0d4` when active)
- **Year (on cards):** 32-36px, Georgia, weight 600
- **Venue name:** 13-14px, Georgia italic
- **Metadata labels:** 11px, system-ui, color `#6a6458`
- **Metadata values:** 11px, system-ui, color `#a8a098`
- **Small labels:** 10-11px, system-ui, letterspacing 2-3px
- **Versions badge:** 11px, system-ui, color `#5a8a7a` (forest teal), prefix with `●`

---

## Layout Structure

### Top Navigation
```
┌─────────────────────────────────────────────────────────────────┐
│ ⚡ Campfire Tapes    ← Grateful Dead       Home  Search  Your Tapes  ☮ │
└─────────────────────────────────────────────────────────────────────┘
```
- Logo: `#d4a060`, 22-24px
- Background: transparent or very subtle `rgba(30,28,25,0.8)` with blur
- Border bottom: `1px solid rgba(200,180,150,0.08)`

### Hero Section
```
┌──────────────────────────────────────────────────────────────────┐
│                    ✦ LIVE FROM THE VAULT ✦                       │
│                                                                  │
│  🌹 ┌─────────────┐                                              │
│     │  CASSETTE   │   ☮ LIVE ALBUM                               │
│     │   (340px)   │   Cornell '77                                │
│     │  slightly   │   Barton Hall                                │
│     │  rotated    │   Grateful Dead • May 8, 1977                │
│  🐻 │  🔥 glow    │                                              │
│     └─────────────┘   "The legendary show..."                    │
│                                                                  │
│                       [▶ Play]  [⟲ Shuffle]  [♡]                 │
└──────────────────────────────────────────────────────────────────┘
```

### Side A / Side B Dividers
```
──────────── ✧ SIDE A ✧ ────────────
```
- Divider line: `linear-gradient(90deg, transparent, rgba(200,180,150,0.2), transparent)`
- Text: `#8a8478`, letterspacing 4px
- Symbols: `#d4a060` amber

---

## Recording Cards

### Card Styles
- **Width:** min 240px
- **Border radius:** 12px
- **Hover:** `transform: translateY(-2px)`, `box-shadow: 0 12px 32px rgba(0,0,0,0.3)`

**Unselected:**
- Background: `linear-gradient(180deg, #2d2a26 0%, #242220 100%)`
- Border: `1px solid #3a3632`
- Year color: `#a8a098`
- Venue color: `#c8c0b4`
- Play button: `linear-gradient(135deg, #3a3632, #2d2a26)`, text `#a8a098`

**Selected:**
- Background: `linear-gradient(180deg, #f5f0e8 0%, #e8e0d0 100%)` (natural cream paper)
- Border: `2px solid #d4a060`
- Year color: `#2a2420`
- Venue color: `#3a3430`
- Labels color: `#7a6a5a`
- Rating color: `#a85a38` (rust)
- Badge: `⚡ PLAYING` — rust gradient, cream text
- Play button: amber gradient, dark text

---

## Cassette Tape Component

### Dimensions
- **Shell:** 340px × 220px (desktop)
- **Label area:** top 14px, sides 24px, height 95px
- **Tape window:** top 118px, sides 42px, height 75px
- **Reels:** 52px diameter

### Label Structure (natural paper feel)
```
┌─────────────────────────────────────────┐
│ ⚡ LIVE RECORDING ⚡      Type II XL 90  │ ← Rust header
├─────────────────────────────────────────┤
│ Cornell '77 ☮                      '77  │ ← Title + peace sign
│ Grateful Dead — Barton Hall             │ ← Artist (italic)
├─────────────────────────────────────────┤
│   22 tracks  ✦  3:58:00  ✦  legendary   │ ← Footer
└─────────────────────────────────────────┘
```

### Fire Glow (warmer, more organic)
```css
background: radial-gradient(ellipse, rgba(220,140,60,0.3) 0%, rgba(180,100,40,0.15) 40%, transparent 70%);
filter: blur(18px);
```

---

## Animations

### Reel Spin
```css
@keyframes spinLeft {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
/* Left reel: 2s, Right reel: 1.2s */
```

### Fire Flicker (softer)
```css
@keyframes flicker {
  0%, 100% { opacity: 0.2; }
  50% { opacity: 0.35; }
}
```

### Firefly Pulse
```css
@keyframes firefly {
  0%, 100% { opacity: 0; transform: scale(0.5); }
  50% { opacity: 0.5; transform: scale(1); }
}
```

---

## Shadows & Effects

### Cassette Shell
```css
box-shadow: 
  0 20px 60px rgba(0,0,0,0.4),
  0 8px 24px rgba(0,0,0,0.2),
  inset 0 1px 0 rgba(255,255,255,0.04);
```

### Label (natural paper)
```css
box-shadow: 
  0 2px 8px rgba(0,0,0,0.2),
  inset 0 1px 0 rgba(255,255,255,0.6);
```

### Ambient Page Glow (warmer)
```css
position: fixed;
bottom: -100px;
width: 100%;
height: 400px;
background: radial-gradient(ellipse at 50% 100%, rgba(200,120,50,0.1) 0%, rgba(180,100,40,0.04) 40%, transparent 70%);
```

---

## Buttons

### Primary (Play)
```css
width: 56px;
height: 56px;
border-radius: 50%;
background: linear-gradient(135deg, #d4a060, #b88030);
color: #1c1a17;
box-shadow: 0 4px 20px rgba(212,160,96,0.3);
```

### Secondary
```css
padding: 14px 24px;
background: transparent;
border: 1px solid #4a4640;
border-radius: 6px;
color: #a8a098;
```

---

## Iconography

### Functional Icons
- Play: `▶`, Pause: `❚❚`, Expand: `▼`, Collapse: `▲`
- Add to queue: `+`, Heart: `♡`, Shuffle: `⟲`
- Version indicator: `●` (in forest teal `#5a8a7a`)
- Stars: `★★★★★`, Back: `←`

### Decorative (use sparingly)
- Peace: `☮`, Stars: `✦` `✧`, Lightning: `⚡`
- Moon: `☽`, Sun: `☀`
- Dead icons: 💀 🐻 🌹 (Stealie, bears, roses)

---

## Responsive Notes

### Desktop (900px+)
- Max-width 1000px centered
- Cassette 340px beside album info
- Organic blobs visible in background

### Mobile (<600px)
- Cassette 280px, centered
- Simplified background (fewer/smaller blobs)
- Mini player fixed at bottom
- Cards stack vertically

---

## Mood Keywords
Campfire, earthy, organic, gallery, natural, muted, warm, amber, ochre, moss, terracotta, analog, worn, museum, forest floor, parchment, handmade, Shakedown Street, tape trading, Cornell '77, "please copy freely"

---

## What This Is NOT
- No harsh blacks (use warm darks)
- No bright saturated colors (everything muted)
- No cold blues or purples
- No sharp geometric backgrounds (organic blobs only)
- No Spotify/corporate feel
- No overwhelming UI — let it breathe

---

## Example Prompt for Claude

"Apply the CAMPFIRE TAPES theme with an earthy, organic, gallery feel. Key points:

1. Earthy muted palette: deep warm browns (#1c1a17), aged cream (#f5f0e8), amber accents (#d4a060)
2. Organic blob shapes in background (moss green, terracotta, sage) — soft, natural, gallery-like
3. NO sidebar — minimal top navigation
4. Centered layout with lots of breathing room
5. Cassette tape with natural cream paper label, rust-colored header
6. Recording cards: dark earth unselected, cream paper when selected
7. Georgia serif for headings, muted forest teal (#5a8a7a) for version badges
8. Soft fire glow effects — warmer, more diffuse
9. Track list with Side A / Side B dividers using ✧ stars
10. Mobile: mini player, vertical card stack, simplified background
11. The vibe: museum exhibit meets record store meets forest floor — natural, warm, organic"

---

## Footer
```
☮ Please copy freely — never sell ☮
POWERED BY ARCHIVE.ORG
```
