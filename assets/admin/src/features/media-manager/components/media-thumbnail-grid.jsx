import { MediaItemActions } from "@/features/media-manager/media-item-actions";
import { MediaImage } from "@/components/ui/media-image";

export function MediaThumbnailGrid({
  trackedImages,
  onDeleteLinkClick,
  onViewClick,
  onEditClick,
}) {
  return (
    <div className="pg-media-grid">
      {trackedImages.map((image) => (
        <article key={image.id} className="pg-media-card">
          <button
            type="button"
            className="pg-media-preview"
            onClick={() => onViewClick(image)}
            aria-label={`Preview ${image.title || `image ${image.id}`}`}
          >
            <MediaImage
              src={image.thumbnail_url || image.url}
              alt={image.alt_text || ""}
              loading="lazy"
              decoding="async"
            />
            <span>
              {(image.mime_type || "image").split("/").pop().toUpperCase()}
            </span>
          </button>
          <div className="pg-media-info">
            <h3 title={image.title}>
              {image.title || `Untitled #${image.id}`}
            </h3>
            <p>
              {image.width || "–"} × {image.height || "–"} <span>·</span>{" "}
              {image.file_size
                ? `${Math.round(image.file_size / 1024)} KB`
                : "Size unknown"}
            </p>
            <div className="pg-media-footer">
              <span>{image.categories?.[0]?.name || "Uncategorised"}</span>
              <div className="flex gap-1">
                <MediaItemActions
                  image={image}
                  onDeleteClick={onDeleteLinkClick}
                  onViewClick={onViewClick}
                  onEditClick={onEditClick}
                />
              </div>
            </div>
          </div>
        </article>
      ))}
    </div>
  );
}
