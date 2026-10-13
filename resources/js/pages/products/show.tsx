import { Head, Link, router } from '@inertiajs/react';
import { Pencil, ReceiptText, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { BottleneckCallout } from '@/components/products/bottleneck-callout';
import { CompositionCard } from '@/components/products/composition-card';
import { CostBreakdownCard } from '@/components/products/cost-breakdown-card';
import { ProductHeader } from '@/components/products/product-header';
import { ProductHero } from '@/components/products/product-hero';
import { ProductPhoto } from '@/components/products/product-photo';
import { RecentSalesCard } from '@/components/products/recent-sales-card';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import { invalidatedBy } from '@/lib/cache-tags';
import type { MaterialRef, ProductShowProps } from '@/types/domain';

const materialHref = (material: MaterialRef) =>
    MaterialController.show.url(material.id);

export default function ProductsShow({
    product,
    sales_last_30_days: sales,
    recent_sales: recentSales,
}: ProductShowProps) {
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
            invalidateCacheTags: invalidatedBy('product'),
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
            },
        });
    }

    return (
        <>
            <Head title={product.name} />

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
                            sales={sales}
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

ProductsShow.layout = (props: ProductShowProps) => ({
    breadcrumbs: [
        {
            title: 'Products',
            href: ProductController.index(),
        },
        {
            title: props.product.name,
            href: ProductController.show(props.product.id),
        },
    ],
});
