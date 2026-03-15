import { Bot, LayoutTemplate } from "lucide-react";

import { proxIcon } from "@/core/brand-assets";

export function SettingsNavigation({
  description,
  activeSection,
  onSelectSection
}) {
  return (
    <aside className="rounded-[24px] border border-white/10 bg-slate-950/[0.74] p-4 shadow-[0_22px_70px_rgba(2,6,23,0.28)] backdrop-blur">
      <h1 className="inline-flex items-center gap-3 text-lg font-semibold text-white">
        <span className="inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl border border-white/12 bg-white/[0.08] p-1 shadow-[0_0_24px_rgba(249,115,22,0.16)]">
          <img src={proxIcon} alt="Prox icon" className="h-full w-full object-cover" />
        </span>
        Prox Gallery Settings
      </h1>
      <p className="mt-2 text-xs text-slate-300">{description}</p>
      <nav className="mt-4 space-y-2">
        <button
          type="button"
          onClick={() => onSelectSection("templates")}
          className={
            activeSection === "templates"
              ? "w-full rounded-2xl border border-orange-300/30 bg-[linear-gradient(135deg,rgba(249,115,22,0.22),rgba(250,204,21,0.12))] px-3 py-2 text-left text-sm font-medium text-orange-100"
              : "w-full rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white"
          }
        >
          <span className="inline-flex items-center gap-2">
            <LayoutTemplate className="h-4 w-4" />
            Templates
          </span>
        </button>
        <button
          type="button"
          onClick={() => onSelectSection("openai")}
          className={
            activeSection === "openai"
              ? "w-full rounded-2xl border border-orange-300/30 bg-[linear-gradient(135deg,rgba(249,115,22,0.22),rgba(250,204,21,0.12))] px-3 py-2 text-left text-sm font-medium text-orange-100"
              : "w-full rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-left text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white"
          }
        >
          <span className="inline-flex items-center gap-2">
            <Bot className="h-4 w-4" />
            OpenAI
          </span>
        </button>
      </nav>
    </aside>
  );
}
