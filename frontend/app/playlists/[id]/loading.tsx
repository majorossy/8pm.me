import { Skeleton, SkeletonTrack } from '@/components/skeletons/Skeleton';

export default function PlaylistDetailLoading() {
  return (
    <div className="min-h-screen bg-[#1c1a17]">
      <div className="p-6 md:p-8">
        <div className="flex flex-col md:flex-row items-center md:items-end gap-6 mb-8">
          <Skeleton className="w-48 h-48 rounded" />
          <div className="flex-1 text-center md:text-left">
            <Skeleton className="h-8 w-48 mb-4 mx-auto md:mx-0" />
            <Skeleton className="h-4 w-32 mx-auto md:mx-0" />
          </div>
        </div>
        <div className="space-y-1">
          {Array.from({ length: 10 }).map((_, i) => (
            <SkeletonTrack key={i} />
          ))}
        </div>
      </div>
    </div>
  );
}
