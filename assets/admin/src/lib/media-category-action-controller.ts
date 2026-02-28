import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/lib/abstract-action-controller";

type MediaCategoryDefinitions = {
  suggest: AjaxActionDefinition;
  list: AjaxActionDefinition;
  assign: AjaxActionDefinition;
};

export type MediaCategoryItem = {
  id: number;
  name: string;
  slug: string;
  count: number;
};

export type MediaCategorySuggestResponse = {
  action: string;
  query: string;
  items: MediaCategoryItem[];
  count: number;
};

export type MediaCategoryListResponse = {
  action: string;
  attachment_id: number;
  items: MediaCategoryItem[];
  count: number;
};

export type MediaCategoryAssignResponse = {
  action: string;
  attachment_id: number;
  items: MediaCategoryItem[];
  count: number;
};

export class MediaCategoryActionController extends AbstractActionController<MediaCategoryDefinitions> {
  constructor(config: ActionControllerConfig, definitions: MediaCategoryDefinitions) {
    super(config, definitions);
  }

  public suggestCategories(query = "", limit = 10): Promise<MediaCategorySuggestResponse> {
    return this.dispatch<MediaCategorySuggestResponse>("suggest", { query, limit });
  }

  public listForAttachment(attachmentId: number): Promise<MediaCategoryListResponse> {
    return this.dispatch<MediaCategoryListResponse>("list", { attachment_id: attachmentId });
  }

  public assignToAttachment(attachmentId: number, categoriesCsv: string): Promise<MediaCategoryAssignResponse> {
    return this.dispatch<MediaCategoryAssignResponse>("assign", {
      attachment_id: attachmentId,
      categories: categoriesCsv
    });
  }
}
