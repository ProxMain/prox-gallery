import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Dialog } from "@/components/ui/dialog";
import { ImageAiPanel } from "./image-ai-panel";
import { useMediaOrganisation } from "../hooks/use-media-organisation";

export function MediaImageModal(props) {
  const { image, mode, onClose, onSave, openAiController, onApplied } = props;
  const [tab, setTab] = useState("details");
  const [form, setForm] = useState({
    title: image.title || "",
    alt_text: image.alt_text || "",
    caption: image.caption || "",
    description: image.description || "",
  });
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState(null);
  const organisation = useMediaOrganisation(props);
  const save = async (event) => {
    event.preventDefault();
    if (busy) return;
    setBusy(true);
    setNotice(null);
    try {
      await onSave({ attachment_id: image.id, ...form });
      setNotice({ message: "Metadata saved.", error: false });
    } catch (error) {
      setNotice({
        message: error.message || "Could not save metadata.",
        error: true,
      });
    } finally {
      setBusy(false);
    }
  };
  const applied = async (response) => {
    setForm((current) => ({
      ...current,
      title: response.short_title,
      description: response.story,
    }));
    await onApplied(response);
  };
  return (
    <Dialog
      title={image.title || `Image #${image.id}`}
      onClose={onClose}
      busy={busy || organisation.busy}
      wide
    >
      <div className="pg-image-editor">
        <div className="pg-image-stage">
          <img src={image.url} alt={image.alt_text || image.title || ""} />
          <p>
            #{image.id} · {image.width || "–"} × {image.height || "–"} ·{" "}
            {image.mime_type}
          </p>
        </div>
        <div className="pg-image-details">
          <nav className="pg-settings-tabs" aria-label="Image sections">
            {[
              ["details", "Details"],
              ["organise", "Organise"],
              ["ai", "AI assistant"],
            ].map(([key, label]) => (
              <button
                type="button"
                key={key}
                aria-current={tab === key ? "page" : undefined}
                onClick={() => setTab(key)}
              >
                {label}
              </button>
            ))}
          </nav>
          {tab === "details" ? (
            <form onSubmit={save} className="space-y-5">
              {[
                ["title", "Title"],
                ["alt_text", "Alternative text"],
                ["caption", "Caption"],
                ["description", "Description"],
              ].map(([key, label]) => (
                <label className="pg-field" key={key}>
                  {label}
                  {key === "description" ? (
                    <textarea
                      rows={5}
                      value={form[key]}
                      onChange={(event) =>
                        setForm({ ...form, [key]: event.target.value })
                      }
                      disabled={busy}
                    />
                  ) : (
                    <input
                      value={form[key]}
                      onChange={(event) =>
                        setForm({ ...form, [key]: event.target.value })
                      }
                      disabled={busy}
                    />
                  )}
                  {key === "alt_text" ? (
                    <small className="pg-muted">
                      Describe the image for people who cannot see it.
                    </small>
                  ) : null}
                </label>
              ))}
              {notice ? (
                <p
                  className={`pg-notice ${notice.error ? "pg-error" : ""}`}
                  role={notice.error ? "alert" : "status"}
                >
                  {notice.message}
                </p>
              ) : null}
              <Button type="submit" disabled={busy}>
                {busy ? "Saving…" : "Save metadata"}
              </Button>
            </form>
          ) : tab === "organise" ? (
            <div className="space-y-6">
              {organisation.notice ? (
                <p
                  className={`pg-notice ${organisation.notice.error ? "pg-error" : ""}`}
                  role={organisation.notice.error ? "alert" : "status"}
                >
                  {organisation.notice.message}
                </p>
              ) : null}
              {!organisation.loaded && !organisation.notice ? (
                <p role="status">Loading organisation…</p>
              ) : null}
              <label className="pg-field">
                Categories
                <input
                  value={organisation.categories}
                  disabled={!organisation.loaded || organisation.busy}
                  onChange={(event) =>
                    organisation.setCategories(event.target.value)
                  }
                  placeholder="Nature, Travel, Portraits"
                />
                <small className="pg-muted">Separate names with commas.</small>
              </label>
              <Button
                variant="outline"
                onClick={organisation.saveCategories}
                disabled={!organisation.loaded || organisation.busy}
              >
                Save categories
              </Button>
              <fieldset
                className="pg-checks"
                disabled={!organisation.loaded || organisation.busy}
              >
                <legend className="mb-3 text-sm font-medium">
                  Included in galleries
                </legend>
                {organisation.galleries.map((gallery) => (
                  <label key={gallery.id}>
                    <input
                      type="checkbox"
                      checked={organisation.selected.includes(gallery.id)}
                      onChange={() => organisation.toggleGallery(gallery.id)}
                    />
                    {gallery.name}
                  </label>
                ))}
                {!organisation.galleries.length ? (
                  <p className="pg-muted">
                    Create a gallery to include this image.
                  </p>
                ) : null}
              </fieldset>
              <Button
                variant="outline"
                onClick={organisation.saveGalleries}
                disabled={!organisation.loaded || organisation.busy}
              >
                Save membership
              </Button>
            </div>
          ) : (
            <ImageAiPanel
              image={image}
              controller={openAiController}
              onApplied={applied}
            />
          )}
        </div>
      </div>
    </Dialog>
  );
}
