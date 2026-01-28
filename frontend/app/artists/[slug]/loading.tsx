import { SkeletonArtistHeader, SkeletonAlbumGrid } from '@/components/skeletons/Skeleton';

export default function ArtistLoading() {
  return (
    <div className="min-h-screen bg-[#1c1a17]">
      <SkeletonArtistHeader />
      <SkeletonAlbumGrid />
    </div>
  );
}
