import { useEffect, useMemo, useState } from "react";
import { Bot, LayoutTemplate, Settings2 } from "lucide-react";

import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";

const DEFAULT_SETTINGS = {
  basic_grid_columns: 4,
  basic_grid_lightbox: true,
  basic_grid_hover_zoom: true,
  basic_grid_full_width: false,
  basic_grid_transition: "none",
  masonry_columns: 4,
  masonry_lightbox: true,
  masonry_hover_zoom: true,
  masonry_full_width: false,
  masonry_transition: "none"
};

const TEMPLATE_SECTIONS = [
  {
    id: "basic_grid",
    label: "Basic Grid",
    columnsKey: "basic_grid_columns",
    hoverZoomKey: "basic_grid_hover_zoom",
    fullWidthKey: "basic_grid_full_width",
    transitionKey: "basic_grid_transition",
    lightboxKey: "basic_grid_lightbox"
  },
  {
    id: "masonry",
    label: "Masonry",
    columnsKey: "masonry_columns",
    hoverZoomKey: "masonry_hover_zoom",
    fullWidthKey: "masonry_full_width",
    transitionKey: "masonry_transition",
    lightboxKey: "masonry_lightbox"
  }
];

export function SettingsSection({ title, description, config, templateSettingsController }) {
  const [settings, setSettings] = useState(DEFAULT_SETTINGS);
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState("");
  const [activeSection, setActiveSection] = useState("templates");

  const suggestion = useMemo(() => {
    if (!settings.basic_grid_lightbox) {
      return "Suggestion: enable lightbox so visitors can view larger images without leaving the page.";
    }

    if (!settings.masonry_lightbox) {
      return "Suggestion: enable masonry lightbox so visitors can browse details without leaving the gallery.";
    }

    if (!settings.basic_grid_hover_zoom) {
      return "Suggestion: enable hover zoom for a smoother, more interactive gallery feel.";
    }

    if (settings.basic_grid_columns > 4 || settings.masonry_columns > 4) {
      return "Suggestion: 3-4 columns often look cleaner on most themes and keep images readable.";
    }

    return "Current setup looks balanced. You can tune columns per your theme width.";
  }, [
    settings.basic_grid_columns,
    settings.basic_grid_hover_zoom,
    settings.basic_grid_lightbox,
    settings.masonry_columns,
    settings.masonry_lightbox
  ]);

  useEffect(() => {
    let isMounted = true;

    const load = async () => {
      if (!templateSettingsController) {
        setMessage("Template settings action configuration is missing.");
        return;
      }

      setIsLoading(true);
      setMessage("");

      try {
        const response = await templateSettingsController.getSettings();

        if (!isMounted) {
          return;
        }

        setSettings({
          basic_grid_columns: Number(response.settings?.basic_grid_columns || 4),
          basic_grid_lightbox: Boolean(response.settings?.basic_grid_lightbox),
          basic_grid_hover_zoom: Boolean(response.settings?.basic_grid_hover_zoom),
          basic_grid_full_width: Boolean(response.settings?.basic_grid_full_width),
          basic_grid_transition:
            typeof response.settings?.basic_grid_transition === "string"
              ? response.settings.basic_grid_transition
              : "none",
          masonry_columns: Number(response.settings?.masonry_columns || 4),
          masonry_lightbox: Boolean(response.settings?.masonry_lightbox),
          masonry_hover_zoom: Boolean(response.settings?.masonry_hover_zoom),
          masonry_full_width: Boolean(response.settings?.masonry_full_width),
          masonry_transition:
            typeof response.settings?.masonry_transition === "string"
              ? response.settings.masonry_transition
              : "none"
        });
      } catch (error) {
        if (!isMounted) {
          return;
        }

        setMessage(error instanceof Error ? error.message : "Failed to load template settings.");
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    void load();

    return () => {
      isMounted = false;
    };
  }, [templateSettingsController]);

  const handleSave = async () => {
    if (!templateSettingsController) {
      setMessage("Template settings action configuration is missing.");
      return;
    }

    setIsSaving(true);
    setMessage("");

    try {
      const response = await templateSettingsController.updateSettings(settings);
      setSettings({
        basic_grid_columns: Number(response.settings?.basic_grid_columns || 4),
        basic_grid_lightbox: Boolean(response.settings?.basic_grid_lightbox),
        basic_grid_hover_zoom: Boolean(response.settings?.basic_grid_hover_zoom),
        basic_grid_full_width: Boolean(response.settings?.basic_grid_full_width),
        basic_grid_transition:
          typeof response.settings?.basic_grid_transition === "string"
            ? response.settings.basic_grid_transition
            : "none",
        masonry_columns: Number(response.settings?.masonry_columns || 4),
        masonry_lightbox: Boolean(response.settings?.masonry_lightbox),
        masonry_hover_zoom: Boolean(response.settings?.masonry_hover_zoom),
        masonry_full_width: Boolean(response.settings?.masonry_full_width),
        masonry_transition:
          typeof response.settings?.masonry_transition === "string"
            ? response.settings.masonry_transition
            : "none"
      });
      setMessage("Template settings saved.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Failed to save template settings.");
    } finally {
      setIsSaving(false);
    }
  };

  const updateSetting = (key, value) => {
    setSettings((current) => ({
      ...current,
      [key]: value
    }));
  };

  return (
    <>
      <div className="grid items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
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
              onClick={() => setActiveSection("templates")}
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
              disabled
              className="w-full cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm font-medium text-slate-500"
            >
              <span className="inline-flex items-center gap-2">
                <Bot className="h-4 w-4" />
                OpenAi Soon
              </span>
              <sup className="ml-0.5 align-super text-[9px] font-semibold tracking-wide">TM</sup>
            </button>
          </nav>
        </aside>

        {activeSection === "templates" ? (
          <Card>
            <CardHeader className="p-0">
              <SectionHeader
                framed={false}
                icon={LayoutTemplate}
                title="Template Customization"
                description="Control how each frontend template appears."
              />
            </CardHeader>
            <CardContent className="space-y-4">
              <section className="space-y-2">
                <h2 className="text-2xl font-semibold tracking-tight text-slate-900">Prox Gallery {title}</h2>
              </section>
              <div className="space-y-0">
                {TEMPLATE_SECTIONS.map((section, index) => (
                  <div key={section.id}>
                    {index > 0 ? <div className="h-12" aria-hidden="true" /> : null}
                    <fieldset className="rounded-xl border border-slate-200 bg-slate-50 p-8 pt-10 shadow-sm">
                      <legend className="ml-2 rounded-md bg-slate-900 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-white shadow-sm">
                        Template: {section.label}
                      </legend>
                      <div className="mt-3 grid gap-10 md:grid-cols-2">
                        <fieldset className="flex h-full flex-col rounded-lg border border-slate-200 bg-slate-50/70 p-8 pt-10 shadow-sm">
                          <legend className="ml-2 rounded-md bg-slate-900 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-white">
                            Grid settings
                          </legend>
                          <div className="h-full rounded-lg border border-slate-100 bg-slate-50/70 p-6">
                            <label className="space-y-2">
                              <span className="text-sm font-medium text-slate-700">Columns per row</span>
                              <select
                                value={settings[section.columnsKey]}
                                onChange={(event) => updateSetting(section.columnsKey, Number(event.target.value))}
                                disabled={isLoading || isSaving}
                                className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                              >
                                <option value={2}>2 columns</option>
                                <option value={3}>3 columns</option>
                                <option value={4}>4 columns</option>
                                <option value={5}>5 columns</option>
                                <option value={6}>6 columns</option>
                              </select>
                            </label>
                            <label className="mt-6 flex items-center justify-between gap-4 text-sm text-slate-700">
                              <span>Enable hover zoom effect</span>
                              <input
                                type="checkbox"
                                checked={settings[section.hoverZoomKey]}
                                onChange={(event) => updateSetting(section.hoverZoomKey, event.target.checked)}
                                disabled={isLoading || isSaving}
                                className="h-4 w-4 rounded border-slate-300"
                              />
                            </label>
                            <label className="mt-6 flex items-center justify-between gap-4 text-sm text-slate-700">
                              <span>Use full width layout</span>
                              <input
                                type="checkbox"
                                checked={settings[section.fullWidthKey]}
                                onChange={(event) => updateSetting(section.fullWidthKey, event.target.checked)}
                                disabled={isLoading || isSaving}
                                className="h-4 w-4 rounded border-slate-300"
                              />
                            </label>
                          </div>
                        </fieldset>

                        <fieldset className="flex h-full flex-col rounded-lg border border-slate-200 bg-slate-50/70 p-6 pt-8 shadow-sm">
                          <legend className="ml-2 rounded-md bg-slate-900 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-white">
                            Lightbox
                          </legend>
                          <div className="h-full rounded-lg border border-slate-100 bg-slate-50/70 p-6">
                            <label className="space-y-2">
                              <span className="text-sm font-medium text-slate-700">Transition</span>
                              <select
                                value={settings[section.transitionKey]}
                                onChange={(event) => updateSetting(section.transitionKey, event.target.value)}
                                disabled={isLoading || isSaving}
                                className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                              >
                                <option value="none">None</option>
                                <option value="slide">Slide</option>
                                <option value="fade">Fade</option>
                                <option value="explode">Explode</option>
                                <option value="implode">Implode</option>
                              </select>
                            </label>
                            <label className="mt-6 flex items-center justify-between gap-4 text-sm text-slate-700">
                              <span>Enable lightbox on image click</span>
                              <input
                                type="checkbox"
                                checked={settings[section.lightboxKey]}
                                onChange={(event) => updateSetting(section.lightboxKey, event.target.checked)}
                                disabled={isLoading || isSaving}
                                className="h-4 w-4 rounded border-slate-300"
                              />
                            </label>
                          </div>
                        </fieldset>
                      </div>
                    </fieldset>
                  </div>
                ))}
              </div>

              <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">{suggestion}</p>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleSave}
                  disabled={isLoading || isSaving}
                  className="rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-700 disabled:opacity-70"
                >
                  {isSaving ? "Saving..." : "Save template settings"}
                </button>
                {isLoading ? <span className="text-xs text-slate-600">Loading current settings...</span> : null}
              </div>

              {message !== "" ? <p className="text-sm text-slate-700">{message}</p> : null}

              <details className="rounded-md border border-slate-200 bg-white p-3">
                <summary className="cursor-pointer text-xs font-medium text-slate-600">Debug config payload</summary>
                <pre className="mt-3 overflow-auto rounded bg-slate-950 p-3 text-xs text-slate-100">
                  {JSON.stringify(config, null, 2)}
                </pre>
              </details>
            </CardContent>
          </Card>
        ) : null}
      </div>
    </>
  );
}
