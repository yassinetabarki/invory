import type { RefObject } from 'react';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { Order } from '@/types/domain';

type DeleteOrderDialogProps = {
    /** The order to delete; the dialog is open while this is set. */
    order: Order | null;
    processing: boolean;
    returnFocusTo: RefObject<HTMLElement | null>;
    onCancel: () => void;
    onConfirm: () => void;
};

/**
 * Confirmation before an order goes. Stock and sales are never touched. While
 * units are in cutting or ready to ship (`delete_refusal`), the window shows
 * the server's refusal instead of its question, with only "Close": nothing is
 * sent.
 */
export function DeleteOrderDialog({
    order,
    processing,
    returnFocusTo,
    onCancel,
    onConfirm,
}: DeleteOrderDialogProps) {
    const { locale } = useAppSettings();

    if (order?.delete_refusal) {
        return (
            <AlertDialog
                open
                onOpenChange={(open) => {
                    if (!open) {
                        onCancel();
                    }
                }}
            >
                <AlertDialogContent
                    onCloseAutoFocus={(event) => {
                        if (returnFocusTo.current) {
                            event.preventDefault();
                            returnFocusTo.current.focus();
                        }
                    }}
                >
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete order?</AlertDialogTitle>
                        <AlertDialogDescription>
                            {order.delete_refusal}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Close</AlertDialogCancel>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        );
    }

    return (
        <ConfirmDeleteDialog
            open={order !== null}
            onOpenChange={(open) => {
                if (!open && !processing) {
                    onCancel();
                }
            }}
            title="Delete order?"
            description={
                order && (
                    <p>
                        The order of <strong>{order.client}</strong> (
                        {order.lines.length === 1
                            ? '1 product'
                            : `${order.lines.length} products`}
                        ,{' '}
                        {order.units === 1
                            ? '1 unit'
                            : `${formatCount(order.units, locale)} units`}
                        ) will be removed. Material stock and sales are not
                        affected. This cannot be undone.
                    </p>
                )
            }
            confirmLabel="Delete order"
            processing={processing}
            returnFocusTo={returnFocusTo}
            onConfirm={onConfirm}
        />
    );
}
