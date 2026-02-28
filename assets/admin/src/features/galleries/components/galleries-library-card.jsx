import { FilePlus2, Filter, FolderOpen, Images, LayoutGrid, List, Pencil, Plus, RefreshCcw, Settings2, Trash2 } from "lucide-react";
import { useId, useMemo, useState } from "react";

import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";

function ActionIconButton({ label, description, onClick, disabled, tone = "slate", children }) {
  const tooltipId = useId();
  const toneClass =
    tone === "violet"
      ? "border-violet-200 text-violet-700 hover:bg-violet-50"
      : tone === "emerald"
      ? "border-emerald-200 text-emerald-700 hover:bg-emerald-50"
      : tone === "sky"
      ? "border-sky-200 text-sky-700 hover:bg-sky-50"
      : tone === "red"
      ? "border-red-200 text-red-700 hover:bg-red-50"
      : "border-slate-200 text-slate-700 hover:bg-slate-50";

  return (
    <div className="group relative">
      <button
        type="button"
        onClick={onClick}
        disabled={disabled}
        className={`inline-flex h-8 w-8 items-center justify-center rounded-md border transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 disabled:opacity-60 ${toneClass}`}
        aria-label={`${label}: ${description}`}
        aria-describedby={tooltipId}
      >
        {children}
      </button>
      <div
        id={tooltipId}
        role="tooltip"
        className="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 w-56 -translate-x-1/2 rounded-md border border-slate-200 bg-slate-900 px-2 py-1.5 text-[11px] leading-4 text-white opacity-0 shadow-lg transition duration-150 group-hover:opacity-100 group-focus-within:opacity-100"
      >
        <p className="font-semibold text-white">{label}</p>
        <p>{description}</p>
        <span className="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 border-b border-r border-slate-200 bg-slate-900" />
      </div>
    </div>
  );
}

export function GalleriesLibraryCard({
  galleries,
  templateOptions = [],
  isLoading,
  error,
  onReloadGalleries,
  onCreateGallery,
  onRenameGallery,
  onDeleteGallery,
  onCreateGalleryPage,
  onLoadTrackedImages,
  onAddImagesToGallery,
  onSetGalleryImages
}) {
  const toNullableInt = (value) => (value === "inherit" ? null : Number(value));
  const toNullableBool = (value) => {
    if (value === "inherit") {
      return null;
    }

    return value === "on";
  };

  const boolOverrideLabel = (value) => {
    if (value === null || value === undefined) {
      return "Inherit";
    }

    return value ? "On" : "Off";
  };

  const templateLabelForGallery = (gallery) =>
    typeof gallery.template === "string" && gallery.template !== "" ? gallery.template : "basic-grid";

  const buildGalleryMetaChips = (gallery) => {
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

    return chips;
  };

  const chipToneClass = (tone) => {
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

    return "bg-slate-100 text-slate-700";
  };

  const [viewMode, setViewMode] = useState("grid");
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const [sortMode, setSortMode] = useState("date_desc");
  const [templateFilter, setTemplateFilter] = useState("all");
  const [createName, setCreateName] = useState("");
  const [createDescription, setCreateDescription] = useState("");
  const [createTemplate, setCreateTemplate] = useState("basic-grid");
  const [createGridColumnsOverride, setCreateGridColumnsOverride] = useState("inherit");
  const [createLightboxOverride, setCreateLightboxOverride] = useState("inherit");
  const [createHoverZoomOverride, setCreateHoverZoomOverride] = useState("inherit");
  const [createFullWidthOverride, setCreateFullWidthOverride] = useState("inherit");
  const [createTransitionOverride, setCreateTransitionOverride] = useState("inherit");
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  const [createMessage, setCreateMessage] = useState("");
  const [isMutating, setIsMutating] = useState(false);
  const [activeAddGalleryId, setActiveAddGalleryId] = useState(null);
  const [trackedImages, setTrackedImages] = useState([]);
  const [selectedImageIds, setSelectedImageIds] = useState([]);
  const [isLoadingTrackedImages, setIsLoadingTrackedImages] = useState(false);
  const [draggedImageId, setDraggedImageId] = useState(null);
  const [activeDisplayGalleryId, setActiveDisplayGalleryId] = useState(null);
  const [editGridColumnsOverride, setEditGridColumnsOverride] = useState("inherit");
  const [editLightboxOverride, setEditLightboxOverride] = useState("inherit");
  const [editHoverZoomOverride, setEditHoverZoomOverride] = useState("inherit");
  const [editFullWidthOverride, setEditFullWidthOverride] = useState("inherit");
  const [editTransitionOverride, setEditTransitionOverride] = useState("inherit");
  const [editTemplate, setEditTemplate] = useState("basic-grid");

  const handleCreateTemplateChange = (nextTemplate) => {
    setCreateTemplate(nextTemplate);

    if (nextTemplate === "masonry") {
      setCreateFullWidthOverride("on");
      return;
    }

    if (nextTemplate === "basic-grid") {
      setCreateFullWidthOverride("inherit");
    }
  };

  const availableTemplates = useMemo(() => {
    const map = new Map();
    const defaults = [
      { slug: "basic-grid", label: "Basic Grid", is_pro: false, available: true },
      { slug: "masonry", label: "Masonry", is_pro: false, available: true }
    ];

    defaults.forEach((template) => {
      map.set(template.slug, template);
    });

    templateOptions.forEach((template) => {
      if (!template || typeof template.slug !== "string" || template.slug === "") {
        return;
      }

      map.set(template.slug, {
        slug: template.slug,
        label: typeof template.label === "string" && template.label !== "" ? template.label : template.slug,
        is_pro: Boolean(template.is_pro),
        available: Boolean(template.available)
      });
    });

    return Array.from(map.values()).filter((template) => Boolean(template.available));
  }, [templateOptions]);

  const allTemplateOptions = useMemo(() => {
    const map = new Map(
      availableTemplates.map((template) => [template.slug, template])
    );

    templateOptions.forEach((template) => {
      if (!template || typeof template.slug !== "string" || template.slug === "") {
        return;
      }

      if (!map.has(template.slug)) {
        map.set(template.slug, template);
      }
    });

    return Array.from(map.values());
  }, [availableTemplates, templateOptions]);

  const visibleGalleries = useMemo(() => {
    const sorted = [...galleries].sort((left, right) => {
      const leftDate = Date.parse(String(left.created_at || ""));
      const rightDate = Date.parse(String(right.created_at || ""));
      const leftValue = Number.isNaN(leftDate) ? 0 : leftDate;
      const rightValue = Number.isNaN(rightDate) ? 0 : rightDate;

      return sortMode === "date_asc" ? leftValue - rightValue : rightValue - leftValue;
    });

    if (templateFilter === "all") {
      return sorted;
    }

    return sorted.filter((gallery) => {
      const slug = typeof gallery.template === "string" && gallery.template !== "" ? gallery.template : "basic-grid";
      return slug === templateFilter;
    });
  }, [galleries, sortMode, templateFilter]);

  const activeDisplayGallery = useMemo(
    () => galleries.find((item) => item.id === activeDisplayGalleryId) ?? null,
    [galleries, activeDisplayGalleryId]
  );

  const selectedImageItems = useMemo(() => {
    const byId = new Map(trackedImages.map((image) => [image.id, image]));

    return selectedImageIds
      .map((id) => byId.get(id))
      .filter(Boolean);
  }, [trackedImages, selectedImageIds]);

  const handleCreate = async () => {
    const trimmedName = createName.trim();

    if (trimmedName === "") {
      setCreateMessage("Gallery name is required.");
      return;
    }

    try {
      setIsCreating(true);
      setCreateMessage("");
      await onCreateGallery({
        name: trimmedName,
        description: createDescription.trim(),
        template: createTemplate,
        grid_columns_override: toNullableInt(createGridColumnsOverride),
        lightbox_override: toNullableBool(createLightboxOverride),
        hover_zoom_override: toNullableBool(createHoverZoomOverride),
        full_width_override: toNullableBool(createFullWidthOverride),
        transition_override: createTransitionOverride === "inherit" ? null : createTransitionOverride
      });
      setCreateName("");
      setCreateDescription("");
      setCreateGridColumnsOverride("inherit");
      setCreateLightboxOverride("inherit");
      setCreateHoverZoomOverride("inherit");
      setCreateFullWidthOverride("inherit");
      setCreateTransitionOverride("inherit");
      setIsCreateOpen(false);
      setCreateMessage("Gallery created.");
    } catch (createError) {
      const message = createError instanceof Error ? createError.message : "Failed to create gallery.";
      setCreateMessage(message);
    } finally {
      setIsCreating(false);
    }
  };

  const handleRename = async (gallery) => {
    const nextName = window.prompt("Rename gallery", gallery.name || "");

    if (nextName === null) {
      return;
    }

    const trimmed = nextName.trim();

    if (trimmed === "") {
      setCreateMessage("Gallery name is required.");
      return;
    }

    try {
      setIsMutating(true);
      setCreateMessage("");
      await onRenameGallery({
        id: gallery.id,
        name: trimmed,
        description: gallery.description || "",
        template: typeof gallery.template === "string" ? gallery.template : "basic-grid",
        grid_columns_override:
          typeof gallery.grid_columns_override === "number" ? gallery.grid_columns_override : null,
        lightbox_override:
          typeof gallery.lightbox_override === "boolean" ? gallery.lightbox_override : null,
        hover_zoom_override:
          typeof gallery.hover_zoom_override === "boolean" ? gallery.hover_zoom_override : null,
        full_width_override:
          typeof gallery.full_width_override === "boolean" ? gallery.full_width_override : null,
        transition_override:
          typeof gallery.transition_override === "string" ? gallery.transition_override : null
      });
      setCreateMessage("Gallery renamed.");
    } catch (renameError) {
      const message = renameError instanceof Error ? renameError.message : "Failed to rename gallery.";
      setCreateMessage(message);
    } finally {
      setIsMutating(false);
    }
  };

  const handleOpenDisplaySettings = (gallery) => {
    setActiveDisplayGalleryId(gallery.id);
    setEditTemplate(
      typeof gallery.template === "string" && gallery.template !== "" ? gallery.template : "basic-grid"
    );
    setEditGridColumnsOverride(
      typeof gallery.grid_columns_override === "number" ? String(gallery.grid_columns_override) : "inherit"
    );
    setEditLightboxOverride(
      typeof gallery.lightbox_override === "boolean" ? (gallery.lightbox_override ? "on" : "off") : "inherit"
    );
    setEditHoverZoomOverride(
      typeof gallery.hover_zoom_override === "boolean" ? (gallery.hover_zoom_override ? "on" : "off") : "inherit"
    );
    setEditFullWidthOverride(
      typeof gallery.full_width_override === "boolean" ? (gallery.full_width_override ? "on" : "off") : "inherit"
    );
    setEditTransitionOverride(
      typeof gallery.transition_override === "string" ? gallery.transition_override : "inherit"
    );
    setCreateMessage("");
  };

  const handleSaveDisplaySettings = async (gallery) => {
    try {
      setIsMutating(true);
      setCreateMessage("");
      await onRenameGallery({
        id: gallery.id,
        name: gallery.name || "",
        description: gallery.description || "",
        template: editTemplate,
        grid_columns_override: toNullableInt(editGridColumnsOverride),
        lightbox_override: toNullableBool(editLightboxOverride),
        hover_zoom_override: toNullableBool(editHoverZoomOverride),
        full_width_override: toNullableBool(editFullWidthOverride),
        transition_override: editTransitionOverride === "inherit" ? null : editTransitionOverride
      });
      setActiveDisplayGalleryId(null);
      setCreateMessage("Per-gallery display settings updated.");
    } catch (saveError) {
      const message = saveError instanceof Error ? saveError.message : "Failed to update display settings.";
      setCreateMessage(message);
    } finally {
      setIsMutating(false);
    }
  };

  const handleDelete = async (gallery) => {
    const confirmed = window.confirm(`Delete gallery \"${gallery.name}\"?`);

    if (!confirmed) {
      return;
    }

    try {
      setIsMutating(true);
      setCreateMessage("");
      await onDeleteGallery({ id: gallery.id });
      setCreateMessage("Gallery deleted.");
    } catch (deleteError) {
      const message = deleteError instanceof Error ? deleteError.message : "Failed to delete gallery.";
      setCreateMessage(message);
    } finally {
      setIsMutating(false);
    }
  };

  const handleCreatePage = async (gallery) => {
    try {
      setIsMutating(true);
      setCreateMessage("");
      const response = await onCreateGalleryPage({ id: gallery.id });
      const pageUrl = typeof response?.page_url === "string" ? response.page_url : "";

      if (pageUrl !== "") {
        setCreateMessage(`Gallery page ready: ${pageUrl}`);
      } else {
        setCreateMessage("Gallery page created and added to menu.");
      }
    } catch (pageError) {
      const message = pageError instanceof Error ? pageError.message : "Failed to create gallery page.";
      setCreateMessage(message);
    } finally {
      setIsMutating(false);
    }
  };

  const handleOpenAddImages = async (galleryId) => {
    const gallery = galleries.find((item) => item.id === galleryId);

    setActiveAddGalleryId(galleryId);
    setSelectedImageIds(Array.isArray(gallery?.image_ids) ? gallery.image_ids : []);
    setCreateMessage("");

    try {
      setIsLoadingTrackedImages(true);
      const items = await onLoadTrackedImages();
      setTrackedImages(Array.isArray(items) ? items : []);
    } catch (loadError) {
      const message = loadError instanceof Error ? loadError.message : "Failed to load tracked images.";
      setCreateMessage(message);
    } finally {
      setIsLoadingTrackedImages(false);
    }
  };

  const toggleImageSelection = (imageId) => {
    setSelectedImageIds((current) => {
      if (current.includes(imageId)) {
        return current.filter((id) => id !== imageId);
      }

      return [...current, imageId];
    });
  };

  const handleAddImages = async () => {
    if (!activeAddGalleryId || selectedImageIds.length === 0) {
      setCreateMessage("Select at least one image.");
      return;
    }

    try {
      setIsMutating(true);
      setCreateMessage("");
      if (onSetGalleryImages) {
        await onSetGalleryImages(activeAddGalleryId, selectedImageIds);
      } else {
        await onAddImagesToGallery(activeAddGalleryId, selectedImageIds);
      }
      setCreateMessage("Gallery images updated.");
      setActiveAddGalleryId(null);
      setSelectedImageIds([]);
    } catch (addError) {
      const message = addError instanceof Error ? addError.message : "Failed to add images.";
      setCreateMessage(message);
    } finally {
      setIsMutating(false);
    }
  };

  const handleDragStart = (imageId) => {
    setDraggedImageId(imageId);
  };

  const handleDropOnImage = (targetImageId) => {
    if (draggedImageId === null || draggedImageId === targetImageId) {
      return;
    }

    setSelectedImageIds((current) => {
      const sourceIndex = current.indexOf(draggedImageId);
      const targetIndex = current.indexOf(targetImageId);

      if (sourceIndex < 0 || targetIndex < 0) {
        return current;
      }

      const next = [...current];
      next.splice(sourceIndex, 1);
      next.splice(targetIndex, 0, draggedImageId);

      return next;
    });
    setDraggedImageId(null);
  };

  return (
    <Card>
      <CardHeader className="p-0">
        <SectionHeader
          framed={false}
          icon={FolderOpen}
          title="Galleries"
          description="Placeholder workspace aligned with Media Manager layout."
          actions={
            <>
              <button
                type="button"
                onClick={() => setIsCreateOpen((current) => !current)}
                className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <Plus className="h-4 w-4 text-sky-700" />
                <span>New</span>
              </button>
              <button
                type="button"
                onClick={() => setViewMode("grid")}
                className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <LayoutGrid className="h-4 w-4 text-indigo-700" />
                <span>Grid</span>
              </button>
              <button
                type="button"
                onClick={() => setViewMode("row")}
                className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <List className="h-4 w-4 text-emerald-700" />
                <span>Rows</span>
              </button>
              <button
                type="button"
                onClick={() => setIsFilterOpen((current) => !current)}
                className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <Filter className="h-4 w-4 text-slate-700" />
                <span>Filters</span>
              </button>
              <button
                type="button"
                onClick={onReloadGalleries}
                className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <RefreshCcw className="h-4 w-4 text-sky-700" />
                <span>Reload</span>
              </button>
            </>
          }
        />
      </CardHeader>
      <CardContent>
        {isCreateOpen ? (
          <div className="mb-4 space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3">
            <input
              type="text"
              value={createName}
              onChange={(event) => setCreateName(event.target.value)}
              placeholder="Gallery name"
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
            <textarea
              value={createDescription}
              onChange={(event) => setCreateDescription(event.target.value)}
              placeholder="Description (optional)"
              rows={3}
              className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
            <label className="space-y-1">
              <span className="text-xs font-medium text-slate-600">Template</span>
              <select
                value={createTemplate}
                onChange={(event) => handleCreateTemplateChange(event.target.value)}
                className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
              >
                {availableTemplates.length > 0 ? (
                  availableTemplates.map((template) => (
                    <option key={template.slug} value={template.slug}>
                      {template.label}
                    </option>
                  ))
                ) : (
                  <>
                    <option value="basic-grid">Basic Grid</option>
                    <option value="masonry">Masonry</option>
                  </>
                )}
              </select>
            </label>
            <div className="grid gap-2 md:grid-cols-4">
              <label className="space-y-1">
                <span className="text-xs font-medium text-slate-600">Columns override</span>
                <select
                  value={createGridColumnsOverride}
                  onChange={(event) => setCreateGridColumnsOverride(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                >
                  <option value="inherit">Inherit global</option>
                  <option value="2">2 columns</option>
                  <option value="3">3 columns</option>
                  <option value="4">4 columns</option>
                  <option value="5">5 columns</option>
                  <option value="6">6 columns</option>
                </select>
              </label>
              <label className="space-y-1">
                <span className="text-xs font-medium text-slate-600">Lightbox override</span>
                <select
                  value={createLightboxOverride}
                  onChange={(event) => setCreateLightboxOverride(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                >
                  <option value="inherit">Inherit global</option>
                  <option value="on">Force on</option>
                  <option value="off">Force off</option>
                </select>
              </label>
              <label className="space-y-1">
                <span className="text-xs font-medium text-slate-600">Hover zoom override</span>
                <select
                  value={createHoverZoomOverride}
                  onChange={(event) => setCreateHoverZoomOverride(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                >
                  <option value="inherit">Inherit global</option>
                  <option value="on">Force on</option>
                  <option value="off">Force off</option>
                </select>
              </label>
              <label className="space-y-1">
                <span className="text-xs font-medium text-slate-600">Full width override</span>
                <select
                  value={createFullWidthOverride}
                  onChange={(event) => setCreateFullWidthOverride(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                >
                  <option value="inherit">Inherit global</option>
                  <option value="on">Force on</option>
                  <option value="off">Force off</option>
                </select>
              </label>
              <label className="space-y-1">
                <span className="text-xs font-medium text-slate-600">Transition override</span>
                <select
                  value={createTransitionOverride}
                  onChange={(event) => setCreateTransitionOverride(event.target.value)}
                  className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900"
                >
                  <option value="inherit">Inherit global</option>
                  <option value="none">None</option>
                  <option value="slide">Slide</option>
                  <option value="fade">Fade</option>
                  <option value="explode">Explode</option>
                  <option value="implode">Implode</option>
                </select>
              </label>
            </div>
            <button
              type="button"
              onClick={handleCreate}
              disabled={isCreating || isMutating}
              className="inline-flex items-center gap-2 rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-70"
            >
              {isCreating ? "Creating..." : "Create gallery"}
            </button>
          </div>
        ) : null}

        {isFilterOpen ? (
          <div className="mb-4 grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3 md:grid-cols-2">
            <label className="space-y-1">
              <span className="text-xs font-medium text-slate-600">Sort by date</span>
              <select
                value={sortMode}
                onChange={(event) => setSortMode(event.target.value)}
                className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
              >
                <option value="date_desc">Newest first</option>
                <option value="date_asc">Oldest first</option>
              </select>
            </label>
            <label className="space-y-1">
              <span className="text-xs font-medium text-slate-600">Filter by template</span>
              <select
                value={templateFilter}
                onChange={(event) => setTemplateFilter(event.target.value)}
                className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
              >
                <option value="all">All templates</option>
                {allTemplateOptions.map((template) => (
                  <option key={template.slug} value={template.slug} disabled={!template.available}>
                    {template.label}
                    {template.is_pro ? " (Pro)" : ""}
                    {!template.available ? " - Locked" : ""}
                  </option>
                ))}
              </select>
            </label>
          </div>
        ) : null}

        {isLoading ? (
          <p className="text-sm text-slate-600">Loading galleries...</p>
        ) : visibleGalleries.length === 0 ? (
          <div className="flex min-h-[220px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
            <h3 className="text-lg font-semibold text-slate-900">No galleries yet</h3>
            <p className="mt-1 max-w-xl text-sm text-slate-600">
              Use the New action to create your first gallery.
            </p>
          </div>
        ) : viewMode === "grid" ? (
          <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            {visibleGalleries.map((gallery) => (
              <article key={gallery.id} className="flex h-full flex-col rounded-lg border border-slate-200 bg-white">
                <header className="flex items-start justify-between border-b border-sky-100 bg-gradient-to-r from-sky-50/80 to-violet-50/60 px-3 py-2">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-900">{gallery.name}</p>
                    <p className="mt-0.5 text-[11px] text-slate-500">Created {gallery.created_at || "Unknown"}</p>
                  </div>
                  <span className="ml-2 shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700 ring-1 ring-inset ring-sky-200">
                    {gallery.image_count || 0} images
                  </span>
                </header>
                <div className="space-y-2 px-3 py-3">
                  <p className="min-h-[42px] rounded-md bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                    {gallery.description || "No description yet. Add context so collaborators know what belongs in this gallery."}
                  </p>
                  <div className="flex flex-wrap gap-1.5">
                    {buildGalleryMetaChips(gallery).map((chip) => (
                      <span key={chip.text} className={`rounded px-2 py-1 text-[11px] font-medium ${chipToneClass(chip.tone)}`}>
                        {chip.text}
                      </span>
                    ))}
                    {buildGalleryMetaChips(gallery).length === 1 ? (
                      <span className="rounded bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700">
                        Using global display defaults
                      </span>
                    ) : null}
                  </div>
                </div>
                <footer className="mt-auto border-t border-slate-200 px-3 py-2">
                  <div className="flex items-center gap-2">
                    <ActionIconButton
                      label="Details"
                      description="Open gallery display settings and template options."
                      onClick={() => handleOpenDisplaySettings(gallery)}
                      disabled={isMutating}
                      tone="violet"
                    >
                      <Settings2 className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Create page"
                      description="Create a frontend page, add shortcode and menu link."
                      onClick={() => void handleCreatePage(gallery)}
                      disabled={isMutating}
                      tone="emerald"
                    >
                      <FilePlus2 className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Add images"
                      description="Select and order tracked images for this gallery."
                      onClick={() => void handleOpenAddImages(gallery.id)}
                      disabled={isMutating}
                      tone="sky"
                    >
                      <Images className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Rename"
                      description="Change the gallery name and keep current settings."
                      onClick={() => void handleRename(gallery)}
                      disabled={isMutating}
                    >
                      <Pencil className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Delete"
                      description="Permanently remove this gallery and its assignments."
                      onClick={() => void handleDelete(gallery)}
                      disabled={isMutating}
                      tone="red"
                    >
                      <Trash2 className="h-4 w-4" />
                    </ActionIconButton>
                  </div>
                </footer>
              </article>
            ))}
          </div>
        ) : (
          <div className="space-y-2">
            {visibleGalleries.map((gallery) => (
              <article key={gallery.id} className="rounded-lg border border-slate-200 bg-white">
                <header className="flex items-start justify-between border-b border-sky-100 bg-gradient-to-r from-sky-50/80 to-violet-50/60 px-3 py-2">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-900">{gallery.name}</p>
                    <p className="mt-0.5 text-[11px] text-slate-500">Created {gallery.created_at || "Unknown"}</p>
                  </div>
                  <span className="ml-2 shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-700 ring-1 ring-inset ring-sky-200">
                    {gallery.image_count || 0} images
                  </span>
                </header>
                <div className="space-y-2 px-3 py-3">
                  <p className="rounded-md bg-slate-50 px-2 py-1.5 text-xs text-slate-700">
                    {gallery.description || "No description yet. Add context so collaborators know what belongs in this gallery."}
                  </p>
                  <div className="flex flex-wrap gap-1.5">
                    {buildGalleryMetaChips(gallery).map((chip) => (
                      <span key={chip.text} className={`rounded px-2 py-1 text-[11px] font-medium ${chipToneClass(chip.tone)}`}>
                        {chip.text}
                      </span>
                    ))}
                    {buildGalleryMetaChips(gallery).length === 1 ? (
                      <span className="rounded bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700">
                        Using global display defaults
                      </span>
                    ) : null}
                  </div>
                </div>
                <footer className="border-t border-slate-200 px-3 py-2">
                  <div className="flex items-center gap-2">
                    <ActionIconButton
                      label="Details"
                      description="Open gallery display settings and template options."
                      onClick={() => handleOpenDisplaySettings(gallery)}
                      disabled={isMutating}
                      tone="violet"
                    >
                      <Settings2 className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Create page"
                      description="Create a frontend page, add shortcode and menu link."
                      onClick={() => void handleCreatePage(gallery)}
                      disabled={isMutating}
                      tone="emerald"
                    >
                      <FilePlus2 className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Add images"
                      description="Select and order tracked images for this gallery."
                      onClick={() => void handleOpenAddImages(gallery.id)}
                      disabled={isMutating}
                      tone="sky"
                    >
                      <Images className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Rename"
                      description="Change the gallery name and keep current settings."
                      onClick={() => void handleRename(gallery)}
                      disabled={isMutating}
                    >
                      <Pencil className="h-4 w-4" />
                    </ActionIconButton>
                    <ActionIconButton
                      label="Delete"
                      description="Permanently remove this gallery and its assignments."
                      onClick={() => void handleDelete(gallery)}
                      disabled={isMutating}
                      tone="red"
                    >
                      <Trash2 className="h-4 w-4" />
                    </ActionIconButton>
                  </div>
                </footer>
              </article>
            ))}
          </div>
        )}

        {activeDisplayGallery ? (
          <div
            className="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/35 p-4"
            role="dialog"
            aria-modal="true"
          >
            <div className="w-full max-w-4xl rounded-xl border border-slate-200 bg-white shadow-2xl [animation:prox-gallery-slide-up_180ms_ease-out]">
              <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <p className="text-sm font-semibold text-slate-900">Gallery details: {activeDisplayGallery.name}</p>
                <button
                  type="button"
                  onClick={() => setActiveDisplayGalleryId(null)}
                  className="h-8 rounded-md border border-slate-300 px-3 text-xs text-slate-700 transition hover:bg-slate-100"
                >
                  Close
                </button>
              </div>
              <div className="grid gap-3 p-4">
                <fieldset className="min-w-0 rounded border border-slate-200 bg-slate-50 p-3">
                  <legend className="px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                    Template
                  </legend>
                  <label className="flex min-w-0 flex-col gap-1">
                    <span className="text-xs font-medium text-slate-600">Template</span>
                    <select
                      value={editTemplate}
                      onChange={(event) => setEditTemplate(event.target.value)}
                      className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-900"
                    >
                      {allTemplateOptions.map((template) => (
                        <option
                          key={template.slug}
                          value={template.slug}
                          disabled={!template.available && template.slug !== editTemplate}
                        >
                          {template.label}
                          {template.is_pro ? " (Pro)" : ""}
                          {!template.available ? " - Locked" : ""}
                        </option>
                      ))}
                    </select>
                  </label>
                </fieldset>
                <fieldset className="min-w-0 rounded border border-slate-200 bg-slate-50 p-3">
                  <legend className="px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                    Grid settings
                  </legend>
                  <div className="grid gap-2 md:grid-cols-3">
                    <label className="flex min-w-0 flex-col gap-1">
                      <span className="text-xs font-medium text-slate-600">Columns</span>
                      <select
                        value={editGridColumnsOverride}
                        onChange={(event) => setEditGridColumnsOverride(event.target.value)}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-900"
                      >
                        <option value="inherit">Inherit</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                      </select>
                    </label>
                    <label className="flex min-w-0 flex-col gap-1">
                      <span className="text-xs font-medium text-slate-600">Hover zoom</span>
                      <select
                        value={editHoverZoomOverride}
                        onChange={(event) => setEditHoverZoomOverride(event.target.value)}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-900"
                      >
                        <option value="inherit">Inherit</option>
                        <option value="on">On</option>
                        <option value="off">Off</option>
                      </select>
                    </label>
                    <label className="flex min-w-0 flex-col gap-1">
                      <span className="text-xs font-medium text-slate-600">Full width</span>
                      <select
                        value={editFullWidthOverride}
                        onChange={(event) => setEditFullWidthOverride(event.target.value)}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-900"
                      >
                        <option value="inherit">Inherit</option>
                        <option value="on">On</option>
                        <option value="off">Off</option>
                      </select>
                    </label>
                  </div>
                </fieldset>
                <fieldset className="min-w-0 rounded border border-slate-200 bg-slate-50 p-3">
                  <legend className="px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                    Lightbox
                  </legend>
                  <div className="grid gap-2 md:grid-cols-2">
                    <label className="flex min-w-0 flex-col gap-1">
                      <span className="text-xs font-medium text-slate-600">Lightbox</span>
                      <select
                        value={editLightboxOverride}
                        onChange={(event) => setEditLightboxOverride(event.target.value)}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-900"
                      >
                        <option value="inherit">Inherit</option>
                        <option value="on">On</option>
                        <option value="off">Off</option>
                      </select>
                    </label>
                    <label className="flex min-w-0 flex-col gap-1">
                      <span className="text-xs font-medium text-slate-600">Transition</span>
                      <select
                        value={editTransitionOverride}
                        onChange={(event) => setEditTransitionOverride(event.target.value)}
                        className="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs text-slate-900"
                      >
                        <option value="inherit">Inherit</option>
                        <option value="none">None</option>
                        <option value="slide">Slide</option>
                        <option value="fade">Fade</option>
                        <option value="explode">Explode</option>
                        <option value="implode">Implode</option>
                      </select>
                    </label>
                  </div>
                </fieldset>
              </div>
              <div className="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3">
                <button
                  type="button"
                  onClick={() => void handleSaveDisplaySettings(activeDisplayGallery)}
                  disabled={isMutating}
                  className="h-8 rounded-md bg-violet-600 px-3 text-xs font-medium text-white transition hover:bg-violet-700 disabled:opacity-60"
                >
                  Save details
                </button>
                <button
                  type="button"
                  onClick={() => setActiveDisplayGalleryId(null)}
                  className="h-8 rounded-md border border-slate-300 px-3 text-xs text-slate-700 transition hover:bg-slate-100"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        ) : null}

        {activeAddGalleryId !== null ? (
          <div className="mt-4 space-y-2 rounded-md border border-slate-200 bg-slate-50 p-3">
            <p className="text-sm font-medium text-slate-800">Add images to gallery</p>
            {isLoadingTrackedImages ? (
              <p className="text-sm text-slate-600">Loading tracked images...</p>
            ) : trackedImages.length === 0 ? (
              <p className="text-sm text-slate-600">No tracked images available.</p>
            ) : (
              <div className="max-h-52 space-y-1 overflow-auto rounded-md border border-slate-200 bg-white p-2">
                {trackedImages.map((image) => (
                  <label key={image.id} className="flex items-center gap-2 rounded px-1 py-1 text-sm text-slate-800">
                    <input
                      type="checkbox"
                      checked={selectedImageIds.includes(image.id)}
                      onChange={() => toggleImageSelection(image.id)}
                      className="h-4 w-4 rounded border-slate-300"
                    />
                    <span className="truncate">{image.title || `#${image.id}`}</span>
                  </label>
                ))}
              </div>
            )}
            {selectedImageItems.length > 0 ? (
              <div className="space-y-1">
                <p className="text-xs font-medium text-slate-600">Selected order (drag to reorder)</p>
                <div className="max-h-44 space-y-1 overflow-auto rounded-md border border-slate-200 bg-white p-2">
                  {selectedImageItems.map((image) => (
                    <div
                      key={image.id}
                      draggable
                      onDragStart={() => handleDragStart(image.id)}
                      onDragOver={(event) => event.preventDefault()}
                      onDrop={() => handleDropOnImage(image.id)}
                      onDragEnd={() => setDraggedImageId(null)}
                      className="cursor-move rounded border border-slate-200 px-2 py-1 text-xs text-slate-700 transition hover:bg-slate-50"
                    >
                      {image.title || `#${image.id}`}
                    </div>
                  ))}
                </div>
              </div>
            ) : null}
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={handleAddImages}
                disabled={isMutating || isLoadingTrackedImages}
                className="rounded-md bg-sky-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-sky-700 disabled:opacity-70"
              >
                Save images
              </button>
              <button
                type="button"
                onClick={() => {
                  setActiveAddGalleryId(null);
                  setSelectedImageIds([]);
                }}
                className="rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-slate-100"
              >
                Cancel
              </button>
            </div>
          </div>
        ) : null}

        {error !== "" ? <p className="mt-3 text-sm text-red-600">{error}</p> : null}
        {createMessage !== "" ? <p className="mt-3 text-sm text-slate-700">{createMessage}</p> : null}
      </CardContent>
    </Card>
  );
}
