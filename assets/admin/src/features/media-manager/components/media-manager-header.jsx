import { Images } from "lucide-react";

import { SectionHeader } from "@/core/section-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function MediaManagerHeader({ config }) {
  return (
    <SectionHeader
      variant="page"
      icon={Images}
      title="Prox Gallery - Media Manager"
      description="Manage your tracked media library."
      actions={
        <>
          <Button
            asChild
            variant="outline"
            className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
          >
            <a href="/wp-admin/upload.php">Upload</a>
          </Button>
          <Badge variant="outline" className="h-9 px-3 text-xs font-medium text-slate-700">
            Environment: Local | Screen: {config.screen || "n/a"}
          </Badge>
        </>
      }
    />
  );
}
