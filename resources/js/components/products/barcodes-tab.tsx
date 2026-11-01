import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { GenerateBarcodesDialog } from '@/components/products/generate-barcodes-dialog';
import { Barcode } from '@/components/shared/barcode';
import { ColourTag } from '@/components/shared/colour-tag';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useAppSettings } from '@/hooks/use-app-settings';
import { EMPTY_VALUE, formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/products';
import type {
    Colour,
    FinishedStock,
    Product,
    ProductBarcodes,
    ProductRef,
    Size,
    VariantBarcode,
} from '@/types/domain';

/** What a click on a **Generate** button asks for. */
export type GenerateTarget =
    | { kind: 'missing' }
    | { kind: 'one'; colour: Colour; size: Size };

// Phone and tablet (up to 1023 px) are one layout; the table starts at lg.
const BUTTON = 'h-11 w-full lg:h-9 lg:w-auto';
const LINK = 'underline underline-offset-2 hover:text-foreground';

type SharedProduct = Pick<ProductRef, 'id' | 'sku'>;

function SkuLink({ product }: { product: SharedProduct }) {
    return (
        <Link href={show(product.id)} className={LINK}>
            {product.sku}
        </Link>
    );
}

/**
 * "Shared with PRD-JKT-002": one to three SKUs in full, four or more the first
 * two and "and N more", where "N more" opens a small list of the rest.
 */
function SharedWith({ shared }: { shared: SharedProduct[] }) {
    if (shared.length === 0) {
        return null;
    }

    let body: ReactNode;

    if (shared.length >= 4) {
        const [first, second, ...rest] = shared;

        body = (
            <>
                <SkuLink product={first} />, <SkuLink product={second} /> and{' '}
                <Popover>
                    <PopoverTrigger asChild>
                        <button type="button" className={cn(LINK, 'min-h-6')}>
                            {rest.length} more
                        </button>
                    </PopoverTrigger>
                    <PopoverContent
                        aria-label="Shared with"
                        className="w-auto min-w-40 p-3 text-sm"
                    >
                        <ul className="flex flex-col gap-1.5 font-mono">
                            {rest.map((product) => (
                                <li key={product.id}>
                                    <SkuLink product={product} />
                                </li>
                            ))}
                        </ul>
                    </PopoverContent>
                </Popover>
            </>
        );
    } else {
        body = shared.map((product, index) => (
            <span key={product.id}>
                {index > 0 && (index === shared.length - 1 ? ' and ' : ', ')}
                <SkuLink product={product} />
            </span>
        ));
    }

    return <p className="text-xs text-muted-foreground">Shared with {body}</p>;
}

/** The bars and digits, with who shares them; or the warning that there is none yet. */
function BarcodeCell({ variant }: { variant: VariantBarcode }) {
    if (variant.barcode === null) {
        return (
            <Badge className="border-transparent bg-warning text-warning-foreground">
                No barcode yet
            </Badge>
        );
    }

    return (
        <div className="flex flex-col items-start gap-1">
            <Barcode value={variant.barcode} width={140} />
            <SharedWith shared={variant.shared_with} />
        </div>
    );
}

function SizeTag({ size }: { size: Size }) {
    return (
        <span className="inline-flex rounded-md bg-muted px-2 py-1 text-sm leading-none font-semibold">
            {size.label}
        </span>
    );
}

/** A row's figure in the number font; a 0 in the readable grey; "—" when unknown. */
function Units({ units }: { units: number | null }) {
    const { locale } = useAppSettings();

    if (units === null) {
        return <span className="text-muted-foreground">{EMPTY_VALUE}</span>;
    }

    return (
        <span
            className={cn(
                'font-mono tabular-nums',
                units === 0 && 'text-muted-foreground',
            )}
        >
            {formatCount(units, locale)}
        </span>
    );
}

type Row = { colour: Colour; variant: VariantBarcode; units: number | null };

/**
 * The Barcodes tab of "Sizes & colours" (INV-8): colour pills, the main
 * **Generate missing barcodes (N)** button, and the chosen colour's barcodes as
 * a table on a laptop and one card per size on a phone. A "One size" product has
 * no pills: one row per colour. Everything shown comes from the server; the tab
 * only picks the chosen colour's rows. The table and the cards sit in one box
 * (`data-slot="barcodes-table"`, `tabIndex={-1}`) the page can focus after a
 * generate. The tab also owns the Generate window (`GenerateBarcodesDialog`).
 */
export function BarcodesTab({
    product,
    barcodes,
    finishedStock,
    oneSize,
}: {
    product: Pick<Product, 'id' | 'name' | 'sku'>;
    barcodes: ProductBarcodes;
    /** In stock per colour and size; "—" while null. */
    finishedStock: FinishedStock | null;
    /** The product's list is "One size" (passed by the page, never inferred from labels). */
    oneSize: boolean;
}) {
    const [chosenId, setChosenId] = useState<number | null>(null);
    // The window's last request stays while it fades out, so it never changes under the person.
    const [asked, setAsked] = useState<{
        target: GenerateTarget;
        open: boolean;
    }>({ target: { kind: 'missing' }, open: false });
    const tableRef = useRef<HTMLDivElement>(null);
    const onGenerate = (target: GenerateTarget) =>
        setAsked({ target, open: true });

    const chosen =
        barcodes.colours.find(({ colour }) => colour.id === chosenId) ??
        barcodes.colours[0];

    const unitsOf = (colourId: number, sizeId: number): number | null =>
        finishedStock?.colours
            .find(({ colour }) => colour.id === colourId)
            ?.sizes.find(({ size }) => size.id === sizeId)?.units ?? null;

    const entries = oneSize ? barcodes.colours : chosen ? [chosen] : [];
    const rows: Row[] = entries.flatMap(({ colour, variants }) =>
        (oneSize ? variants.slice(0, 1) : variants).map((variant) => ({
            colour,
            variant,
            units: unitsOf(colour.id, variant.size.id),
        })),
    );

    const tableName = oneSize
        ? 'Barcodes'
        : `Barcodes for ${chosen?.colour.name ?? ''}`;
    const generateOne = ({ colour, variant }: Row) => (
        <Button
            type="button"
            variant="outline"
            className={BUTTON}
            aria-label={`Generate barcode for ${variant.sku}`}
            onClick={() =>
                onGenerate({ kind: 'one', colour, size: variant.size })
            }
        >
            Generate
        </Button>
    );

    return (
        <div className="flex flex-col gap-4">
            {(!oneSize || barcodes.missing > 0) && (
                <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    {!oneSize && (
                        <div
                            role="group"
                            aria-label="Colour"
                            className="flex flex-wrap gap-2"
                        >
                            {barcodes.colours.map(({ colour, missing }) => (
                                <button
                                    key={colour.id}
                                    type="button"
                                    aria-pressed={
                                        colour.id === chosen?.colour.id
                                    }
                                    onClick={() => setChosenId(colour.id)}
                                    className={cn(
                                        // Focus is an outline, the chosen colour a ring: both show on a pressed pill.
                                        'inline-flex min-h-11 max-w-full min-w-0 items-center gap-x-1.5 rounded-full border px-3 py-1.5 text-left text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring aria-pressed:focus-visible:outline-offset-4 lg:min-h-9',
                                        'aria-pressed:border-primary aria-pressed:bg-primary/10 aria-pressed:ring-2 aria-pressed:ring-primary',
                                    )}
                                >
                                    <ColourTag colour={colour} />{' '}
                                    {missing > 0 && (
                                        <span className="shrink-0 text-muted-foreground">
                                            · {missing} missing
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                    )}
                    {barcodes.missing > 0 && (
                        <Button
                            type="button"
                            className={cn(BUTTON, 'lg:shrink-0')}
                            onClick={() => onGenerate({ kind: 'missing' })}
                        >
                            Generate missing barcodes ({barcodes.missing})
                        </Button>
                    )}
                </div>
            )}

            <div
                ref={tableRef}
                role="region"
                aria-label={tableName}
                data-slot="barcodes-table"
                tabIndex={-1}
                className="outline-none"
            >
                {/* Laptop (from 1024 px): the table. */}
                <div className="hidden overflow-x-auto lg:block">
                    <table className="w-full border-collapse text-sm">
                        <caption className="sr-only">{tableName}</caption>
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                <th
                                    scope="col"
                                    className="px-3 py-2 font-medium"
                                >
                                    {oneSize ? 'Colour' : 'Size'}
                                </th>
                                <th
                                    scope="col"
                                    className="px-3 py-2 font-medium"
                                >
                                    SKU
                                </th>
                                <th
                                    scope="col"
                                    className="px-3 py-2 font-medium"
                                >
                                    Barcode (EAN-13)
                                </th>
                                <th
                                    scope="col"
                                    className="px-3 py-2 text-right font-medium"
                                >
                                    In stock
                                </th>
                                <th scope="col" className="px-3 py-2">
                                    <span className="sr-only">Action</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr
                                    key={`${row.colour.id}-${row.variant.size.id}`}
                                    className="border-b align-top"
                                >
                                    <th
                                        scope="row"
                                        className="max-w-64 px-3 py-2 text-left font-medium"
                                    >
                                        {oneSize ? (
                                            <ColourTag colour={row.colour} />
                                        ) : (
                                            <SizeTag size={row.variant.size} />
                                        )}
                                    </th>
                                    <td className="px-3 py-2 font-mono">
                                        {row.variant.sku}
                                    </td>
                                    <td className="px-3 py-2">
                                        <BarcodeCell variant={row.variant} />
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Units units={row.units} />
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        {row.variant.barcode === null &&
                                            generateOne(row)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Phone and tablet (up to 1023 px): one card per size (per colour for "One size"). */}
                <ul className="flex flex-col gap-3 lg:hidden">
                    {rows.map((row) => (
                        <li
                            key={`${row.colour.id}-${row.variant.size.id}`}
                            className="flex flex-col gap-3 rounded-lg border p-3"
                        >
                            <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                {oneSize ? (
                                    <ColourTag
                                        colour={row.colour}
                                        className="font-medium"
                                    />
                                ) : (
                                    <SizeTag size={row.variant.size} />
                                )}
                                <span className="font-mono">
                                    {row.variant.sku}
                                </span>
                            </p>
                            <BarcodeCell variant={row.variant} />
                            <p className="text-sm">
                                <span className="text-muted-foreground">
                                    In stock
                                </span>{' '}
                                <Units units={row.units} />
                            </p>
                            {row.variant.barcode === null && generateOne(row)}
                        </li>
                    ))}
                </ul>
            </div>

            <p className="text-xs text-muted-foreground">
                One barcode for each colour and size. Every product named{' '}
                {product.name} shares it, and a barcode never changes.
            </p>

            <GenerateBarcodesDialog
                product={product}
                barcodes={barcodes}
                oneSize={oneSize}
                target={asked.target}
                open={asked.open}
                onOpenChange={(open) =>
                    setAsked((current) => ({ ...current, open }))
                }
                tableRef={tableRef}
            />
        </div>
    );
}
