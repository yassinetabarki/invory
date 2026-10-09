import { Link } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { CapacityBadge } from '@/components/shared/capacity-badge';
import { Money } from '@/components/shared/money';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { Quantity } from '@/components/shared/quantity';
import { Badge } from '@/components/ui/badge';
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
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { formatCount, formatPercent } from '@/lib/format';
import { cn } from '@/lib/utils';
import type {
    Decimal,
    MaterialRef,
    ProductComponentLine,
} from '@/types/domain';

type BomTableProps = {
    lines: ProductComponentLine[];
    /** `cost.material_cost`: the sum of the line costs, from the server. */
    materialsTotal: Decimal;
    /** `capacity.max_producible`; null without a composition. */
    capacity: number | null;
    /** URL builder for a material (Wayfinder in the page). */
    materialHref: (material: MaterialRef) => string;
};

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

/** Units this stock makes of the product, with the badge on the limiting line. */
function UnitsPossible({ line }: { line: ProductComponentLine }) {
    const { locale } = useAppSettings();

    return (
        <span className="inline-flex flex-wrap items-center justify-end gap-2">
            {line.is_bottleneck && <BottleneckBadge />}
            <strong className="tabular-nums">
                {formatCount(line.units_possible, locale)}
            </strong>
        </span>
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
                    {...prefetchProps(CACHE_TAGS.materials)}
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
    materialHref,
}: BomTableProps) {
    const { locale } = useAppSettings();

    return (
        <>
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
                                    <Quantity
                                        value={line.quantity}
                                        unit={line.material.unit}
                                    />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground tabular-nums">
                                    {formatPercent(line.waste_percent, locale)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <Quantity
                                        value={line.effective_quantity}
                                        unit={line.material.unit}
                                    />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground tabular-nums">
                                    <Money value={line.unit_cost} precise />
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    <Money value={line.line_cost} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <Quantity
                                        value={line.stock_on_hand}
                                        unit={line.material.unit}
                                    />
                                </TableCell>
                                <TableCell className="pr-5 text-right">
                                    <UnitsPossible line={line} />
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
                                <Money value={materialsTotal} />
                            </TableCell>
                            <TableCell />
                            <TableCell className="pr-5 text-right">
                                <CapacityBadge value={capacity} />
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
                                        <Quantity
                                            value={line.quantity}
                                            unit={line.material.unit}
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
                                        <Quantity
                                            value={line.effective_quantity}
                                            unit={line.material.unit}
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
                                        <Money value={line.line_cost} />
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        In stock
                                    </dt>
                                    <dd className="tabular-nums">
                                        <Quantity
                                            value={line.stock_on_hand}
                                            unit={line.material.unit}
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
                            <Money value={materialsTotal} />
                        </dd>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <dt>Can produce</dt>
                        <dd>
                            <CapacityBadge value={capacity} />
                        </dd>
                    </div>
                </dl>
            </div>
        </>
    );
}
