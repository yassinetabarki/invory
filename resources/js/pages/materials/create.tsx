import { Form, Head } from '@inertiajs/react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { MaterialForm } from '@/components/materials/material-form';
import { MaterialFormActions } from '@/components/materials/material-form-actions';
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
    photo: null,
    remove_photo: false,
};

const SUBMIT_LABEL = 'Save material';

export default function MaterialsCreate({
    categories,
    units,
}: MaterialCreateProps) {
    const cancelHref = MaterialController.index.url();

    return (
        <>
            <Head title="Add material" />

            {/* The header is inside the form so its Save button submits it. */}
            <Form
                {...MaterialController.store.form()}
                className="flex flex-1 flex-col gap-6 p-4 md:p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <PageHeader
                            title="Add material"
                            description="A primary item you buy and keep in stock: fabric, trims, thread, leather, soles…"
                            actions={
                                <MaterialFormActions
                                    processing={processing}
                                    submitLabel={SUBMIT_LABEL}
                                    cancelHref={cancelHref}
                                />
                            }
                        />
                        <MaterialForm
                            initial={EMPTY_MATERIAL}
                            categories={categories}
                            units={units}
                            errors={errors}
                            processing={processing}
                            submitLabel={SUBMIT_LABEL}
                            cancelHref={cancelHref}
                        />
                    </>
                )}
            </Form>
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
            title: 'Add material',
            href: MaterialController.create(),
        },
    ],
};
