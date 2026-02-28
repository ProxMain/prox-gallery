import { MediaItemActions } from "@/features/media-manager/media-item-actions";
import { buildMediaMetaChips, mediaChipToneClass } from "@/features/media-manager/components/media-item-meta";

export function MediaRowsList({ trackedImages, onDeleteLinkClick, onEditClick, onViewClick }) {
  return (
    <div className="space-y-2">
      {trackedImages.map((image) => {
        const chips = buildMediaMetaChips(image);

        return (
          <article
            key={image.id}
            className="rounded-lg border border-slate-200 bg-white"
          >
            <header className="flex items-center justify-between border-b border-sky-100 bg-gradient-to-r from-sky-50/80 to-violet-50/60 px-3 py-2">
              <div className="flex min-w-0 items-center gap-3">
                <img
                  src={image.url}
                  alt={image.title || `Tracked image ${image.id}`}
                  className="h-10 w-10 rounded-md object-cover ring-1 ring-inset ring-slate-200"
                  loading="lazy"
                />
                <div className="min-w-0">
                  <p className="truncate text-sm font-semibold text-slate-900">{image.title || `#${image.id}`}</p>
                  <p className="truncate text-[11px] text-slate-600">{image.uploaded_by || "Unknown uploader"}</p>
                </div>
              </div>
              <span className="ml-2 shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700 ring-1 ring-inset ring-sky-200">
                #{image.id}
              </span>
            </header>

            <div className="px-3 py-3">
              <div className="flex flex-wrap gap-1.5">
                {chips.map((chip) => (
                  <span key={`${image.id}-${chip.text}`} className={`rounded px-2 py-1 text-[11px] font-medium ${mediaChipToneClass(chip.tone)}`}>
                    {chip.text}
                  </span>
                ))}
                {chips.length === 0 ? (
                  <span className="rounded bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700">
                    Basic metadata only
                  </span>
                ) : null}
              </div>
            </div>

            <footer className="border-t border-slate-200 px-3 py-2">
              <div className="flex items-center gap-2">
                <MediaItemActions
                  image={image}
                  onDeleteClick={onDeleteLinkClick}
                  onEditClick={onEditClick}
                  onViewClick={onViewClick}
                />
              </div>
            </footer>
          </article>
        );
      })}
    </div>
  );
}
