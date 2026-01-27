import type { Metadata } from 'next';
import { Orbitron, Space_Mono } from 'next/font/google';
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
  title: 'EIGHTPM - Music Archive',
  description: 'Discover and stream live music recordings',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${orbitron.variable} ${spaceMono.variable}`}>
      <head>
        {/* iOS Web App Meta Tags */}
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no" />

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
      </body>
    </html>
  );
}
