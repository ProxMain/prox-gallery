import { Button } from "@/components/ui/button";

export function TopBar({ menuItems, activeMenu, onSelectMenu }) {
  return (
    <section className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
      <div className="flex flex-wrap items-center gap-2">
        {menuItems.map((menuItem) => (
          <Button
            key={menuItem.key}
            variant={activeMenu === menuItem.key ? "default" : "ghost"}
            size="sm"
            onClick={() => onSelectMenu(menuItem.key)}
            className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
          >
            {menuItem.label}
          </Button>
        ))}
      </div>
    </section>
  );
}
