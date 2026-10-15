import type { RefObject } from 'react';
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

/** Confirmation before an order goes. Stock and sales are never touched. */
export function DeleteOrderDialog({
    order,
    processing,
    returnFocusTo,
    onCancel,
    onConfirm,
}: DeleteOrderDialogProps) {
    const { locale } = useAppSettings();

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
