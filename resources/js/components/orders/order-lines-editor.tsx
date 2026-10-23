import { Plus } from 'lucide-react';
import { useEffect, useId, useRef } from 'react';
import {
    countingFor,
    newOrderLine,
    OrderLineRowFields,
} from '@/components/orders/order-line-row';
import type {
    LineCounting,
    OrderLineRow,
    StartedLine,
} from '@/components/orders/order-line-row';
import { Button } from '@/components/ui/button';
import type {
    OrderFormProduct,
    OrderLineSizeMode,
    SizeList,
} from '@/types/domain';

/** An order holds up to this many lines (the server enforces it too). */
export const MAX_ORDER_LINES = 50;

type OrderLinesEditorProps = {
    rows: OrderLineRow[];
    products: readonly OrderFormProduct[];
    /** The size lists by code: a product's list says how its line counts. */
    sizeLists: Readonly<Record<string, SizeList>>;
    /** How the saved order's lines count, by product id (Edit order): a product already on it keeps that way. */
    savedModes?: ReadonlyMap<number, OrderLineSizeMode>;
    /**
     * The form's errors by key: `lines` for the list as a whole and
     * `lines.N.product_id` / `lines.N.quantity` for a line (`N` counts from 0);
     * `lines.N.sizes` for the sizes of a line and `lines.N.sizes.<size id>` for one size.
     */
    errors: Record<string, string | undefined>;
    /** Saved lines that have started, by row `uid`: their product is fixed and they can't be removed. */
    started?: ReadonlyMap<string, StartedLine>;
    onChange: (rows: OrderLineRow[]) => void;
};

/**
 * The server's messages for line `index`, each under a field that is on screen.
 * A message whose field isn't shown (the product's sizes changed since the form
 * was built: a quantity on a line now counted by size, sizes on a "Quantity"
 * line, a size that is gone) moves under "Total" or "Quantity", so none is lost.
 */
function lineErrors(
    errors: Record<string, string | undefined>,
    index: number,
    counting: LineCounting,
) {
    const key = (name: string) => `lines.${index}.${name}`;
    const product_id = errors[key('product_id')];
    const sizeKeys = Object.keys(errors).filter(
        (name) => name.startsWith(key('sizes.')) && errors[name],
    );

    if (counting.kind === 'quantity') {
        const lost = [key('sizes'), ...sizeKeys].find((name) => errors[name]);

        return {
            product_id,
            quantity: errors[key('quantity')] ?? (lost && errors[lost]),
        };
    }

    const shown = new Set(
        counting.sizes.map((size) => key(`sizes.${size.id}`)),
    );
    const lost = sizeKeys.find((name) => !shown.has(name));

    return {
        product_id,
        sizes:
            errors[key('sizes')] ??
            errors[key('quantity')] ??
            (lost && errors[lost]),
        bySize: Object.fromEntries(
            counting.sizes.map((size) => [
                size.id,
                errors[key(`sizes.${size.id}`)],
            ]),
        ),
    };
}

/**
 * The "Products" section of the order form: one line per product with its
 * quantity. Nothing is checked here (duplicates, quantities): the server's
 * messages come back under the field they belong to.
 */
export function OrderLinesEditor({
    rows,
    products,
    sizeLists,
    savedModes,
    errors,
    started,
    onChange,
}: OrderLinesEditorProps) {
    const idPrefix = useId();
    const containerRef = useRef<HTMLElement>(null);
    // Where focus goes after the next render (an added or removed line moves it).
    const pendingFocus = useRef<string | null>(null);

    useEffect(() => {
        const selector = pendingFocus.current;

        if (selector === null) {
            return;
        }

        pendingFocus.current = null;
        containerRef.current?.querySelector<HTMLElement>(selector)?.focus();
    });

    function addLine() {
        const line = newOrderLine();

        pendingFocus.current = `[data-line-uid="${line.uid}"] [data-action="product"]`;
        onChange([...rows, line]);
    }

    function removeLine(index: number) {
        const neighbour = rows[index + 1] ?? rows[index - 1];

        pendingFocus.current = neighbour
            ? `[data-line-uid="${neighbour.uid}"] [data-action="product"]`
            : '[data-action="add-line"]';
        onChange(rows.filter((_, position) => position !== index));
    }

    const full = rows.length >= MAX_ORDER_LINES;
    const hintId = `${idPrefix}-limit`;

    return (
        <section
            ref={containerRef}
            aria-labelledby={`${idPrefix}-title`}
            className="flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <h2 id={`${idPrefix}-title`} className="text-base font-bold">
                Products
            </h2>

            {errors.lines && (
                <p role="alert" className="text-sm text-destructive-foreground">
                    {errors.lines}
                </p>
            )}

            {rows.length === 0 ? (
                <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                    No products yet. Add the products the client ordered.
                </p>
            ) : (
                <div className="flex flex-col gap-3">
                    {rows.map((row, index) => {
                        const counting = countingFor(
                            row,
                            products,
                            sizeLists,
                            savedModes,
                        );

                        return (
                            <OrderLineRowFields
                                key={row.uid}
                                row={row}
                                number={index + 1}
                                idPrefix={idPrefix}
                                products={products}
                                counting={counting}
                                errors={lineErrors(errors, index, counting)}
                                started={started?.get(row.uid)}
                                onChange={(patch) =>
                                    onChange(
                                        rows.map((other) =>
                                            other.uid === row.uid
                                                ? { ...other, ...patch }
                                                : other,
                                        ),
                                    )
                                }
                                onRemove={() => removeLine(index)}
                            />
                        );
                    })}
                </div>
            )}

            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    data-action="add-line"
                    className="border-dashed text-primary"
                    disabled={full}
                    aria-describedby={full ? hintId : undefined}
                    onClick={addLine}
                >
                    <Plus aria-hidden="true" />
                    Add product
                </Button>
                {full && (
                    <p id={hintId} className="text-xs text-muted-foreground">
                        An order can hold up to {MAX_ORDER_LINES} products.
                    </p>
                )}
            </div>
        </section>
    );
}
