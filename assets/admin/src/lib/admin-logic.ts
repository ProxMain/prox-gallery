export type AdminMenuKey = "dashboard" | "media-manager" | "galleries" | "settings";

export type AdminStat = {
  label: string;
  value: string;
  icon: "images" | "upload" | "shield-check" | "folder-open";
};

export const ADMIN_MENU_ITEMS: Array<{ key: AdminMenuKey; label: string }> = [
  { key: "dashboard", label: "Dashboard" },
  { key: "media-manager", label: "Media Manager" },
  { key: "galleries", label: "Galleries" },
  { key: "settings", label: "Settings" }
];

export const DASHBOARD_STATS: AdminStat[] = [
  { label: "Tracked Images", value: "124", icon: "images" },
  { label: "Queued Uploads", value: "3", icon: "upload" },
  { label: "Visibility Rules", value: "2", icon: "shield-check" },
  { label: "Active Collections", value: "9", icon: "folder-open" }
];

export function sectionTitle(activeMenu: AdminMenuKey): string {
  switch (activeMenu) {
    case "dashboard":
      return "Dashboard";
    case "media-manager":
      return "Media Manager";
    case "galleries":
      return "Galleries";
    case "settings":
    default:
      return "Settings";
  }
}

export function sectionDescription(activeMenu: AdminMenuKey): string {
  switch (activeMenu) {
    case "dashboard":
      return "Dashboard section placeholder. Content will be added later.";
    case "media-manager":
      return "Media Manager section placeholder. Content will be added later.";
    case "galleries":
      return "Galleries section placeholder. Content will be added later.";
    case "settings":
    default:
      return "Settings section placeholder. Content will be added later.";
  }
}
