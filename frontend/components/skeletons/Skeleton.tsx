export function Skeleton({ className = '' }: { className?: string }) {
  return <div className={`bg-[#2d2a26] animate-pulse rounded ${className}`} />;
}

export function SkeletonCard({ className = '' }: { className?: string }) {
  return (
    <div className={`p-4 ${className}`}>
      <Skeleton className="w-full aspect-square rounded mb-3" />
      <Skeleton className="h-4 w-3/4 mb-2" />
      <Skeleton className="h-3 w-1/2" />
    </div>
  );
}

export function SkeletonTrack() {
  return (
    <div className="flex items-center gap-3 p-3">
      <Skeleton className="w-10 h-10 rounded" />
      <div className="flex-1">
        <Skeleton className="h-4 w-48 mb-2" />
        <Skeleton className="h-3 w-32" />
      </div>
      <Skeleton className="h-4 w-12 hidden md:block" />
    </div>
  );
}

export function SkeletonArtistHeader() {
  return (
    <div className="p-6 md:p-8">
      <div className="flex flex-col md:flex-row items-center md:items-end gap-6">
        <Skeleton className="w-48 h-48 rounded-full" />
        <div className="flex-1 text-center md:text-left">
          <Skeleton className="h-8 w-48 mb-4 mx-auto md:mx-0" />
          <Skeleton className="h-4 w-32 mx-auto md:mx-0" />
        </div>
      </div>
    </div>
  );
}

export function SkeletonAlbumGrid() {
  return (
    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 p-6">
      {Array.from({ length: 10 }).map((_, i) => (
        <SkeletonCard key={i} />
      ))}
    </div>
  );
}

export function SkeletonCassette() {
  return (
    <div className="w-[280px] sm:w-[340px] h-[180px] sm:h-[220px] rounded-xl bg-[#2a2520] animate-pulse">
      {/* Label area */}
      <div className="mx-5 mt-3 h-20 sm:h-24 rounded bg-[#3a3530]" />
      {/* Tape window */}
      <div className="mx-8 sm:mx-10 mt-4 h-14 sm:h-[75px] rounded-md bg-[#1a1815] flex items-center justify-between px-6">
        <div className="w-10 sm:w-[52px] h-10 sm:h-[52px] rounded-full bg-[#2a2520]" />
        <div className="w-10 sm:w-[52px] h-10 sm:h-[52px] rounded-full bg-[#2a2520]" />
      </div>
    </div>
  );
}

export function SkeletonAlbumHeader() {
  return (
    <div className="flex flex-col lg:flex-row gap-8 lg:gap-12 items-center lg:items-start justify-center">
      <SkeletonCassette />
      <div className="pt-4 max-w-[400px] text-center lg:text-left">
        <Skeleton className="h-3 w-24 mb-3 mx-auto lg:mx-0" />
        <Skeleton className="h-12 w-64 mb-3 mx-auto lg:mx-0" />
        <Skeleton className="h-6 w-48 mb-2 mx-auto lg:mx-0" />
        <Skeleton className="h-4 w-40 mb-6 mx-auto lg:mx-0" />
        <div className="flex gap-3 justify-center lg:justify-start">
          <Skeleton className="w-14 h-14 rounded-full" />
          <Skeleton className="w-24 h-12 rounded-md" />
          <Skeleton className="w-11 h-11 rounded-full" />
        </div>
      </div>
    </div>
  );
}

export function SkeletonTrackRow() {
  return (
    <div className="flex items-center gap-4 px-4 py-4 border-b border-[#2a2520]">
      <Skeleton className="w-8 h-6" />
      <div className="flex-1">
        <Skeleton className="h-5 w-48 mb-2" />
        <Skeleton className="h-4 w-32" />
      </div>
      <Skeleton className="h-4 w-16" />
    </div>
  );
}

export function SkeletonAlbumPage() {
  return (
    <div className="min-h-screen font-serif text-[#e8d8c8]">
      {/* Header badge */}
      <div className="text-center pt-8 pb-4">
        <Skeleton className="h-3 w-40 mx-auto" />
      </div>

      {/* Main content */}
      <div className="max-w-[1000px] mx-auto px-4 sm:px-8 pb-36">
        <SkeletonAlbumHeader />

        {/* Side A divider */}
        <div className="flex items-center gap-4 my-8">
          <div className="flex-1 h-px bg-[#2a2520]" />
          <Skeleton className="h-3 w-16" />
          <div className="flex-1 h-px bg-[#2a2520]" />
        </div>

        {/* Tracks */}
        {Array.from({ length: 6 }).map((_, i) => (
          <SkeletonTrackRow key={i} />
        ))}
      </div>
    </div>
  );
}
