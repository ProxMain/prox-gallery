import { useMemo, useState } from "react";

import { TopBar } from "@/core/topbar";
import { DashboardSection } from "@/features/dashboard/dashboard-section";
import { GalleriesSection } from "@/features/galleries/galleries-section";
import { useGalleriesState } from "@/features/galleries/use-galleries-state";
import { MediaManagerSection } from "@/features/media-manager/media-manager-section";
import { useMediaManagerState } from "@/features/media-manager/use-media-manager-state";
import { SettingsSection } from "@/features/settings/settings-section";
import { getAdminConfig } from "@/lib/admin-config";
import { GalleryActionController } from "@/lib/gallery-action-controller";
import { MediaCategoryActionController } from "@/lib/media-category-action-controller";
import {
  ADMIN_MENU_ITEMS,
  sectionDescription,
  sectionTitle
} from "@/lib/admin-logic";
import { MediaManagerActionController } from "@/lib/media-manager-action-controller";
import { TemplateSettingsActionController } from "@/lib/template-settings-action-controller";

export function App() {
  const [activeMenu, setActiveMenu] = useState("dashboard");
  const config = getAdminConfig();
  const title = sectionTitle(activeMenu);
  const description = sectionDescription(activeMenu);

  const mediaManagerController = useMemo(() => {
    const listDefinition = config.action_controllers?.media_manager?.list ?? {
      action: "prox_gallery_media_manager_list",
      nonce: config.rest_nonce || ""
    };
    const updateDefinition = config.action_controllers?.media_manager?.update ?? {
      action: "prox_gallery_media_manager_update",
      nonce: config.rest_nonce || ""
    };

    if (config.ajax_url === "") {
      return null;
    }

    return new MediaManagerActionController(
      { ajax_url: config.ajax_url },
      { list: listDefinition, update: updateDefinition }
    );
  }, [
    config.ajax_url,
    config.rest_nonce,
    config.action_controllers?.media_manager?.list?.action,
    config.action_controllers?.media_manager?.list?.nonce,
    config.action_controllers?.media_manager?.update?.action,
    config.action_controllers?.media_manager?.update?.nonce
  ]);

  const {
    viewMode,
    trackedImages,
    isLoadingList,
    listError,
    setViewMode,
    loadTrackedImages,
    reloadTrackedImages
  } = useMediaManagerState(mediaManagerController);

  const mediaCategoryController = useMemo(() => {
    const suggestDefinition = config.action_controllers?.media_category?.suggest ?? {
      action: "prox_gallery_media_category_suggest",
      nonce: config.rest_nonce || ""
    };
    const listDefinition = config.action_controllers?.media_category?.list ?? {
      action: "prox_gallery_media_category_list",
      nonce: config.rest_nonce || ""
    };
    const assignDefinition = config.action_controllers?.media_category?.assign ?? {
      action: "prox_gallery_media_category_assign",
      nonce: config.rest_nonce || ""
    };

    if (config.ajax_url === "") {
      return null;
    }

    return new MediaCategoryActionController(
      { ajax_url: config.ajax_url },
      {
        suggest: suggestDefinition,
        list: listDefinition,
        assign: assignDefinition
      }
    );
  }, [
    config.ajax_url,
    config.rest_nonce,
    config.action_controllers?.media_category?.suggest?.action,
    config.action_controllers?.media_category?.suggest?.nonce,
    config.action_controllers?.media_category?.list?.action,
    config.action_controllers?.media_category?.list?.nonce,
    config.action_controllers?.media_category?.assign?.action,
    config.action_controllers?.media_category?.assign?.nonce
  ]);

  const galleryController = useMemo(() => {
    const listDefinition = config.action_controllers?.galleries?.list ?? {
      action: "prox_gallery_gallery_list",
      nonce: config.rest_nonce || ""
    };
    const createDefinition = config.action_controllers?.galleries?.create ?? {
      action: "prox_gallery_gallery_create",
      nonce: config.rest_nonce || ""
    };
    const renameDefinition = config.action_controllers?.galleries?.rename ?? {
      action: "prox_gallery_gallery_rename",
      nonce: config.rest_nonce || ""
    };
    const deleteDefinition = config.action_controllers?.galleries?.delete ?? {
      action: "prox_gallery_gallery_delete",
      nonce: config.rest_nonce || ""
    };
    const listImageGalleriesDefinition = config.action_controllers?.galleries?.list_image_galleries ?? {
      action: "prox_gallery_gallery_list_image_galleries",
      nonce: config.rest_nonce || ""
    };
    const setImageGalleriesDefinition = config.action_controllers?.galleries?.set_image_galleries ?? {
      action: "prox_gallery_gallery_set_image_galleries",
      nonce: config.rest_nonce || ""
    };
    const addImagesDefinition = config.action_controllers?.galleries?.add_images ?? {
      action: "prox_gallery_gallery_add_images",
      nonce: config.rest_nonce || ""
    };
    const setImagesDefinition = config.action_controllers?.galleries?.set_images ?? {
      action: "prox_gallery_gallery_set_images",
      nonce: config.rest_nonce || ""
    };
    const createPageDefinition = config.action_controllers?.galleries?.create_page ?? {
      action: "prox_gallery_gallery_create_page",
      nonce: config.rest_nonce || ""
    };

    if (config.ajax_url === "") {
      return null;
    }

    return new GalleryActionController(
      { ajax_url: config.ajax_url },
      {
        list: listDefinition,
        create: createDefinition,
        rename: renameDefinition,
        delete: deleteDefinition,
        list_image_galleries: listImageGalleriesDefinition,
        set_image_galleries: setImageGalleriesDefinition,
        add_images: addImagesDefinition,
        set_images: setImagesDefinition,
        create_page: createPageDefinition
      }
    );
  }, [
    config.ajax_url,
    config.rest_nonce,
    config.action_controllers?.galleries?.list?.action,
    config.action_controllers?.galleries?.list?.nonce,
    config.action_controllers?.galleries?.create?.action,
    config.action_controllers?.galleries?.create?.nonce,
    config.action_controllers?.galleries?.rename?.action,
    config.action_controllers?.galleries?.rename?.nonce,
    config.action_controllers?.galleries?.delete?.action,
    config.action_controllers?.galleries?.delete?.nonce,
    config.action_controllers?.galleries?.list_image_galleries?.action,
    config.action_controllers?.galleries?.list_image_galleries?.nonce,
    config.action_controllers?.galleries?.set_image_galleries?.action,
    config.action_controllers?.galleries?.set_image_galleries?.nonce,
    config.action_controllers?.galleries?.add_images?.action,
    config.action_controllers?.galleries?.add_images?.nonce,
    config.action_controllers?.galleries?.set_images?.action,
    config.action_controllers?.galleries?.set_images?.nonce,
    config.action_controllers?.galleries?.create_page?.action,
    config.action_controllers?.galleries?.create_page?.nonce
  ]);

  const {
    galleries,
    isLoading: isLoadingGalleries,
    error: galleriesError,
    loadGalleries,
    reloadGalleries,
    createGallery,
    renameGallery,
    deleteGallery,
    createGalleryPage
  } = useGalleriesState(galleryController);

  const templateSettingsController = useMemo(() => {
    const getDefinition = config.action_controllers?.template_settings?.get ?? {
      action: "prox_gallery_template_settings_get",
      nonce: config.rest_nonce || ""
    };
    const updateDefinition = config.action_controllers?.template_settings?.update ?? {
      action: "prox_gallery_template_settings_update",
      nonce: config.rest_nonce || ""
    };

    if (config.ajax_url === "") {
      return null;
    }

    return new TemplateSettingsActionController(
      { ajax_url: config.ajax_url },
      {
        get: getDefinition,
        update: updateDefinition
      }
    );
  }, [
    config.ajax_url,
    config.rest_nonce,
    config.action_controllers?.template_settings?.get?.action,
    config.action_controllers?.template_settings?.get?.nonce,
    config.action_controllers?.template_settings?.update?.action,
    config.action_controllers?.template_settings?.update?.nonce
  ]);

  const handleMenuSelect = async (nextMenu) => {
    setActiveMenu(nextMenu);

    if (nextMenu === "media-manager") {
      await loadTrackedImages();
      return;
    }

    if (nextMenu === "galleries") {
      await loadGalleries();
    }
  };

  const handleUpdateMediaMetadata = async (payload) => {
    if (!mediaManagerController) {
      throw new Error("Media manager action configuration is missing.");
    }

    const response = await mediaManagerController.updateTrackedImageMetadata(payload);
    await reloadTrackedImages();

    return response.item;
  };

  const handleLoadMediaCategories = async (attachmentId) => {
    if (!mediaCategoryController) {
      throw new Error("Media category action configuration is missing.");
    }

    const response = await mediaCategoryController.listForAttachment(attachmentId);
    return response.items;
  };

  const handleSuggestMediaCategories = async (query) => {
    if (!mediaCategoryController) {
      return [];
    }

    const response = await mediaCategoryController.suggestCategories(query, 12);
    return response.items;
  };

  const handleAssignMediaCategories = async (attachmentId, categories) => {
    if (!mediaCategoryController) {
      throw new Error("Media category action configuration is missing.");
    }

    const response = await mediaCategoryController.assignToAttachment(attachmentId, categories.join(","));
    return response.items;
  };

  const handleLoadImageGalleries = async (imageId) => {
    if (!galleryController) {
      return [];
    }

    const response = await galleryController.listImageGalleries(imageId);
    return response.gallery_ids;
  };

  const handleSetImageGalleries = async (imageId, galleryIds) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    const response = await galleryController.setImageGalleries(imageId, galleryIds);
    return response.gallery_ids;
  };

  const handleListGalleriesForImagePicker = async () => {
    if (!galleryController) {
      return [];
    }

    const response = await galleryController.listGalleries();
    return response.items;
  };

  const handleLoadTrackedImagesForGallery = async () => {
    if (!mediaManagerController) {
      return [];
    }

    const response = await mediaManagerController.listTrackedImages();
    return response.items;
  };

  const handleAddImagesToGallery = async (galleryId, imageIds) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.addImagesToGallery(galleryId, imageIds);
    await reloadGalleries();
  };

  const handleSetGalleryImages = async (galleryId, imageIds) => {
    if (!galleryController) {
      throw new Error("Galleries action configuration is missing.");
    }

    await galleryController.setGalleryImages(galleryId, imageIds);
    await reloadGalleries();
  };

  return (
    <main className="prox-gallery-admin mx-auto max-w-[1100px] space-y-8 py-8">
      <TopBar menuItems={ADMIN_MENU_ITEMS} activeMenu={activeMenu} onSelectMenu={handleMenuSelect} />

      {activeMenu === "dashboard" ? (
        <DashboardSection />
      ) : activeMenu === "media-manager" ? (
        <MediaManagerSection
          config={config}
          isLoadingList={isLoadingList}
          listError={listError}
          trackedImages={trackedImages}
          viewMode={viewMode}
          setViewMode={setViewMode}
          onReloadTrackedImages={reloadTrackedImages}
          onUpdateMediaMetadata={handleUpdateMediaMetadata}
          onLoadMediaCategories={handleLoadMediaCategories}
          onSuggestMediaCategories={handleSuggestMediaCategories}
          onAssignMediaCategories={handleAssignMediaCategories}
          onListGalleries={handleListGalleriesForImagePicker}
          onLoadImageGalleries={handleLoadImageGalleries}
          onSetImageGalleries={handleSetImageGalleries}
        />
      ) : activeMenu === "galleries" ? (
        <GalleriesSection
          config={config}
          galleries={galleries}
          isLoading={isLoadingGalleries}
          error={galleriesError}
          onReloadGalleries={reloadGalleries}
          onCreateGallery={createGallery}
          onRenameGallery={renameGallery}
          onDeleteGallery={deleteGallery}
          onCreateGalleryPage={createGalleryPage}
          onLoadTrackedImages={handleLoadTrackedImagesForGallery}
          onAddImagesToGallery={handleAddImagesToGallery}
          onSetGalleryImages={handleSetGalleryImages}
        />
      ) : (
        <SettingsSection
          title={title}
          description={description}
          config={config}
          templateSettingsController={templateSettingsController}
        />
      )}
    </main>
  );
}
