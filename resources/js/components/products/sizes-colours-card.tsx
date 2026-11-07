import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useCallback, useId, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { BarcodesTab } from '@/components/products/barcodes-tab';
import {
    SIZES_COLOURS_ID,
    StockLoadError,
    useSizesColoursTab,
} from '@/components/products/finished-stock-figure';
import { unitsWord } from '@/components/products/product-hero';
import { UpdateStockDialog } from '@/components/products/update-stock-dialog';
import { ColourTag } from '@/components/shared/colour-tag';
import { SizeChips } from '@/components/shared/size-chips';
import { SkeletonRow } from '@/components/shared/skeletons';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    create as restockCreate,
    index as restocksIndex,
} from '@/routes/restocks';
import type {
    FinishedStock,
    Product,
    ProductBarcodes,
    ProductShowProps,
} from '@/types/domain';

const TABLE_NAME = 'Units in stock per colour and size';
const BUTTON = 'h-11 w-full md:h-9 md:w-auto';
// A visible focus ring for a tab panel (it is a tab stop): the ui/tabs panel has none.
const PANEL = 'rounded-md focus-visible:ring-[3px] focus-visible:ring-ring';
// Below md a tab is at least 44 px tall and may wrap onto two lines ("Barcodes" over
// its "24 missing" badge) so nothing is clipped at 320 px; from md it is one line.
const TRIGGER =
    'h-auto min-h-11 min-w-0 flex-wrap gap-x-1.5 gap-y-0.5 whitespace-normal md:h-[calc(100%-1px)] md:min-h-0 md:flex-nowrap md:px-4 md:whitespace-nowrap';

function plural(count: number, one: string, many: string): string {
    return `${count} ${count === 1 ? one : many}`;
}

/** The frame of the card: the heading and, on its row, the actions. */
function CardFrame({
    line,
    actions,
    children,
}: {
    /** The variants line under the heading. */
    line?: string;
    actions?: ReactNode;
    children: ReactNode;
}) {
    const headingId = useId();

    return (
        <section
            id={SIZES_COLOURS_ID}
            tabIndex={-1}
            aria-labelledby={headingId}
            className="flex min-w-0 scroll-mt-4 flex-col gap-4 rounded-xl border bg-card p-5 text-card-foreground outline-none"
        >
            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div className="min-w-0 space-y-0.5">
                    <h2 id={headingId} className="text-base font-bold">
                        Sizes &amp; colours
                    </h2>
                    {line && (
                        <p className="text-sm text-muted-foreground">{line}</p>
                    )}
                </div>
                {actions}
            </div>
            {children}
        </section>
    );
}

/** A figure of the table or of a phone chip row: the number font, a 0 in the readable grey. */
function Figure({ units }: { units: number }) {
    const { locale } = useAppSettings();

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

const CELL = 'px-3 py-2 text-right';

/**
 * The two tabs, half the width each on a phone. While barcodes are missing,
 * Barcodes carries the warning badge "4 missing" (no badge while they load).
 */
function CardTabList({ missing }: { missing: number | null }) {
    return (
        <TabsList className="h-auto w-full items-stretch md:h-9 md:w-fit md:items-center">
            <TabsTrigger value="stock" className={TRIGGER}>
                Stock by colour
            </TabsTrigger>
            <TabsTrigger value="barcodes" className={TRIGGER}>
                Barcodes
                {missing !== null && missing > 0 && (
                    <>
                        {/* A real space: "Barcodes 4 missing" is the tab's spoken name. */}{' '}
                        <Badge className="border-transparent bg-warning text-warning-foreground">
                            {missing} missing
                        </Badge>
                    </>
                )}
            </TabsTrigger>
        </TabsList>
    );
}

function asTab(value: string): 'stock' | 'barcodes' {
    return value === 'barcodes' ? 'barcodes' : 'stock';
}

/**
 * "Sizes & colours" on the product page (INV-2, INV-8, INV-3): how many colours and
 * sizes make how many variants, the buttons **Manage sizes & colours** (Edit
 * product, with the focus on the Colours field), **Update stock** and **Restock**
 * (New restock with this product in it), then two
 * tabs kept in the address (`?tab=barcodes`): **Stock by colour**, the stock of
 * every variant, and **Barcodes**. The stock tab is a table on a laptop (a shoe's 11 columns scroll inside the card), one
 * card per colour and a Total card on a phone. A "One size" product has one
 * "In stock" column. Every figure comes from the server.
 */
export function SizesColoursCard({
    product,
    stock,
    barcodes,
    manageHref,
    lastRestock,
}: {
    product: Pick<Product, 'id' | 'name' | 'sku' | 'sizes' | 'one_size'>;
    stock: FinishedStock;
    barcodes: ProductBarcodes;
    /** Edit product with `#sizes`. */
    manageHref: string;
    /** INV-3: the newest restock not undone of this product; null "Not restocked yet."; absent: no line. */
    lastRestock?: ProductShowProps['last_restock'];
}) {
    const { locale } = useAppSettings();
    const mobile = useIsMobile();
    const [tab, openTab] = useSizesColoursTab();
    const [updating, setUpdating] = useState(false);
    const updateButtonRef = useRef<HTMLButtonElement>(null);
    const [overflows, setOverflows] = useState(false);

    // The table's box is a tab stop only when it really scrolls (a shoe's 11 sizes
    // on a narrow laptop), so keyboard users can scroll it; otherwise it is not.
    // A callback ref: the box only exists while the Stock tab is open (and measures
    // 0 while hidden), so it is measured each time it is attached, then watched.
    const measureRegion = useCallback((region: HTMLDivElement | null) => {
        if (!region) {
            return;
        }

        const measure = () =>
            setOverflows(region.scrollWidth > region.clientWidth);

        measure();

        // jsdom has no ResizeObserver: the measure on attach is all there is.
        if (typeof ResizeObserver === 'undefined') {
            return;
        }

        const observer = new ResizeObserver(measure);

        observer.observe(region);

        if (region.firstElementChild) {
            observer.observe(region.firstElementChild);
        }

        return () => observer.disconnect();
    }, []);

    const { colours } = stock;
    const sizeCount = product.sizes.length;
    // A single size that isn't One size keeps its size column (INV-18, AC-7).
    const oneSize = product.one_size;
    const variants = colours.length * sizeCount;
    const line = `${plural(colours.length, 'colour', 'colours')} × ${
        oneSize ? product.sizes[0].label : plural(sizeCount, 'size', 'sizes')
    } = ${plural(variants, 'variant', 'variants')}. Each variant has its own stock and its own barcode.`;
    const showTotals = colours.length > 1;
    // Every cell is a grey 0 when nothing is on the shelf: nothing to explain then.
    const showNote = stock.units > 0;
    const restockButton = (
        <Button asChild className={BUTTON}>
            <Link href={restockCreate({ query: { product: product.id } })}>
                <Plus aria-hidden="true" />
                Restock
            </Link>
        </Button>
    );

    return (
        <CardFrame
            line={line}
            actions={
                // On a laptop Manage · Update stock · Restock; on a phone they stack with Restock first (INV-3, AC-7). The DOM
                // order is the visual order either way, so it is the keyboard order too.
                <div className="flex flex-col gap-2 md:shrink-0 md:flex-row">
                    {mobile && restockButton}
                    <Button asChild variant="outline" className={BUTTON}>
                        <Link href={manageHref}>
                            Manage sizes &amp; colours
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        className={BUTTON}
                        ref={updateButtonRef}
                        onClick={() => setUpdating(true)}
                    >
                        Update stock
                    </Button>
                    {!mobile && restockButton}
                </div>
            }
        >
            <Tabs
                value={tab}
                onValueChange={(value) => openTab(asTab(value))}
                className="gap-4"
            >
                <CardTabList missing={barcodes.missing} />
                <TabsContent
                    value="stock"
                    className={cn('flex flex-col gap-4', PANEL)}
                >
                    {/* Laptop: the table, scrolling inside the card when it is wider. */}
                    <div
                        role="region"
                        aria-label={`${TABLE_NAME}, scrollable`}
                        ref={measureRegion}
                        tabIndex={overflows ? 0 : undefined}
                        className="hidden overflow-x-auto rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring md:block"
                    >
                        <table className="w-full min-w-max border-collapse text-sm">
                            <caption className="sr-only">{TABLE_NAME}</caption>
                            <thead>
                                <tr className="border-b text-muted-foreground">
                                    <th
                                        scope="col"
                                        className="px-3 py-2 text-left font-medium"
                                    >
                                        Colour
                                    </th>
                                    {oneSize ? (
                                        <th
                                            scope="col"
                                            className={cn(CELL, 'font-medium')}
                                        >
                                            In stock
                                        </th>
                                    ) : (
                                        <>
                                            {stock.sizes.map(({ size }) => (
                                                <th
                                                    key={size.id}
                                                    scope="col"
                                                    className={cn(
                                                        CELL,
                                                        'font-medium',
                                                    )}
                                                >
                                                    {size.label}
                                                </th>
                                            ))}
                                            <th
                                                scope="col"
                                                className={cn(
                                                    CELL,
                                                    'font-medium',
                                                )}
                                            >
                                                Total
                                            </th>
                                        </>
                                    )}
                                </tr>
                            </thead>
                            <tbody>
                                {colours.map(({ colour, units, sizes }) => (
                                    <tr key={colour.id} className="border-b">
                                        <th
                                            scope="row"
                                            className="max-w-64 px-3 py-2 text-left font-medium"
                                        >
                                            <ColourTag colour={colour} />
                                        </th>
                                        {oneSize ? (
                                            <td className={CELL}>
                                                <Figure units={units} />
                                            </td>
                                        ) : (
                                            <>
                                                {sizes.map(
                                                    ({ size, units: n }) => (
                                                        <td
                                                            key={size.id}
                                                            className={CELL}
                                                        >
                                                            <Figure units={n} />
                                                        </td>
                                                    ),
                                                )}
                                                <td
                                                    className={cn(
                                                        CELL,
                                                        'font-semibold',
                                                    )}
                                                >
                                                    <Figure units={units} />
                                                </td>
                                            </>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                            {showTotals && (
                                <tfoot>
                                    <tr className="border-t-2 border-foreground/40 font-semibold">
                                        <th
                                            scope="row"
                                            className="px-3 py-2 text-left"
                                        >
                                            Total
                                        </th>
                                        {oneSize ? (
                                            <td className={CELL}>
                                                <Figure units={stock.units} />
                                            </td>
                                        ) : (
                                            <>
                                                {stock.sizes.map(
                                                    ({ size, units }) => (
                                                        <td
                                                            key={size.id}
                                                            className={CELL}
                                                        >
                                                            <Figure
                                                                units={units}
                                                            />
                                                        </td>
                                                    ),
                                                )}
                                                <td className={CELL}>
                                                    <Figure
                                                        units={stock.units}
                                                    />
                                                </td>
                                            </>
                                        )}
                                    </tr>
                                </tfoot>
                            )}
                        </table>
                    </div>

                    {/* Phone: one card per colour in place of the table, then a Total card. */}
                    <ul
                        aria-label={TABLE_NAME}
                        className="flex flex-col gap-3 md:hidden"
                    >
                        {colours.map(({ colour, units, sizes }) => (
                            <li
                                key={colour.id}
                                className="flex flex-col gap-2 rounded-lg border p-3"
                            >
                                <p className="flex flex-wrap items-center gap-x-2 text-sm font-medium">
                                    <ColourTag colour={colour} />{' '}
                                    <span aria-hidden="true">·</span>{' '}
                                    <span>
                                        {formatCount(units, locale)}{' '}
                                        {unitsWord(units)}
                                    </span>
                                </p>
                                {!oneSize && (
                                    <SizeChips
                                        sizes={sizes.map(
                                            ({ size, units: n }) => ({
                                                label: size.label,
                                                units: n,
                                            }),
                                        )}
                                        keepZeros
                                        spokenPrefix={`${colour.name}, per size: `}
                                    />
                                )}
                            </li>
                        ))}
                        {showTotals && (
                            <li className="flex flex-col gap-2 rounded-lg border bg-muted/40 p-3">
                                <p className="text-sm font-semibold">Total</p>
                                <p className="text-sm font-medium">
                                    {formatCount(stock.units, locale)}{' '}
                                    {unitsWord(stock.units)}
                                </p>
                                {!oneSize && (
                                    <SizeChips
                                        sizes={stock.sizes.map(
                                            ({ size, units }) => ({
                                                label: size.label,
                                                units,
                                            }),
                                        )}
                                        keepZeros
                                        spokenPrefix="Total per size: "
                                    />
                                )}
                            </li>
                        )}
                    </ul>

                    {showNote && (
                        <p className="text-xs text-muted-foreground">
                            Grey zeros are variants with nothing on the shelf.
                        </p>
                    )}
                    {/* INV-3: the newest restock not undone, or "Not restocked yet.". */}
                    {lastRestock !== undefined && (
                        <p className="text-sm text-muted-foreground">
                            {lastRestock === null ? (
                                'Not restocked yet.'
                            ) : (
                                <>
                                    Last restock:{' '}
                                    {formatDate(
                                        lastRestock.restocked_on,
                                        locale,
                                    )}
                                    , {formatCount(lastRestock.units, locale)}{' '}
                                    {lastRestock.units === 1 ? 'unit' : 'units'}{' '}
                                    ·{' '}
                                    <Link
                                        href={restocksIndex({
                                            query: { product: product.id },
                                        })}
                                        className="font-medium text-primary hover:underline focus-visible:underline"
                                    >
                                        See the restocks of {product.name}
                                    </Link>
                                </>
                            )}
                        </p>
                    )}
                </TabsContent>
                <TabsContent value="barcodes" className={PANEL}>
                    <BarcodesTab
                        product={product}
                        barcodes={barcodes}
                        finishedStock={stock}
                        oneSize={oneSize}
                    />
                </TabsContent>
            </Tabs>

            <UpdateStockDialog
                product={product}
                stock={stock}
                open={updating}
                onOpenChange={setUpdating}
                returnFocusTo={updateButtonRef}
            />
        </CardFrame>
    );
}

/** The card when the stock or the barcodes couldn't be read: the frame and heading stay. */
export function SizesColoursCardError({ reloading }: { reloading: boolean }) {
    return (
        <CardFrame>
            <StockLoadError
                reloading={reloading}
                message="Couldn't load the stock and barcodes."
            />
        </CardFrame>
    );
}

/** The card while the stock and barcodes load: the frame, heading and tabs at once, and 3 placeholder rows. */
export function SizesColoursCardLoading() {
    const [tab, openTab] = useSizesColoursTab();
    const placeholder = (
        <div>
            <span role="status" className="sr-only">
                Loading sizes and colours…
            </span>
            {[0, 1, 2].map((row) => (
                <SkeletonRow key={row} thumb={false} />
            ))}
        </div>
    );

    return (
        <CardFrame>
            <Tabs
                value={tab}
                onValueChange={(value) => openTab(asTab(value))}
                className="gap-4"
            >
                <CardTabList missing={null} />
                <TabsContent value="stock" className={PANEL}>
                    {placeholder}
                </TabsContent>
                <TabsContent value="barcodes" className={PANEL}>
                    {placeholder}
                </TabsContent>
            </Tabs>
        </CardFrame>
    );
}
