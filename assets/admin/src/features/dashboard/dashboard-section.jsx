import { useEffect, useState } from "react";
import {
  AlertTriangle,
  ArrowDownRight,
  ArrowUpRight,
  Camera,
  Clock3,
  FolderOpen,
  Globe2,
  Image as ImageIcon,
  LayoutDashboard,
  LineChart,
  MapPinned,
  Sparkles,
  Tags,
  Zap
} from "lucide-react";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
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

function deltaTone(delta) {
  if (number(delta) > 0) {
    return "text-emerald-100 bg-emerald-400/15 border-emerald-400/25";
  }

  if (number(delta) < 0) {
    return "text-rose-100 bg-rose-400/15 border-rose-400/25";
  }

  return "text-white bg-white/[0.08] border-white/10";
}

function DeltaBadge({ delta, deltaPercentage }) {
  const positive = number(delta) > 0;
  const negative = number(delta) < 0;

  return (
    <span className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold ${deltaTone(delta)}`}>
      {positive ? <ArrowUpRight className="h-3.5 w-3.5" /> : null}
      {negative ? <ArrowDownRight className="h-3.5 w-3.5" /> : null}
      {formatCount(Math.abs(delta))}
      {typeof deltaPercentage === "number" ? ` (${deltaPercentage > 0 ? "+" : ""}${deltaPercentage}%)` : ""}
    </span>
  );
}

function StatChip({ label, value, tone = "slate", delta = null, deltaPercentage = null }) {
  const toneClassName = {
    slate: "border-white/20 bg-white/10 text-white",
    sky: "border-sky-200 bg-sky-50 text-sky-950",
    amber: "border-amber-200 bg-amber-50 text-amber-950"
  }[tone] ?? "border-white/20 bg-white/10 text-white";

  return (
    <div className={`rounded-2xl border px-4 py-3 ${toneClassName}`}>
      <div className="flex items-start justify-between gap-3">
        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] opacity-70">{label}</p>
        {delta !== null ? <DeltaBadge delta={delta} deltaPercentage={deltaPercentage} /> : null}
      </div>
      <p className="mt-2 text-2xl font-semibold">{value}</p>
    </div>
  );
}

function SectionCard({ title, description, children, className = "" }) {
  return (
    <Card className={`overflow-hidden border-white/10 bg-slate-950/[0.78] shadow-[0_30px_80px_rgba(2,6,23,0.3)] backdrop-blur ${className}`}>
      <CardHeader className="border-b border-white/10 pb-4">
        <CardTitle className="text-base text-white">{title}</CardTitle>
        {description ? <CardDescription>{description}</CardDescription> : null}
      </CardHeader>
      <CardContent className="pt-5">{children}</CardContent>
    </Card>
  );
}

function TrendBars({ rows, tone = "sky" }) {
  const maxValue = Math.max(...rows.map((row) => number(row.count)), 1);
  const gradientClassName =
    tone === "amber"
      ? "from-amber-400 to-rose-400"
      : "from-sky-400 to-emerald-400";

  return (
    <div className="mt-4">
      <div className="flex h-40 items-end gap-2">
        {rows.map((row) => (
          <div key={row.date || row.label} className="flex flex-1 flex-col items-center gap-2">
            <div className="flex h-32 w-full items-end">
              <div
                title={`${row.label}: ${row.count}`}
                className={`w-full rounded-t-xl bg-gradient-to-t ${gradientClassName} shadow-[0_10px_30px_rgba(14,165,233,0.18)]`}
                style={{ height: `${Math.max(8, (number(row.count) / maxValue) * 100)}%` }}
              />
            </div>
            <span className="text-[11px] text-white">{String(row.label).replace(" ", "\u00a0")}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function InsightList({ rows, emptyText, renderRow }) {
  if (!Array.isArray(rows) || rows.length === 0) {
    return <p className="text-sm text-white">{emptyText}</p>;
  }

  return <div className="space-y-3">{rows.map(renderRow)}</div>;
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
  const comparison = summary?.comparison ?? {
    gallery_views: { current: 0, previous: 0, delta: 0, delta_percentage: null },
    image_views: { current: 0, previous: 0, delta: 0, delta_percentage: null }
  };
  const countries = topCountryRows(summary?.countries ?? {}, 5);
  const allCountryCount = Object.keys(summary?.countries ?? {}).length;
  const galleries = Array.isArray(summary?.galleries) ? summary.galleries.slice(0, 5) : [];
  const images = Array.isArray(summary?.images) ? summary.images.slice(0, 5) : [];
  const categories = Array.isArray(summary?.categories) ? summary.categories.slice(0, 6) : [];
  const recentActivity = Array.isArray(summary?.recent_activity) ? summary.recent_activity : [];
  const spotlightGallery = summary?.spotlight?.gallery ?? null;
  const spotlightImage = summary?.spotlight?.image ?? null;
  const trends = summary?.trends ?? { gallery_views: [], image_views: [] };
  const momentum = summary?.momentum ?? { galleries: [], images: [] };
  const underperforming = Array.isArray(summary?.underperforming_galleries) ? summary.underperforming_galleries : [];
  const freshUploads = summary?.fresh_uploads ?? { images: [], galleries: [] };
  const templatePerformance = Array.isArray(summary?.template_performance) ? summary.template_performance : [];
  const layoutPerformance = Array.isArray(summary?.layout_performance) ? summary.layout_performance : [];
  const sources = Array.isArray(summary?.sources) ? summary.sources.slice(0, 6) : [];
  const devices = Array.isArray(summary?.devices) ? summary.devices : [];
  const lightboxEngagement = summary?.lightbox_engagement ?? {
    totals: { lightbox_opens: 0, info_panel_opens: 0, lightbox_rate_per_gallery_visit: 0, info_rate_per_image_view: 0 },
    top_galleries: [],
    top_images: []
  };
  const seasonal = summary?.seasonal ?? { gallery_views: [], image_views: [] };
  const recommendations = Array.isArray(summary?.recommendations) ? summary.recommendations : [];
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
        description="A cinematic command center for portfolio health, momentum, and visitor engagement."
      />

      <section className="overflow-hidden rounded-[28px] border border-slate-900/10 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_28%),radial-gradient(circle_at_80%_20%,_rgba(245,158,11,0.18),_transparent_24%),linear-gradient(135deg,#0f172a_0%,#111827_45%,#1e293b_100%)] p-6 text-white shadow-[0_40px_120px_rgba(15,23,42,0.28)] md:p-8">
        <div className="grid gap-8 xl:grid-cols-[1.3fr_1fr]">
          <div>
            <div className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs uppercase tracking-[0.2em] text-white/80">
              <LineChart className="h-3.5 w-3.5" />
              Phase 2 Trends
            </div>
            <h2 className="mt-4 max-w-3xl text-3xl font-semibold leading-tight text-white md:text-4xl">
              Track what is rising, what is slipping, and where your portfolio is actually gaining traction.
            </h2>
            <p className="mt-3 max-w-2xl text-sm text-white md:text-base">
              Phase 2 adds trend and comparison signals so you can see momentum, underperformance, and layout-level performance instead of just raw totals.
            </p>

            <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <StatChip
                label="Gallery visits"
                value={formatCount(totals.gallery_views)}
                delta={comparison.gallery_views.delta}
                deltaPercentage={comparison.gallery_views.delta_percentage}
              />
              <StatChip
                label="Image views"
                value={formatCount(totals.image_views)}
                delta={comparison.image_views.delta}
                deltaPercentage={comparison.image_views.delta_percentage}
              />
              <StatChip label="Tracked images" value={formatCount(totals.tracked_images)} />
              <StatChip label="Published galleries" value={formatCount(totals.galleries)} />
              <StatChip label="Media categories" value={formatCount(totals.categories)} />
              <StatChip label="Countries reached" value={formatCount(allCountryCount)} />
            </div>

            <div className="mt-6 flex flex-wrap items-center gap-3 text-sm text-white">
              <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1">
                <Clock3 className="h-4 w-4" />
                Last refresh: {formatDateTime(summary?.updated_at)}
              </span>
              <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1">
                <Globe2 className="h-4 w-4" />
                Current depth: {totals.gallery_views > 0 ? `${Math.round((totals.image_views / totals.gallery_views) * 10) / 10} image views per visit` : "No engagement data yet"}
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
                  <div className="mt-3 grid gap-3 sm:grid-cols-2">
                    <StatChip label="Visits" value={formatCount(spotlightGallery.total)} tone="sky" />
                    <StatChip label="Health" value={`${formatCount(spotlightGallery.health_score)}/100`} tone="sky" />
                  </div>
                </div>
              ) : (
                <p className="mt-3 text-sm text-white">No gallery performance data yet.</p>
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
                    <img src={spotlightImage.thumbnail_url} alt={spotlightImage.title} className="h-20 w-20 rounded-2xl object-cover ring-1 ring-white/20" />
                  ) : (
                    <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20">
                      <ImageIcon className="h-8 w-8 text-white" />
                    </div>
                  )}
                  <div className="min-w-0">
                    <p className="truncate text-lg font-semibold text-white">{spotlightImage.title || `#${spotlightImage.image_id}`}</p>
                    <p className="mt-1 text-sm text-white">{formatCount(spotlightImage.total)} image views</p>
                    <p className="mt-1 text-xs text-white">
                      {Array.isArray(spotlightImage.categories) && spotlightImage.categories.length > 0
                        ? spotlightImage.categories.map((category) => category.name).join(", ")
                        : "No categories assigned"}
                    </p>
                  </div>
                </div>
              ) : (
                <p className="mt-3 text-sm text-white">No image performance data yet.</p>
              )}
            </div>
          </div>
        </div>
      </section>

      <Tabs defaultValue="overview" className="mt-6">
        <TabsList className="h-auto w-full flex-wrap justify-start gap-2 rounded-2xl border border-white/10 bg-slate-950/[0.72] p-2">
          <TabsTrigger value="overview" className="rounded-xl px-4 py-2">Overview</TabsTrigger>
          <TabsTrigger value="performance" className="rounded-xl px-4 py-2">Performance</TabsTrigger>
          <TabsTrigger value="audience" className="rounded-xl px-4 py-2">Audience</TabsTrigger>
          <TabsTrigger value="actions" className="rounded-xl px-4 py-2">Actions</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="mt-4 space-y-4">
          <section className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <SectionCard
              title="Top Galleries"
              description="Your most visited galleries, now enriched with period comparison and health."
            >
              <InsightList
                rows={galleries}
                emptyText="No gallery visits yet."
                renderRow={(gallery, index) => (
                  <div
                    key={gallery.gallery_id}
                    className="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4 md:grid-cols-[auto_1fr_auto]"
                  >
                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/[0.08] text-sm font-semibold text-white">
                      {index + 1}
                    </div>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-semibold text-white">{gallery.name}</p>
                      <p className="mt-1 text-xs text-white">
                        {gallery.template} · {formatCount(gallery.image_count)} images · health {formatCount(gallery.health_score)}/100
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-lg font-semibold text-white">{formatCount(gallery.total)}</p>
                      <p className="text-xs text-white">visits</p>
                      <div className="mt-2">
                        <DeltaBadge delta={gallery.delta} deltaPercentage={gallery.delta_percentage} />
                      </div>
                    </div>
                  </div>
                )}
              />
            </SectionCard>

            <SectionCard title="Recent Activity" description="New galleries and tracked images that keep the dashboard alive.">
              <InsightList
                rows={recentActivity}
                emptyText="No recent activity yet."
                renderRow={(item, index) => (
                  <div key={`${item.type}-${item.title}-${index}`} className="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4 md:grid-cols-[auto_1fr]">
                    <div className={`flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 ${item.type === "gallery" ? "bg-sky-400/15 text-sky-200" : "bg-fuchsia-400/15 text-fuchsia-200"}`}>
                      {item.type === "gallery" ? <FolderOpen className="h-5 w-5" /> : <ImageIcon className="h-5 w-5" />}
                    </div>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-semibold text-white">{item.title}</p>
                      <p className="mt-1 text-xs text-white">{item.subtitle}</p>
                      <p className="mt-1 text-xs text-white">{formatDateTime(item.timestamp)}</p>
                    </div>
                  </div>
                )}
              />
            </SectionCard>
          </section>

          <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
            <SectionCard title="Top Images" description="Tracked images with the strongest overall pull.">
              <InsightList
                rows={images}
                emptyText="No image views yet."
                renderRow={(image) => (
                  <div key={image.image_id} className="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.05] p-3">
                    {image.thumbnail_url ? (
                      <img src={image.thumbnail_url} alt={image.title} className="h-14 w-14 rounded-xl object-cover" />
                    ) : (
                      <div className="flex h-14 w-14 items-center justify-center rounded-xl border border-white/10 bg-white/[0.08]">
                        <ImageIcon className="h-5 w-5 text-white" />
                      </div>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-semibold text-white">{image.title || `#${image.image_id}`}</p>
                      <p className="mt-1 text-xs text-white">
                        {Array.isArray(image.categories) && image.categories.length > 0
                          ? image.categories.map((category) => category.name).join(", ")
                          : "No categories"}
                      </p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold text-white">{formatCount(image.total)}</p>
                      <p className="text-xs text-white">views</p>
                    </div>
                  </div>
                )}
              />
            </SectionCard>

            <SectionCard title="Category Inventory" description="How your tracked media is distributed across categories.">
              <InsightList
                rows={categories}
                emptyText="No categories assigned yet."
                renderRow={(category) => (
                  <div key={category.name} className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                    <div className="flex items-center justify-between gap-3">
                      <div className="inline-flex items-center gap-2 text-sm font-semibold text-white">
                        <Tags className="h-4 w-4 text-orange-200" />
                        {category.name}
                      </div>
                      <span className="text-sm font-semibold text-white">{formatCount(category.count)}</span>
                    </div>
                    <div className="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                      <div className="h-full rounded-full bg-gradient-to-r from-orange-500 to-amber-300" style={{ width: `${Math.max(10, percentage(category.count, totals.tracked_images))}%` }} />
                    </div>
                  </div>
                )}
              />
            </SectionCard>
          </section>
        </TabsContent>

        <TabsContent value="performance" className="mt-4 space-y-4">
          <section className="grid gap-4 xl:grid-cols-2">
            <SectionCard
              title="Gallery Visits Trend"
              description={`Last ${Array.isArray(trends.gallery_views) ? trends.gallery_views.length : 14} days, compared against the previous ${comparison.gallery_views.previous > 0 ? "period" : "window"}.`}
            >
              <div className="flex items-center justify-between gap-4">
                <div>
                  <p className="text-3xl font-semibold text-white">{formatCount(comparison.gallery_views.current)}</p>
                  <p className="text-sm text-white">Current 7-day gallery visits</p>
                </div>
                <DeltaBadge delta={comparison.gallery_views.delta} deltaPercentage={comparison.gallery_views.delta_percentage} />
              </div>
              <TrendBars rows={Array.isArray(trends.gallery_views) ? trends.gallery_views : []} tone="sky" />
            </SectionCard>

            <SectionCard
              title="Image Views Trend"
              description="Track whether traffic is turning into deeper image engagement."
            >
              <div className="flex items-center justify-between gap-4">
                <div>
                  <p className="text-3xl font-semibold text-white">{formatCount(comparison.image_views.current)}</p>
                  <p className="text-sm text-white">Current 7-day image views</p>
                </div>
                <DeltaBadge delta={comparison.image_views.delta} deltaPercentage={comparison.image_views.delta_percentage} />
              </div>
              <TrendBars rows={Array.isArray(trends.image_views) ? trends.image_views : []} tone="amber" />
            </SectionCard>
          </section>

          <section className="grid gap-4 xl:grid-cols-3">
            <SectionCard title="Momentum" description="Galleries and images accelerating in the current 7-day window.">
              <div className="space-y-5">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Galleries</p>
                  <InsightList
                    rows={momentum.galleries}
                    emptyText="No gallery momentum yet."
                    renderRow={(item) => (
                      <div key={`momentum-gallery-${item.id}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="truncate text-sm font-semibold text-white">{item.name}</p>
                          <DeltaBadge delta={item.delta} />
                        </div>
                      </div>
                    )}
                  />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Images</p>
                  <InsightList
                    rows={momentum.images}
                    emptyText="No image momentum yet."
                    renderRow={(item) => (
                      <div key={`momentum-image-${item.id}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="truncate text-sm font-semibold text-white">{item.name}</p>
                          <DeltaBadge delta={item.delta} />
                        </div>
                      </div>
                    )}
                  />
                </div>
              </div>
            </SectionCard>

            <SectionCard title="Underperforming Galleries" description="Galleries losing momentum or drawing weak current-period traffic.">
              <InsightList
                rows={underperforming}
                emptyText="No underperforming galleries yet."
                renderRow={(gallery) => (
                  <div key={gallery.gallery_id} className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                    <div className="flex items-center justify-between gap-3">
                      <p className="truncate text-sm font-semibold text-white">{gallery.name}</p>
                      <DeltaBadge delta={gallery.delta} deltaPercentage={gallery.delta_percentage} />
                    </div>
                    <p className="mt-2 text-xs text-white">
                      Current 7-day visits: {formatCount(gallery.current_period)} · Health {formatCount(gallery.health_score)}/100
                    </p>
                  </div>
                )}
              />
            </SectionCard>

            <SectionCard title="Fresh Upload Performance" description="New uploads and galleries gaining traction soon after creation.">
              <div className="space-y-5">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Recent images</p>
                  <InsightList
                    rows={freshUploads.images}
                    emptyText="No recent tracked images yet."
                    renderRow={(item) => (
                      <div key={`fresh-image-${item.image_id}`} className="mt-3 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.05] p-3">
                        {item.thumbnail_url ? (
                          <img src={item.thumbnail_url} alt={item.title} className="h-14 w-14 rounded-xl object-cover" />
                        ) : (
                          <div className="flex h-14 w-14 items-center justify-center rounded-xl border border-white/10 bg-white/[0.08]">
                            <ImageIcon className="h-5 w-5 text-white" />
                          </div>
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-semibold text-white">{item.title}</p>
                          <p className="text-xs text-white">
                            {formatCount(item.current_period)} recent views · {formatCount(item.total)} total
                          </p>
                        </div>
                      </div>
                    )}
                  />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Recent galleries</p>
                  <InsightList
                    rows={freshUploads.galleries}
                    emptyText="No recent galleries yet."
                    renderRow={(item) => (
                      <div key={`fresh-gallery-${item.gallery_id}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <p className="truncate text-sm font-semibold text-white">{item.name}</p>
                        <p className="mt-1 text-xs text-white">
                          {item.template} · {formatCount(item.image_count)} images · {formatCount(item.current_period)} recent visits
                        </p>
                      </div>
                    )}
                  />
                </div>
              </div>
            </SectionCard>
          </section>

          <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
            <SectionCard title="Template And Layout Performance" description="Compare which presentation styles are pulling stronger traffic.">
              <div className="grid gap-5 md:grid-cols-2">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Templates</p>
                  <InsightList
                    rows={templatePerformance}
                    emptyText="No template performance data yet."
                    renderRow={(item) => (
                      <div key={`template-${item.template}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="text-sm font-semibold text-white">{item.template}</p>
                          <DeltaBadge delta={item.delta} />
                        </div>
                        <p className="mt-2 text-xs text-white">
                          {formatCount(item.visits)} visits · {formatCount(item.avg_visits)} avg per gallery
                        </p>
                      </div>
                    )}
                  />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Layouts</p>
                  <InsightList
                    rows={layoutPerformance}
                    emptyText="No layout performance data yet."
                    renderRow={(item) => (
                      <div key={`layout-${item.label}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="text-sm font-semibold text-white">{item.label}</p>
                          <DeltaBadge delta={item.delta} />
                        </div>
                        <p className="mt-2 text-xs text-white">
                          {formatCount(item.visits)} visits · {formatCount(item.avg_visits)} avg per gallery
                        </p>
                      </div>
                    )}
                  />
                </div>
              </div>
            </SectionCard>

            <SectionCard title="Seasonal Comparison" description="A 12-month view of gallery traffic and image engagement.">
              <div className="grid gap-6">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Gallery views by month</p>
                  <TrendBars rows={Array.isArray(seasonal.gallery_views) ? seasonal.gallery_views : []} tone="sky" />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Image views by month</p>
                  <TrendBars rows={Array.isArray(seasonal.image_views) ? seasonal.image_views : []} tone="amber" />
                </div>
              </div>
            </SectionCard>
          </section>
        </TabsContent>

        <TabsContent value="audience" className="mt-4 space-y-4">
          <section className="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <SectionCard
              title="Country Reach"
              description="Where your audience is concentrated right now."
            >
              <InsightList
                rows={countries}
                emptyText="No country data yet."
                renderRow={(row) => (
                  <div key={row.code} className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                    <div className="flex items-center justify-between gap-3">
                      <div className="inline-flex items-center gap-2 text-sm font-semibold text-white">
                        <MapPinned className="h-4 w-4 text-emerald-300" />
                        {row.code}
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-white">{formatCount(row.count)}</p>
                        <p className="text-xs text-white">{percentage(row.count, totals.gallery_views)}%</p>
                      </div>
                    </div>
                    <div className="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                      <div className="h-full rounded-full bg-gradient-to-r from-emerald-500 to-sky-500" style={{ width: `${Math.max(8, percentage(row.count, totals.gallery_views))}%` }} />
                    </div>
                  </div>
                )}
              />
            </SectionCard>

            <SectionCard title="Traffic Sources And Devices" description="See how visitors are arriving and which devices dominate the experience.">
              <div className="grid gap-5 md:grid-cols-2">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Sources</p>
                  <InsightList
                    rows={sources}
                    emptyText="No source tracking yet."
                    renderRow={(item) => (
                      <div key={`source-${item.label}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="text-sm font-semibold text-white">{item.label}</p>
                          <span className="text-sm font-semibold text-white">{formatCount(item.count)}</span>
                        </div>
                      </div>
                    )}
                  />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Devices</p>
                  <InsightList
                    rows={devices}
                    emptyText="No device tracking yet."
                    renderRow={(item) => (
                      <div key={`device-${item.label}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="text-sm font-semibold text-white">{item.label}</p>
                          <span className="text-sm font-semibold text-white">{formatCount(item.count)}</span>
                        </div>
                      </div>
                    )}
                  />
                </div>
              </div>
            </SectionCard>
          </section>

          <section className="grid gap-4 xl:grid-cols-[1fr_1fr]">
            <SectionCard title="Lightbox Engagement" description="Measure deeper image curiosity beyond basic visits and views.">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Totals</p>
                  <p className="mt-3 text-2xl font-semibold text-white">{formatCount(lightboxEngagement.totals.lightbox_opens)}</p>
                  <p className="mt-1 text-sm text-white">Lightbox opens</p>
                  <p className="mt-3 text-sm text-white">
                    {lightboxEngagement.totals.lightbox_rate_per_gallery_visit}% per gallery visit
                  </p>
                  <p className="mt-1 text-sm text-white">
                    {formatCount(lightboxEngagement.totals.info_panel_opens)} info opens
                  </p>
                </div>
                <div className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Top image by curiosity</p>
                  {Array.isArray(lightboxEngagement.top_images) && lightboxEngagement.top_images.length > 0 ? (
                    <>
                      <p className="mt-3 text-lg font-semibold text-white">{lightboxEngagement.top_images[0].title}</p>
                      <p className="mt-1 text-sm text-white">
                        {formatCount(lightboxEngagement.top_images[0].lightbox_opens)} lightbox opens · {lightboxEngagement.top_images[0].lightbox_rate}% of image views
                      </p>
                    </>
                  ) : (
                    <p className="mt-3 text-sm text-white">No lightbox interaction yet.</p>
                  )}
                </div>
              </div>
              <div className="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Top galleries</p>
                  <InsightList
                    rows={lightboxEngagement.top_galleries}
                    emptyText="No gallery lightbox data yet."
                    renderRow={(item) => (
                      <div key={`lightbox-gallery-${item.gallery_id}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="truncate text-sm font-semibold text-white">{item.name}</p>
                          <span className="text-sm font-semibold text-white">{formatCount(item.lightbox_opens)}</span>
                        </div>
                      </div>
                    )}
                  />
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.18em] text-white">Top images</p>
                  <InsightList
                    rows={lightboxEngagement.top_images}
                    emptyText="No image lightbox data yet."
                    renderRow={(item) => (
                      <div key={`lightbox-image-${item.image_id}`} className="mt-3 rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                        <div className="flex items-center justify-between gap-3">
                          <p className="truncate text-sm font-semibold text-white">{item.title}</p>
                          <span className="text-sm font-semibold text-white">{formatCount(item.lightbox_opens)}</span>
                        </div>
                      </div>
                    )}
                  />
                </div>
              </div>
            </SectionCard>
          </section>
        </TabsContent>

        <TabsContent value="actions" className="mt-4 space-y-4">
          <section className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <SectionCard title="Smart Recommendations" description="Action-oriented prompts derived from momentum, gaps, sources, and interaction behavior.">
              <InsightList
                rows={recommendations}
                emptyText="No recommendations yet."
                renderRow={(item, index) => (
                  <div
                    key={`${item.title}-${index}`}
                    className={`rounded-2xl border p-4 ${
                      item.tone === "positive"
                        ? "border-emerald-400/25 bg-emerald-400/10"
                        : item.tone === "warning"
                          ? "border-amber-400/25 bg-amber-400/10"
                          : "border-white/10 bg-white/[0.05]"
                    }`}
                  >
                    <div className="flex items-start gap-3">
                      <div className={`mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl ${
                        item.tone === "positive"
                          ? "bg-emerald-400/15 text-emerald-200"
                          : item.tone === "warning"
                            ? "bg-amber-400/15 text-amber-200"
                            : "bg-white/10 text-white"
                      }`}>
                        <Zap className="h-4 w-4" />
                      </div>
                      <div>
                        <p className="text-sm font-semibold text-white">{item.title}</p>
                        <p className="mt-1 text-sm text-white">{item.detail}</p>
                      </div>
                    </div>
                  </div>
                )}
              />
            </SectionCard>

            <SectionCard title="Portfolio Gaps" description="Structural issues worth fixing before promotion.">
              <div className="space-y-3">
                <div className="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-4">
                  <div className="flex items-center gap-2 text-sm font-semibold text-amber-100">
                    <AlertTriangle className="h-4 w-4" />
                    Galleries missing descriptions
                  </div>
                  <p className="mt-2 text-2xl font-semibold text-amber-100">{formatCount(gaps.galleries_without_description)}</p>
                </div>
                <div className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                  <p className="text-sm font-semibold text-white">Thin galleries</p>
                  <p className="mt-1 text-sm text-white">Galleries with fewer than three images.</p>
                  <p className="mt-2 text-xl font-semibold text-white">{formatCount(gaps.galleries_with_few_images)}</p>
                </div>
                <div className="rounded-2xl border border-white/10 bg-white/[0.05] p-4">
                  <p className="text-sm font-semibold text-white">Uncategorized tracked images</p>
                  <p className="mt-1 text-sm text-white">Tracked images that still need categorization.</p>
                  <p className="mt-2 text-xl font-semibold text-white">{formatCount(gaps.uncategorized_images)}</p>
                </div>
              </div>
            </SectionCard>
          </section>
        </TabsContent>
      </Tabs>

      {isLoading ? <p className="mt-4 text-sm text-white">Loading analytics...</p> : null}
      {error !== "" ? <p className="mt-4 text-sm text-red-300">{error}</p> : null}
    </div>
  );
}
