import { router } from '@inertiajs/react';
import { useId, useState } from 'react';
import type { FormEvent, RefObject } from 'react';
import { flushSync } from 'react-dom';
import ProductStockController from '@/actions/App/Http/Controllers/ProductStockController';
import { OrderSizeFields } from '@/components/orders/order-size-fields';
import { formatUnits } from '@/components/orders/stage-figure';
import {
    ColourFields,
    ColourPills,
    changedSizes,
    countsOf,
    fieldId,
    typedTotal,
} from '@/components/products/finished-stock-fields';
import type {
    PillState,
    Typed,
} from '@/components/products/finished-stock-fields';
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
    product: Pick<Product, 'id' | 'name' | 'one_size'>;
    /** Today's count per colour and size; follows the reload of `finished_stock`. */
    stock: FinishedStock;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** The button that opened the window: gets focus back when it closes. */
    returnFocusTo?: RefObject<HTMLElement | null>;
};

/** "1 size can't be saved", "2 sizes can't be saved". */
function cantBeSaved(count: number): string {
    return `${count} ${count === 1 ? 'size' : 'sizes'} can't be saved`;
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
    const { colours } = stock;
    // "One size" is the workshop's flagged size, not any product with a single size.
    const oneSize = product.one_size;
    const oneColour = colours.length === 1;
    // What was typed belongs to the colours and sizes it was typed for: when the
    // product's colours or sizes change under the window, the fields start again
    // from the new counts (derived here, so the form, and the message it shows, is
    // never remounted).
    const basis = colours
        .map(
            ({ colour, sizes }) =>
                `${colour.id}:${sizes.map(({ size }) => size.id).join('.')}`,
        )
        .join(',');
    const [edit, setEdit] = useState<{ basis: string; values: Typed } | null>(
        null,
    );
    const values = edit?.basis === basis ? edit.values : countsOf(stock);
    const [activeId, setActiveId] = useState<number | null>(null);
    // The first colour when the window opens, and when the shown one is gone.
    const active =
        colours.find(({ colour }) => colour.id === activeId) ?? colours[0];
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [failed, setFailed] = useState(false);
    const [processing, setProcessing] = useState(false);

    // `stock`: the colours or sizes changed; `stock.<colour id>.<size id>`: one count refused.
    const sizeError = (colourId: number, sizeId: number) =>
        errors[`stock.${colourId}.${sizeId}`];
    const states = Object.fromEntries(
        colours.map((entry) => [
            entry.colour.id,
            {
                changed: changedSizes(entry, values).size > 0,
                errors: entry.sizes.filter(({ size }) =>
                    sizeError(entry.colour.id, size.id),
                ).length,
                total: typedTotal(entry, values),
            } satisfies PillState,
        ]),
    );
    // "Check Black: 1 size can't be saved.": one sentence per colour with refused numbers, in list order.
    // With one colour (or one size) the message under the field is the only one.
    const checks =
        oneSize || oneColour
            ? []
            : colours
                  .filter(({ colour }) => states[colour.id].errors > 0)
                  .map(
                      ({ colour }) =>
                          `Check ${colour.name}: ${cantBeSaved(states[colour.id].errors)}.`,
                  );
    const messages = [
        ...(errors.stock ? [errors.stock] : []),
        ...checks,
        ...(failed ? ["Couldn't save the stock. Try again."] : []),
    ];
    // A size is changed when its text differs from today's count; it shows "was N".
    const changed = changedSizes(active, values);
    const hintsOf = (entry: typeof active) =>
        Object.fromEntries(
            entry.sizes
                .filter(({ size }) => changedSizes(entry, values).has(size.id))
                .map(({ size, units }) => [
                    size.id,
                    `was ${formatCount(units, locale)}`,
                ]),
        );
    const total = colours.reduce(
        (sum, entry) => sum + states[entry.colour.id].total,
        0,
    );

    function type(colourId: number, sizeId: number, value: string) {
        setEdit({
            basis,
            values: {
                ...values,
                [colourId]: { ...values[colourId], [sizeId]: value },
            },
        });
    }

    // After a refusal: the first colour with a refused number (list order) comes
    // forward and its first refused size takes the focus.
    function showFirstError(refused: Record<string, string>) {
        for (const { colour, sizes } of colours) {
            const first = sizes.find(
                ({ size }) => refused[`stock.${colour.id}.${size.id}`],
            );

            if (first) {
                flushSync(() => {
                    setErrors(refused);
                    setActiveId(colour.id);
                });
                document
                    .getElementById(fieldId(uid, colour.id, first.size.id))
                    ?.focus();

                return;
            }
        }

        setErrors(refused);
    }

    function save(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        // Every colour and size shown goes by its ids; an empty field is sent empty and counts 0.
        router.put(
            ProductStockController.update.url(product.id),
            {
                stock: Object.fromEntries(
                    colours.map(({ colour, sizes }) => [
                        colour.id,
                        Object.fromEntries(
                            sizes.map(({ size }) => [
                                size.id,
                                values[colour.id]?.[size.id] ?? '',
                            ]),
                        ),
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
                onError: showFirstError,
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
            className="flex min-h-0 flex-1 flex-col gap-4"
            onSubmit={save}
        >
            <DialogHeader className="shrink-0 pr-6">
                <DialogTitle>Update stock · {product.name}</DialogTitle>
                <DialogDescription className="text-sm text-muted-foreground">
                    Finished units on the shelf, counted per colour and size.
                    Saving sets these counts; to add units that arrived, use
                    Restock.
                </DialogDescription>
            </DialogHeader>

            {/* The middle scrolls; the title and the buttons stay on screen. */}
            <div className="-mx-1 flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-1">
                {messages.length > 0 && (
                    <div
                        id={messageId}
                        role="alert"
                        className="flex flex-col gap-1 text-sm text-destructive-foreground"
                    >
                        {messages.map((message) => (
                            <p key={message}>{message}</p>
                        ))}
                    </div>
                )}
                {oneSize ? (
                    <ColourFields
                        uid={uid}
                        colours={colours}
                        values={values}
                        onChange={type}
                        errors={errors}
                        hints={Object.fromEntries(
                            colours.flatMap((entry) => {
                                const hint =
                                    hintsOf(entry)[entry.sizes[0].size.id];

                                return hint ? [[entry.colour.id, hint]] : [];
                            }),
                        )}
                        messageId={errors.stock ? messageId : undefined}
                    />
                ) : (
                    <>
                        {!oneColour && (
                            <ColourPills
                                name={`${uid}-colour`}
                                colours={colours}
                                states={states}
                                activeId={active.colour.id}
                                onSelect={setActiveId}
                            />
                        )}
                        <OrderSizeFields
                            idPrefix={`${uid}-c${active.colour.id}`}
                            legend={`Units in stock per size · ${active.colour.name}`}
                            showLegend
                            sizes={active.sizes.map(({ size }) => size)}
                            values={values[active.colour.id] ?? {}}
                            onChange={(sizeId, value) =>
                                type(active.colour.id, sizeId, value)
                            }
                            errors={Object.fromEntries(
                                active.sizes.map(({ size }) => [
                                    size.id,
                                    sizeError(active.colour.id, size.id),
                                ]),
                            )}
                            messageId={errors.stock ? messageId : undefined}
                            showTotal={false}
                            hints={hintsOf(active)}
                            changed={changed}
                        />
                    </>
                )}
                <div className="flex items-center justify-between gap-2 rounded-xl bg-muted px-4 py-3.5 text-sm">
                    <span>
                        {oneColour
                            ? 'Total after saving'
                            : 'Total after saving, all colours'}
                    </span>{' '}
                    <strong className="text-base">
                        {formatUnits(total, locale)}
                    </strong>
                </div>
                <p className="text-sm text-muted-foreground">
                    Doesn&apos;t change materials, &quot;Can produce now&quot;
                    or sales.
                </p>
            </div>

            <DialogFooter className="shrink-0 max-sm:-mx-4 max-sm:border-t max-sm:px-4 max-sm:pt-3">
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
 * "Update stock · Denim Jacket": colour pills over a field per size holding
 * today's count (a "One size" product has a field per colour instead). "Save"
 * sets every colour's counts to what is typed (an empty field counts 0) and
 * reloads only `finished_stock`. The server judges every number; its messages
 * show under the size (and the window brings the first colour with one forward),
 * or at the top when the product's sizes or colours changed (the fields then show
 * the new ones). A failed save keeps the window and what was typed. On a phone
 * the window fills the screen.
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
                className="flex max-h-[calc(100dvh-2rem)] flex-col px-4 max-sm:top-0 max-sm:left-0 max-sm:h-dvh max-sm:max-h-none max-sm:max-w-none max-sm:translate-x-0 max-sm:translate-y-0 max-sm:rounded-none sm:px-6"
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
