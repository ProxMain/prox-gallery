import { useEffect, useMemo, useState } from "react";

import { CollectionPagination } from "@/components/ui/collection-pagination";
import { GalleriesHeader } from "@/features/galleries/components/galleries-header";
import { GalleriesLibraryCard } from "@/features/galleries/components/galleries-library-card";
import { useGalleryCreationWizard } from "@/features/galleries/hooks/use-gallery-creation-wizard";
import { useGalleriesState } from "@/features/galleries/use-galleries-state";
import { useGalleryActionController, useMediaManagerActionController } from "@/lib/action-controller-hooks";

export function GalleriesSection({
  config,
  isActive
}) {
  const galleryController = useGalleryActionController(config);
  const mediaManagerController = useMediaManagerActionController(config);
  const templateOptions = config.action_controllers?.galleries?.templates ?? [];
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(24);
  const [totalItems, setTotalItems] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
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
  const galleryWizard = useGalleryCreationWizard({
    onCreateGallery: createGallery
  });

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

  useEffect(() => {
    setCurrentPage((page) => Math.min(page, totalPages));
  }, [totalPages]);

  const pagination = useMemo(() => {
    if (totalItems <= 0) {
      return null;
    }

    return (
      <CollectionPagination
        className="border-white/70 bg-white/80"
        itemLabel="galleries"
        totalItems={totalItems}
        currentPage={currentPage}
        pageSize={pageSize}
        onPageSizeChange={(nextPageSize) => {
          setPageSize(nextPageSize);
          setCurrentPage(1);
        }}
        onPreviousPage={() => setCurrentPage((page) => Math.max(1, page - 1))}
        onNextPage={() => setCurrentPage((page) => Math.min(totalPages, page + 1))}
        totalPages={totalPages}
      />
    );
  }, [currentPage, pageSize, totalItems, totalPages]);

  return (
    <section className={isActive ? "space-y-6" : "hidden space-y-6"}>
      <GalleriesHeader
        config={config}
        templateCount={templateOptions.length}
        pagination={pagination}
        onOpenWizard={galleryWizard.openWizard}
      />
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
        currentPage={currentPage}
        pageSize={pageSize}
        onPageChange={setCurrentPage}
        onPaginationChange={({ totalItems: nextTotalItems, totalPages: nextTotalPages }) => {
          setTotalItems(nextTotalItems);
          setTotalPages(nextTotalPages);
        }}
        wizard={galleryWizard}
      />
    </section>
  );
}
