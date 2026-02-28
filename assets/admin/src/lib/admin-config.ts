import type { AjaxActionDefinition } from "@/lib/abstract-action-controller";

export type ProxGalleryAdminConfig = {
  screen: string;
  rest_nonce: string;
  ajax_url: string;
  action_controllers?: {
    media_manager?: {
      list?: AjaxActionDefinition;
      sync?: AjaxActionDefinition;
    };
  };
};

export function getAdminConfig(): ProxGalleryAdminConfig {
  const source = window.ProxGalleryAdminConfig;
  const globalAjaxUrl = typeof window.ajaxurl === "string" ? window.ajaxurl : "";
  const fallbackAjaxUrl = globalAjaxUrl !== "" ? globalAjaxUrl : "/wp-admin/admin-ajax.php";

  if (!source || typeof source !== "object") {
    return {
      screen: "",
      rest_nonce: "",
      ajax_url: fallbackAjaxUrl
    };
  }

  return {
    screen: typeof source.screen === "string" ? source.screen : "",
    rest_nonce: typeof source.rest_nonce === "string" ? source.rest_nonce : "",
    ajax_url:
      typeof source.ajax_url === "string" && source.ajax_url !== ""
        ? source.ajax_url
        : fallbackAjaxUrl,
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
