import { Link } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import { gridFromCells } from '@/components/products/bom-rows';
import {
    ColourStockLine,
    UnitsByColour,
} from '@/components/products/colour-stock-line';
import type { AddColour } from '@/components/products/colour-stock-line';
import { QuantityGridSheet } from '@/components/products/quantity-grid-sheet';
import { CapacityBadge } from '@/components/shared/capacity-badge';
import { Money, MoneyRange } from '@/components/shared/money';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { Quantity } from '@/components/shared/quantity';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatPercent } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    Colour,
    DecimalRange,
    MaterialRef,
    ProductComponentLine,
    SizeOption,
} from '@/types/domain';

type BomTableProps = {
    lines: ProductComponentLine[];
    /** `cost.material_cost`: the sum of the line costs, lowest to highest over every colour and size, from the server. */
    materialsTotal: DecimalRange | null;
    /** `capacity.max_producible`; null without a composition. */
    capacity: number | null;
    /** `capacity.best` (INV-16): shown as "16–112 units" when above `capacity`. Defaults to `capacity`. */
    best?: number | null;
    /** URL builder for a material (Wayfinder in the page). */
    materialHref: (material: MaterialRef) => string;
    /** The product's colours and sizes, in list order: the rows and columns of View grid. */
    colours: readonly Colour[];
    sizes: readonly SizeOption[];
    /** Opens the window that adds a colour to a material that lacks it (INV-21-p); the link shows only when given. */
    onAddColour?: AddColour;
};

/** No errors to show in the read-only grid. */
const NO_ERRORS: never[] = [];

/** The "Bottleneck" marker: text and icon, never colour alone. */
function BottleneckBadge() {
    return (
        <Badge
            variant="outline"
            className="rounded-full border-transparent bg-warning font-semibold text-warning-foreground"
        >
            <TriangleAlert aria-hidden="true" />
            Bottleneck
        </Badge>
    );
}

/** A figure that has no single value on a by-colour-and-size line (INV-16). */
function Varies() {
    return <span className="text-muted-foreground">Varies</span>;
}

/**
 * Units this stock makes of the product, with the badge on the limiting line.
 * A material kept per colour reads "Varies" with **View by colour** (INV-21).
 */
function UnitsPossible({
    line,
    alignEnd = false,
}: {
    line: ProductComponentLine;
    alignEnd?: boolean;
}) {
    const { locale } = useAppSettings();
    const perColour = line.stock_by_colour !== null;

    return (
        <span
            className={cn(
                'flex flex-col gap-1',
                alignEnd ? 'items-end' : 'items-start',
            )}
        >
            <span className="inline-flex flex-wrap items-center justify-end gap-2">
                {line.is_bottleneck && <BottleneckBadge />}
                {perColour ||
                line.quantity_mode === 'by_variant' ||
                line.units_possible === null ? (
                    <Varies />
                ) : (
                    <strong className="tabular-nums">
                        {formatCount(line.units_possible, locale)}
                    </strong>
                )}
            </span>
            {perColour && <UnitsByColour line={line} alignEnd={alignEnd} />}
        </span>
    );
}

/** The material's stock: one figure, or each of the product's colours for a material kept per colour. */
function StockOnHand({
    line,
    onAddColour,
    alignEnd = false,
}: {
    line: ProductComponentLine;
    onAddColour?: AddColour;
    alignEnd?: boolean;
}) {
    return line.stock_by_colour === null ? (
        <Quantity value={line.stock_on_hand} unit={line.material.unit} />
    ) : (
        <ColourStockLine
            line={line}
            onAddColour={onAddColour}
            alignEnd={alignEnd}
        />
    );
}

/** Quantity per unit or effective quantity: the server's figure, or "Varies". */
function LineQuantity({
    line,
    field,
    onViewGrid,
    alignEnd = false,
}: {
    line: ProductComponentLine;
    field: 'quantity' | 'effective_quantity';
    /** Given on the Quantity figure only: a by-colour-and-size line gets its **View grid** link. */
    onViewGrid?: () => void;
    alignEnd?: boolean;
}) {
    const value = line[field];

    if (line.quantity_mode !== 'by_variant' && value !== null) {
        return <Quantity value={value} unit={line.material.unit} />;
    }

    return (
        <span
            className={cn(
                'flex flex-col',
                alignEnd ? 'items-end' : 'items-start',
            )}
        >
            <Varies />
            {onViewGrid && (
                <Button
                    type="button"
                    variant="link"
                    onClick={onViewGrid}
                    aria-label={`View grid for ${line.material.name}`}
                    className="h-auto min-h-11 p-0 text-sm md:min-h-0"
                >
                    View grid
                </Button>
            )}
        </span>
    );
}

function LineCost({ line }: { line: ProductComponentLine }) {
    return line.quantity_mode === 'by_variant' || line.line_cost === null ? (
        <Varies />
    ) : (
        <Money value={line.line_cost} />
    );
}

function MaterialCell({
    line,
    materialHref,
}: {
    line: ProductComponentLine;
    materialHref: BomTableProps['materialHref'];
}) {
    const { material } = line;

    return (
        <div className="flex items-center gap-3">
            <PhotoThumb src={material.photo_url} alt="" size="sm" />
            <div className="flex min-w-0 flex-col">
                <Link
                    href={materialHref(material)}
                    className="font-semibold whitespace-normal hover:underline focus-visible:underline"
                >
                    {material.name}
                </Link>
                <span className="font-mono text-xs text-muted-foreground">
                    {material.sku}
                </span>
                {line.notes && (
                    <span className="text-xs text-muted-foreground">
                        {line.notes}
                    </span>
                )}
            </div>
        </div>
    );
}

/**
 * The composition (bill of materials) of one unit: quantity, waste, effective
 * quantity, costs and how many units the stock allows per line. The
 * bottleneck line is highlighted and badged. A table from `md` up, stacked
 * cards below. Every figure is a server value that is only formatted here.
 */
export function BomTable({
    lines,
    materialsTotal,
    capacity,
    best = capacity,
    materialHref,
    colours,
    sizes,
    onAddColour,
}: BomTableProps) {
    const { locale } = useAppSettings();
    const [viewing, setViewing] = useState<ProductComponentLine | null>(null);

    return (
        <>
            {viewing && (
                <QuantityGridSheet
                    title={viewing.material.name}
                    unit={viewing.material.unit}
                    colours={colours}
                    sizes={sizes}
                    grid={gridFromCells(viewing.quantities)}
                    errors={NO_ERRORS}
                    readOnly
                    onClose={() => setViewing(null)}
                />
            )}
            {/* >= md: table */}
            <div className="hidden md:block">
                <Table>
                    <TableCaption className="sr-only">Composition</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col" className="pl-5">
                                Material
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Qty per unit
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Waste
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Effective qty
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Unit cost
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Line cost
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                In stock
                            </TableHead>
                            <TableHead scope="col" className="pr-5 text-right">
                                Units possible
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {lines.map((line) => (
                            <TableRow
                                key={line.id}
                                data-bottleneck={
                                    line.is_bottleneck || undefined
                                }
                                className={cn(
                                    line.is_bottleneck &&
                                        'bg-warning/40 hover:bg-warning/50',
                                )}
                            >
                                <TableCell className="min-w-56 pl-5">
                                    <MaterialCell
                                        line={line}
                                        materialHref={materialHref}
                                    />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <LineQuantity
                                        line={line}
                                        field="quantity"
                                        alignEnd
                                        onViewGrid={() => setViewing(line)}
                                    />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground tabular-nums">
                                    {formatPercent(line.waste_percent, locale)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <LineQuantity
                                        line={line}
                                        field="effective_quantity"
                                        alignEnd
                                    />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground tabular-nums">
                                    <Money value={line.unit_cost} precise />
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    <LineCost line={line} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <StockOnHand
                                        line={line}
                                        onAddColour={onAddColour}
                                        alignEnd
                                    />
                                </TableCell>
                                <TableCell className="pr-5 text-right">
                                    <UnitsPossible line={line} alignEnd />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                    <TableFooter>
                        <TableRow>
                            <TableCell colSpan={5} className="pl-5 font-bold">
                                Materials total
                            </TableCell>
                            <TableCell className="text-right font-bold tabular-nums">
                                <MoneyRange value={materialsTotal} />
                            </TableCell>
                            <TableCell />
                            <TableCell className="pr-5 text-right">
                                <CapacityBadge value={capacity} best={best} />
                            </TableCell>
                        </TableRow>
                    </TableFooter>
                </Table>
            </div>

            {/* < md: stacked cards */}
            <div className="md:hidden">
                <ul aria-label="Composition" className="space-y-3 px-4 pb-4">
                    {lines.map((line) => (
                        <li
                            key={line.id}
                            data-bottleneck={line.is_bottleneck || undefined}
                            className={cn(
                                'space-y-3 rounded-lg border p-4',
                                line.is_bottleneck && 'bg-warning/40',
                            )}
                        >
                            <MaterialCell
                                line={line}
                                materialHref={materialHref}
                            />
                            <dl className="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Qty per unit
                                    </dt>
                                    <dd className="tabular-nums">
                                        <LineQuantity
                                            line={line}
                                            field="quantity"
                                            onViewGrid={() => setViewing(line)}
                                        />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Waste
                                    </dt>
                                    <dd className="tabular-nums">
                                        {formatPercent(
                                            line.waste_percent,
                                            locale,
                                        )}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Effective qty
                                    </dt>
                                    <dd className="tabular-nums">
                                        <LineQuantity
                                            line={line}
                                            field="effective_quantity"
                                        />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Unit cost
                                    </dt>
                                    <dd className="tabular-nums">
                                        <Money value={line.unit_cost} precise />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Line cost
                                    </dt>
                                    <dd className="font-semibold tabular-nums">
                                        <LineCost line={line} />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        In stock
                                    </dt>
                                    <dd className="tabular-nums">
                                        <StockOnHand
                                            line={line}
                                            onAddColour={onAddColour}
                                        />
                                    </dd>
                                </div>
                                <div className="col-span-2">
                                    <dt className="text-xs text-muted-foreground">
                                        Units possible
                                    </dt>
                                    <dd className="text-left">
                                        <UnitsPossible line={line} />
                                    </dd>
                                </div>
                            </dl>
                        </li>
                    ))}
                </ul>
                <dl className="mx-4 mb-4 space-y-2 rounded-lg bg-muted/50 p-4 text-sm font-bold">
                    <div className="flex items-center justify-between gap-3">
                        <dt>Materials total</dt>
                        <dd className="tabular-nums">
                            <MoneyRange value={materialsTotal} />
                        </dd>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <dt>Can produce</dt>
                        <dd>
                            <CapacityBadge value={capacity} best={best} />
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}
