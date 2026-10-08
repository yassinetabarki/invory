import { X } from 'lucide-react';
import { useId } from 'react';
import type { RefObject } from 'react';
import { SaleForm } from '@/components/sales/sale-form';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { Sale, SaleProductOption } from '@/types/domain';

type SalePanelProps = {
    /**
     * `aside`: a card beside the table (from `lg`). `sheet`: a slide-over
     * below it. The page picks one from the viewport.
     */
    variant: 'aside' | 'sheet';
    /** The sale being edited; `null` records a new one. */
    sale: Sale | null;
    products: SaleProductOption[];
    initialProductId: number | null;
    onClose: () => void;
    /** What had focus when the panel opened; the side card hands focus back to it. */
    returnFocusTo: RefObject<HTMLElement | null>;
};

/**
 * Where the record / edit sale form lives. The same `<SaleForm>` is shown in a
 * card next to the table on wide screens and in a sheet on narrow ones, with
 * the same heading, so nothing is lost when the window is resized.
 */
export function SalePanel({
    variant,
    sale,
    products,
    initialProductId,
    onClose,
    returnFocusTo,
}: SalePanelProps) {
    const headingId = useId();
    const title = sale ? 'Edit sale' : 'Record a sale';

    // The card has no focus trap to hand focus back, so do it on the way out.
    // (The sheet's own focus scope returns focus to what opened it.)
    function close() {
        if (variant === 'aside') {
            returnFocusTo.current?.focus();
        }

        onClose();
    }

    const form = (
        <SaleForm
            // A different sale or product pre-selection starts a fresh form.
            key={sale ? `edit-${sale.id}` : `create-${initialProductId ?? ''}`}
            sale={sale}
            products={products}
            initialProductId={initialProductId}
            onClose={close}
        />
    );

    if (variant === 'sheet') {
        return (
            <Sheet
                open
                onOpenChange={(open) => {
                    if (!open) {
                        close();
                    }
                }}
            >
                <SheetContent
                    side="right"
                    className="w-full gap-0 overflow-y-auto sm:max-w-md"
                >
                    <SheetHeader>
                        <SheetTitle>{title}</SheetTitle>
                        <SheetDescription className="sr-only">
                            Enter the product, quantity, unit price and date of
                            the sale.
                        </SheetDescription>
                    </SheetHeader>
                    <div className="px-4 pb-6">{form}</div>
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <aside
            aria-labelledby={headingId}
            className="flex min-w-0 flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex items-center justify-between gap-2">
                <h2 id={headingId} className="text-base font-bold">
                    {title}
                </h2>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Close"
                    onClick={close}
                >
                    <X aria-hidden="true" />
                </Button>
            </div>
            {form}
        </aside>
    );
}
