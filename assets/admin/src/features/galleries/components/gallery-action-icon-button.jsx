import { useId } from "react";

export function GalleryActionIconButton({
  label,
  description,
  onClick,
  disabled,
  tone = "slate",
  children
}) {
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
