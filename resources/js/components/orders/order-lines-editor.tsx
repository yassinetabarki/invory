import { useEffect, useId, useRef } from 'react';
import { ProductSearch } from '@/components/orders/product-search';
import {
    gridColours,
    lineTotal,
    orderFieldId,
    QuantityGrid,
} from '@/components/orders/quantity-grid';
import { formatUnits } from '@/components/orders/stage-figure';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type { Colour, OrderStockProduct } from '@/types/domain';

/** An order holds up to this many products (the server enforces it too). */
export const MAX_ORDER_LINES = 50;

/**
 * A product on the order in the form: what the grid shows and what was typed,
 * with a client-only `uid` so React keeps a line's values when another goes.
 */
export type OrderLineEntry = {
    uid: string;
    product: OrderStockProduct;
    /** Colour id → size id → as typed. */
    units: Record<number, Record<number, string>>;
    /** Edit order: colours the saved line holds, units started per cell and on the whole line. */
    heldColours?: Colour[];
    floors?: Record<string, number>;
    started?: number;
};

let lineCounter = 0;

/** A new line for this product, with a key that is unique on the page. */
export function newOrderLine(
    product: OrderStockProduct,
    fields: Partial<Omit<OrderLineEntry, 'uid' | 'product'>> = {},
): OrderLineEntry {
    lineCounter += 1;

    return { uid: `line-${lineCounter}`, product, units: {}, ...fields };
}

/** "22 units · 2 products". */
export function orderSummary(
    units: number,
    products: number,
    locale: string,
): string {
    return `${formatUnits(units, locale)} · ${formatCount(products, locale)} ${products === 1 ? 'product' : 'products'}`;
}

/** The units typed on every line. */
export function orderUnits(entries: readonly OrderLineEntry[]): number {
    return entries.reduce(
        (sum, entry) =>
            sum +
            lineTotal(
                gridColours(entry.product, entry.heldColours),
                entry.product.sizes,
                entry.units,
            ),
        0,
    );
}

/** The server's messages for line `index`: the line's own (`lines.N.units`, `lines.N.product_id`) and one per field. */
function lineErrors(errors: Record<string, string | undefined>, index: number) {
    const prefix = `lines.${index}.units.`;
    const cells: Record<string, string> = {};

    for (const [key, message] of Object.entries(errors)) {
        if (message && key.startsWith(prefix)) {
            cells[key.slice(prefix.length)] = message;
        }
    }

    return {
        line:
            errors[`lines.${index}.units`] ??
            errors[`lines.${index}.product_id`],
        cells,
    };
}

type OrderLinesEditorProps = {
    entries: OrderLineEntry[];
    /** The form's errors: `lines`, `lines.N.product_id`, `lines.N.units`, `lines.N.units.<colour id>.<size id>`. */
    errors: Record<string, string | undefined>;
    onChange: (entries: OrderLineEntry[]) => void;
};

/**
 * The Products card of New order and Edit order (INV-13, AC-1, AC-2): Search
 * products, then "On this order" with a quantity grid per product, and the
 * band "Total on this order · 22 units · 2 products". Nothing is checked here:
 * the server's messages come back under the field they belong to.
 */
export function OrderLinesEditor({
    entries,
    errors,
    onChange,
}: OrderLinesEditorProps) {
    const { locale } = useAppSettings();
    const id = useId();
    const containerRef = useRef<HTMLElement>(null);
    // Where focus goes after the next render (an added or removed product moves it).
    const pendingFocus = useRef<string | null>(null);

    useEffect(() => {
        const selector = pendingFocus.current;

        if (selector === null) {
            return;
        }

        pendingFocus.current = null;
        containerRef.current?.querySelector<HTMLElement>(selector)?.focus();
    });

    const onOrder = new Set(entries.map((entry) => entry.product.product.id));
    const full = entries.length >= MAX_ORDER_LINES;

    function firstField(product: OrderStockProduct, held?: Colour[]): string {
        const colour = gridColours(product, held)[0];
        const size = product.sizes[0];

        return colour && size
            ? `#${orderFieldId(product.product.id, colour.id, size.id)}`
            : `[data-order-product="${product.product.id}"]`;
    }

    function add(product: OrderStockProduct) {
        if (onOrder.has(product.product.id) || full) {
            return;
        }

        pendingFocus.current = firstField(product);
        onChange([...entries, newOrderLine(product)]);
    }

    function showLine(productId: number) {
        const entry = entries.find(
            (line) => line.product.product.id === productId,
        );

        if (entry) {
            containerRef.current
                ?.querySelector<HTMLElement>(
                    firstField(entry.product, entry.heldColours),
                )
                ?.focus();
        }
    }

    function remove(index: number) {
        pendingFocus.current = '[data-action="product"]';
        onChange(entries.filter((_, position) => position !== index));
    }

    const units = orderUnits(entries);

    return (
        <section
            ref={containerRef}
            aria-labelledby={`${id}-title`}
            className="flex flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground"
        >
            <div className="flex flex-col gap-1">
                <h2 id={`${id}-title`} className="text-base font-bold">
                    Products
                </h2>
                <p className="text-sm text-muted-foreground">
                    Search what the client wants, check what is in stock, then
                    add it.
                </p>
            </div>

            {errors.lines && (
                <p role="alert" className="text-sm text-destructive-foreground">
                    {errors.lines}
                </p>
            )}

            <ProductSearch
                onOrder={onOrder}
                full={full}
                onAdd={add}
                onShowLine={showLine}
            />

            {entries.length === 0 ? (
                <div className="rounded-lg border border-dashed px-4 py-6 text-center text-sm">
                    <p className="font-medium">No products on this order yet</p>
                    <p className="text-muted-foreground">
                        Search for a product above and add what the client
                        ordered.
                    </p>
                </div>
            ) : (
                <div className="flex flex-col gap-3">
                    <h3 className="text-sm font-semibold">
                        On this order · {formatCount(entries.length, locale)}{' '}
                        {entries.length === 1 ? 'product' : 'products'}
                    </h3>
                    {entries.map((entry, index) => {
                        const lineError = lineErrors(errors, index);

                        return (
                            <QuantityGrid
                                key={entry.uid}
                                product={entry.product}
                                heldColours={entry.heldColours}
                                values={entry.units}
                                floors={entry.floors}
                                started={entry.started}
                                lineError={lineError.line}
                                cellErrors={lineError.cells}
                                onRemove={() => remove(index)}
                                onChange={(colourId, sizeId, value) =>
                                    onChange(
                                        entries.map((other) =>
                                            other.uid === entry.uid
                                                ? {
                                                      ...other,
                                                      units: {
                                                          ...other.units,
                                                          [colourId]: {
                                                              ...other.units[
                                                                  colourId
                                                              ],
                                                              [sizeId]: value,
                                                          },
                                                      },
                                                  }
                                                : other,
                                        ),
                                    )
                                }
                            />
                        );
                    })}
                    <p className="rounded-lg bg-muted px-4 py-3 text-sm font-medium">
                        Total on this order ·{' '}
                        {orderSummary(units, entries.length, locale)}
                    </p>
                </div>
            )}
        </section>
    );
}
