import { Head } from '@inertiajs/react';
import { Tags } from 'lucide-react';
import { ReferenceFormDialog } from '@/components/settings/reference-form-dialog';
import { ReferenceManager } from '@/components/settings/reference-manager';
import {
    archive,
    destroy,
    index,
    restore,
    store,
    update,
} from '@/routes/settings/product-categories';
import type { ProductCategoriesSettingsProps } from '@/types/domain';

const urls = {
    archive: (id: number) => archive.url(id),
    restore: (id: number) => restore.url(id),
    destroy: (id: number) => destroy.url(id),
};

const formUrls = {
    store: store.url(),
    update: (id: number) => update.url(id),
};

export default function ProductCategories({
    categories,
}: ProductCategoriesSettingsProps) {
    return (
        <>
            <Head title="Product categories" />

            <ReferenceManager
                title="Product categories"
                description="The categories you group products by. Rename or archive any of them, or add your own."
                singular="product category"
                addLabel="Add product category"
                emptyDescription="Add the categories you group products by, such as apparel or footwear."
                emptyIcon={Tags}
                rows={categories}
                usedBy={{ singular: 'product', plural: 'products' }}
                urls={urls}
                renderDialog={(dialog) => (
                    <ReferenceFormDialog
                        {...dialog}
                        singular="product category"
                        codeMaxLength={32}
                        urls={formUrls}
                    />
                )}
            />
        </>
    );
}

ProductCategories.layout = {
    breadcrumbs: [
        {
            title: 'Product categories',
            href: index(),
        },
    ],
};
