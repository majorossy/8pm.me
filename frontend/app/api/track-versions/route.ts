import { NextRequest, NextResponse } from 'next/server';
import { getVersionsForTrack } from '@/lib/api';

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const trackUid = searchParams.get('uid');

  if (!trackUid) {
    return NextResponse.json(
      { error: 'Track UID is required' },
      { status: 400 }
    );
  }

  try {
    const versions = await getVersionsForTrack(trackUid);
    return NextResponse.json(versions);
  } catch {
    return NextResponse.json(
      { error: 'Failed to fetch track versions' },
      { status: 500 }
    );
  }
}
