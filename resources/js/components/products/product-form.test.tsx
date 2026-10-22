import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ProductForm } from '@/components/products/product-form';
import {
    answerMaterialOptions,
    categorySizesFixture,
    choreJacket,
    denimJacket,
    productCategories,
    productStatuses,
} from '@/test/fixtures/products';
import {
    inertiaMock,
    resetInertiaMock,
    respondToHttp,
    setFormState,
    setPageProps,
} from '@/test/inertia';
import type { Product } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

type SetupOptions = {
    product?: Product;
    method?: 'post' | 'put';
    url?: string;
};

function setup({
    product,
    method = product ? 'put' : 'post',
    url = product ? `/products/${product.id}` : '/products',
}: SetupOptions = {}) {
    const user = userEvent.setup();
    const view = render(
        <ProductForm
            product={product}
            title={product ? `Edit ${product.name}` : 'Add product'}
            description="Details, photo and the materials that make one unit."
            categories={productCategories}
            statuses={productStatuses}
            categorySizes={categorySizesFixture}
            action={{ url, method }}
            cancelHref={product ? `/products/${product.id}` : '/products'}
            submitLabel="Save product"
        />,
    );

    return { user, ...view };
}

/** The n-th row (1-based) of the composition. */
function rowGroup(n: number): HTMLElement {
    return screen.getByRole('group', { name: new RegExp(`^Row ${n}:`) });
}

/** The single submit the mocked `useForm` recorded. */
function submission() {
    expect(inertiaMock.submissions).toHaveLength(1);

    return inertiaMock.submissions[0];
}

/** The `components` rows of the recorded submission. */
function submittedRows(): unknown[] {
    const rows = submission().data.components;

    if (!Array.isArray(rows)) {
        throw new Error('Expected the submission to carry the composition.');
    }

    return rows;
}

async function pickMaterial(
    user: ReturnType<typeof userEvent.setup>,
    name: RegExp,
) {
    await user.click(screen.getByRole('button', { name: 'Add material' }));
    await user.click(await screen.findByRole('option', { name }));
}

const photo = new File(['png'], 'jacket.png', { type: 'image/png' });

describe('<ProductForm>', () => {
    beforeEach(() => {
        resetInertiaMock();
        respondToHttp(({ params }) => answerMaterialOptions(params));
    });

    describe('a new product', () => {
        it('shows the page title, its sections and the helper copy', () => {
            setup();

            expect(
                screen.getByRole('heading', { level: 1, name: 'Add product' }),
            ).toBeInTheDocument();
            expect(
                screen.getAllByText(
                    'Details, photo and the materials that make one unit.',
                ),
            ).not.toHaveLength(0);

            for (const name of [
                'Details',
                'Costs and price',
                /^Photo\s*\(optional\)$/,
                'Composition',
            ]) {
                expect(
                    screen.getByRole('region', { name }),
                ).toBeInTheDocument();
            }

            expect(
                screen.getByText('Only active products can be sold.'),
            ).toBeInTheDocument();
            expect(
                screen.getByText(
                    'Pre-fills Record a sale. The margin is calculated when you save.',
                ),
            ).toBeInTheDocument();
        });

        it('starts blank, as a draft, with no materials', () => {
            setup();

            expect(screen.getByLabelText(/^SKU/)).toHaveValue('');
            expect(screen.getByLabelText(/^Name/)).toHaveValue('');
            expect(screen.getByLabelText(/^Category/)).toHaveTextContent(
                'Select a category',
            );
            expect(screen.getByLabelText(/^Status/)).toHaveTextContent('Draft');
            expect(screen.getByLabelText(/^Description/)).toHaveValue('');
            expect(screen.getByLabelText(/^Labor cost/)).toHaveValue('');
            expect(screen.getByLabelText(/^Overhead cost/)).toHaveValue('');
            expect(screen.getByLabelText(/^Selling price/)).toHaveValue('');
            expect(screen.getByText('0 materials')).toBeInTheDocument();
            expect(
                screen.queryByRole('group', { name: /^Row/ }),
            ).not.toBeInTheDocument();
        });

        it('marks the required fields and labels the optional ones', () => {
            setup();

            for (const field of [
                /^SKU/,
                /^Name/,
                /^Category/,
                /^Status/,
                /^Labor cost/,
            ]) {
                expect(screen.getByLabelText(field)).toHaveAttribute(
                    'aria-required',
                    'true',
                );
            }

            const overhead = screen.getByLabelText('Overhead cost (optional)');

            expect(overhead).not.toHaveAttribute('aria-required');
            // The description also carries the currency addon ("€").
            expect(overhead).toHaveAccessibleDescription(
                expect.stringContaining(
                    "Leave empty if you don't track overhead. It counts as 0.",
                ),
            );

            expect(
                screen.getByLabelText('Description (optional)'),
            ).toBeInTheDocument();
            expect(
                screen.getByLabelText('Selling price (optional)'),
            ).not.toHaveAttribute('aria-required');
        });

        it('uses a mono SKU field and text inputs with a decimal keypad for money', () => {
            setup();

            expect(screen.getByLabelText(/^SKU/)).toHaveClass('font-mono');

            for (const field of [
                /^Labor cost/,
                /^Overhead cost/,
                /^Selling price/,
            ]) {
                const input = screen.getByLabelText(field);

                expect(input).toHaveAttribute('type', 'text');
                expect(input).toHaveAttribute('inputmode', 'decimal');
            }
        });

        it('links Cancel back to the list', () => {
            setup();

            const cancel = screen.getAllByRole('link', { name: 'Cancel' });

            expect(cancel).toHaveLength(2);

            for (const link of cancel) {
                expect(link).toHaveAttribute('href', '/products');
            }
        });
    });

    describe('currency', () => {
        it("prefixes the money fields with the installation currency's own symbol", () => {
            setup();

            for (const field of [
                /^Labor cost/,
                /^Overhead cost/,
                /^Selling price/,
            ]) {
                expect(
                    screen.getByLabelText(field),
                ).toHaveAccessibleDescription(expect.stringContaining('€'));
            }
        });

        it('follows the currency from the shared props, never a hard-coded euro', () => {
            setPageProps({
                app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
            });
            setup();

            const labor = screen.getByLabelText(/^Labor cost/);

            expect(labor).toHaveAccessibleDescription('TND');
            expect(labor.closest('form')).not.toHaveTextContent('€');
        });
    });

    describe('validation errors', () => {
        const errors = {
            sku: 'The sku has already been taken.',
            name: 'The name field is required.',
            category: 'The category field is required.',
            status: 'The selected status is invalid.',
            description: 'The description is too long.',
            labor_cost: 'The labor cost must be at least 0.',
            overhead_cost: 'The overhead cost must be a number.',
            selling_price: 'The selling price must be at least 0.',
            photo: 'The photo must be a file of type: jpg, png, webp.',
        };

        it('shows each error under its field and links it with aria-describedby', () => {
            setFormState({ errors });
            setup();

            const fields = {
                sku: /^SKU/,
                name: /^Name/,
                category: /^Category/,
                status: /^Status/,
                description: /^Description/,
                labor_cost: /^Labor cost/,
                overhead_cost: /^Overhead cost/,
                selling_price: /^Selling price/,
            };

            for (const [key, label] of Object.entries(fields)) {
                const message = errors[key as keyof typeof errors];
                const field = screen.getByLabelText(label);

                expect(screen.getByText(message)).toBeInTheDocument();
                expect(field).toHaveAttribute('aria-invalid', 'true');
                expect(field).toHaveAccessibleDescription(
                    expect.stringContaining(message),
                );
            }

            expect(screen.getByText(errors.photo)).toBeInTheDocument();
            expect(screen.getByLabelText('Photo')).toHaveAttribute(
                'aria-invalid',
                'true',
            );
        });

        it('leaves valid fields unmarked', () => {
            setFormState({ errors: { name: errors.name } });
            setup();

            expect(screen.getByLabelText(/^SKU/)).not.toHaveAttribute(
                'aria-invalid',
            );
            expect(screen.getByLabelText(/^Name/)).toHaveAttribute(
                'aria-invalid',
                'true',
            );
        });

        it('shows the category-lock error under "Category", with the sizes line still there', () => {
            const message =
                "Denim Jacket is on orders, so its category can't change to one with other sizes. Keep a category with sizes S, M, L, XL.";

            setFormState({ errors: { category: message } });
            setup({ product: denimJacket });

            const category = screen.getByLabelText(/^Category/);

            expect(screen.getByText(message)).toBeInTheDocument();
            expect(category).toHaveAttribute('aria-invalid', 'true');
            expect(category).toHaveAccessibleDescription(
                expect.stringContaining(message),
            );
            expect(screen.getByText('Sizes: S, M, L, XL')).toBeInTheDocument();
        });

        it('moves focus to the first invalid field', () => {
            setFormState({ errors: { name: errors.name } });
            setup();

            expect(screen.getByLabelText(/^Name/)).toHaveFocus();
        });

        it('shows an error about the whole composition as an alert', () => {
            setFormState({
                errors: { components: 'The components field is required.' },
            });
            setup();

            expect(screen.getByRole('alert')).toHaveTextContent(
                'The components field is required.',
            );
        });

        it('puts a nested composition error on the row it belongs to', () => {
            setFormState({
                errors: {
                    'components.1.quantity': 'The quantity must be above 0.',
                },
            });
            setup({ product: denimJacket });

            expect(
                within(rowGroup(2)).getByText('The quantity must be above 0.'),
            ).toBeInTheDocument();
            expect(
                within(rowGroup(1)).queryByText(
                    'The quantity must be above 0.',
                ),
            ).not.toBeInTheDocument();
            expect(
                within(rowGroup(2)).getByLabelText(
                    'Quantity per unit for Metal shank button 17mm',
                ),
            ).toHaveAttribute('aria-invalid', 'true');
        });
    });

    describe('submitting a new product', () => {
        it('posts the typed values, the selling price and the composition without row keys', async () => {
            const { user } = setup();

            await user.type(screen.getByLabelText(/^SKU/), 'PRD-JKT-010');
            await user.type(screen.getByLabelText(/^Name/), 'Work Jacket');

            await user.click(screen.getByLabelText(/^Category/));
            await user.click(screen.getByRole('option', { name: 'Apparel' }));
            await user.click(screen.getByLabelText(/^Status/));
            await user.click(screen.getByRole('option', { name: 'Active' }));

            await user.type(
                screen.getByLabelText(/^Description/),
                'Heavy twill jacket.',
            );
            await user.type(screen.getByLabelText(/^Labor cost/), '12.5');
            await user.type(screen.getByLabelText(/^Overhead cost/), '3');
            await user.type(screen.getByLabelText(/^Selling price/), '89.90');

            await pickMaterial(user, /Denim 12oz Indigo/);

            const row = within(rowGroup(1));

            await user.type(
                row.getByLabelText('Quantity per unit for Denim 12oz Indigo'),
                '1.8',
            );
            await user.clear(
                row.getByLabelText('Waste percent for Denim 12oz Indigo'),
            );
            await user.type(
                row.getByLabelText('Waste percent for Denim 12oz Indigo'),
                '10',
            );
            await user.type(
                row.getByLabelText('Placement notes for Denim 12oz Indigo'),
                'Body',
            );

            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission()).toMatchObject({
                action: '/products',
                method: 'post',
                data: {
                    sku: 'PRD-JKT-010',
                    name: 'Work Jacket',
                    category: 'apparel',
                    status: 'active',
                    description: 'Heavy twill jacket.',
                    labor_cost: '12.5',
                    overhead_cost: '3',
                    selling_price: '89.90',
                    photo: null,
                    remove_photo: false,
                },
            });
            // The client-only row key never reaches the server.
            expect(submittedRows()).toEqual([
                {
                    material_id: 4,
                    quantity: '1.8',
                    waste_percent: '10',
                    notes: 'Body',
                },
            ]);
            expect(submission().data).not.toHaveProperty('_method');
        });

        it('sends an empty selling price and an empty overhead as empty strings', async () => {
            const { user } = setup();

            await user.type(screen.getByLabelText(/^SKU/), 'PRD-JKT-011');
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[1],
            );

            expect(submission().data).toMatchObject({
                selling_price: '',
                overhead_cost: '',
            });
        });

        it('submits from the header button too', async () => {
            const { user } = setup();

            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission()).toMatchObject({
                action: '/products',
                method: 'post',
            });
        });

        it('sends a picked photo with the post', async () => {
            const { user } = setup();

            await user.upload(screen.getByLabelText('Photo'), photo);
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission()).toMatchObject({
                action: '/products',
                method: 'post',
                data: { photo, remove_photo: false },
            });
        });

        it('disables the save buttons while the request runs', () => {
            setFormState({ processing: true });
            setup();

            // The spinner inside the button adds "Loading" to its name.
            const buttons = screen.getAllByRole('button', {
                name: /Save product$/,
            });

            expect(buttons).toHaveLength(2);

            for (const button of buttons) {
                expect(button).toBeDisabled();
                expect(
                    within(button).getByRole('status', { name: 'Loading' }),
                ).toBeInTheDocument();
            }
        });
    });

    describe('editing a product', () => {
        it("fills every field from the product, with decimals as the server's text trimmed", () => {
            setup({ product: denimJacket });

            expect(
                screen.getByRole('heading', {
                    level: 1,
                    name: 'Edit Denim Jacket',
                }),
            ).toBeInTheDocument();
            expect(screen.getByLabelText(/^SKU/)).toHaveValue('PRD-JKT-001');
            expect(screen.getByLabelText(/^Name/)).toHaveValue('Denim Jacket');
            expect(screen.getByLabelText(/^Category/)).toHaveTextContent(
                'Apparel',
            );
            expect(screen.getByLabelText(/^Status/)).toHaveTextContent(
                'Active',
            );
            expect(screen.getByLabelText(/^Description/)).toHaveValue(
                'Classic trucker jacket in 12oz indigo denim with metal shank buttons.',
            );
            expect(screen.getByLabelText(/^Labor cost/)).toHaveValue('12');
            expect(screen.getByLabelText(/^Overhead cost/)).toHaveValue('3.5');
            expect(screen.getByLabelText(/^Selling price/)).toHaveValue('89');
        });

        it('fills the composition rows in order, with quantity, waste and notes', () => {
            setup({ product: denimJacket });

            expect(screen.getByText('4 materials')).toBeInTheDocument();

            const expected = [
                ['Denim 12oz Indigo', '1.8', '10', 'Body, sleeves, collar'],
                [
                    'Metal shank button 17mm',
                    '8',
                    '0',
                    'Front placket (6), cuffs (2)',
                ],
                ['Polyester thread', '120', '5', 'All seams'],
                ['Woven brand label', '1', '0', 'Inside back yoke'],
            ];

            expected.forEach(([name, quantity, waste, notes], index) => {
                const row = within(rowGroup(index + 1));

                expect(
                    row.getByRole('combobox', { name: new RegExp(name) }),
                ).toBeInTheDocument();
                expect(
                    row.getByLabelText(`Quantity per unit for ${name}`),
                ).toHaveValue(quantity);
                expect(
                    row.getByLabelText(`Waste percent for ${name}`),
                ).toHaveValue(waste);
                expect(
                    row.getByLabelText(`Placement notes for ${name}`),
                ).toHaveValue(notes);
            });
        });

        it("shows the unit of each material after its quantity (the material's own unit)", () => {
            setup({ product: denimJacket });

            expect(
                within(rowGroup(1)).getByLabelText(/^Quantity per unit for/)
                    .parentElement,
            ).toHaveTextContent('m');
            expect(
                within(rowGroup(2)).getByLabelText(/^Quantity per unit for/)
                    .parentElement,
            ).toHaveTextContent('pc');
        });

        it('shows 0 in Overhead cost for a product whose overhead is 0', () => {
            setup({ product: { ...denimJacket, overhead_cost: '0.0000' } });

            expect(
                screen.getByLabelText('Overhead cost (optional)'),
            ).toHaveValue('0');
        });

        it('leaves the selling price empty for a product without one', () => {
            setup({ product: choreJacket });

            expect(screen.getByLabelText(/^Selling price/)).toHaveValue('');
            expect(screen.getByLabelText(/^Status/)).toHaveTextContent('Draft');
        });

        it('shows the current photo with Replace and Remove', () => {
            setup({ product: denimJacket });

            expect(
                screen.getByRole('img', { name: 'Current photo' }),
            ).toBeInTheDocument();
            expect(screen.getByText('Replace photo')).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Remove photo' }),
            ).toBeInTheDocument();
        });

        it('offers materials already used on other rows as unavailable', async () => {
            const { user } = setup({ product: denimJacket });

            await user.click(
                screen.getByRole('button', { name: 'Add material' }),
            );

            expect(
                await screen.findByRole('option', { name: /Polyester thread/ }),
            ).toHaveAttribute('aria-disabled', 'true');
            expect(
                screen.getByRole('option', { name: /Brass rivet 9mm/ }),
            ).not.toHaveAttribute('aria-disabled', 'true');
        });

        it('sends PUT to the product URL when no file is attached', async () => {
            const { user } = setup({ product: denimJacket });

            await user.clear(screen.getByLabelText(/^Name/));
            await user.type(screen.getByLabelText(/^Name/), 'Denim Jacket II');
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission()).toMatchObject({
                action: '/products/1',
                method: 'put',
                data: {
                    sku: 'PRD-JKT-001',
                    name: 'Denim Jacket II',
                    category: 'apparel',
                    status: 'active',
                    labor_cost: '12',
                    overhead_cost: '3.5',
                    selling_price: '89',
                    photo: null,
                    remove_photo: false,
                },
            });
            expect(submission().data).not.toHaveProperty('_method');
            expect(submittedRows()).toEqual([
                {
                    material_id: 4,
                    quantity: '1.8',
                    waste_percent: '10',
                    notes: 'Body, sleeves, collar',
                },
                {
                    material_id: 5,
                    quantity: '8',
                    waste_percent: '0',
                    notes: 'Front placket (6), cuffs (2)',
                },
                {
                    material_id: 8,
                    quantity: '120',
                    waste_percent: '5',
                    notes: 'All seams',
                },
                {
                    material_id: 12,
                    quantity: '1',
                    waste_percent: '0',
                    notes: 'Inside back yoke',
                },
            ]);
        });

        it('sends POST with _method=put and multipart when a file is attached', async () => {
            const { user } = setup({ product: denimJacket });

            await user.upload(screen.getByLabelText('Photo'), photo);
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission()).toMatchObject({
                action: '/products/1',
                method: 'post',
                data: { _method: 'put', photo, remove_photo: false },
                options: { forceFormData: true },
            });
        });

        it('sends remove_photo when the current photo is removed', async () => {
            const { user } = setup({ product: denimJacket });

            await user.click(
                screen.getByRole('button', { name: 'Remove photo' }),
            );
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission()).toMatchObject({
                action: '/products/1',
                method: 'put',
                data: { photo: null, remove_photo: true },
            });
        });

        it('links Cancel back to the product', () => {
            setup({ product: denimJacket });

            for (const link of screen.getAllByRole('link', {
                name: 'Cancel',
            })) {
                expect(link).toHaveAttribute('href', '/products/1');
            }
        });

        it('keeps the order after a row is moved', async () => {
            const { user } = setup({ product: denimJacket });

            await user.click(
                screen.getByRole('button', {
                    name: 'Move Polyester thread up',
                }),
            );
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(
                submittedRows().map(
                    (row) => (row as { material_id: number }).material_id,
                ),
            ).toEqual([4, 8, 5, 12]);
        });
    });
});
