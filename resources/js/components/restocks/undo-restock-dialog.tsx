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
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatDate } from '@/lib/format';
import type { RestockDetail } from '@/types/domain';

/**
 * "Takes the 34 units of 25 Oct 2026 (BL-0412) back off the shelf: Denim Jacket 24, Denim Jeans 5-pocket 10. The restock stays in
 * the list, marked Undone." A product deleted since is left out: its stock went with it.
 */
export function undoText(restock: RestockDetail, locale: string): string {
    const kept = restock.blocks.filter((block) => !block.product.deleted);
    const products = kept
        .map(
            (block) =>
                `${block.product.name} ${formatCount(block.units, locale)}`,
        )
        .join(', ');
    const when = formatDate(restock.restocked_on, locale);
    const of = restock.reference ? `${when} (${restock.reference})` : when;
    const what =
        kept.length === restock.blocks.length
            ? `the ${formatCount(restock.units, locale)} ${restock.units === 1 ? 'unit' : 'units'} of ${of}`
            : `the units of ${of}`;

    return `Takes ${what} back off the shelf: ${products}. The restock stays in the list, marked Undone.`;
}

type UndoRestockDialogProps = {
    restock: RestockDetail;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** True while the undo is in flight: the confirm button shows a spinner and keeps its label. */
    processing: boolean;
    /** The server's refusal (the stock changed while the confirm was open), shown in red. */
    error: string | null;
    onConfirm: () => void;
};

/**
 * "Undo this restock?" (INV-3, AC-6). Confirming does not close it: the panel
 * closes it once the undo is done, so the spinner stays visible meanwhile, and
 * a refusal shows here in red, with nothing changed.
 */
export function UndoRestockDialog({
    restock,
    open,
    onOpenChange,
    processing,
    error,
    onConfirm,
}: UndoRestockDialogProps) {
    const { locale } = useAppSettings();

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Undo this restock?</AlertDialogTitle>
                    <AlertDialogDescription>
                        {undoText(restock, locale)}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                {error && (
                    <p role="alert" className="text-sm text-destructive">
                        {error}
                    </p>
                )}
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Keep restock
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
                        Undo restock
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
