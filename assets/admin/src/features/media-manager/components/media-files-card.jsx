import { Images } from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { CollectionPagination } from "@/components/ui/collection-pagination";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";
import { MediaFilesEmptyState } from "@/features/media-manager/components/media-files-empty-state";
import { MediaFilesFilters } from "@/features/media-manager/components/media-files-filters";
import { MediaFilesToolbar } from "@/features/media-manager/components/media-files-toolbar";
import { MediaImageModal } from "@/features/media-manager/components/media-image-modal";
import { MediaRowsList } from "@/features/media-manager/components/media-rows-list";
import { MediaThumbnailGrid } from "@/features/media-manager/components/media-thumbnail-grid";

export function MediaFilesCard({
  isLoadingList,
  listError,
  trackedImages,
  viewMode,
  setViewMode,
  onReloadTrackedImages,
  onUpdateMediaMetadata,
  onLoadMediaCategories,
  onSuggestMediaCategories,
  onAssignMediaCategories,
  onListGalleries,
  onLoadImageGalleries,
  onSetImageGalleries,
  openAiController,
  onDeleteLinkClick,
  currentPage,
  pageSize,
  onPageChange,
  onPaginationChange
}) {
  const [activeModal, setActiveModal] = useState(null);
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [dateSort, setDateSort] = useState("date_desc");
  const [searchQuery, setSearchQuery] = useState("");

  const handleOpenImageModal = (image, mode = "view") => {
    setActiveModal({ image, mode });
  };

  const handleCloseImageModal = () => {
    setActiveModal(null);
  };

  const handleSaveImageMetadata = async (payload) => {
    const updated = await onUpdateMediaMetadata(payload);

    setActiveModal((current) => {
      if (!current) {
        return current;
      }

      return {
        ...current,
        image: {
          ...current.image,
          ...updated
        },
        mode: "view"
      };
    });
  };

  const categoryOptions = useMemo(() => {
    const names = new Set();

    trackedImages.forEach((image) => {
      (image.categories || []).forEach((category) => {
        const name = String(category.name || "").trim();

        if (name !== "") {
          names.add(name);
        }
      });
    });

    return Array.from(names).sort((a, b) => a.localeCompare(b));
  }, [trackedImages]);

  const visibleImages = useMemo(() => {
    const normalizedSearchQuery = searchQuery.trim().toLowerCase();
    const filtered = trackedImages.filter((image) => {
      const title = String(image.title || "").trim().toLowerCase();

      if (normalizedSearchQuery !== "" && !title.includes(normalizedSearchQuery)) {
        return false;
      }

      if (selectedCategory === "all") {
        return true;
      }

      return (image.categories || []).some(
        (category) => String(category.name || "").toLowerCase() === selectedCategory.toLowerCase()
      );
    });

    const sorted = [...filtered].sort((left, right) => {
      const leftDate = Date.parse(String(left.uploaded_at || ""));
      const rightDate = Date.parse(String(right.uploaded_at || ""));
      const leftValue = Number.isNaN(leftDate) ? 0 : leftDate;
      const rightValue = Number.isNaN(rightDate) ? 0 : rightDate;

      return dateSort === "date_asc" ? leftValue - rightValue : rightValue - leftValue;
    });

    return sorted;
  }, [trackedImages, selectedCategory, dateSort, searchQuery]);

  const totalPages = Math.max(1, Math.ceil(visibleImages.length / pageSize));
  const paginatedImages = useMemo(() => {
    const startIndex = (currentPage - 1) * pageSize;

    return visibleImages.slice(startIndex, startIndex + pageSize);
  }, [visibleImages, currentPage, pageSize]);

  useEffect(() => {
    onPageChange(1);
  }, [searchQuery, selectedCategory, dateSort, viewMode, onPageChange]);

  useEffect(() => {
    onPaginationChange({
      totalItems: visibleImages.length,
      totalPages
    });
  }, [visibleImages.length, totalPages, onPaginationChange]);

  return (
    <>
      <Card>
        <CardHeader className="p-0">
          <SectionHeader
            framed={false}
            icon={Images}
            title="Media files"
            description="Manage files and switch views from one compact header bar."
            actions={
              <MediaFilesToolbar
                onReloadTrackedImages={onReloadTrackedImages}
                onSetThumbnailView={() => setViewMode("thumbnail")}
                onSetRowView={() => setViewMode("row")}
                onToggleFilters={() => setIsFilterOpen((current) => !current)}
                searchQuery={searchQuery}
                onSearchQueryChange={setSearchQuery}
              />
            }
          />
        </CardHeader>
        <CardContent>
          {isFilterOpen ? (
            <MediaFilesFilters
              selectedCategory={selectedCategory}
              onSelectedCategoryChange={setSelectedCategory}
              categoryOptions={categoryOptions}
              dateSort={dateSort}
              onDateSortChange={setDateSort}
            />
          ) : null}

          {isLoadingList ? (
            <p className="text-sm text-slate-600">Loading tracked images...</p>
          ) : visibleImages.length === 0 ? (
            <MediaFilesEmptyState
              isLoadingList={isLoadingList}
              onReloadTrackedImages={onReloadTrackedImages}
              hasActiveSearch={searchQuery.trim() !== ""}
            />
          ) : viewMode === "thumbnail" ? (
            <MediaThumbnailGrid
              trackedImages={paginatedImages}
              onDeleteLinkClick={onDeleteLinkClick}
              onViewClick={(image) => handleOpenImageModal(image, "view")}
              onEditClick={(image) => handleOpenImageModal(image, "edit")}
            />
          ) : (
            <MediaRowsList
              trackedImages={paginatedImages}
              onDeleteLinkClick={onDeleteLinkClick}
              onViewClick={(image) => handleOpenImageModal(image, "view")}
              onEditClick={(image) => handleOpenImageModal(image, "edit")}
            />
          )}
          {listError !== "" ? <p className="mt-3 text-sm text-red-600">{listError}</p> : null}
        </CardContent>
      </Card>

      <MediaImageModal
        image={activeModal?.image ?? null}
        mode={activeModal?.mode ?? "view"}
        onSave={handleSaveImageMetadata}
        onLoadCategories={onLoadMediaCategories}
        onSuggestCategories={onSuggestMediaCategories}
        onAssignCategories={onAssignMediaCategories}
        onListGalleries={onListGalleries}
        onLoadImageGalleries={onLoadImageGalleries}
        onSetImageGalleries={onSetImageGalleries}
        openAiController={openAiController}
        onClose={handleCloseImageModal}
      />
    </>
  );
}
