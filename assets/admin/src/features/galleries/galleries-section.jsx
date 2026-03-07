import { GalleriesHeader } from "@/features/galleries/components/galleries-header";
import { GalleriesLibraryCard } from "@/features/galleries/components/galleries-library-card";

export function GalleriesSection({
  config,
  galleries,
  isLoading,
  error,
  onReloadGalleries,
  onCreateGallery,
  onRenameGallery,
  onDeleteGallery,
  onCreateGalleryPage,
  onLoadTrackedImages,
  onAddImagesToGallery,
  onSetGalleryImages
}) {
  const templateOptions = config.action_controllers?.galleries?.templates ?? [];

  return (
    <section className="space-y-6">
      <GalleriesHeader config={config} templateCount={templateOptions.length} />
      <GalleriesLibraryCard
        galleries={galleries}
        templateOptions={templateOptions}
        isLoading={isLoading}
        error={error}
        onReloadGalleries={onReloadGalleries}
        onCreateGallery={onCreateGallery}
        onRenameGallery={onRenameGallery}
        onDeleteGallery={onDeleteGallery}
        onCreateGalleryPage={onCreateGalleryPage}
        onLoadTrackedImages={onLoadTrackedImages}
        onAddImagesToGallery={onAddImagesToGallery}
        onSetGalleryImages={onSetGalleryImages}
      />
    </section>
  );
}
