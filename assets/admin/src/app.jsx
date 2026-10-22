import { useEffect, useMemo, useState } from "react";

import { TopBar } from "@/core/topbar";
import { DashboardSection } from "@/features/dashboard/dashboard-section";
import { GalleriesSection } from "@/features/galleries/galleries-section";
import { MediaManagerSection } from "@/features/media-manager/media-manager-section";
import { SettingsSection } from "@/features/settings/settings-section";
import { getAdminConfig } from "@/lib/admin-config";
import { ADMIN_MENU_ITEMS, sectionTitle } from "@/lib/admin-logic";

export function App() {
  const readSection = () => {
    const key = window.location.hash.slice(1);
    return ADMIN_MENU_ITEMS.some((item) => item.key === key)
      ? key
      : "galleries";
  };
  const [activeMenu, setActiveMenu] = useState(readSection);
  const config = useMemo(getAdminConfig, []);
  const title = sectionTitle(activeMenu);

  useEffect(() => {
    const sync = () => setActiveMenu(readSection());
    window.addEventListener("hashchange", sync);
    return () => window.removeEventListener("hashchange", sync);
  }, []);

  const selectMenu = (key) => {
    setActiveMenu(key);
    window.location.hash = key;
  };

  return (
    <div className="prox-gallery-admin pg-workspace">
      <TopBar
        menuItems={ADMIN_MENU_ITEMS}
        activeMenu={activeMenu}
        onSelectMenu={selectMenu}
      />
      <main id="pg-content" className="pg-content" tabIndex={-1}>
        <div className="pg-breadcrumb">
          <span>Workspace</span>
          <span aria-hidden="true">/</span>
          {title}
        </div>

        <DashboardSection
          config={config}
          isActive={activeMenu === "dashboard"}
        />
        <MediaManagerSection
          config={config}
          isActive={activeMenu === "media-manager"}
        />
        <GalleriesSection
          config={config}
          isActive={activeMenu === "galleries"}
        />
        <SettingsSection
          title={title}
          config={config}
          isActive={activeMenu === "settings"}
        />
        <footer className="pg-workspace-footer">
          <span>Prox Gallery</span>
          <span>Your images. Thoughtfully organised.</span>
        </footer>
      </main>
    </div>
  );
}
