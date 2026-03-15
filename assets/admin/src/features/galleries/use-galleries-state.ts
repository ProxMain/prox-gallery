import { useCallback } from "react";

import { useLoadableCollection } from "@/lib/use-loadable-collection";
import type {
  GalleryActionController,
  GalleryCreatePageResponse,
  GalleryItem
} from "@/modules/gallery/controllers/gallery-action-controller";

type GalleryOverrides = Parameters<GalleryActionController["createGallery"]>[3];

type CreateGalleryPayload = GalleryOverrides & {
  name?: string;
  description?: string;
  template?: string;
};

type RenameGalleryPayload = GalleryOverrides & {
  id?: number;
  name?: string;
  description?: string;
  template?: string;
};

type DeleteGalleryPayload = {
  id: number;
};

function collectOverrides(payload?: Partial<GalleryOverrides>): GalleryOverrides {
  const overrides: GalleryOverrides = {};

  if (!payload) {
    return overrides;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "grid_columns_override")) {
    overrides.grid_columns_override = payload.grid_columns_override;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "lightbox_override")) {
    overrides.lightbox_override = payload.lightbox_override;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "hover_zoom_override")) {
    overrides.hover_zoom_override = payload.hover_zoom_override;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "full_width_override")) {
    overrides.full_width_override = payload.full_width_override;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "transition_override")) {
    overrides.transition_override = payload.transition_override;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "show_title")) {
    overrides.show_title = payload.show_title;
  }

  if (Object.prototype.hasOwnProperty.call(payload, "show_description")) {
    overrides.show_description = payload.show_description;
  }

  return overrides;
}

export function useGalleriesState(galleryController: GalleryActionController | null) {
  const {
    items,
    isLoading,
    error,
    load,
    reload
  } = useLoadableCollection<GalleryActionController, GalleryItem>({
    missingMessage: "Galleries action configuration is missing.",
    loadErrorMessage: "Failed to load galleries.",
    controller: galleryController,
    loadItems: async (controller) => {
      const response = await controller.listGalleries();
      return response.items;
    }
  });

  const createGallery = useCallback(async (payload: CreateGalleryPayload = {}) => {
    const name = payload?.name ?? "";
    const description = payload?.description ?? "";
    const template = payload?.template ?? "basic-grid";

    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    const created = await galleryController.createGallery(name, description, template, collectOverrides(payload));
    const galleryId = Number(created?.item?.id ?? 0);

    if (galleryId <= 0) {
      throw new Error("Gallery was created without a valid ID.");
    }

    await galleryController.createGalleryPage(galleryId);
    await reload();
  }, [galleryController, reload]);

  const renameGallery = useCallback(async (payload: RenameGalleryPayload = {}) => {
    const id = payload?.id ?? 0;
    const name = payload?.name ?? "";
    const description = payload?.description ?? "";
    const template = payload?.template;

    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.renameGallery(id, name, description, template, collectOverrides(payload));
    await reload();
  }, [galleryController, reload]);

  const deleteGallery = useCallback(async ({ id }: DeleteGalleryPayload) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.deleteGallery(id);
    await reload();
  }, [galleryController, reload]);

  const createGalleryPage = useCallback(async ({ id }: DeleteGalleryPayload): Promise<GalleryCreatePageResponse> => {
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
