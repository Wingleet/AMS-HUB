/**
 * ConfirmDialog
 *
 * A lightweight, reusable confirmation modal. Renders nothing when closed.
 * Portaled to document.body so it escapes overflow/transform contexts.
 *
 * Ported from iDeck's version (`frontend/src/components/ConfirmDialog.tsx`):
 * self-contained, no external hook or store dependency, so it works from the
 * login screen where no session exists yet.
 */

import { useEffect } from "react";
import { createPortal } from "react-dom";

/** Visual intent of the dialog, driving icon and confirm-button colors. */
type ConfirmTone = "confirm" | "warning" | "danger";

interface ConfirmDialogProps {
  /** Whether the dialog is visible */
  isOpen: boolean;
  /** Dialog title */
  title: string;
  /** Body message (string or custom node) */
  message: React.ReactNode;
  /** Label for the confirm button (default: "Confirm") */
  confirmLabel?: string;
  /** Label for the cancel button (default: "Cancel") */
  cancelLabel?: string;
  /** Label shown on the confirm button while processing (default: "Processing...") */
  processingLabel?: string;
  /** Visual intent driving icon and confirm-button color (default: "warning") */
  tone?: ConfirmTone;
  /** Disables buttons and shows a processing label while an action runs */
  isProcessing?: boolean;
  /** Called when the user confirms */
  onConfirm: () => void;
  /** Called when the user cancels or dismisses the dialog */
  onCancel: () => void;
}

const TONE_STYLES: Record<
  ConfirmTone,
  { iconWrap: string; iconColor: string; confirmButton: string; glyph: string }
> = {
  confirm: {
    iconWrap: "bg-green-100",
    iconColor: "text-green-600",
    confirmButton:
      "text-white bg-green-600 hover:bg-green-700 focus-visible:ring-green-500",
    glyph: "✓",
  },
  warning: {
    iconWrap: "bg-amber-100",
    iconColor: "text-amber-600",
    confirmButton:
      "text-white bg-amber-500 hover:bg-amber-600 focus-visible:ring-amber-400",
    glyph: "!",
  },
  danger: {
    iconWrap: "bg-red-100",
    iconColor: "text-red-600",
    confirmButton:
      "text-white bg-red-600 hover:bg-red-700 focus-visible:ring-red-500",
    glyph: "!",
  },
};

/**
 * Displays a confirmation dialog with confirm/cancel actions.
 */
export function ConfirmDialog({
  isOpen,
  title,
  message,
  confirmLabel = "Confirm",
  cancelLabel = "Cancel",
  processingLabel = "Processing...",
  tone = "warning",
  isProcessing = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape" && !isProcessing) onCancel();
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, isProcessing, onCancel]);

  if (!isOpen) return null;

  const toneStyle = TONE_STYLES[tone];

  return createPortal(
    <div
      className="fixed inset-0 z-[2000] bg-black/40 backdrop-blur-[2px] flex items-center justify-center p-4"
      onClick={() => {
        if (!isProcessing) onCancel();
      }}
      role="presentation"
    >
      <div
        className="w-full max-w-md bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden"
        onClick={(e) => e.stopPropagation()}
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-dialog-title"
      >
        <div className="px-5 pt-5 pb-4 flex items-start gap-3.5">
          <div
            className={`flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full ${toneStyle.iconWrap}`}
            aria-hidden="true"
          >
            <span className={`text-lg font-bold ${toneStyle.iconColor}`}>
              {toneStyle.glyph}
            </span>
          </div>
          <div className="flex-1 min-w-0 pt-0.5">
            <h3
              id="confirm-dialog-title"
              className="text-base font-semibold text-slate-900"
            >
              {title}
            </h3>
            <div className="mt-1.5 text-sm leading-relaxed text-slate-600">
              {message}
            </div>
          </div>
        </div>
        <div className="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
          <button
            type="button"
            onClick={onCancel}
            disabled={isProcessing}
            className="px-3.5 py-1.5 rounded-md text-sm font-medium text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {cancelLabel}
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={isProcessing}
            className={`px-3.5 py-1.5 rounded-md text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center gap-1.5 ${toneStyle.confirmButton}`}
          >
            {isProcessing && (
              <span
                className="inline-block h-3.5 w-3.5 rounded-full border-2 border-current border-t-transparent animate-spin"
                aria-hidden="true"
              />
            )}
            {isProcessing ? processingLabel : confirmLabel}
          </button>
        </div>
      </div>
    </div>,
    document.body,
  );
}

export default ConfirmDialog;
