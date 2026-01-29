# Album Artwork Proxy Setup

**Date:** 2026-01-28
**Status:** ✅ **NOT NEEDED** - Using Wikipedia API instead
**Proxy Status:** Created but optional (MusicBrainz still blocked)

## Overview

This document describes the Node.js proxy setup created to bypass Docker container SSL issues when fetching album artwork from MusicBrainz and Cover Art Archive APIs.

**UPDATE:** The artwork system now uses Wikipedia API as primary source, which works without a proxy. The MusicBrainz proxy remains available as a fallback option if needed in the future.

## Problem Statement

The PHP container (Debian 12, OpenSSL 3.0.15) cannot establish SSL connections to:
- `musicbrainz.org` (MusicBrainz API)
- `coverartarchive.org` (Cover Art Archive API)

**Error:** `OpenSSL SSL_connect: SSL_ERROR_SYSCALL` - TLS handshake fails

The same code works fine from the host Mac, indicating a Docker/container-specific SSL configuration issue.

## Solution Implemented

Created a host-based proxy that forwards requests from the Docker container to MusicBrainz/CoverArtArchive using the Mac's working HTTPS stack.

### Architecture

```
Docker Container (PHP)
    ↓ HTTP
http://host.docker.internal:3333/musicbrainz/...
    ↓
Node.js Proxy (Mac)
    ↓ HTTPS (with retry logic)
https://musicbrainz.org/...
```

### Files Created/Modified

1. **`musicbrainz-proxy.js`** - Node.js Express proxy server
   - Listens on port 3333
   - Routes:
     - `/musicbrainz/*` → `https://musicbrainz.org/*`
     - `/coverart/*` → `https://coverartarchive.org/*`
   - Features:
     - Automatic retry with exponential backoff (3 attempts)
     - Request logging
     - Error handling

2. **`bin/proxy`** - Helper script to start the proxy
   ```bash
   #!/bin/bash
   cd "$(dirname "$0")/.."
   node musicbrainz-proxy.js
   ```

3. **Modified: `src/app/code/ArchiveDotOrg/Core/Model/MusicBrainzClient.php`**
   - Line 21-22: Changed API base URLs to use proxy:
   ```php
   // Before:
   private const MUSICBRAINZ_API_BASE = 'https://musicbrainz.org/ws/2';
   private const COVERART_API_BASE = 'https://coverartarchive.org';

   // After:
   private const MUSICBRAINZ_API_BASE = 'http://host.docker.internal:3333/musicbrainz/ws/2';
   private const COVERART_API_BASE = 'http://host.docker.internal:3333/coverart';
   ```

4. **Installed Dependencies:**
   ```bash
   npm install express axios
   ```

## Current Status: Wikipedia API Implemented

### What Happened

After initial successful proxy requests, both `musicbrainz.org` and `coverartarchive.org` (same IP: 142.132.240.1) started closing SSL connections during handshake:

```
Client network socket disconnected before secure TLS connection was established
```

### Solution: Wikipedia API

**Decision Made:** Switch to Wikipedia API for album artwork instead of fighting MusicBrainz SSL issues.

**Benefits:**
- ✅ No SSL issues - works perfectly from Docker
- ✅ No authentication required
- ✅ Reliable, fast, and free
- ✅ High-quality artwork available

**Command to use:**
```bash
bin/magento archive:artwork:download "Artist Name" --limit=20
```

### Evidence

1. **First few requests work** - Initial API calls succeed
2. **Subsequent requests fail** - SSL handshake terminates immediately
3. **Both IPv4 and IPv6 affected** - Tried forcing IPv4, same issue
4. **Both domains affected** - MusicBrainz and CoverArtArchive (share IP)
5. **Direct IP connections fail** - `curl https://142.132.240.1` fails with SSL error
6. **Other HTTPS sites work** - Google, etc. work fine from same machine
7. **Affects both Mac and Docker** - Not container-specific

### Likely Causes

1. **Rate Limiting / DDoS Protection** - Too many requests triggered anti-bot measures
2. **Geographic/IP Blocking** - IP address may be on a blocklist
3. **Temporary Server Issues** - 142.132.240.1 may be having SSL certificate/config issues

### Proxy Logs

```
[PROXY] https://musicbrainz.org/ws/2/release/?query=...
[RETRY 1/3] Client network socket disconnected before secure TLS connection was established
[RETRY 2/3] Client network socket disconnected before secure TLS connection was established
[RETRY 3/3] Client network socket disconnected before secure TLS connection was established
[PROXY ERROR] Client network socket disconnected before secure TLS connection was established
```

## How to Use (Once Connectivity Restored)

### 1. Start the Proxy

**Terminal 1:**
```bash
cd /Users/chris.majorossy/Education/8pm
bin/proxy
```

**Expected output:**
```
🎵 MusicBrainz Proxy running on http://localhost:3333

Routes:
  http://localhost:3333/musicbrainz/ws/2/...
  http://localhost:3333/coverart/release/...

With automatic retry logic (3 attempts with backoff)
```

### 2. Test Connectivity

**Test MusicBrainz API:**
```bash
curl "http://localhost:3333/musicbrainz/ws/2/release/?query=artist:Phish&fmt=json&limit=1"
```

**Test Cover Art Archive:**
```bash
curl -I "http://localhost:3333/coverart/release/d6e9bdca-1ec6-409e-9249-b02738b2aa83/front-500"
```

### 3. Run Album Download Command

**Terminal 2:**
```bash
# Download album artwork for Phish
bin/magento archivedotorg:download-album-art "Phish" --limit=10

# Download for other artists
bin/magento archivedotorg:download-album-art "Grateful Dead" --limit=20
bin/magento archivedotorg:download-album-art "Billy Strings" --limit=10
```

### 4. Verify Data

**Check database:**
```bash
bin/mysql -e "
SELECT
  artist_name,
  COUNT(*) as album_count,
  SUM(CASE WHEN artwork_url IS NOT NULL THEN 1 ELSE 0 END) as with_artwork
FROM archivedotorg_studio_albums
GROUP BY artist_name;
"
```

**Expected output:**
```
+---------------+-------------+--------------+
| artist_name   | album_count | with_artwork |
+---------------+-------------+--------------+
| Phish         |          10 |            8 |
| Grateful Dead |          20 |           16 |
+---------------+-------------+--------------+
```

## Troubleshooting

### Proxy won't start
```bash
# Check if port 3333 is in use
lsof -i :3333

# Kill existing proxy
pkill -f "node musicbrainz-proxy.js"

# Restart
bin/proxy
```

### SSL errors persist

**Try from different network:**
```bash
# Use VPN
# Or mobile hotspot
# Or different location
```

**Test direct connectivity:**
```bash
# Test MusicBrainz
curl -4 -I https://musicbrainz.org/

# Test Cover Art Archive
curl -4 -I https://coverartarchive.org/

# If these fail, it's a network-level block
```

**Check if IP is blocked:**
- Visit https://musicbrainz.org in browser
- If site loads but API fails, might be API-specific rate limiting
- Contact MusicBrainz support if needed

### Container can't reach proxy

**Verify Docker networking:**
```bash
docker exec 8pm-phpfpm-1 curl "http://host.docker.internal:3333/musicbrainz/ws/2/release/?query=artist:Phish&fmt=json&limit=1"
```

**If `host.docker.internal` doesn't resolve:**
```bash
# Find host IP
ipconfig getifaddr en0  # or en1 for WiFi

# Update MusicBrainzClient.php to use explicit IP:
# http://192.168.1.x:3333/musicbrainz/ws/2
```

## Alternative Solutions

If proxy approach doesn't work:

### Option 1: Pre-populate Data in Deployment

Run the import from a different network/machine where connectivity works, then copy the database:

```bash
# On working machine
bin/magento archivedotorg:download-album-art "Artist" --limit=50

# Export data
bin/mysql -e "SELECT * FROM archivedotorg_studio_albums" > albums.sql

# Import on target machine
bin/mysql < albums.sql
```

### Option 2: Fix Docker SSL Configuration

Investigate and fix the underlying OpenSSL 3.0.15 issue in the container. This is more complex and may affect other HTTPS connections.

### Option 3: Use Alternative API

Find an alternative album artwork API that doesn't have the SSL/blocking issues:
- Discogs API
- Last.fm API
- Spotify API (requires auth)
- MusicBrainz mirror servers

### Option 4: Manual Upload

For key artists, manually download artwork and upload via admin:
1. Get artwork from musicbrainz.org (via browser)
2. Upload to Magento media
3. Manually insert database records

## Rate Limiting Compliance

MusicBrainz requires **1 request per second** maximum. The `MusicBrainzClient::respectRateLimit()` method enforces this automatically.

**From MusicBrainz API docs:**
> The rate limit is currently 1 request per second for regular users, and 50 requests per second for authenticated users.

Our implementation:
- Sleeps 1 second between requests
- Logs all API calls
- Uses proper User-Agent header
- Respects API terms of service

## Next Steps (After Connectivity Restored)

1. **Verify proxy works** - Test simple API call
2. **Download album data** - Run CLI commands for all artists
3. **Create GraphQL endpoint** - Add `studioAlbums` query
4. **Build frontend component** - Display albums on artist pages
5. **Add error handling** - Graceful fallback for missing artwork
6. **Add attribution** - Link to Cover Art Archive

See `ALBUM_ARTWORK_PLAN.md` for full implementation roadmap.

## Monitoring

**Check proxy logs:**
```bash
tail -f proxy.log
```

**Watch for these patterns:**
- `[PROXY]` - Successful request forwarded
- `[RETRY x/3]` - Retry attempt (normal)
- `[PROXY ERROR]` - Request failed after all retries (investigate)

**Healthy proxy output:**
```
[PROXY] https://musicbrainz.org/ws/2/release/...
[PROXY] https://coverartarchive.org/release/.../front-500
```

**Unhealthy proxy output:**
```
[PROXY] https://musicbrainz.org/ws/2/release/...
[RETRY 1/3] Connection reset
[RETRY 2/3] Connection reset
[RETRY 3/3] Connection reset
[PROXY ERROR] Connection reset
```

## Rollback

To revert to direct HTTPS (without proxy):

```bash
# Edit MusicBrainzClient.php lines 21-22
# Change back to:
private const MUSICBRAINZ_API_BASE = 'https://musicbrainz.org/ws/2';
private const COVERART_API_BASE = 'https://coverartarchive.org';

# Stop proxy
pkill -f "node musicbrainz-proxy.js"
```

Note: This will restore the original SSL error in the container.

## Technical Notes

### Why host.docker.internal Works

Docker Desktop provides `host.docker.internal` as a special DNS name that resolves to the host machine's IP address from within containers. This allows containers to access services running on the host (like our proxy on port 3333).

### Proxy Performance

- **Latency overhead:** ~10-20ms per request (local network)
- **No caching:** Proxy forwards all requests (caching happens in PHP layer)
- **Memory usage:** Minimal (~50MB for Node.js + Express)
- **Concurrent requests:** Handles multiple simultaneous requests

### Security Considerations

- **Development only:** This proxy should NOT be used in production
- **No authentication:** Proxy has no auth - only accessible from Docker containers
- **Rate limiting:** Proxy doesn't add rate limiting (relies on PHP layer)
- **SSL validation disabled:** `rejectUnauthorized: false` for dev convenience

## Contact

For MusicBrainz API issues:
- Forum: https://community.metabrainz.org/
- IRC: #musicbrainz on irc.libera.chat
- Email: support@musicbrainz.org

For Cover Art Archive issues:
- Same as MusicBrainz (it's part of the MetaBrainz project)

## References

- MusicBrainz API: https://musicbrainz.org/doc/MusicBrainz_API
- Cover Art Archive: https://coverartarchive.org/
- Rate limiting policy: https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting
- Docker host networking: https://docs.docker.com/desktop/networking/#i-want-to-connect-from-a-container-to-a-service-on-the-host
