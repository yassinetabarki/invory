import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import MaterialsEdit from '@/pages/materials/edit';
import { categories, denim, thread, units } from '@/test/fixtures';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';

// `<Form>` is replaced by a plain <form> whose render prop receives the
// `errors` / `processing` set with setFormState(); see resources/js/test/inertia.tsx.
vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('materials/edit page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/materials/1/edit' });
    });

    it('pre-fills the form with the material, decimals kept as strings', () => {
        const { container } = render(
            <MaterialsEdit
                categories={categories}
                units={units}
                material={denim}
            />,
        );

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Edit Denim 12oz Indigo',
            }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText(/^SKU/)).toHaveValue('FAB-001');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('Denim 12oz Indigo');
        expect(screen.getByLabelText(/^Unit cost/)).toHaveValue('8.5000');
        expect(screen.getByLabelText(/^Quantity on hand/)).toHaveValue(
            '250.0000',
        );
        expect(screen.getByLabelText(/^Reorder level/)).toHaveValue('50.0000');
        expect(screen.getByLabelText(/^Supplier$/)).toHaveValue(
            'Tessuti Rossi',
        );
        expect(screen.getByLabelText(/^Notes/)).toHaveValue(
            'First line\nSecond line',
        );
        expect(
            screen.getByRole('combobox', { name: /Category/ }),
        ).toHaveTextContent('Fabric');
        expect(
            screen.getByRole('combobox', { name: /Unit of measure/ }),
        ).toHaveTextContent('Metre (m)');

        // Spoofed PUT through Wayfinder's form helper.
        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/materials/1?_method=PUT');
        expect(form).toHaveAttribute('method', 'post');
        expect(
            screen.getByRole('button', { name: 'Save changes' }),
        ).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/materials/1',
        );
    });

    it('renders null fields as empty inputs', () => {
        render(
            <MaterialsEdit
                categories={categories}
                units={units}
                material={thread}
            />,
        );

        expect(screen.getByLabelText(/^Reorder level/)).toHaveValue('');
        expect(screen.getByLabelText(/^Supplier$/)).toHaveValue('');
        expect(screen.getByLabelText(/^Colour/)).toHaveValue('');
        expect(screen.getByLabelText(/^Notes/)).toHaveValue('');
    });

    it('shows server errors on the right fields', () => {
        setFormState({
            errors: { sku: 'The sku has already been taken.' },
        });

        render(
            <MaterialsEdit
                categories={categories}
                units={units}
                material={denim}
            />,
        );

        expect(screen.getByLabelText(/^SKU/)).toBeInvalid();
        expect(screen.getByLabelText(/^SKU/)).toHaveAccessibleDescription(
            'The sku has already been taken.',
        );
        expect(screen.getByLabelText(/^Name/)).toBeValid();
    });

    it('submits the edited quantity to the update route', async () => {
        const user = userEvent.setup();

        render(
            <MaterialsEdit
                categories={categories}
                units={units}
                material={denim}
            />,
        );

        const quantity = screen.getByLabelText(/^Quantity on hand/);

        await user.clear(quantity);
        await user.type(quantity, '300');
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/materials/1?_method=PUT',
            method: 'post',
            data: {
                sku: 'FAB-001',
                category: 'fabric',
                unit: 'm',
                quantity_on_hand: '300',
            },
        });
    });

    it('shows another material when navigated to (keyed by id)', () => {
        const { rerender } = render(
            <MaterialsEdit
                categories={categories}
                units={units}
                material={denim}
            />,
        );

        rerender(
            <MaterialsEdit
                categories={categories}
                units={units}
                material={thread}
            />,
        );

        expect(screen.getByLabelText(/^SKU/)).toHaveValue('THR-001');
    });

    it('defines the Materials > name > Edit breadcrumbs', () => {
        const { breadcrumbs } = MaterialsEdit.layout({
            categories,
            units,
            material: denim,
        });

        expect(breadcrumbs.map((c) => c.title)).toEqual([
            'Materials',
            'Denim 12oz Indigo',
            'Edit',
        ]);
        expect(breadcrumbs[1].href).toMatchObject({ url: '/materials/1' });
    });
});
