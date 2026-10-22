import { useEffect, useState } from "react";

/** Local draft ownership and persistence lifecycle for one settings endpoint. */
export function useSettingsEditor(controller, active, defaults) {
  const [value, setValue] = useState(defaults);
  const [loaded, setLoaded] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState(null);
  useEffect(() => {
    if (!active || loaded) return;
    if (!controller) {
      setNotice({
        error: true,
        message: "Settings are unavailable for this account.",
      });
      return;
    }
    let current = true;
    setLoading(true);
    controller
      .getSettings()
      .then((response) => {
        if (current) {
          setValue({ ...defaults, ...response.settings });
          setLoaded(true);
          setNotice(null);
        }
      })
      .catch((error) => {
        if (current) setNotice({ error: true, message: error.message });
      })
      .finally(() => {
        if (current) setLoading(false);
      });
    return () => {
      current = false;
    };
  }, [controller, active, loaded, defaults]);

  const update = (key, next) => {
    setValue((draft) => ({ ...draft, [key]: next }));
    setNotice(null);
  };
  const save = async () => {
    if (!controller || !loaded || saving) return;
    setSaving(true);
    setNotice(null);
    try {
      const response = await controller.updateSettings(value);
      setValue({ ...defaults, ...response.settings });
      setNotice({ error: false, message: "Changes saved." });
    } catch (error) {
      setNotice({
        error: true,
        message: error.message || "Could not save settings.",
      });
    } finally {
      setSaving(false);
    }
  };
  return {
    value,
    update,
    save,
    notice,
    busy: loading || saving,
    loading,
    saving,
    loaded,
  };
}
