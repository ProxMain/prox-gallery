import { useState } from "react";
import { Bot, LayoutGrid, Save } from "lucide-react";
import { SectionHeader } from "@/core/section-header";
import { Button } from "@/components/ui/button";
import {
  useOpenAiActionController,
  useTemplateSettingsActionController,
} from "@/lib/action-controller-hooks";
import { useSettingsEditor } from "./use-settings-editor";
import { TemplateSettingsPanel } from "./components/template-settings-panel";
import { OpenAiSettingsPanel } from "./components/openai-settings-panel";

const TEMPLATE_DEFAULTS = {
  basic_grid_columns: 4,
  basic_grid_lightbox: true,
  basic_grid_hover_zoom: true,
  basic_grid_full_width: false,
  basic_grid_transition: "none",
  masonry_columns: 4,
  masonry_lightbox: true,
  masonry_hover_zoom: true,
  masonry_full_width: false,
  masonry_transition: "none",
};
const AI_DEFAULTS = {
  api_key: "",
  api_key_configured: false,
  model: "gpt-4.1-mini",
  languages: ["English", "Dutch"],
  prompt_templates: [],
};

export function SettingsSection({ config, isActive }) {
  const [section, setSection] = useState("templates");
  const templateController = useTemplateSettingsActionController(config);
  const aiController = useOpenAiActionController(config);
  const templates = useSettingsEditor(
    templateController,
    isActive && section === "templates",
    TEMPLATE_DEFAULTS,
  );
  const ai = useSettingsEditor(
    aiController,
    isActive && section === "openai",
    AI_DEFAULTS,
  );
  const editor = section === "templates" ? templates : ai;
  return (
    <section className={isActive ? "space-y-6" : "hidden"}>
      <SectionHeader
        variant="page"
        title="Make it feel like you."
        description="Thoughtful defaults for every collection. Fine-tune individual galleries whenever you need."
        actions={
          <Button
            onClick={editor.save}
            disabled={editor.busy || !editor.loaded}
          >
            <Save size={15} />
            {editor.saving ? "Saving…" : "Save changes"}
          </Button>
        }
      />
      <nav className="pg-settings-tabs" aria-label="Settings sections">
        {[
          ["templates", "Gallery defaults", LayoutGrid],
          ["openai", "OpenAI", Bot],
        ].map(([key, label, Icon]) => (
          <button
            type="button"
            key={key}
            onClick={() => setSection(key)}
            aria-current={section === key ? "page" : undefined}
          >
            <Icon size={16} />
            {label}
          </button>
        ))}
      </nav>
      {editor.notice ? (
        <p
          className={`pg-notice ${editor.notice.error ? "pg-error" : ""}`}
          role={editor.notice.error ? "alert" : "status"}
        >
          {editor.notice.message}
        </p>
      ) : null}
      {editor.loading ? (
        <p className="pg-muted" role="status">
          Loading settings…
        </p>
      ) : null}
      {section === "templates" ? (
        <TemplateSettingsPanel
          value={templates.value}
          update={templates.update}
          disabled={templates.busy || !templates.loaded}
        />
      ) : (
        <OpenAiSettingsPanel
          value={ai.value}
          update={ai.update}
          disabled={ai.busy || !ai.loaded}
        />
      )}
    </section>
  );
}
