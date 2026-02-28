import { useMemo, useState } from "react";

import { TopBar } from "@/core/topbar";
import { DashboardSection } from "@/features/dashboard/dashboard-section";
import { GalleriesSection } from "@/features/galleries/galleries-section";
import { MediaManagerSection } from "@/features/media-manager/media-manager-section";
import { useMediaManagerState } from "@/features/media-manager/use-media-manager-state";
import { SettingsSection } from "@/features/settings/settings-section";
import { getAdminConfig } from "@/lib/admin-config";
import { MediaCategoryActionController } from "@/lib/media-category-action-controller";
import {
  ADMIN_MENU_ITEMS,
  sectionDescription,
  sectionTitle
} from "@/lib/admin-logic";
import { MediaManagerActionController } from "@/lib/media-manager-action-controller";

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

  const handleMenuSelect = async (nextMenu) => {
    setActiveMenu(nextMenu);

    if (nextMenu === "media-manager") {
      await loadTrackedImages();
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
        />
      ) : activeMenu === "galleries" ? (
        <GalleriesSection title={title} description={description} />
      ) : (
        <SettingsSection title={title} description={description} config={config} />
      )}
    </main>
  );
}
