import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { ProductForm } from '@/components/products/product-form';
import { useReference } from '@/hooks/use-reference';
import { categoryOptions } from '@/lib/reference';
import type { ProductEditProps } from '@/types/domain';

export default function ProductsEdit({ product, statuses }: ProductEditProps) {
    const reference = useReference();

    return (
        <>
            <Head title={`Edit ${product.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Keyed by id: the form's values are only seeded from the product. */}
                <ProductForm
                    key={product.id}
                    product={product}
                    title={`Edit ${product.name}`}
                    description="Details, photo and the materials that make one unit."
                    // An archived category the product already uses stays listed.
                    categories={categoryOptions(
                        reference.product_categories,
                        product.category,
                    )}
                    statuses={statuses}
                    action={{
                        url: ProductController.update.url(product.id),
                        method: 'put',
                    }}
                    cancelHref={ProductController.show.url(product.id)}
                    submitLabel="Save product"
                />
            </div>
        </>
    );
}

ProductsEdit.layout = (props: ProductEditProps) => ({
    breadcrumbs: [
        {
            title: 'Products',
            href: ProductController.index(),
        },
        {
            title: props.product.name,
            href: ProductController.show(props.product.id),
        },
        {
            title: 'Edit',
            href: ProductController.edit(props.product.id),
        },
    ],
});
