import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="min-h-screen bg-[#1c1a17] flex items-center justify-center p-4">
      <div className="max-w-md w-full text-center">
        <div className="w-20 h-20 mx-auto mb-6 rounded-full bg-[#2d2a26] flex items-center justify-center">
          <span className="text-4xl font-bold text-[#d4a060]">404</span>
        </div>
        <h1 className="text-2xl font-bold text-white mb-2">Page not found</h1>
        <p className="text-[#8a8478] mb-6">The page you're looking for doesn't exist or has been moved.</p>
        <Link
          href="/"
          className="inline-block px-6 py-3 rounded-full bg-[#d4a060] text-black font-medium hover:bg-[#c08a40] transition-colors"
        >
          Go home
        </Link>
      </div>
    </div>
  );
}
