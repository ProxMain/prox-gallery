import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/lib/abstract-action-controller";

type TemplateSettingsDefinitions = {
  get: AjaxActionDefinition;
  update: AjaxActionDefinition;
};

export type TemplateSettings = {
  basic_grid_columns: number;
  basic_grid_lightbox: boolean;
  basic_grid_hover_zoom: boolean;
  basic_grid_full_width: boolean;
  basic_grid_transition: "none" | "slide" | "fade" | "explode" | "implode";
  masonry_columns: number;
  masonry_lightbox: boolean;
  masonry_hover_zoom: boolean;
  masonry_full_width: boolean;
  masonry_transition: "none" | "slide" | "fade" | "explode" | "implode";
};

type TemplateSettingsResponse = {
  action: string;
  settings: TemplateSettings;
};

export class TemplateSettingsActionController extends AbstractActionController<TemplateSettingsDefinitions> {
  constructor(config: ActionControllerConfig, definitions: TemplateSettingsDefinitions) {
    super(config, definitions);
  }

  public getSettings(): Promise<TemplateSettingsResponse> {
    return this.dispatch<TemplateSettingsResponse>("get");
  }

  public updateSettings(settings: TemplateSettings): Promise<TemplateSettingsResponse> {
    return this.dispatch<TemplateSettingsResponse>("update", settings);
  }
}
