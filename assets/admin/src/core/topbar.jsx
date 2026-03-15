import { FolderOpen, Images, LayoutDashboard, Settings2 } from "lucide-react";

import { proxIcon, proxLogo } from "@/core/brand-assets";

const menuIconByKey = {
  dashboard: LayoutDashboard,
  "media-manager": Images,
  galleries: FolderOpen,
  settings: Settings2
};

export function TopBar({ menuItems, activeMenu, onSelectMenu }) {
  return (
    <section className="overflow-hidden rounded-[28px] border border-white/10 bg-slate-950/90 shadow-[0_30px_90px_rgba(2,6,23,0.45)] backdrop-blur">
      <div className="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,_rgba(251,146,60,0.28),_transparent_38%),radial-gradient(circle_at_top_right,_rgba(250,204,21,0.22),_transparent_32%),linear-gradient(135deg,rgba(15,23,42,0.96),rgba(17,24,39,0.9))] px-4 py-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/8 p-1.5 shadow-[0_0_30px_rgba(249,115,22,0.22)]">
              <img src={proxIcon} alt="Prox icon" className="h-full w-full rounded-[18px] object-cover" />
            </div>
            <div className="min-w-0">
              <div className="flex max-w-full items-center rounded-[22px] border border-white/12 bg-black/20 px-3 py-2 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]">
                <img src={proxLogo} alt="Prox logo" className="h-14 w-auto max-w-full object-contain sm:h-16" />
              </div>
              <p className="mt-2 text-xs font-medium uppercase tracking-[0.24em] text-orange-200/80">Cinematic admin portal</p>
            </div>
          </div>
          <div className="flex items-center gap-2 self-start rounded-full border border-white/10 bg-white/6 px-3 py-1.5 text-[11px] font-medium uppercase tracking-[0.22em] text-slate-300">
            <span className="inline-flex h-2 w-2 rounded-full bg-orange-400 shadow-[0_0_16px_rgba(251,146,60,0.9)]" />
            Portfolio control center
          </div>
        </div>
      </div>
      <div className="border-b border-white/6 px-4 py-3">
        <p className="text-xs font-medium uppercase tracking-[0.24em] text-slate-400">Navigation</p>
      </div>
      <div className="flex flex-wrap items-center gap-2 p-4">
        {menuItems.map((menuItem) => {
          const Icon = menuIconByKey[menuItem.key] ?? LayoutDashboard;
          const isActive = activeMenu === menuItem.key;

          return (
            <button
              key={menuItem.key}
              type="button"
              onClick={() => onSelectMenu(menuItem.key)}
              className={`inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 ${
                isActive
                  ? "border-orange-300/40 bg-[linear-gradient(135deg,rgba(249,115,22,0.26),rgba(251,191,36,0.18))] text-white shadow-[0_16px_40px_rgba(249,115,22,0.18)]"
                  : "border-white/10 bg-white/5 text-slate-300 hover:border-orange-300/25 hover:bg-white/10 hover:text-white"
              }`}
            >
              <Icon className={`h-4 w-4 ${isActive ? "text-orange-100" : "text-slate-400"}`} />
              {menuItem.label}
            </button>
          );
        })}
      </div>
    </section>
  );
}
