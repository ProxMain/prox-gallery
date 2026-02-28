import { Images } from "lucide-react";

import { Button } from "@/components/ui/button";

export function MediaFilesEmptyState({ isLoadingList, onReloadTrackedImages }) {
  return (
    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
      <div className="mb-4 rounded-full bg-white p-4 shadow-sm">
        <Images className="h-8 w-8 text-slate-500" aria-hidden="true" />
      </div>
      <h3 className="text-lg font-semibold text-slate-900">No media tracked yet</h3>
      <p className="mt-1 text-sm text-slate-600">Load tracked entries to review your media data.</p>
      <Button
        onClick={onReloadTrackedImages}
        disabled={isLoadingList}
        className="mt-6 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
      >
        {isLoadingList ? "Loading..." : "Load tracked media"}
      </Button>
    </div>
  );
}
