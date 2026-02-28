import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/lib/abstract-action-controller";

type MediaManagerDefinitions = {
  list: AjaxActionDefinition;
};

export type MediaManagerTrackedImage = {
  id: number;
  title: string;
  mime_type: string;
  uploaded_at: string;
  uploaded_by: string;
  url: string;
  width: number | null;
  height: number | null;
  file_size: number | null;
};

export type MediaManagerListResponse = {
  action: string;
  items: MediaManagerTrackedImage[];
  count: number;
};

export class MediaManagerActionController extends AbstractActionController<MediaManagerDefinitions> {
  constructor(config: ActionControllerConfig, definitions: MediaManagerDefinitions) {
    super(config, definitions);
  }

  public listTrackedImages(): Promise<MediaManagerListResponse> {
    return this.dispatch<MediaManagerListResponse>("list");
  }
}
