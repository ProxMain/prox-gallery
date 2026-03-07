import {
  AbstractActionController,
  type ActionControllerConfig,
  type AjaxActionDefinition
} from "@/modules/core/controllers/abstract-action-controller";

type OpenAiDefinitions = {
  settings_get: AjaxActionDefinition;
  settings_update: AjaxActionDefinition;
  config_get: AjaxActionDefinition;
  generate: AjaxActionDefinition;
  apply: AjaxActionDefinition;
};

export type OpenAiPromptTemplate = {
  key: string;
  label: string;
  prompt: string;
  built_in: boolean;
};

export type OpenAiSettings = {
  api_key: string;
  model: string;
  languages: string[];
  prompt_templates: OpenAiPromptTemplate[];
};

export type OpenAiGenerationConfig = {
  model: string;
  languages: string[];
  prompt_templates: OpenAiPromptTemplate[];
};

export class OpenAiActionController extends AbstractActionController<OpenAiDefinitions> {
  constructor(config: ActionControllerConfig, definitions: OpenAiDefinitions) {
    super(config, definitions);
  }

  public getSettings(): Promise<{ action: string; settings: OpenAiSettings }> {
    return this.dispatch("settings_get");
  }

  public updateSettings(settings: OpenAiSettings): Promise<{ action: string; settings: OpenAiSettings }> {
    return this.dispatch("settings_update", {
      api_key: settings.api_key,
      model: settings.model,
      languages_csv: settings.languages.join(","),
      prompt_templates_json: JSON.stringify(settings.prompt_templates)
    });
  }

  public getGenerationConfig(): Promise<{ action: string; config: OpenAiGenerationConfig }> {
    return this.dispatch("config_get");
  }

  public generateStory(payload: {
    attachment_id: number;
    template_key: string;
    language: string;
    prompt_override: string;
  }): Promise<{ action: string; attachment_id: number; story: string; language: string; template_key: string; prompt: string; model: string }> {
    return this.dispatch("generate", payload);
  }

  public applyStory(payload: {
    attachment_id: number;
    story: string;
  }): Promise<{ action: string; attachment_id: number; story: string }> {
    return this.dispatch("apply", payload);
  }
}
