import { useCallback, useReducer } from "react";

const initialState = {
  galleries: [],
  isLoading: false,
  error: "",
  hasLoaded: false
};

function reducer(state, action) {
  switch (action.type) {
    case "load_start":
      return {
        ...state,
        isLoading: true,
        error: ""
      };
    case "load_success":
      return {
        ...state,
        isLoading: false,
        error: "",
        galleries: action.payload,
        hasLoaded: true
      };
    case "load_error":
      return {
        ...state,
        isLoading: false,
        error: action.payload
      };
    default:
      return state;
  }
}

export function useGalleriesState(galleryController) {
  const [state, dispatch] = useReducer(reducer, initialState);

  const loadGalleries = useCallback(async ({ force = false } = {}) => {
    if (!galleryController) {
      dispatch({ type: "load_error", payload: "Galleries action configuration is missing." });
      return;
    }

    if (state.isLoading) {
      return;
    }

    if (!force && state.hasLoaded) {
      return;
    }

    dispatch({ type: "load_start" });

    try {
      const response = await galleryController.listGalleries();
      dispatch({ type: "load_success", payload: response.items });
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to load galleries.";
      dispatch({ type: "load_error", payload: message });
    }
  }, [galleryController, state.hasLoaded, state.isLoading]);

  const createGallery = useCallback(async (payload) => {
    const name = payload?.name ?? "";
    const description = payload?.description ?? "";
    const template = payload?.template ?? "basic-grid";

    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    const overrides = {};

    if (payload && Object.prototype.hasOwnProperty.call(payload, "grid_columns_override")) {
      overrides.grid_columns_override = payload.grid_columns_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "lightbox_override")) {
      overrides.lightbox_override = payload.lightbox_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "hover_zoom_override")) {
      overrides.hover_zoom_override = payload.hover_zoom_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "full_width_override")) {
      overrides.full_width_override = payload.full_width_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "transition_override")) {
      overrides.transition_override = payload.transition_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "show_title")) {
      overrides.show_title = payload.show_title;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "show_description")) {
      overrides.show_description = payload.show_description;
    }

    await galleryController.createGallery(name, description, template, {
      ...overrides
    });
    await loadGalleries({ force: true });
  }, [galleryController, loadGalleries]);

  const renameGallery = useCallback(async (payload) => {
    const id = payload?.id ?? 0;
    const name = payload?.name ?? "";
    const description = payload?.description ?? "";
    const template = payload?.template;

    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    const overrides = {};

    if (payload && Object.prototype.hasOwnProperty.call(payload, "grid_columns_override")) {
      overrides.grid_columns_override = payload.grid_columns_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "lightbox_override")) {
      overrides.lightbox_override = payload.lightbox_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "hover_zoom_override")) {
      overrides.hover_zoom_override = payload.hover_zoom_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "full_width_override")) {
      overrides.full_width_override = payload.full_width_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "transition_override")) {
      overrides.transition_override = payload.transition_override;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "show_title")) {
      overrides.show_title = payload.show_title;
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, "show_description")) {
      overrides.show_description = payload.show_description;
    }

    await galleryController.renameGallery(id, name, description, template, {
      ...overrides
    });
    await loadGalleries({ force: true });
  }, [galleryController, loadGalleries]);

  const deleteGallery = useCallback(async ({ id }) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.deleteGallery(id);
    await loadGalleries({ force: true });
  }, [galleryController, loadGalleries]);

  const createGalleryPage = useCallback(async ({ id }) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    return galleryController.createGalleryPage(id);
  }, [galleryController]);

  return {
    ...state,
    loadGalleries,
    reloadGalleries: () => loadGalleries({ force: true }),
    createGallery,
    renameGallery,
    deleteGallery,
    createGalleryPage
  };
}
