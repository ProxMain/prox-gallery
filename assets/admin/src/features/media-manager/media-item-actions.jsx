import { Eye, Pencil, Trash2 } from "lucide-react";

export function MediaItemActions({
  image,
  onDeleteClick,
  onViewClick,
  onEditClick,
}) {
  const name = image.title || `image ${image.id}`;
  return (
    <>
      <button
        type="button"
        className="pg-icon-button"
        onClick={() => onViewClick(image)}
        aria-label={`View ${name}`}
        title="View image"
      >
        <Eye size={15} />
      </button>
      <button
        type="button"
        className="pg-icon-button"
        onClick={() => onEditClick(image)}
        aria-label={`Edit ${name}`}
        title="Edit metadata"
      >
        <Pencil size={15} />
      </button>
      {image.delete_url ? (
        <a
          className="pg-icon-button pg-danger"
          href={image.delete_url}
          onClick={(event) => onDeleteClick(event, image.delete_url)}
          aria-label={`Delete ${name}`}
          title="Permanently delete from WordPress"
        >
          <Trash2 size={15} />
        </a>
      ) : null}
    </>
  );
}
