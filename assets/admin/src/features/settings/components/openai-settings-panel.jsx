import { Plus, X } from "lucide-react";
import { Button } from "@/components/ui/button";

export function OpenAiSettingsPanel({ value, update, disabled }) {
  const templates = value.prompt_templates;
  const editTemplate = (index, field, next) =>
    update(
      "prompt_templates",
      templates.map((template, position) =>
        position === index ? { ...template, [field]: next } : template,
      ),
    );
  return (
    <fieldset disabled={disabled} className="pg-settings-card pg-settings-ai">
      <legend>Image stories, with a little help</legend>
      <p className="pg-muted">
        Optional. An explicit Generate action sends the selected image and
        prompt to OpenAI. Review the result before applying it. API charges may
        apply.
      </p>
      <div className="pg-form-grid">
        <label className="pg-field">
          API key
          <input
            type="password"
            autoComplete="new-password"
            value={value.api_key}
            onChange={(event) => update("api_key", event.target.value)}
            placeholder={
              value.api_key_configured
                ? "Key saved · leave blank to keep it"
                : "Enter an API key"
            }
          />
          <small className="pg-muted">
            Stored encrypted. The saved key is never returned to your browser.
          </small>
        </label>
        <label className="pg-field">
          Model
          <input
            value={value.model}
            onChange={(event) => update("model", event.target.value)}
          />
        </label>
      </div>
      <div className="pg-form-grid">
        <label className="pg-field">
          Daily attempts per user
          <input type="number" min="0" max="10000" step="1"
            value={value.user_daily_limit}
            onChange={(event) => update("user_daily_limit", event.target.value)} />
        </label>
        <label className="pg-field">
          Daily attempts for the site
          <input type="number" min="0" max="10000" step="1"
            value={value.site_daily_limit}
            onChange={(event) => update("site_daily_limit", event.target.value)} />
        </label>
      </div>
      <p className="pg-muted">
        Resets at 00:00 UTC. Limits include administrators and failed provider
        requests. Set either limit to 0 to pause generation. These are request
        limits; set a spending budget with your API provider as well.
      </p>
      <label className="pg-field">
        Languages, separated by commas
        <input
          value={value.languages.join(", ")}
          onChange={(event) =>
            update(
              "languages",
              event.target.value.split(",").map((item) => item.trim()),
            )
          }
        />
      </label>
      <h3>Prompt templates</h3>
      {templates.map((template, index) => (
        <div className="pg-prompt-template" key={template.key}>
          <div className="pg-heading-row">
            <label className="pg-field">
              Template name
              <input
                value={template.label}
                onChange={(event) =>
                  editTemplate(index, "label", event.target.value)
                }
              />
            </label>
            {!template.built_in ? (
              <button
                type="button"
                className="pg-icon-button pg-danger"
                onClick={() =>
                  update(
                    "prompt_templates",
                    templates.filter((_, position) => position !== index),
                  )
                }
                aria-label={`Remove ${template.label}`}
              >
                <X size={17} />
              </button>
            ) : (
              <span className="pg-muted">Built in</span>
            )}
          </div>
          <label className="pg-field">
            Instructions
            <textarea
              rows={3}
              value={template.prompt}
              onChange={(event) =>
                editTemplate(index, "prompt", event.target.value)
              }
            />
          </label>
        </div>
      ))}
      <Button
        type="button"
        variant="outline"
        onClick={() =>
          update("prompt_templates", [
            ...templates,
            {
              key: `custom_${Date.now()}`,
              label: "New template",
              prompt: "Describe the visible details in this image.",
              built_in: false,
            },
          ])
        }
      >
        <Plus size={15} />
        Add prompt template
      </Button>
    </fieldset>
  );
}
