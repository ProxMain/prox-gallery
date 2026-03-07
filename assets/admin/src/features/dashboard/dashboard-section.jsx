import { BarChart3, Eye, FolderOpen, Globe2, Image as ImageIcon, LayoutDashboard } from "lucide-react";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";

function number(value) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) {
    return 0;
  }

  return parsed;
}

function formatCount(value) {
  return number(value).toLocaleString();
}

function topCountryRows(countries = {}, limit = 8) {
  return Object.entries(countries)
    .map(([code, count]) => ({ code, count: number(count) }))
    .sort((left, right) => right.count - left.count)
    .slice(0, limit);
}

export function DashboardSection({ summary, isLoading = false, error = "" }) {
  const totals = summary?.totals ?? { gallery_views: 0, image_views: 0 };
  const countries = topCountryRows(summary?.countries ?? {}, 8);
  const galleries = Array.isArray(summary?.galleries) ? summary.galleries.slice(0, 8) : [];
  const images = Array.isArray(summary?.images) ? summary.images.slice(0, 8) : [];

  return (
    <>
      <SectionHeader
        variant="page"
        icon={LayoutDashboard}
        title="Prox Gallery - Dashboard"
        description="Live analytics for gallery visits and image views by country."
      />

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center justify-between text-sm font-medium text-slate-700">
              Gallery Visits
              <FolderOpen className="h-4 w-4 text-violet-600" />
            </CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-semibold text-slate-900">
            {formatCount(totals.gallery_views)}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center justify-between text-sm font-medium text-slate-700">
              Image Views
              <Eye className="h-4 w-4 text-sky-600" />
            </CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-semibold text-slate-900">
            {formatCount(totals.image_views)}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center justify-between text-sm font-medium text-slate-700">
              Countries Tracked
              <Globe2 className="h-4 w-4 text-emerald-600" />
            </CardTitle>
          </CardHeader>
          <CardContent className="text-2xl font-semibold text-slate-900">
            {formatCount(countries.length)}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center justify-between text-sm font-medium text-slate-700">
              Last Update
              <BarChart3 className="h-4 w-4 text-amber-600" />
            </CardTitle>
          </CardHeader>
          <CardContent className="text-sm font-medium text-slate-900">
            {summary?.updated_at ? new Date(summary.updated_at).toLocaleString() : "No data yet"}
          </CardContent>
        </Card>
      </section>

      <section className="grid gap-4 xl:grid-cols-3">
        <Card className="xl:col-span-1">
          <CardHeader className="border-b border-slate-200 pb-3">
            <CardTitle className="text-base">Top Countries</CardTitle>
          </CardHeader>
          <CardContent className="pt-3">
            {countries.length === 0 ? (
              <p className="text-sm text-slate-600">No country data yet.</p>
            ) : (
              <div className="space-y-2">
                {countries.map((row) => (
                  <div key={row.code} className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2">
                    <span className="text-sm font-medium text-slate-900">{row.code}</span>
                    <span className="text-xs text-slate-600">{formatCount(row.count)}</span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="xl:col-span-1">
          <CardHeader className="border-b border-slate-200 pb-3">
            <CardTitle className="text-base">Top Galleries</CardTitle>
          </CardHeader>
          <CardContent className="pt-3">
            {galleries.length === 0 ? (
              <p className="text-sm text-slate-600">No gallery visits yet.</p>
            ) : (
              <div className="space-y-2">
                {galleries.map((gallery) => (
                  <div key={gallery.gallery_id} className="rounded-md border border-slate-200 px-3 py-2">
                    <p className="truncate text-sm font-medium text-slate-900">{gallery.name}</p>
                    <p className="mt-0.5 text-xs text-slate-600">{formatCount(gallery.total)} visits</p>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="xl:col-span-1">
          <CardHeader className="border-b border-slate-200 pb-3">
            <CardTitle className="text-base">Top Images</CardTitle>
          </CardHeader>
          <CardContent className="pt-3">
            {images.length === 0 ? (
              <p className="text-sm text-slate-600">No image views yet.</p>
            ) : (
              <div className="space-y-2">
                {images.map((image) => (
                  <div key={image.image_id} className="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2">
                    <div className="min-w-0">
                      <p className="truncate text-sm font-medium text-slate-900">{image.title || `#${image.image_id}`}</p>
                      <p className="text-xs text-slate-600">Image #{image.image_id}</p>
                    </div>
                    <span className="inline-flex items-center gap-1 text-xs text-slate-700">
                      <ImageIcon className="h-3.5 w-3.5 text-sky-600" />
                      {formatCount(image.total)}
                    </span>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </section>

      {isLoading ? <p className="text-sm text-slate-600">Loading analytics...</p> : null}
      {error !== "" ? <p className="text-sm text-red-600">{error}</p> : null}
    </>
  );
}

