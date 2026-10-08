import { Link } from '@inertiajs/react';
import { Scissors, TriangleAlert } from 'lucide-react';
import { useId } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Callout } from '@/components/shared/callout';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
import { Quantity } from '@/components/shared/quantity';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount, formatQuantity } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Material, MaterialUsage } from '@/types/domain';

type MaterialUsageCardProps = {
    material: Material;
    /** Bottleneck rows first, then by product name (the server's order). */
    usage: MaterialUsage[];
};

/** "112 units", "1 unit". */
function useUnitsLabel(): (count: number) => string {
    const { locale } = useAppSettings();

    return (count) =>
        `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}

/** The product's name (a link), a status badge unless it is active, and its SKU. */
function ProductCell({ row }: { row: MaterialUsage }) {
    const { product } = row;

    return (
        <div className="flex items-center gap-3">
            <PhotoThumb src={product.photo_url} alt="" size="sm" />
            <div className="flex min-w-0 flex-col">
                <span className="flex flex-wrap items-center gap-2">
                    <Link
                        href={ProductController.show(product.id)}
                        className="font-semibold hover:underline focus-visible:underline"
                    >
                        {product.name}
                    </Link>
                    {product.status !== 'active' && (
                        <ProductStatusBadge
                            status={product.status}
                            label={product.status_label}
                        />
                    )}
                </span>
                <span className="font-mono text-xs text-muted-foreground">
                    {product.sku}
                </span>
            </div>
        </div>
    );
}

/** Warning badge when this material is the limit, else a link to the one that is. */
function LimitedBy({ row }: { row: MaterialUsage }) {
    if (row.is_bottleneck) {
        return (
            <Badge
                variant="outline"
                data-bottleneck="true"
                className="rounded-full border-transparent bg-warning font-semibold text-warning-foreground"
            >
                <TriangleAlert aria-hidden="true" />
                This material (bottleneck)
            </Badge>
        );
    }

    return (
        <Link
            href={MaterialController.show(row.bottleneck.id)}
            className="font-semibold hover:underline focus-visible:underline"
        >
            {row.bottleneck.name}
        </Link>
    );
}

/** One "Suggestion" per product this material limits. */
function Suggestion({ row }: { row: MaterialUsage }) {
    const unitsLabel = useUnitsLabel();
    const { product, next_limit: nextLimit } = row;

    if (nextLimit === null) {
        return (
            <Callout tone="info" title="Suggestion:">
                {`this is ${product.name}'s only material, so restocking it raises its capacity directly.`}
            </Callout>
        );
    }

    return (
        <Callout tone="info" title="Suggestion:">
            {`this material limits ${product.name} to ${unitsLabel(row.max_producible)}. If you restock it, the next limit is ${nextLimit.material.name} at ${unitsLabel(nextLimit.max_producible)}.`}
        </Callout>
    );
}

/** >= md: Product, Needs per unit, This stock covers, Can make now, Limited by. */
function UsageTable({ material, usage }: MaterialUsageCardProps) {
    const unitsLabel = useUnitsLabel();

    return (
        <div className="hidden md:block">
            <Table>
                <TableCaption className="sr-only">
                    Products that use {material.name}
                </TableCaption>
                <TableHeader>
                    <TableRow>
                        <TableHead scope="col" className="px-4">
                            Product
                        </TableHead>
                        <TableHead scope="col" className="px-4 text-right">
                            Needs per unit
                        </TableHead>
                        <TableHead scope="col" className="px-4 text-right">
                            This stock covers
                        </TableHead>
                        <TableHead scope="col" className="px-4 text-right">
                            Can make now
                        </TableHead>
                        <TableHead scope="col" className="px-4">
                            Limited by
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {usage.map((row) => (
                        <TableRow
                            key={row.product.id}
                            data-bottleneck={row.is_bottleneck || undefined}
                            className={cn(
                                row.is_bottleneck &&
                                    'bg-warning/40 hover:bg-warning/60',
                            )}
                        >
                            <TableCell className="min-w-60 px-4 py-3 whitespace-normal">
                                <ProductCell row={row} />
                            </TableCell>
                            <TableCell className="px-4 text-right tabular-nums">
                                <Quantity
                                    value={row.effective_quantity}
                                    unit={material.unit}
                                />
                            </TableCell>
                            <TableCell className="px-4 text-right tabular-nums">
                                {unitsLabel(row.units_covered)}
                            </TableCell>
                            <TableCell className="px-4 text-right text-base font-bold tabular-nums">
                                {unitsLabel(row.max_producible)}
                            </TableCell>
                            <TableCell className="px-4 whitespace-normal">
                                <LimitedBy row={row} />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/** < md: one card per product with the same four figures. */
function UsageCards({ material, usage }: MaterialUsageCardProps) {
    const unitsLabel = useUnitsLabel();

    return (
        <ul
            aria-label={`Products that use ${material.name}`}
            className="space-y-3 px-5 pb-5 md:hidden"
        >
            {usage.map((row) => (
                <li
                    key={row.product.id}
                    data-bottleneck={row.is_bottleneck || undefined}
                    className={cn(
                        'space-y-3 rounded-lg border p-4',
                        row.is_bottleneck && 'bg-warning/40',
                    )}
                >
                    <ProductCell row={row} />
                    <dl className="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Needs per unit
                            </dt>
                            <dd className="tabular-nums">
                                <Quantity
                                    value={row.effective_quantity}
                                    unit={material.unit}
                                />
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                This stock covers
                            </dt>
                            <dd className="tabular-nums">
                                {unitsLabel(row.units_covered)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Can make now
                            </dt>
                            <dd className="font-bold tabular-nums">
                                {unitsLabel(row.max_producible)}
                            </dd>
                        </div>
                    </dl>
                    <p className="flex flex-wrap items-center gap-2 text-sm">
                        <span className="text-xs text-muted-foreground">
                            Limited by
                        </span>
                        <LimitedBy row={row} />
                    </p>
                </li>
            ))}
        </ul>
    );
}

/**
 * "What you can make with it": every product that uses the material, how far
 * this stock goes on its own, how many units can be made now with the whole
 * composition, and what limits that. A restock suggestion follows for each
 * product this material limits.
 */
export function MaterialUsageCard({ material, usage }: MaterialUsageCardProps) {
    const titleId = useId();
    const { locale } = useAppSettings();
    const bottlenecks = usage.filter((row) => row.is_bottleneck);
    const count = usage.length;
    const stock = formatQuantity(material.quantity_on_hand, {
        locale,
        unit: material.unit,
    });

    return (
        <section
            aria-labelledby={titleId}
            className="rounded-xl border bg-card text-card-foreground shadow-sm"
        >
            <div className="flex items-start gap-3 p-5 pb-4">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-info text-info-foreground">
                    <Scissors className="size-[18px]" aria-hidden="true" />
                </span>
                <div className="min-w-0 space-y-0.5">
                    <h2 id={titleId} className="text-lg font-bold">
                        What you can make with it
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {count === 0
                            ? 'Not used in any product yet.'
                            : `${formatCount(count, locale)} ${count === 1 ? 'product uses' : 'products use'} this material. Here's how far your ${stock} go, and how many units you can make right now with all ${count === 1 ? 'its' : 'their'} materials.`}
                    </p>
                </div>
            </div>

            {count > 0 && (
                <>
                    <UsageTable material={material} usage={usage} />
                    <UsageCards material={material} usage={usage} />
                </>
            )}

            {bottlenecks.length > 0 && (
                <div className="flex flex-col gap-3 border-t p-5">
                    {bottlenecks.map((row) => (
                        <Suggestion key={row.product.id} row={row} />
                    ))}
                </div>
            )}
        </section>
    );
}
