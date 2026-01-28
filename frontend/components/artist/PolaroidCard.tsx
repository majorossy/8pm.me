'use client';

import { useState } from 'react';

interface SocialLink {
  icon: string;
  name: string;
  url: string;
}

interface PolaroidCardProps {
  imageUrl?: string;
  artistName: string;
  caption?: string;
  socialLinks?: {
    website?: string;
    youtube?: string;
    facebook?: string;
    instagram?: string;
    twitter?: string;
  };
}

export default function PolaroidCard({ imageUrl, artistName, caption, socialLinks }: PolaroidCardProps) {
  const [isFlipped, setIsFlipped] = useState(false);

  // Build social links array from props
  const socials: SocialLink[] = [];
  if (socialLinks?.website) {
    socials.push({ icon: '🌐', name: 'Website', url: socialLinks.website });
  }
  if (socialLinks?.facebook) {
    socials.push({ icon: '📘', name: 'Facebook', url: socialLinks.facebook });
  }
  if (socialLinks?.twitter) {
    socials.push({ icon: '𝕏', name: 'Twitter', url: socialLinks.twitter });
  }
  if (socialLinks?.instagram) {
    socials.push({ icon: '📷', name: 'Instagram', url: socialLinks.instagram });
  }
  if (socialLinks?.youtube) {
    socials.push({ icon: '▶', name: 'YouTube', url: socialLinks.youtube });
  }

  const hasSocials = socials.length > 0;

  return (
    <div
      className="relative w-[200px] md:w-[240px] flex-shrink-0"
      style={{ perspective: '1000px' }}
    >
      {/* Push pin */}
      <div
        className="absolute -top-2 left-1/2 -translate-x-1/2 w-5 h-5 rounded-full z-10"
        style={{
          background: 'radial-gradient(circle at 30% 30%, #c86a48, #8a4a28)',
          boxShadow: '0 3px 6px rgba(0,0,0,0.4)',
        }}
      >
        {/* Pin highlight */}
        <div
          className="absolute top-1 left-1.5 w-1.5 h-1.5 rounded-full"
          style={{ background: 'rgba(255,255,255,0.3)' }}
        />
      </div>

      {/* The flipping card */}
      <div
        className={`relative w-full cursor-pointer ${hasSocials ? '' : 'pointer-events-none'}`}
        style={{
          height: '280px',
          transformStyle: 'preserve-3d',
          transform: isFlipped ? 'rotateY(180deg)' : 'rotateY(0)',
          transition: 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)',
        }}
        onClick={() => hasSocials && setIsFlipped(!isFlipped)}
      >
        {/* ============ FRONT - Polaroid Photo ============ */}
        <div
          className="absolute w-full h-full"
          style={{
            backfaceVisibility: 'hidden',
            WebkitBackfaceVisibility: 'hidden',
          }}
        >
          <div
            className="w-full h-full rounded p-3 md:p-4"
            style={{
              background: '#f5f0e8',
              boxShadow: '0 8px 32px rgba(0,0,0,0.35), 0 2px 8px rgba(0,0,0,0.2)',
              transform: 'rotate(3deg)',
            }}
          >
            {/* Photo area */}
            <div
              className="w-full relative overflow-hidden mb-3"
              style={{ height: '180px' }}
            >
              {imageUrl ? (
                <>
                  <img
                    src={imageUrl}
                    alt={artistName}
                    className="w-full h-full object-cover"
                  />
                  {/* Subtle vignette */}
                  <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                      background: 'radial-gradient(circle, transparent 40%, rgba(0,0,0,0.2) 100%)',
                    }}
                  />
                </>
              ) : (
                <div
                  className="w-full h-full flex items-center justify-center"
                  style={{
                    background: 'linear-gradient(135deg, #2a2825 0%, #1e1c1a 50%, #252220 100%)',
                  }}
                >
                  <div className="text-5xl opacity-50">🎸</div>
                </div>
              )}

              {/* Tap hint if there are social links */}
              {hasSocials && (
                <div
                  className="absolute bottom-2 right-2 text-[10px] px-2 py-1 rounded bg-black/40 text-white/70"
                  style={{ fontFamily: 'system-ui' }}
                >
                  tap to flip →
                </div>
              )}
            </div>

            {/* Handwritten caption */}
            <div
              className="text-center text-sm md:text-base"
              style={{
                color: '#3a3430',
                fontFamily: 'Georgia, serif',
                fontStyle: 'italic',
              }}
            >
              {caption || artistName}
            </div>
          </div>
        </div>

        {/* ============ BACK - Social Links ============ */}
        {hasSocials && (
          <div
            className="absolute w-full h-full"
            style={{
              backfaceVisibility: 'hidden',
              WebkitBackfaceVisibility: 'hidden',
              transform: 'rotateY(180deg)',
            }}
          >
            <div
              className="w-full h-full rounded p-4 flex flex-col justify-center"
              style={{
                background: 'linear-gradient(180deg, #2d2a26 0%, #1e1c1a 100%)',
                boxShadow: '0 8px 32px rgba(0,0,0,0.35), 0 2px 8px rgba(0,0,0,0.2)',
              }}
            >
              {/* Header */}
              <div
                className="text-center mb-4"
                style={{
                  fontSize: '10px',
                  letterSpacing: '3px',
                  color: '#6a6458',
                  fontFamily: 'system-ui',
                }}
              >
                FIND US ONLINE
              </div>

              {/* Social links */}
              <div className="flex flex-col gap-2">
                {socials.map((social, i) => (
                  <a
                    key={i}
                    href={social.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={(e) => e.stopPropagation()}
                    className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[#e8e0d4] no-underline text-sm transition-all duration-200 hover:translate-x-1"
                    style={{
                      background: 'rgba(200,180,150,0.06)',
                      border: '1px solid rgba(200,180,150,0.1)',
                      fontFamily: 'system-ui',
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.background = 'rgba(212,160,96,0.15)';
                      e.currentTarget.style.borderColor = 'rgba(212,160,96,0.3)';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.background = 'rgba(200,180,150,0.06)';
                      e.currentTarget.style.borderColor = 'rgba(200,180,150,0.1)';
                    }}
                  >
                    <span className="text-lg">{social.icon}</span>
                    <span>{social.name}</span>
                  </a>
                ))}
              </div>

              {/* Footer hint */}
              <div
                className="text-center mt-4"
                style={{
                  fontSize: '10px',
                  color: '#5a5550',
                  fontFamily: 'system-ui',
                }}
              >
                tap to flip back
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
