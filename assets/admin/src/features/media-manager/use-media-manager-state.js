import { useCallback, useState } from "react";

import { useLoadableCollection } from "@/lib/use-loadable-collection";

export function useMediaManagerState(mediaManagerController) {
  const [viewMode, setViewMode] = useState("thumbnail");
  const {
    items,
    isLoading,
    error,
    load,
    reload
  } = useLoadableCollection({
    missingMessage: "Media manager action configuration is missing.",
    loadErrorMessage: "Failed to load tracked images.",
    controller: mediaManagerController,
    loadItems: async (controller) => {
      const trackedResponse = await controller.listTrackedImages();
      return trackedResponse.items;
    }
  });

  const handleSetViewMode = useCallback((nextMode) => {
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
