export type AdminMenuKey =
  "galleries" | "media-manager" | "dashboard" | "settings";
export const ADMIN_MENU_ITEMS: Array<{ key: AdminMenuKey; label: string }> = [
  { key: "galleries", label: "Galleries" },
  { key: "media-manager", label: "Media library" },
  { key: "dashboard", label: "Overview" },
  { key: "settings", label: "Settings" },
];
export function sectionTitle(activeMenu: AdminMenuKey): string {
  return (
    ADMIN_MENU_ITEMS.find((item) => item.key === activeMenu)?.label ??
    "Galleries"
  );
}
