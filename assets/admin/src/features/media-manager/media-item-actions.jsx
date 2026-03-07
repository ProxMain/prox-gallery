import { Eye, Pencil, Trash2 } from "lucide-react";
import { useId } from "react";

function resolveEditUrl(imageId, editUrl) {
  if (editUrl !== "") {
    return editUrl;
  }

  return `/wp-admin/post.php?post=${imageId}&action=edit`;
}

export function MediaItemActions({
  image,
  onDeleteClick,
  onViewClick,
  onEditClick
}) {
  const viewTooltipId = useId();
  const editTooltipId = useId();
  const deleteTooltipId = useId();

  const baseButtonClass =
    "inline-flex h-8 w-8 items-center justify-center rounded-md border transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2";
  const viewButtonClass = `${baseButtonClass} border-violet-200 text-violet-700 hover:bg-violet-50`;
  const editButtonClass = `${baseButtonClass} border-slate-200 text-slate-700 hover:bg-slate-50`;
  const deleteButtonClass = `${baseButtonClass} border-red-200 text-red-700 hover:bg-red-50`;

  return (
    <>
      <div className="group relative">
        <a
          href={image.view_url || image.url}
          target="_blank"
          rel="noreferrer noopener"
          className={viewButtonClass}
          onClick={(event) => {
            if (!onViewClick) {
              return;
            }

            event.preventDefault();
            onViewClick(image);
          }}
          aria-label={`View ${image.title || `image ${image.id}`}`}
          aria-describedby={viewTooltipId}
        >
          <Eye className="h-4 w-4 text-violet-700" />
        </a>
        <div
          id={viewTooltipId}
          role="tooltip"
          className="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 w-52 -translate-x-1/2 rounded-md border border-slate-200 bg-slate-900 px-2 py-1.5 text-[11px] leading-4 text-white opacity-0 shadow-lg transition duration-150 group-hover:opacity-100 group-focus-within:opacity-100"
        >
          <p className="font-semibold text-white">View</p>
          <p>Open image preview and metadata tabs.</p>
          <span className="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 border-b border-r border-slate-200 bg-slate-900" />
        </div>
      </div>

      <div className="group relative">
        <a
          href={resolveEditUrl(image.id, image.edit_url || "")}
          className={editButtonClass}
          onClick={(event) => {
            if (!onEditClick) {
              return;
            }

            event.preventDefault();
            onEditClick(image);
          }}
          aria-label={`Edit ${image.title || `image ${image.id}`}`}
          aria-describedby={editTooltipId}
        >
          <Pencil className="h-4 w-4 text-slate-700" />
        </a>
        <div
          id={editTooltipId}
          role="tooltip"
          className="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 w-52 -translate-x-1/2 rounded-md border border-slate-200 bg-slate-900 px-2 py-1.5 text-[11px] leading-4 text-white opacity-0 shadow-lg transition duration-150 group-hover:opacity-100 group-focus-within:opacity-100"
        >
          <p className="font-semibold text-white">Edit</p>
          <p>Update metadata, categories and galleries.</p>
          <span className="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 border-b border-r border-slate-200 bg-slate-900" />
        </div>
      </div>

      <div className="group relative">
        <a
          href={image.delete_url || "#"}
          onClick={(event) => onDeleteClick(event, image.delete_url || "")}
          className={deleteButtonClass}
          aria-label={`Delete ${image.title || `image ${image.id}`}`}
          aria-describedby={deleteTooltipId}
        >
          <Trash2 className="h-4 w-4 text-red-700" />
        </a>
        <div
          id={deleteTooltipId}
          role="tooltip"
          className="pointer-events-none absolute bottom-full left-1/2 z-50 mb-2 w-52 -translate-x-1/2 rounded-md border border-slate-200 bg-slate-900 px-2 py-1.5 text-[11px] leading-4 text-white opacity-0 shadow-lg transition duration-150 group-hover:opacity-100 group-focus-within:opacity-100"
        >
          <p className="font-semibold text-white">Delete</p>
          <p>Remove this tracked image from the manager.</p>
          <span className="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 border-b border-r border-slate-200 bg-slate-900" />
        </div>
      </div>
    </>
  );
}
