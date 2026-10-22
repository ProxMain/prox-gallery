import { useCallback, useEffect, useMemo, useState } from "react";
import { GalleriesHeader } from "./components/galleries-header";
import { GalleriesLibraryCard } from "./components/galleries-library-card";
import { GalleryEditor } from "./components/gallery-editor";
import { GalleryImagesDialog } from "./components/gallery-images-dialog";
import { useGalleriesState } from "./use-galleries-state";
import {
  useGalleryActionController,
  useMediaManagerActionController,
} from "@/lib/action-controller-hooks";

const DEFAULT_TEMPLATES = [
  { slug: "basic-grid", label: "Basic Grid", available: true },
  { slug: "masonry", label: "Masonry", available: true },
];

export function GalleriesSection({ config, isActive }) {
  const controller = useGalleryActionController(config);
  const media = useMediaManagerActionController(config);
  const {
    galleries,
    isLoading,
    error,
    reloadGalleries,
    createGallery,
    renameGallery,
    deleteGallery,
    createGalleryPage,
  } = useGalleriesState(controller);
  const [editor, setEditor] = useState(null);
  const [imageGallery, setImageGallery] = useState(null);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState(null);
  const templates = useMemo(() => {
    const supplied = config.action_controllers?.galleries?.templates;
    return (supplied?.length ? supplied : DEFAULT_TEMPLATES).filter(
      (item) => item.available,
    );
  }, [config]);

  useEffect(() => {
    if (isActive) void reloadGalleries();
  }, [isActive, reloadGalleries]);

  const loadImages = useCallback(async () => {
    if (!media) throw new Error("Media library configuration is missing.");
    return (await media.listTrackedImages()).items;
  }, [media]);
  const saveImages = async (id, ids) => {
    if (!controller) throw new Error("Gallery configuration is missing.");
    await controller.setGalleryImages(id, ids);
    await reloadGalleries();
  };
  const runAction = async (action, message) => {
    setBusy(true);
    setNotice(null);
    try {
      await action();
      setNotice({ message, error: false });
    } catch (failure) {
      setNotice({
        message: failure.message || "The action failed.",
        error: true,
      });
    } finally {
      setBusy(false);
    }
  };
  const remove = (gallery) => {
    if (
      !window.confirm(
        `Delete “${gallery.name}”? Images stay in the WordPress media library. Published pages are kept; remove their shortcode separately.`,
      )
    )
      return;
    void runAction(
      () => deleteGallery({ id: gallery.id }),
      "Gallery deleted. Your original images are safe.",
    );
  };
  const publish = (gallery) => {
    if (
      !window.confirm(
        `Publish a public WordPress page for “${gallery.name}” and add a navigation link?`,
      )
    )
      return;
    void runAction(async () => {
      const result = await createGalleryPage({ id: gallery.id });
      setNotice({ message: "Page ready.", url: result.page_url, error: false });
    }, "Gallery page is ready. Check Pages in WordPress.");
  };

  return (
    <section className={isActive ? "space-y-7" : "hidden"}>
      <GalleriesHeader onCreate={() => setEditor({ gallery: null })} />
      <div className="pg-collection-summary">
        <span>
          <strong>{galleries.length}</strong> collections
        </span>
        <span>
          <strong>
            {galleries.reduce(
              (sum, gallery) => sum + (gallery.image_count ?? 0),
              0,
            )}
          </strong>{" "}
          image assignments
        </span>
        <span className="pg-summary-hint">
          Curated by you. Ready to be discovered.
        </span>
      </div>
      {notice ? (
        <p
          className={`pg-notice ${notice.error ? "pg-error" : ""}`}
          role={notice.error ? "alert" : "status"}
        >
          {notice.message}
        </p>
      ) : null}
      <GalleriesLibraryCard
        galleries={galleries}
        templates={templates}
        isLoading={isLoading}
        error={error}
        onReloadGalleries={reloadGalleries}
        onEdit={(gallery) => setEditor({ gallery })}
        onImages={setImageGallery}
        onDelete={remove}
        onPublish={publish}
        disabled={busy}
        onCreate={() => setEditor({ gallery: null })}
      />
      {editor ? (
        <GalleryEditor
          gallery={editor.gallery}
          templates={templates}
          onClose={() => setEditor(null)}
          onSave={editor.gallery ? renameGallery : createGallery}
        />
      ) : null}
      {imageGallery ? (
        <GalleryImagesDialog
          gallery={imageGallery}
          onLoad={loadImages}
          onSave={saveImages}
          onClose={() => setImageGallery(null)}
        />
      ) : null}
    </section>
  );
}
