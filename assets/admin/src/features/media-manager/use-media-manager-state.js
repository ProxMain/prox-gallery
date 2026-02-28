import { useCallback, useReducer } from "react";

const initialState = {
  viewMode: "thumbnail",
  trackedImages: [],
  isLoadingList: false,
  listError: "",
  hasLoaded: false
};

function reducer(state, action) {
  switch (action.type) {
    case "set_view_mode":
      return {
        ...state,
        viewMode: action.payload
      };
    case "load_start":
      return {
        ...state,
        isLoadingList: true,
        listError: ""
      };
    case "load_success":
      return {
        ...state,
        isLoadingList: false,
        listError: "",
        trackedImages: action.payload,
        hasLoaded: true
      };
    case "load_error":
      return {
        ...state,
        isLoadingList: false,
        listError: action.payload
      };
    default:
      return state;
  }
}

export function useMediaManagerState(mediaManagerController) {
  const [state, dispatch] = useReducer(reducer, initialState);

  const setViewMode = useCallback((nextMode) => {
    dispatch({ type: "set_view_mode", payload: nextMode });
  }, []);

  const loadTrackedImages = useCallback(async ({ force = false } = {}) => {
    if (!mediaManagerController) {
      dispatch({
        type: "load_error",
        payload: "Media manager action configuration is missing."
      });
      return;
    }

    if (state.isLoadingList) {
      return;
    }

    if (!force && state.hasLoaded) {
      return;
    }

    dispatch({ type: "load_start" });

    try {
      const trackedResponse = await mediaManagerController.listTrackedImages();
      dispatch({ type: "load_success", payload: trackedResponse.items });
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to load tracked images.";
      dispatch({ type: "load_error", payload: message });
    }
  }, [mediaManagerController, state.hasLoaded, state.isLoadingList]);

  const reloadTrackedImages = useCallback(async () => {
    await loadTrackedImages({ force: true });
  }, [loadTrackedImages]);

  return {
    ...state,
    setViewMode,
    loadTrackedImages,
    reloadTrackedImages
  };
}
