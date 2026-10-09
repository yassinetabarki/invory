import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import MaterialsEdit from '@/pages/materials/edit';
import { button, denim, thread } from '@/test/fixtures';
import {
    archived,
    referenceData,
    referenceMaterialCategories,
    referenceUnits,
} from '@/test/fixtures/reference';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { Material } from '@/types/domain';

// `<Form>` is replaced by a plain <form> whose render prop receives the
// `errors` / `processing` set with setFormState(); see resources/js/test/inertia.tsx.
vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderEdit(material: Material) {
    return render(<MaterialsEdit material={material} />);
}

/** The header and the bottom of the form both carry Cancel and Save. */
function saveButtons() {
    return screen.getAllByRole('button', { name: /Save material/ });
}

describe('materials/edit page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/materials/1/edit' });
    });

    it('pre-fills the form with the material, decimals kept as strings', () => {
        const { container } = renderEdit(denim);

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Edit Denim 12oz Indigo',
            }),
        ).toBeInTheDocument();
        expect(screen.getByText('FAB-001 · Fabric')).toBeInTheDocument();
        expect(screen.getByLabelText(/^SKU/)).toHaveValue('FAB-001');
        expect(screen.getByLabelText(/^Name/)).toHaveValue('Denim 12oz Indigo');
        expect(screen.getByLabelText(/^Unit cost/)).toHaveValue('8.5000');
        expect(screen.getByLabelText(/^Quantity in stock/)).toHaveValue(
            '250.0000',
        );
        expect(screen.getByLabelText(/^Reorder at/)).toHaveValue('50.0000');
        expect(screen.getByLabelText('Supplier (optional)')).toHaveValue(
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

        // Spoofed PUT through Wayfinder's form helper (multipart-safe).
        const form = container.querySelector('form');

        expect(form).toHaveAttribute('action', '/materials/1?_method=PUT');
        expect(form).toHaveAttribute('method', 'post');

        expect(saveButtons()).toHaveLength(2);

        for (const cancel of screen.getAllByRole('link', { name: 'Cancel' })) {
            expect(cancel).toHaveAttribute('href', '/materials/1');
        }
    });

    it('renders null fields as empty inputs', () => {
        renderEdit(thread);

        expect(screen.getByLabelText(/^Reorder at/)).toHaveValue('');
        expect(screen.getByLabelText('Supplier (optional)')).toHaveValue('');
        expect(screen.getByLabelText(/^Colour/)).toHaveValue('');
        expect(screen.getByLabelText(/^Notes/)).toHaveValue('');
    });

    it('shows server errors on the right fields', () => {
        setFormState({
            errors: { sku: 'The sku has already been taken.' },
        });

        renderEdit(denim);

        expect(screen.getByLabelText(/^SKU/)).toBeInvalid();
        expect(screen.getByLabelText(/^SKU/)).toHaveAccessibleDescription(
            'The sku has already been taken.',
        );
        expect(screen.getByLabelText(/^Name/)).toBeValid();
    });

    it('disables Save while the request is in flight', () => {
        setFormState({ processing: true });

        renderEdit(denim);

        for (const save of saveButtons()) {
            expect(save).toBeDisabled();
        }
    });

    it('flushes the material, product and dashboard caches when it saves', async () => {
        const user = userEvent.setup();

        renderEdit(denim);
        await user.click(saveButtons()[0]);

        expect(inertiaMock.submissions[0].options).toEqual({
            invalidateCacheTags: ['materials', 'products', 'dashboard'],
        });
    });

    it('submits the edited quantity to the update route', async () => {
        const user = userEvent.setup();

        renderEdit(denim);

        const quantity = screen.getByLabelText(/^Quantity in stock/);

        await user.clear(quantity);
        await user.type(quantity, '300');
        await user.click(saveButtons()[0]);

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

    describe('photo', () => {
        it('shows a drop zone when the material has no photo', () => {
            renderEdit(denim);

            expect(
                screen.getByText(/drop a photo here or/i),
            ).toBeInTheDocument();
            expect(
                screen.queryByAltText('Current photo'),
            ).not.toBeInTheDocument();
        });

        it('shows the current photo with Replace photo and Remove', () => {
            renderEdit(button);

            expect(screen.getByAltText('Current photo')).toHaveAttribute(
                'src',
                '/mock/photos/mat-btn-017.svg',
            );
            expect(screen.getByText('Replace photo')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Remove photo' }),
            ).toBeInTheDocument();
        });

        it('keeps the photo and sends no file by default', async () => {
            const user = userEvent.setup();

            renderEdit(button);
            await user.click(saveButtons()[1]);

            expect(inertiaMock.submissions[0].data).toMatchObject({
                photo: '',
                remove_photo: '0',
            });
        });

        it('sends the replacement file', async () => {
            const user = userEvent.setup();
            const replacement = new File(['webp'], 'button-new.webp', {
                type: 'image/webp',
            });

            renderEdit(button);
            await user.upload(screen.getByLabelText('Photo'), replacement);

            expect(
                screen.getByAltText('New photo preview'),
            ).toBeInTheDocument();

            await user.click(saveButtons()[0]);

            expect(inertiaMock.submissions[0].data).toMatchObject({
                photo: replacement,
                remove_photo: '0',
            });
        });

        it('sends remove_photo as 1 after Remove, and 0 again after changing the mind', async () => {
            const user = userEvent.setup();

            renderEdit(button);
            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );

            expect(
                screen.getByText(
                    'The current photo will be removed when you save.',
                ),
            ).toBeInTheDocument();

            await user.click(saveButtons()[0]);

            expect(inertiaMock.submissions[0].data).toMatchObject({
                remove_photo: '1',
            });

            await user.click(
                screen.getByRole('button', { name: 'Keep current photo' }),
            );
            await user.click(saveButtons()[0]);

            expect(inertiaMock.submissions[1].data).toMatchObject({
                remove_photo: '0',
            });
        });
    });

    it('shows another material when navigated to (keyed by id)', () => {
        const { rerender } = renderEdit(denim);

        rerender(<MaterialsEdit material={thread} />);

        expect(screen.getByLabelText(/^SKU/)).toHaveValue('THR-001');
    });

    it('defines the Materials > name > Edit breadcrumbs', () => {
        const { breadcrumbs } = MaterialsEdit.layout({ material: denim });

        expect(breadcrumbs.map((c) => c.title)).toEqual([
            'Materials',
            'Denim 12oz Indigo',
            'Edit',
        ]);
        expect(breadcrumbs[1].href).toMatchObject({ url: '/materials/1' });
    });
});

describe('materials/edit page options', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    function optionNames() {
        return within(screen.getByRole('listbox'))
            .getAllByRole('option')
            .map((option) => option.textContent);
    }

    it('keeps the archived category and unit the material uses, and hides other archived ones', async () => {
        const user = userEvent.setup();

        setPageProps({
            reference: referenceData({
                material_categories: [
                    archived(referenceMaterialCategories[0]),
                    archived(referenceMaterialCategories[1]),
                    referenceMaterialCategories[2],
                ],
                units: [
                    archived(referenceUnits[0]),
                    archived(referenceUnits[1]),
                    referenceUnits[2],
                ],
            }),
        });
        renderEdit(denim); // category 'fabric', unit 'm'

        expect(
            screen.getByRole('combobox', { name: /Category/ }),
        ).toHaveTextContent('Fabric');
        expect(
            screen.getByRole('combobox', { name: /Unit of measure/ }),
        ).toHaveTextContent('Metre (m)');

        await user.click(screen.getByRole('combobox', { name: /Category/ }));
        expect(optionNames()).toEqual(['Fabric', 'Interlining']);
        await user.keyboard('{Escape}');

        await user.click(
            screen.getByRole('combobox', { name: /Unit of measure/ }),
        );
        expect(optionNames()).toEqual(['Metre (m)', 'Yard (yd)']);
    });
});
