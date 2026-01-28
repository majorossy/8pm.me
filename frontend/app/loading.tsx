import { SkeletonCard } from '@/components/skeletons/Skeleton';

export default function Loading() {
  return (
    <div className="min-h-screen bg-[#1c1a17] p-6 md:p-8">
      <div className="h-10 w-48 bg-[#2d2a26] animate-pulse rounded mb-8" />
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        {Array.from({ length: 10 }).map((_, i) => (
          <SkeletonCard key={i} />
        ))}
      </div>
    </div>
  );
}
