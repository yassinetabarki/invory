import { Link } from '@inertiajs/react';
import { Scissors, TriangleAlert } from 'lucide-react';
import { Fragment, useId, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { AddMaterialColourDialog } from '@/components/materials/add-material-colour-dialog';
import { AddColourLink } from '@/components/products/colour-stock-line';
import { Callout } from '@/components/shared/callout';
import { ColourSwatch } from '@/components/shared/colour-tag';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
import { Quantity } from '@/components/shared/quantity';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type {
    Colour,
    CountRange,
    Material,
    MaterialUsage,
} from '@/types/domain';

type MaterialUsageCardProps = {
    material: Material;
    /** Bottleneck rows first, then by product name (the server's order). */
    usage: MaterialUsage[];
};

/** Asks the card to open the window that adds a colour the material lacks, for the product whose row has the link. */
type AddColour = (productName: string, colour: Colour) => void;

type RowsProps = MaterialUsageCardProps & { onAddColour: AddColour };

type RowProps = {
    row: MaterialUsage;
    material: Material;
    onAddColour: AddColour;
};

/** "112 units", "1 unit". */
function useUnitsLabel(): (count: number) => string {
    const { locale } = useAppSettings();

    return (count) =>
        `${formatCount(count, locale)} ${count === 1 ? 'unit' : 'units'}`;
}

/** "112 units" when nothing varies, "16–22 units" (en dash) when it does. */
function useRangeLabel(): (range: CountRange) => string {
    const { locale } = useAppSettings();
    const unitsLabel = useUnitsLabel();

    return ({ min, max }) =>
        min === max
            ? unitsLabel(min)
            : `${formatCount(min, locale)}–${unitsLabel(max)}`;
}

/** The material's need per unit in the material's unit, or "Varies" for a product that uses it differently per colour and size. */
function NeedsPerUnit({
    row,
    unit,
}: {
    row: MaterialUsage;
    unit: Material['unit'];
}) {
    return row.effective_quantity === null ? (
        'Varies'
    ) : (
        <Quantity value={row.effective_quantity} unit={unit} />
    );
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

/** Warning badge when this material is the limit ("…, Black stock" in a colour), else a link to the one that is. */
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
                {row.limit_colour && `, ${row.limit_colour.name} stock`}
            </Badge>
        );
    }

    return (
        <span>
            <Link
                href={MaterialController.show(row.bottleneck.id)}
                className="font-semibold hover:underline focus-visible:underline"
            >
                {row.bottleneck.name}
            </Link>
            {row.bottleneck_colour && `, ${row.bottleneck_colour.name} stock`}
        </span>
    );
}

/** "20–126 units", and for a material kept per colour the small By colour button that opens the line under it. */
function Covers({
    row,
    open,
    onToggle,
    panelId,
    alignEnd = false,
}: {
    row: MaterialUsage;
    open: boolean;
    onToggle: () => void;
    panelId: string;
    alignEnd?: boolean;
}) {
    const rangeLabel = useRangeLabel();

    return (
        <span
            className={cn(
                'flex flex-col gap-0.5',
                alignEnd ? 'items-end' : 'items-start',
            )}
        >
            <span>{rangeLabel(row.units_covered)}</span>
            {row.by_colour && (
                <Button
                    type="button"
                    variant="link"
                    aria-expanded={open}
                    aria-controls={panelId}
                    onClick={onToggle}
                    className="h-auto min-h-11 p-0 text-sm md:min-h-0"
                >
                    By colour
                </Button>
            )}
        </span>
    );
}

/** "Indigo 126 units · Black 20 units · Light wash no stock", with the Add link for each colour the material lacks. */
function ColourLine({
    id,
    row,
    material,
    onAddColour,
}: RowProps & { id: string }) {
    const rangeLabel = useRangeLabel();
    const readings = row.by_colour ?? [];

    return (
        <div
            id={id}
            className="flex flex-col items-start gap-1 text-left text-sm font-normal tabular-nums"
        >
            <p>
                {readings.map((reading, index) => (
                    <Fragment key={reading.colour.id}>
                        {index > 0 && ' · '}
                        <ColourSwatch
                            swatch={reading.colour.swatch}
                            className="mr-1.5 align-middle"
                        />
                        {reading.colour.name}{' '}
                        {reading.missing
                            ? 'no stock'
                            : rangeLabel(reading.units_possible)}
                    </Fragment>
                ))}
            </p>
            {readings
                .filter((reading) => reading.missing)
                .map((reading) => (
                    <AddColourLink
                        key={reading.colour.id}
                        materialName={material.name}
                        colourName={reading.colour.name}
                        onClick={() =>
                            onAddColour(row.product.name, reading.colour)
                        }
                    />
                ))}
        </div>
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

    const colour = row.limit_colour?.name;

    return (
        <Callout tone="info" title="Suggestion:">
            {`this material limits ${product.name} to ${unitsLabel(row.max_producible)}${colour ? `, in ${colour}` : ''}. If you restock ${colour ?? 'it'}, the next limit is ${nextLimit.material.name} at ${unitsLabel(nextLimit.max_producible)}.`}
        </Callout>
    );
}

/** One table row; a material kept per colour adds a second row with the colours while By colour is open. */
function UsageTableRow({ row, material, onAddColour }: RowProps) {
    const unitsLabel = useUnitsLabel();
    const [open, setOpen] = useState(false);
    const panelId = useId();
    const tint = row.is_bottleneck && 'bg-warning/40';

    return (
        <>
            <TableRow
                data-bottleneck={row.is_bottleneck || undefined}
                className={cn(row.is_bottleneck && 'hover:bg-warning/60', tint)}
            >
                <TableCell className="min-w-60 px-4 py-3 whitespace-normal">
                    <ProductCell row={row} />
                </TableCell>
                <TableCell className="px-4 text-right tabular-nums">
                    <NeedsPerUnit row={row} unit={material.unit} />
                </TableCell>
                <TableCell className="px-4 text-right tabular-nums">
                    <Covers
                        row={row}
                        open={open}
                        onToggle={() => setOpen((value) => !value)}
                        panelId={panelId}
                        alignEnd
                    />
                </TableCell>
                <TableCell className="px-4 text-right text-base font-bold tabular-nums">
                    {unitsLabel(row.max_producible)}
                </TableCell>
                <TableCell className="px-4 whitespace-normal">
                    <LimitedBy row={row} />
                </TableCell>
            </TableRow>
            {open && (
                <TableRow className={cn('hover:bg-transparent', tint)}>
                    <TableCell
                        colSpan={5}
                        className="px-4 py-3 whitespace-normal"
                    >
                        <ColourLine
                            id={panelId}
                            row={row}
                            material={material}
                            onAddColour={onAddColour}
                        />
                    </TableCell>
                </TableRow>
            )}
        </>
    );
}

/** >= md: Product, Needs per unit, This stock covers, Can make now, Limited by. */
function UsageTable({ material, usage, onAddColour }: RowsProps) {
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
                        <UsageTableRow
                            key={row.product.id}
                            row={row}
                            material={material}
                            onAddColour={onAddColour}
                        />
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

/** One card: the product and its figures; a material kept per colour adds the colours line while By colour is open. */
function UsageCard({ row, material, onAddColour }: RowProps) {
    const unitsLabel = useUnitsLabel();
    const [open, setOpen] = useState(false);
    const panelId = useId();

    return (
        <li
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
                        <NeedsPerUnit row={row} unit={material.unit} />
                    </dd>
                </div>
                <div>
                    <dt className="text-xs text-muted-foreground">
                        This stock covers
                    </dt>
                    <dd className="tabular-nums">
                        <Covers
                            row={row}
                            open={open}
                            onToggle={() => setOpen((value) => !value)}
                            panelId={panelId}
                        />
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
            {open && (
                <ColourLine
                    id={panelId}
                    row={row}
                    material={material}
                    onAddColour={onAddColour}
                />
            )}
            <p className="flex flex-wrap items-center gap-2 text-sm">
                <span className="text-xs text-muted-foreground">
                    Limited by
                </span>
                <LimitedBy row={row} />
            </p>
        </li>
    );
}

/** < md: one card per product with the same figures. */
function UsageCards({ material, usage, onAddColour }: RowsProps) {
    return (
        <ul
            aria-label={`Products that use ${material.name}`}
            className="space-y-3 px-5 pb-5 md:hidden"
        >
            {usage.map((row) => (
                <UsageCard
                    key={row.product.id}
                    row={row}
                    material={material}
                    onAddColour={onAddColour}
                />
            ))}
        </ul>
    );
}

/**
 * "What you can make with it": every product that uses the material, how far
 * this stock goes on its own, how many units can be made now with the whole
 * composition, and what limits that. A restock suggestion follows for each
 * product this material limits. A material kept per colour reads colour by
 * colour (By colour) and can add a colour it lacks from here.
 */
export function MaterialUsageCard({ material, usage }: MaterialUsageCardProps) {
    const titleId = useId();
    const { locale } = useAppSettings();
    const [adding, setAdding] = useState<{
        productName: string;
        colour: Colour;
        open: boolean;
    } | null>(null);
    const suggestions = usage.filter((row) => row.is_bottleneck);
    const count = usage.length;
    const stock = formatQuantity(material.quantity_on_hand, {
        locale,
        unit: material.unit,
    });
    const reach =
        material.stock_mode === 'per_colour'
            ? 'your stock goes, colour by colour'
            : `your ${stock} go`;
    const openAdd: AddColour = (productName, colour) =>
        setAdding({ productName, colour, open: true });

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
                            : `${formatCount(count, locale)} ${count === 1 ? 'product uses' : 'products use'} this material. Here's how far ${reach}, and how many units you can make right now with all ${count === 1 ? 'its' : 'their'} materials.`}
                    </p>
                </div>
            </div>

            {count > 0 && (
                <>
                    <UsageTable
                        material={material}
                        usage={usage}
                        onAddColour={openAdd}
                    />
                    <UsageCards
                        material={material}
                        usage={usage}
                        onAddColour={openAdd}
                    />
                </>
            )}

            {suggestions.length > 0 && (
                <div className="flex flex-col gap-3 border-t p-5">
                    {suggestions.map((row) => (
                        <Suggestion key={row.product.id} row={row} />
                    ))}
                </div>
            )}

            {adding && (
                <AddMaterialColourDialog
                    open={adding.open}
                    onOpenChange={(open) =>
                        setAdding((current) =>
                            current ? { ...current, open } : null,
                        )
                    }
                    material={material}
                    colour={adding.colour}
                    productName={adding.productName}
                />
            )}
        </section>
    );
}
