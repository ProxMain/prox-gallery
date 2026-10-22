import {
  ArrowUpRight,
  Image as ImageIcon,
  Images,
  Pencil,
  Trash2,
} from "lucide-react";
import { MediaImage } from "@/components/ui/media-image";

export function GalleryCard({
  gallery,
  onEdit,
  onImages,
  onPublish,
  onDelete,
  disabled,
}) {
  const covers = gallery.cover_images ?? [];
  return (
    <article className="pg-gallery-card">
      <button
        type="button"
        className="pg-gallery-cover"
        onClick={() => onImages(gallery)}
        aria-label={`Manage images in ${gallery.name}`}
        disabled={disabled}
      >
        {covers.length ? (
          <MediaImage src={covers[0]} alt="" loading="lazy" />
        ) : (
          <span className="pg-cover-empty">
            <ImageIcon size={38} strokeWidth={1} />
            <span>Your next collection starts here</span>
          </span>
        )}
        <span className="pg-cover-label">
          {gallery.template === "masonry" ? "Masonry" : "Grid"}
        </span>
        <span className="pg-cover-count">
          <Images size={13} />
          {gallery.image_count ?? 0}
        </span>
      </button>
      <div className="pg-gallery-info">
        <div className="pg-gallery-name">
          <h3>{gallery.name}</h3>
          <span>#{gallery.id}</span>
        </div>
        <p>
          {gallery.description || "A new perspective, waiting to be shared."}
        </p>
        <div className="pg-gallery-actions">
          <button
            type="button"
            onClick={() => onImages(gallery)}
            disabled={disabled}
          >
            Manage images <ArrowUpRight size={14} />
          </button>
          <button
            type="button"
            className="pg-icon-button"
            onClick={() => onEdit(gallery)}
            disabled={disabled}
            aria-label={`Edit ${gallery.name}`}
            title="Edit gallery"
          >
            <Pencil size={15} />
          </button>
          <button
            type="button"
            className="pg-icon-button"
            onClick={() => onPublish(gallery)}
            disabled={disabled}
            aria-label={`Publish page for ${gallery.name}`}
            title="Publish a page and menu link"
          >
            <ArrowUpRight size={16} />
          </button>
          <button
            type="button"
            className="pg-icon-button pg-danger"
            onClick={() => onDelete(gallery)}
            disabled={disabled}
            aria-label={`Delete ${gallery.name}`}
            title="Delete gallery"
          >
            <Trash2 size={15} />
          </button>
        </div>
      </div>
    </article>
  );
}
