'use client';

import { useEffect, useState } from 'react';

interface TwitterWidgetProps {
  artistName: string;
  url?: string;
}

export default function TwitterWidget({ artistName, url }: TwitterWidgetProps) {
  const [loading, setLoading] = useState(true);

  // Extract username from URL
  const username = url?.split('twitter.com/')[1]?.split('/')[0] || url?.split('x.com/')[1]?.split('/')[0];

  useEffect(() => {
    // Check if script already exists
    if (document.querySelector('script[src*="platform.twitter.com"]')) {
      setLoading(false);
      return;
    }

    // Load Twitter widgets script
    const script = document.createElement('script');
    script.src = 'https://platform.twitter.com/widgets.js';
    script.async = true;
    script.charset = 'utf-8';
    document.body.appendChild(script);

    script.onload = () => setLoading(false);

    return () => {
      // Don't remove the script on unmount as it's shared
    };
  }, []);

  if (!username) {
    return <p className="info-message">No Twitter profile found</p>;
  }

  return (
    <div className="twitter-widget-container">
      {loading && <p className="info-message">Loading tweets...</p>}

      {/* Twitter Timeline Embed */}
      <a
        className="twitter-timeline"
        data-height="400"
        data-theme="dark"
        href={`https://twitter.com/${username}`}
      >
        Tweets by @{username}
      </a>

      <style jsx>{`
        .twitter-widget-container {
          min-height: 400px;
        }

        .info-message {
          text-align: center;
          padding: 2rem;
          opacity: 0.7;
        }
      `}</style>
    </div>
  );
}
