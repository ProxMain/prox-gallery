import { cn } from "@/lib/utils";
import { proxIcon } from "@/core/brand-assets";

export function SectionHeader({
  title,
  description = "",
  icon: Icon,
  actions = null,
  meta = null,
  footer = null,
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
            ? "rounded-[28px] border border-white/10 bg-slate-950/75 shadow-[0_26px_80px_rgba(2,6,23,0.32)] backdrop-blur"
            : "rounded-2xl border border-white/10 bg-slate-950/70 backdrop-blur"
          : "bg-transparent",
        className
      )}
    >
      <div className="border-b border-white/8 bg-[linear-gradient(135deg,rgba(251,146,60,0.14),rgba(250,204,21,0.08),rgba(15,23,42,0.1))] px-4 py-3">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="inline-flex items-center gap-2">
              {Icon ? (
                <span className="relative inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white/[0.08] shadow-[0_0_24px_rgba(249,115,22,0.12)]">
                  <img src={proxIcon} alt="" className="absolute inset-0 h-full w-full object-cover opacity-30" aria-hidden="true" />
                  <span className="absolute inset-0 bg-slate-950/40" aria-hidden="true" />
                  <Icon className="relative h-4 w-4 text-orange-100" />
                </span>
              ) : null}
              <h2 className={cn("truncate text-white", isPage ? "text-2xl font-semibold tracking-tight" : "text-lg font-semibold")}>
                {title}
              </h2>
            </div>
            {description !== "" ? (
              <p className="mt-1 text-sm text-slate-300">{description}</p>
            ) : null}
            {meta ? <div className="mt-1">{meta}</div> : null}
          </div>
          {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
        </div>
        {footer ? <div className="mt-3">{footer}</div> : null}
      </div>
    </header>
  );
}
