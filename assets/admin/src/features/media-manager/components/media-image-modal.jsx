import { X } from "lucide-react";
import { useEffect, useState } from "react";

import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

function formatFileSize(sizeInBytes) {
  if (typeof sizeInBytes !== "number" || Number.isNaN(sizeInBytes) || sizeInBytes <= 0) {
    return "Unknown";
  }

  const units = ["B", "KB", "MB", "GB"];
  let size = sizeInBytes;
  let unitIndex = 0;

  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }

  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function formatDimensions(width, height) {
  if (typeof width !== "number" || typeof height !== "number" || width <= 0 || height <= 0) {
    return "Unknown";
  }

  return `${width} x ${height}`;
}

export function MediaImageModal({
  image,
  mode = "view",
  onSave,
  onClose,
  onLoadCategories,
  onSuggestCategories,
  onAssignCategories,
  onListGalleries,
  onLoadImageGalleries,
  onSetImageGalleries
}) {
  const [formState, setFormState] = useState({
    title: "",
    alt_text: "",
    caption: "",
    description: ""
  });
  const [isSaving, setIsSaving] = useState(false);
  const [saveError, setSaveError] = useState("");
  const [categories, setCategories] = useState([]);
  const [categoryQuery, setCategoryQuery] = useState("");
  const [categorySuggestions, setCategorySuggestions] = useState([]);
  const [isLoadingCategories, setIsLoadingCategories] = useState(false);
  const [categoriesError, setCategoriesError] = useState("");
  const [isSavingCategories, setIsSavingCategories] = useState(false);
  const [categorySaveNotice, setCategorySaveNotice] = useState("");
  const [categorySaveNoticeType, setCategorySaveNoticeType] = useState("success");
  const [galleries, setGalleries] = useState([]);
  const [selectedGalleryIds, setSelectedGalleryIds] = useState([]);
  const [isLoadingGalleries, setIsLoadingGalleries] = useState(false);
  const [galleriesNotice, setGalleriesNotice] = useState("");
  const [isSavingGalleries, setIsSavingGalleries] = useState(false);
  const isEditMode = mode === "edit";

  useEffect(() => {
    if (!image) {
      return;
    }

    setFormState({
      title: image.title || "",
      alt_text: image.alt_text || "",
      caption: image.caption || "",
      description: image.description || ""
    });
    setIsSaving(false);
    setSaveError("");
    setCategorySaveNotice("");
    setCategorySaveNoticeType("success");
  }, [image, mode]);

  useEffect(() => {
    if (!image || !onLoadCategories) {
      setCategories([]);
      return;
    }

    let active = true;

    const load = async () => {
      try {
        setIsLoadingCategories(true);
        setCategoriesError("");
        const items = await onLoadCategories(image.id);

        if (!active) {
          return;
        }

        setCategories(Array.isArray(items) ? items : []);
      } catch (error) {
        if (!active) {
          return;
        }

        const message = error instanceof Error ? error.message : "Failed to load categories.";
        setCategoriesError(message);
      } finally {
        if (active) {
          setIsLoadingCategories(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [image, onLoadCategories]);

  useEffect(() => {
    if (!onSuggestCategories || !isEditMode) {
      setCategorySuggestions([]);
      return;
    }

    const query = categoryQuery.trim();

    if (query === "") {
      setCategorySuggestions([]);
      return;
    }

    let active = true;
    const timeoutId = window.setTimeout(async () => {
      try {
        const suggestions = await onSuggestCategories(query);

        if (!active) {
          return;
        }

        const existing = new Set(categories.map((item) => String(item.name || "").toLowerCase()));
        const filtered = (Array.isArray(suggestions) ? suggestions : []).filter(
          (item) => !existing.has(String(item.name || "").toLowerCase())
        );
        setCategorySuggestions(filtered);
      } catch {
        if (active) {
          setCategorySuggestions([]);
        }
      }
    }, 180);

    return () => {
      active = false;
      window.clearTimeout(timeoutId);
    };
  }, [categoryQuery, categories, isEditMode, onSuggestCategories]);

  useEffect(() => {
    if (!image || !onListGalleries || !onLoadImageGalleries) {
      setGalleries([]);
      setSelectedGalleryIds([]);
      return;
    }

    let active = true;

    const load = async () => {
      try {
        setIsLoadingGalleries(true);
        setGalleriesNotice("");
        const [galleryItems, imageGalleryIds] = await Promise.all([
          onListGalleries(),
          onLoadImageGalleries(image.id)
        ]);

        if (!active) {
          return;
        }

        setGalleries(Array.isArray(galleryItems) ? galleryItems : []);
        setSelectedGalleryIds(Array.isArray(imageGalleryIds) ? imageGalleryIds : []);
      } catch (error) {
        if (!active) {
          return;
        }

        const message = error instanceof Error ? error.message : "Failed to load galleries.";
        setGalleriesNotice(message);
      } finally {
        if (active) {
          setIsLoadingGalleries(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [image, onListGalleries, onLoadImageGalleries]);

  if (!image) {
    return null;
  }

  const handleFieldChange = (field) => (event) => {
    const value = event.target.value;
    setFormState((current) => ({
      ...current,
      [field]: value
    }));
  };

  const persistCategories = async (nextCategories, successMessage = "Category saved.") => {
    if (!onAssignCategories || !image) {
      return;
    }

    try {
      setIsSavingCategories(true);
      setCategorySaveNotice("");
      const names = nextCategories.map((item) => String(item.name || "").trim()).filter((item) => item !== "");
      const assigned = await onAssignCategories(image.id, names);
      setCategories(Array.isArray(assigned) ? assigned : []);
      setCategorySaveNoticeType("success");
      setCategorySaveNotice(successMessage);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Category save failed.";
      setCategorySaveNoticeType("error");
      setCategorySaveNotice(message || "Category save failed.");
    } finally {
      setIsSavingCategories(false);
    }
  };

  const addCategoryName = async (name) => {
    const trimmed = String(name || "").trim();

    if (trimmed === "") {
      return;
    }

    const exists = categories.some((item) => String(item.name || "").toLowerCase() === trimmed.toLowerCase());

    if (exists) {
      setCategoryQuery("");
      setCategorySaveNoticeType("error");
      setCategorySaveNotice("Category already exists.");
      return;
    }

    const nextCategories = [
      ...categories,
      {
        id: 0,
        name: trimmed,
        slug: trimmed.toLowerCase().replace(/\s+/g, "-"),
        count: 0
      }
    ];

    setCategories(nextCategories);
    setCategoryQuery("");
    setCategorySuggestions([]);
    await persistCategories(nextCategories, "Category added.");
  };

  const handleCategoryKeyDown = async (event) => {
    if (event.key === "Enter") {
      event.preventDefault();
      await addCategoryName(categoryQuery);
    }
  };

  const removeCategory = async (name) => {
    const nextCategories = categories.filter(
      (item) => String(item.name || "").toLowerCase() !== String(name || "").toLowerCase()
    );
    setCategories(nextCategories);
    await persistCategories(nextCategories, "Category removed.");
  };

  const handleSuggestionClick = async (name) => {
    await addCategoryName(name);
  };

  const handleSave = async () => {
    if (!onSave) {
      return;
    }

    try {
      setIsSaving(true);
      setSaveError("");
      await onSave({
        attachment_id: image.id,
        ...formState
      });

      if (onSetImageGalleries) {
        await onSetImageGalleries(image.id, selectedGalleryIds);
      }
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to save metadata.";
      setSaveError(message);
    } finally {
      setIsSaving(false);
    }
  };

  const toggleGallerySelection = (galleryId) => {
    setSelectedGalleryIds((current) => {
      if (current.includes(galleryId)) {
        return current.filter((id) => id !== galleryId);
      }

      return [...current, galleryId];
    });
  };

  const saveGalleries = async () => {
    if (!onSetImageGalleries || !image) {
      return;
    }

    try {
      setIsSavingGalleries(true);
      setGalleriesNotice("");
      const ids = await onSetImageGalleries(image.id, selectedGalleryIds);
      setSelectedGalleryIds(Array.isArray(ids) ? ids : []);
      setGalleriesNotice("Galleries updated.");
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to update galleries.";
      setGalleriesNotice(message);
    } finally {
      setIsSavingGalleries(false);
    }
  };

  const categoryNoticeClassName =
    categorySaveNoticeType === "error"
      ? "rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
      : "rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700";

  const categoryActionDisabled = isSavingCategories || isSaving;
  const selectedGalleries = galleries.filter((gallery) => selectedGalleryIds.includes(gallery.id));
  const modalTitle = image.title || `Image #${image.id}`;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" role="dialog" aria-modal="true">
      <div className="max-h-[92vh] w-full max-w-6xl overflow-auto rounded-xl border border-slate-200 bg-white shadow-2xl">
        <div className="flex items-center justify-between border-b border-sky-100 bg-gradient-to-r from-sky-50/80 to-violet-50/60 px-4 py-3">
          <div className="min-w-0">
            <h2 className="truncate text-base font-semibold text-slate-900">{modalTitle}</h2>
            <p className="mt-0.5 text-xs text-slate-600">
              {isEditMode ? "Edit mode" : "Preview mode"}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <span className="hidden rounded-full bg-white px-2 py-1 text-[11px] font-medium text-slate-700 ring-1 ring-inset ring-sky-200 sm:inline-flex">
              #{image.id}
            </span>
            {isEditMode ? (
              <>
                <button
                  type="button"
                  onClick={onClose}
                  className="rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleSave}
                  disabled={isSaving}
                  className="rounded-md bg-sky-600 px-2.5 py-1.5 text-xs font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                >
                  {isSaving ? "Saving..." : "Save metadata"}
                </button>
              </>
            ) : null}
            <button
              type="button"
              onClick={onClose}
              className="rounded p-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
              aria-label="Close preview"
            >
              <X className="h-5 w-5" />
            </button>
          </div>
        </div>

        <div className="grid gap-0 md:grid-cols-[minmax(0,2.4fr)_minmax(320px,1fr)]">
          <div className="bg-slate-100 p-3">
            <img
              src={image.url}
              alt={image.title || `Tracked image ${image.id}`}
              className="max-h-[78vh] w-full rounded-lg bg-white object-contain ring-1 ring-inset ring-slate-200"
            />
          </div>

          <div className="p-4">
            <Tabs defaultValue="info" className="gap-3">
              <TabsList className="grid w-full grid-cols-4 rounded-lg border border-slate-200 bg-slate-50 p-1">
                <TabsTrigger value="info" className="rounded-md text-xs">Info</TabsTrigger>
                <TabsTrigger value="stats" className="rounded-md text-xs">Stats</TabsTrigger>
                <TabsTrigger value="categories" className="rounded-md text-xs">Categories</TabsTrigger>
                <TabsTrigger value="galleries" className="rounded-md text-xs">Galleries</TabsTrigger>
              </TabsList>

              <TabsContent value="info">
                {isEditMode ? (
                  <div className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                    <label className="block space-y-1">
                      <span className="inline-flex items-center rounded bg-violet-50 px-1.5 py-0.5 text-[11px] font-medium text-violet-700">
                        Title
                      </span>
                      <input
                        type="text"
                        value={formState.title}
                        onChange={handleFieldChange("title")}
                        className="w-full rounded-md border border-violet-200 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                      />
                    </label>
                    <label className="block space-y-1">
                      <span className="inline-flex items-center rounded bg-sky-50 px-1.5 py-0.5 text-[11px] font-medium text-sky-700">
                        Alt text
                      </span>
                      <input
                        type="text"
                        value={formState.alt_text}
                        onChange={handleFieldChange("alt_text")}
                        className="w-full rounded-md border border-sky-200 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                      />
                    </label>
                    <label className="block space-y-1">
                      <span className="inline-flex items-center rounded bg-emerald-50 px-1.5 py-0.5 text-[11px] font-medium text-emerald-700">
                        Caption
                      </span>
                      <input
                        type="text"
                        value={formState.caption}
                        onChange={handleFieldChange("caption")}
                        className="w-full rounded-md border border-emerald-200 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                      />
                    </label>
                    <label className="block space-y-1">
                      <span className="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700">
                        Description
                      </span>
                      <textarea
                        value={formState.description}
                        onChange={handleFieldChange("description")}
                        rows={4}
                        className="w-full rounded-md border border-amber-200 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                      />
                    </label>
                    {saveError !== "" ? <p className="text-sm text-red-600">{saveError}</p> : null}
                  </div>
                ) : (
                  <dl className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                    <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                      <dt className="text-slate-500">Title</dt>
                      <dd className="truncate font-medium text-slate-900">{image.title || "Untitled"}</dd>
                    </div>
                    <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                      <dt className="text-slate-500">Alt text</dt>
                      <dd className="truncate font-medium text-slate-900">{image.alt_text || "None"}</dd>
                    </div>
                    <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                      <dt className="text-slate-500">Uploaded by</dt>
                      <dd className="truncate font-medium text-slate-900">{image.uploaded_by || "Unknown"}</dd>
                    </div>
                    <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                      <dt className="text-slate-500">Uploaded at</dt>
                      <dd className="font-medium text-slate-900">{image.uploaded_at || "Unknown"}</dd>
                    </div>
                    <div className="space-y-1 pt-1">
                      <dt className="text-slate-500">URL</dt>
                      <dd className="break-all text-xs text-slate-700">{image.url}</dd>
                    </div>
                  </dl>
                )}
              </TabsContent>

              <TabsContent value="stats">
                <dl className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                  <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                    <dt className="text-slate-500">ID</dt>
                    <dd className="font-medium text-slate-900">{image.id}</dd>
                  </div>
                  <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                    <dt className="text-slate-500">Type</dt>
                    <dd className="font-medium text-slate-900">{image.mime_type || "Unknown"}</dd>
                  </div>
                  <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                    <dt className="text-slate-500">Dimensions</dt>
                    <dd className="font-medium text-slate-900">{formatDimensions(image.width, image.height)}</dd>
                  </div>
                  <div className="flex items-center justify-between gap-3 rounded-md bg-white px-2 py-1.5 ring-1 ring-inset ring-slate-200">
                    <dt className="text-slate-500">File size</dt>
                    <dd className="font-medium text-slate-900">{formatFileSize(image.file_size)}</dd>
                  </div>
                </dl>
              </TabsContent>

              <TabsContent value="categories">
                <div className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                  {isLoadingCategories ? (
                    <p className="text-sm text-slate-600">Loading categories...</p>
                  ) : isEditMode ? (
                    <>
                      <div className="flex flex-wrap gap-2">
                        {categories.length === 0 ? (
                          <span className="text-sm text-slate-500">No categories selected.</span>
                        ) : (
                          categories.map((category) => (
                            <span
                              key={`${category.slug}-${category.name}`}
                              className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800"
                            >
                              <span>{category.name}</span>
                              <button
                                type="button"
                                onClick={() => void removeCategory(category.name)}
                                disabled={categoryActionDisabled}
                                className="rounded p-0.5 text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 disabled:opacity-50"
                                aria-label={`Remove ${category.name}`}
                              >
                                <X className="h-3 w-3" />
                              </button>
                            </span>
                          ))
                        )}
                      </div>

                      <div className="space-y-2">
                        <input
                          type="text"
                          value={categoryQuery}
                          onChange={(event) => setCategoryQuery(event.target.value)}
                          onKeyDown={(event) => void handleCategoryKeyDown(event)}
                          placeholder="Type a category and press Enter"
                          disabled={categoryActionDisabled}
                          className="w-full rounded-md border border-amber-200 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 disabled:opacity-60"
                        />
                        {categorySuggestions.length > 0 ? (
                          <div className="flex flex-wrap gap-2">
                            {categorySuggestions.map((category) => (
                              <button
                                key={`${category.slug}-${category.id}`}
                                type="button"
                                onClick={() => void handleSuggestionClick(category.name)}
                                disabled={categoryActionDisabled}
                                className="rounded-md border border-amber-200 bg-white px-2.5 py-1 text-xs text-amber-700 transition hover:bg-amber-50 disabled:opacity-60"
                              >
                                {category.name}
                              </button>
                            ))}
                          </div>
                        ) : null}
                      </div>
                    </>
                  ) : (
                    <div className="space-y-2">
                      {categories.length === 0 ? (
                        <p className="text-sm text-slate-500">No categories assigned.</p>
                      ) : (
                        categories.map((category) => (
                          <div
                            key={`${category.slug}-${category.id}`}
                            className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2"
                          >
                            <span className="text-sm font-medium text-slate-900">{category.name}</span>
                            <span className="text-xs text-slate-600">{category.count} tracked images</span>
                          </div>
                        ))
                      )}
                    </div>
                  )}

                  {categoriesError !== "" ? <p className="text-sm text-red-600">{categoriesError}</p> : null}
                  {categorySaveNotice !== "" ? (
                    <div className={categoryNoticeClassName}>
                      {categorySaveNotice}
                    </div>
                  ) : null}
                </div>
              </TabsContent>

              <TabsContent value="galleries">
                <div className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                  {isLoadingGalleries ? (
                    <p className="text-sm text-slate-600">Loading galleries...</p>
                  ) : galleries.length === 0 ? (
                    <p className="text-sm text-slate-500">No galleries available yet.</p>
                  ) : isEditMode ? (
                    <div className="space-y-3">
                      <div className="max-h-52 space-y-2 overflow-auto rounded-md border border-slate-200 bg-white p-2">
                        {galleries.map((gallery) => (
                          <label key={gallery.id} className="flex items-center gap-2 rounded px-1 py-1 text-sm text-slate-800">
                            <input
                              type="checkbox"
                              checked={selectedGalleryIds.includes(gallery.id)}
                              onChange={() => toggleGallerySelection(gallery.id)}
                              className="h-4 w-4 rounded border-slate-300"
                            />
                            <span>{gallery.name}</span>
                          </label>
                        ))}
                      </div>
                      <button
                        type="button"
                        onClick={saveGalleries}
                        disabled={isSavingGalleries}
                        className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-emerald-700 disabled:opacity-70"
                      >
                        {isSavingGalleries ? "Saving..." : "Save galleries"}
                      </button>
                    </div>
                  ) : (
                    <div className="space-y-2">
                      {selectedGalleries.map((gallery) => (
                        <div
                          key={gallery.id}
                          className="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2"
                        >
                          <span className="text-sm font-medium text-slate-900">{gallery.name}</span>
                          <span className="text-xs text-slate-600">{gallery.image_count || 0} images</span>
                        </div>
                      ))}
                      {selectedGalleries.length === 0 ? (
                        <p className="text-sm text-slate-500">No galleries assigned.</p>
                      ) : null}
                    </div>
                  )}
                  {galleriesNotice !== "" ? <p className="mt-2 text-xs text-slate-700">{galleriesNotice}</p> : null}
                </div>
              </TabsContent>
            </Tabs>
          </div>
        </div>
      </div>

      <button
        type="button"
        onClick={onClose}
        className="absolute inset-0 -z-10"
        aria-label="Close preview"
      />
    </div>
  );
}
