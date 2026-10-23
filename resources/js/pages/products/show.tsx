import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Pencil, ReceiptText, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { BottleneckCallout } from '@/components/products/bottleneck-callout';
import { CompositionCard } from '@/components/products/composition-card';
import { CostBreakdownCard } from '@/components/products/cost-breakdown-card';
import {
    FinishedStockError,
    FinishedStockFigure,
} from '@/components/products/finished-stock-figure';
import { ProductHeader } from '@/components/products/product-header';
import { ProductHero } from '@/components/products/product-hero';
import { ProductPhoto } from '@/components/products/product-photo';
import { RecentSalesCard } from '@/components/products/recent-sales-card';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { SkeletonCard, SkeletonStat } from '@/components/shared/skeletons';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import type { MaterialRef, Product, ProductShowProps } from '@/types/domain';

const materialHref = (material: MaterialRef) =>
    MaterialController.show.url(material.id);

type ProductDetailsProps = {
    product: Product;
    sales: NonNullable<ProductShowProps['sales_last_30_days']>;
    recentSales: NonNullable<ProductShowProps['recent_sales']>;
    /** The "In stock" figure's data; absent until it lands, and while it is rescued. */
    finishedStock: ProductShowProps['finished_stock'];
};

/** The whole page body; rendered only once the deferred props have landed. */
function ProductDetails({
    product,
    sales,
    recentSales,
    finishedStock,
}: ProductDetailsProps) {
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const deleteButtonRef = useRef<HTMLButtonElement>(null);

    const editHref = ProductController.edit.url(product.id);
    const isActive = product.status === 'active';
    // Only active products can be sold, so only they get the shortcut.
    const recordHref = isActive
        ? SaleController.index.url({
              query: { record: 1, record_product_id: product.id },
          })
        : null;

    function destroy() {
        router.delete(ProductController.destroy.url(product.id), {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
            },
        });
    }

    return (
        <>
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <ProductHeader
                    product={product}
                    actions={
                        <>
                            {recordHref !== null ? (
                                <Button asChild variant="outline">
                                    <Link href={recordHref}>
                                        <ReceiptText aria-hidden="true" />
                                        Record sale
                                    </Link>
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled
                                    title="Only active products can be sold."
                                    aria-describedby="record-sale-hint"
                                >
                                    <ReceiptText aria-hidden="true" />
                                    Record sale
                                </Button>
                            )}
                            {recordHref === null && (
                                <span id="record-sale-hint" className="sr-only">
                                    Only active products can be sold.
                                </span>
                            )}
                            <Button asChild variant="outline">
                                <Link href={editHref}>
                                    <Pencil aria-hidden="true" />
                                    Edit
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                ref={deleteButtonRef}
                                className="text-destructive-foreground hover:text-destructive-foreground"
                                onClick={() => setConfirming(true)}
                            >
                                <Trash2 aria-hidden="true" />
                                Delete
                            </Button>
                        </>
                    }
                />

                <div className="flex flex-col gap-6 lg:flex-row lg:items-start">
                    <ProductPhoto
                        name={product.name}
                        photoUrl={product.photo_url}
                        description={product.description}
                    />

                    <div className="flex min-w-0 flex-1 flex-col gap-4">
                        <ProductHero
                            capacity={product.capacity}
                            unitCost={product.cost.unit_cost}
                            multipleSizes={product.size_list.sizes.length > 1}
                            sales={sales}
                            stock={
                                <Deferred
                                    data="finished_stock"
                                    fallback={<SkeletonStat label="In stock" />}
                                    rescue={({ reloading }) => (
                                        <FinishedStockError
                                            reloading={reloading}
                                        />
                                    )}
                                >
                                    {/* Built eagerly: never read before it lands. */}
                                    {finishedStock && (
                                        <FinishedStockFigure
                                            product={product}
                                            stock={finishedStock}
                                        />
                                    )}
                                </Deferred>
                            }
                        />
                        <BottleneckCallout
                            productName={product.name}
                            capacity={product.capacity}
                            components={product.components}
                            materialHref={materialHref}
                            editHref={editHref}
                        />
                        <CostBreakdownCard
                            cost={product.cost}
                            linesCount={product.components.length}
                            sellingPrice={product.selling_price}
                            margin={product.margin}
                            editHref={editHref}
                        />
                    </div>
                </div>

                <CompositionCard
                    lines={product.components}
                    materialsTotal={product.cost.material_cost}
                    capacity={product.capacity.max_producible}
                    materialHref={materialHref}
                    editHref={editHref}
                />

                <RecentSalesCard
                    sales={recentSales}
                    recordHref={recordHref}
                    viewAllHref={SaleController.index.url({
                        query: { product_id: product.id },
                    })}
                />
            </div>

            <ConfirmDeleteDialog
                open={confirming}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setConfirming(false);
                    }
                }}
                title="Delete product?"
                description={
                    <p>
                        <strong>{product.name}</strong> (SKU {product.sku}) and
                        its composition will be removed. This cannot be undone.
                    </p>
                }
                confirmLabel="Delete product"
                processing={processing}
                returnFocusTo={deleteButtonRef}
                onConfirm={destroy}
            />
        </>
    );
}

/** Mirrors the page (header, hero, cards) so nothing jumps when the content lands. */
function ProductPageSkeleton() {
    return (
        <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div aria-hidden="true" className="flex flex-col gap-2">
                <Skeleton className="h-8 w-64 max-w-full motion-reduce:animate-none" />
                <Skeleton className="h-4 w-40 motion-reduce:animate-none" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <SkeletonStat label="Can produce now" />
                <SkeletonStat label="In stock" />
                <SkeletonStat label="Unit cost" />
                <SkeletonStat label="Sold · last 30 days" />
            </div>
            <SkeletonCard
                title="Cost breakdown"
                label="cost breakdown"
                rows={4}
                thumb={false}
            />
            <SkeletonCard
                title="Composition"
                label="composition"
                rows={4}
                thumb={false}
            />
            <SkeletonCard
                title="Recent sales"
                label="recent sales"
                rows={5}
                thumb={false}
            />
        </div>
    );
}

export default function ProductsShow({
    product,
    sales_last_30_days: sales,
    recent_sales: recentSales,
    finished_stock: finishedStock,
}: ProductShowProps) {
    return (
        <>
            <Head title={product?.name ?? 'Product'} />

            {/* `<Deferred>` children are built eagerly: ProductDetails only
                mounts behind the guard, so `product` is never read before it lands. */}
            <Deferred
                data={['product', 'sales_last_30_days', 'recent_sales']}
                fallback={<ProductPageSkeleton />}
            >
                {product && sales && (
                    <ProductDetails
                        product={product}
                        sales={sales}
                        recentSales={recentSales ?? []}
                        finishedStock={finishedStock}
                    />
                )}
            </Deferred>
        </>
    );
}

ProductsShow.layout = (props: ProductShowProps) => ({
    breadcrumbs: [
        {
            title: 'Products',
            href: ProductController.index(),
        },
        ...(props.product
            ? [
                  {
                      title: props.product.name,
                      href: ProductController.show(props.product.id),
                  },
              ]
            : []),
    ],
});
