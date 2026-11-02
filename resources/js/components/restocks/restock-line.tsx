import { X } from 'lucide-react';
import { typedUnits } from '@/components/orders/order-size-fields';
import type { Typed } from '@/components/products/finished-stock-fields';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { FinishedStockColour, RestockLine, Size } from '@/types/domain';

/** The id of one field of a line: the page focuses it (the first one, the first wrong one, a scanned one). */
export function restockFieldId(
    productId: number,
    colourId: number,
    sizeId: number,
): string {
    return `restock-p${productId}-c${colourId}-s${sizeId}`;
}

/** The whole numbers typed in a colour (an empty field counts 0). */
export function colourAdding(
    colourId: number,
    sizes: Size[],
    values: Typed,
): number {
    return sizes.reduce(
        (sum, size) => sum + typedUnits(values[colourId]?.[size.id]),
        0,
    );
}

/** The whole numbers typed in a line. */
export function lineAdding(line: RestockLine, values: Typed): number {
    return line.stock.colours.reduce(
        (sum, { colour }) => sum + colourAdding(colour.id, line.sizes, values),
        0,
    );
}

/** "Indigo M", "Black, size 38", "Camel": how a field is named to screen readers, as the server names it in messages. */
export function variantName(
    colour: string,
    size: Size,
    oneSize: boolean,
): string {
    if (oneSize) {
        return colour;
    }

    return /^\d+$/.test(size.label)
        ? `${colour}, size ${size.label}`
        : `${colour} ${size.label}`;
}

type RestockLineCardProps = {
    line: RestockLine;
    values: Typed;
    onChange: (colourId: number, sizeId: number, value: string) => void;
    onRemove: () => void;
    /** The server's messages for the fields, by `${colourId}.${sizeId}`. */
    fieldErrors: Readonly<Record<string, string>>;
    /** The server's message about the line ("Enter at least one unit of …"), under its table. */
    lineError?: string;
    /** The field the last scan added to: ringed in Denim until the next scan. */
    scanned?: { colourId: number; sizeId: number } | null;
};

type CellProps = {
    line: RestockLine;
    entry: FinishedStockColour;
    size: Size;
    index: number;
    values: Typed;
    onChange: RestockLineCardProps['onChange'];
    fieldErrors: RestockLineCardProps['fieldErrors'];
    scanned: RestockLineCardProps['scanned'];
    /** Show the size above the field (the phone's groups); the table's head names it otherwise. */
    showLabel: boolean;
};

/** One field: empty at first, "now 2" in grey under it, the Denim border once typed. */
function Cell({
    line,
    entry,
    size,
    index,
    values,
    onChange,
    fieldErrors,
    scanned,
    showLabel,
}: CellProps) {
    const { locale } = useAppSettings();
    const { colour } = entry;
    const id = restockFieldId(line.product.id, colour.id, size.id);
    const value = values[colour.id]?.[size.id] ?? '';
    const error = fieldErrors[`${colour.id}.${size.id}`];
    const isScanned =
        scanned?.colourId === colour.id && scanned.sizeId === size.id;
    const now = entry.sizes[index]?.units ?? 0;

    return (
        <div className="flex min-w-0 flex-col items-center gap-1">
            <Label
                htmlFor={id}
                className={
                    showLabel ? 'text-[13px] leading-none font-bold' : 'sr-only'
                }
            >
                {showLabel
                    ? size.label
                    : variantName(colour.name, size, line.one_size)}
            </Label>
            <Input
                id={id}
                type="text"
                inputMode="numeric"
                autoComplete="off"
                maxLength={7}
                className={cn(
                    'h-11 w-full min-w-16 px-0 text-center tabular-nums md:px-2',
                    value.trim() !== '' &&
                        'border-primary ring-[3px] ring-primary/15',
                    isScanned && 'border-primary ring-[3px] ring-primary/40',
                )}
                aria-invalid={error ? true : undefined}
                aria-describedby={`${id}-now${error ? ` ${id}-error` : ''}`}
                value={value}
                onChange={(event) =>
                    onChange(colour.id, size.id, event.target.value)
                }
            />
            <p
                id={`${id}-now`}
                className="text-xs text-muted-foreground tabular-nums"
            >
                now {formatCount(now, locale)}
            </p>
        </div>
    );
}

/**
 * One product on New restock (INV-3, AC-3): its name, SKU and sizes, "Adding
 * 24 units" and ✕ to remove it, then a field per colour and size with what is
 * on the shelf ("now 2") under it. On a laptop a table (Colour, one column per
 * size, Adding), which scrolls sideways inside the card when it is too wide; a
 * "One size" product has one field per colour. On a phone, one group per
 * colour with its sizes four to a row. The server's messages for the line go
 * under it, each starting with its variant ("Indigo M: …").
 */
export function RestockLineCard({
    line,
    values,
    onChange,
    onRemove,
    fieldErrors,
    lineError,
    scanned = null,
}: RestockLineCardProps) {
    const { locale } = useAppSettings();
    const mobile = useIsMobile();
    const adding = lineAdding(line, values);
    const sizesText = line.one_size
        ? 'One size'
        : `Sizes ${line.sizes.map((size) => size.label).join(', ')}`;
    const caption = `Units to add · ${line.product.name}`;
    const messages = Object.keys(fieldErrors);
    const cell = (
        entry: FinishedStockColour,
        size: Size,
        index: number,
        showLabel: boolean,
    ) => (
        <Cell
            key={size.id}
            line={line}
            entry={entry}
            size={size}
            index={index}
            values={values}
            onChange={onChange}
            fieldErrors={fieldErrors}
            scanned={scanned}
            showLabel={showLabel}
        />
    );

    let grid;

    if (mobile) {
        grid = (
            <div className="flex flex-col gap-4">
                {line.stock.colours.map((entry) => (
                    <fieldset
                        key={entry.colour.id}
                        className="flex min-w-0 flex-col gap-2"
                    >
                        <legend className="flex w-full items-center justify-between gap-2 text-sm font-medium">
                            <ColourTag colour={entry.colour} />
                            {!line.one_size && (
                                <span className="text-muted-foreground tabular-nums">
                                    Adding{' '}
                                    {formatCount(
                                        colourAdding(
                                            entry.colour.id,
                                            line.sizes,
                                            values,
                                        ),
                                        locale,
                                    )}
                                </span>
                            )}
                        </legend>
                        <div className="grid grid-cols-4 items-start gap-x-1 gap-y-2">
                            {line.sizes.map((size, index) =>
                                cell(entry, size, index, !line.one_size),
                            )}
                        </div>
                    </fieldset>
                ))}
            </div>
        );
    } else {
        grid = (
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <caption className="sr-only">{caption}</caption>
                    <thead>
                        <tr className="border-b text-left">
                            <th scope="col" className="py-2 pr-3 font-medium">
                                Colour
                            </th>
                            {line.one_size ? (
                                <th
                                    scope="col"
                                    className="px-1 py-2 text-center font-medium"
                                >
                                    Units to add
                                </th>
                            ) : (
                                line.sizes.map((size) => (
                                    <th
                                        key={size.id}
                                        scope="col"
                                        className="px-1 py-2 text-center font-medium"
                                    >
                                        {size.label}
                                    </th>
                                ))
                            )}
                            {!line.one_size && (
                                <th
                                    scope="col"
                                    className="py-2 pl-3 text-right font-medium"
                                >
                                    Adding
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {line.stock.colours.map((entry) => {
                            const colourTotal = colourAdding(
                                entry.colour.id,
                                line.sizes,
                                values,
                            );

                            return (
                                <tr
                                    key={entry.colour.id}
                                    className="border-b last:border-b-0"
                                >
                                    <th
                                        scope="row"
                                        className="py-2 pr-3 text-left font-normal"
                                    >
                                        <ColourTag colour={entry.colour} />
                                    </th>
                                    {line.sizes.map((size, index) => (
                                        <td
                                            key={size.id}
                                            className="w-20 px-1 py-2 align-top"
                                        >
                                            {cell(entry, size, index, false)}
                                        </td>
                                    ))}
                                    {!line.one_size && (
                                        <td className="py-2 pl-3 text-right font-mono tabular-nums">
                                            {colourTotal > 0
                                                ? formatCount(
                                                      colourTotal,
                                                      locale,
                                                  )
                                                : '—'}
                                        </td>
                                    )}
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        );
    }

    return (
        <section
            aria-label={line.product.name}
            className="flex min-w-0 flex-col gap-3 rounded-xl border bg-card p-4 text-card-foreground"
        >
            <div className="flex items-start gap-3">
                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                    <h3 className="truncate font-semibold">
                        {line.product.name}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        <span className="font-mono">{line.product.sku}</span> ·{' '}
                        {sizesText}
                    </p>
                </div>
                <p className="shrink-0 text-sm text-muted-foreground">
                    Adding{' '}
                    <span className="font-semibold text-foreground tabular-nums">
                        {formatCount(adding, locale)}
                    </span>{' '}
                    {adding === 1 ? 'unit' : 'units'}
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label={`Remove ${line.product.name} from this restock`}
                    className="-mt-1 shrink-0 max-sm:size-11"
                    onClick={onRemove}
                >
                    <X aria-hidden="true" />
                </Button>
            </div>

            {grid}

            {(messages.length > 0 || lineError) && (
                <div className="flex flex-col gap-1">
                    {lineError && (
                        <p className="text-sm text-destructive-foreground">
                            {lineError}
                        </p>
                    )}
                    {Object.entries(fieldErrors).map(([key, message]) => {
                        const [colourId, sizeId] = key.split('.').map(Number);

                        return (
                            <p
                                key={key}
                                id={`${restockFieldId(line.product.id, colourId, sizeId)}-error`}
                                className="text-sm text-destructive-foreground"
                            >
                                {message}
                            </p>
                        );
                    })}
                </div>
            )}
        </section>
    );
}
