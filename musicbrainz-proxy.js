const express = require('express');
const axios = require('axios');
const app = express();

const PORT = 3333;

// Simple retry wrapper
async function fetchWithRetry(url, options, retries = 3) {
  for (let i = 0; i < retries; i++) {
    try {
      return await axios(url, options);
    } catch (error) {
      console.error(`[RETRY ${i + 1}/${retries}] ${error.message}`);
      if (i === retries - 1) throw error;
      // Wait before retry (exponential backoff)
      await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, i)));
    }
  }
}

// Proxy for MusicBrainz API - matches anything under /musicbrainz/
app.use('/musicbrainz', async (req, res) => {
  try {
    const mbUrl = `https://musicbrainz.org${req.path}`;
    const fullUrl = mbUrl + (req.url.includes('?') ? '?' + req.url.split('?')[1] : '');

    console.log(`[PROXY] ${fullUrl}`);

    const response = await fetchWithRetry(mbUrl, {
      method: 'GET',
      params: req.query,
      headers: {
        'User-Agent': req.headers['user-agent'] || 'ArchiveDotOrg-Proxy/1.0',
        'Accept': req.headers['accept'] || 'application/json'
      },
      timeout: 30000
    });

    res.status(response.status).send(response.data);
  } catch (error) {
    console.error('[PROXY ERROR]', error.message);
    const status = error.response?.status || 500;
    const message = error.response?.data || error.message;
    res.status(status).send(message);
  }
});

// Proxy for Cover Art Archive - matches anything under /coverart/
app.use('/coverart', async (req, res) => {
  try {
    const coverartUrl = `https://coverartarchive.org${req.path}`;

    console.log(`[PROXY] ${coverartUrl}`);

    const response = await fetchWithRetry(coverartUrl, {
      method: req.method.toLowerCase(),
      headers: {
        'Accept': req.headers['accept'] || 'image/*'
      },
      responseType: 'arraybuffer',
      timeout: 30000,
      maxRedirects: 5
    });

    // Forward image data
    res.status(response.status)
      .set('Content-Type', response.headers['content-type'])
      .send(response.data);

  } catch (error) {
    console.error('[PROXY ERROR]', error.message);
    const status = error.response?.status || 500;
    const message = error.response?.data || error.message;
    res.status(status).send(message);
  }
});

app.listen(PORT, () => {
  console.log(`\n🎵 MusicBrainz Proxy running on http://localhost:${PORT}\n`);
  console.log('Routes:');
  console.log('  http://localhost:3333/musicbrainz/ws/2/...');
  console.log('  http://localhost:3333/coverart/release/...\n');
  console.log('With automatic retry logic (3 attempts with backoff)\n');
});
