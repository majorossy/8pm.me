'use client';

import { useEffect, useState } from 'react';
import Script from 'next/script';
import { hasAnalyticsConsent, getStoredConsent } from '@/hooks/useCookieConsent';

/**
 * Conditionally loads Google Analytics 4 based on user's cookie consent.
 * Only loads the GA4 script when the user has explicitly accepted analytics cookies.
 *
 * This component listens for cookie consent updates and loads/unloads GA4 accordingly.
 */
export default function ConditionalAnalytics() {
  const [shouldLoad, setShouldLoad] = useState(false);
  const measurementId = process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID;

  useEffect(() => {
    // Check initial consent state
    setShouldLoad(hasAnalyticsConsent());

    // Listen for consent updates
    const handleConsentUpdate = (event: Event) => {
      const customEvent = event as CustomEvent;
      const consent = customEvent.detail;
      setShouldLoad(consent?.analytics ?? false);
    };

    window.addEventListener('cookieConsentUpdate', handleConsentUpdate);

    return () => {
      window.removeEventListener('cookieConsentUpdate', handleConsentUpdate);
    };
  }, []);

  // Don't render anything if no measurement ID or no consent
  if (!measurementId || !shouldLoad) {
    return null;
  }

  return (
    <>
      {/* Google Analytics 4 */}
      <Script
        src={`https://www.googletagmanager.com/gtag/js?id=${measurementId}`}
        strategy="afterInteractive"
      />
      <Script id="google-analytics" strategy="afterInteractive">
        {`
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '${measurementId}', {
            page_path: window.location.pathname,
            anonymize_ip: true,
            cookie_flags: 'SameSite=None;Secure'
          });
        `}
      </Script>
    </>
  );
}
