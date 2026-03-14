import { Bot, LayoutTemplate, Settings2 } from "lucide-react";

export function SettingsNavigation({
  description,
  activeSection,
  onSelectSection
}) {
  return (
    <aside className="rounded-xl border border-slate-200 bg-white p-4">
      <h1 className="inline-flex items-center gap-2 text-lg font-semibold text-slate-900">
        <span className="inline-flex h-7 w-7 items-center justify-center rounded-md bg-white ring-1 ring-inset ring-sky-200">
          <Settings2 className="h-4 w-4 text-violet-700" />
        </span>
        Prox Gallery Settings
      </h1>
      <p className="mt-1 text-xs text-slate-600">{description}</p>
      <nav className="mt-4 space-y-2">
        <button
          type="button"
          onClick={() => onSelectSection("templates")}
          className={
            activeSection === "templates"
              ? "w-full rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-left text-sm font-medium text-sky-800"
              : "w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50"
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
              ? "w-full rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-left text-sm font-medium text-sky-800"
              : "w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50"
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
