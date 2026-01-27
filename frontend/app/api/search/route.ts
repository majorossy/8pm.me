import { NextRequest, NextResponse } from 'next/server';
import { search } from '@/lib/api';

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const query = searchParams.get('q');

  if (!query) {
    return NextResponse.json({ artists: [], albums: [], tracks: [] });
  }

  try {
    const results = await search(query);
    return NextResponse.json(results);
  } catch (error) {
    console.error('[API] Search failed:', error);
    return NextResponse.json(
      { error: 'Search failed' },
      { status: 500 }
    );
  }
}
