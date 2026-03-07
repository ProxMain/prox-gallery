export function formatDimensions(width, height) {
  const w = Number(width);
  const h = Number(height);

  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) {
    return null;
  }

  return `${w}x${h}`;
}

export function buildMediaMetaChips(image) {
  const chips = [];

  if (image.mime_type) {
    chips.push({ text: image.mime_type, tone: "violet" });
  }

  const dimensions = formatDimensions(image.width, image.height);

  if (dimensions) {
    chips.push({ text: dimensions, tone: "indigo" });
  }

  if (Array.isArray(image.categories) && image.categories.length > 0) {
    chips.push({
      text: `${image.categories.length} categor${image.categories.length === 1 ? "y" : "ies"}`,
      tone: "amber"
    });
  }

  if (Array.isArray(image.gallery_ids) && image.gallery_ids.length > 0) {
    chips.push({
      text: `${image.gallery_ids.length} galler${image.gallery_ids.length === 1 ? "y" : "ies"}`,
      tone: "emerald"
    });
  }

  return chips;
}

export function mediaChipToneClass(tone) {
  if (tone === "violet") {
    return "bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200";
  }

  if (tone === "indigo") {
    return "bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200";
  }

  if (tone === "amber") {
    return "bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200";
  }

  if (tone === "emerald") {
    return "bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200";
  }

  return "bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200";
}
