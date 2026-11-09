import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import {
    cellValue,
    countFilledCells,
    withCell,
} from '@/components/products/bom-rows';
import type { CellError, QuantityGrid } from '@/components/products/bom-rows';
import {
    compareDecimals,
    isFilledQuantity,
} from '@/components/products/decimals';
import { DecimalInput } from '@/components/products/decimal-input';
import { ColourTag } from '@/components/shared/colour-tag';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { Colour, SizeOption } from '@/types/domain';

type QuantityGridSheetProps = {
    /** The material's name. */
    title: string;
    /** The material's unit ("m"). */
    unit: string;
    /** The colours ticked on the form, in list order. */
    colours: readonly Colour[];
    /** The sizes pressed on the form, in list order. */
    sizes: readonly SizeOption[];
    grid: QuantityGrid;
    /** Server errors on this line's cells. */
    errors: readonly CellError[];
    /** View grid: the same panel with the values shown and none editable. */
    readOnly?: boolean;
    onChange?: (grid: QuantityGrid) => void;
    onClose: () => void;
};

/** What a closed colour row says: "Not used", "4 of 4 sizes · 1.8000 m" or "4 of 4 sizes · 1.6000–2.2000 m". */
function rowSummary(
    values: readonly string[],
    sizeCount: number,
    unit: string,
    oneSize: boolean,
): string {
    const filled = values.filter(isFilledQuantity).map((v) => v.trim());

    if (filled.length === 0) {
        return 'Not used';
    }

    const lowest = filled.reduce((a, b) => (compareDecimals(b, a) < 0 ? b : a));
    const highest = filled.reduce((a, b) =>
        compareDecimals(b, a) > 0 ? b : a,
    );
    const range =
        compareDecimals(lowest, highest) === 0
            ? lowest
            : `${lowest}–${highest}`;

    return oneSize
        ? `${range} ${unit}`
        : `${filled.length} of ${sizeCount} sizes · ${range} ${unit}`;
}

/**
 * The quantity grid of a "By colour and size" line (INV-16): a side panel with
 * one collapsible row per colour of the form, in list order, and one field per
 * pressed size. A blank cell (or 0) means that colour and size doesn't use the
 * material. Nothing is saved here: **Done** closes the panel and **Save
 * product** saves the page. Mount it only while it is open: the rows that start
 * open (a colour with a cell, or with an error) are worked out when it mounts.
 */
export function QuantityGridSheet({
    title,
    unit,
    colours,
    sizes,
    grid,
    errors,
    readOnly = false,
    onChange,
    onClose,
}: QuantityGridSheetProps) {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState<ReadonlySet<number>>(() => {
        const withCells = colours.filter(
            (colour) =>
                countFilledCells(grid, [colour], sizes) > 0 ||
                errors.some((error) => error.colourId === colour.id),
        );

        // Nothing set yet: the first colour opens, so there is a field to type in.
        return new Set(
            (withCells.length > 0 ? withCells : colours.slice(0, 1)).map(
                (colour) => colour.id,
            ),
        );
    });
    const oneSize = sizes.length === 1 && sizes[0].one_size;
    const shown = colours.filter((colour) =>
        colour.name.toLowerCase().includes(query.trim().toLowerCase()),
    );
    const used = colours.filter(
        (colour) => countFilledCells(grid, [colour], sizes) > 0,
    ).length;
    const filled = countFilledCells(grid, colours, sizes);

    function toggle(colourId: number, isOpen: boolean) {
        const next = new Set(open);

        if (isOpen) {
            next.add(colourId);
        } else {
            next.delete(colourId);
        }

        setOpen(next);
    }

    /** The first typed value of the colour fills its empty sizes; typed cells stay as they are. */
    function fillEmpty(colourId: number, value: string) {
        let next = grid;

        for (const size of sizes) {
            if (!isFilledQuantity(cellValue(next, colourId, size.id))) {
                next = withCell(next, colourId, size.id, value);
            }
        }

        onChange?.(next);
    }

    return (
        <Sheet open onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent className="w-full gap-0 sm:max-w-xl">
                <SheetHeader className="pr-12">
                    <SheetTitle>{title}</SheetTitle>
                    <SheetDescription>
                        Quantity by colour and size, in {unit}. A blank cell
                        means that colour and size doesn&apos;t use this
                        material.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-3 px-4 pb-3">
                    <Input
                        type="text"
                        aria-label="Find colour"
                        placeholder="Find colour"
                        autoComplete="off"
                        className="h-11 md:h-9"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                    />
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm text-muted-foreground">
                            {used} of {colours.length} colours used
                        </p>
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11 md:h-9"
                                onClick={() =>
                                    setOpen(new Set(colours.map((c) => c.id)))
                                }
                            >
                                Expand all
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11 md:h-9"
                                onClick={() => setOpen(new Set())}
                            >
                                Collapse all
                            </Button>
                        </div>
                    </div>
                </div>

                <div aria-live="polite" className="px-4">
                    {shown.length === 0 && (
                        <p className="pb-3 text-sm text-muted-foreground">
                            No colour matches &quot;{query.trim()}&quot;.
                        </p>
                    )}
                </div>

                <ul
                    aria-label="Colours"
                    hidden={shown.length === 0}
                    className="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto px-4 pb-3"
                >
                    {shown.map((colour) => {
                        const values = sizes.map((size) =>
                            cellValue(grid, colour.id, size.id),
                        );
                        const typed = values.filter(isFilledQuantity);
                        const fillWith = typed[0]?.trim();

                        return (
                            <li key={colour.id}>
                                <Collapsible
                                    open={open.has(colour.id)}
                                    onOpenChange={(isOpen) =>
                                        toggle(colour.id, isOpen)
                                    }
                                    className="rounded-lg border"
                                >
                                    <CollapsibleTrigger className="group flex min-h-11 w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring">
                                        <ChevronDown
                                            aria-hidden="true"
                                            className="size-4 shrink-0 transition-transform group-data-[state=open]:rotate-180"
                                        />
                                        <ColourTag
                                            colour={colour}
                                            className="font-medium"
                                        />
                                        <span className="ml-auto text-right text-muted-foreground">
                                            {rowSummary(
                                                values,
                                                sizes.length,
                                                unit,
                                                oneSize,
                                            )}
                                        </span>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent className="flex flex-col gap-3 px-3 pb-3">
                                        <div
                                            className={cn(
                                                'grid grid-cols-1 gap-3',
                                                !oneSize &&
                                                    'sm:grid-cols-2 lg:grid-cols-4',
                                            )}
                                        >
                                            {sizes.map((size) => {
                                                const name = oneSize
                                                    ? `${colour.name}, ${size.label}`
                                                    : `${colour.name}, size ${size.label}`;
                                                const id = `grid-${colour.id}-${size.id}`;
                                                const value = cellValue(
                                                    grid,
                                                    colour.id,
                                                    size.id,
                                                );
                                                const error = errors.find(
                                                    (e) =>
                                                        e.colourId ===
                                                            colour.id &&
                                                        e.sizeId === size.id,
                                                );

                                                return (
                                                    <div
                                                        key={size.id}
                                                        className="flex min-w-0 flex-col gap-1.5"
                                                    >
                                                        {readOnly ? (
                                                            <span className="text-xs text-muted-foreground">
                                                                {size.label}
                                                            </span>
                                                        ) : (
                                                            <Label
                                                                htmlFor={id}
                                                                className="text-xs text-muted-foreground"
                                                            >
                                                                {size.label}
                                                            </Label>
                                                        )}
                                                        {readOnly ? (
                                                            <p className="text-sm">
                                                                {isFilledQuantity(
                                                                    value,
                                                                )
                                                                    ? `${value.trim()} ${unit}`
                                                                    : '—'}
                                                            </p>
                                                        ) : (
                                                            <DecimalInput
                                                                id={id}
                                                                aria-label={
                                                                    name
                                                                }
                                                                aria-invalid={
                                                                    error
                                                                        ? true
                                                                        : undefined
                                                                }
                                                                aria-describedby={
                                                                    error
                                                                        ? `${id}-error`
                                                                        : undefined
                                                                }
                                                                className="h-11 md:h-9"
                                                                value={value}
                                                                placeholder="0"
                                                                addonEnd={unit}
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    onChange?.(
                                                                        withCell(
                                                                            grid,
                                                                            colour.id,
                                                                            size.id,
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        ),
                                                                    )
                                                                }
                                                            />
                                                        )}
                                                        {error && (
                                                            <p
                                                                id={`${id}-error`}
                                                                className="text-sm text-destructive-foreground"
                                                            >
                                                                {error.message}
                                                            </p>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        {!readOnly &&
                                            fillWith !== undefined &&
                                            typed.length < sizes.length && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="h-11 self-start md:h-9"
                                                    onClick={() =>
                                                        fillEmpty(
                                                            colour.id,
                                                            fillWith,
                                                        )
                                                    }
                                                >
                                                    Fill empty sizes with{' '}
                                                    {fillWith} {unit}
                                                </Button>
                                            )}
                                    </CollapsibleContent>
                                </Collapsible>
                            </li>
                        );
                    })}
                </ul>

                <SheetFooter className="flex-row items-center justify-between gap-3 border-t">
                    <p className="text-sm">
                        {filled} of {colours.length * sizes.length} cells filled
                    </p>
                    <Button
                        type="button"
                        className="h-11 md:h-9"
                        onClick={onClose}
                    >
                        Done
                    </Button>
                </SheetFooter>
            </SheetContent>
        </Sheet>
    );
}
