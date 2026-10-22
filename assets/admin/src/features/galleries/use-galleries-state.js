import { galleryOverrides } from "./gallery-payload";
import { useCallback } from "react";

import { useLoadableCollection } from "@/lib/use-loadable-collection";

export function useGalleriesState(galleryController) {
  const { items, isLoading, error, load, reload } = useLoadableCollection({
    missingMessage: "Galleries action configuration is missing.",
    loadErrorMessage: "Failed to load galleries.",
    controller: galleryController,
    loadItems: async (controller) => {
      const response = await controller.listGalleries();
      return response.items;
    },
  });

  const createGallery = useCallback(
    async (payload) => {
      const name = payload?.name ?? "";
      const description = payload?.description ?? "";
      const template = payload?.template ?? "basic-grid";

      if (!galleryController) {
        throw new Error("Galleries action configuration is missing.");
      }

      const overrides = galleryOverrides(payload);

      const created = await galleryController.createGallery(
        name,
        description,
        template,
        {
          ...overrides,
        },
      );

      const galleryId = Number(created?.item?.id ?? 0);

      if (galleryId <= 0) {
        throw new Error("Gallery was created without a valid ID.");
      }

      await reload();
    },
    [galleryController, reload],
  );

  const renameGallery = useCallback(
    async (payload) => {
      const id = payload?.id ?? 0;
      const name = payload?.name ?? "";
      const description = payload?.description ?? "";
      const template = payload?.template;

      if (!galleryController) {
        throw new Error("Galleries action configuration is missing.");
      }

      const overrides = galleryOverrides(payload);

      await galleryController.renameGallery(id, name, description, template, {
        ...overrides,
      });
      await reload();
    },
    [galleryController, reload],
  );

  const deleteGallery = useCallback(
    async ({ id }) => {
      if (!galleryController) {
        throw new Error("Galleries action configuration is missing.");
      }

      await galleryController.deleteGallery(id);
      await reload();
    },
    [galleryController, reload],
  );

  const createGalleryPage = useCallback(
    async ({ id }) => {
      if (!galleryController) {
        throw new Error("Galleries action configuration is missing.");
      }

      return galleryController.createGalleryPage(id);
    },
    [galleryController],
  );

  return {
    galleries: items,
    isLoading,
    error,
    loadGalleries: load,
    reloadGalleries: reload,
    createGallery,
    renameGallery,
    deleteGallery,
    createGalleryPage,
  };
}
