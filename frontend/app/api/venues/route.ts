import { NextResponse } from 'next/server';

const GRAPHQL_ENDPOINT = process.env.MAGENTO_GRAPHQL_URL || 'https://magento.test/graphql';

// Query to fetch unique venues from products using aggregations
const GET_VENUE_AGGREGATIONS = `
  query GetVenueAggregations {
    products(
      filter: { show_venue: { neq: "" } }
      pageSize: 1
    ) {
      aggregations {
        attribute_code
        label
        options {
          label
          value
          count
        }
      }
    }
  }
`;

// Fallback: fetch products and extract unique venues
// Use "the" as search term - common in show/venue names
const GET_PRODUCTS_WITH_VENUES = `
  query GetProductsWithVenues($pageSize: Int!) {
    products(
      search: "the"
      pageSize: $pageSize
    ) {
      total_count
      items {
        show_venue
      }
    }
  }
`;

export async function GET() {
  try {
    // First try aggregations (more efficient if available)
    const aggResponse = await fetch(GRAPHQL_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query: GET_VENUE_AGGREGATIONS }),
      cache: 'force-cache',
      next: { revalidate: 3600 }, // Cache for 1 hour
    });

    const aggData = await aggResponse.json();

    // Check if show_venue aggregation is available
    const venueAgg = aggData?.data?.products?.aggregations?.find(
      (agg: { attribute_code: string }) => agg.attribute_code === 'show_venue'
    );

    if (venueAgg?.options?.length > 0) {
      const venues = venueAgg.options
        .map((opt: { label: string }) => opt.label)
        .filter((v: string) => v && v.trim())
        .sort();

      return NextResponse.json({ venues, source: 'aggregations' });
    }

    // Fallback: fetch products and extract unique venues
    // This is less efficient but works if aggregations aren't set up
    const productResponse = await fetch(GRAPHQL_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        query: GET_PRODUCTS_WITH_VENUES,
        variables: { pageSize: 500 } // Fetch up to 500 to get diverse venues
      }),
      cache: 'force-cache',
      next: { revalidate: 3600 },
    });

    const productData = await productResponse.json();
    const products = productData?.data?.products?.items || [];

    const venueSet = new Set<string>();
    for (const product of products) {
      if (product.show_venue && product.show_venue.trim()) {
        venueSet.add(product.show_venue.trim());
      }
    }

    const venues = Array.from(venueSet).sort();

    return NextResponse.json({
      venues,
      source: 'products',
      totalProducts: productData?.data?.products?.total_count
    });

  } catch (error) {
    console.error('[api/venues] Error fetching venues:', error);
    return NextResponse.json({ venues: [], error: 'Failed to fetch venues' }, { status: 500 });
  }
}
