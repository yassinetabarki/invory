import { router } from '@inertiajs/react';
import { useId, useState } from 'react';
import type { FormEvent, RefObject } from 'react';
import ProductStockController from '@/actions/App/Http/Controllers/ProductStockController';
import {
    OrderSizeFields,
    typedUnits,
} from '@/components/orders/order-size-fields';
import { formatUnits } from '@/components/orders/stage-figure';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { FinishedStock, Product } from '@/types/domain';

type UpdateStockDialogProps = {
    product: Pick<Product, 'id' | 'name'>;
    /** Today's count per size; follows the reload of `finished_stock`. */
    stock: FinishedStock;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The button that opened the window: gets focus back when it closes. */
    returnFocusTo?: RefObject<HTMLElement | null>;
};

type Typed = Record<number, string>;

/** What each field holds when the window opens: today's count, as text. */
function countsOf(stock: FinishedStock): Typed {
    return Object.fromEntries(
        stock.sizes.map(({ size, units }) => [size.id, String(units)]),
    );
}

function UpdateStockForm({
    product,
    stock,
    onClose,
}: {
    product: UpdateStockDialogProps['product'];
    stock: FinishedStock;
    onClose: () => void;
}) {
    const { locale } = useAppSettings();
    const uid = useId();
    const messageId = `${uid}-message`;
    // What was typed belongs to the sizes it was typed for: when the product's sizes
    // change under the window, the fields start again from the new counts (derived
    // here, so the form, and the message it shows, is never remounted).
    const basis = stock.sizes.map(({ size }) => size.id).join(',');
    const [edit, setEdit] = useState<{ basis: string; values: Typed } | null>(
        null,
    );
    const values = edit?.basis === basis ? edit.values : countsOf(stock);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [failed, setFailed] = useState(false);
    const [processing, setProcessing] = useState(false);
    // `sizes`: the sizes changed; `sizes.<size id>`: one count refused. Both come from the server.
    const message =
        errors.sizes ?? (failed ? "Couldn't save the stock. Try again." : null);
    // A size is changed when its text differs from today's count; it shows "was N".
    const changed = new Set(
        stock.sizes
            .filter(
                ({ size, units }) =>
                    (values[size.id] ?? '').trim() !== String(units),
            )
            .map(({ size }) => size.id),
    );
    const hints = Object.fromEntries(
        stock.sizes
            .filter(({ size }) => changed.has(size.id))
            .map(({ size, units }) => [
                size.id,
                `was ${formatCount(units, locale)}`,
            ]),
    );
    const total = stock.sizes.reduce(
        (sum, { size }) => sum + typedUnits(values[size.id]),
        0,
    );
    const sizeErrors = Object.fromEntries(
        stock.sizes.map(({ size }) => [size.id, errors[`sizes.${size.id}`]]),
    );

    function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        // Every size shown goes by its id; an empty field is sent empty and counts 0.
        router.put(
            ProductStockController.update.url(product.id),
            {
                sizes: Object.fromEntries(
                    stock.sizes.map(({ size }) => [
                        size.id,
                        values[size.id] ?? '',
                    ]),
                ),
            },
            {
                // Only the figure's data comes back; the rest of the page stays as it is.
                only: ['finished_stock'],
                preserveScroll: true,
                preserveState: true,
                onStart: () => {
                    setProcessing(true);
                    setErrors({});
                    setFailed(false);
                },
                onSuccess: onClose,
                onError: setErrors,
                // A server or network failure, not a refused number: keep the window and what was
                // typed. Returning false also stops Inertia's own error window.
                onHttpException: () => {
                    setFailed(true);

                    return false;
                },
                onNetworkError: () => {
                    setFailed(true);

                    return false;
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <form
            noValidate
            className="flex min-h-0 flex-col gap-4"
            onSubmit={save}
        >
            <DialogHeader className="shrink-0 pr-6">
                <DialogTitle>Update stock · {product.name}</DialogTitle>
            </DialogHeader>

            {/* The middle scrolls; the title and the buttons stay on screen. */}
            <div className="-mx-1 flex min-h-0 flex-col gap-4 overflow-y-auto px-1">
                {message && (
                    <p
                        id={messageId}
                        role="alert"
                        className="text-sm text-destructive-foreground"
                    >
                        {message}
                    </p>
                )}
                <OrderSizeFields
                    idPrefix={`${uid}-sizes`}
                    legend="Units in stock, by size"
                    sizes={stock.sizes.map(({ size }) => size)}
                    values={values}
                    onChange={(sizeId, value) =>
                        setEdit({
                            basis,
                            values: { ...values, [sizeId]: value },
                        })
                    }
                    errors={sizeErrors}
                    messageId={errors.sizes ? messageId : undefined}
                    showTotal={false}
                    hints={hints}
                    changed={changed}
                />
                <div className="flex items-center justify-between gap-2 rounded-xl bg-muted px-4 py-3.5 text-sm">
                    <span>Total after saving</span>{' '}
                    <strong className="text-base">
                        {formatUnits(total, locale)}
                    </strong>
                </div>
                <DialogDescription className="text-sm text-muted-foreground">
                    Doesn&apos;t change materials, &quot;Can produce now&quot;
                    or sales.
                </DialogDescription>
            </div>

            <DialogFooter className="shrink-0">
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Save
                </Button>
            </DialogFooter>
        </form>
    );
}

/**
 * "Update stock · Denim Jacket": a field per size holding today's count. "Save"
 * sets the counts to what is typed (an empty field counts 0) and reloads only
 * `finished_stock`. The server judges every number; its messages show under the
 * size, or at the top when the product's sizes changed (the fields then show the
 * new sizes). A failed save keeps the window and what was typed.
 */
export function UpdateStockDialog({
    product,
    stock,
    open,
    onOpenChange,
    returnFocusTo,
}: UpdateStockDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                // At most the screen's height: the form inside scrolls its middle. 16 px at the sides on a phone, so four size fields fit a row.
                className="flex max-h-[calc(100dvh-2rem)] flex-col px-4 sm:px-6"
                onCloseAutoFocus={(event) => {
                    if (returnFocusTo?.current) {
                        event.preventDefault();
                        returnFocusTo.current.focus();
                    }
                }}
            >
                <UpdateStockForm
                    product={product}
                    stock={stock}
                    onClose={() => onOpenChange(false)}
                />
            </DialogContent>
        </Dialog>
    );
}
