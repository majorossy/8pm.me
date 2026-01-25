import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { CartProvider } from '@/context/CartContext';
import { WishlistProvider } from '@/context/WishlistContext';
import { PlayerProvider } from '@/context/PlayerContext';
import BottomPlayer from '@/components/BottomPlayer';
import Queue from '@/components/Queue';
import Link from 'next/link';

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'EightPM - Music Store',
  description: 'Discover and listen to amazing music',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className={inter.className}>
        <CartProvider>
          <WishlistProvider>
            <PlayerProvider>
              {/* Header */}
              <header className="sticky top-0 z-30 bg-dark-900/95 backdrop-blur-sm border-b border-dark-700">
                <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
                  <Link href="/" className="flex items-center gap-2">
                    <div className="w-8 h-8 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center">
                      <span className="text-white font-bold text-sm">8</span>
                    </div>
                    <span className="text-xl font-bold text-white">EightPM</span>
                  </Link>
                  <nav className="flex items-center gap-6">
                    <Link
                      href="/"
                      className="text-gray-400 hover:text-white transition-colors"
                    >
                      Home
                    </Link>
                    <Link
                      href="/artists"
                      className="text-gray-400 hover:text-white transition-colors"
                    >
                      Artists
                    </Link>
                  </nav>
                </div>
              </header>

              {/* Main content */}
              <main className="pb-24">
                {children}
              </main>

              {/* Player components */}
              <BottomPlayer />
              <Queue />
            </PlayerProvider>
          </WishlistProvider>
        </CartProvider>
      </body>
    </html>
  );
}
