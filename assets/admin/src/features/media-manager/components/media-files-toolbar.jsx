import { Database, Filter, Settings2, WandSparkles } from "lucide-react";

export function MediaFilesToolbar({
  onReloadTrackedImages,
  onSetThumbnailView,
  onSetRowView,
  onToggleFilters
}) {
  return (
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
        onClick={onSetThumbnailView}
        className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
      >
        <WandSparkles className="h-4 w-4 text-indigo-700" />
        <span>Thumbnails</span>
      </button>
      <button
        type="button"
        onClick={onSetRowView}
        className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
      >
        <Database className="h-4 w-4 text-emerald-700" />
        <span>Rows</span>
      </button>
      <button
        type="button"
        onClick={onToggleFilters}
        className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
      >
        <Filter className="h-4 w-4 text-slate-700" />
        <span>Filters</span>
      </button>
    </>
  );
}
