import { FolderOpen, Plus } from "lucide-react";

import { SectionHeader } from "@/core/section-header";
import { Button } from "@/components/ui/button";

export function GalleriesHeader({ onCreate }) {
  return (
    <SectionHeader
      variant="page"
      icon={FolderOpen}
      title="A home for your best work."
      description="Bring your images together. Create a collection worth exploring."
      actions={
        <>
          <Button
            type="button"
            onClick={onCreate}
            className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
          >
            <Plus size={16} />
            Create gallery
          </Button>
        </>
      }
    />
  );
}
