import { Metadata } from 'next';

interface SeoData {
  title?: string;
  description?: string;
  keywords?: string;
  path?: string;
  image?: string;
  type?: 'website' | 'music.song' | 'music.album' | 'profile';
}

export function getBaseUrl(): string {
  return process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001';
}

export function getCanonicalUrl(path: string): string {
  const base = getBaseUrl();
  const cleanPath = path.startsWith('/') ? path : `/${path}`;
  return `${base}${cleanPath}`;
}

export function generateSeoMetadata(data: SeoData): Metadata {
  const baseUrl = getBaseUrl();
  const fullUrl = data.path ? `${baseUrl}${data.path}` : baseUrl;
  const ogImage = data.image || `${baseUrl}/images/og-default.jpg`;

  return {
    title: data.title || '8pm.me - Live Music Archive',
    description: data.description || 'Stream live concert recordings from Archive.org',
    keywords: data.keywords,
    alternates: {
      canonical: fullUrl,
    },
    openGraph: {
      title: data.title,
      description: data.description,
      url: fullUrl,
      siteName: '8pm.me',
      images: [{ url: ogImage, width: 1200, height: 630, alt: data.title || '8pm.me' }],
      type: data.type || 'website',
    },
    twitter: {
      card: 'summary_large_image',
      title: data.title,
      description: data.description,
      images: [ogImage],
    },
  };
}
