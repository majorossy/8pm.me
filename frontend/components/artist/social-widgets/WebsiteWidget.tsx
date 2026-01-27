'use client';

import { useState } from 'react';

interface WebsiteWidgetProps {
  url?: string;
}

export default function WebsiteWidget({ url }: WebsiteWidgetProps) {
  const [iframeError, setIframeError] = useState(false);

  if (!url) {
    return <p className="info-message">No website URL provided</p>;
  }

  // Handle iframe load errors
  const handleIframeError = () => {
    setIframeError(true);
  };

  return (
    <div className="website-widget-container">
      {!iframeError ? (
        <div className="website-iframe-wrapper">
          <iframe
            src={url}
            width="100%"
            height="400"
            title="Artist website"
            sandbox="allow-same-origin allow-scripts allow-popups"
            onError={handleIframeError}
          ></iframe>
        </div>
      ) : (
        <div className="iframe-fallback">
          <p className="info-message">
            Cannot embed this website. Click below to visit directly:
          </p>
          <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            className="website-link"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="24" height="24">
              <circle cx="12" cy="12" r="10" />
              <line x1="2" y1="12" x2="22" y2="12" />
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            </svg>
            <span>Visit website →</span>
          </a>
        </div>
      )}

      <style jsx>{`
        .website-widget-container {
          padding: 0;
        }

        .website-iframe-wrapper {
          position: relative;
          width: 100%;
          border-radius: 8px;
          overflow: hidden;
        }

        .website-iframe-wrapper iframe {
          display: block;
          border: none;
        }

        .iframe-fallback {
          padding: 2rem 0;
          text-align: center;
        }

        .info-message {
          margin-bottom: 1.5rem;
          opacity: 0.7;
          font-size: 0.875rem;
        }

        .website-link {
          display: inline-flex;
          align-items: center;
          gap: 0.75rem;
          padding: 1rem 1.5rem;
          border-radius: 8px;
          text-decoration: none;
          transition: all 0.3s ease;
          font-weight: 500;
        }

        /* Tron Theme */
        :global(.theme-tron) .website-iframe-wrapper {
          border: 1px solid rgba(0, 255, 255, 0.3);
          box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
        }

        :global(.theme-tron) .website-link {
          background: rgba(0, 255, 255, 0.05);
          border: 1px solid rgba(0, 255, 255, 0.2);
          color: #00ffff;
        }

        :global(.theme-tron) .website-link:hover {
          background: rgba(0, 255, 255, 0.1);
          border-color: #00ffff;
          transform: translateY(-2px);
        }

        /* Metro Theme */
        :global(.theme-metro) .website-iframe-wrapper {
          border: 1px solid #e0e0e0;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        :global(.theme-metro) .website-link {
          background: #f5f5f5;
          border: 1px solid #e0e0e0;
          color: #333333;
        }

        :global(.theme-metro) .website-link:hover {
          background: #f97316;
          border-color: #f97316;
          color: white;
          transform: translateY(-2px);
        }

        /* Jamify Theme */
        :global(.theme-jamify) .website-iframe-wrapper {
          border: 1px solid rgba(255, 255, 255, 0.1);
        }

        :global(.theme-jamify) .website-link {
          background: rgba(255, 255, 255, 0.05);
          border: 1px solid rgba(255, 255, 255, 0.1);
          color: rgba(255, 255, 255, 0.9);
        }

        :global(.theme-jamify) .website-link:hover {
          background: rgba(34, 197, 94, 0.1);
          border-color: #22c55e;
          color: #22c55e;
          transform: translateY(-2px);
        }
      `}</style>
    </div>
  );
}
