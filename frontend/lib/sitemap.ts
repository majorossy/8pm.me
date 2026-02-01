const GRAPHQL_URL = process.env.MAGENTO_GRAPHQL_URL || 'https://magento.test/graphql';

async function fetchGraphQL(query: string, variables: Record<string, unknown> = {}) {
  const response = await fetch(GRAPHQL_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ query, variables }),
    next: { revalidate: 3600 },
  });
  return response.json();
}

interface ArtistCategory {
  url_key: string;
  updated_at?: string;
}

interface AlbumCategory {
  url_key: string;
  url_path?: string;
  updated_at?: string;
}

export async function getAllArtists(): Promise<ArtistCategory[]> {
  const query = `
    query GetArtists($pageSize: Int!, $currentPage: Int!) {
      categories(filters: { parent_id: { eq: "48" } }, pageSize: $pageSize, currentPage: $currentPage) {
        items {
          url_key
          updated_at
        }
        total_count
      }
    }
  `;

  const artists: ArtistCategory[] = [];
  let currentPage = 1;
  const pageSize = 50;
  let hasMore = true;

  while (hasMore) {
    const response = await fetchGraphQL(query, { pageSize, currentPage });
    const page = response?.data?.categories?.items || [];
    artists.push(...page);
    hasMore = page.length >= pageSize;
    currentPage++;
  }

  return artists;
}

export async function getAllAlbumsPaginated(): Promise<AlbumCategory[]> {
  const albums: AlbumCategory[] = [];
  let currentPage = 1;
  const pageSize = 100;
  let hasMore = true;

  const query = `
    query GetAlbums($pageSize: Int!, $currentPage: Int!) {
      categories(
        filters: { is_album: { eq: "1" } }
        pageSize: $pageSize
        currentPage: $currentPage
      ) {
        items {
          url_key
          url_path
          updated_at
        }
        total_count
      }
    }
  `;

  while (hasMore) {
    const response = await fetchGraphQL(query, { pageSize, currentPage });
    const page = response?.data?.categories?.items || [];
    albums.push(...page);
    hasMore = page.length >= pageSize;
    currentPage++;
  }

  return albums;
}
