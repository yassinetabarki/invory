import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import MaterialsCreate from '@/pages/materials/create';
import { categories, units } from '@/test/fixtures';
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

describe('materials/create page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/materials/create' });
    });

    it('renders an empty form that posts to the store route', () => {
        const { container } = render(
            <MaterialsCreate categories={categories} units={units} />,
        );

        expect(
            screen.getByRole('heading', { level: 1, name: 'New material' }),
        ).toBeInTheDocument();

        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/materials');
        expect(form).toHaveAttribute('method', 'post');
        expect(screen.getByLabelText(/^SKU/)).toHaveValue('');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('');
        expect(screen.getByLabelText(/^Unit cost/)).toHaveValue('');
        expect(
            screen.getByRole('button', { name: 'Create material' }),
        ).toBeEnabled();
        expect(screen.getByRole('link', { name: 'Cancel' })).toHaveAttribute(
            'href',
            '/materials',
        );
    });

    it('shows each server error on its own field', () => {
        setFormState({
            errors: {
                sku: 'The sku has already been taken.',
                unit_cost: 'The unit cost must be at least 0.',
                category: 'The category field is required.',
            },
        });

        render(<MaterialsCreate categories={categories} units={units} />);

        const sku = screen.getByLabelText(/^SKU/);

        expect(sku).toBeInvalid();
        expect(sku).toHaveAccessibleDescription(
            'The sku has already been taken.',
        );
        expect(screen.getByLabelText(/^Unit cost/)).toHaveAccessibleDescription(
            expect.stringContaining('The unit cost must be at least 0.'),
        );
        expect(
            screen.getByText('The category field is required.'),
        ).toBeInTheDocument();
        // Fields without an error stay valid.
        expect(screen.getByLabelText(/^Name/)).toBeValid();
        // Focus moves to the first invalid field.
        expect(sku).toHaveFocus();
    });

    it('disables submit while the request is in flight', () => {
        setFormState({ processing: true });

        render(<MaterialsCreate categories={categories} units={units} />);

        expect(
            screen.getByRole('button', { name: /create material/i }),
        ).toBeDisabled();
    });

    it('submits the typed values with keyboard and mouse', async () => {
        const user = userEvent.setup();

        render(<MaterialsCreate categories={categories} units={units} />);

        await user.type(screen.getByLabelText(/^SKU/), 'FAB-009');
        await user.type(screen.getByLabelText(/^Name/), 'Denim 12oz Indigo');
        await user.click(screen.getByRole('combobox', { name: /Category/ }));
        await user.click(screen.getByRole('option', { name: 'Fabric' }));
        await user.click(
            screen.getByRole('combobox', { name: /Unit of measure/ }),
        );
        await user.click(screen.getByRole('option', { name: 'Metre (m)' }));
        await user.type(screen.getByLabelText(/^Unit cost/), '8.5000');
        await user.type(screen.getByLabelText(/^Quantity on hand/), '250');
        // Submit with the keyboard from the last field.
        await user.type(screen.getByLabelText(/^Notes/), 'Soft hand');
        await user.tab();
        await user.tab();
        await user.keyboard('{Enter}');

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/materials',
            method: 'post',
            data: {
                sku: 'FAB-009',
                name: 'Denim 12oz Indigo',
                category: 'fabric',
                unit: 'm',
                unit_cost: '8.5000',
                quantity_on_hand: '250',
                notes: 'Soft hand',
            },
        });
    });

    it('defines the Materials > New material breadcrumbs', () => {
        expect(MaterialsCreate.layout.breadcrumbs.map((c) => c.title)).toEqual([
            'Materials',
            'New material',
        ]);
    });
});
