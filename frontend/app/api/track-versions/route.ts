import { NextRequest, NextResponse } from 'next/server';
import { getVersionsForTrack } from '@/lib/api';
import { apiRateLimit } from '@/lib/rateLimit';
import { validateUid } from '@/lib/validation';

export async function GET(request: NextRequest) {
  // Check rate limit
  const rateLimitResult = await apiRateLimit.check(request, 'track-versions');
  if (!rateLimitResult.success) {
    return rateLimitResult.response;
  }

  const searchParams = request.nextUrl.searchParams;
  const trackUid = searchParams.get('uid');

  if (!trackUid) {
    return NextResponse.json(
      { error: 'Track UID is required' },
      { status: 400 }
    );
  }

  // Input validation: UID should have valid format and length
  const validationError = validateUid(trackUid);
  if (validationError) {
    return NextResponse.json(
      { error: 'Invalid track UID' },
      { status: 400 }
    );
  }

  try {
    const versions = await getVersionsForTrack(trackUid);
    const response = NextResponse.json(versions);
    return apiRateLimit.addHeaders(response, rateLimitResult);
  } catch {
    return NextResponse.json(
      { error: 'Failed to fetch track versions' },
      { status: 500 }
    );
  }
}
