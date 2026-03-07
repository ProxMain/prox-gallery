import type { AjaxActionDefinition } from "@/modules/core/controllers/abstract-action-controller";

export type GalleryTemplateDefinition = {
  slug: string;
  label: string;
  is_pro: boolean;
  available: boolean;
};

export type ProxGalleryAdminConfig = {
  screen: string;
  rest_nonce: string;
  ajax_url: string;
  action_controllers?: {
    media_manager?: {
      list?: AjaxActionDefinition;
      sync?: AjaxActionDefinition;
      update?: AjaxActionDefinition;
    };
    media_category?: {
      suggest?: AjaxActionDefinition;
      list?: AjaxActionDefinition;
      assign?: AjaxActionDefinition;
      taxonomy?: string;
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
      templates?: GalleryTemplateDefinition[];
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

export function getAdminConfig(): ProxGalleryAdminConfig {
  const source = window.ProxGalleryAdminConfig;

  if (!source || typeof source !== "object") {
    return {
      screen: "",
      rest_nonce: "",
      ajax_url: ""
    };
  }

  return {
    screen: typeof source.screen === "string" ? source.screen : "",
    rest_nonce: typeof source.rest_nonce === "string" ? source.rest_nonce : "",
    ajax_url: typeof source.ajax_url === "string" ? source.ajax_url : "",
    action_controllers:
      source.action_controllers && typeof source.action_controllers === "object"
        ? source.action_controllers
        : undefined
  };
}

declare global {
  interface Window {
    ProxGalleryAdminConfig?: ProxGalleryAdminConfig;
    ajaxurl?: string;
  }
}
