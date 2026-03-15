import { useCallback, useState } from "react";

import type { GalleryActionController } from "@/modules/gallery/controllers/gallery-action-controller";

type OverrideSelectValue = "inherit" | "on" | "off";
type GridColumnsValue = "inherit" | `${number}`;
type TransitionValue = "inherit" | "none" | "slide" | "fade" | "explode" | "implode";

export type GalleryWizardValue = {
  name: string;
  description: string;
  template: "basic-grid" | "masonry";
  grid_columns_override: GridColumnsValue;
  lightbox_override: OverrideSelectValue;
  hover_zoom_override: OverrideSelectValue;
  full_width_override: OverrideSelectValue;
  transition_override: TransitionValue;
  show_title: boolean;
  show_description: boolean;
};

type CreateGalleryPayload = Parameters<GalleryActionController["createGallery"]>[3] & {
  name: string;
  description: string;
  template: GalleryWizardValue["template"];
};

type UseGalleryCreationWizardOptions = {
  onCreateGallery: (payload: CreateGalleryPayload) => Promise<unknown>;
};

type UpdateableField = keyof GalleryWizardValue;

const INITIAL_VALUE: GalleryWizardValue = {
  name: "",
  description: "",
  template: "basic-grid",
  grid_columns_override: "inherit",
  lightbox_override: "inherit",
  hover_zoom_override: "inherit",
  full_width_override: "inherit",
  transition_override: "inherit",
  show_title: true,
  show_description: true
};

export function useGalleryCreationWizard({ onCreateGallery }: UseGalleryCreationWizardOptions) {
  const [isOpen, setIsOpen] = useState(false);
  const [stepIndex, setStepIndex] = useState(0);
  const [value, setValue] = useState<GalleryWizardValue>(INITIAL_VALUE);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [message, setMessage] = useState("");

  const openWizard = useCallback(() => {
    setIsOpen(true);
    setMessage("");
  }, []);

  const closeWizard = useCallback(() => {
    setIsOpen(false);
    setStepIndex(0);
    setValue(INITIAL_VALUE);
    setMessage("");
    setIsSubmitting(false);
  }, []);

  const updateValue = useCallback(<TField extends UpdateableField>(field: TField, nextValue: GalleryWizardValue[TField]) => {
    setValue((current: GalleryWizardValue) => {
      const next: GalleryWizardValue = {
        ...current,
        [field]: nextValue
      };

      if (field === "template") {
        if (nextValue === "masonry" && next.full_width_override === "inherit") {
          next.full_width_override = "on";
        }

        if (nextValue === "basic-grid" && next.full_width_override === "on") {
          next.full_width_override = "inherit";
        }
      }

      return next;
    });
  }, []);

  const submitWizard = useCallback(async () => {
    const trimmedName = value.name.trim();

    if (trimmedName === "") {
      setMessage("Gallery name is required.");
      setStepIndex(0);
      return;
    }

    const toNullableInt = (rawValue: GridColumnsValue): number | null => (rawValue === "inherit" ? null : Number(rawValue));
    const toNullableBool = (rawValue: OverrideSelectValue): boolean | null => {
      if (rawValue === "inherit") {
        return null;
      }

      return rawValue === "on";
    };

    try {
      setIsSubmitting(true);
      setMessage("");

      await onCreateGallery({
        name: trimmedName,
        description: value.description.trim(),
        template: value.template,
        grid_columns_override: toNullableInt(value.grid_columns_override),
        lightbox_override: toNullableBool(value.lightbox_override),
        hover_zoom_override: toNullableBool(value.hover_zoom_override),
        full_width_override: toNullableBool(value.full_width_override),
        transition_override: value.transition_override === "inherit" ? null : value.transition_override,
        show_title: value.show_title,
        show_description: value.show_description
      });

      closeWizard();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Failed to create gallery.");
    } finally {
      setIsSubmitting(false);
    }
  }, [closeWizard, onCreateGallery, value]);

  return {
    isOpen,
    stepIndex,
    value,
    isSubmitting,
    message,
    openWizard,
    closeWizard,
    setStepIndex,
    updateValue,
    submitWizard
  };
}
