'use client';

import { useState, useEffect } from 'react';

const STORAGE_KEY = '8pm-early-access';
const VALID_USERNAME = 'phish';
const VALID_PASSWORD = 'phish';

export default function EarlyAccessGate({ children }: { children: React.ReactNode }) {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean | null>(null);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  // Check localStorage on mount
  useEffect(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    setIsAuthenticated(stored === 'true');
  }, []);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (
      username.toLowerCase() === VALID_USERNAME &&
      password.toLowerCase() === VALID_PASSWORD
    ) {
      localStorage.setItem(STORAGE_KEY, 'true');
      setIsAuthenticated(true);
    } else {
      setError('Invalid credentials');
    }
  };

  // Still checking auth state
  if (isAuthenticated === null) {
    return (
      <div className="fixed inset-0 bg-[#1c1a17] z-[99999] flex items-center justify-center">
        <div className="w-8 h-8 border-2 border-[#d4a060] border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  // Authenticated - show app
  if (isAuthenticated) {
    return <>{children}</>;
  }

  // Not authenticated - show login modal
  return (
    <div className="fixed inset-0 bg-[#1c1a17] z-[99999] flex items-center justify-center p-4">
      <div className="bg-[#252220] rounded-xl p-8 max-w-md w-full shadow-2xl border border-[#3a3632]">
        {/* Logo/Title */}
        <div className="text-center mb-8">
          <h1
            className="text-4xl font-bold text-[#d4a060] mb-2"
            style={{ fontFamily: 'Georgia, serif' }}
          >
            8pm.me
          </h1>
          <div className="inline-block px-3 py-1 bg-[#d4a060]/10 rounded-full">
            <span className="text-xs font-medium text-[#d4a060] tracking-wider uppercase">
              Early Access
            </span>
          </div>
        </div>

        {/* Message */}
        <p className="text-[#a8a098] text-center mb-6 text-sm">
          This site is still under development.<br />
          Enter your credentials to continue.
        </p>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="username" className="block text-xs font-medium text-[#8a8478] mb-1.5 uppercase tracking-wider">
              Username
            </label>
            <input
              type="text"
              id="username"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              className="w-full px-4 py-3 bg-[#1c1a17] border border-[#3a3632] rounded-lg text-[#e8e0d4] placeholder-[#6a6458] focus:outline-none focus:border-[#d4a060] transition-colors"
              placeholder="Enter username"
              autoComplete="username"
              autoFocus
            />
          </div>

          <div>
            <label htmlFor="password" className="block text-xs font-medium text-[#8a8478] mb-1.5 uppercase tracking-wider">
              Password
            </label>
            <input
              type="password"
              id="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-3 bg-[#1c1a17] border border-[#3a3632] rounded-lg text-[#e8e0d4] placeholder-[#6a6458] focus:outline-none focus:border-[#d4a060] transition-colors"
              placeholder="Enter password"
              autoComplete="current-password"
            />
          </div>

          {error && (
            <p className="text-red-400 text-sm text-center">{error}</p>
          )}

          <button
            type="submit"
            className="w-full py-3 bg-[#d4a060] hover:bg-[#c08a40] text-[#1c1a17] font-bold rounded-lg transition-colors"
          >
            Enter
          </button>
        </form>

        {/* Footer */}
        <p className="text-[#6a6458] text-xs text-center mt-6">
          Live music archive • Coming soon
        </p>
      </div>
    </div>
  );
}
