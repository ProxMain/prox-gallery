import { FolderOpen } from "lucide-react";

import { SectionHeader } from "@/core/section-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function GalleriesHeader({ config, templateCount = 0 }) {
  return (
    <SectionHeader
      variant="page"
      icon={FolderOpen}
      title="Prox Gallery - Galleries"
      description="Create and organize your gallery collections."
      meta={<p className="text-xs text-slate-500">Templates loaded: {templateCount}</p>}
      actions={
        <>
          <Button
            type="button"
            variant="outline"
            className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
          >
            New gallery (placeholder)
          </Button>
          <Badge variant="outline" className="h-9 px-3 text-xs font-medium text-slate-700">
            Environment: Local | Screen: {config.screen || "n/a"}
          </Badge>
        </>
      }
    />
  );
}
