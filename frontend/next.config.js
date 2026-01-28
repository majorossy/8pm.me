const withPWA = require('next-pwa')({
  dest: 'public',
  disable: process.env.NODE_ENV === 'development',
  register: true,
  skipWaiting: true,
  runtimeCaching: [
    // Audio files - cache first, 1 week
    {
      urlPattern: /^https?:\/\/.*\.(?:mp3|flac|ogg|wav|m4a)$/i,
      handler: 'CacheFirst',
      options: {
        cacheName: 'audio-cache',
        expiration: {
          maxEntries: 50,
          maxAgeSeconds: 60 * 60 * 24 * 7, // 1 week
        },
        cacheableResponse: {
          statuses: [0, 200],
        },
      },
    },
    // Images - cache first, 30 days
    {
      urlPattern: /^https?:\/\/.*\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/i,
      handler: 'CacheFirst',
      options: {
        cacheName: 'image-cache',
        expiration: {
          maxEntries: 100,
          maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
        },
        cacheableResponse: {
          statuses: [0, 200],
        },
      },
    },
    // Google Fonts
    {
      urlPattern: /^https:\/\/fonts\.(?:gstatic|googleapis)\.com\/.*/i,
      handler: 'CacheFirst',
      options: {
        cacheName: 'google-fonts',
        expiration: {
          maxEntries: 30,
          maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
        },
        cacheableResponse: {
          statuses: [0, 200],
        },
      },
    },
    // API calls - network first, 5 min cache
    {
      urlPattern: /\/api\/.*/i,
      handler: 'NetworkFirst',
      options: {
        cacheName: 'api-cache',
        expiration: {
          maxEntries: 50,
          maxAgeSeconds: 60 * 5, // 5 minutes
        },
        networkTimeoutSeconds: 10,
        cacheableResponse: {
          statuses: [0, 200],
        },
      },
    },
    // GraphQL endpoint - network first
    {
      urlPattern: /\/graphql$/i,
      handler: 'NetworkFirst',
      options: {
        cacheName: 'graphql-cache',
        expiration: {
          maxEntries: 50,
          maxAgeSeconds: 60 * 5, // 5 minutes
        },
        networkTimeoutSeconds: 10,
        cacheableResponse: {
          statuses: [0, 200],
        },
      },
    },
    // Static assets from same origin
    {
      urlPattern: /^https?:\/\/localhost:3001\/_next\/static\/.*/i,
      handler: 'CacheFirst',
      options: {
        cacheName: 'static-resources',
        expiration: {
          maxEntries: 100,
          maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
        },
      },
    },
    // Default - stale while revalidate
    {
      urlPattern: /.*/i,
      handler: 'StaleWhileRevalidate',
      options: {
        cacheName: 'default-cache',
        expiration: {
          maxEntries: 200,
          maxAgeSeconds: 60 * 60 * 24, // 1 day
        },
      },
    },
  ],
});

/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    domains: ['localhost', 'magento.test'],
    unoptimized: true,
  },
  env: {
    MAGENTO_GRAPHQL_URL: process.env.MAGENTO_GRAPHQL_URL || 'https://app:8443/graphql',
  },
};

module.exports = withPWA(nextConfig);
