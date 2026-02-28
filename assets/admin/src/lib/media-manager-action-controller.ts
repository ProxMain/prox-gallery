import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/lib/abstract-action-controller";

type MediaManagerDefinitions = {
  list: AjaxActionDefinition;
  update: AjaxActionDefinition;
};

export type MediaManagerTrackedImage = {
  id: number;
  title: string;
  mime_type: string;
  uploaded_at: string;
  uploaded_by: string;
  url: string;
  view_url: string;
  edit_url: string;
  delete_url: string;
  alt_text: string;
  caption: string;
  description: string;
  categories: Array<{
    id: number;
    name: string;
    slug: string;
  }>;
  width: number | null;
  height: number | null;
  file_size: number | null;
};

export type MediaManagerListResponse = {
  action: string;
  items: MediaManagerTrackedImage[];
  count: number;
};

export type MediaManagerUpdatePayload = {
  attachment_id: number;
  title: string;
  alt_text: string;
  caption: string;
  description: string;
};

export type MediaManagerUpdateResponse = {
  action: string;
  attachment_id: number;
  item: {
    id: number;
    title: string;
    alt_text: string;
    caption: string;
    description: string;
  };
};

export class MediaManagerActionController extends AbstractActionController<MediaManagerDefinitions> {
  constructor(config: ActionControllerConfig, definitions: MediaManagerDefinitions) {
    super(config, definitions);
  }

  public listTrackedImages(): Promise<MediaManagerListResponse> {
    return this.dispatch<MediaManagerListResponse>("list");
  }

  public updateTrackedImageMetadata(payload: MediaManagerUpdatePayload): Promise<MediaManagerUpdateResponse> {
    return this.dispatch<MediaManagerUpdateResponse>("update", payload);
  }
}
