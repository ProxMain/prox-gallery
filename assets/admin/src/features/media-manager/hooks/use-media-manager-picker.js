import { useCallback, useRef, useState } from "react";

export function useMediaManagerPicker({ onTrackSelection }) {
  const frameRef = useRef(null);
  const [isOpeningPicker, setIsOpeningPicker] = useState(false);
  const [pickerError, setPickerError] = useState("");
  const [lastSelectionSummary, setLastSelectionSummary] = useState("");

  const openPicker = useCallback(() => {
    if (typeof window === "undefined" || !window.wp?.media) {
      setPickerError("WordPress media modal is unavailable on this screen.");
      return;
    }

    if (!frameRef.current) {
      frameRef.current = window.wp.media({
        title: "Add media to Prox Gallery",
        library: { type: "image" },
        button: { text: "Track selected media" },
        multiple: true
      });

      frameRef.current.on("select", async () => {
        const selection = frameRef.current.state().get("selection");
        const attachmentIds = selection
          .map((attachment) => Number(attachment?.get?.("id") ?? 0))
          .filter((attachmentId) => Number.isInteger(attachmentId) && attachmentId > 0);

        if (attachmentIds.length === 0) {
          setLastSelectionSummary("No media selected.");
          return;
        }

        setIsOpeningPicker(true);
        setPickerError("");

        try {
          const result = await onTrackSelection(attachmentIds);
          const skippedSuffix =
            result.skipped_count > 0 ? `, skipped ${result.skipped_count}` : "";

          setLastSelectionSummary(`Tracked ${result.tracked_count} of ${result.requested_count} selected item(s)${skippedSuffix}.`);
        } catch (error) {
          setPickerError(error instanceof Error ? error.message : "Failed to track selected media.");
        } finally {
          setIsOpeningPicker(false);
        }
      });
    }

    setPickerError("");
    frameRef.current.open();
  }, [onTrackSelection]);

  return {
    isOpeningPicker,
    pickerError,
    lastSelectionSummary,
    openPicker
  };
}
