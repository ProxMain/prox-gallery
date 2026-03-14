import { Check, ChevronLeft, ChevronRight, Sparkles } from "lucide-react";

const STEPS = [
  { key: "basics", label: "Basics" },
  { key: "template", label: "Template" },
  { key: "layout", label: "Layout" },
  { key: "effects", label: "Effects" },
  { key: "content", label: "Content" },
  { key: "review", label: "Review" }
];

export function GalleryCreationWizard({
  isOpen,
  stepIndex,
  value,
  availableTemplates,
  isSubmitting,
  message,
  onClose,
  onStepChange,
  onValueChange,
  onSubmit
}) {
  if (!isOpen) {
    return null;
  }

  const activeStep = STEPS[stepIndex] ?? STEPS[0];
  const selectedTemplate = availableTemplates.find((template) => template.slug === value.template) ?? null;
  const canGoBack = stepIndex > 0;
  const canGoForward = stepIndex < STEPS.length - 1;
  const canSubmit = value.name.trim() !== "";

  const handleNext = () => {
    if (activeStep.key === "basics" && value.name.trim() === "") {
      return;
    }

    onStepChange(Math.min(STEPS.length - 1, stepIndex + 1));
  };

  const renderStep = () => {
    if (activeStep.key === "basics") {
      return (
        <div className="grid gap-4">
          <label className="space-y-1">
            <span className="text-sm font-medium text-slate-700">Gallery name</span>
            <input
              type="text"
              value={value.name}
              onChange={(event) => onValueChange("name", event.target.value)}
              placeholder="Summer campaign selects"
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
          </label>
          <label className="space-y-1">
            <span className="text-sm font-medium text-slate-700">Description</span>
            <textarea
              value={value.description}
              onChange={(event) => onValueChange("description", event.target.value)}
              placeholder="Optional context for collaborators."
              rows={4}
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
          </label>
        </div>
      );
    }

    if (activeStep.key === "template") {
      return (
        <div className="grid gap-3 md:grid-cols-2">
          {availableTemplates.map((template) => {
            const isSelected = template.slug === value.template;

            return (
              <button
                key={template.slug}
                type="button"
                onClick={() => onValueChange("template", template.slug)}
                className={[
                  "rounded-xl border px-4 py-4 text-left transition",
                  isSelected
                    ? "border-sky-500 bg-sky-50 ring-2 ring-sky-200"
                    : "border-slate-200 bg-white hover:border-slate-300"
                ].join(" ")}
              >
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <p className="text-sm font-semibold text-slate-900">{template.label}</p>
                    <p className="mt-1 text-xs text-slate-600">
                      {template.slug === "masonry"
                        ? "Loose masonry flow with strong visual rhythm."
                        : "Structured grid for consistent presentation."}
                    </p>
                  </div>
                  {isSelected ? <Check className="h-5 w-5 text-sky-600" /> : null}
                </div>
                <div className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                  {template.slug === "masonry" ? <MasonryPreview /> : <BasicGridPreview />}
                </div>
              </button>
            );
          })}
        </div>
      );
    }

    if (activeStep.key === "layout") {
      return (
        <div className="space-y-5">
          <div>
            <p className="text-sm font-medium text-slate-800">Choose how images are arranged on the page.</p>
            <p className="mt-1 text-sm text-slate-600">
              Columns control how many images sit next to each other before wrapping to the next row.
            </p>
          </div>
          <WizardChoiceGrid
            label="Columns"
            description="More columns make smaller thumbnails. Fewer columns give each image more space."
            value={value.grid_columns_override}
            onChange={(nextValue) => onValueChange("grid_columns_override", nextValue)}
            options={[
              { value: "inherit", label: "Inherit", preview: <ColumnPreview columns={4} muted /> },
              { value: "2", label: "2 columns", preview: <ColumnPreview columns={2} /> },
              { value: "3", label: "3 columns", preview: <ColumnPreview columns={3} /> },
              { value: "4", label: "4 columns", preview: <ColumnPreview columns={4} /> },
              { value: "5", label: "5 columns", preview: <ColumnPreview columns={5} /> },
              { value: "6", label: "6 columns", preview: <ColumnPreview columns={6} /> }
            ]}
          />
          <WizardChoiceGrid
            label="Page width"
            description="Full width stretches the gallery wider across the page. Use it when the gallery should feel cinematic."
            value={value.full_width_override}
            onChange={(nextValue) => onValueChange("full_width_override", nextValue)}
            options={[
              { value: "inherit", label: "Inherit", preview: <WidthPreview mode="inherit" /> },
              { value: "on", label: "Full width", preview: <WidthPreview mode="wide" /> },
              { value: "off", label: "Contained", preview: <WidthPreview mode="contained" /> }
            ]}
          />
        </div>
      );
    }

    if (activeStep.key === "effects") {
      return (
        <div className="space-y-5">
          <div>
            <p className="text-sm font-medium text-slate-800">Choose how the gallery behaves when people interact with it.</p>
            <p className="mt-1 text-sm text-slate-600">
              These settings control click behavior, motion, and how dynamic the gallery feels.
            </p>
          </div>
          <WizardChoiceGrid
            label="Lightbox"
            description="A lightbox opens the image in a larger overlay when someone clicks it."
            value={value.lightbox_override}
            onChange={(nextValue) => onValueChange("lightbox_override", nextValue)}
            options={[
              { value: "inherit", label: "Inherit", preview: <LightboxPreview mode="inherit" /> },
              { value: "on", label: "Enable", preview: <LightboxPreview mode="on" /> },
              { value: "off", label: "Disable", preview: <LightboxPreview mode="off" /> }
            ]}
          />
          <WizardChoiceGrid
            label="Hover zoom"
            description="Hover zoom adds a subtle scale effect when the cursor moves over an image."
            value={value.hover_zoom_override}
            onChange={(nextValue) => onValueChange("hover_zoom_override", nextValue)}
            options={[
              { value: "inherit", label: "Inherit", preview: <HoverPreview mode="inherit" /> },
              { value: "on", label: "Enable", preview: <HoverPreview mode="on" /> },
              { value: "off", label: "Disable", preview: <HoverPreview mode="off" /> }
            ]}
          />
          <WizardChoiceGrid
            label="Transition"
            description="Transitions define how the lightbox feels when moving between images."
            value={value.transition_override}
            onChange={(nextValue) => onValueChange("transition_override", nextValue)}
            options={[
              { value: "inherit", label: "Inherit", preview: <TransitionPreview mode="inherit" /> },
              { value: "none", label: "None", preview: <TransitionPreview mode="none" /> },
              { value: "slide", label: "Slide", preview: <TransitionPreview mode="slide" /> },
              { value: "fade", label: "Fade", preview: <TransitionPreview mode="fade" /> },
              { value: "explode", label: "Explode", preview: <TransitionPreview mode="explode" /> },
              { value: "implode", label: "Implode", preview: <TransitionPreview mode="implode" /> }
            ]}
          />
        </div>
      );
    }

    if (activeStep.key === "content") {
      return (
        <div className="grid gap-3">
          <WizardToggleCard
            title="Show gallery title"
            description="Keep the gallery title visible on the frontend."
            checked={value.show_title}
            onChange={(checked) => onValueChange("show_title", checked)}
          />
          <WizardToggleCard
            title="Show gallery description"
            description="Display the description above the gallery images."
            checked={value.show_description}
            onChange={(checked) => onValueChange("show_description", checked)}
          />
        </div>
      );
    }

    return (
      <div className="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div className="flex items-center gap-2 text-slate-900">
          <Sparkles className="h-5 w-5 text-sky-600" />
          <p className="text-sm font-semibold">Review your gallery setup</p>
        </div>
        <ReviewRow label="Name" value={value.name || "Untitled"} />
        <ReviewRow label="Description" value={value.description || "None"} />
        <ReviewRow label="Template" value={selectedTemplate?.label ?? value.template} />
        <ReviewRow label="Columns" value={formatOverride(value.grid_columns_override)} />
        <ReviewRow label="Full width" value={formatOverride(value.full_width_override, true)} />
        <ReviewRow label="Lightbox" value={formatOverride(value.lightbox_override, true)} />
        <ReviewRow label="Hover zoom" value={formatOverride(value.hover_zoom_override, true)} />
        <ReviewRow label="Transition" value={formatOverride(value.transition_override)} />
        <ReviewRow label="Show title" value={value.show_title ? "On" : "Off"} />
        <ReviewRow label="Show description" value={value.show_description ? "On" : "Off"} />
      </div>
    );
  };

  return (
    <div className="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/40 p-4 md:items-center">
      <div className="w-full max-w-6xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div className="border-b border-slate-200 bg-gradient-to-r from-sky-50/90 to-violet-50/70 px-5 py-4">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Gallery wizard</p>
              <h3 className="mt-1 text-xl font-semibold text-slate-900">Build a gallery step by step</h3>
              <p className="mt-1 text-sm text-slate-600">Choose template and display behavior before creating the gallery.</p>
            </div>
            <button
              type="button"
              onClick={onClose}
              className="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-100"
            >
              Close
            </button>
          </div>
          <div className="mt-4 grid gap-2 md:grid-cols-6">
            {STEPS.map((step, index) => (
              <button
                key={step.key}
                type="button"
                onClick={() => {
                  if (index <= stepIndex) {
                    onStepChange(index);
                  }
                }}
                className={[
                  "rounded-lg border px-3 py-2 text-left text-xs transition whitespace-nowrap",
                  index === stepIndex
                    ? "border-sky-500 bg-white text-sky-700 shadow-sm"
                    : index < stepIndex
                    ? "border-slate-200 bg-white text-slate-700"
                    : "border-transparent bg-white/50 text-slate-400"
                ].join(" ")}
              >
                <span className="block font-semibold">{index + 1}. {step.label}</span>
              </button>
            ))}
          </div>
        </div>
        <div className="px-5 py-5">
          {renderStep()}
          {message !== "" ? <p className="mt-4 text-sm text-slate-700">{message}</p> : null}
        </div>
        <div className="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4">
          <button
            type="button"
            onClick={() => onStepChange(Math.max(0, stepIndex - 1))}
            disabled={!canGoBack || isSubmitting}
            className="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:opacity-50"
          >
            <ChevronLeft className="h-4 w-4" />
            Back
          </button>
          {canGoForward ? (
            <button
              type="button"
              onClick={handleNext}
              disabled={isSubmitting || (activeStep.key === "basics" && value.name.trim() === "")}
              className="inline-flex items-center gap-2 rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700 disabled:opacity-50"
            >
              Next
              <ChevronRight className="h-4 w-4" />
            </button>
          ) : (
            <button
              type="button"
              onClick={onSubmit}
              disabled={!canSubmit || isSubmitting}
              className="inline-flex items-center gap-2 rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700 disabled:opacity-50"
            >
              {isSubmitting ? "Creating..." : "Create gallery"}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

function WizardSelect({ label, value, onChange, options }) {
  return (
    <label className="space-y-1">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <select
        value={value}
        onChange={(event) => onChange(event.target.value)}
        className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </label>
  );
}

function WizardChoiceGrid({ label, description, value, onChange, options }) {
  return (
    <div className="space-y-2">
      <div>
        <p className="text-sm font-medium text-slate-800">{label}</p>
        <p className="mt-1 text-sm text-slate-600">{description}</p>
      </div>
      <div className="grid gap-3 md:grid-cols-3">
        {options.map((option) => {
          const isSelected = option.value === value;

          return (
            <button
              key={option.value}
              type="button"
              onClick={() => onChange(option.value)}
              className={[
                "rounded-xl border p-3 text-left transition",
                isSelected
                  ? "border-sky-500 bg-sky-50 ring-2 ring-sky-200"
                  : "border-slate-200 bg-white hover:border-slate-300"
              ].join(" ")}
            >
              <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                {option.preview}
              </div>
              <p className="mt-3 text-sm font-semibold text-slate-900">{option.label}</p>
            </button>
          );
        })}
      </div>
    </div>
  );
}

function WizardToggleCard({ title, description, checked, onChange }) {
  return (
    <button
      type="button"
      onClick={() => onChange(!checked)}
      className={[
        "flex items-start justify-between gap-4 rounded-xl border px-4 py-4 text-left transition",
        checked ? "border-sky-500 bg-sky-50" : "border-slate-200 bg-white hover:border-slate-300"
      ].join(" ")}
    >
      <div>
        <p className="text-sm font-semibold text-slate-900">{title}</p>
        <p className="mt-1 text-sm text-slate-600">{description}</p>
      </div>
      <span className={[
        "inline-flex min-w-[54px] items-center justify-center rounded-full px-3 py-1 text-xs font-semibold",
        checked ? "bg-sky-600 text-white" : "bg-slate-200 text-slate-700"
      ].join(" ")}>
        {checked ? "On" : "Off"}
      </span>
    </button>
  );
}

function ReviewRow({ label, value }) {
  return (
    <div className="flex items-center justify-between gap-4 border-b border-slate-200 pb-2 text-sm last:border-b-0 last:pb-0">
      <span className="font-medium text-slate-600">{label}</span>
      <span className="text-right text-slate-900">{value}</span>
    </div>
  );
}

function formatOverride(value, isBoolean = false) {
  if (value === "inherit") {
    return "Inherit global";
  }

  if (isBoolean) {
    return value === "on" ? "Force on" : "Force off";
  }

  return String(value);
}

function BasicGridPreview() {
  return (
    <div className="grid grid-cols-3 gap-2">
      {["h-12", "h-12", "h-12", "h-12", "h-12", "h-12"].map((heightClass, index) => (
        <div
          key={`grid-${index}`}
          className={`${heightClass} rounded-md bg-gradient-to-br from-sky-200 to-sky-100 ring-1 ring-inset ring-sky-300`}
        />
      ))}
    </div>
  );
}

function MasonryPreview() {
  return (
    <div className="grid grid-cols-3 gap-2">
      {["h-10", "h-16", "h-12", "h-16", "h-9", "h-14"].map((heightClass, index) => (
        <div
          key={`masonry-${index}`}
          className={`${heightClass} rounded-md bg-gradient-to-br from-violet-200 to-fuchsia-100 ring-1 ring-inset ring-violet-300`}
        />
      ))}
    </div>
  );
}

function ColumnPreview({ columns, muted = false }) {
  return (
    <div
      className="grid gap-1.5"
      style={{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }}
    >
      {Array.from({ length: columns * 2 }).map((_, index) => (
        <div
          key={`column-${columns}-${index}`}
          className={[
            "h-7 rounded-sm ring-1 ring-inset",
            muted
              ? "bg-slate-200 ring-slate-300"
              : "bg-gradient-to-br from-sky-200 to-sky-100 ring-sky-300"
          ].join(" ")}
        />
      ))}
    </div>
  );
}

function WidthPreview({ mode }) {
  const innerClass =
    mode === "wide"
      ? "w-full"
      : mode === "contained"
      ? "w-3/4 mx-auto"
      : "w-5/6 mx-auto opacity-80";

  return (
    <div className="rounded-md bg-slate-200 p-2">
      <div className={`${innerClass} grid grid-cols-4 gap-1 rounded-md bg-white p-2`}>
        {Array.from({ length: 4 }).map((_, index) => (
          <div key={`width-${mode}-${index}`} className="h-6 rounded-sm bg-slate-300" />
        ))}
      </div>
    </div>
  );
}

function LightboxPreview({ mode }) {
  return (
    <div className="space-y-2">
      <div className="grid grid-cols-3 gap-1.5">
        {Array.from({ length: 3 }).map((_, index) => (
          <div key={`lightbox-${mode}-${index}`} className="h-8 rounded-sm bg-slate-300" />
        ))}
      </div>
      <div className={[
        "rounded-md px-2 py-1 text-center text-xs font-medium",
        mode === "on"
          ? "bg-slate-900 text-white"
          : mode === "off"
          ? "bg-slate-200 text-slate-600"
          : "bg-sky-100 text-sky-700"
      ].join(" ")}>
        {mode === "on" ? "Opens large overlay" : mode === "off" ? "Stays inline" : "Use global setting"}
      </div>
    </div>
  );
}

function HoverPreview({ mode }) {
  return (
    <div className="space-y-2">
      <div className="grid grid-cols-2 gap-2">
        <div className="rounded-md border border-slate-200 bg-white p-2">
          <div className="h-12 rounded-md bg-gradient-to-br from-violet-200 to-fuchsia-100 ring-1 ring-inset ring-violet-300" />
          <p className="mt-1 text-[10px] font-medium text-slate-500">Default</p>
        </div>
        <div className="rounded-md border border-slate-200 bg-white p-2">
          <div
            className={[
              "h-12 rounded-md bg-gradient-to-br from-violet-200 to-fuchsia-100 ring-1 ring-inset ring-violet-300 transition",
              mode === "on" ? "scale-105 shadow-lg" : mode === "off" ? "" : "scale-[1.02] opacity-85"
            ].join(" ")}
          />
          <p className="mt-1 text-[10px] font-medium text-slate-500">
            {mode === "on" ? "Hover state" : mode === "off" ? "No change" : "Global behavior"}
          </p>
        </div>
      </div>
      <p className="text-xs text-slate-500">
        {mode === "on"
          ? "Images subtly grow on hover to feel more interactive."
          : mode === "off"
          ? "Images stay visually steady when hovered."
          : "Uses the hover behavior from your global template settings."}
      </p>
    </div>
  );
}

function TransitionPreview({ mode }) {
  const label =
    mode === "inherit"
      ? "Global"
      : mode === "none"
      ? "Static"
      : mode === "slide"
      ? "Slides"
      : mode === "fade"
      ? "Fades"
      : mode === "explode"
      ? "Bursts"
      : "Pulls in";

  return (
    <div className="space-y-2">
      <div className="rounded-md border border-slate-200 bg-white p-2">
        <div className="relative flex h-14 items-center justify-center overflow-hidden rounded-md bg-slate-100">
          <div className="absolute left-2 top-1/2 h-8 w-8 -translate-y-1/2 rounded-sm bg-slate-300" />
          <div
            className={[
              "absolute top-1/2 h-8 w-8 -translate-y-1/2 rounded-sm bg-sky-300 transition",
              mode === "slide"
                ? "left-5"
                : mode === "fade"
                ? "left-1/2 -translate-x-1/2 opacity-55"
                : mode === "explode"
                ? "left-1/2 -translate-x-1/2 scale-125"
                : mode === "implode"
                ? "left-1/2 -translate-x-1/2 scale-75"
                : mode === "inherit"
                ? "left-1/2 -translate-x-1/2 bg-sky-200"
                : "left-1/2 -translate-x-1/2"
            ].join(" ")}
          />
        </div>
      </div>
      <p className="text-xs font-medium text-slate-600">{label}</p>
      <p className="text-xs text-slate-500">
        {mode === "slide"
          ? "The next image appears to move horizontally into place."
          : mode === "fade"
          ? "The next image softly fades in."
          : mode === "explode"
          ? "The image arrives with a stronger scaled burst."
          : mode === "implode"
          ? "The image pulls inward for a tighter motion."
          : mode === "none"
          ? "Images switch immediately with no extra motion."
          : "Uses the transition defined in your global template settings."}
      </p>
    </div>
  );
}
