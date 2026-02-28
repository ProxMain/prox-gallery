import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/lib/abstract-action-controller";

type TrackingDefinitions = {
  get: AjaxActionDefinition;
};

export type TrackingSummaryResponse = {
  action: string;
  summary: {
    totals: {
      gallery_views: number;
      image_views: number;
    };
    countries: Record<string, number>;
    galleries: Array<{
      gallery_id: number;
      name: string;
      total: number;
      countries: Record<string, number>;
    }>;
    images: Array<{
      image_id: number;
      title: string;
      total: number;
      countries: Record<string, number>;
    }>;
    updated_at: string;
  };
};

export class TrackingActionController extends AbstractActionController<TrackingDefinitions> {
  constructor(config: ActionControllerConfig, definitions: TrackingDefinitions) {
    super(config, definitions);
  }

  public getSummary(): Promise<TrackingSummaryResponse> {
    return this.dispatch<TrackingSummaryResponse>("get");
  }
}

