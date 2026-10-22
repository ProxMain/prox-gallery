const OVERRIDE_FIELDS = [
  "grid_columns_override",
  "lightbox_override",
  "hover_zoom_override",
  "full_width_override",
  "transition_override",
  "show_title",
  "show_description",
];

export function galleryOverrides(payload = {}) {
  return Object.fromEntries(
    OVERRIDE_FIELDS.filter((key) => Object.hasOwn(payload, key)).map((key) => [
      key,
      payload[key],
    ]),
  );
}
