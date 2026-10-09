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
} from '@/routes/settings/material-categories';
import type { MaterialCategoriesSettingsProps } from '@/types/domain';

const urls = {
    archive: (id: number) => archive.url(id),
    restore: (id: number) => restore.url(id),
    destroy: (id: number) => destroy.url(id),
};

const formUrls = {
    store: store.url(),
    update: (id: number) => update.url(id),
};

export default function MaterialCategories({
    categories,
}: MaterialCategoriesSettingsProps) {
    return (
        <>
            <Head title="Material categories" />

            <ReferenceManager
                title="Material categories"
                description="The categories you group materials by. Rename or archive any of them, or add your own."
                singular="material category"
                addLabel="Add material category"
                emptyDescription="Add the categories you group materials by, such as fabric or trim."
                emptyIcon={Tags}
                rows={categories}
                usedBy={{ singular: 'material', plural: 'materials' }}
                urls={urls}
                renderDialog={(dialog) => (
                    <ReferenceFormDialog
                        {...dialog}
                        singular="material category"
                        codeMaxLength={32}
                        urls={formUrls}
                    />
                )}
            />
        </>
    );
}

MaterialCategories.layout = {
    breadcrumbs: [
        {
            title: 'Material categories',
            href: index(),
        },
    ],
};
