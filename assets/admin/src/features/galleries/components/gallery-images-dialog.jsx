import { useEffect, useState } from "react";
import { ArrowDown, ArrowUp, Check } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Dialog } from "@/components/ui/dialog";

export function GalleryImagesDialog({ gallery, onLoad, onSave, onClose }) {
  const [images, setImages] = useState([]);
  const [selected, setSelected] = useState(gallery.image_ids ?? []);
  const [loading, setLoading] = useState(true);
  const [hasLoaded, setHasLoaded] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [query, setQuery] = useState("");
  useEffect(() => {
    let active = true;
    onLoad()
      .then((items) => {
        if (active) {
          setImages(items);
          setHasLoaded(true);
        }
      })
      .catch((failure) => {
        if (active) setError(failure.message || "Could not load images.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [onLoad]);
  const toggle = (id) =>
    setSelected((current) =>
      current.includes(id)
        ? current.filter((item) => item !== id)
        : [...current, id],
    );
  const move = (index, delta) =>
    setSelected((current) => {
      const next = [...current];
      if (index + delta < 0 || index + delta >= next.length) return current;
      [next[index], next[index + delta]] = [next[index + delta], next[index]];
      return next;
    });
  const save = async () => {
    if (busy) return;
    setBusy(true);
    setError("");
    try {
      await onSave(gallery.id, selected);
      onClose();
    } catch (failure) {
      setError(failure.message || "Could not save images.");
    } finally {
      setBusy(false);
    }
  };

  return (
    <Dialog
      title={`Images · ${gallery.name}`}
      onClose={onClose}
      busy={busy}
      wide
    >
      <div className="pg-form-body">
        <div className="pg-heading-row">
          <p className="pg-muted">
            Select images, then arrange their order. Your source files stay in
            WordPress.
          </p>
          <span>{selected.length} selected</span>
        </div>
        <label className="pg-field">
          Find an image
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search your library…"
          />
        </label>
        {loading ? (
          <p role="status">Loading your library…</p>
        ) : (
          <div className="pg-image-picker">
            {images
              .filter((image) =>
                image.title.toLowerCase().includes(query.toLowerCase()),
              )
              .map((image) => (
                <button
                  type="button"
                  key={image.id}
                  className={`pg-picker-item ${selected.includes(image.id) ? "is-selected" : ""}`}
                  onClick={() => toggle(image.id)}
                  aria-pressed={selected.includes(image.id)}
                  disabled={busy}
                >
                  <img
                    src={image.thumbnail_url || image.url}
                    alt=""
                    loading="lazy"
                  />
                  <span>{image.title || `Image #${image.id}`}</span>
                  {selected.includes(image.id) ? (
                    <Check size={18} className="pg-picker-check" />
                  ) : null}
                </button>
              ))}
            {images.length === 0 ? (
              <p>Add images to your media library first.</p>
            ) : null}
          </div>
        )}
        {selected.length ? (
          <details className="pg-advanced">
            <summary>Arrange {selected.length} images</summary>
            <ol className="pg-order-list">
              {selected.map((id, index) => (
                <li key={id}>
                  <span>
                    {index + 1}.{" "}
                    {images.find((image) => image.id === id)?.title ||
                      `Image #${id}`}
                  </span>
                  <button
                    type="button"
                    className="pg-icon-button"
                    onClick={() => move(index, -1)}
                    disabled={busy || index === 0}
                    aria-label={`Move image ${id} up`}
                  >
                    <ArrowUp size={16} />
                  </button>
                  <button
                    type="button"
                    className="pg-icon-button"
                    onClick={() => move(index, 1)}
                    disabled={busy || index === selected.length - 1}
                    aria-label={`Move image ${id} down`}
                  >
                    <ArrowDown size={16} />
                  </button>
                </li>
              ))}
            </ol>
          </details>
        ) : null}
        {error ? (
          <p className="pg-notice pg-error" role="alert">
            {error}
          </p>
        ) : null}
      </div>
      <footer className="pg-dialog-footer">
        <Button variant="outline" onClick={onClose} disabled={busy}>
          Cancel
        </Button>
        <Button onClick={save} disabled={busy || loading || !hasLoaded}>
          {busy ? "Saving…" : "Save selection"}
        </Button>
      </footer>
    </Dialog>
  );
}
