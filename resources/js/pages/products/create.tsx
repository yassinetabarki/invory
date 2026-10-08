import { Head } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { ProductForm } from '@/components/products/product-form';
import type { ProductCreateProps } from '@/types/domain';

export default function ProductsCreate({
    categories,
    statuses,
    materials,
}: ProductCreateProps) {
    return (
        <>
            <Head title="Add product" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <ProductForm
                    title="Add product"
                    description="Details, photo and the materials that make one unit."
                    categories={categories}
                    statuses={statuses}
                    materials={materials}
                    action={{
                        url: ProductController.store.url(),
                        method: 'post',
                    }}
                    cancelHref={ProductController.index.url()}
                    submitLabel="Save product"
                />
            </div>
        </>
    );
}

ProductsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: ProductController.index(),
        },
        {
            title: 'Add product',
            href: ProductController.create(),
        },
    ],
};
