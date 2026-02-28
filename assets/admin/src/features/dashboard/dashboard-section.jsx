import { FolderOpen, Images, LayoutDashboard, ShieldCheck, Upload } from "lucide-react";

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { SectionHeader } from "@/core/section-header";
import { DASHBOARD_STATS } from "@/lib/admin-logic";

const STAT_ICONS = {
  images: Images,
  upload: Upload,
  "shield-check": ShieldCheck,
  "folder-open": FolderOpen
};

export function DashboardSection() {
  return (
    <>
      <SectionHeader
        variant="page"
        icon={LayoutDashboard}
        title="Prox Gallery - Dashboard"
        description="Overview and health indicators for your gallery workspace."
      />
      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {DASHBOARD_STATS.map((item) => {
          const Icon = STAT_ICONS[item.icon];

          return (
            <Card key={item.label}>
              <CardHeader className="pb-3">
                <CardDescription>{item.label}</CardDescription>
                <CardTitle className="flex items-center justify-between text-2xl">
                  {item.value}
                  <Icon className="h-5 w-5 text-slate-500" />
                </CardTitle>
              </CardHeader>
            </Card>
          );
        })}
      </section>
      <Card>
        <CardHeader className="p-0">
          <SectionHeader
            framed={false}
            icon={LayoutDashboard}
            title="Dashboard Placeholder"
            description="This area is reserved for future dashboard widgets and summaries."
          />
        </CardHeader>
        <CardContent>
          <p className="text-sm text-slate-600">Add your dashboard content here later.</p>
        </CardContent>
      </Card>
    </>
  );
}
