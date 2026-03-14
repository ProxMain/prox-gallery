import { useCallback } from "react";

import { useLoadableCollection } from "@/lib/use-loadable-collection";

export function useGalleriesState(galleryController) {
  const {
    items,
    isLoading,
    error,
    load,
    reload
  } = useLoadableCollection({
    missingMessage: "Galleries action configuration is missing.",
    loadErrorMessage: "Failed to load galleries.",
    controller: galleryController,
    loadItems: async (controller) => {
      const response = await controller.listGalleries();
      return response.items;
    }
  });

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

    const created = await galleryController.createGallery(name, description, template, {
      ...overrides
    });

    const galleryId = Number(created?.item?.id ?? 0);

    if (galleryId <= 0) {
      throw new Error("Gallery was created without a valid ID.");
    }

    await galleryController.createGalleryPage(galleryId);
    await reload();
  }, [galleryController, reload]);

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
    await reload();
  }, [galleryController, reload]);

  const deleteGallery = useCallback(async ({ id }) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.deleteGallery(id);
    await reload();
  }, [galleryController, reload]);

  const createGalleryPage = useCallback(async ({ id }) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    return galleryController.createGalleryPage(id);
  }, [galleryController]);

  return {
    galleries: items,
    isLoading,
    error,
    loadGalleries: load,
    reloadGalleries: reload,
    createGallery,
    renameGallery,
    deleteGallery,
    createGalleryPage
  };
}
