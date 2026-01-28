'use client';

import { useState, useEffect, useCallback } from 'react';
import { useAuth } from '@/context/AuthContext';

interface AuthModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialMode?: 'signin' | 'signup';
}

export default function AuthModal({ isOpen, onClose, initialMode = 'signin' }: AuthModalProps) {
  const [mode, setMode] = useState<'signin' | 'signup' | 'magic-link'>(initialMode);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { signIn, signUp, signInWithMagicLink, isConfigured } = useAuth();

  // Reset form when modal opens/closes
  useEffect(() => {
    if (isOpen) {
      setMode(initialMode);
      setEmail('');
      setPassword('');
      setConfirmPassword('');
      setError(null);
      setSuccess(null);
    }
  }, [isOpen, initialMode]);

  // Handle escape key
  useEffect(() => {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    window.addEventListener('keydown', handleEscape);
    return () => window.removeEventListener('keydown', handleEscape);
  }, [isOpen, onClose]);

  const handleSubmit = useCallback(async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    if (!isConfigured) {
      setError('Authentication is not configured. Please set up Supabase credentials.');
      return;
    }

    if (mode === 'signup' && password !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    if (mode !== 'magic-link' && password.length < 6) {
      setError('Password must be at least 6 characters');
      return;
    }

    setIsSubmitting(true);

    try {
      if (mode === 'signin') {
        const { error } = await signIn(email, password);
        if (error) {
          setError(error.message);
        } else {
          onClose();
        }
      } else if (mode === 'signup') {
        const { error } = await signUp(email, password);
        if (error) {
          setError(error.message);
        } else {
          setSuccess('Check your email to confirm your account!');
        }
      } else if (mode === 'magic-link') {
        const { error } = await signInWithMagicLink(email);
        if (error) {
          setError(error.message);
        } else {
          setSuccess('Check your email for the magic link!');
        }
      }
    } catch (err) {
      setError('An unexpected error occurred');
    } finally {
      setIsSubmitting(false);
    }
  }, [mode, email, password, confirmPassword, signIn, signUp, signInWithMagicLink, isConfigured, onClose]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/70 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="relative bg-[#1c1a17] border border-[#3a3632] rounded-lg w-full max-w-md mx-4 p-6 shadow-xl">
        {/* Close button */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-[#8a8478] hover:text-[#e8e0d4] transition-colors"
          aria-label="Close"
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        {/* Header */}
        <div className="text-center mb-6">
          <span className="text-3xl">⚡</span>
          <h2 className="text-xl font-serif text-[#e8e0d4] mt-2">
            {mode === 'signin' && 'Welcome Back'}
            {mode === 'signup' && 'Create Account'}
            {mode === 'magic-link' && 'Magic Link'}
          </h2>
          <p className="text-[#8a8478] text-sm mt-1">
            {mode === 'signin' && 'Sign in to sync your library across devices'}
            {mode === 'signup' && 'Join to save your playlists and favorites'}
            {mode === 'magic-link' && 'Sign in without a password'}
          </p>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Email */}
          <div>
            <label htmlFor="email" className="block text-sm text-[#8a8478] mb-1">
              Email
            </label>
            <input
              type="email"
              id="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="w-full px-4 py-3 bg-[#2d2a26] border border-[#3a3632] rounded-md text-[#e8e0d4] placeholder-[#6a6458] focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:border-transparent"
              placeholder="you@example.com"
            />
          </div>

          {/* Password (not for magic link) */}
          {mode !== 'magic-link' && (
            <div>
              <label htmlFor="password" className="block text-sm text-[#8a8478] mb-1">
                Password
              </label>
              <input
                type="password"
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                minLength={6}
                className="w-full px-4 py-3 bg-[#2d2a26] border border-[#3a3632] rounded-md text-[#e8e0d4] placeholder-[#6a6458] focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:border-transparent"
                placeholder="••••••••"
              />
            </div>
          )}

          {/* Confirm Password (signup only) */}
          {mode === 'signup' && (
            <div>
              <label htmlFor="confirmPassword" className="block text-sm text-[#8a8478] mb-1">
                Confirm Password
              </label>
              <input
                type="password"
                id="confirmPassword"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                required
                minLength={6}
                className="w-full px-4 py-3 bg-[#2d2a26] border border-[#3a3632] rounded-md text-[#e8e0d4] placeholder-[#6a6458] focus:outline-none focus:ring-2 focus:ring-[#d4a060] focus:border-transparent"
                placeholder="••••••••"
              />
            </div>
          )}

          {/* Error message */}
          {error && (
            <div className="p-3 bg-red-900/30 border border-red-700/50 rounded-md text-red-300 text-sm">
              {error}
            </div>
          )}

          {/* Success message */}
          {success && (
            <div className="p-3 bg-green-900/30 border border-green-700/50 rounded-md text-green-300 text-sm">
              {success}
            </div>
          )}

          {/* Submit button */}
          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full py-3 bg-[#d4a060] hover:bg-[#c49050] text-[#1c1a17] font-medium rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isSubmitting ? (
              <span className="flex items-center justify-center gap-2">
                <svg className="animate-spin h-5 w-5" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                Loading...
              </span>
            ) : (
              <>
                {mode === 'signin' && 'Sign In'}
                {mode === 'signup' && 'Create Account'}
                {mode === 'magic-link' && 'Send Magic Link'}
              </>
            )}
          </button>
        </form>

        {/* Divider */}
        <div className="my-6 flex items-center gap-4">
          <div className="flex-1 border-t border-[#3a3632]" />
          <span className="text-[#6a6458] text-xs">or</span>
          <div className="flex-1 border-t border-[#3a3632]" />
        </div>

        {/* Alternative options */}
        <div className="space-y-3">
          {mode !== 'magic-link' && (
            <button
              type="button"
              onClick={() => setMode('magic-link')}
              className="w-full py-2 text-sm text-[#d4a060] hover:text-[#e8c090] transition-colors"
            >
              Sign in with magic link
            </button>
          )}

          {mode === 'signin' && (
            <p className="text-center text-sm text-[#8a8478]">
              Don&apos;t have an account?{' '}
              <button
                type="button"
                onClick={() => setMode('signup')}
                className="text-[#d4a060] hover:text-[#e8c090] transition-colors"
              >
                Sign up
              </button>
            </p>
          )}

          {(mode === 'signup' || mode === 'magic-link') && (
            <p className="text-center text-sm text-[#8a8478]">
              Already have an account?{' '}
              <button
                type="button"
                onClick={() => setMode('signin')}
                className="text-[#d4a060] hover:text-[#e8c090] transition-colors"
              >
                Sign in
              </button>
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
