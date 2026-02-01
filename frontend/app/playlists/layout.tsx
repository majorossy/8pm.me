import { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Playlists',
  description: 'Create and manage your playlists on 8PM. Organize your favorite live concert recordings from Archive.org.',
  alternates: {
    canonical: '/playlists',
  },
};

export default function PlaylistsLayout({ children }: { children: React.ReactNode }) {
  return children;
}
