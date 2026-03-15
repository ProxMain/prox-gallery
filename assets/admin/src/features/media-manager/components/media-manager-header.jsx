import { Images } from "lucide-react";

import { SectionHeader } from "@/core/section-header";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

export function MediaManagerHeader({
  config,
  isOpeningPicker,
  onOpenPicker,
  pickerError,
  lastSelectionSummary,
  pagination
}) {
  return (
    <div className="space-y-2">
      <SectionHeader
        variant="page"
        icon={Images}
        title="Prox Gallery - Media Manager"
        description="Manage tracked media and add existing or new images without leaving this screen."
        footer={pagination}
        actions={
          <>
            <Button
            type="button"
            variant="outline"
            onClick={onOpenPicker}
            disabled={isOpeningPicker}
            className="focus-visible:ring-2 focus-visible:ring-orange-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
          >
              {isOpeningPicker ? "Opening..." : "Add media"}
          </Button>
            <Badge variant="outline" className="h-9 px-3 text-xs font-medium text-slate-300">
              Environment: Local | Screen: {config.screen || "n/a"}
            </Badge>
          </>
        }
      />
      {lastSelectionSummary !== "" ? (
        <p className="text-sm text-emerald-300">{lastSelectionSummary}</p>
      ) : null}
      {pickerError !== "" ? (
        <p className="text-sm text-red-300">{pickerError}</p>
      ) : null}
    </div>
  );
}
