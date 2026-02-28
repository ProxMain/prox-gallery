import {
  ChevronRight,
  Database,
  FolderOpen,
  Images,
  Settings2,
  ShieldCheck,
  Upload,
  WandSparkles
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { getAdminConfig } from "@/lib/admin-config";
import {
  ADMIN_MENU_ITEMS,
  DASHBOARD_STATS,
  sectionDescription,
  sectionTitle
} from "@/lib/admin-logic";
import { MediaManagerActionController } from "@/lib/media-manager-action-controller";

const STAT_ICONS = {
  images: Images,
  upload: Upload,
  "shield-check": ShieldCheck,
  "folder-open": FolderOpen
};

export function App() {
  const [activeMenu, setActiveMenu] = useState("dashboard");
  const [viewMode, setViewMode] = useState("thumbnail");
  const [trackedImages, setTrackedImages] = useState([]);
  const [isLoadingList, setIsLoadingList] = useState(false);
  const [listError, setListError] = useState("");
  const config = getAdminConfig();
  const title = sectionTitle(activeMenu);
  const description = sectionDescription(activeMenu);

  const mediaManagerController = useMemo(() => {
    const listDefinition = config.action_controllers?.media_manager?.list ?? (
      {
        action: "prox_gallery_media_manager_list",
        nonce: config.rest_nonce || ""
      }
    );

    if (config.ajax_url === "") {
      return null;
    }

    return new MediaManagerActionController(
      { ajax_url: config.ajax_url },
      { list: listDefinition }
    );
  }, [
    config.ajax_url,
    config.rest_nonce,
    config.action_controllers?.media_manager?.list?.action,
    config.action_controllers?.media_manager?.list?.nonce
  ]);

  useEffect(() => {
    if (activeMenu !== "media-manager" || !mediaManagerController || trackedImages.length > 0) {
      return;
    }

    const load = async () => {
      try {
        setIsLoadingList(true);
        setListError("");
        const response = await mediaManagerController.listTrackedImages();
        setTrackedImages(response.items);
      } catch (error) {
        const message = error instanceof Error ? error.message : "Failed to load tracked images.";
        setListError(message);
      } finally {
        setIsLoadingList(false);
      }
    };

    void load();
  }, [activeMenu, mediaManagerController, trackedImages.length]);

  const handleReloadTrackedImages = async () => {
    if (!mediaManagerController) {
      setListError("Media manager action configuration is missing.");
      return;
    }

    try {
      setIsLoadingList(true);
      setListError("");
      const trackedResponse = await mediaManagerController.listTrackedImages();
      setTrackedImages(trackedResponse.items);
    } catch (error) {
      const message = error instanceof Error ? error.message : "Failed to load tracked images.";
      setListError(message);
    } finally {
      setIsLoadingList(false);
    }
  };

  return (
    <main className="prox-gallery-admin mx-auto max-w-[1100px] space-y-8 py-8">
      <section className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
        <div className="flex flex-wrap items-center gap-2">
          {ADMIN_MENU_ITEMS.map((menuItem) => (
            <Button
              key={menuItem.key}
              variant={activeMenu === menuItem.key ? "default" : "ghost"}
              size="sm"
              onClick={() => setActiveMenu(menuItem.key)}
              className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
            >
              {menuItem.label}
            </Button>
          ))}
        </div>
      </section>

      {activeMenu === "dashboard" ? (
        <>
          <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {DASHBOARD_STATS.map((item) => {
              const Icon = STAT_ICONS[item.icon];

              return (
                <Card key={item.label}>
                  <CardHeader className="pb-3">
                    <CardDescription>{item.label}</CardDescription>
                    <CardTitle className="flex items-center justify-between text-2xl">
                      {item.value}
                      <Icon className="h-5 w-5 text-slate-500" />
                    </CardTitle>
                  </CardHeader>
                </Card>
              );
            })}
          </section>
          <Card>
            <CardHeader>
              <CardTitle>Dashboard Placeholder</CardTitle>
              <CardDescription>This area is reserved for future dashboard widgets and summaries.</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-slate-600">Add your dashboard content here later.</p>
            </CardContent>
          </Card>
        </>
      ) : activeMenu === "media-manager" ? (
        <section className="space-y-6">
          <header className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
              <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery → Media Manager</h1>
              <p className="mt-2 text-sm text-slate-600">Manage your tracked media library.</p>
            </div>
            <div className="flex flex-wrap items-center gap-2 md:justify-end">
              <Button
                asChild
                variant="outline"
                className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <a href="/wp-admin/upload.php">Upload</a>
              </Button>
              <Badge variant="outline" className="h-9 px-3 text-xs font-medium text-slate-700">
                Environment: Local · Screen: {config.screen || "n/a"}
              </Badge>
            </div>
          </header>

          <Card>
            <CardHeader className="flex-row items-center justify-between space-y-0 pb-4">
              <div>
                <CardTitle className="text-lg">Actions</CardTitle>
                <CardDescription>Quick tools for media operations.</CardDescription>
              </div>
              <ChevronRight className="h-4 w-4 text-slate-500" aria-hidden="true" />
            </CardHeader>
            <CardContent className="space-y-3">
              <button
                type="button"
                onClick={handleReloadTrackedImages}
                className="group flex w-full items-start gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <span className="rounded-md bg-sky-100 p-2 text-sky-700">
                  <Settings2 className="h-4 w-4" />
                </span>
                <span>
                  <span className="block text-sm font-semibold text-slate-900">Reload tracked media</span>
                  <span className="block text-sm text-slate-600">Retrieve existing tracked entries</span>
                </span>
              </button>

              <button
                type="button"
                onClick={() => setViewMode("thumbnail")}
                className="group flex w-full items-start gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <span className="rounded-md bg-indigo-100 p-2 text-indigo-700">
                  <WandSparkles className="h-4 w-4" />
                </span>
                <span>
                  <span className="block text-sm font-semibold text-slate-900">Generate thumbnails</span>
                  <span className="block text-sm text-slate-600">Create missing sizes</span>
                </span>
              </button>

              <button
                type="button"
                onClick={() => setViewMode("row")}
                className="group flex w-full items-start gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
              >
                <span className="rounded-md bg-emerald-100 p-2 text-emerald-700">
                  <Database className="h-4 w-4" />
                </span>
                <span>
                  <span className="block text-sm font-semibold text-slate-900">Manage rows</span>
                  <span className="block text-sm text-slate-600">View database entries</span>
                </span>
              </button>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-lg">No media found</CardTitle>
            </CardHeader>
            <CardContent>
              {isLoadingList ? (
                <p className="text-sm text-slate-600">Loading tracked images...</p>
              ) : trackedImages.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                  <div className="mb-4 rounded-full bg-white p-4 shadow-sm">
                    <Images className="h-8 w-8 text-slate-500" aria-hidden="true" />
                  </div>
                  <h3 className="text-lg font-semibold text-slate-900">No media tracked yet</h3>
                  <p className="mt-1 text-sm text-slate-600">Load tracked entries to review your media data.</p>
                  <Button
                    onClick={handleReloadTrackedImages}
                    disabled={isLoadingList}
                    className="mt-6 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                  >
                    {isLoadingList ? "Loading..." : "Load tracked media"}
                  </Button>
                </div>
              ) : viewMode === "thumbnail" ? (
                <div className="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-5">
                  {trackedImages.map((image) => (
                    <article key={image.id} className="overflow-hidden rounded-lg border bg-white">
                      <img
                        src={image.url}
                        alt={image.title || `Tracked image ${image.id}`}
                        className="h-32 w-full object-cover"
                        loading="lazy"
                      />
                      <div className="p-3">
                        <p className="truncate text-sm font-medium text-slate-900">{image.title || `#${image.id}`}</p>
                        <p className="truncate text-xs text-slate-600">{image.uploaded_by || "Unknown uploader"}</p>
                      </div>
                    </article>
                  ))}
                </div>
              ) : (
                <div className="space-y-2">
                  {trackedImages.map((image) => (
                    <article
                      key={image.id}
                      className="flex items-center justify-between gap-3 rounded-lg border bg-white px-3 py-2"
                    >
                      <div className="flex items-center gap-3">
                        <img
                          src={image.url}
                          alt={image.title || `Tracked image ${image.id}`}
                          className="h-10 w-10 rounded object-cover"
                          loading="lazy"
                        />
                        <div>
                          <p className="text-sm font-medium text-slate-900">{image.title || `#${image.id}`}</p>
                          <p className="text-xs text-slate-600">{image.uploaded_by || "Unknown uploader"}</p>
                        </div>
                      </div>
                      <span className="text-xs text-slate-600">{image.mime_type}</span>
                    </article>
                  ))}
                </div>
              )}
              {listError !== "" ? <p className="mt-3 text-sm text-red-600">{listError}</p> : null}
            </CardContent>
          </Card>
        </section>
      ) : activeMenu === "galleries" ? (
        <>
          <section className="space-y-2">
            <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery {title}</h1>
            <p className="text-sm text-slate-600">{description}</p>
          </section>
          <Card>
            <CardHeader>
              <CardTitle>Galleries Placeholder</CardTitle>
              <CardDescription>This area is reserved for gallery list, creation, and organization tools.</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-slate-600">Add your galleries UI here later.</p>
            </CardContent>
          </Card>
        </>
      ) : (
        <>
          <section className="space-y-2">
            <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery {title}</h1>
            <p className="text-sm text-slate-600">{description}</p>
          </section>
          <Card>
            <CardHeader>
              <CardTitle>Settings Placeholder</CardTitle>
              <CardDescription>This area is reserved for settings forms and configuration controls.</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="mb-4 text-sm text-slate-600">Add your settings controls here later.</p>
              <pre className="overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">
                {JSON.stringify(config, null, 2)}
              </pre>
            </CardContent>
          </Card>
        </>
      )}
    </main>
  );
}
