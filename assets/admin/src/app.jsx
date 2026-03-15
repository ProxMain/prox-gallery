import { useState } from "react";

import { TopBar } from "@/core/topbar";
import { DashboardSection } from "@/features/dashboard/dashboard-section";
import { GalleriesSection } from "@/features/galleries/galleries-section";
import { MediaManagerSection } from "@/features/media-manager/media-manager-section";
import { SettingsSection } from "@/features/settings/settings-section";
import { getAdminConfig } from "@/lib/admin-config";
import {
  ADMIN_MENU_ITEMS,
  sectionDescription,
  sectionTitle
} from "@/lib/admin-logic";

export function App() {
  const [activeMenu, setActiveMenu] = useState("dashboard");
  const config = getAdminConfig();
  const title = sectionTitle(activeMenu);
  const description = sectionDescription(activeMenu);

  return (
    <main className="prox-gallery-admin prox-gallery-admin-theme min-h-full w-full max-w-none space-y-8 py-8">
      <TopBar menuItems={ADMIN_MENU_ITEMS} activeMenu={activeMenu} onSelectMenu={setActiveMenu} />

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
        description={description}
        config={config}
        isActive={activeMenu === "settings"}
      />
    </main>
  );
}
