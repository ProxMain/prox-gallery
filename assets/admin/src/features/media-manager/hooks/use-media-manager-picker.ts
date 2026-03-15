import { useCallback, useRef, useState } from "react";

import type { MediaManagerTrackSelectionResponse } from "@/modules/media-library/controllers/media-manager-action-controller";

type MediaAttachment = {
  get?: (key: string) => unknown;
};

type MediaSelection = {
  map: <TValue>(mapper: (attachment: MediaAttachment) => TValue) => TValue[];
};

type MediaFrame = {
  on: (eventName: string, callback: () => void | Promise<void>) => void;
  state: () => {
    get: (key: string) => MediaSelection;
  };
  open: () => void;
};

type UseMediaManagerPickerOptions = {
  onTrackSelection: (attachmentIds: number[]) => Promise<MediaManagerTrackSelectionResponse>;
};

type WordPressWindow = Window & {
  wp?: {
    media?: (options: {
      title: string;
      library: { type: string };
      button: { text: string };
      multiple: boolean;
    }) => MediaFrame;
  };
};

export function useMediaManagerPicker({ onTrackSelection }: UseMediaManagerPickerOptions) {
  const frameRef = useRef<MediaFrame | null>(null);
  const [isOpeningPicker, setIsOpeningPicker] = useState(false);
  const [pickerError, setPickerError] = useState("");
  const [lastSelectionSummary, setLastSelectionSummary] = useState("");

  const openPicker = useCallback(() => {
    const browserWindow = typeof window === "undefined" ? undefined : (window as WordPressWindow);

    if (!browserWindow?.wp?.media) {
      setPickerError("WordPress media modal is unavailable on this screen.");
      return;
    }

    if (!frameRef.current) {
      frameRef.current = browserWindow.wp.media({
        title: "Add media to Prox Gallery",
        library: { type: "image" },
        button: { text: "Track selected media" },
        multiple: true
      });

      frameRef.current.on("select", async () => {
        const selection = frameRef.current?.state().get("selection");
        const attachmentIds = selection
          ? selection
            .map((attachment: MediaAttachment) => Number(attachment?.get?.("id") ?? 0))
            .filter((attachmentId: number) => Number.isInteger(attachmentId) && attachmentId > 0)
          : [];

        if (attachmentIds.length === 0) {
          setLastSelectionSummary("No media selected.");
          return;
        }

        setIsOpeningPicker(true);
        setPickerError("");

        try {
          const result = await onTrackSelection(attachmentIds);
          const skippedSuffix = result.skipped_count > 0 ? `, skipped ${result.skipped_count}` : "";

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
