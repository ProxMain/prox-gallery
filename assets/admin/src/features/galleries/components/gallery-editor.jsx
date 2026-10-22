import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Dialog } from "@/components/ui/dialog";

const INHERITED = [
  ["lightbox_override", "Lightbox"],
  ["hover_zoom_override", "Hover zoom"],
  ["full_width_override", "Full width"],
];

export function GalleryEditor({ gallery, templates, onSave, onClose }) {
  const [value, setValue] = useState(() => ({
    name: "",
    description: "",
    template: "basic-grid",
    show_title: true,
    show_description: true,
    grid_columns_override: null,
    lightbox_override: null,
    hover_zoom_override: null,
    full_width_override: null,
    transition_override: null,
    ...gallery,
  }));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const update = (field, next) =>
    setValue((current) => ({ ...current, [field]: next }));
  const submit = async (event) => {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setError("");
    try {
      await onSave({ ...value, name: value.name.trim() });
      onClose();
    } catch (failure) {
      setError(
        failure instanceof Error ? failure.message : "Could not save gallery.",
      );
    } finally {
      setBusy(false);
    }
  };

  return (
    <Dialog
      title={gallery ? "Edit gallery" : "Create a gallery"}
      onClose={onClose}
      busy={busy}
    >
      <form onSubmit={submit}>
        <fieldset disabled={busy} className="pg-form-body">
          <p className="pg-muted">
            Give your collection a name and choose how it tells its story.
          </p>
          <label className="pg-field">
            Gallery name
            <input
              autoFocus
              required
              maxLength={200}
              value={value.name}
              onChange={(event) => update("name", event.target.value)}
              placeholder="e.g. Quiet moments"
            />
          </label>
          <label className="pg-field">
            Description
            <textarea
              rows={3}
              value={value.description}
              onChange={(event) => update("description", event.target.value)}
              placeholder="What brings these images together?"
            />
          </label>
          <label className="pg-field">
            Layout
            <select
              value={value.template}
              onChange={(event) => update("template", event.target.value)}
            >
              {templates.map((template) => (
                <option key={template.slug} value={template.slug}>
                  {template.label}
                </option>
              ))}
            </select>
          </label>
          <details className="pg-advanced">
            <summary>Display options</summary>
            <div className="pg-form-grid">
              <label className="pg-field">
                Columns
                <select
                  value={value.grid_columns_override ?? ""}
                  onChange={(event) =>
                    update(
                      "grid_columns_override",
                      event.target.value === ""
                        ? null
                        : Number(event.target.value),
                    )
                  }
                >
                  <option value="">Use default</option>
                  {[2, 3, 4, 5, 6].map((count) => (
                    <option key={count} value={count}>
                      {count} columns
                    </option>
                  ))}
                </select>
              </label>
              {INHERITED.map(([key, label]) => (
                <label className="pg-field" key={key}>
                  {label}
                  <select
                    value={value[key] === null ? "" : String(value[key])}
                    onChange={(event) =>
                      update(
                        key,
                        event.target.value === ""
                          ? null
                          : event.target.value === "true",
                      )
                    }
                  >
                    <option value="">Use default</option>
                    <option value="true">Enabled</option>
                    <option value="false">Disabled</option>
                  </select>
                </label>
              ))}
              <label className="pg-field">
                Transition
                <select
                  value={value.transition_override ?? ""}
                  onChange={(event) =>
                    update("transition_override", event.target.value || null)
                  }
                >
                  <option value="">Use default</option>
                  {["none", "fade", "slide", "explode", "implode"].map(
                    (mode) => (
                      <option key={mode}>{mode}</option>
                    ),
                  )}
                </select>
              </label>
              <div className="pg-checks">
                {[
                  ["show_title", "Show title"],
                  ["show_description", "Show description"],
                ].map(([key, label]) => (
                  <label key={key}>
                    <input
                      type="checkbox"
                      checked={value[key]}
                      onChange={(event) => update(key, event.target.checked)}
                    />
                    {label}
                  </label>
                ))}
              </div>
            </div>
          </details>
          {error ? (
            <p className="pg-notice pg-error" role="alert">
              {error}
            </p>
          ) : null}
        </fieldset>
        <footer className="pg-dialog-footer">
          <Button
            type="button"
            variant="outline"
            disabled={busy}
            onClick={onClose}
          >
            Cancel
          </Button>
          <Button type="submit" disabled={busy || !value.name.trim()}>
            {busy ? "Saving…" : gallery ? "Save changes" : "Create gallery"}
          </Button>
        </footer>
      </form>
    </Dialog>
  );
}
