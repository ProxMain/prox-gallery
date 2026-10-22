const LAYOUTS = [
  [
    "basic_grid",
    "Basic grid",
    "A consistent rhythm. Every image has its place.",
  ],
  ["masonry", "Masonry", "Natural proportions, an effortless flow."],
];

export function TemplateSettingsPanel({ value, update, disabled }) {
  return (
    <div className="pg-settings-layouts">
      {LAYOUTS.map(([prefix, label, description]) => (
        <fieldset key={prefix} disabled={disabled} className="pg-settings-card">
          <legend>{label}</legend>
          <p className="pg-muted">{description}</p>
          <div
            className={`pg-layout-preview ${prefix === "masonry" ? "pg-layout-masonry" : ""}`}
            aria-hidden="true"
          >
            {[1, 2, 3, 4, 5, 6].map((key) => (
              <span key={key} />
            ))}
          </div>
          <label className="pg-field">
            Columns
            <select
              value={value[`${prefix}_columns`]}
              onChange={(event) =>
                update(`${prefix}_columns`, Number(event.target.value))
              }
            >
              {[2, 3, 4, 5, 6].map((count) => (
                <option key={count} value={count}>
                  {count} columns
                </option>
              ))}
            </select>
          </label>
          <label className="pg-field">
            Lightbox transition
            <select
              value={value[`${prefix}_transition`]}
              onChange={(event) =>
                update(`${prefix}_transition`, event.target.value)
              }
            >
              {["none", "fade", "slide", "explode", "implode"].map(
                (transition) => (
                  <option key={transition}>{transition}</option>
                ),
              )}
            </select>
          </label>
          <div className="pg-checks">
            {[
              ["lightbox", "Open images in a lightbox"],
              ["hover_zoom", "Zoom gently on hover"],
              ["full_width", "Use the full page width"],
            ].map(([key, title]) => (
              <label key={key}>
                <input
                  type="checkbox"
                  checked={value[`${prefix}_${key}`]}
                  onChange={(event) =>
                    update(`${prefix}_${key}`, event.target.checked)
                  }
                />
                {title}
              </label>
            ))}
          </div>
        </fieldset>
      ))}
    </div>
  );
}
