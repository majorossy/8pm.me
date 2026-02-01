import { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Our Tapers',
  description: 'Thank you to the tapers who recorded and shared these live concert recordings with the world. Explore the community that preserves live music.',
  alternates: {
    canonical: '/tapers',
  },
};

export default function TapersLayout({ children }: { children: React.ReactNode }) {
  return children;
}
