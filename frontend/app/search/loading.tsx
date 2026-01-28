import { Skeleton } from '@/components/skeletons/Skeleton';

export default function SearchLoading() {
  return (
    <div className="min-h-screen bg-[#1c1a17] p-6 md:p-8">
      <Skeleton className="h-10 w-32 mb-4" />
      <Skeleton className="h-14 w-full max-w-2xl rounded-full" />
    </div>
  );
}
