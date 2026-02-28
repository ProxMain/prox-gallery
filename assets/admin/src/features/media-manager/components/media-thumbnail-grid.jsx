import { MediaItemActions } from "@/features/media-manager/media-item-actions";

export function MediaThumbnailGrid({ trackedImages, onDeleteLinkClick, onViewClick, onEditClick }) {
  return (
    <div className="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-5">
      {trackedImages.map((image) => (
        <article key={image.id} className="group relative overflow-hidden rounded-lg border bg-white">
          <img
            src={image.url}
            alt={image.title || `Tracked image ${image.id}`}
            className="h-32 w-full object-cover"
            loading="lazy"
          />
          <div className="pointer-events-none absolute inset-0 bg-slate-900/55 opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100" />
          <div className="absolute right-2 top-2 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100">
            <MediaItemActions
              image={image}
              onDeleteClick={onDeleteLinkClick}
              onViewClick={onViewClick}
              onEditClick={onEditClick}
              buttonClassName="rounded bg-white/95 p-1.5 text-slate-800 shadow-sm transition hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
            />
          </div>
          <div className="p-3">
            <p className="truncate text-sm font-medium text-slate-900">{image.title || `#${image.id}`}</p>
            <p className="truncate text-xs text-slate-600">{image.uploaded_by || "Unknown uploader"}</p>
          </div>
        </article>
      ))}
    </div>
  );
}
