import { cn } from "@/lib/utils";

export function SectionHeader({
  title,
  description = "",
  icon: Icon,
  actions = null,
  meta = null,
  footer = null,
  variant = "card",
  framed = true,
  className = "",
}) {
  const isPage = variant === "page";

  return (
    <header
      className={cn(isPage ? "pg-page-header" : "pg-section-header", className)}
    >
      <div>
        <div className="pg-heading-row">
          <div className="min-w-0">
            <div className="inline-flex items-center gap-2">
              {Icon && !isPage ? <Icon size={18} aria-hidden="true" /> : null}
              {isPage ? (
                <h1 className="pg-page-title">{title}</h1>
              ) : (
                <h2 className="pg-section-title">{title}</h2>
              )}
            </div>
            {description !== "" ? (
              <p className="mt-1 text-sm text-slate-600">{description}</p>
            ) : null}
            {meta ? <div className="mt-1">{meta}</div> : null}
          </div>
          {actions ? <div className="pg-heading-actions">{actions}</div> : null}
        </div>
        {footer ? <div className="mt-3">{footer}</div> : null}
      </div>
    </header>
  );
}
