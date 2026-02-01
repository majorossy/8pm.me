import { MetadataRoute } from 'next';
import { getAllArtists, getAllAlbumsPaginated } from '@/lib/sitemap';

export const revalidate = 3600; // Regenerate every hour

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3001';

  // Static pages
  const staticPages: MetadataRoute.Sitemap = [
    { url: baseUrl, lastModified: new Date(), changeFrequency: 'daily', priority: 1 },
    { url: `${baseUrl}/artists`, lastModified: new Date(), changeFrequency: 'weekly', priority: 0.9 },
    { url: `${baseUrl}/library`, lastModified: new Date(), changeFrequency: 'weekly', priority: 0.8 },
  ];

  // Artist pages
  const artists = await getAllArtists();
  const artistPages: MetadataRoute.Sitemap = artists.map((artist) => ({
    url: `${baseUrl}/artists/${artist.url_key}`,
    lastModified: artist.updated_at ? new Date(artist.updated_at) : new Date(),
    changeFrequency: 'monthly' as const,
    priority: 0.8,
  }));

  // Album pages (paginated fetch - avoids N+1)
  const albums = await getAllAlbumsPaginated();
  const albumPages: MetadataRoute.Sitemap = albums.map((album) => {
    // url_path format: "artists/artistslug/albumslug"
    const pathParts = album.url_path?.split('/') || [];
    const artistSlug = pathParts[1] || 'unknown';
    return {
      url: `${baseUrl}/artists/${artistSlug}/album/${album.url_key}`,
      lastModified: album.updated_at ? new Date(album.updated_at) : new Date(),
      changeFrequency: 'never' as const,
      priority: 0.7,
    };
  });

  console.log(`Sitemap: ${staticPages.length} static, ${artistPages.length} artists, ${albumPages.length} albums`);

  return [...staticPages, ...artistPages, ...albumPages];
}
