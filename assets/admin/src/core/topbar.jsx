import { FolderOpen, Images, LayoutDashboard, Settings2 } from "lucide-react";

const menuIconByKey = {
  dashboard: LayoutDashboard,
  "media-manager": Images,
  galleries: FolderOpen,
  settings: Settings2
};

export function TopBar({ menuItems, activeMenu, onSelectMenu }) {
  return (
    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="border-b border-sky-100 bg-gradient-to-r from-sky-50/80 to-violet-50/60 px-3 py-2">
        <p className="text-xs font-medium uppercase tracking-wide text-slate-600">Navigation</p>
      </div>
      <div className="flex flex-wrap items-center gap-2 p-3">
        {menuItems.map((menuItem) => {
          const Icon = menuIconByKey[menuItem.key] ?? LayoutDashboard;
          const isActive = activeMenu === menuItem.key;

          return (
            <button
              key={menuItem.key}
              type="button"
              onClick={() => onSelectMenu(menuItem.key)}
              className={`inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 ${
                isActive
                  ? "border-violet-200 bg-violet-50 text-violet-700"
                  : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
              }`}
            >
              <Icon className={`h-4 w-4 ${isActive ? "text-violet-700" : "text-slate-600"}`} />
              {menuItem.label}
            </button>
          );
        })}
      </div>
    </section>
  );
}
