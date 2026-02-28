import { MediaItemActions } from "@/features/media-manager/media-item-actions";

export function MediaRowsList({ trackedImages, onDeleteLinkClick, onEditClick, onViewClick }) {
  return (
    <div className="space-y-2">
      {trackedImages.map((image) => (
        <article
          key={image.id}
          className="flex items-center justify-between gap-3 rounded-lg border bg-white px-3 py-2"
        >
          <div className="flex items-center gap-3">
            <img
              src={image.url}
              alt={image.title || `Tracked image ${image.id}`}
              className="h-10 w-10 rounded object-cover"
              loading="lazy"
            />
            <div>
              <p className="text-sm font-medium text-slate-900">{image.title || `#${image.id}`}</p>
              <p className="text-xs text-slate-600">{image.uploaded_by || "Unknown uploader"}</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <span className="text-xs text-slate-600">{image.mime_type}</span>
            <div className="flex items-center gap-1">
              <MediaItemActions
                image={image}
                onDeleteClick={onDeleteLinkClick}
                onEditClick={onEditClick}
                onViewClick={onViewClick}
                buttonClassName="rounded p-1.5 text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              />
            </div>
          </div>
        </article>
      ))}
    </div>
  );
}
