// Development-only fixture adapter. Never imported by the production entrypoint.
export function createPreviewBackend() {
  const photo = (id) =>
    `https://images.unsplash.com/${id}?auto=format&fit=crop&w=1100&q=80`;
  const photos = [
    "photo-1472396961693-142e6e269027",
    "photo-1470770841072-f978cf4d019e",
    "photo-1469474968028-56623f02e42e",
    "photo-1441974231531-c6227db76b6e",
    "photo-1500530855697-b586d89ba3ee",
    "photo-1447752875215-b2761acb3c5d",
  ];
  const names = [
    "Into the wild",
    "Still waters",
    "Higher ground",
    "Forest light",
    "The long way home",
    "Between the trees",
  ];
  const images = photos.map((url, index) => ({
    id: index + 1,
    title: names[index],
    url: photo(url),
    thumbnail_url: photo(url),
    view_url: photo(url),
    edit_url: "",
    delete_url: "",
    mime_type: "image/jpeg",
    uploaded_at: `2026-10-${20 - index}T12:00:00Z`,
    uploaded_by: "Studio",
    width: 2400,
    height: 1600,
    file_size: 920000,
    alt_text: "",
    caption: "",
    description: "",
    categories: [{ id: 1, name: "Nature", slug: "nature" }],
  }));
  let galleries = [
    {
      id: 1,
      name: "Wilderness, quietly",
      description: "Small moments in the great outdoors.",
      image_ids: [1, 4, 6],
      template: "masonry",
    },
    {
      id: 2,
      name: "Where the water rests",
      description: "A collection of reflections and slower days.",
      image_ids: [2, 5],
      template: "basic-grid",
    },
    {
      id: 3,
      name: "Above it all",
      description: "A different view from a little higher up.",
      image_ids: [3],
      template: "masonry",
    },
    {
      id: 4,
      name: "The way back",
      description: "Places worth returning to.",
      image_ids: [5, 2],
      template: "basic-grid",
    },
    {
      id: 5,
      name: "In good company",
      description: "Among the trees, away from the noise.",
      image_ids: [6, 4],
      template: "masonry",
    },
  ].map((item) => ({
    ...item,
    created_at: `2026-10-${22 - item.id}T12:00:00Z`,
    grid_columns_override: null,
    lightbox_override: null,
    hover_zoom_override: null,
    full_width_override: null,
    transition_override: null,
    show_title: true,
    show_description: true,
  }));
  const templates = [
    { slug: "basic-grid", label: "Basic grid", available: true },
    { slug: "masonry", label: "Masonry", available: true },
  ];
  const definitions = {
    galleries: [
      "list",
      "create",
      "rename",
      "delete",
      "list_image_galleries",
      "set_image_galleries",
      "add_images",
      "set_images",
      "create_page",
    ],
    media_manager: ["list", "track_selected", "update"],
    media_category: ["suggest", "list", "assign"],
    template_settings: ["get", "update"],
    tracking: ["get"],
    openai: [
      "settings_get",
      "settings_update",
      "config_get",
      "generate",
      "apply",
    ],
  };
  const config = {
    screen: "preview",
    ajax_url: "/__preview_ajax__",
    action_controllers: Object.fromEntries(
      Object.entries(definitions).map(([module, keys]) => [
        module,
        Object.fromEntries(
          keys.map((key) => [
            key,
            { action: `${module}.${key}`, nonce: "preview" },
          ]),
        ),
      ]),
    ),
  };
  config.action_controllers.galleries.templates = templates;
  let templateSettings = {
    basic_grid_columns: 4,
    basic_grid_lightbox: true,
    basic_grid_hover_zoom: true,
    basic_grid_full_width: false,
    basic_grid_transition: "fade",
    masonry_columns: 3,
    masonry_lightbox: true,
    masonry_hover_zoom: false,
    masonry_full_width: false,
    masonry_transition: "fade",
  };
  let aiSettings = {
    api_key: "",
    api_key_configured: false,
    model: "gpt-4.1-mini",
    languages: ["English", "Dutch"],
    prompt_templates: [
      {
        key: "factual",
        label: "Factual",
        prompt: "Describe the visible details in this image.",
        built_in: true,
      },
    ],
  };
  const galleryRows = () =>
    galleries.map((gallery) => ({
      ...gallery,
      image_count: gallery.image_ids.length,
      cover_images: gallery.image_ids
        .map((id) => images.find((image) => image.id === id)?.url)
        .filter(Boolean),
    }));
  const calls = [];
  const request = async (_url, options) => {
    const payload = Object.fromEntries(new URLSearchParams(options.body));
    const { action } = payload;
    calls.push(action);
    let data = { action };
    const ids = (raw = "") => raw.split(",").filter(Boolean).map(Number);
    const attachment = () =>
      images.find((image) => image.id === Number(payload.attachment_id));
    switch (action) {
      case "galleries.list":
        data.items = galleryRows();
        break;
      case "galleries.create": {
        const item = {
          id: Math.max(0, ...galleries.map((gallery) => gallery.id)) + 1,
          ...payload,
          image_ids: [],
          created_at: new Date().toISOString(),
        };
        item.id = Number(item.id);
        galleries.push(item);
        data.item = item;
        break;
      }
      case "galleries.rename": {
        const item = galleries.find(
          (gallery) => gallery.id === Number(payload.id),
        );
        Object.assign(item, payload, { id: Number(payload.id) });
        data.item = item;
        break;
      }
      case "galleries.delete":
        galleries = galleries.filter(
          (gallery) => gallery.id !== Number(payload.id),
        );
        break;
      case "galleries.set_images": {
        const gallery = galleries.find(
          (item) => item.id === Number(payload.gallery_id),
        );
        gallery.image_ids = ids(payload.image_ids);
        break;
      }
      case "galleries.list_image_galleries":
        data.gallery_ids = galleries
          .filter((gallery) =>
            gallery.image_ids.includes(Number(payload.image_id)),
          )
          .map((gallery) => gallery.id);
        break;
      case "galleries.set_image_galleries":
        galleries.forEach((gallery) => {
          gallery.image_ids = gallery.image_ids.filter(
            (id) => id !== Number(payload.image_id),
          );
          if (ids(payload.gallery_ids).includes(gallery.id))
            gallery.image_ids.push(Number(payload.image_id));
        });
        data.gallery_ids = ids(payload.gallery_ids);
        break;
      case "galleries.create_page":
        return Response.json(
          {
            success: false,
            data: {
              message:
                "Publishing is available in WordPress. This preview never publishes.",
            },
          },
          { status: 400 },
        );
      case "media_manager.list":
        data.items = images;
        break;
      case "media_manager.update":
        Object.assign(attachment(), payload);
        data.item = attachment();
        break;
      case "media_category.list":
        data.items = attachment()?.categories ?? [];
        break;
      case "media_category.suggest":
        data.items = [{ id: 1, name: "Nature", slug: "nature" }];
        break;
      case "media_category.assign":
        attachment().categories = (payload.categories || "")
          .split(",")
          .filter(Boolean)
          .map((name, index) => ({
            id: index + 1,
            name,
            slug: name.toLowerCase(),
          }));
        data.items = attachment().categories;
        break;
      case "template_settings.get":
        data.settings = templateSettings;
        break;
      case "template_settings.update":
        templateSettings = Object.fromEntries(
          Object.entries(payload)
            .filter(([key]) => key !== "action" && key !== "_ajax_nonce")
            .map(([key, value]) => [
              key,
              value === "true"
                ? true
                : value === "false"
                  ? false
                  : key.endsWith("columns")
                    ? Number(value)
                    : value,
            ]),
        );
        data.settings = templateSettings;
        break;
      case "openai.settings_get":
        data.settings = aiSettings;
        break;
      case "openai.settings_update":
        aiSettings = {
          ...aiSettings,
          api_key: "",
          api_key_configured:
            Boolean(payload.api_key) || aiSettings.api_key_configured,
          model: payload.model,
          languages: payload.languages_csv.split(","),
          prompt_templates: JSON.parse(payload.prompt_templates_json),
        };
        data.settings = aiSettings;
        break;
      case "openai.config_get":
        data.config = aiSettings;
        break;
      case "openai.generate":
      case "openai.apply":
        return Response.json(
          {
            success: false,
            data: {
              message:
                "AI requests are disabled in the design preview. No image has been sent.",
            },
          },
          { status: 400 },
        );
      case "tracking.get":
        data.summary = {
          totals: { gallery_views: 1284, image_views: 4326 },
          countries: {
            NL: 620,
            DE: 280,
            GB: 120,
            US: 95,
            FR: 62,
            BE: 40,
            DK: 25,
            SE: 20,
            IT: 12,
            ZZ: 10,
          },
          galleries: galleryRows().map((item, index) => ({
            gallery_id: item.id,
            name: item.name,
            total: 620 - index * 95,
          })),
          images: images.map((image, index) => ({
            image_id: image.id,
            title: image.title,
            total: 860 - index * 70,
          })),
          updated_at: "2026-10-22T09:20:00Z",
        };
        break;
      default:
        return Response.json(
          {
            success: false,
            data: { message: "This action needs a WordPress installation." },
          },
          { status: 400 },
        );
    }
    return Response.json({ success: true, data });
  };
  return { config, request, calls };
}

export function installPreviewBackend() {
  const backend = createPreviewBackend();
  window.ProxGalleryAdminConfig = backend.config;
  const original = window.fetch.bind(window);
  window.fetch = (url, options) =>
    String(url) === backend.config.ajax_url
      ? backend.request(url, options)
      : original(url, options);
  return backend;
}
