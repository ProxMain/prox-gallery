function boolOverrideLabel(value) {
  if (value === null || value === undefined) {
    return "Inherit";
  }

  return value ? "On" : "Off";
}

function templateLabelForGallery(gallery) {
  return typeof gallery.template === "string" && gallery.template !== "" ? gallery.template : "basic-grid";
}

function buildGalleryMetaChips(gallery) {
  const chips = [
    {
      text: `Template: ${templateLabelForGallery(gallery)}`,
      tone: "violet"
    }
  ];

  if (typeof gallery.grid_columns_override === "number") {
    chips.push({
      text: `Columns: ${gallery.grid_columns_override}`,
      tone: "indigo"
    });
  }

  if (typeof gallery.lightbox_override === "boolean") {
    chips.push({
      text: `Lightbox: ${boolOverrideLabel(gallery.lightbox_override)}`,
      tone: "amber"
    });
  }

  if (typeof gallery.hover_zoom_override === "boolean") {
    chips.push({
      text: `Zoom: ${boolOverrideLabel(gallery.hover_zoom_override)}`,
      tone: "sky"
    });
  }

  if (typeof gallery.full_width_override === "boolean") {
    chips.push({
      text: `Full width: ${boolOverrideLabel(gallery.full_width_override)}`,
      tone: "emerald"
    });
  }

  if (typeof gallery.transition_override === "string" && gallery.transition_override !== "") {
    chips.push({
      text: `Transition: ${gallery.transition_override}`,
      tone: "rose"
    });
  }

  if (gallery.show_title === false) {
    chips.push({
      text: "Title hidden",
      tone: "slate"
    });
  }

  if (gallery.show_description === false) {
    chips.push({
      text: "Description hidden",
      tone: "slate"
    });
  }

  return chips;
}

function chipToneClass(tone) {
  if (tone === "violet") {
    return "bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200";
  }

  if (tone === "indigo") {
    return "bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200";
  }

  if (tone === "amber") {
    return "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200";
  }

  if (tone === "sky") {
    return "bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200";
  }

  if (tone === "emerald") {
    return "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200";
  }

  if (tone === "rose") {
    return "bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200";
  }

  if (tone === "slate") {
    return "bg-slate-200 text-slate-800 ring-1 ring-inset ring-slate-300";
  }

  return "bg-slate-100 text-slate-700";
}

export function GalleryMetaChipList({ gallery }) {
  const chips = buildGalleryMetaChips(gallery);

  return (
    <div className="flex flex-wrap gap-1.5">
      {chips.map((chip) => (
        <span key={chip.text} className={`rounded px-2 py-1 text-[11px] font-medium ${chipToneClass(chip.tone)}`}>
          {chip.text}
        </span>
      ))}
      {chips.length === 1 ? (
        <span className="rounded bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700">
          Using global display defaults
        </span>
      ) : null}
    </div>
  );
}
