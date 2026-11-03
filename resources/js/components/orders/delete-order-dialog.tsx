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

/**
 * Confirmation before an order goes (INV-14, AC-6). It is never refused: an
 * order holds no materials and no production. Stock, sales and the shelf are
 * never touched; when units were delivered, one more sentence says they are
 * not put back.
 */
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
                        {order.delivered > 0 &&
                            (order.delivered === 1
                                ? " Finished stock isn't changed: the 1 unit already delivered is not put back."
                                : ` Finished stock isn't changed: the ${formatCount(order.delivered, locale)} units already delivered are not put back.`)}
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
