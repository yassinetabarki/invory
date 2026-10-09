import { Form, Head } from '@inertiajs/react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { MaterialForm } from '@/components/materials/material-form';
import { MaterialFormActions } from '@/components/materials/material-form-actions';
import { PageHeader } from '@/components/shared/page-header';
import { invalidatedBy } from '@/lib/cache-tags';
import type {
    Material,
    MaterialEditProps,
    MaterialFormData,
} from '@/types/domain';

/** Decimals stay strings; null becomes '' (the form's "empty"). */
function toFormData(material: Material): MaterialFormData {
    return {
        sku: material.sku,
        name: material.name,
        category: material.category,
        unit: material.unit,
        unit_cost: material.unit_cost,
        quantity_on_hand: material.quantity_on_hand,
        reorder_level: material.reorder_level ?? '',
        supplier_name: material.supplier_name ?? '',
        supplier_reference: material.supplier_reference ?? '',
        color: material.color ?? '',
        composition: material.composition ?? '',
        notes: material.notes ?? '',
        photo: null,
        remove_photo: false,
    };
}

const SUBMIT_LABEL = 'Save material';

export default function MaterialsEdit({
    categories,
    units,
    material,
}: MaterialEditProps) {
    const cancelHref = MaterialController.show.url(material);

    return (
        <>
            <Head title={`Edit ${material.name}`} />

            {/* Keyed by id: the form's `initial` only seeds the inputs. The
                header is inside the form so its Save button submits it. */}
            <Form
                key={material.id}
                {...MaterialController.update.form(material)}
                invalidateCacheTags={invalidatedBy('material')}
                className="flex flex-1 flex-col gap-6 p-4 md:p-6"
            >
                {({ errors, processing }) => (
                    <>
                        <PageHeader
                            title={`Edit ${material.name}`}
                            description={`${material.sku} · ${material.category_label}`}
                            actions={
                                <MaterialFormActions
                                    processing={processing}
                                    submitLabel={SUBMIT_LABEL}
                                    cancelHref={cancelHref}
                                />
                            }
                        />
                        <MaterialForm
                            initial={toFormData(material)}
                            categories={categories}
                            units={units}
                            errors={errors}
                            processing={processing}
                            submitLabel={SUBMIT_LABEL}
                            cancelHref={cancelHref}
                            photoUrl={material.photo_url}
                        />
                    </>
                )}
            </Form>
        </>
    );
}

MaterialsEdit.layout = (props: MaterialEditProps) => ({
    breadcrumbs: [
        {
            title: 'Materials',
            href: MaterialController.index(),
        },
        {
            title: props.material.name,
            href: MaterialController.show(props.material),
        },
        {
            title: 'Edit',
            href: MaterialController.edit(props.material),
        },
    ],
});
