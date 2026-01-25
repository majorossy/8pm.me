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

module.exports = nextConfig;
