import { cn } from "@/lib/utils";

export function SectionHeader({
  title,
  description = "",
  icon: Icon,
  actions = null,
  meta = null,
  variant = "card",
  framed = true,
  className = ""
}) {
  const isPage = variant === "page";

  return (
    <header
      className={cn(
        framed
          ? isPage
            ? "rounded-2xl border border-slate-200 bg-white shadow-sm"
            : "rounded-lg border border-slate-200 bg-white"
          : "bg-white",
        className
      )}
    >
      <div className="border-b border-sky-100 bg-gradient-to-r from-sky-50/80 to-violet-50/60 px-4 py-2.5">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="inline-flex items-center gap-2">
              {Icon ? (
                <span className="inline-flex h-7 w-7 items-center justify-center rounded-md bg-white ring-1 ring-inset ring-sky-200">
                  <Icon className="h-4 w-4 text-violet-700" />
                </span>
              ) : null}
              <h2 className={cn("truncate text-slate-900", isPage ? "text-2xl font-semibold tracking-tight" : "text-lg font-semibold")}>
                {title}
              </h2>
            </div>
            {description !== "" ? (
              <p className="mt-1 text-sm text-slate-600">{description}</p>
            ) : null}
            {meta ? <div className="mt-1">{meta}</div> : null}
          </div>
          {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
        </div>
      </div>
    </header>
  );
}
