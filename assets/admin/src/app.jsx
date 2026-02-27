import { FolderOpen, Images, ShieldCheck, Upload } from "lucide-react";
import { useState } from "react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  ADMIN_MENU_ITEMS,
  DASHBOARD_STATS,
  sectionDescription,
  sectionTitle
} from "@/lib/admin-logic";

const STAT_ICONS = {
  images: Images,
  upload: Upload,
  "shield-check": ShieldCheck,
  "folder-open": FolderOpen
};

export function App() {
  const [activeMenu, setActiveMenu] = useState("dashboard");
  const config = window.ProxGalleryAdminConfig ?? {};
  const title = sectionTitle(activeMenu);
  const description = sectionDescription(activeMenu);

  return (
    <main className="prox-gallery-admin max-w-7xl py-6">
      <section className="mb-6 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div className="flex items-center gap-2">
          {ADMIN_MENU_ITEMS.map((menuItem) => (
            <Button
              key={menuItem.key}
              variant={activeMenu === menuItem.key ? "default" : "ghost"}
              size="sm"
              onClick={() => setActiveMenu(menuItem.key)}
            >
              {menuItem.label}
            </Button>
          ))}
        </div>
        <Badge variant="outline">Screen: {config.screen ?? "n/a"}</Badge>
      </section>

      <section className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-semibold tracking-tight text-slate-900">Prox Gallery {title}</h1>
          <p className="text-sm text-slate-600">{description}</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="secondary" size="sm">
            Secondary Action
          </Button>
          <Button size="sm">Primary Action</Button>
        </div>
      </section>

      {activeMenu === "dashboard" ? (
        <>
          <section className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
        <>
          <Card>
            <CardHeader>
              <CardTitle>Media Manager Placeholder</CardTitle>
              <CardDescription>This area is reserved for media management tools and workflows.</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-slate-600">Add your media manager UI here later.</p>
            </CardContent>
          </Card>
        </>
      ) : activeMenu === "galleries" ? (
        <>
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
