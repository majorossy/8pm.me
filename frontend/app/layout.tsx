import type { Metadata } from 'next';
import { Orbitron, Space_Mono } from 'next/font/google';
import { GoogleAnalytics } from '@next/third-parties/google';
import './globals.css';
import ClientLayout from '@/components/ClientLayout';

const orbitron = Orbitron({
  subsets: ['latin'],
  variable: '--font-orbitron',
  display: 'swap',
});

const spaceMono = Space_Mono({
  weight: ['400', '700'],
  subsets: ['latin'],
  variable: '--font-space-mono',
  display: 'swap',
});

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001'),
  title: {
    default: 'EIGHTPM - Live Music Archive',
    template: '%s | EIGHTPM',
  },
  description: 'Stream high-quality live concert recordings from legendary artists. Discover thousands of shows from Archive.org.',
  keywords: ['live music', 'concert recordings', 'archive.org', 'streaming', 'grateful dead', 'phish', 'jam bands'],
  openGraph: {
    type: 'website',
    locale: 'en_US',
    siteName: 'EIGHTPM',
  },
  twitter: {
    card: 'summary_large_image',
  },
  manifest: '/manifest.json',
  appleWebApp: {
    capable: true,
    statusBarStyle: 'black-translucent',
    title: 'EIGHTPM',
  },
  formatDetection: {
    telephone: false,
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${orbitron.variable} ${spaceMono.variable}`}>
      <head>
        {/* PWA Meta Tags */}
        <meta name="application-name" content="EIGHTPM" />
        <meta name="theme-color" content="#d4a060" />
        <meta name="mobile-web-app-capable" content="yes" />

        {/* iOS Web App Meta Tags */}
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="apple-mobile-web-app-title" content="EIGHTPM" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no" />

        {/* Apple Touch Icons */}
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png" />
        <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png" />
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png" />
        <link rel="apple-touch-icon" sizes="167x167" href="/icons/icon-152x152.png" />

        {/* iOS Splash Screens - can be generated later */}

        {/* iOS Haptic Feedback Preparation */}
        {/* Note: Add navigator.vibrate() calls at the following touch points:
            - Button press: navigator.vibrate(10) - light tap
            - Swipe complete (expand/collapse): navigator.vibrate([10, 50, 10]) - double tap
            - Delete/remove action: navigator.vibrate(20) - medium tap
            - Play/pause toggle: navigator.vibrate(15) - medium-light tap
        */}
      </head>
      <body className="font-mono">
        <ClientLayout>{children}</ClientLayout>
        {/* Google Analytics 4 - only loads when measurement ID is configured */}
        {process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID && (
          <GoogleAnalytics gaId={process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID} />
        )}
      </body>
    </html>
  );
}
