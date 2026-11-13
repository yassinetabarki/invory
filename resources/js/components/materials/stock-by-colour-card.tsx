import { useId, useState } from 'react';
import type { ReactNode } from 'react';
import { StockBadge } from '@/components/materials/stock-badge';
import { ColourTag } from '@/components/shared/colour-tag';
import { Money } from '@/components/shared/money';
import { Quantity } from '@/components/shared/quantity';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Material, MaterialColourStock } from '@/types/domain';

const TOTAL_LABEL = 'All colours';

/** More colours than this and the card gets its summary line and filter switch. */
const FILTER_FROM = 8;

type StockByColourCardProps = {
    material: Material;
    /** Opens the Restock sheet on a colour. Without it the buttons are left out. */
    onRestock?: (colourId: number) => void;
};

/** A fine colour is plain muted text; only Low and Out get a badge (the UI card). */
function Status({ status }: { status: MaterialColourStock['stock_status'] }) {
    return status === 'in_stock' ? (
        <span className="text-muted-foreground">In stock</span>
    ) : (
        <StockBadge status={status} />
    );
}

/** Unit cost of one colour, per unit ("TND 8.500 / m"). */
function UnitCost({
    material,
    row,
}: {
    material: Material;
    row: MaterialColourStock;
}) {
    return (
        <>
            <Money value={row.unit_cost} precise /> / {material.unit}
        </>
    );
}

/** The small switch the UI card asks for (no Switch primitive in the kit): a labelled `role="switch"` button. */
function OnlyRestockSwitch({
    checked,
    onChange,
}: {
    checked: boolean;
    onChange: (next: boolean) => void;
}) {
    const id = useId();

    return (
        <div className="flex min-h-11 items-center gap-3 md:min-h-9">
            {/* The button is the 44 px target below md; the 24 px track is drawn inside it. */}
            <button
                id={id}
                type="button"
                role="switch"
                aria-checked={checked}
                onClick={() => onChange(!checked)}
                className="inline-flex h-11 w-11 shrink-0 items-center rounded-full outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 md:h-6"
            >
                <span
                    aria-hidden="true"
                    className={cn(
                        'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-transparent shadow-xs transition-colors',
                        checked ? 'bg-primary' : 'bg-input',
                    )}
                >
                    <span
                        className={cn(
                            'pointer-events-none block size-5 rounded-full bg-background shadow-sm ring-1 ring-border transition-transform',
                            checked ? 'translate-x-[22px]' : 'translate-x-0.5',
                        )}
                    />
                </span>
            </button>
            <Label htmlFor={id} className="text-sm font-normal">
                Only colours that need restock
            </Label>
        </div>
    );
}

function Item({
    label,
    children,
    className,
}: {
    label: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={className}>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd className="tabular-nums">{children}</dd>
        </div>
    );
}

/**
 * "Stock by colour" (INV-21): each colour of a per-colour material with its
 * quantity and status, in Settings › Colours order, then the total. Unit cost
 * and Stock value join it with cost per colour. With more than 8 colours a
 * summary line and a switch show only the colours that need restock, without
 * reordering. A table from `md` up, one block per colour below. Every figure,
 * the total included, is the server's.
 */
export function StockByColourCard({
    material,
    onRestock,
}: StockByColourCardProps) {
    const [onlyRestock, setOnlyRestock] = useState(false);
    const withCost = material.cost_mode === 'per_colour';
    const filterable = material.colours.length > FILTER_FROM;
    const rows =
        filterable && onlyRestock
            ? material.colours.filter((row) => row.stock_status !== 'in_stock')
            : material.colours;
    const buttonColumn = onRestock !== undefined;

    function restockButton(row: MaterialColourStock, className: string) {
        return (
            onRestock && (
                <Button
                    type="button"
                    variant="outline"
                    aria-label={`Restock ${row.colour.name}`}
                    className={className}
                    onClick={() => onRestock(row.colour.id)}
                >
                    Restock
                </Button>
            )
        );
    }

    return (
        <section
            aria-labelledby="stock-by-colour-title"
            className="overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm"
        >
            <div className="space-y-1 px-4 pt-5 pb-3 md:px-5">
                <h2 id="stock-by-colour-title" className="text-lg font-bold">
                    Stock by colour
                </h2>
                <p className="text-sm text-muted-foreground">
                    Each colour has its own stock. A product takes its material
                    from its own colour.
                </p>
            </div>

            {filterable && (
                <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-4 pb-3 md:px-5">
                    {material.colours_restock_summary && (
                        <p className="text-sm font-medium">
                            {material.colours_restock_summary}
                        </p>
                    )}
                    <OnlyRestockSwitch
                        checked={onlyRestock}
                        onChange={setOnlyRestock}
                    />
                </div>
            )}

            {rows.length === 0 && (
                <p
                    role="status"
                    className="px-4 pb-3 text-sm text-muted-foreground md:px-5"
                >
                    No colour needs restock.
                </p>
            )}

            {/* >= md: table */}
            <div className="hidden md:block">
                <Table>
                    <TableCaption className="sr-only">
                        Stock by colour
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col" className="pl-5">
                                Colour
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                In stock
                            </TableHead>
                            {withCost && (
                                <>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Unit cost
                                    </TableHead>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Stock value
                                    </TableHead>
                                </>
                            )}
                            <TableHead
                                scope="col"
                                className={cn(!buttonColumn && 'pr-5')}
                            >
                                Status
                            </TableHead>
                            {buttonColumn && (
                                <TableHead scope="col" className="pr-5">
                                    <span className="sr-only">Restock</span>
                                </TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => (
                            <TableRow key={row.colour.id}>
                                <TableHead scope="row" className="pl-5">
                                    <ColourTag colour={row.colour} />
                                </TableHead>
                                <TableCell className="text-right">
                                    <Quantity
                                        value={row.quantity_on_hand}
                                        unit={material.unit}
                                    />
                                </TableCell>
                                {withCost && (
                                    <>
                                        <TableCell className="text-right tabular-nums">
                                            <UnitCost
                                                material={material}
                                                row={row}
                                            />
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Money value={row.stock_value} />
                                        </TableCell>
                                    </>
                                )}
                                <TableCell
                                    className={cn(!buttonColumn && 'pr-5')}
                                >
                                    <Status status={row.stock_status} />
                                </TableCell>
                                {buttonColumn && (
                                    <TableCell className="pr-5 text-right">
                                        {restockButton(row, 'h-8')}
                                    </TableCell>
                                )}
                            </TableRow>
                        ))}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableHead scope="row" className="pl-5 font-bold">
                                {TOTAL_LABEL}
                            </TableHead>
                            <TableCell className="text-right font-bold">
                                <Quantity
                                    value={material.quantity_on_hand}
                                    unit={material.unit}
                                />
                            </TableCell>
                            {withCost && (
                                <>
                                    <TableCell />
                                    <TableCell className="text-right font-bold">
                                        <Money value={material.stock_value} />
                                    </TableCell>
                                </>
                            )}
                            <TableCell />
                            {buttonColumn && <TableCell className="pr-5" />}
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>

            {/* < md: one block per colour, a 44 px Restock button beside its name */}
            <ul
                aria-label="Stock by colour"
                className="space-y-3 px-4 pb-4 md:hidden"
            >
                {rows.map((row) => (
                    <li
                        key={row.colour.id}
                        className="space-y-3 rounded-lg border p-4"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <ColourTag
                                colour={row.colour}
                                className="font-semibold"
                            />
                            {restockButton(row, 'h-11 shrink-0')}
                        </div>
                        <dl className="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                            <Item label="In stock">
                                <Quantity
                                    value={row.quantity_on_hand}
                                    unit={material.unit}
                                />
                            </Item>
                            <Item label="Status">
                                <Status status={row.stock_status} />
                            </Item>
                            {withCost && (
                                <>
                                    <Item label="Unit cost">
                                        <UnitCost
                                            material={material}
                                            row={row}
                                        />
                                    </Item>
                                    <Item label="Stock value">
                                        <Money value={row.stock_value} />
                                    </Item>
                                </>
                            )}
                        </dl>
                    </li>
                ))}
                <li className="space-y-3 rounded-lg border bg-muted/40 p-4">
                    <strong>{TOTAL_LABEL}</strong>
                    <dl className="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                        <Item label="In stock" className="font-bold">
                            <Quantity
                                value={material.quantity_on_hand}
                                unit={material.unit}
                            />
                        </Item>
                        {withCost && (
                            <Item label="Stock value" className="font-bold">
                                <Money value={material.stock_value} />
                            </Item>
                        )}
                    </dl>
                </li>
            </ul>
        </section>
    );
}
