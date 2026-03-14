export function GalleryCreatePanel({
  isOpen,
  createName,
  createDescription,
  createTemplate,
  availableTemplates,
  createGridColumnsOverride,
  createLightboxOverride,
  createHoverZoomOverride,
  createFullWidthOverride,
  createTransitionOverride,
  onNameChange,
  onDescriptionChange,
  onTemplateChange,
  onGridColumnsOverrideChange,
  onLightboxOverrideChange,
  onHoverZoomOverrideChange,
  onFullWidthOverrideChange,
  onTransitionOverrideChange,
  onCreate,
  isCreating,
  isMutating
}) {
  if (!isOpen) {
    return null;
  }

  return (
    <div className="mb-4 space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3">
      <input
        type="text"
        value={createName}
        onChange={(event) => onNameChange(event.target.value)}
        placeholder="Gallery name"
        className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
      />
      <textarea
        value={createDescription}
        onChange={(event) => onDescriptionChange(event.target.value)}
        placeholder="Description (optional)"
        rows={3}
        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
      />
      <label className="space-y-1">
        <span className="text-xs font-medium text-slate-600">Template</span>
        <select
          value={createTemplate}
          onChange={(event) => onTemplateChange(event.target.value)}
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
            onChange={(event) => onGridColumnsOverrideChange(event.target.value)}
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
            onChange={(event) => onLightboxOverrideChange(event.target.value)}
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
            onChange={(event) => onHoverZoomOverrideChange(event.target.value)}
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
            onChange={(event) => onFullWidthOverrideChange(event.target.value)}
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
            onChange={(event) => onTransitionOverrideChange(event.target.value)}
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
        onClick={onCreate}
        disabled={isCreating || isMutating}
        className="inline-flex items-center gap-2 rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-70"
      >
        {isCreating ? "Creating..." : "Create gallery"}
      </button>
    </div>
  );
}
