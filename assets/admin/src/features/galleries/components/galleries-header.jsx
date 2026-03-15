import { FolderOpen } from "lucide-react";

import { SectionHeader } from "@/core/section-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function GalleriesHeader({ config, templateCount = 0, pagination = null, onOpenWizard }) {
  return (
    <SectionHeader
      variant="page"
      icon={FolderOpen}
      title="Prox Gallery - Galleries"
      description="Create and organize your gallery collections."
      meta={<p className="text-xs text-slate-400">Templates loaded: {templateCount}</p>}
      footer={pagination}
      actions={
        <>
          <Button
            type="button"
            variant="outline"
            onClick={onOpenWizard}
            className="focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
          >
            New gallery wizard
          </Button>
          <Badge variant="outline" className="h-9 px-3 text-xs font-medium text-slate-300">
            Environment: Local | Screen: {config.screen || "n/a"}
          </Badge>
        </>
      }
    />
  );
}
