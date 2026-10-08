import type { ReactNode, RefObject } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Spinner } from '@/components/ui/spinner';

type ConfirmDeleteDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: ReactNode;
    confirmLabel?: string;
    /** True while the delete request is in flight (buttons disabled, spinner shown). */
    processing?: boolean;
    /**
     * Element that gets focus back when the dialog closes. The dialog is
     * opened by page state, not by a Radix trigger, so without this focus
     * would fall back to the page body.
     */
    returnFocusTo?: RefObject<HTMLElement | null>;
    onConfirm: () => void;
};

/**
 * Destructive confirmation. Confirming does NOT close the dialog by itself:
 * the page closes it (`onOpenChange(false)`) when the request finishes, so the
 * `processing` state stays visible while the request runs.
 */
export function ConfirmDeleteDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = 'Delete',
    processing = false,
    returnFocusTo,
    onConfirm,
}: ConfirmDeleteDialogProps) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription asChild>
                        <div>{description}</div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        variant="destructive"
                        disabled={processing}
                        onClick={(event) => {
                            event.preventDefault();
                            onConfirm();
                        }}
                    >
                        {processing && <Spinner />}
                        {confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
