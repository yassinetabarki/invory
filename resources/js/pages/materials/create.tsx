import { Form, Head } from '@inertiajs/react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { MaterialForm } from '@/components/materials/material-form';
import { PageHeader } from '@/components/shared/page-header';
import type { MaterialCreateProps, MaterialFormData } from '@/types/domain';

const EMPTY_MATERIAL: MaterialFormData = {
    sku: '',
    name: '',
    category: '',
    unit: '',
    unit_cost: '',
    quantity_on_hand: '',
    reorder_level: '',
    supplier_name: '',
    supplier_reference: '',
    color: '',
    composition: '',
    notes: '',
};

export default function MaterialsCreate({
    categories,
    units,
}: MaterialCreateProps) {
    return (
        <>
            <Head title="New material" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="New material"
                    description="Add a primary material to your inventory."
                />

                <Form
                    {...MaterialController.store.form()}
                    className="max-w-3xl"
                >
                    {({ errors, processing }) => (
                        <MaterialForm
                            initial={EMPTY_MATERIAL}
                            categories={categories}
                            units={units}
                            errors={errors}
                            processing={processing}
                            submitLabel="Create material"
                            cancelHref={MaterialController.index.url()}
                        />
                    )}
                </Form>
            </div>
        </>
    );
}

MaterialsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Materials',
            href: MaterialController.index(),
        },
        {
            title: 'New material',
            href: MaterialController.create(),
        },
    ],
};
