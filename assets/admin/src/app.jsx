import { useEffect, useMemo, useState } from "react";

import { TopBar } from "@/core/topbar";
import { DashboardSection } from "@/features/dashboard/dashboard-section";
import { GalleriesSection } from "@/features/galleries/galleries-section";
import { useGalleriesState } from "@/features/galleries/use-galleries-state";
import { MediaManagerSection } from "@/features/media-manager/media-manager-section";
import { useMediaManagerState } from "@/features/media-manager/use-media-manager-state";
import { SettingsSection } from "@/features/settings/settings-section";
import { getAdminConfig } from "@/lib/admin-config";
import { GalleryActionController } from "@/modules/gallery/controllers/gallery-action-controller";
import { MediaCategoryActionController } from "@/modules/media-library/controllers/media-category-action-controller";
import {
  ADMIN_MENU_ITEMS,
  sectionDescription,
  sectionTitle
} from "@/lib/admin-logic";
import { MediaManagerActionController } from "@/modules/media-library/controllers/media-manager-action-controller";
import { TemplateSettingsActionController } from "@/modules/admin/controllers/template-settings-action-controller";
import { TrackingActionController } from "@/modules/admin/controllers/tracking-action-controller";
import { OpenAiActionController } from "@/modules/openai/controllers/openai-action-controller";

export function App() {
  const [activeMenu, setActiveMenu] = useState("dashboard");
  const config = getAdminConfig();
  const title = sectionTitle(activeMenu);
  const description = sectionDescription(activeMenu);

  const mediaManagerController = useMemo(() => {
    const listDefinition = config.action_controllers?.media_manager?.list;
    const updateDefinition = config.action_controllers?.media_manager?.update;

    if (config.ajax_url === "" || !listDefinition || !updateDefinition) {
      return null;
    }

    return new MediaManagerActionController(
      { ajax_url: config.ajax_url },
      { list: listDefinition, update: updateDefinition }
    );
  }, [
    config.ajax_url,
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
    const suggestDefinition = config.action_controllers?.media_category?.suggest;
    const listDefinition = config.action_controllers?.media_category?.list;
    const assignDefinition = config.action_controllers?.media_category?.assign;

    if (config.ajax_url === "" || !suggestDefinition || !listDefinition || !assignDefinition) {
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
    config.action_controllers?.media_category?.suggest?.action,
    config.action_controllers?.media_category?.suggest?.nonce,
    config.action_controllers?.media_category?.list?.action,
    config.action_controllers?.media_category?.list?.nonce,
    config.action_controllers?.media_category?.assign?.action,
    config.action_controllers?.media_category?.assign?.nonce
  ]);

  const galleryController = useMemo(() => {
    const listDefinition = config.action_controllers?.galleries?.list;
    const createDefinition = config.action_controllers?.galleries?.create;
    const renameDefinition = config.action_controllers?.galleries?.rename;
    const deleteDefinition = config.action_controllers?.galleries?.delete;
    const listImageGalleriesDefinition = config.action_controllers?.galleries?.list_image_galleries;
    const setImageGalleriesDefinition = config.action_controllers?.galleries?.set_image_galleries;
    const addImagesDefinition = config.action_controllers?.galleries?.add_images;
    const setImagesDefinition = config.action_controllers?.galleries?.set_images;
    const createPageDefinition = config.action_controllers?.galleries?.create_page;

    if (
      config.ajax_url === ""
      || !listDefinition
      || !createDefinition
      || !renameDefinition
      || !deleteDefinition
      || !listImageGalleriesDefinition
      || !setImageGalleriesDefinition
      || !addImagesDefinition
      || !setImagesDefinition
      || !createPageDefinition
    ) {
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
    const getDefinition = config.action_controllers?.template_settings?.get;
    const updateDefinition = config.action_controllers?.template_settings?.update;

    if (config.ajax_url === "" || !getDefinition || !updateDefinition) {
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
    config.action_controllers?.template_settings?.get?.action,
    config.action_controllers?.template_settings?.get?.nonce,
    config.action_controllers?.template_settings?.update?.action,
    config.action_controllers?.template_settings?.update?.nonce
  ]);

  const trackingController = useMemo(() => {
    const getDefinition = config.action_controllers?.tracking?.get;

    if (config.ajax_url === "" || !getDefinition) {
      return null;
    }

    return new TrackingActionController(
      { ajax_url: config.ajax_url },
      {
        get: getDefinition
      }
    );
  }, [
    config.ajax_url,
    config.action_controllers?.tracking?.get?.action,
    config.action_controllers?.tracking?.get?.nonce
  ]);

  const openAiController = useMemo(() => {
    const settingsGetDefinition = config.action_controllers?.openai?.settings_get;
    const settingsUpdateDefinition = config.action_controllers?.openai?.settings_update;
    const configGetDefinition = config.action_controllers?.openai?.config_get;
    const generateDefinition = config.action_controllers?.openai?.generate;
    const applyDefinition = config.action_controllers?.openai?.apply;

    if (
      config.ajax_url === ""
      || !settingsGetDefinition
      || !settingsUpdateDefinition
      || !configGetDefinition
      || !generateDefinition
      || !applyDefinition
    ) {
      return null;
    }

    return new OpenAiActionController(
      { ajax_url: config.ajax_url },
      {
        settings_get: settingsGetDefinition,
        settings_update: settingsUpdateDefinition,
        config_get: configGetDefinition,
        generate: generateDefinition,
        apply: applyDefinition
      }
    );
  }, [
    config.ajax_url,
    config.action_controllers?.openai?.settings_get?.action,
    config.action_controllers?.openai?.settings_get?.nonce,
    config.action_controllers?.openai?.settings_update?.action,
    config.action_controllers?.openai?.settings_update?.nonce,
    config.action_controllers?.openai?.config_get?.action,
    config.action_controllers?.openai?.config_get?.nonce,
    config.action_controllers?.openai?.generate?.action,
    config.action_controllers?.openai?.generate?.nonce,
    config.action_controllers?.openai?.apply?.action,
    config.action_controllers?.openai?.apply?.nonce
  ]);

  const [dashboardSummary, setDashboardSummary] = useState(null);
  const [isLoadingDashboard, setIsLoadingDashboard] = useState(false);
  const [dashboardError, setDashboardError] = useState("");

  const handleMenuSelect = async (nextMenu) => {
    setActiveMenu(nextMenu);

    if (nextMenu === "dashboard") {
      if (trackingController) {
        try {
          setIsLoadingDashboard(true);
          setDashboardError("");
          const response = await trackingController.getSummary();
          setDashboardSummary(response.summary);
        } catch (error) {
          const message = error instanceof Error ? error.message : "Failed to load dashboard analytics.";
          setDashboardError(message);
        } finally {
          setIsLoadingDashboard(false);
        }
      }
      return;
    }

    if (nextMenu === "media-manager") {
      await loadTrackedImages();
      return;
    }

    if (nextMenu === "galleries") {
      await loadGalleries();
    }
  };

  useEffect(() => {
    if (activeMenu !== "dashboard" || !trackingController) {
      return;
    }

    let active = true;

    const loadDashboard = async () => {
      try {
        setIsLoadingDashboard(true);
        setDashboardError("");
        const response = await trackingController.getSummary();

        if (!active) {
          return;
        }

        setDashboardSummary(response.summary);
      } catch (error) {
        if (!active) {
          return;
        }

        const message = error instanceof Error ? error.message : "Failed to load dashboard analytics.";
        setDashboardError(message);
      } finally {
        if (active) {
          setIsLoadingDashboard(false);
        }
      }
    };

    void loadDashboard();

    return () => {
      active = false;
    };
  }, [activeMenu, trackingController]);

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
        <DashboardSection
          summary={dashboardSummary}
          isLoading={isLoadingDashboard}
          error={dashboardError}
        />
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
          openAiController={openAiController}
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
          openAiController={openAiController}
        />
      )}
    </main>
  );
}
