'use client';

import TwitterWidget from './social-widgets/TwitterWidget';
import InstagramWidget from './social-widgets/InstagramWidget';
import YouTubeWidget from './social-widgets/YouTubeWidget';
import FacebookWidget from './social-widgets/FacebookWidget';
import WebsiteWidget from './social-widgets/WebsiteWidget';

interface BandLinksWidgetProps {
  platform: string;
  artistName: string;
  url?: string;
  onClose: () => void;
}

export default function BandLinksWidget({
  platform,
  artistName,
  url,
  onClose
}: BandLinksWidgetProps) {
  // Render platform-specific widget
  const renderWidget = () => {
    switch (platform) {
      case 'twitter':
        return <TwitterWidget artistName={artistName} url={url} />;
      case 'instagram':
        return <InstagramWidget artistName={artistName} url={url} />;
      case 'youtube':
        return <YouTubeWidget artistName={artistName} url={url} />;
      case 'facebook':
        return <FacebookWidget artistName={artistName} url={url} />;
      case 'website':
        return <WebsiteWidget url={url} />;
      case 'wikipedia':
        return <WebsiteWidget url={url} />;
      default:
        return null;
    }
  };

  // Platform display names
  const platformNames: Record<string, string> = {
    twitter: 'Twitter',
    instagram: 'Instagram',
    youtube: 'YouTube',
    facebook: 'Facebook',
    website: 'Website',
    wikipedia: 'Wikipedia'
  };

  return (
    <div className="band-links-widget">
      {/* Header with close button and "Open in [Platform]" */}
      <div className="widget-header">
        <h3 className="widget-title">
          #{artistName} on {platformNames[platform] || platform}
        </h3>
        <div className="widget-actions">
          {url && (
            <a
              href={url}
              target="_blank"
              rel="noopener noreferrer"
              className="open-external-btn"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                <polyline points="15 3 21 3 21 9" />
                <line x1="10" y1="14" x2="21" y2="3" />
              </svg>
              <span>Open in {platformNames[platform]}</span>
            </a>
          )}
          <button onClick={onClose} className="close-btn" aria-label="Close widget">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="20" height="20">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>
      </div>

      {/* Widget content */}
      <div className="widget-content">
        {renderWidget()}
      </div>

      {/* Theme-specific styling */}
      <style jsx>{`
        .band-links-widget {
          margin-top: 1rem;
          border-radius: 8px;
          overflow: hidden;
          animation: slideDown 0.3s ease-out;
        }

        /* Tron theme */
        :global(.theme-tron) .band-links-widget {
          background: rgba(0, 0, 0, 0.8);
          border: 1px solid #00ffff;
          box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        /* Metro theme */
        :global(.theme-metro) .band-links-widget {
          background: white;
          border: 1px solid #d4d0c8;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Jamify theme */
        :global(.theme-jamify) .band-links-widget {
          background: #181818;
          border: 1px solid #282828;
        }

        @keyframes slideDown {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .widget-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 1rem;
          gap: 1rem;
        }

        .widget-title {
          margin: 0;
          font-size: 1rem;
          font-weight: 600;
          flex: 1;
        }

        /* Tron theme header */
        :global(.theme-tron) .widget-header {
          border-bottom: 1px solid rgba(0, 255, 255, 0.3);
        }

        :global(.theme-tron) .widget-title {
          color: #00ffff;
          text-shadow: 0 0 8px rgba(0, 255, 255, 0.5);
        }

        /* Metro theme header */
        :global(.theme-metro) .widget-header {
          border-bottom: 1px solid #e0e0e0;
        }

        :global(.theme-metro) .widget-title {
          color: #333333;
        }

        /* Jamify theme header */
        :global(.theme-jamify) .widget-header {
          border-bottom: 1px solid #282828;
        }

        :global(.theme-jamify) .widget-title {
          color: rgba(255, 255, 255, 0.9);
        }

        .widget-actions {
          display: flex;
          gap: 0.5rem;
          align-items: center;
        }

        .open-external-btn {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          padding: 0.5rem 1rem;
          border-radius: 6px;
          text-decoration: none;
          font-size: 0.875rem;
          font-weight: 500;
          transition: all 0.2s ease;
          white-space: nowrap;
        }

        /* Tron external button */
        :global(.theme-tron) .open-external-btn {
          background: rgba(0, 255, 255, 0.1);
          border: 1px solid rgba(0, 255, 255, 0.3);
          color: #00ffff;
        }

        :global(.theme-tron) .open-external-btn:hover {
          background: rgba(0, 255, 255, 0.2);
          border-color: #00ffff;
        }

        /* Metro external button */
        :global(.theme-metro) .open-external-btn {
          background: #f97316;
          border: 1px solid #f97316;
          color: white;
        }

        :global(.theme-metro) .open-external-btn:hover {
          background: #ea580c;
          border-color: #ea580c;
        }

        /* Jamify external button */
        :global(.theme-jamify) .open-external-btn {
          background: rgba(34, 197, 94, 0.1);
          border: 1px solid rgba(34, 197, 94, 0.3);
          color: #22c55e;
        }

        :global(.theme-jamify) .open-external-btn:hover {
          background: rgba(34, 197, 94, 0.2);
          border-color: #22c55e;
        }

        .close-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 0.5rem;
          border: none;
          border-radius: 6px;
          background: transparent;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        /* Tron close button */
        :global(.theme-tron) .close-btn {
          color: #00ffff;
        }

        :global(.theme-tron) .close-btn:hover {
          background: rgba(0, 255, 255, 0.2);
        }

        /* Metro close button */
        :global(.theme-metro) .close-btn {
          color: #666666;
        }

        :global(.theme-metro) .close-btn:hover {
          background: #f5f5f5;
          color: #333333;
        }

        /* Jamify close button */
        :global(.theme-jamify) .close-btn {
          color: rgba(255, 255, 255, 0.7);
        }

        :global(.theme-jamify) .close-btn:hover {
          background: rgba(255, 255, 255, 0.1);
          color: rgba(255, 255, 255, 0.9);
        }

        .widget-content {
          padding: 1rem;
          max-height: 500px;
          overflow-y: auto;
        }

        /* Tron scrollbar */
        :global(.theme-tron) .widget-content::-webkit-scrollbar {
          width: 8px;
        }

        :global(.theme-tron) .widget-content::-webkit-scrollbar-track {
          background: rgba(0, 0, 0, 0.5);
        }

        :global(.theme-tron) .widget-content::-webkit-scrollbar-thumb {
          background: rgba(0, 255, 255, 0.3);
          border-radius: 4px;
        }

        :global(.theme-tron) .widget-content::-webkit-scrollbar-thumb:hover {
          background: rgba(0, 255, 255, 0.5);
        }

        /* Metro scrollbar */
        :global(.theme-metro) .widget-content::-webkit-scrollbar {
          width: 8px;
        }

        :global(.theme-metro) .widget-content::-webkit-scrollbar-track {
          background: #f5f5f5;
        }

        :global(.theme-metro) .widget-content::-webkit-scrollbar-thumb {
          background: #d4d0c8;
          border-radius: 4px;
        }

        :global(.theme-metro) .widget-content::-webkit-scrollbar-thumb:hover {
          background: #b0b0b0;
        }

        /* Jamify scrollbar */
        :global(.theme-jamify) .widget-content::-webkit-scrollbar {
          width: 8px;
        }

        :global(.theme-jamify) .widget-content::-webkit-scrollbar-track {
          background: rgba(0, 0, 0, 0.3);
        }

        :global(.theme-jamify) .widget-content::-webkit-scrollbar-thumb {
          background: rgba(255, 255, 255, 0.2);
          border-radius: 4px;
        }

        :global(.theme-jamify) .widget-content::-webkit-scrollbar-thumb:hover {
          background: rgba(255, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 640px) {
          .widget-header {
            flex-direction: column;
            align-items: flex-start;
          }

          .widget-actions {
            width: 100%;
            justify-content: space-between;
          }

          .open-external-btn span {
            display: none;
          }

          .open-external-btn {
            padding: 0.5rem;
          }
        }
      `}</style>
    </div>
  );
}
