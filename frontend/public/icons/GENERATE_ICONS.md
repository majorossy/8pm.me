# PWA Icon Generation

The `icon-base.svg` file contains the base icon design. You need to generate PNG icons at various sizes for the PWA.

## Required Icons

- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png
- icon-384x384.png
- icon-512x512.png
- apple-touch-icon.png (180x180)

## Generation Methods

### Option 1: Using ImageMagick (recommended)

```bash
cd frontend/public/icons

# Generate all sizes
convert icon-base.svg -resize 72x72 icon-72x72.png
convert icon-base.svg -resize 96x96 icon-96x96.png
convert icon-base.svg -resize 128x128 icon-128x128.png
convert icon-base.svg -resize 144x144 icon-144x144.png
convert icon-base.svg -resize 152x152 icon-152x152.png
convert icon-base.svg -resize 192x192 icon-192x192.png
convert icon-base.svg -resize 384x384 icon-384x384.png
convert icon-base.svg -resize 512x512 icon-512x512.png
convert icon-base.svg -resize 180x180 apple-touch-icon.png
```

### Option 2: Using a web tool

1. Go to https://realfavicongenerator.net/
2. Upload the icon-base.svg
3. Configure settings and download the icon pack

### Option 3: Using Figma/Sketch

1. Import icon-base.svg
2. Export at each required size

## Testing

After generating icons, test the PWA installation:

1. Build the production version: `npm run build`
2. Start production server: `npm start`
3. Open in Chrome and check the install prompt
4. On mobile, use "Add to Home Screen"
