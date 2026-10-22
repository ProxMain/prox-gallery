import { Images, Plus } from "lucide-react";

import { SectionHeader } from "@/core/section-header";
import { Button } from "@/components/ui/button";

export function MediaManagerHeader({
  config,
  isOpeningPicker,
  onOpenPicker,
  pickerError,
  lastSelectionSummary,
  pagination,
}) {
  return (
    <div className="space-y-2">
      <SectionHeader
        variant="page"
        icon={Images}
        title="Your image library"
        description="Every image, in its place. Find it, refine it, make it yours."
        actions={
          <>
            <Button
              type="button"
              onClick={onOpenPicker}
              disabled={isOpeningPicker}
              className="focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
            >
              <Plus size={16} />
              {isOpeningPicker ? "Opening..." : "Add images"}
            </Button>
          </>
        }
      />
      {pagination}
      {lastSelectionSummary !== "" ? (
        <p className="text-sm text-emerald-700">{lastSelectionSummary}</p>
      ) : null}
      {pickerError !== "" ? (
        <p className="text-sm text-red-600">{pickerError}</p>
      ) : null}
    </div>
  );
}
