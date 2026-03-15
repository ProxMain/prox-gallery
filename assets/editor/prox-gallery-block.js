(function (wp, config) {
  if (!wp?.blocks || !wp?.element || !wp?.components || !wp?.blockEditor) {
    return;
  }

  const { registerBlockType } = wp.blocks;
  const { createElement: el, Fragment } = wp.element;
  const { SelectControl, Placeholder } = wp.components;
  const { useBlockProps } = wp.blockEditor;
  const galleries = Array.isArray(config?.galleries) ? config.galleries : [];

  registerBlockType("prox-gallery/gallery", {
    apiVersion: 2,
    title: "Prox Gallery",
    description: "Select and render one of your Prox Gallery galleries.",
    icon: "format-gallery",
    category: "widgets",
    attributes: {
      id: {
        type: "number",
        default: 0
      }
    },
    edit: function Edit(props) {
      const { attributes, setAttributes } = props;
      const selectedId = Number(attributes?.id ?? 0);
      const selectedGallery = galleries.find((gallery) => Number(gallery.id) === selectedId) || null;
      const options = [
        { label: "Select a gallery", value: 0 },
        ...galleries.map((gallery) => ({
          label: gallery.name,
          value: Number(gallery.id)
        }))
      ];

      return el(
        "div",
        useBlockProps(),
        el(
          Placeholder,
          {
            icon: "format-gallery",
            label: "Prox Gallery",
            instructions: "Choose which gallery to render in this block."
          },
          el(
            Fragment,
            null,
            el(SelectControl, {
              label: "Gallery",
              value: selectedId,
              options: options,
              onChange: function onChange(value) {
                setAttributes({ id: Number(value) || 0 });
              }
            }),
            selectedGallery
              ? el(
                  "p",
                  {
                    style: {
                      marginTop: "12px",
                      marginBottom: 0
                    }
                  },
                  "Selected gallery: ",
                  el("strong", null, selectedGallery.name)
                )
              : null
          )
        )
      );
    },
    save: function save() {
      return null;
    }
  });
})(window.wp, window.ProxGalleryBlockEditor);
