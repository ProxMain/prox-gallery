import { useEffect, useRef } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";

/** Native modal semantics provide focus trapping, Escape and background isolation. */
export function Dialog({
  title,
  onClose,
  children,
  busy = false,
  wide = false,
}) {
  const ref = useRef(null);
  const close = useRef(onClose);
  close.current = onClose;
  useEffect(() => {
    const dialog = ref.current;
    const previousFocus = document.activeElement;
    const overflow = document.body.style.overflow;
    dialog.showModal();
    document.body.style.overflow = "hidden";
    return () => {
      dialog.close();
      document.body.style.overflow = overflow;
      previousFocus?.focus();
    };
  }, []);

  return createPortal(
    <dialog
      ref={ref}
      className={`pg-dialog ${wide ? "pg-dialog-wide" : ""}`}
      aria-label={title}
      onCancel={(event) => {
        event.preventDefault();
        if (!busy) close.current();
      }}
    >
      <div className="prox-gallery-admin">
        <header className="pg-dialog-header">
          <h2>{title}</h2>
          <button
            type="button"
            className="pg-icon-button"
            onClick={onClose}
            disabled={busy}
            aria-label="Close dialog"
          >
            <X size={20} />
          </button>
        </header>
        {children}
      </div>
    </dialog>,
    document.body,
  );
}
