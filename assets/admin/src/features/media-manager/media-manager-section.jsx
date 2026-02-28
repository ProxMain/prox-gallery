import { MediaFilesCard } from "@/features/media-manager/components/media-files-card";
import { MediaManagerHeader } from "@/features/media-manager/components/media-manager-header";

export function MediaManagerSection({
  config,
  isLoadingList,
  listError,
  trackedImages,
  viewMode,
  setViewMode,
  onReloadTrackedImages,
  onUpdateMediaMetadata,
  onLoadMediaCategories,
  onSuggestMediaCategories,
  onAssignMediaCategories
}) {
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

  return (
    <section className="space-y-6">
      <MediaManagerHeader config={config} />
      <MediaFilesCard
        isLoadingList={isLoadingList}
        listError={listError}
        trackedImages={trackedImages}
        viewMode={viewMode}
        setViewMode={setViewMode}
        onReloadTrackedImages={onReloadTrackedImages}
        onUpdateMediaMetadata={onUpdateMediaMetadata}
        onLoadMediaCategories={onLoadMediaCategories}
        onSuggestMediaCategories={onSuggestMediaCategories}
        onAssignMediaCategories={onAssignMediaCategories}
        onDeleteLinkClick={handleDeleteLinkClick}
      />
    </section>
  );
}
