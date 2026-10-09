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

/** The header and the bottom of the form both carry Cancel and Save. */
function saveButtons() {
    return screen.getAllByRole('button', { name: /Save material/ });
}

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
            screen.getByRole('heading', { level: 1, name: 'Add material' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(/A primary item you buy and keep in stock/),
        ).toBeInTheDocument();

        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/materials');
        expect(form).toHaveAttribute('method', 'post');
        expect(screen.getByLabelText(/^SKU/)).toHaveValue('');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('');
        expect(screen.getByLabelText(/^Unit cost/)).toHaveValue('');
        expect(screen.getByLabelText(/^Quantity in stock/)).toHaveValue('');

        const saves = saveButtons();

        expect(saves).toHaveLength(2);

        for (const save of saves) {
            expect(save).toBeEnabled();
        }

        for (const cancel of screen.getAllByRole('link', { name: 'Cancel' })) {
            expect(cancel).toHaveAttribute('href', '/materials');
        }
    });

    it('has the mockup sections, including the optional photo', () => {
        render(<MaterialsCreate categories={categories} units={units} />);

        for (const name of [
            'Identity',
            'Stock and cost',
            'Supplier and notes',
            'Photo (optional)',
        ]) {
            expect(screen.getByRole('region', { name })).toBeInTheDocument();
        }

        // A new material has no photo yet.
        expect(screen.getByText(/drop a photo here or/i)).toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Remove photo' }),
        ).not.toBeInTheDocument();
    });

    it('shows each server error on its own field', () => {
        setFormState({
            errors: {
                sku: 'The sku has already been taken.',
                unit_cost: 'The unit cost must be at least 0.',
                category: 'The category field is required.',
                photo: 'The photo must be a file of type: jpg, png, webp.',
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
        expect(screen.getByLabelText('Photo')).toHaveAccessibleDescription(
            expect.stringContaining('jpg, png, webp'),
        );
        // Fields without an error stay valid.
        expect(screen.getByLabelText(/^Name/)).toBeValid();
        // Focus moves to the first invalid field.
        expect(sku).toHaveFocus();
    });

    it('disables both Save buttons while the request is in flight', () => {
        setFormState({ processing: true });

        render(<MaterialsCreate categories={categories} units={units} />);

        for (const save of saveButtons()) {
            expect(save).toBeDisabled();
        }
    });

    it('submits from the Save button in the header', async () => {
        const user = userEvent.setup();

        render(<MaterialsCreate categories={categories} units={units} />);

        await user.type(screen.getByLabelText(/^SKU/), 'FAB-009');
        await user.click(saveButtons()[0]);

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/materials',
            method: 'post',
            data: { sku: 'FAB-009' },
        });
    });

    it('flushes the material, product and dashboard caches when it saves', async () => {
        const user = userEvent.setup();

        render(<MaterialsCreate categories={categories} units={units} />);

        await user.type(screen.getByLabelText(/^SKU/), 'FAB-009');
        await user.click(saveButtons()[0]);

        expect(inertiaMock.submissions[0].options).toEqual({
            invalidateCacheTags: ['materials', 'products', 'dashboard'],
        });
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
        await user.type(screen.getByLabelText(/^Reorder at/), '50');
        await user.type(screen.getByLabelText(/^Notes/), 'Soft hand');
        // Enter in the last field submits (implicit submission, keyboard only).
        await user.type(
            screen.getByLabelText(/^Quantity in stock/),
            '250{Enter}',
        );

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
                reorder_level: '50',
                notes: 'Soft hand',
            },
        });
    });

    it('sends the chosen photo with the form, and remove_photo as 0', async () => {
        const user = userEvent.setup();
        const photo = new File(['png'], 'button.png', { type: 'image/png' });

        render(<MaterialsCreate categories={categories} units={units} />);

        await user.type(screen.getByLabelText(/^SKU/), 'TRM-100');
        await user.upload(screen.getByLabelText('Photo'), photo);

        expect(screen.getByAltText('New photo preview')).toBeInTheDocument();

        await user.click(saveButtons()[1]);

        expect(inertiaMock.submissions).toHaveLength(1);
        expect(inertiaMock.submissions[0].data).toMatchObject({
            sku: 'TRM-100',
            photo,
            remove_photo: '0',
        });
    });

    it('sends no file when no photo was chosen', async () => {
        const user = userEvent.setup();

        render(<MaterialsCreate categories={categories} units={units} />);

        await user.click(saveButtons()[0]);

        expect(inertiaMock.submissions[0].data).toMatchObject({
            photo: '',
            remove_photo: '0',
        });
    });

    it('defines the Materials > Add material breadcrumbs', () => {
        expect(MaterialsCreate.layout.breadcrumbs.map((c) => c.title)).toEqual([
            'Materials',
            'Add material',
        ]);
    });
});
