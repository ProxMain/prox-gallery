=== Prox Gallery CLI Guide ===

== Overview ==
Prox Gallery tracks newly uploaded image attachments and stores tracked image DTO data.
The WP-CLI commands let you list tracked images, manually track an attachment,
and validate tracked rows.

== Commands ==
`wp prox media list-tracked`
`wp prox media track <id>`
`wp prox media validate`

Command behavior:
- `list-tracked`: prints tracked image rows as a table.
- `track <id>`: manually tracks an existing image attachment by ID.
- `validate`: removes stale tracked rows when the attachment no longer exists.

`list-tracked` prints a table with:
- `id`
- `title`
- `mime_type`
- `width`
- `height`
- `file_size`
- `camera`
- `iso`
- `uploaded_at`
- `uploaded_by`
- `url`

If no tracked images exist, it prints:
`Success: No tracked images found.`

== How Tracking Works ==
1. The Media Library module hooks into WordPress `add_attachment`.
2. On each new attachment, Prox Gallery checks if it is an image.
3. If it is an image, the ID is stored in the plugin queue option:
   `prox_gallery_uploaded_image_ids`

== Usage With wp-env ==
From project root:

1. Start environment:
`npx wp-env start`

2. Run command in CLI container:
`npx wp-env run cli wp prox media list-tracked`
`npx wp-env run cli wp prox media track 123`
`npx wp-env run cli wp prox media validate`

== Notes ==
- The list only includes images detected after the plugin is active.
- Non-image attachments are ignored.
- The queue deduplicates IDs and keeps recent tracked entries.
