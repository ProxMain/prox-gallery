import { useCallback, useState } from "react";

import { useLoadableCollection } from "@/lib/use-loadable-collection";
import type {
  MediaManagerActionController,
  MediaManagerTrackedImage
} from "@/modules/media-library/controllers/media-manager-action-controller";

type ViewMode = "thumbnail" | "row";

export function useMediaManagerState(mediaManagerController: MediaManagerActionController | null) {
  const [viewMode, setViewMode] = useState<ViewMode>("thumbnail");
  const {
    items,
    isLoading,
    error,
    load,
    reload
  } = useLoadableCollection<MediaManagerActionController, MediaManagerTrackedImage>({
    missingMessage: "Media manager action configuration is missing.",
    loadErrorMessage: "Failed to load tracked images.",
    controller: mediaManagerController,
    loadItems: async (controller) => {
      const trackedResponse = await controller.listTrackedImages();
      return trackedResponse.items;
    }
  });

  const handleSetViewMode = useCallback((nextMode: ViewMode) => {
    setViewMode(nextMode);
  }, []);

  return {
    viewMode,
    trackedImages: items,
    isLoadingList: isLoading,
    listError: error,
    setViewMode: handleSetViewMode,
    loadTrackedImages: load,
    reloadTrackedImages: reload
  };
}
