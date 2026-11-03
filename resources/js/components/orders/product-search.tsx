import { Check, Plus, Search, X } from 'lucide-react';
import { useId, useRef, useState } from 'react';
import { useProductSearch } from '@/components/orders/use-product-search';
import { ColourSwatch, ColourTag } from '@/components/shared/colour-tag';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppSettings } from '@/hooks/use-app-settings';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatCount } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { OrderStockProduct } from '@/types/domain';

/** "In stock 10 units", "In stock 1 unit". */
export function inStockUnits(units: number, locale: string): string {
    return `In stock ${formatCount(units, locale)} ${units === 1 ? 'unit' : 'units'}`;
}

/** The amber "Out" of a stock cell: a word as well as a colour. */
const OUT = 'font-semibold text-amber-700 dark:text-amber-400';

type ProductSearchProps = {
    /** The products already on the order, by id: their result shows "On this order". */
    onOrder: ReadonlySet<number>;
    /** The order holds 50 products: "Add to order" can't be pressed. */
    full: boolean;
    onAdd: (product: OrderStockProduct) => void;
    /** "On this order" moves the focus to that product's line. */
    onShowLine: (productId: number) => void;
};

/**
 * "Search products" (INV-13, AC-1): the labelled box, its hint, and while there
 * is a search, the products found with their shelf stock per colour and size.
 * Out of stock never blocks: the amber "Out" only tells. The server finds the
 * products and adds up every figure; nothing is computed here.
 */
export function ProductSearch({
    onOrder,
    full,
    onAdd,
    onShowLine,
}: ProductSearchProps) {
    const id = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const [search, setSearch] = useState('');
    const { state, retry } = useProductSearch(search);
    const hintId = `${id}-hint`;

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-col gap-2">
                <Label htmlFor={`${id}-search`}>Search products</Label>
                <div className="relative">
                    <Search
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        ref={inputRef}
                        id={`${id}-search`}
                        type="search"
                        autoComplete="off"
                        data-action="product"
                        placeholder="Name, SKU, colour or barcode"
                        aria-describedby={hintId}
                        className="h-11 pr-11 pl-9 [&::-webkit-search-cancel-button]:hidden"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    {search !== '' && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Clear search"
                            className="absolute top-1/2 right-0.5 size-10 -translate-y-1/2"
                            onClick={() => {
                                setSearch('');
                                inputRef.current?.focus();
                            }}
                        >
                            <X aria-hidden="true" />
                        </Button>
                    )}
                </div>
                <p id={hintId} className="text-xs text-muted-foreground">
                    In stock is what is on the shelf now. Open orders don't set
                    it aside, and a colour that is out of stock can still be
                    ordered.
                </p>
            </div>

            <div aria-live="polite" className="flex flex-col gap-3">
                {state.status === 'searching' && (
                    <div className="flex flex-col gap-2">
                        <p className="text-sm text-muted-foreground">
                            Searching…
                        </p>
                        <div
                            aria-hidden="true"
                            className="flex gap-3 rounded-lg border p-3"
                        >
                            <Skeleton className="size-12 shrink-0" />
                            <div className="flex flex-1 flex-col gap-2">
                                <Skeleton className="h-4 w-1/3" />
                                <Skeleton className="h-3 w-1/2" />
                                <Skeleton className="h-16 w-full" />
                            </div>
                        </div>
                    </div>
                )}

                {state.status === 'failed' && (
                    <div
                        role="alert"
                        className="flex flex-wrap items-center gap-3 rounded-lg border border-dashed p-3 text-sm"
                    >
                        <span>Couldn't search the products.</span>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={retry}
                        >
                            Try again
                        </Button>
                    </div>
                )}

                {state.status === 'found' &&
                    (state.found.total === 0 ? (
                        <div className="rounded-lg border border-dashed p-4 text-sm">
                            <p className="font-medium">
                                No product matches "{search.trim()}".
                            </p>
                            <p className="text-muted-foreground">
                                Check the spelling, or search by SKU, colour or
                                barcode.
                            </p>
                        </div>
                    ) : (
                        <>
                            <p className="text-sm text-muted-foreground">
                                {state.found.total === 1
                                    ? '1 product found'
                                    : `${state.found.total} products found`}
                            </p>
                            <ul className="flex flex-col gap-3">
                                {state.found.products.map((product) => (
                                    <li key={product.product.id}>
                                        <ProductSearchResult
                                            product={product}
                                            onOrder={onOrder.has(
                                                product.product.id,
                                            )}
                                            full={full}
                                            onAdd={() => onAdd(product)}
                                            onShowLine={() =>
                                                onShowLine(product.product.id)
                                            }
                                        />
                                    </li>
                                ))}
                            </ul>
                            {state.found.total >
                                state.found.products.length && (
                                <p className="text-sm text-muted-foreground">
                                    Showing {state.found.products.length} of{' '}
                                    {state.found.total}. Keep typing to narrow
                                    the list.
                                </p>
                            )}
                        </>
                    ))}
            </div>
        </div>
    );
}

type ResultProps = {
    product: OrderStockProduct;
    onOrder: boolean;
    full: boolean;
    onAdd: () => void;
    onShowLine: () => void;
};

/** One product found: photo, name, SKU, "In stock 10 units", the button, and the stock of each colour and size. */
export function ProductSearchResult({
    product,
    onOrder,
    full,
    onAdd,
    onShowLine,
}: ResultProps) {
    const { locale } = useAppSettings();
    const mobile = useIsMobile();
    const id = useId();
    const name = product.product.name;
    const fullId = `${id}-full`;

    const button = onOrder ? (
        <Button
            type="button"
            variant="outline"
            className="max-sm:h-11 max-sm:w-full"
            onClick={onShowLine}
        >
            <Check aria-hidden="true" />
            On this order
        </Button>
    ) : (
        <Button
            type="button"
            variant="outline"
            aria-label={`Add ${name} to this order`}
            aria-describedby={full ? fullId : undefined}
            disabled={full}
            className="max-sm:h-11 max-sm:w-full"
            onClick={onAdd}
        >
            <Plus aria-hidden="true" />
            Add to order
        </Button>
    );

    return (
        <article
            aria-label={name}
            className="flex min-w-0 flex-col gap-3 rounded-lg border p-3"
        >
            <div className="flex flex-wrap items-start gap-3">
                <PhotoThumb src={product.product.photo_url} alt="" size="sm" />
                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                    <p className="font-bold [overflow-wrap:anywhere]">{name}</p>
                    <p className="font-mono text-sm text-muted-foreground">
                        {product.product.sku}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        {inStockUnits(product.stock.units, locale)}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1 max-sm:w-full">
                    {button}
                    {full && !onOrder && (
                        <p
                            id={fullId}
                            className="text-xs text-muted-foreground"
                        >
                            An order can hold up to 50 products.
                        </p>
                    )}
                </div>
            </div>

            {mobile ? (
                <StockChips product={product} />
            ) : (
                <StockTable product={product} />
            )}
        </article>
    );
}

/** Laptop: the table "Stock of Denim Jacket by colour and size", scrolling inside its own region when too wide. */
function StockTable({ product }: { product: OrderStockProduct }) {
    const { locale } = useAppSettings();
    const caption = `Stock of ${product.product.name} by colour and size`;

    return (
        <div
            role="region"
            aria-label={caption}
            tabIndex={0}
            className="overflow-x-auto rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring"
        >
            <table className="w-full min-w-max border-collapse text-sm">
                <caption className="sr-only">{caption}</caption>
                <thead>
                    <tr className="border-b text-muted-foreground">
                        <th
                            scope="col"
                            className="py-1.5 pr-3 text-left font-medium"
                        >
                            Colour
                        </th>
                        {product.one_size ? (
                            <th
                                scope="col"
                                className="px-2 py-1.5 text-right font-medium"
                            >
                                Stock
                            </th>
                        ) : (
                            product.sizes.map((size) => (
                                <th
                                    key={size.id}
                                    scope="col"
                                    className="px-2 py-1.5 text-right font-medium"
                                >
                                    {size.label}
                                </th>
                            ))
                        )}
                    </tr>
                </thead>
                <tbody>
                    {product.stock.colours.map((entry) => (
                        <tr
                            key={entry.colour.id}
                            className="border-b last:border-b-0"
                        >
                            <th
                                scope="row"
                                className="py-1.5 pr-3 text-left font-normal"
                            >
                                <ColourTag colour={entry.colour} />
                            </th>
                            {(product.one_size
                                ? [
                                      {
                                          size: product.sizes[0],
                                          units: entry.units,
                                      },
                                  ]
                                : entry.sizes
                            ).map(({ size, units }) => (
                                <td
                                    key={size.id}
                                    className={cn(
                                        'px-2 py-1.5 text-right font-mono tabular-nums',
                                        units === 0 && OUT,
                                    )}
                                >
                                    {units === 0
                                        ? 'Out'
                                        : formatCount(units, locale)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

/** Phone: one group per colour with a chip per size ("S 1", "M Out"), or "Out of stock in every size". */
function StockChips({ product }: { product: OrderStockProduct }) {
    const { locale } = useAppSettings();

    return (
        <ul
            aria-label={`Stock of ${product.product.name} by colour and size`}
            className="flex flex-col gap-2"
        >
            {product.stock.colours.map((entry) => (
                <li key={entry.colour.id} className="flex flex-col gap-1">
                    <span className="inline-flex items-center gap-2 text-sm font-medium">
                        <ColourSwatch swatch={entry.colour.swatch} />
                        {entry.colour.name}
                    </span>
                    {entry.units === 0 ? (
                        <span className={cn('text-sm', OUT)}>
                            Out of stock in every size
                        </span>
                    ) : product.one_size ? (
                        <span className="text-sm tabular-nums">
                            {formatCount(entry.units, locale)}
                        </span>
                    ) : (
                        <span className="flex flex-wrap gap-1">
                            {entry.sizes.map(({ size, units }) => (
                                <span
                                    key={size.id}
                                    className="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs"
                                >
                                    <span className="font-semibold">
                                        {size.label}
                                    </span>
                                    <span
                                        className={cn(
                                            'font-mono tabular-nums',
                                            units === 0 && OUT,
                                        )}
                                    >
                                        {units === 0
                                            ? 'Out'
                                            : formatCount(units, locale)}
                                    </span>
                                </span>
                            ))}
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}
