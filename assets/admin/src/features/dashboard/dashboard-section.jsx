import { useEffect, useState } from "react";
import {
  AlertTriangle,
  Camera,
  Clock3,
  FolderOpen,
  Globe2,
  Image as ImageIcon,
  LayoutDashboard,
  MapPinned,
  Sparkles,
  Tags
} from "lucide-react";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";
import { useTrackingActionController } from "@/lib/action-controller-hooks";

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

function formatDateTime(value) {
  if (typeof value !== "string" || value.trim() === "") {
    return "No data yet";
  }

  const parsed = new Date(value);

  if (Number.isNaN(parsed.getTime())) {
    return "No data yet";
  }

  return parsed.toLocaleString();
}

function percentage(part, total) {
  const normalizedTotal = number(total);

  if (normalizedTotal <= 0) {
    return 0;
  }

  return Math.round((number(part) / normalizedTotal) * 100);
}

function topCountryRows(countries = {}, limit = 5) {
  return Object.entries(countries)
    .map(([code, count]) => ({ code, count: number(count) }))
    .sort((left, right) => right.count - left.count)
    .slice(0, limit);
}

function StatChip({ label, value, tone = "slate" }) {
  const toneClassName = {
    slate: "border-white/20 bg-white/10 text-white",
    sky: "border-sky-200 bg-sky-50 text-sky-950",
    amber: "border-amber-200 bg-amber-50 text-amber-950"
  }[tone] ?? "border-white/20 bg-white/10 text-white";

  return (
    <div className={`rounded-2xl border px-4 py-3 ${toneClassName}`}>
      <p className="text-[11px] font-semibold uppercase tracking-[0.18em] opacity-70">{label}</p>
      <p className="mt-2 text-2xl font-semibold">{value}</p>
    </div>
  );
}

function SectionCard({ title, description, children, className = "" }) {
  return (
    <Card className={`overflow-hidden border-slate-200/80 bg-white/90 shadow-[0_30px_80px_rgba(15,23,42,0.08)] backdrop-blur ${className}`}>
      <CardHeader className="border-b border-slate-200/80 pb-4">
        <CardTitle className="text-base text-slate-950">{title}</CardTitle>
        {description ? <CardDescription>{description}</CardDescription> : null}
      </CardHeader>
      <CardContent className="pt-5">{children}</CardContent>
    </Card>
  );
}

export function DashboardSection({ config, isActive }) {
  const trackingController = useTrackingActionController(config);
  const [summary, setSummary] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (!isActive || !trackingController) {
      return;
    }

    let active = true;

    const loadDashboard = async () => {
      try {
        setIsLoading(true);
        setError("");
        const response = await trackingController.getSummary();

        if (!active) {
          return;
        }

        setSummary(response.summary);
      } catch (loadError) {
        if (!active) {
          return;
        }

        const message = loadError instanceof Error ? loadError.message : "Failed to load dashboard analytics.";
        setError(message);
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    };

    void loadDashboard();

    return () => {
      active = false;
    };
  }, [isActive, trackingController]);

  const totals = summary?.totals ?? {
    gallery_views: 0,
    image_views: 0,
    tracked_images: 0,
    galleries: 0,
    categories: 0
  };
  const countries = topCountryRows(summary?.countries ?? {}, 5);
  const allCountryCount = Object.keys(summary?.countries ?? {}).length;
  const galleries = Array.isArray(summary?.galleries) ? summary.galleries.slice(0, 5) : [];
  const images = Array.isArray(summary?.images) ? summary.images.slice(0, 5) : [];
  const categories = Array.isArray(summary?.categories) ? summary.categories.slice(0, 6) : [];
  const recentActivity = Array.isArray(summary?.recent_activity) ? summary.recent_activity : [];
  const spotlightGallery = summary?.spotlight?.gallery ?? null;
  const spotlightImage = summary?.spotlight?.image ?? null;
  const gaps = summary?.portfolio_gaps ?? {
    galleries_without_description: 0,
    galleries_with_few_images: 0,
    uncategorized_images: 0,
    empty_galleries: 0
  };

  return (
    <div className={isActive ? "" : "hidden"}>
      <SectionHeader
        variant="page"
        icon={LayoutDashboard}
        title="Prox Gallery - Dashboard"
        description="A cinematic command center for gallery performance, image traction, and portfolio health."
      />

      <section className="overflow-hidden rounded-[28px] border border-slate-900/10 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_28%),radial-gradient(circle_at_80%_20%,_rgba(245,158,11,0.18),_transparent_24%),linear-gradient(135deg,#0f172a_0%,#111827_45%,#1e293b_100%)] p-6 text-white shadow-[0_40px_120px_rgba(15,23,42,0.28)] md:p-8">
        <div className="grid gap-8 xl:grid-cols-[1.35fr_0.95fr]">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs uppercase tracking-[0.2em] text-white/80">
              <Sparkles className="h-3.5 w-3.5" />
              Phase 1 Overview
            </div>
            <h2 className="mt-4 max-w-3xl text-3xl font-semibold leading-tight text-white md:text-4xl">
              See which galleries are earning attention and which parts of your portfolio need work.
            </h2>
            <p className="mt-3 max-w-2xl text-sm text-slate-200 md:text-base">
              This dashboard turns your gallery traffic, image views, tracked media, and categories into a clearer story for what to feature next.
            </p>

            <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <StatChip label="Gallery visits" value={formatCount(totals.gallery_views)} />
              <StatChip label="Image views" value={formatCount(totals.image_views)} />
              <StatChip label="Tracked images" value={formatCount(totals.tracked_images)} />
              <StatChip label="Published galleries" value={formatCount(totals.galleries)} />
              <StatChip label="Media categories" value={formatCount(totals.categories)} />
              <StatChip label="Countries reached" value={formatCount(allCountryCount)} />
            </div>

            <div className="mt-6 flex flex-wrap items-center gap-3 text-sm text-slate-200">
              <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1">
                <Clock3 className="h-4 w-4" />
                Last refresh: {formatDateTime(summary?.updated_at)}
              </span>
              <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1">
                <Globe2 className="h-4 w-4" />
                Engagement depth: {totals.gallery_views > 0 ? `${Math.round((totals.image_views / totals.gallery_views) * 10) / 10} views per visit` : "No engagement data yet"}
              </span>
            </div>
          </div>

          <div className="grid gap-4">
            <div className="rounded-[24px] border border-white/12 bg-white/10 p-5 backdrop-blur">
              <div className="flex items-center gap-2 text-sm font-medium text-slate-100">
                <FolderOpen className="h-4 w-4 text-sky-300" />
                Gallery Spotlight
              </div>
              {spotlightGallery ? (
                <div className="mt-4">
                  <p className="text-xl font-semibold text-white">{spotlightGallery.name}</p>
                  <div className="mt-3 grid gap-3 sm:grid-cols-3">
                    <StatChip label="Visits" value={formatCount(spotlightGallery.total)} tone="sky" />
                    <StatChip label="Images" value={formatCount(spotlightGallery.image_count)} tone="sky" />
                    <StatChip label="Template" value={String(spotlightGallery.template || "basic-grid")} tone="sky" />
                  </div>
                </div>
              ) : (
                <p className="mt-3 text-sm text-slate-200">No gallery performance data yet.</p>
              )}
            </div>

            <div className="rounded-[24px] border border-white/12 bg-white/10 p-5 backdrop-blur">
              <div className="flex items-center gap-2 text-sm font-medium text-slate-100">
                <Camera className="h-4 w-4 text-amber-300" />
                Image Spotlight
              </div>
              {spotlightImage ? (
                <div className="mt-4 flex items-center gap-4">
                  {spotlightImage.thumbnail_url ? (
                    <img
                      src={spotlightImage.thumbnail_url}
                      alt={spotlightImage.title}
                      className="h-20 w-20 rounded-2xl object-cover ring-1 ring-white/20"
                    />
                  ) : (
                    <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                      <ImageIcon className="h-8 w-8 text-slate-200" />
                    </div>
                  )}
                  <div className="min-w-0">
                    <p className="truncate text-lg font-semibold text-white">{spotlightImage.title || `#${spotlightImage.image_id}`}</p>
                    <p className="mt-1 text-sm text-slate-200">{formatCount(spotlightImage.total)} image views</p>
                    <p className="mt-1 text-xs text-slate-300">
                      {Array.isArray(spotlightImage.categories) && spotlightImage.categories.length > 0
                        ? spotlightImage.categories.map((category) => category.name).join(", ")
                        : "No categories assigned"}
                    </p>
                  </div>
                </div>
              ) : (
                <p className="mt-3 text-sm text-slate-200">No image performance data yet.</p>
              )}
            </div>
          </div>
        </div>
      </section>

      <section className="mt-6 grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
        <SectionCard
          title="Top Galleries"
          description="Your most visited galleries right now, enriched with template and image-count context."
        >
          {galleries.length === 0 ? (
            <p className="text-sm text-slate-600">No gallery visits yet.</p>
          ) : (
            <div className="space-y-3">
              {galleries.map((gallery, index) => (
                <div
                  key={gallery.gallery_id}
                  className="grid gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-[auto_1fr_auto]"
                >
                  <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white">
                    {index + 1}
                  </div>
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-950">{gallery.name}</p>
                    <p className="mt-1 text-xs text-slate-600">
                      {gallery.template} · {formatCount(gallery.image_count)} images · {gallery.has_description ? "described" : "missing description"}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-lg font-semibold text-slate-950">{formatCount(gallery.total)}</p>
                    <p className="text-xs text-slate-500">visits</p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </SectionCard>

        <SectionCard
          title="Country Reach"
          description="Where your gallery audience is coming from based on current visit tracking."
        >
          {countries.length === 0 ? (
            <p className="text-sm text-slate-600">No country data yet.</p>
          ) : (
            <div className="space-y-3">
              {countries.map((row) => (
                <div key={row.code} className="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
                  <div className="flex items-center justify-between gap-3">
                    <div className="inline-flex items-center gap-2 text-sm font-semibold text-slate-950">
                      <MapPinned className="h-4 w-4 text-emerald-600" />
                      {row.code}
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold text-slate-950">{formatCount(row.count)}</p>
                      <p className="text-xs text-slate-500">{percentage(row.count, totals.gallery_views)}%</p>
                    </div>
                  </div>
                  <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div
                      className="h-full rounded-full bg-gradient-to-r from-emerald-500 to-sky-500"
                      style={{ width: `${Math.max(8, percentage(row.count, totals.gallery_views))}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>
          )}
        </SectionCard>
      </section>

      <section className="mt-4 grid gap-4 xl:grid-cols-3">
        <SectionCard
          title="Top Images"
          description="Images pulling the strongest view counts across your tracked media."
          className="xl:col-span-1"
        >
          {images.length === 0 ? (
            <p className="text-sm text-slate-600">No image views yet.</p>
          ) : (
            <div className="space-y-3">
              {images.map((image) => (
                <div key={image.image_id} className="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-3">
                  {image.thumbnail_url ? (
                    <img
                      src={image.thumbnail_url}
                      alt={image.title}
                      className="h-14 w-14 rounded-xl object-cover"
                    />
                  ) : (
                    <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-200">
                      <ImageIcon className="h-5 w-5 text-slate-500" />
                    </div>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-slate-950">{image.title || `#${image.image_id}`}</p>
                    <p className="mt-1 text-xs text-slate-600">
                      {Array.isArray(image.categories) && image.categories.length > 0
                        ? image.categories.map((category) => category.name).join(", ")
                        : "No categories"}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-semibold text-slate-950">{formatCount(image.total)}</p>
                    <p className="text-xs text-slate-500">views</p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </SectionCard>

        <SectionCard
          title="Category Inventory"
          description="How your tracked media is distributed across Prox Gallery categories."
          className="xl:col-span-1"
        >
          {categories.length === 0 ? (
            <p className="text-sm text-slate-600">No categories assigned yet.</p>
          ) : (
            <div className="space-y-3">
              {categories.map((category) => (
                <div key={category.name} className="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
                  <div className="flex items-center justify-between gap-3">
                    <div className="inline-flex items-center gap-2 text-sm font-semibold text-slate-950">
                      <Tags className="h-4 w-4 text-violet-600" />
                      {category.name}
                    </div>
                    <span className="text-sm font-semibold text-slate-950">{formatCount(category.count)}</span>
                  </div>
                  <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                    <div
                      className="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-400"
                      style={{ width: `${Math.max(10, percentage(category.count, totals.tracked_images))}%` }}
                    />
                  </div>
                </div>
              ))}
            </div>
          )}
        </SectionCard>

        <SectionCard
          title="Portfolio Gaps"
          description="Places where your library or gallery setup still needs polish."
          className="xl:col-span-1"
        >
          <div className="space-y-3">
            <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
              <div className="flex items-center gap-2 text-sm font-semibold text-amber-950">
                <AlertTriangle className="h-4 w-4" />
                Galleries missing descriptions
              </div>
              <p className="mt-2 text-2xl font-semibold text-amber-950">{formatCount(gaps.galleries_without_description)}</p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p className="text-sm font-semibold text-slate-950">Thin galleries</p>
              <p className="mt-1 text-sm text-slate-600">Galleries with fewer than three images.</p>
              <p className="mt-2 text-xl font-semibold text-slate-950">{formatCount(gaps.galleries_with_few_images)}</p>
            </div>
            <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <p className="text-sm font-semibold text-slate-950">Uncategorized tracked images</p>
              <p className="mt-1 text-sm text-slate-600">Tracked images that still need categorization.</p>
              <p className="mt-2 text-xl font-semibold text-slate-950">{formatCount(gaps.uncategorized_images)}</p>
            </div>
          </div>
        </SectionCard>
      </section>

      <section className="mt-4 grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
        <SectionCard
          title="Client-ready Spotlight"
          description="The strongest gallery and image surfaced from current performance."
        >
          <div className="grid gap-4 md:grid-cols-2">
            <div className="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Top gallery</p>
              <p className="mt-3 text-lg font-semibold text-slate-950">{spotlightGallery?.name || "No gallery yet"}</p>
              <p className="mt-2 text-sm text-slate-600">
                {spotlightGallery
                  ? `${formatCount(spotlightGallery.total)} visits · ${formatCount(spotlightGallery.image_count)} images · ${spotlightGallery.template}`
                  : "Once galleries receive visits, this area will highlight your strongest candidate."}
              </p>
            </div>
            <div className="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Top image</p>
              <p className="mt-3 text-lg font-semibold text-slate-950">{spotlightImage?.title || "No image yet"}</p>
              <p className="mt-2 text-sm text-slate-600">
                {spotlightImage
                  ? `${formatCount(spotlightImage.total)} views · ${Array.isArray(spotlightImage.categories) && spotlightImage.categories.length > 0 ? spotlightImage.categories.map((category) => category.name).join(", ") : "No categories"}`
                  : "Once tracked images receive views, this area will highlight your strongest portfolio piece."}
              </p>
            </div>
          </div>
        </SectionCard>

        <SectionCard
          title="Recent Activity"
          description="New galleries and tracked images so the dashboard stays alive even before trends arrive."
        >
          {recentActivity.length === 0 ? (
            <p className="text-sm text-slate-600">No recent activity yet.</p>
          ) : (
            <div className="space-y-3">
              {recentActivity.map((item, index) => (
                <div key={`${item.type}-${item.title}-${index}`} className="grid gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 md:grid-cols-[auto_1fr_auto]">
                  <div className={`flex h-11 w-11 items-center justify-center rounded-2xl ${item.type === "gallery" ? "bg-sky-100 text-sky-700" : "bg-violet-100 text-violet-700"}`}>
                    {item.type === "gallery" ? <FolderOpen className="h-5 w-5" /> : <ImageIcon className="h-5 w-5" />}
                  </div>
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-slate-950">{item.title}</p>
                    <p className="mt-1 text-xs text-slate-600">{item.subtitle}</p>
                  </div>
                  <div className="text-right text-xs text-slate-500">{formatDateTime(item.timestamp)}</div>
                </div>
              ))}
            </div>
          )}
        </SectionCard>
      </section>

      {isLoading ? <p className="mt-4 text-sm text-slate-600">Loading analytics...</p> : null}
      {error !== "" ? <p className="mt-4 text-sm text-red-600">{error}</p> : null}
    </div>
  );
}
