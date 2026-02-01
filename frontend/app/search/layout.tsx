import { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Search',
  description: 'Search for live concert recordings by artist, venue, date, or song. Discover thousands of free recordings from Archive.org.',
  alternates: {
    canonical: '/search',
  },
};

export default function SearchLayout({ children }: { children: React.ReactNode }) {
  return children;
}
