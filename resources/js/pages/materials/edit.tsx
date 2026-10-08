import { Form, Head } from '@inertiajs/react';
import MaterialController from '@/actions/App/Http/Controllers/MaterialController';
import { MaterialForm } from '@/components/materials/material-form';
import { PageHeader } from '@/components/shared/page-header';
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
    };
}

export default function MaterialsEdit({
    categories,
    units,
    material,
}: MaterialEditProps) {
    return (
        <>
            <Head title={`Edit ${material.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={`Edit ${material.name}`}
                    description={`${material.sku} · ${material.category_label}`}
                />

                {/* Keyed by id: the form's `initial` only seeds the inputs. */}
                <Form
                    key={material.id}
                    {...MaterialController.update.form(material)}
                    className="max-w-3xl"
                >
                    {({ errors, processing }) => (
                        <MaterialForm
                            initial={toFormData(material)}
                            categories={categories}
                            units={units}
                            errors={errors}
                            processing={processing}
                            submitLabel="Save changes"
                            cancelHref={MaterialController.show.url(material)}
                        />
                    )}
                </Form>
            </div>
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
