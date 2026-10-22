import { useEffect, useState } from "react";
import { Sparkles } from "lucide-react";
import { Button } from "@/components/ui/button";

export function ImageAiPanel({ image, controller, onApplied }) {
  const [config, setConfig] = useState(null);
  const [template, setTemplate] = useState("");
  const [language, setLanguage] = useState("");
  const [prompt, setPrompt] = useState("");
  const [preview, setPreview] = useState(null);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState(null);
  useEffect(() => {
    if (!controller) return;
    let active = true;
    controller
      .getGenerationConfig()
      .then(({ config: value }) => {
        if (!active) return;
        setConfig(value);
        setTemplate(value.prompt_templates[0]?.key ?? "");
        setPrompt(value.prompt_templates[0]?.prompt ?? "");
        setLanguage(value.languages[0] ?? "English");
      })
      .catch((error) => {
        if (active) setNotice({ error: true, message: error.message });
      });
    return () => {
      active = false;
    };
  }, [controller]);
  const generate = async () => {
    if (busy || !config) return;
    setBusy(true);
    setNotice(null);
    setPreview(null);
    try {
      setPreview(
        await controller.generateStory({
          attachment_id: image.id,
          template_key: template,
          language,
          prompt_override: prompt,
        }),
      );
    } catch (error) {
      setNotice({
        error: true,
        message: error.message || "Generation failed.",
      });
    } finally {
      try {
        const response = await controller.getGenerationConfig();
        setConfig(response.config);
      } catch {
        // Preserve the generation result or error if refreshing usage fails.
      }
      setBusy(false);
    }
  };
  const apply = async () => {
    if (busy || !preview) return;
    setBusy(true);
    setNotice(null);
    try {
      const response = await controller.applyStory({
        attachment_id: image.id,
        story: preview.story,
        short_title: preview.short_title,
      });
      await onApplied(response);
      setNotice({ error: false, message: "Title and description applied." });
    } catch (error) {
      setNotice({
        error: true,
        message: error.message || "Could not apply the result.",
      });
    } finally {
      setBusy(false);
    }
  };
  if (!controller)
    return (
      <p className="pg-muted">AI generation is unavailable for this account.</p>
    );
  return (
    <div className="space-y-5">
      <p className="pg-muted">
        Generate sends this image and your prompt to OpenAI. API charges may
        apply. Nothing changes until you apply the result.
      </p>
      {notice ? (
        <p
          className={`pg-notice ${notice.error ? "pg-error" : ""}`}
          role={notice.error ? "alert" : "status"}
        >
          {notice.message}
        </p>
      ) : null}
      {!config ? (
        !notice ? (
          <p role="status">Loading AI configuration…</p>
        ) : null
      ) : (
        <>
          {config.usage ? (
            <p className="pg-muted" role="status">
              Today: {config.usage.user_remaining} of {config.usage.user_limit} attempts left for you;
              {" "}{config.usage.site_remaining} of {config.usage.site_limit} for this site.
              Resets at 00:00 UTC.
            </p>
          ) : null}
          <div className="pg-form-grid">
            <label className="pg-field">
              Template
              <select
                value={template}
                disabled={busy}
                onChange={(event) => {
                  setTemplate(event.target.value);
                  setPrompt(
                    config.prompt_templates.find(
                      (item) => item.key === event.target.value,
                    )?.prompt ?? "",
                  );
                }}
              >
                {config.prompt_templates.map((item) => (
                  <option key={item.key} value={item.key}>
                    {item.label}
                  </option>
                ))}
              </select>
            </label>
            <label className="pg-field">
              Language
              <select
                value={language}
                disabled={busy}
                onChange={(event) => setLanguage(event.target.value)}
              >
                {config.languages.map((item) => (
                  <option key={item}>{item}</option>
                ))}
              </select>
            </label>
          </div>
          <label className="pg-field">
            Instructions
            <textarea
              rows={3}
              value={prompt}
              disabled={busy}
              onChange={(event) => setPrompt(event.target.value)}
            />
          </label>
          <Button onClick={generate} disabled={busy || !template || (config.usage && (config.usage.user_remaining === 0 || config.usage.site_remaining === 0))}>
            <Sparkles size={15} />
            {busy ? "Working…" : "Generate preview"}
          </Button>
          {preview ? (
            <div className="pg-ai-preview space-y-4">
              <p className="pg-muted">Review and edit before applying.</p>
              <label className="pg-field">
                Short title (up to 4 words)
                <input
                  value={preview.short_title}
                  onChange={(event) =>
                    setPreview({ ...preview, short_title: event.target.value })
                  }
                />
              </label>
              <label className="pg-field">
                Description
                <textarea
                  rows={5}
                  value={preview.story}
                  onChange={(event) =>
                    setPreview({ ...preview, story: event.target.value })
                  }
                />
              </label>
              <Button
                onClick={apply}
                disabled={
                  busy || !preview.story.trim() || !preview.short_title.trim()
                }
              >
                Apply to this image
              </Button>
            </div>
          ) : null}
        </>
      )}
    </div>
  );
}
