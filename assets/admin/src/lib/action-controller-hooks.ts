import { useMemo } from "react";

import { TemplateSettingsActionController } from "@/modules/admin/controllers/template-settings-action-controller";
import { TrackingActionController } from "@/modules/admin/controllers/tracking-action-controller";
import { GalleryActionController } from "@/modules/gallery/controllers/gallery-action-controller";
import { MediaCategoryActionController } from "@/modules/media-library/controllers/media-category-action-controller";
import { MediaManagerActionController } from "@/modules/media-library/controllers/media-manager-action-controller";
import { OpenAiActionController } from "@/modules/openai/controllers/openai-action-controller";

type AjaxActionDefinition = {
  action: string;
  nonce: string;
};

type AdminConfig = {
  ajax_url: string;
  action_controllers?: {
    media_manager?: {
      list?: AjaxActionDefinition;
      update?: AjaxActionDefinition;
    };
    media_category?: {
      suggest?: AjaxActionDefinition;
      list?: AjaxActionDefinition;
      assign?: AjaxActionDefinition;
    };
    galleries?: {
      list?: AjaxActionDefinition;
      create?: AjaxActionDefinition;
      rename?: AjaxActionDefinition;
      delete?: AjaxActionDefinition;
      list_image_galleries?: AjaxActionDefinition;
      set_image_galleries?: AjaxActionDefinition;
      add_images?: AjaxActionDefinition;
      set_images?: AjaxActionDefinition;
      create_page?: AjaxActionDefinition;
    };
    template_settings?: {
      get?: AjaxActionDefinition;
      update?: AjaxActionDefinition;
    };
    tracking?: {
      get?: AjaxActionDefinition;
    };
    openai?: {
      settings_get?: AjaxActionDefinition;
      settings_update?: AjaxActionDefinition;
      config_get?: AjaxActionDefinition;
      generate?: AjaxActionDefinition;
      apply?: AjaxActionDefinition;
    };
  };
};

export function useMediaManagerActionController(config: AdminConfig): MediaManagerActionController | null {
  return useMemo(() => {
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
}

export function useMediaCategoryActionController(config: AdminConfig): MediaCategoryActionController | null {
  return useMemo(() => {
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
}

export function useGalleryActionController(config: AdminConfig): GalleryActionController | null {
  return useMemo(() => {
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
}

export function useTemplateSettingsActionController(config: AdminConfig): TemplateSettingsActionController | null {
  return useMemo(() => {
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
}

export function useTrackingActionController(config: AdminConfig): TrackingActionController | null {
  return useMemo(() => {
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
}

export function useOpenAiActionController(config: AdminConfig): OpenAiActionController | null {
  return useMemo(() => {
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
}
