import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/lib/abstract-action-controller";

type GalleryDefinitions = {
  list: AjaxActionDefinition;
  create: AjaxActionDefinition;
  rename: AjaxActionDefinition;
  delete: AjaxActionDefinition;
  list_image_galleries: AjaxActionDefinition;
  set_image_galleries: AjaxActionDefinition;
  add_images: AjaxActionDefinition;
  set_images: AjaxActionDefinition;
  create_page: AjaxActionDefinition;
};

export type GalleryItem = {
  id: number;
  name: string;
  description: string;
  template?: string;
  grid_columns_override?: number | null;
  lightbox_override?: boolean | null;
  hover_zoom_override?: boolean | null;
  full_width_override?: boolean | null;
  transition_override?: "none" | "slide" | "fade" | "explode" | "implode" | null;
  show_title?: boolean;
  show_description?: boolean;
  created_at: string;
  image_ids?: number[];
  image_count?: number;
};

export type GalleryListResponse = {
  action: string;
  items: GalleryItem[];
  count: number;
};

export type GalleryCreateResponse = {
  action: string;
  item: GalleryItem;
};

export type GalleryRenameResponse = {
  action: string;
  item: GalleryItem;
};

export type GalleryDeleteResponse = {
  action: string;
  deleted_id: number;
};

export type GalleryImageGalleriesResponse = {
  action: string;
  image_id: number;
  gallery_ids: number[];
};

export type GalleryCreatePageResponse = {
  action: string;
  gallery_id: number;
  page_id: number;
  page_url: string;
  menu_id: number;
  menu_item_id: number;
};

export class GalleryActionController extends AbstractActionController<GalleryDefinitions> {
  constructor(config: ActionControllerConfig, definitions: GalleryDefinitions) {
    super(config, definitions);
  }

  public listGalleries(): Promise<GalleryListResponse> {
    return this.dispatch<GalleryListResponse>("list");
  }

  public createGallery(
    name: string,
    description = "",
    template = "basic-grid",
    overrides: {
      grid_columns_override?: number | null;
      lightbox_override?: boolean | null;
      hover_zoom_override?: boolean | null;
      full_width_override?: boolean | null;
      transition_override?: "none" | "slide" | "fade" | "explode" | "implode" | null;
      show_title?: boolean;
      show_description?: boolean;
    } = {}
  ): Promise<GalleryCreateResponse> {
    const payload: Record<string, string | number | boolean | null> = {
      name,
      description,
      template
    };

    if (Object.prototype.hasOwnProperty.call(overrides, "grid_columns_override")) {
      payload.grid_columns_override = overrides.grid_columns_override ?? "inherit";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "lightbox_override")) {
      payload.lightbox_override =
        overrides.lightbox_override === null || overrides.lightbox_override === undefined
          ? "inherit"
          : overrides.lightbox_override
          ? "1"
          : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "hover_zoom_override")) {
      payload.hover_zoom_override =
        overrides.hover_zoom_override === null || overrides.hover_zoom_override === undefined
          ? "inherit"
          : overrides.hover_zoom_override
          ? "1"
          : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "full_width_override")) {
      payload.full_width_override =
        overrides.full_width_override === null || overrides.full_width_override === undefined
          ? "inherit"
          : overrides.full_width_override
          ? "1"
          : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "transition_override")) {
      payload.transition_override = overrides.transition_override ?? "inherit";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "show_title")) {
      payload.show_title = overrides.show_title ? "1" : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "show_description")) {
      payload.show_description = overrides.show_description ? "1" : "0";
    }

    return this.dispatch<GalleryCreateResponse>("create", payload);
  }

  public renameGallery(
    id: number,
    name: string,
    description = "",
    template?: string,
    overrides: {
      grid_columns_override?: number | null;
      lightbox_override?: boolean | null;
      hover_zoom_override?: boolean | null;
      full_width_override?: boolean | null;
      transition_override?: "none" | "slide" | "fade" | "explode" | "implode" | null;
      show_title?: boolean;
      show_description?: boolean;
    } = {}
  ): Promise<GalleryRenameResponse> {
    const payload: Record<string, string | number | boolean | null> = {
      id,
      name,
      description
    };

    if (typeof template === "string" && template !== "") {
      payload.template = template;
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "grid_columns_override")) {
      payload.grid_columns_override = overrides.grid_columns_override ?? "inherit";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "lightbox_override")) {
      payload.lightbox_override =
        overrides.lightbox_override === null || overrides.lightbox_override === undefined
          ? "inherit"
          : overrides.lightbox_override
          ? "1"
          : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "hover_zoom_override")) {
      payload.hover_zoom_override =
        overrides.hover_zoom_override === null || overrides.hover_zoom_override === undefined
          ? "inherit"
          : overrides.hover_zoom_override
          ? "1"
          : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "full_width_override")) {
      payload.full_width_override =
        overrides.full_width_override === null || overrides.full_width_override === undefined
          ? "inherit"
          : overrides.full_width_override
          ? "1"
          : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "transition_override")) {
      payload.transition_override = overrides.transition_override ?? "inherit";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "show_title")) {
      payload.show_title = overrides.show_title ? "1" : "0";
    }

    if (Object.prototype.hasOwnProperty.call(overrides, "show_description")) {
      payload.show_description = overrides.show_description ? "1" : "0";
    }

    return this.dispatch<GalleryRenameResponse>("rename", payload);
  }

  public deleteGallery(id: number): Promise<GalleryDeleteResponse> {
    return this.dispatch<GalleryDeleteResponse>("delete", { id });
  }

  public listImageGalleries(imageId: number): Promise<GalleryImageGalleriesResponse> {
    return this.dispatch<GalleryImageGalleriesResponse>("list_image_galleries", {
      image_id: imageId
    });
  }

  public setImageGalleries(imageId: number, galleryIds: number[]): Promise<GalleryImageGalleriesResponse> {
    return this.dispatch<GalleryImageGalleriesResponse>("set_image_galleries", {
      image_id: imageId,
      gallery_ids: galleryIds.join(",")
    });
  }

  public addImagesToGallery(galleryId: number, imageIds: number[]): Promise<GalleryRenameResponse> {
    return this.dispatch<GalleryRenameResponse>("add_images", {
      gallery_id: galleryId,
      image_ids: imageIds.join(",")
    });
  }

  public setGalleryImages(galleryId: number, imageIds: number[]): Promise<GalleryRenameResponse> {
    return this.dispatch<GalleryRenameResponse>("set_images", {
      gallery_id: galleryId,
      image_ids: imageIds.join(",")
    });
  }

  public createGalleryPage(galleryId: number): Promise<GalleryCreatePageResponse> {
    return this.dispatch<GalleryCreatePageResponse>("create_page", {
      id: galleryId
    });
  }
}
