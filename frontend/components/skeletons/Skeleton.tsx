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
