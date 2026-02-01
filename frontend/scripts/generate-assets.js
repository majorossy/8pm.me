/**
 * Generate OG image and favicon from SVG sources
 *
 * Run: node scripts/generate-assets.js
 */

const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const PUBLIC_DIR = path.join(__dirname, '..', 'public');
const ICONS_DIR = path.join(PUBLIC_DIR, 'icons');
const IMAGES_DIR = path.join(PUBLIC_DIR, 'images');

async function generateOGImage() {
  console.log('Generating OG image...');

  const svgPath = path.join(IMAGES_DIR, 'og-default.svg');
  const jpgPath = path.join(IMAGES_DIR, 'og-default.jpg');

  // Read the SVG
  const svgBuffer = fs.readFileSync(svgPath);

  // Convert to JPG
  await sharp(svgBuffer)
    .resize(1200, 630)
    .jpeg({ quality: 90 })
    .toFile(jpgPath);

  console.log(`  Created: ${jpgPath}`);

  // Also create a PNG version for better quality
  const pngPath = path.join(IMAGES_DIR, 'og-default.png');
  await sharp(svgBuffer)
    .resize(1200, 630)
    .png()
    .toFile(pngPath);

  console.log(`  Created: ${pngPath}`);
}

async function generateFavicon() {
  console.log('Generating favicon...');

  const svgPath = path.join(ICONS_DIR, 'icon-base.svg');
  const svgBuffer = fs.readFileSync(svgPath);

  // Create favicon.ico (actually a 32x32 PNG, browsers handle this)
  // For true .ico, we'd need a different library, but PNG works for most browsers
  const favicon32Path = path.join(PUBLIC_DIR, 'favicon-32x32.png');
  await sharp(svgBuffer)
    .resize(32, 32)
    .png()
    .toFile(favicon32Path);
  console.log(`  Created: ${favicon32Path}`);

  const favicon16Path = path.join(PUBLIC_DIR, 'favicon-16x16.png');
  await sharp(svgBuffer)
    .resize(16, 16)
    .png()
    .toFile(favicon16Path);
  console.log(`  Created: ${favicon16Path}`);

  // Copy SVG as favicon.svg for modern browsers
  const faviconSvgPath = path.join(PUBLIC_DIR, 'favicon.svg');
  fs.copyFileSync(svgPath, faviconSvgPath);
  console.log(`  Created: ${faviconSvgPath}`);

  // Create a simple ICO-compatible PNG (most browsers accept this)
  const faviconPath = path.join(PUBLIC_DIR, 'favicon.ico');
  await sharp(svgBuffer)
    .resize(32, 32)
    .png()
    .toFile(faviconPath.replace('.ico', '.png'));

  // Rename to .ico (it's technically a PNG but browsers handle it)
  fs.renameSync(faviconPath.replace('.ico', '.png'), faviconPath);
  console.log(`  Created: ${faviconPath}`);
}

async function main() {
  try {
    await generateOGImage();
    await generateFavicon();
    console.log('\nAll assets generated successfully!');
  } catch (error) {
    console.error('Error generating assets:', error);
    process.exit(1);
  }
}

main();
