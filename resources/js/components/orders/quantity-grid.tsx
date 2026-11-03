import { Lock, Trash2 } from 'lucide-react';
import { typedUnits } from '@/components/orders/order-size-fields';
import { formatUnits } from '@/components/orders/stage-figure';
import { variantName } from '@/components/restocks/restock-line';
import { ColourSwatch, ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Colour, OrderStockProduct, Size } from '@/types/domain';

/** What was typed on a line: colour id → size id → as typed. */
export type CellValues = Readonly<
    Record<number, Readonly<Record<number, string>>>
>;

/** The id of one quantity field: the form focuses it (the first one after Add to order, the first wrong one). */
export function orderFieldId(
    productId: number,
    colourId: number,
    sizeId: number,
): string {
    return `order-p${productId}-c${colourId}-s${sizeId}`;
}

/** The whole numbers typed in one colour (an empty or non-numeric field counts 0). */
export function colourTotal(
    colourId: number,
    sizes: readonly Size[],
    values: CellValues,
): number {
    return sizes.reduce(
        (sum, size) => sum + typedUnits(values[colourId]?.[size.id]),
        0,
    );
}

/** The whole numbers typed on a line. */
export function lineTotal(
    colours: readonly Colour[],
    sizes: readonly Size[],
    values: CellValues,
): number {
    return colours.reduce(
        (sum, colour) => sum + colourTotal(colour.id, sizes, values),
        0,
    );
}

/** The grid's rows: every colour of the product, then a colour the line already holds that the product no longer has. */
export function gridColours(
    product: OrderStockProduct,
    held: readonly Colour[] = [],
): Colour[] {
    const colours = product.stock.colours.map((entry) => entry.colour);
    const known = new Set(colours.map((colour) => colour.id));

    return [...colours, ...held.filter((colour) => !known.has(colour.id))];
}

/** "In stock 2" (the colour's figure for a "One size" line), or 0 when the product doesn't have that colour any more. */
function shelf(
    product: OrderStockProduct,
    colourId: number,
    sizeId: number,
): number {
    const entry = product.stock.colours.find(
        (stock) => stock.colour.id === colourId,
    );

    if (!entry) {
        return 0;
    }

    return product.one_size
        ? entry.units
        : (entry.sizes.find((stock) => stock.size.id === sizeId)?.units ?? 0);
}

type QuantityGridProps = {
    product: OrderStockProduct;
    /** Colours the saved line holds (Edit order), so one taken off the product since still shows. */
    heldColours?: readonly Colour[];
    values: CellValues;
    onChange: (colourId: number, sizeId: number, value: string) => void;
    onRemove: () => void;
    /** Units already delivered or cancelled per cell (`${colourId}.${sizeId}`), the lowest each can go ("At least 10"; INV-14). */
    floors?: Readonly<Record<string, number>>;
    /** Units delivered or cancelled on the whole line: it can't be removed (INV-14). */
    started?: number;
    /** The server's message about the line, under its total. */
    lineError?: string;
    /** The server's messages for its fields, by `${colourId}.${sizeId}`. */
    cellErrors?: Readonly<Record<string, string>>;
};

/**
 * One product on the order (INV-13, AC-2): its name, SKU and Remove, then the
 * table "Quantity per colour and size": a field per colour and size with the
 * shelf under it ("In stock 2", or the amber "Out of stock"), a Total per
 * colour and "Total 12 units" under it. A "One size" product has the columns
 * Colour · Quantity. On a phone, a band per colour with its sizes three to a
 * row. Out of stock never blocks; nothing is checked here.
 */
export function QuantityGrid({
    product,
    heldColours = [],
    values,
    onChange,
    onRemove,
    floors = {},
    started = 0,
    lineError,
    cellErrors = {},
}: QuantityGridProps) {
    const { locale } = useAppSettings();
    const mobile = useIsMobile();
    const name = product.product.name;
    const colours = gridColours(product, heldColours);
    const total = lineTotal(colours, product.sizes, values);
    const caption = `Quantity per colour and size · ${name}`;
    const removeHintId = `order-p${product.product.id}-remove-hint`;
    const lineErrorId = `order-p${product.product.id}-error`;

    const cell = (colour: Colour, size: Size, showLabel: boolean) => {
        const id = orderFieldId(product.product.id, colour.id, size.id);
        const key = `${colour.id}.${size.id}`;
        const error = cellErrors[key];
        const floor = floors[key];
        const onShelf = shelf(product, colour.id, size.id);
        const describedBy = [
            `${id}-stock`,
            floor ? `${id}-floor` : null,
            error ? `${id}-error` : null,
            lineError ? lineErrorId : null,
        ]
            .filter(Boolean)
            .join(' ');

        return (
            <div
                key={size.id}
                className="flex min-w-0 flex-col items-center gap-1"
            >
                <Label
                    htmlFor={id}
                    className={
                        showLabel
                            ? 'text-[13px] leading-none font-bold'
                            : 'sr-only'
                    }
                >
                    {showLabel
                        ? size.label
                        : variantName(colour.name, size, product.one_size)}
                </Label>
                <Input
                    id={id}
                    type="text"
                    inputMode="numeric"
                    autoComplete="off"
                    maxLength={7}
                    className="h-11 w-full min-w-16 px-0 text-center tabular-nums md:px-2"
                    aria-invalid={error || lineError ? true : undefined}
                    aria-describedby={describedBy}
                    value={values[colour.id]?.[size.id] ?? ''}
                    onChange={(event) =>
                        onChange(colour.id, size.id, event.target.value)
                    }
                />
                <span
                    id={`${id}-stock`}
                    className={cn(
                        'text-center text-xs whitespace-nowrap',
                        onShelf === 0
                            ? 'font-bold text-amber-700 dark:text-amber-400'
                            : 'text-muted-foreground',
                    )}
                >
                    {onShelf === 0
                        ? 'Out of stock'
                        : `In stock ${formatCount(onShelf, locale)}`}
                </span>
                {floor ? (
                    <span
                        id={`${id}-floor`}
                        className="inline-flex items-center gap-1 text-xs whitespace-nowrap text-muted-foreground"
                    >
                        <Lock aria-hidden="true" className="size-3 shrink-0" />
                        At least {formatCount(floor, locale)}
                    </span>
                ) : null}
            </div>
        );
    };

    let grid;

    if (mobile) {
        grid = (
            <div className="flex flex-col gap-3">
                {colours.map((colour) => (
                    <fieldset
                        key={colour.id}
                        className="flex min-w-0 flex-col gap-2"
                    >
                        <legend className="flex w-full items-center justify-between gap-2 rounded-md bg-muted px-2 py-1.5 text-sm font-medium">
                            <span className="inline-flex min-w-0 items-center gap-2">
                                <ColourSwatch swatch={colour.swatch} />
                                <span className="[overflow-wrap:anywhere]">
                                    {colour.name}
                                </span>
                            </span>
                            <span className="text-muted-foreground tabular-nums">
                                {formatCount(
                                    colourTotal(
                                        colour.id,
                                        product.sizes,
                                        values,
                                    ),
                                    locale,
                                )}
                            </span>
                        </legend>
                        <div className="grid grid-cols-3 items-start gap-x-2 gap-y-3">
                            {product.sizes.map((size) =>
                                cell(colour, size, !product.one_size),
                            )}
                        </div>
                    </fieldset>
                ))}
            </div>
        );
    } else {
        grid = (
            <div
                role="region"
                aria-label={caption}
                tabIndex={0}
                className="overflow-x-auto rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring"
            >
                <table className="w-full min-w-max border-collapse text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th scope="col" className="py-2 pr-3 font-medium">
                                Colour
                            </th>
                            {product.one_size ? (
                                <th
                                    scope="col"
                                    className="px-1 py-2 text-center font-medium"
                                >
                                    Quantity
                                </th>
                            ) : (
                                product.sizes.map((size) => (
                                    <th
                                        key={size.id}
                                        scope="col"
                                        className="px-1 py-2 text-center font-medium"
                                    >
                                        {size.label}
                                    </th>
                                ))
                            )}
                            {!product.one_size && (
                                <th
                                    scope="col"
                                    className="py-2 pl-3 text-right font-medium"
                                >
                                    Total
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {colours.map((colour) => (
                            <tr
                                key={colour.id}
                                className="border-b last:border-b-0"
                            >
                                <th
                                    scope="row"
                                    className="py-2 pr-3 text-left align-top font-normal"
                                >
                                    <ColourTag
                                        colour={colour}
                                        className="mt-3"
                                    />
                                </th>
                                {product.sizes.map((size) => (
                                    <td
                                        key={size.id}
                                        className="w-24 px-1 py-2 align-top"
                                    >
                                        {cell(colour, size, false)}
                                    </td>
                                ))}
                                {!product.one_size && (
                                    <td className="py-2 pl-3 text-right align-top font-mono tabular-nums">
                                        <span className="mt-3 inline-block">
                                            {formatCount(
                                                colourTotal(
                                                    colour.id,
                                                    product.sizes,
                                                    values,
                                                ),
                                                locale,
                                            )}
                                        </span>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        );
    }

    const messages = Object.entries(cellErrors);

    return (
        <section
            aria-label={name}
            data-order-product={product.product.id}
            className="flex min-w-0 flex-col gap-3 rounded-xl border p-4"
        >
            <div className="flex items-start gap-3">
                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                    <h3 className="font-semibold [overflow-wrap:anywhere]">
                        {name}
                    </h3>
                    <p className="font-mono text-sm text-muted-foreground">
                        {product.product.sku}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size={mobile ? 'icon' : 'default'}
                    aria-label={`Remove ${name} from this order`}
                    disabled={started > 0}
                    aria-describedby={started > 0 ? removeHintId : undefined}
                    className="shrink-0 text-destructive-foreground hover:text-destructive-foreground max-sm:size-11"
                    onClick={onRemove}
                >
                    <Trash2 aria-hidden="true" />
                    {!mobile && 'Remove'}
                </Button>
            </div>
            {started > 0 && (
                <p id={removeHintId} className="text-xs text-muted-foreground">
                    {name} can't be removed:{' '}
                    {started === 1
                        ? '1 unit is'
                        : `${formatCount(started, locale)} units are`}{' '}
                    delivered or cancelled.
                </p>
            )}

            {grid}

            <div className="flex flex-col gap-1">
                <p className="text-sm font-medium">
                    Total {formatUnits(total, locale)}
                </p>
                {lineError && (
                    <p
                        id={lineErrorId}
                        className="text-sm text-destructive-foreground"
                    >
                        {lineError}
                    </p>
                )}
                {messages.map(([key, message]) => {
                    const [colourId, sizeId] = key.split('.').map(Number);

                    return (
                        <p
                            key={key}
                            id={`${orderFieldId(product.product.id, colourId, sizeId)}-error`}
                            className="text-sm text-destructive-foreground"
                        >
                            {message}
                        </p>
                    );
                })}
            </div>
        </section>
    );
}
