import { Link } from '@inertiajs/react';
import { Eye, Pencil } from 'lucide-react';
import { PriceMargin } from '@/components/products/price-margin';
import { CapacityBadge } from '@/components/shared/capacity-badge';
import { Money } from '@/components/shared/money';
import { PhotoThumb } from '@/components/shared/photo-thumb';
import { ProductStatusBadge } from '@/components/shared/product-status-badge';
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
import { CACHE_TAGS, prefetchProps } from '@/lib/cache-tags';
import { EMPTY_VALUE, formatCount } from '@/lib/format';
import type { MaterialRef, ProductListItem } from '@/types/domain';

type ProductsTableProps = {
    products: ProductListItem[];
    /** URL builders (Wayfinder in the page, plain functions in tests). */
    hrefs: {
        show: (product: ProductListItem) => string;
        edit: (product: ProductListItem) => string;
        material: (material: MaterialRef) => string;
    };
};

type RowProps = Pick<ProductsTableProps, 'hrefs'> & {
    product: ProductListItem;
};

/** "4 materials", "1 material". */
function materialsLabel(count: number): string {
    return `${count} ${count === 1 ? 'material' : 'materials'}`;
}

/** The material that limits the product, as a link, or an em dash. */
function Bottleneck({ product, hrefs }: RowProps) {
    const { bottleneck } = product.capacity;

    if (bottleneck === null) {
        return <span className="text-muted-foreground">{EMPTY_VALUE}</span>;
    }

    return (
        <Link
            {...prefetchProps(CACHE_TAGS.materials)}
            href={hrefs.material(bottleneck)}
            className="font-medium hover:underline focus-visible:underline"
        >
            {bottleneck.name}
        </Link>
    );
}

/** Units sold in the last 30 days; an em dash when there were none. */
function SoldLastThirtyDays({ product }: { product: ProductListItem }) {
    const { locale } = useAppSettings();

    return (
        <span className="tabular-nums">
            {product.units_sold_last_30_days === 0
                ? EMPTY_VALUE
                : formatCount(product.units_sold_last_30_days, locale)}
        </span>
    );
}

function IconActions({ product, hrefs }: RowProps) {
    return (
        <div className="flex justify-end gap-1">
            <Button asChild variant="ghost" size="icon">
                <Link
                    {...prefetchProps(CACHE_TAGS.products)}
                    href={hrefs.show(product)}
                    aria-label={`View ${product.name}`}
                    title="View"
                >
                    <Eye aria-hidden="true" />
                </Link>
            </Button>
            <Button asChild variant="ghost" size="icon">
                <Link
                    {...prefetchProps(CACHE_TAGS.products)}
                    href={hrefs.edit(product)}
                    aria-label={`Edit ${product.name}`}
                    title="Edit"
                >
                    <Pencil aria-hidden="true" />
                </Link>
            </Button>
        </div>
    );
}

function TextActions({ product, hrefs }: RowProps) {
    return (
        <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm">
                <Link
                    {...prefetchProps(CACHE_TAGS.products)}
                    href={hrefs.show(product)}
                    aria-label={`View ${product.name}`}
                >
                    <Eye aria-hidden="true" />
                    View
                </Link>
            </Button>
            <Button asChild variant="outline" size="sm">
                <Link
                    {...prefetchProps(CACHE_TAGS.products)}
                    href={hrefs.edit(product)}
                    aria-label={`Edit ${product.name}`}
                >
                    <Pencil aria-hidden="true" />
                    Edit
                </Link>
            </Button>
        </div>
    );
}

/**
 * Products as a table from `md` up and as stacked cards below it. Cost,
 * margin and capacity come from the server; this only formats them.
 */
export function ProductsTable({ products, hrefs }: ProductsTableProps) {
    return (
        <>
            {/* >= md: table */}
            <div className="hidden rounded-lg border md:block">
                <Table>
                    <TableCaption className="sr-only">Products</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead scope="col">Product</TableHead>
                            <TableHead scope="col">Category</TableHead>
                            <TableHead scope="col">Status</TableHead>
                            <TableHead scope="col" className="text-right">
                                Unit cost
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                Price · margin
                            </TableHead>
                            <TableHead scope="col">Can produce</TableHead>
                            <TableHead scope="col">Bottleneck</TableHead>
                            <TableHead scope="col" className="text-right">
                                Sold (30 d)
                            </TableHead>
                            <TableHead scope="col" className="text-right">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {products.map((product) => (
                            <TableRow key={product.id}>
                                <TableCell className="min-w-56">
                                    <div className="flex items-center gap-3">
                                        <PhotoThumb
                                            src={product.photo_url}
                                            alt=""
                                            size="md"
                                        />
                                        <div className="flex min-w-0 flex-col">
                                            <Link
                                                {...prefetchProps(
                                                    CACHE_TAGS.products,
                                                )}
                                                href={hrefs.show(product)}
                                                className="font-semibold whitespace-normal hover:underline focus-visible:underline"
                                            >
                                                {product.name}
                                            </Link>
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {product.sku} ·{' '}
                                                {materialsLabel(
                                                    product.components_count,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell className="text-muted-foreground">
                                    {product.category_label}
                                </TableCell>
                                <TableCell>
                                    <ProductStatusBadge
                                        status={product.status}
                                        label={product.status_label}
                                    />
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    <Money value={product.unit_cost} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    <PriceMargin
                                        sellingPrice={product.selling_price}
                                        margin={product.margin}
                                        className="items-end"
                                    />
                                </TableCell>
                                <TableCell>
                                    <CapacityBadge
                                        value={product.capacity.max_producible}
                                    />
                                </TableCell>
                                <TableCell className="max-w-56 whitespace-normal">
                                    <Bottleneck
                                        product={product}
                                        hrefs={hrefs}
                                    />
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground">
                                    <SoldLastThirtyDays product={product} />
                                </TableCell>
                                <TableCell>
                                    <IconActions
                                        product={product}
                                        hrefs={hrefs}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            {/* < md: stacked cards */}
            <ul aria-label="Products" className="space-y-3 md:hidden">
                {products.map((product) => (
                    <li
                        key={product.id}
                        className="space-y-3 rounded-lg border bg-card p-4 text-card-foreground"
                    >
                        <div className="flex items-start gap-3">
                            <PhotoThumb
                                src={product.photo_url}
                                alt=""
                                size="md"
                            />
                            <div className="min-w-0 flex-1">
                                <Link
                                    {...prefetchProps(CACHE_TAGS.products)}
                                    href={hrefs.show(product)}
                                    className="font-semibold break-words hover:underline focus-visible:underline"
                                >
                                    {product.name}
                                </Link>
                                <p className="font-mono text-xs text-muted-foreground">
                                    {product.sku} ·{' '}
                                    {materialsLabel(product.components_count)}
                                </p>
                            </div>
                            <ProductStatusBadge
                                status={product.status}
                                label={product.status_label}
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <span className="text-xs text-muted-foreground">
                                Can produce
                            </span>
                            <CapacityBadge
                                value={product.capacity.max_producible}
                            />
                        </div>

                        <dl className="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Category
                                </dt>
                                <dd>{product.category_label}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Unit cost
                                </dt>
                                <dd className="font-semibold tabular-nums">
                                    <Money value={product.unit_cost} />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Price · margin
                                </dt>
                                <dd className="tabular-nums">
                                    <PriceMargin
                                        sellingPrice={product.selling_price}
                                        margin={product.margin}
                                    />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Sold (30 d)
                                </dt>
                                <dd className="text-muted-foreground">
                                    <SoldLastThirtyDays product={product} />
                                </dd>
                            </div>
                            <div className="col-span-2">
                                <dt className="text-xs text-muted-foreground">
                                    Bottleneck
                                </dt>
                                <dd>
                                    <Bottleneck
                                        product={product}
                                        hrefs={hrefs}
                                    />
                                </dd>
                            </div>
                        </dl>

                        <TextActions product={product} hrefs={hrefs} />
                    </li>
                ))}
            </ul>
        </>
    );
}
