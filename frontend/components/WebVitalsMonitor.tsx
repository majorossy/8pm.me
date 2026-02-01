'use client';

import { useEffect } from 'react';
import { onCLS, onFCP, onINP, onLCP, onTTFB, type Metric } from 'web-vitals';

/**
 * WebVitalsMonitor - Reports Core Web Vitals metrics
 *
 * Metrics tracked:
 * - CLS (Cumulative Layout Shift) - Visual stability
 * - FCP (First Contentful Paint) - Time to first content
 * - INP (Interaction to Next Paint) - Responsiveness (replaced FID)
 * - LCP (Largest Contentful Paint) - Loading performance
 * - TTFB (Time to First Byte) - Server response time
 *
 * In development: Logs to console
 * In production: Can send to analytics endpoint
 */

function reportMetric(metric: Metric) {
  // Log in development
  if (process.env.NODE_ENV === 'development') {
    const rating = metric.rating || 'unknown';
    const color = rating === 'good' ? '🟢' : rating === 'needs-improvement' ? '🟡' : '🔴';
    console.log(`[WebVitals] ${color} ${metric.name}: ${metric.value.toFixed(2)} (${rating})`);
  }

  // In production, send to analytics
  // Uncomment and configure when analytics is set up:
  /*
  if (process.env.NODE_ENV === 'production') {
    const body = JSON.stringify({
      name: metric.name,
      value: metric.value,
      rating: metric.rating,
      delta: metric.delta,
      id: metric.id,
      navigationType: metric.navigationType,
    });

    // Use sendBeacon for reliability during page unload
    if (navigator.sendBeacon) {
      navigator.sendBeacon('/api/analytics/vitals', body);
    } else {
      fetch('/api/analytics/vitals', {
        method: 'POST',
        body,
        keepalive: true,
        headers: { 'Content-Type': 'application/json' },
      });
    }
  }
  */
}

export default function WebVitalsMonitor() {
  useEffect(() => {
    // Register all Core Web Vitals metrics
    onCLS(reportMetric);
    onFCP(reportMetric);
    onINP(reportMetric);
    onLCP(reportMetric);
    onTTFB(reportMetric);
  }, []);

  // This component renders nothing
  return null;
}
