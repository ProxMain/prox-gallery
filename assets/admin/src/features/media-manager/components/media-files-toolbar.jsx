import { Filter, LayoutGrid, List, RefreshCw, Search } from "lucide-react";

export function MediaFilesToolbar({
  onReloadTrackedImages,
  onSetThumbnailView,
  onSetRowView,
  onToggleFilters,
  searchQuery,
  onSearchQueryChange,
  viewMode,
  isFilterOpen,
  isLoading,
}) {
  return (
    <div className="pg-library-toolbar">
      <label className="pg-search">
        <Search size={17} aria-hidden="true" />
        <input
          type="search"
          aria-label="Search media by name"
          value={searchQuery}
          onChange={(event) => onSearchQueryChange(event.target.value)}
          placeholder="Find an image…"
        />
      </label>
      <button
        type="button"
        className="pg-filter-button"
        onClick={onToggleFilters}
        aria-expanded={isFilterOpen}
      >
        <Filter size={16} />
        Filters
      </button>
      <div className="pg-view-switch">
        <button
          type="button"
          onClick={onSetThumbnailView}
          aria-label="Thumbnail view"
          aria-pressed={viewMode === "thumbnail"}
        >
          <LayoutGrid size={17} />
        </button>
        <button
          type="button"
          onClick={onSetRowView}
          aria-label="Row view"
          aria-pressed={viewMode === "row"}
        >
          <List size={17} />
        </button>
      </div>
      <button
        type="button"
        className="pg-icon-button"
        onClick={onReloadTrackedImages}
        disabled={isLoading}
        aria-label="Refresh media"
      >
        <RefreshCw size={17} className={isLoading ? "animate-spin" : ""} />
      </button>
    </div>
  );
}
