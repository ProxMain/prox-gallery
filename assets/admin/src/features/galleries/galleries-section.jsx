import { useEffect } from "react";

import { GalleriesHeader } from "@/features/galleries/components/galleries-header";
import { GalleriesLibraryCard } from "@/features/galleries/components/galleries-library-card";
import { useGalleriesState } from "@/features/galleries/use-galleries-state";
import { useGalleryActionController, useMediaManagerActionController } from "@/lib/action-controller-hooks";

export function GalleriesSection({
  config,
  isActive
}) {
  const galleryController = useGalleryActionController(config);
  const mediaManagerController = useMediaManagerActionController(config);
  const templateOptions = config.action_controllers?.galleries?.templates ?? [];
  const {
    galleries,
    isLoading,
    error,
    loadGalleries,
    reloadGalleries,
    createGallery,
    renameGallery,
    deleteGallery,
    createGalleryPage
  } = useGalleriesState(galleryController);

  useEffect(() => {
    if (!isActive) {
      return;
    }

    void loadGalleries();
  }, [isActive, loadGalleries]);

  const handleLoadTrackedImages = async () => {
    if (!mediaManagerController) {
      return [];
    }

    const response = await mediaManagerController.listTrackedImages();
    return response.items;
  };

  const handleAddImagesToGallery = async (galleryId, imageIds) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.addImagesToGallery(galleryId, imageIds);
    await reloadGalleries();
  };

  const handleSetGalleryImages = async (galleryId, imageIds) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.setGalleryImages(galleryId, imageIds);
    await reloadGalleries();
  };

  return (
    <section className={isActive ? "space-y-6" : "hidden space-y-6"}>
      <GalleriesHeader config={config} templateCount={templateOptions.length} />
      <GalleriesLibraryCard
        galleries={galleries}
        templateOptions={templateOptions}
        isLoading={isLoading}
        error={error}
        onReloadGalleries={reloadGalleries}
        onCreateGallery={createGallery}
        onRenameGallery={renameGallery}
        onDeleteGallery={deleteGallery}
        onCreateGalleryPage={createGalleryPage}
        onLoadTrackedImages={handleLoadTrackedImages}
        onAddImagesToGallery={handleAddImagesToGallery}
        onSetGalleryImages={handleSetGalleryImages}
      />
    </section>
  );
}
