import { NextRequest, NextResponse } from 'next/server';
import { search } from '@/lib/api';
import { searchRateLimit } from '@/lib/rateLimit';
import { validateSearchQuery, VALIDATION_LIMITS } from '@/lib/validation';

export async function GET(request: NextRequest) {
  // Check rate limit
  const rateLimitResult = await searchRateLimit.check(request);
  if (!rateLimitResult.success) {
    return rateLimitResult.response;
  }

  const searchParams = request.nextUrl.searchParams;
  const query = searchParams.get('q');

  if (!query) {
    const response = NextResponse.json({ artists: [], albums: [], tracks: [] });
    return searchRateLimit.addHeaders(response, rateLimitResult);
  }

  // Input validation: check query length and format
  const validationError = validateSearchQuery(query);
  if (validationError) {
    return NextResponse.json(
      { error: validationError },
      { status: 400 }
    );
  }

  // Additional security: trim the query
  const sanitizedQuery = query.trim().slice(0, VALIDATION_LIMITS.SEARCH_QUERY_MAX);

  try {
    const results = await search(sanitizedQuery);
    const response = NextResponse.json(results);
    return searchRateLimit.addHeaders(response, rateLimitResult);
  } catch {
    return NextResponse.json(
      { error: 'Search failed' },
      { status: 500 }
    );
  }
}
