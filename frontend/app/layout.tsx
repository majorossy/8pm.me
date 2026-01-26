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
      <body className="font-mono">
        <ClientLayout>{children}</ClientLayout>
      </body>
    </html>
  );
}
