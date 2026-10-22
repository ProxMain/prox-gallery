import { useEffect, useState } from "react";

export function useMediaOrganisation({
  image,
  onLoadCategories,
  onListGalleries,
  onLoadImageGalleries,
  onAssignCategories,
  onSetImageGalleries,
}) {
  const [categories, setCategories] = useState("");
  const [galleries, setGalleries] = useState([]);
  const [selected, setSelected] = useState([]);
  const [loaded, setLoaded] = useState(false);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState(null);
  useEffect(() => {
    let active = true;
    Promise.all([
      onLoadCategories(image.id),
      onListGalleries(),
      onLoadImageGalleries(image.id),
    ])
      .then(([terms, rows, ids]) => {
        if (!active) return;
        setCategories(terms.map((term) => term.name).join(", "));
        setGalleries(rows);
        setSelected(ids);
        setLoaded(true);
      })
      .catch((error) => {
        if (active)
          setNotice({
            error: true,
            message: error.message || "Could not load image organisation.",
          });
      });
    return () => {
      active = false;
    };
  }, [image.id, onLoadCategories, onListGalleries, onLoadImageGalleries]);

  const save = async (operation, message) => {
    if (busy || !loaded) return;
    setBusy(true);
    setNotice(null);
    try {
      await operation();
      setNotice({ message, error: false });
    } catch (error) {
      setNotice({
        message: error.message || "Could not save changes.",
        error: true,
      });
    } finally {
      setBusy(false);
    }
  };
  return {
    categories,
    setCategories,
    galleries,
    selected,
    loaded,
    busy,
    notice,
    toggleGallery: (id) =>
      setSelected((ids) =>
        ids.includes(id) ? ids.filter((item) => item !== id) : [...ids, id],
      ),
    saveCategories: () =>
      save(
        () =>
          onAssignCategories(
            image.id,
            categories
              .split(",")
              .map((name) => name.trim())
              .filter(Boolean),
          ),
        "Categories saved.",
      ),
    saveGalleries: () =>
      save(
        () => onSetImageGalleries(image.id, selected),
        "Gallery membership saved.",
      ),
  };
}
