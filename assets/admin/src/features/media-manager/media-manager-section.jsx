import { useEffect, useMemo, useState } from "react";

import { CollectionPagination } from "@/components/ui/collection-pagination";
import { MediaFilesCard } from "@/features/media-manager/components/media-files-card";
import { MediaManagerHeader } from "@/features/media-manager/components/media-manager-header";
import { useMediaManagerPicker } from "@/features/media-manager/hooks/use-media-manager-picker";
import { useMediaManagerState } from "@/features/media-manager/use-media-manager-state";
import {
  useGalleryActionController,
  useMediaCategoryActionController,
  useMediaManagerActionController,
  useOpenAiActionController
} from "@/lib/action-controller-hooks";

export function MediaManagerSection({
  config,
  isActive
}) {
  const mediaManagerController = useMediaManagerActionController(config);
  const mediaCategoryController = useMediaCategoryActionController(config);
  const galleryController = useGalleryActionController(config);
  const openAiController = useOpenAiActionController(config);
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(24);
  const [totalItems, setTotalItems] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
  const {
    viewMode,
    trackedImages,
    isLoadingList,
    listError,
    setViewMode,
    loadTrackedImages,
    reloadTrackedImages
  } = useMediaManagerState(mediaManagerController);
  const {
    isOpeningPicker,
    pickerError,
    lastSelectionSummary,
    openPicker
  } = useMediaManagerPicker({
    onTrackSelection: async (attachmentIds) => {
      if (!mediaManagerController) {
        throw new Error("Media manager action configuration is missing.");
      }

      const response = await mediaManagerController.trackSelectedAttachments(attachmentIds);
      await reloadTrackedImages();

      return response;
    }
  });

  useEffect(() => {
    if (!isActive) {
      return;
    }

    void loadTrackedImages();
  }, [isActive, loadTrackedImages]);

  useEffect(() => {
    setCurrentPage((page) => Math.min(page, totalPages));
  }, [totalPages]);

  const handleDeleteLinkClick = (event, deleteUrl) => {
    if (deleteUrl === "") {
      event.preventDefault();
      return;
    }

    const confirmed = window.confirm("Delete this media item?");

    if (!confirmed) {
      event.preventDefault();
    }
  };

  const handleUpdateMediaMetadata = async (payload) => {
    if (!mediaManagerController) {
      throw new Error("Media manager action configuration is missing.");
    }

    const response = await mediaManagerController.updateTrackedImageMetadata(payload);
    await reloadTrackedImages();

    return response.item;
  };

  const handleLoadMediaCategories = async (attachmentId) => {
    if (!mediaCategoryController) {
      throw new Error("Media category action configuration is missing.");
    }

    const response = await mediaCategoryController.listForAttachment(attachmentId);
    return response.items;
  };

  const handleSuggestMediaCategories = async (query) => {
    if (!mediaCategoryController) {
      return [];
    }

    const response = await mediaCategoryController.suggestCategories(query, 12);
    return response.items;
  };

  const handleAssignMediaCategories = async (attachmentId, categories) => {
    if (!mediaCategoryController) {
      throw new Error("Media category action configuration is missing.");
    }

    const response = await mediaCategoryController.assignToAttachment(attachmentId, categories.join(","));
    return response.items;
  };

  const handleLoadImageGalleries = async (imageId) => {
    if (!galleryController) {
      return [];
    }

    const response = await galleryController.listImageGalleries(imageId);
    return response.gallery_ids;
  };

  const handleSetImageGalleries = async (imageId, galleryIds) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    const response = await galleryController.setImageGalleries(imageId, galleryIds);
    return response.gallery_ids;
  };

  const handleListGalleries = async () => {
    if (!galleryController) {
      return [];
    }

    const response = await galleryController.listGalleries();
    return response.items;
  };

  const pagination = useMemo(() => {
    if (totalItems <= 0) {
      return null;
    }

    return (
      <CollectionPagination
        className="border-white/70 bg-white/80"
        itemLabel={viewMode === "thumbnail" ? "items" : "rows"}
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
  }, [currentPage, pageSize, totalItems, totalPages, viewMode]);

  return (
    <section className={isActive ? "space-y-6" : "hidden space-y-6"}>
      <MediaManagerHeader
        config={config}
        isOpeningPicker={isOpeningPicker}
        onOpenPicker={openPicker}
        pickerError={pickerError}
        lastSelectionSummary={lastSelectionSummary}
        pagination={pagination}
      />
      <MediaFilesCard
        isLoadingList={isLoadingList}
        listError={listError}
        trackedImages={trackedImages}
        viewMode={viewMode}
        setViewMode={setViewMode}
        onReloadTrackedImages={reloadTrackedImages}
        onUpdateMediaMetadata={handleUpdateMediaMetadata}
        onLoadMediaCategories={handleLoadMediaCategories}
        onSuggestMediaCategories={handleSuggestMediaCategories}
        onAssignMediaCategories={handleAssignMediaCategories}
        onListGalleries={handleListGalleries}
        onLoadImageGalleries={handleLoadImageGalleries}
        onSetImageGalleries={handleSetImageGalleries}
        openAiController={openAiController}
        onDeleteLinkClick={handleDeleteLinkClick}
        currentPage={currentPage}
        pageSize={pageSize}
        onPageChange={setCurrentPage}
        onPaginationChange={({ totalItems: nextTotalItems, totalPages: nextTotalPages }) => {
          setTotalItems(nextTotalItems);
          setTotalPages(nextTotalPages);
        }}
      />
    </section>
  );
}
