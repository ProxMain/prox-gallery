import { ArrowDownAZ, Database, Filter, Images, Settings2, WandSparkles } from "lucide-react";
import { useMemo, useState } from "react";

import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";
import { MediaFilesEmptyState } from "@/features/media-manager/components/media-files-empty-state";
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
  onDeleteLinkClick
}) {
  const [activeModal, setActiveModal] = useState(null);
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [dateSort, setDateSort] = useState("date_desc");

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
    const filtered = trackedImages.filter((image) => {
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
  }, [trackedImages, selectedCategory, dateSort]);

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
              <>
                <button
                  type="button"
                  onClick={onReloadTrackedImages}
                  className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                >
                  <Settings2 className="h-4 w-4 text-sky-700" />
                  <span>Reload</span>
                </button>
                <button
                  type="button"
                  onClick={() => setViewMode("thumbnail")}
                  className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                >
                  <WandSparkles className="h-4 w-4 text-indigo-700" />
                  <span>Thumbnails</span>
                </button>
                <button
                  type="button"
                  onClick={() => setViewMode("row")}
                  className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                >
                  <Database className="h-4 w-4 text-emerald-700" />
                  <span>Rows</span>
                </button>
                <button
                  type="button"
                  onClick={() => setIsFilterOpen((current) => !current)}
                  className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                >
                  <Filter className="h-4 w-4 text-slate-700" />
                  <span>Filters</span>
                </button>
              </>
            }
          />
        </CardHeader>
        <CardContent>
          {isFilterOpen ? (
            <div className="mb-4 grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 md:grid-cols-2">
              <label className="space-y-1">
                <span className="flex items-center gap-1 text-xs font-medium text-slate-600">
                  <Filter className="h-3.5 w-3.5" />
                  Category
                </span>
                <select
                  value={selectedCategory}
                  onChange={(event) => setSelectedCategory(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                >
                  <option value="all">All categories</option>
                  {categoryOptions.map((name) => (
                    <option key={name} value={name}>
                      {name}
                    </option>
                  ))}
                </select>
              </label>

              <label className="space-y-1">
                <span className="flex items-center gap-1 text-xs font-medium text-slate-600">
                  <ArrowDownAZ className="h-3.5 w-3.5" />
                  Sort by date
                </span>
                <select
                  value={dateSort}
                  onChange={(event) => setDateSort(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                >
                  <option value="date_desc">Newest first</option>
                  <option value="date_asc">Oldest first</option>
                </select>
              </label>
            </div>
          ) : null}

          {isLoadingList ? (
            <p className="text-sm text-slate-600">Loading tracked images...</p>
          ) : visibleImages.length === 0 ? (
            <MediaFilesEmptyState
              isLoadingList={isLoadingList}
              onReloadTrackedImages={onReloadTrackedImages}
            />
          ) : viewMode === "thumbnail" ? (
            <MediaThumbnailGrid
              trackedImages={visibleImages}
              onDeleteLinkClick={onDeleteLinkClick}
              onViewClick={(image) => handleOpenImageModal(image, "view")}
              onEditClick={(image) => handleOpenImageModal(image, "edit")}
            />
          ) : (
            <MediaRowsList
              trackedImages={visibleImages}
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
        onClose={handleCloseImageModal}
      />
    </>
  );
}
