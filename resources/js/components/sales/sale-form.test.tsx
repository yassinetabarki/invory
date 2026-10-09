import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SaleForm } from '@/components/sales/sale-form';
import { invalidatedBy } from '@/lib/cache-tags';
import {
    productRefs,
    recentSales,
    saleOfInactiveProduct,
    saleProducts,
} from '@/test/fixtures/sales';
import {
    inertiaMock,
    resetInertiaMock,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { SaleProductOption } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const ACTIVE_OPTIONS = [
    'Canvas Low Sneaker · PRD-SNK-003',
    'Classic White T-shirt · PRD-TSH-002',
    'Denim Jacket · PRD-JKT-001',
    'Denim Jeans 5-pocket · PRD-JNS-005',
    'Leather Belt · PRD-BLT-007',
    'Leather Tote · PRD-TOT-004',
];

function renderForm(props: Partial<ComponentProps<typeof SaleForm>> = {}) {
    const onClose = vi.fn();
    const user = userEvent.setup();

    render(
        <SaleForm
            sale={null}
            products={saleProducts}
            initialProductId={null}
            onClose={onClose}
            {...props}
        />,
    );

    return { onClose, user };
}

type User = ReturnType<typeof userEvent.setup>;

async function openProducts(user: User) {
    await user.click(screen.getByRole('combobox', { name: 'Product' }));
}

async function pickProduct(user: User, name: string) {
    await openProducts(user);
    await user.click(screen.getByRole('option', { name }));
}

const field = {
    quantity: () => screen.getByLabelText('Quantity'),
    price: () => screen.getByLabelText('Unit price'),
    date: () => screen.getByLabelText('Date'),
    note: () => screen.getByLabelText(/^Note/),
};

describe('<SaleForm>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('record a sale', () => {
        it('has the mockup fields, hint, info and buttons', () => {
            renderForm();

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Choose a product…');
            expect(
                screen.getByText('Only active products are listed.'),
            ).toBeVisible();
            expect(field.quantity()).toHaveAttribute('inputmode', 'numeric');
            expect(field.quantity()).toHaveAttribute('placeholder', '0');
            expect(field.price()).toHaveAttribute('inputmode', 'decimal');
            expect(field.date()).toHaveAttribute('type', 'date');
            expect(field.note()).toHaveAttribute(
                'placeholder',
                'Channel, customer or order number',
            );
            expect(screen.getByText('(optional)')).toBeVisible();
            expect(
                screen.getByText(
                    "The total is calculated when you save. Recording a sale doesn't change material stock.",
                ),
            ).toBeVisible();
            expect(
                screen.getByRole('button', { name: 'Save sale' }),
            ).toBeEnabled();
            expect(
                screen.getByRole('button', { name: 'Cancel' }),
            ).toBeEnabled();
        });

        it('starts empty, dated today', () => {
            renderForm();

            expect(field.quantity()).toHaveValue('');
            expect(field.price()).toHaveValue('');
            expect(field.note()).toHaveValue('');
            expect(field.date()).toHaveValue('2026-10-08');
        });

        it("takes the date's default and maximum from app.today", () => {
            setPageProps({
                app: { currency: 'EUR', locale: 'en', today: '2026-11-15' },
            });

            renderForm();

            expect(field.date()).toHaveValue('2026-11-15');
            expect(field.date()).toHaveAttribute('max', '2026-11-15');
        });

        it('does not allow a future date', () => {
            renderForm();

            expect(field.date()).toHaveAttribute('max', '2026-10-08');
        });

        it('shows the currency in front of the unit price', () => {
            renderForm();

            expect(screen.getByText('€')).toBeVisible();
            expect(field.price()).toHaveAccessibleDescription('€');
        });

        it("uses the installation's own currency code", () => {
            setPageProps({
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });

            renderForm();

            expect(screen.getByText('TND')).toBeVisible();
        });
    });

    describe('product list', () => {
        it('lists active products only, by name', async () => {
            const { user } = renderForm();

            await openProducts(user);

            expect(
                screen
                    .getAllByRole('option')
                    .map((option) => option.textContent),
            ).toEqual(ACTIVE_OPTIONS);
            expect(
                screen.queryByRole('option', { name: /Canvas Chore Jacket/ }),
            ).not.toBeInTheDocument();
        });

        it("keeps the sale's own product when editing a sale of an inactive one", async () => {
            const { user } = renderForm({ sale: saleOfInactiveProduct });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent(
                'Canvas Chore Jacket · PRD-JKT-006 (not active)',
            );

            await openProducts(user);

            expect(
                screen
                    .getAllByRole('option')
                    .map((option) => option.textContent),
            ).toEqual([
                'Canvas Chore Jacket · PRD-JKT-006 (not active)',
                ...ACTIVE_OPTIONS,
            ]);
        });

        it("does not list an inactive product that is not the sale's own", async () => {
            const { user } = renderForm({ sale: recentSales[3] });

            await openProducts(user);

            expect(
                screen.queryByRole('option', { name: /Canvas Chore Jacket/ }),
            ).not.toBeInTheDocument();
        });

        it('pre-selects the product the page asked for, with its price', () => {
            renderForm({ initialProductId: 1 });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jacket · PRD-JKT-001');
            expect(field.price()).toHaveValue('89.0000');
        });

        it('does not pre-select a product that cannot be sold', () => {
            renderForm({ initialProductId: 6 });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Choose a product…');
            expect(field.price()).toHaveValue('');
        });

        it('ignores the pre-selection when editing', () => {
            renderForm({ sale: recentSales[0], initialProductId: 1 });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jeans 5-pocket · PRD-JNS-005');
        });
    });

    describe('unit price', () => {
        it("is pre-filled with the product's selling price when one is picked", async () => {
            const { user } = renderForm();

            await pickProduct(user, 'Denim Jacket · PRD-JKT-001');

            expect(field.price()).toHaveValue('89.0000');

            await pickProduct(user, 'Classic White T-shirt · PRD-TSH-002');

            expect(field.price()).toHaveValue('24.0000');
        });

        it('stays editable after the pre-fill and is what gets sent', async () => {
            const { user } = renderForm();

            await pickProduct(user, 'Denim Jacket · PRD-JKT-001');
            await user.clear(field.price());
            await user.type(field.price(), '85.50');
            await user.type(field.quantity(), '3');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions[0].data).toMatchObject({
                product_id: 1,
                unit_price: '85.50',
            });
        });

        it('keeps what was typed for a product without a selling price', async () => {
            const withoutPrice: SaleProductOption[] = saleProducts.map(
                (product) =>
                    product.id === productRefs.sneaker.id
                        ? { ...product, selling_price: null }
                        : product,
            );
            const { user } = renderForm({ products: withoutPrice });

            await user.type(field.price(), '12');
            await pickProduct(user, 'Canvas Low Sneaker · PRD-SNK-003');

            expect(field.price()).toHaveValue('12');
        });
    });

    describe('validation errors', () => {
        it('shows each server error under its field, linked and flagged', () => {
            setFormState({
                errors: {
                    product_id: 'The selected product is not active.',
                    quantity: 'The quantity must be at least 1.',
                    unit_price: 'The unit price must be at least 0.',
                    sold_on: 'The sold on must be a date before tomorrow.',
                    note: 'The note must not be greater than 255 characters.',
                },
            });

            renderForm();

            const product = screen.getByRole('combobox', { name: 'Product' });

            expect(product).toBeInvalid();
            expect(product).toHaveAccessibleDescription(
                'Only active products are listed. The selected product is not active.',
            );
            expect(field.quantity()).toBeInvalid();
            expect(field.quantity()).toHaveAccessibleDescription(
                'The quantity must be at least 1.',
            );
            expect(field.price()).toBeInvalid();
            expect(field.price()).toHaveAccessibleDescription(
                '€ The unit price must be at least 0.',
            );
            expect(field.date()).toBeInvalid();
            expect(field.date()).toHaveAccessibleDescription(
                'The sold on must be a date before tomorrow.',
            );
            expect(field.note()).toBeInvalid();
            expect(field.note()).toHaveAccessibleDescription(
                'The note must not be greater than 255 characters.',
            );
        });

        it('leaves valid fields unflagged', () => {
            setFormState({ errors: { quantity: 'The quantity is required.' } });

            renderForm();

            expect(field.quantity()).toBeInvalid();
            expect(field.price()).toBeValid();
            expect(field.date()).toBeValid();
            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toBeValid();
        });

        it('moves focus to the first invalid field when a save fails', () => {
            renderForm();

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveFocus();

            setFormState({
                errors: {
                    quantity: 'The quantity is required.',
                    unit_price: 'The unit price is required.',
                },
            });

            expect(field.quantity()).toHaveFocus();
        });
    });

    describe('saving', () => {
        it('posts the new sale to the sales route', async () => {
            const { user } = renderForm();

            await pickProduct(user, 'Denim Jacket · PRD-JKT-001');
            await user.type(field.quantity(), '6');
            await user.type(field.note(), 'Boutique Lyon');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/sales',
                method: 'post',
                data: {
                    product_id: 1,
                    quantity: '6',
                    unit_price: '89.0000',
                    sold_on: '2026-10-08',
                    note: 'Boutique Lyon',
                },
                options: { preserveScroll: true },
            });
        });

        it('invalidates the cached pages a sale touches when it records one', async () => {
            const { user } = renderForm();

            await pickProduct(user, 'Denim Jacket · PRD-JKT-001');
            await user.type(field.quantity(), '6');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(invalidatedBy('sale').length).toBeGreaterThan(0);
            expect(inertiaMock.submissions[0]).toMatchObject({
                method: 'post',
                options: { invalidateCacheTags: invalidatedBy('sale') },
            });
        });

        it('sends a changed date', async () => {
            const { user } = renderForm({ initialProductId: 1 });

            await user.type(field.quantity(), '2');
            await user.clear(field.date());
            await user.type(field.date(), '2026-10-01');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions[0].data).toMatchObject({
                sold_on: '2026-10-01',
            });
        });

        it('sends nothing the server computes (no total)', async () => {
            const { user } = renderForm({ initialProductId: 1 });

            await user.type(field.quantity(), '6');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(Object.keys(inertiaMock.submissions[0].data).sort()).toEqual(
                ['note', 'product_id', 'quantity', 'sold_on', 'unit_price'],
            );
        });

        it('closes the form once the save succeeded', async () => {
            const { user, onClose } = renderForm({ initialProductId: 1 });

            await user.type(field.quantity(), '6');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(onClose).not.toHaveBeenCalled();

            const options = inertiaMock.submissions[0].options as {
                onSuccess: () => void;
            };

            options.onSuccess();

            expect(onClose).toHaveBeenCalledOnce();
        });

        it('disables Save sale while the request runs', () => {
            setFormState({ processing: true });

            renderForm();

            expect(
                screen.getByRole('button', { name: /Save sale/ }),
            ).toBeDisabled();
            expect(
                within(
                    screen.getByRole('button', { name: /Save sale/ }),
                ).getByRole('status'),
            ).toBeInTheDocument();
        });

        it('closes without saving on Cancel', async () => {
            const { user, onClose } = renderForm();

            await user.click(screen.getByRole('button', { name: 'Cancel' }));

            expect(onClose).toHaveBeenCalledOnce();
            expect(inertiaMock.submissions).toHaveLength(0);
        });
    });

    describe('edit a sale', () => {
        it('is pre-filled from the sale', () => {
            renderForm({ sale: recentSales[3] });

            expect(
                screen.getByRole('combobox', { name: 'Product' }),
            ).toHaveTextContent('Denim Jacket · PRD-JKT-001');
            expect(field.quantity()).toHaveValue('4');
            expect(field.price()).toHaveValue('89.0000');
            expect(field.date()).toHaveValue('2026-10-05');
            expect(field.note()).toHaveValue('Web shop');
        });

        it('shows an empty note for a sale without one', () => {
            renderForm({ sale: { ...recentSales[3], note: null } });

            expect(field.note()).toHaveValue('');
        });

        it("puts the change to the sale's own route", async () => {
            const { user } = renderForm({ sale: recentSales[3] });

            await user.clear(field.quantity());
            await user.type(field.quantity(), '5');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions).toHaveLength(1);
            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/sales/57',
                method: 'put',
                data: {
                    product_id: 1,
                    quantity: '5',
                    unit_price: '89.0000',
                    sold_on: '2026-10-05',
                    note: 'Web shop',
                },
                options: { preserveScroll: true },
            });
        });

        it('invalidates the cached pages a sale touches when it edits one', async () => {
            const { user } = renderForm({ sale: recentSales[3] });

            await user.clear(field.quantity());
            await user.type(field.quantity(), '5');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions[0]).toMatchObject({
                method: 'put',
                options: { invalidateCacheTags: invalidatedBy('sale') },
            });
        });

        it('keeps the original price when only the quantity changes', async () => {
            const { user } = renderForm({ sale: saleOfInactiveProduct });

            await user.clear(field.quantity());
            await user.type(field.quantity(), '4');
            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            expect(inertiaMock.submissions[0]).toMatchObject({
                action: '/sales/14',
                method: 'put',
                data: { product_id: 6, quantity: '4', unit_price: '95.0000' },
            });
        });

        it('closes the form once the save succeeded', async () => {
            const { user, onClose } = renderForm({ sale: recentSales[3] });

            await user.click(screen.getByRole('button', { name: 'Save sale' }));

            const options = inertiaMock.submissions[0].options as {
                onSuccess: () => void;
            };

            options.onSuccess();

            expect(onClose).toHaveBeenCalledOnce();
        });
    });
});
