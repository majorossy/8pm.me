'use client';

interface ErrorFallbackProps {
  error: Error & { digest?: string };
  reset: () => void;
  title?: string;
  description?: string;
}

export default function ErrorFallback({
  error,
  reset,
  title = 'Something went wrong',
  description = 'An error occurred while loading this page.',
}: ErrorFallbackProps) {
  return (
    <div className="min-h-screen bg-[#1c1a17] flex items-center justify-center p-4">
      <div className="max-w-md w-full text-center">
        <div className="w-16 h-16 mx-auto mb-6 rounded-full bg-[#2d2a26] flex items-center justify-center">
          <svg className="w-8 h-8 text-[#d4a060]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h1 className="text-2xl font-bold text-white mb-2">{title}</h1>
        <p className="text-[#8a8478] mb-6">{description}</p>
        {process.env.NODE_ENV === 'development' && (
          <p className="text-[#6a6458] text-sm mb-4 font-mono bg-[#2d2a26] p-3 rounded">{error.message}</p>
        )}
        <div className="flex gap-3 justify-center">
          <button onClick={reset} className="px-6 py-3 rounded-full bg-[#d4a060] text-black font-medium hover:bg-[#c08a40] transition-colors">Try again</button>
          <a href="/" className="px-6 py-3 rounded-full border border-white/20 text-white hover:bg-white/10 transition-colors">Go home</a>
        </div>
      </div>
    </div>
  );
}
