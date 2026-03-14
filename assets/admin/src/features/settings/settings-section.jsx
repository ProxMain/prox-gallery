import { useEffect, useMemo, useState } from "react";
import { Bot, LayoutTemplate, Plus, Settings2, X } from "lucide-react";

import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";
import {
  useOpenAiActionController,
  useTemplateSettingsActionController
} from "@/lib/action-controller-hooks";

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

const DEFAULT_OPENAI_SETTINGS = {
  api_key: "",
  model: "gpt-4.1-mini",
  languages: ["English", "German", "Dutch", "French"],
  prompt_templates: []
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

const DEFAULT_LANGUAGE_CHOICES = ["English", "German", "Dutch", "French"];

function normalizeOpenAiSettings(settings) {
  if (!settings || typeof settings !== "object") {
    return DEFAULT_OPENAI_SETTINGS;
  }

  const languages = Array.isArray(settings.languages)
    ? settings.languages
        .map((item) => String(item || "").trim())
        .filter((item) => item !== "")
    : DEFAULT_OPENAI_SETTINGS.languages;

  const promptTemplates = Array.isArray(settings.prompt_templates)
    ? settings.prompt_templates
        .map((template) => {
          const key = String(template?.key || "").trim();
          const label = String(template?.label || "").trim();
          const prompt = String(template?.prompt || "").trim();

          if (key === "" || label === "" || prompt === "") {
            return null;
          }

          return {
            key,
            label,
            prompt,
            built_in: Boolean(template?.built_in)
          };
        })
        .filter(Boolean)
    : [];

  return {
    api_key: String(settings.api_key || ""),
    model: String(settings.model || DEFAULT_OPENAI_SETTINGS.model),
    languages: languages.length > 0 ? languages : DEFAULT_OPENAI_SETTINGS.languages,
    prompt_templates: promptTemplates
  };
}

export function SettingsSection({ title, description, config, isActive }) {
  const templateSettingsController = useTemplateSettingsActionController(config);
  const openAiController = useOpenAiActionController(config);
  const [settings, setSettings] = useState(DEFAULT_SETTINGS);
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState("");
  const [activeSection, setActiveSection] = useState("templates");

  const [openAiSettings, setOpenAiSettings] = useState(DEFAULT_OPENAI_SETTINGS);
  const [openAiLanguageInput, setOpenAiLanguageInput] = useState("");
  const [openAiCustomTemplate, setOpenAiCustomTemplate] = useState({ key: "", label: "", prompt: "" });
  const [isOpenAiLoading, setIsOpenAiLoading] = useState(false);
  const [isOpenAiSaving, setIsOpenAiSaving] = useState(false);
  const [openAiMessage, setOpenAiMessage] = useState("");

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
    if (!isActive) {
      return;
    }

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
  }, [isActive, templateSettingsController]);

  useEffect(() => {
    if (!isActive) {
      return;
    }

    let isMounted = true;

    const load = async () => {
      if (!openAiController) {
        setOpenAiMessage("OpenAI action configuration is missing.");
        return;
      }

      setIsOpenAiLoading(true);
      setOpenAiMessage("");

      try {
        const response = await openAiController.getSettings();

        if (!isMounted) {
          return;
        }

        setOpenAiSettings(normalizeOpenAiSettings(response.settings));
      } catch (error) {
        if (!isMounted) {
          return;
        }

        setOpenAiMessage(error instanceof Error ? error.message : "Failed to load OpenAI settings.");
      } finally {
        if (isMounted) {
          setIsOpenAiLoading(false);
        }
      }
    };

    void load();

    return () => {
      isMounted = false;
    };
  }, [isActive, openAiController]);

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

  const handleSaveOpenAi = async () => {
    if (!openAiController) {
      setOpenAiMessage("OpenAI action configuration is missing.");
      return;
    }

    setIsOpenAiSaving(true);
    setOpenAiMessage("");

    try {
      const response = await openAiController.updateSettings(openAiSettings);
      setOpenAiSettings(normalizeOpenAiSettings(response.settings));
      setOpenAiMessage("OpenAI settings saved.");
    } catch (error) {
      setOpenAiMessage(error instanceof Error ? error.message : "Failed to save OpenAI settings.");
    } finally {
      setIsOpenAiSaving(false);
    }
  };

  const updateSetting = (key, value) => {
    setSettings((current) => ({
      ...current,
      [key]: value
    }));
  };

  const updateOpenAiSetting = (key, value) => {
    setOpenAiSettings((current) => ({
      ...current,
      [key]: value
    }));
  };

  const updatePromptTemplate = (key, field, value) => {
    setOpenAiSettings((current) => ({
      ...current,
      prompt_templates: current.prompt_templates.map((template) =>
        template.key === key
          ? {
              ...template,
              [field]: value
            }
          : template
      )
    }));
  };

  const removeCustomPromptTemplate = (key) => {
    setOpenAiSettings((current) => ({
      ...current,
      prompt_templates: current.prompt_templates.filter((template) => template.key !== key || template.built_in)
    }));
  };

  const addLanguage = (name) => {
    const normalizedName = String(name || "").trim();

    if (normalizedName === "") {
      return;
    }

    const exists = openAiSettings.languages.some(
      (language) => language.toLowerCase() === normalizedName.toLowerCase()
    );

    if (exists) {
      setOpenAiLanguageInput("");
      return;
    }

    updateOpenAiSetting("languages", [...openAiSettings.languages, normalizedName]);
    setOpenAiLanguageInput("");
  };

  const removeLanguage = (name) => {
    const nextLanguages = openAiSettings.languages.filter(
      (language) => language.toLowerCase() !== String(name).toLowerCase()
    );

    if (nextLanguages.length === 0) {
      return;
    }

    updateOpenAiSetting("languages", nextLanguages);
  };

  const addCustomPromptTemplate = () => {
    const key = String(openAiCustomTemplate.key || "")
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9_\-]/g, "-");
    const label = String(openAiCustomTemplate.label || "").trim();
    const prompt = String(openAiCustomTemplate.prompt || "").trim();

    if (key === "" || label === "" || prompt === "") {
      setOpenAiMessage("Custom template key, label and prompt are required.");
      return;
    }

    const exists = openAiSettings.prompt_templates.some((template) => template.key === key);

    if (exists) {
      setOpenAiMessage(`Template key \"${key}\" already exists.`);
      return;
    }

    updateOpenAiSetting("prompt_templates", [
      ...openAiSettings.prompt_templates,
      {
        key,
        label,
        prompt,
        built_in: false
      }
    ]);

    setOpenAiCustomTemplate({ key: "", label: "", prompt: "" });
    setOpenAiMessage("");
  };

  const openAiCard = (
    <Card>
      <CardHeader className="p-0">
        <SectionHeader
          framed={false}
          icon={Bot}
          title="OpenAI"
          description="Configure AI image story generation and prompt templates."
        />
      </CardHeader>
      <CardContent className="space-y-4">
        <section className="space-y-2">
          <h2 className="text-2xl font-semibold tracking-tight text-slate-900">OpenAI Story Generation</h2>
        </section>

        <fieldset className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Connection</legend>
          <label className="space-y-1 text-sm">
            <span className="font-medium text-slate-700">API key</span>
            <input
              type="password"
              value={openAiSettings.api_key}
              onChange={(event) => updateOpenAiSetting("api_key", event.target.value)}
              disabled={isOpenAiLoading || isOpenAiSaving}
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
          </label>
          <label className="space-y-1 text-sm">
            <span className="font-medium text-slate-700">Model</span>
            <input
              type="text"
              value={openAiSettings.model}
              onChange={(event) => updateOpenAiSetting("model", event.target.value)}
              disabled={isOpenAiLoading || isOpenAiSaving}
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
          </label>
        </fieldset>

        <fieldset className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Languages</legend>
          <div className="flex flex-wrap gap-2">
            {openAiSettings.languages.map((language) => (
              <span
                key={language}
                className="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-800 ring-1 ring-inset ring-slate-200"
              >
                {language}
                <button
                  type="button"
                  onClick={() => removeLanguage(language)}
                  disabled={isOpenAiLoading || isOpenAiSaving}
                  className="rounded p-0.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                  aria-label={`Remove ${language}`}
                >
                  <X className="h-3 w-3" />
                </button>
              </span>
            ))}
          </div>

          <div className="flex flex-wrap gap-2">
            {DEFAULT_LANGUAGE_CHOICES.map((language) => (
              <button
                key={language}
                type="button"
                onClick={() => addLanguage(language)}
                disabled={isOpenAiLoading || isOpenAiSaving}
                className="rounded-md border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-700 transition hover:bg-slate-50"
              >
                {language}
              </button>
            ))}
          </div>

          <div className="flex gap-2">
            <input
              type="text"
              value={openAiLanguageInput}
              onChange={(event) => setOpenAiLanguageInput(event.target.value)}
              onKeyDown={(event) => {
                if (event.key === "Enter" || event.key === ",") {
                  event.preventDefault();
                  addLanguage(openAiLanguageInput);
                }
              }}
              placeholder="Add custom language"
              disabled={isOpenAiLoading || isOpenAiSaving}
              className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
            <button
              type="button"
              onClick={() => addLanguage(openAiLanguageInput)}
              disabled={isOpenAiLoading || isOpenAiSaving}
              className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              Add
            </button>
          </div>
        </fieldset>

        <fieldset className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <legend className="px-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Prompt templates</legend>

          <div className="space-y-3">
            {openAiSettings.prompt_templates.map((template) => (
              <div key={template.key} className="rounded-md border border-slate-200 bg-white p-3">
                <div className="mb-2 flex items-center justify-between gap-2">
                  <div className="text-xs text-slate-500">
                    <span className="font-semibold text-slate-700">{template.label}</span>
                    <span className="ml-2 rounded bg-slate-100 px-1.5 py-0.5">{template.key}</span>
                    {template.built_in ? (
                      <span className="ml-2 rounded bg-sky-100 px-1.5 py-0.5 text-sky-700">built-in</span>
                    ) : null}
                  </div>
                  {!template.built_in ? (
                    <button
                      type="button"
                      onClick={() => removeCustomPromptTemplate(template.key)}
                      disabled={isOpenAiLoading || isOpenAiSaving}
                      className="rounded-md border border-red-200 px-2 py-1 text-xs font-medium text-red-700 transition hover:bg-red-50"
                    >
                      Remove
                    </button>
                  ) : null}
                </div>

                <label className="space-y-1 text-sm">
                  <span className="font-medium text-slate-700">Label</span>
                  <input
                    type="text"
                    value={template.label}
                    onChange={(event) => updatePromptTemplate(template.key, "label", event.target.value)}
                    disabled={isOpenAiLoading || isOpenAiSaving}
                    className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                  />
                </label>

                <label className="mt-2 block space-y-1 text-sm">
                  <span className="font-medium text-slate-700">Prompt</span>
                  <textarea
                    value={template.prompt}
                    onChange={(event) => updatePromptTemplate(template.key, "prompt", event.target.value)}
                    rows={3}
                    disabled={isOpenAiLoading || isOpenAiSaving}
                    className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                  />
                </label>
              </div>
            ))}
          </div>

          <div className="rounded-md border border-dashed border-slate-300 bg-white p-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Add custom template</p>
            <div className="mt-2 grid gap-2 md:grid-cols-2">
              <input
                type="text"
                value={openAiCustomTemplate.key}
                onChange={(event) =>
                  setOpenAiCustomTemplate((current) => ({
                    ...current,
                    key: event.target.value
                  }))
                }
                placeholder="Template key"
                disabled={isOpenAiLoading || isOpenAiSaving}
                className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
              />
              <input
                type="text"
                value={openAiCustomTemplate.label}
                onChange={(event) =>
                  setOpenAiCustomTemplate((current) => ({
                    ...current,
                    label: event.target.value
                  }))
                }
                placeholder="Template label"
                disabled={isOpenAiLoading || isOpenAiSaving}
                className="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
              />
            </div>
            <textarea
              value={openAiCustomTemplate.prompt}
              onChange={(event) =>
                setOpenAiCustomTemplate((current) => ({
                  ...current,
                  prompt: event.target.value
                }))
              }
              rows={3}
              placeholder="Prompt text"
              disabled={isOpenAiLoading || isOpenAiSaving}
              className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            />
            <button
              type="button"
              onClick={addCustomPromptTemplate}
              disabled={isOpenAiLoading || isOpenAiSaving}
              className="mt-2 inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
              <Plus className="h-4 w-4" />
              Add template
            </button>
          </div>
        </fieldset>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={handleSaveOpenAi}
            disabled={isOpenAiLoading || isOpenAiSaving}
            className="rounded-md bg-sky-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-sky-700 disabled:opacity-70"
          >
            {isOpenAiSaving ? "Saving..." : "Save OpenAI settings"}
          </button>
          {isOpenAiLoading ? <span className="text-xs text-slate-600">Loading current settings...</span> : null}
        </div>

        {openAiMessage !== "" ? <p className="text-sm text-slate-700">{openAiMessage}</p> : null}
      </CardContent>
    </Card>
  );

  return (
    <div className={isActive ? "" : "hidden"}>
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
              onClick={() => setActiveSection("openai")}
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

        {activeSection === "openai" ? openAiCard : null}
      </div>
    </div>
  );
}
