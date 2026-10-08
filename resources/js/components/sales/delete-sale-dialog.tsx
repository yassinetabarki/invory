import type { RefObject } from 'react';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import type { Sale } from '@/types/domain';

type DeleteSaleDialogProps = {
    /** The sale to delete; the dialog is open while this is set. */
    sale: Sale | null;
    processing: boolean;
    returnFocusTo: RefObject<HTMLElement | null>;
    onCancel: () => void;
    onConfirm: () => void;
};

/** Confirmation before a sale leaves the log (and the dashboard totals). */
export function DeleteSaleDialog({
    sale,
    processing,
    returnFocusTo,
    onCancel,
    onConfirm,
}: DeleteSaleDialogProps) {
    const { locale } = useAppSettings();

    return (
        <ConfirmDeleteDialog
            open={sale !== null}
            onOpenChange={(open) => {
                if (!open && !processing) {
                    onCancel();
                }
            }}
            title="Delete sale?"
            description={
                sale && (
                    <p>
                        The sale of{' '}
                        <strong>
                            {formatCount(sale.quantity, locale)} ×{' '}
                            {sale.product.name}
                        </strong>{' '}
                        on {formatDate(sale.sold_on, locale)} will be removed
                        from the sales log and the dashboard totals. Material
                        stock is not affected. This cannot be undone.
                    </p>
                )
            }
            confirmLabel="Delete sale"
            processing={processing}
            returnFocusTo={returnFocusTo}
            onConfirm={onConfirm}
        />
    );
}
