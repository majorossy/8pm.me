'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useCookieConsent, CookieCategory } from '@/hooks/useCookieConsent';

// Cookie icon
const CookieIcon = ({ className = 'w-6 h-6' }: { className?: string }) => (
  <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <circle cx="12" cy="12" r="10" strokeWidth="2" />
    <circle cx="8" cy="9" r="1.5" fill="currentColor" />
    <circle cx="15" cy="8" r="1" fill="currentColor" />
    <circle cx="10" cy="14" r="1" fill="currentColor" />
    <circle cx="16" cy="13" r="1.5" fill="currentColor" />
    <circle cx="12" cy="17" r="1" fill="currentColor" />
  </svg>
);

// Settings/gear icon
const SettingsIcon = ({ className = 'w-4 h-4' }: { className?: string }) => (
  <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="2"
      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
    />
    <path
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="2"
      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
    />
  </svg>
);

// Check icon
const CheckIcon = ({ className = 'w-4 h-4' }: { className?: string }) => (
  <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
  </svg>
);

interface CategoryToggleProps {
  label: string;
  description: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
  disabled?: boolean;
}

function CategoryToggle({ label, description, checked, onChange, disabled }: CategoryToggleProps) {
  return (
    <label className="flex items-start gap-3 cursor-pointer group">
      <div className="relative mt-0.5">
        <input
          type="checkbox"
          checked={checked}
          onChange={(e) => onChange(e.target.checked)}
          disabled={disabled}
          className="sr-only peer"
        />
        <div
          className={`
            w-5 h-5 rounded border-2 flex items-center justify-center transition-all
            ${disabled
              ? 'bg-[#3a3632] border-[#4a4642] cursor-not-allowed'
              : checked
                ? 'bg-[#d4a060] border-[#d4a060]'
                : 'bg-transparent border-[#5a5652] group-hover:border-[#d4a060]'
            }
          `}
        >
          {checked && <CheckIcon className="w-3 h-3 text-[#1c1a17]" />}
        </div>
      </div>
      <div className="flex-1">
        <div className={`text-sm font-medium ${disabled ? 'text-[#6a6458]' : 'text-[#e8e4dc]'}`}>
          {label}
          {disabled && <span className="ml-2 text-xs text-[#5a5652]">(Required)</span>}
        </div>
        <div className="text-xs text-[#8a8478] mt-0.5">{description}</div>
      </div>
    </label>
  );
}

export default function CookieConsentBanner() {
  const {
    hasConsented,
    isLoading,
    acceptAll,
    declineNonEssential,
    updateConsent,
  } = useCookieConsent();

  const [showCustomize, setShowCustomize] = useState(false);
  const [customSettings, setCustomSettings] = useState({
    functional: false,
    analytics: false,
  });

  // Don't render while loading or if user has already consented
  if (isLoading || hasConsented) {
    return null;
  }

  const handleCustomSave = () => {
    updateConsent({
      functional: customSettings.functional,
      analytics: customSettings.analytics,
    });
  };

  return (
    <>
      {/* Backdrop overlay when customize is open */}
      {showCustomize && (
        <div
          className="fixed inset-0 bg-black/50 z-[9998] animate-fade-in"
          onClick={() => setShowCustomize(false)}
        />
      )}

      {/* Cookie consent banner */}
      <div
        className={`
          fixed z-[9999] transition-all duration-300 ease-out
          ${showCustomize
            ? 'inset-4 md:inset-auto md:bottom-4 md:left-1/2 md:-translate-x-1/2 md:w-[500px]'
            : 'bottom-0 left-0 right-0 md:bottom-4 md:left-4 md:right-auto md:w-[420px]'
          }
        `}
        role="dialog"
        aria-modal="true"
        aria-labelledby="cookie-consent-title"
      >
        <div className="bg-[#2a2825] border border-[#3a3632] rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden">
          {/* Header */}
          <div className="p-4 pb-3 border-b border-[#3a3632]/50">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-[#1c1a17] rounded-lg">
                <CookieIcon className="w-5 h-5 text-[#d4a060]" />
              </div>
              <h2 id="cookie-consent-title" className="text-lg font-semibold text-[#e8e4dc]">
                Cookie Preferences
              </h2>
            </div>
          </div>

          {/* Content */}
          <div className="p-4">
            {!showCustomize ? (
              <>
                <p className="text-sm text-[#8a8478] mb-4 leading-relaxed">
                  We use cookies to enhance your experience. Essential cookies are required for basic functionality.
                  Analytics cookies help us improve 8PM.{' '}
                  <Link
                    href="/cookie-policy"
                    className="text-[#d4a060] hover:underline focus:outline-none focus:ring-2 focus:ring-[#d4a060] rounded"
                  >
                    Learn more
                  </Link>
                </p>

                {/* Quick action buttons */}
                <div className="flex flex-col sm:flex-row gap-2">
                  <button
                    onClick={acceptAll}
                    className="flex-1 px-4 py-2.5 bg-[#d4a060] text-[#1c1a17] font-medium rounded-lg hover:bg-[#e8b070] transition-colors focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:ring-offset-2 focus:ring-offset-[#2a2825]"
                  >
                    Accept All
                  </button>
                  <button
                    onClick={declineNonEssential}
                    className="flex-1 px-4 py-2.5 bg-[#3a3632] text-[#e8e4dc] font-medium rounded-lg hover:bg-[#4a4642] transition-colors focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:ring-offset-2 focus:ring-offset-[#2a2825]"
                  >
                    Essential Only
                  </button>
                  <button
                    onClick={() => setShowCustomize(true)}
                    className="flex items-center justify-center gap-2 px-4 py-2.5 border border-[#3a3632] text-[#8a8478] font-medium rounded-lg hover:border-[#d4a060] hover:text-[#d4a060] transition-colors focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:ring-offset-2 focus:ring-offset-[#2a2825]"
                    aria-expanded={showCustomize}
                  >
                    <SettingsIcon className="w-4 h-4" />
                    <span className="hidden sm:inline">Customize</span>
                  </button>
                </div>
              </>
            ) : (
              <>
                {/* Customization panel */}
                <div className="space-y-4 mb-4">
                  <CategoryToggle
                    label="Essential Cookies"
                    description="Required for basic site functionality. Cannot be disabled."
                    checked={true}
                    onChange={() => {}}
                    disabled={true}
                  />

                  <CategoryToggle
                    label="Functional Cookies"
                    description="Remember your preferences and enable features like cross-device sync."
                    checked={customSettings.functional}
                    onChange={(checked) => setCustomSettings((s) => ({ ...s, functional: checked }))}
                  />

                  <CategoryToggle
                    label="Analytics Cookies"
                    description="Help us understand how you use 8PM so we can improve the experience."
                    checked={customSettings.analytics}
                    onChange={(checked) => setCustomSettings((s) => ({ ...s, analytics: checked }))}
                  />
                </div>

                <p className="text-xs text-[#6a6458] mb-4">
                  View our{' '}
                  <Link href="/cookie-policy" className="text-[#d4a060] hover:underline">
                    Cookie Policy
                  </Link>{' '}
                  for detailed information about each cookie type.
                </p>

                {/* Customize action buttons */}
                <div className="flex gap-2">
                  <button
                    onClick={() => setShowCustomize(false)}
                    className="flex-1 px-4 py-2.5 border border-[#3a3632] text-[#8a8478] font-medium rounded-lg hover:border-[#5a5652] hover:text-[#e8e4dc] transition-colors focus:outline-none focus:ring-2 focus:ring-[#d4a060]"
                  >
                    Back
                  </button>
                  <button
                    onClick={handleCustomSave}
                    className="flex-1 px-4 py-2.5 bg-[#d4a060] text-[#1c1a17] font-medium rounded-lg hover:bg-[#e8b070] transition-colors focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:ring-offset-2 focus:ring-offset-[#2a2825]"
                  >
                    Save Preferences
                  </button>
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
