import { Eye, Pencil, Trash2 } from "lucide-react";

function resolveEditUrl(imageId, editUrl) {
  if (editUrl !== "") {
    return editUrl;
  }

  return `/wp-admin/post.php?post=${imageId}&action=edit`;
}

export function MediaItemActions({
  image,
  buttonClassName = "",
  onDeleteClick,
  onViewClick,
  onEditClick
}) {
  return (
    <>
      <a
        href={image.view_url || image.url}
        target="_blank"
        rel="noreferrer noopener"
        className={buttonClassName}
        onClick={(event) => {
          if (!onViewClick) {
            return;
          }

          event.preventDefault();
          onViewClick(image);
        }}
        aria-label={`View ${image.title || `image ${image.id}`}`}
      >
        <Eye className="h-4 w-4" />
      </a>
      <a
        href={resolveEditUrl(image.id, image.edit_url || "")}
        className={buttonClassName}
        onClick={(event) => {
          if (!onEditClick) {
            return;
          }

          event.preventDefault();
          onEditClick(image);
        }}
        aria-label={`Edit ${image.title || `image ${image.id}`}`}
      >
        <Pencil className="h-4 w-4" />
      </a>
      <a
        href={image.delete_url || "#"}
        onClick={(event) => onDeleteClick(event, image.delete_url || "")}
        className={buttonClassName}
        aria-label={`Delete ${image.title || `image ${image.id}`}`}
      >
        <Trash2 className="h-4 w-4" />
      </a>
    </>
  );
}
