import { Deferred, Head, Link, router } from '@inertiajs/react';
import { Pencil, ReceiptText, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import { AddMaterialColourDialog } from '@/components/materials/add-material-colour-dialog';
import { BottleneckCallout } from '@/components/products/bottleneck-callout';
import {
    CapacityByColourCard,
    CapacityByColourCardLoading,
} from '@/components/products/capacity-by-colour-card';
import { CompositionCard } from '@/components/products/composition-card';
import {
    CostBreakdownCard,
    missingCostColours,
} from '@/components/products/cost-breakdown-card';
import {
    FinishedStockError,
    FinishedStockFigure,
} from '@/components/products/finished-stock-figure';
import { ProductHeader } from '@/components/products/product-header';
import { HERO_ROW, ProductHero } from '@/components/products/product-hero';
import { ProductPhoto } from '@/components/products/product-photo';
import { RecentSalesCard } from '@/components/products/recent-sales-card';
import {
    SizesColoursCard,
    SizesColoursCardError,
    SizesColoursCardLoading,
} from '@/components/products/sizes-colours-card';
import { ConfirmDeleteDialog } from '@/components/shared/confirm-delete-dialog';
import { SkeletonCard, SkeletonStat } from '@/components/shared/skeletons';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useAppSettings } from '@/hooks/use-app-settings';
import { formatCount } from '@/lib/format';
import type {
    MaterialRef,
    Product,
    ProductBarcodes,
    ProductShowProps,
} from '@/types/domain';

const materialHref = (material: MaterialRef) =>
    MaterialController.show.url(material.id);

type ProductDetailsProps = {
    product: Product;
    sales: NonNullable<ProductShowProps['sales_last_30_days']>;
    recentSales: NonNullable<ProductShowProps['recent_sales']>;
    /** The "In stock" figure's data; absent until it lands, and while it is rescued. */
    finishedStock: ProductShowProps['finished_stock'];
    /** The Barcodes tab's data and the Delete sentence; absent until it lands, and while it is rescued. */
    barcodes: ProductShowProps['barcodes'];
    /** INV-3: the "Last restock" line; lands with the stock. */
    lastRestock: ProductShowProps['last_restock'];
};

/**
 * "Its 12 barcodes are kept: they come back on any product named Denim Jacket
 * in the same colours and sizes." (one barcode: "Its barcode is kept: it comes
 * back …in the same colour and size."; a "One size" product names the colours only).
 */
function barcodesKept(
    name: string,
    barcodes: ProductBarcodes,
    oneSize: boolean,
    locale: string,
): string {
    const one = barcodes.with_barcode === 1;
    const where = `${one ? 'colour' : 'colours'}${
        oneSize ? '' : ` and ${one ? 'size' : 'sizes'}`
    }`;

    return one
        ? `Its barcode is kept: it comes back on any product named ${name} in the same ${where}.`
        : `Its ${formatCount(barcodes.with_barcode, locale)} barcodes are kept: they come back on any product named ${name} in the same ${where}.`;
}

/** The whole page body; rendered only once the deferred props have landed. */
function ProductDetails({
    product,
    sales,
    recentSales,
    finishedStock,
    barcodes,
    lastRestock,
}: ProductDetailsProps) {
    const { locale } = useAppSettings();
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const deleteButtonRef = useRef<HTMLButtonElement>(null);
    // INV-21-p: "Add Light wash to Denim 12oz" opens its window; the last one asked stays while it closes.
    const [adding, setAdding] = useState<{
        materialId: number;
        colourId: number;
    } | null>(null);
    const [addingOpen, setAddingOpen] = useState(false);
    const addMaterial = product.components.find(
        (line) => line.material.id === adding?.materialId,
    );
    const addColourReading = addMaterial?.stock_by_colour?.find(
        (reading) => reading.colour.id === adding?.colourId,
    );

    function addColour(materialId: number, colourId: number) {
        setAdding({ materialId, colourId });
        setAddingOpen(true);
    }

    // INV-21: the colours a cost-per-colour material lacks: their variants have no unit cost.
    const missingCosts = missingCostColours(product.components);
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
                            missingCosts={missingCosts}
                            onAddColour={addColour}
                            multipleSizes={product.sizes.length > 1}
                            multipleColours={product.colours.length > 1}
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
                            singleColour={product.colours.length === 1}
                            materialHref={materialHref}
                            editHref={editHref}
                            onAddColour={addColour}
                        />
                        {product.capacity_by_colour && (
                            <CapacityByColourCard
                                rows={product.capacity_by_colour}
                                guaranteed={product.capacity.max_producible}
                                best={product.capacity.best}
                                materialHref={materialHref}
                                onAddColour={addColour}
                            />
                        )}
                        <CostBreakdownCard
                            cost={product.cost}
                            linesCount={product.components.length}
                            sellingPrice={product.selling_price}
                            margin={product.margin}
                            editHref={editHref}
                            missingCosts={missingCosts}
                            onAddColour={addColour}
                        />
                    </div>
                </div>

                {/* Waits for the figure's prop and the barcodes: one reload brings all back. */}
                <Deferred
                    data={['finished_stock', 'barcodes']}
                    fallback={<SizesColoursCardLoading />}
                    rescue={({ reloading }) => (
                        <SizesColoursCardError reloading={reloading} />
                    )}
                >
                    {finishedStock && barcodes && (
                        <SizesColoursCard
                            product={product}
                            stock={finishedStock}
                            barcodes={barcodes}
                            manageHref={`${editHref}#sizes`}
                            lastRestock={lastRestock}
                        />
                    )}
                </Deferred>

                <CompositionCard
                    lines={product.components}
                    materialsTotal={product.cost.material_cost}
                    capacity={product.capacity.max_producible}
                    best={product.capacity.best}
                    materialHref={materialHref}
                    colours={product.colours}
                    sizes={product.sizes.map((size) => ({
                        ...size,
                        one_size: product.one_size,
                    }))}
                    editHref={editHref}
                    onAddColour={addColour}
                />

                <RecentSalesCard
                    sales={recentSales}
                    last30Days={sales}
                    recordHref={recordHref}
                    viewAllHref={SaleController.index.url({
                        query: { product_id: product.id },
                    })}
                />
            </div>

            {addMaterial && addColourReading && (
                <AddMaterialColourDialog
                    open={addingOpen}
                    onOpenChange={setAddingOpen}
                    material={addMaterial.material}
                    colour={addColourReading.colour}
                    productName={product.name}
                />
            )}

            <ConfirmDeleteDialog
                open={confirming}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setConfirming(false);
                    }
                }}
                title="Delete product?"
                description={
                    <div className="space-y-2">
                        <p>
                            <strong>{product.name}</strong> (SKU {product.sku})
                            and its composition will be removed. This cannot be
                            undone.
                        </p>
                        {barcodes && barcodes.with_barcode > 0 && (
                            <p>
                                {barcodesKept(
                                    product.name,
                                    barcodes,
                                    product.one_size,
                                    locale,
                                )}
                            </p>
                        )}
                    </div>
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
            <div className={HERO_ROW}>
                <SkeletonStat label="Can produce now" />
                <SkeletonStat label="In stock" />
                <SkeletonStat label="Unit cost" />
            </div>
            <CapacityByColourCardLoading />
            <SkeletonCard
                title="Cost breakdown"
                label="cost breakdown"
                rows={4}
                thumb={false}
            />
            <SkeletonCard
                title="Sizes & colours"
                label="sizes and colours"
                rows={3}
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
    barcodes,
    last_restock: lastRestock,
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
                        barcodes={barcodes}
                        lastRestock={lastRestock}
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
