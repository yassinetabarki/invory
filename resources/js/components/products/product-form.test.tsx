import { render, screen, within } from '@testing-library/react';
import type { ComponentProps } from 'react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { ProductForm } from '@/components/products/product-form';
import { colourNamed, defaultColours } from '@/test/fixtures/colours';
import {
    answerMaterialOptions,
    choreJacket,
    denimBlackByVariantLine,
    denimJacket,
    fullCategorySizesFixture,
    fullSizeOptionsFixture,
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
import type { Product, SizeOption } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

type SetupOptions = {
    locks?: Pick<
        ComponentProps<typeof ProductForm>,
        'sizeLocks' | 'oneSizeLock' | 'otherSizesLock'
    >;
    product?: Product;
    method?: 'post' | 'put';
    url?: string;
    /** The workshop's size list, when a test names its sizes otherwise. */
    sizes?: SizeOption[];
};

function setup({
    product,
    method = product ? 'put' : 'post',
    url = product ? `/products/${product.id}` : '/products',
    locks,
    sizes = fullSizeOptionsFixture,
}: SetupOptions = {}) {
    const user = userEvent.setup();
    const view = render(
        <ProductForm
            product={product}
            title={product ? `Edit ${product.name}` : 'Add product'}
            description="Details, photo and the materials that make one unit."
            categories={productCategories}
            statuses={productStatuses}
            colours={defaultColours}
            sizes={sizes}
            categorySizes={fullCategorySizesFixture}
            {...locks}
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

        it('shows a category error under "Category"', () => {
            const message = 'The selected category is invalid.';

            setFormState({ errors: { category: message } });
            setup({ product: denimJacket });

            const category = screen.getByLabelText(/^Category/);

            expect(screen.getByText(message)).toBeInTheDocument();
            expect(category).toHaveAttribute('aria-invalid', 'true');
            expect(category).toHaveAccessibleDescription(
                expect.stringContaining(message),
            );
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
                    quantity_mode: 'same',
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
                    quantity_mode: 'same',
                    quantity: '1.8',
                    waste_percent: '10',
                    notes: 'Body, sleeves, collar',
                },
                {
                    material_id: 5,
                    quantity_mode: 'same',
                    quantity: '8',
                    waste_percent: '0',
                    notes: 'Front placket (6), cuffs (2)',
                },
                {
                    material_id: 8,
                    quantity_mode: 'same',
                    quantity: '120',
                    waste_percent: '5',
                    notes: 'All seams',
                },
                {
                    material_id: 12,
                    quantity_mode: 'same',
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

    describe('the Sizes field', () => {
        const sizesGroup = () => screen.getByRole('group', { name: /^Sizes/ });
        const size = (name: string) =>
            within(sizesGroup()).getByRole('button', { name });

        async function chooseCategory(
            user: ReturnType<typeof userEvent.setup>,
            name: string,
        ) {
            await user.click(screen.getByLabelText(/^Category/));
            await user.click(screen.getByRole('option', { name }));
        }

        it('starts with nothing chosen on Add, and a category pre-selects its sizes', async () => {
            const { user } = setup();

            expect(
                screen.getByText(
                    'Choosing a category pre-selects its sizes. Change them as you like.',
                ),
            ).toBeInTheDocument();
            expect(screen.getByText('None chosen yet')).toBeInTheDocument();
            expect(size('XS')).toHaveAttribute('aria-pressed', 'false');

            await chooseCategory(user, 'Apparel');

            expect(screen.getByText('7 sizes chosen')).toBeInTheDocument();
            expect(size('XXXL')).toHaveAttribute('aria-pressed', 'true');
            expect(size('36')).toHaveAttribute('aria-pressed', 'false');
        });

        it("keeps the sizes she changed on another category, until she asks for that category's sizes", async () => {
            const { user } = setup();

            await chooseCategory(user, 'Apparel');
            await user.click(size('XS'));
            await user.click(size('XXL'));
            await user.click(size('XXXL'));

            expect(screen.getByText('4 sizes chosen')).toBeInTheDocument();

            await chooseCategory(user, 'Footwear');

            expect(screen.getByText('4 sizes chosen')).toBeInTheDocument();
            expect(
                screen.getByText(/Sizes kept: you changed them./),
            ).toBeInTheDocument();

            await user.click(
                screen.getByRole('button', {
                    name: 'Use the sizes of Footwear',
                }),
            );

            expect(screen.getByText('11 sizes chosen')).toBeInTheDocument();
            expect(size('36')).toHaveAttribute('aria-pressed', 'true');
            expect(size('S')).toHaveAttribute('aria-pressed', 'false');
            expect(screen.queryByText(/Sizes kept/)).toBeNull();
        });

        it('submits the chosen size ids', async () => {
            const { user } = setup();

            await chooseCategory(user, 'Apparel');
            await user.click(size('XS'));
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submission().data.sizes).toEqual([1, 2, 3, 4, 22, 23]);
        });

        it('shows both kinds of refusal under the group, in list order, with the focus on the first button', () => {
            setFormState({
                errors: {
                    sizes: 'Choose at least one size.',
                    'sizes.removed.4':
                        "XL can't be taken off Denim Jacket: it has units in stock.",
                    'sizes.removed.2':
                        "M can't be taken off Denim Jacket: it is on orders.",
                },
            });
            setup({ product: denimJacket });

            for (const sentence of [
                'Choose at least one size.',
                "M can't be taken off Denim Jacket: it is on orders.",
                "XL can't be taken off Denim Jacket: it has units in stock.",
            ]) {
                expect(screen.getByText(sentence)).toBeVisible();
            }

            expect(
                screen
                    .getAllByText(/can't be taken off Denim Jacket/)
                    .map((line) => line.textContent?.[0]),
            ).toEqual(['M', 'X']);
            expect(size('XS')).toHaveFocus();
            expect(sizesGroup()).toHaveAccessibleDescription(
                expect.stringContaining('Choose at least one size.'),
            );
        });

        it("on Edit presses the product's sizes and never changes them with the category", async () => {
            window.history.replaceState(null, '', '/products/1/edit#sizes');
            const { user } = setup({
                product: { ...denimJacket, sizes: [{ id: 1, label: 'S' }] },
            });

            expect(
                screen.getByText(
                    'A new size adds a variant for every colour, with no stock and no barcode yet.',
                ),
            ).toBeInTheDocument();
            expect(screen.getByText('1 size chosen')).toBeInTheDocument();
            expect(size('S')).toHaveAttribute('aria-pressed', 'true');
            // #sizes: the focus goes to the group's first button.
            expect(size('XS')).toHaveFocus();

            await chooseCategory(user, 'Footwear');

            expect(screen.getByText('1 size chosen')).toBeInTheDocument();
            expect(screen.queryByText(/Sizes kept/)).toBeNull();
            window.history.replaceState(null, '', '/');
        });
    });
    describe('a line By colour and size (INV-16-f)', () => {
        const black = colourNamed('Black');
        const indigo = colourNamed('Indigo');
        const cells = (colourId: number) =>
            ['1.6000', '1.8000', '2.0000', '2.2000'].map((quantity, index) => ({
                colour_id: colourId,
                size_id: index + 1,
                quantity,
            }));
        const grid = (colourId: number) =>
            Object.fromEntries(
                cells(colourId).map((cell) => [cell.size_id, cell.quantity]),
            );
        // The Denim Jacket in Black and Indigo (list order), its black denim By colour and size.
        const jacketWithGrid: Product = {
            ...denimJacket,
            colours: [black, indigo],
            components: [
                ...denimJacket.components,
                {
                    ...denimBlackByVariantLine,
                    quantities: [...cells(black.id), ...cells(indigo.id)],
                },
            ],
        };

        it('posts the mode of every line and the cells of the grid on Add product', async () => {
            const { user } = setup();

            await user.type(screen.getByLabelText(/^SKU/), 'PRD-JKT-010');
            await user.click(screen.getByLabelText(/^Category/));
            await user.click(screen.getByRole('option', { name: 'Apparel' }));
            await user.click(
                screen.getByRole('button', { name: 'Add colour' }),
            );
            await user.click(
                await screen.findByRole('option', { name: 'Indigo, IND' }),
            );
            await user.keyboard('{Escape}');

            await pickMaterial(user, /Denim 12oz Indigo/);
            await user.type(
                within(rowGroup(1)).getByLabelText(
                    'Quantity per unit for Denim 12oz Indigo',
                ),
                '1.8',
            );
            await pickMaterial(user, /Metal shank button 17mm/);
            await user.type(
                within(rowGroup(2)).getByLabelText(
                    'Quantity per unit for Metal shank button 17mm',
                ),
                '8',
            );
            await user.click(
                within(rowGroup(1)).getByRole('radio', {
                    name: 'By colour and size',
                }),
            );
            // The grid opens at once; Done closes it and keeps the cells.
            await user.click(screen.getByRole('button', { name: 'Done' }));
            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            // Indigo × the seven sizes of Apparel, each holding what was typed.
            const sizeIds = [21, 1, 2, 3, 4, 22, 23];

            expect(submittedRows()).toEqual([
                {
                    material_id: 4,
                    quantity_mode: 'by_variant',
                    quantity: '',
                    waste_percent: '0',
                    notes: '',
                    quantities: {
                        [indigo.id]: Object.fromEntries(
                            sizeIds.map((sizeId) => [sizeId, '1.8']),
                        ),
                    },
                },
                {
                    material_id: 5,
                    quantity_mode: 'same',
                    quantity: '8',
                    waste_percent: '0',
                    notes: '',
                },
            ]);
        });

        it('posts the saved cells of a grid, as sent, on Edit product', async () => {
            const { user } = setup({ product: jacketWithGrid });

            expect(
                within(rowGroup(5)).getByText(
                    '8 of 8 cells · Black and Indigo only',
                ),
            ).toBeInTheDocument();

            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submittedRows()[4]).toEqual({
                material_id: 13,
                quantity_mode: 'by_variant',
                quantity: '',
                waste_percent: '10',
                notes: '',
                quantities: {
                    [black.id]: grid(black.id),
                    [indigo.id]: grid(indigo.id),
                },
            });
            expect(submittedRows()[0]).toMatchObject({ quantity_mode: 'same' });
        });

        it('keeps what was typed in the grid in the form when Done closes it', async () => {
            const { user } = setup({ product: jacketWithGrid });

            await user.click(
                within(rowGroup(5)).getByRole('button', { name: 'Edit grid' }),
            );

            const sheet = within(
                screen.getByRole('dialog', { name: 'Denim 12oz Black' }),
            );

            await user.clear(sheet.getByLabelText('Indigo, size S'));
            await user.type(sheet.getByLabelText('Indigo, size S'), '1.7');
            await user.clear(sheet.getByLabelText('Black, size XL'));
            await user.click(sheet.getByRole('button', { name: 'Done' }));

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
            expect(
                within(rowGroup(5)).getByText(
                    '7 of 8 cells · Black and Indigo only',
                ),
            ).toBeInTheDocument();

            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submittedRows()[4]).toMatchObject({
                quantities: {
                    [indigo.id]: { 1: '1.7', 2: '1.8000' },
                    [black.id]: { 1: '1.6000', 3: '2.0000' },
                },
            });
            expect(
                (submittedRows()[4] as { quantities: Record<number, object> })
                    .quantities[black.id],
            ).not.toHaveProperty('4');
        });

        it('leaves out the cells of a colour taken off the form, with no question', async () => {
            const { user } = setup({ product: jacketWithGrid });

            await user.click(
                screen.getByRole('button', { name: 'Remove Black' }),
            );

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();

            await user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

            expect(submittedRows()[4]).toMatchObject({
                quantities: { [indigo.id]: grid(indigo.id) },
            });
            expect(submittedRows()[4]).not.toMatchObject({
                quantities: { [black.id]: expect.anything() },
            });
        });

        it('shows the message of a refused cell under its line and marks the grid button', () => {
            setFormState({
                errors: {
                    'components.4.quantities.8.2':
                        'Enter a number from 0 to 1,000,000 with up to 4 decimals.',
                },
            });
            setup({ product: jacketWithGrid });

            expect(
                within(rowGroup(5)).getByText(
                    'Enter a number from 0 to 1,000,000 with up to 4 decimals.',
                ),
            ).toBeInTheDocument();
            expect(
                within(rowGroup(5)).getByRole('button', { name: 'Edit grid' }),
            ).toHaveAttribute('aria-invalid', 'true');
        });
    });

    describe('asking before typed quantities are cleared (INV-16-m)', () => {
        const black = colourNamed('Black');
        const indigo = colourNamed('Indigo');
        const white = colourNamed('White');
        const sizesGroup = () => screen.getByRole('group', { name: /^Sizes/ });
        const size = (name: string) =>
            within(sizesGroup()).getByRole('button', { name });
        const pressed = (name: string) =>
            expect(size(name)).toHaveAttribute('aria-pressed', 'true');
        const notPressed = (name: string) =>
            expect(size(name)).toHaveAttribute('aria-pressed', 'false');
        const question = (name: string) =>
            screen.getByRole('alertdialog', { name });
        const save = (user: ReturnType<typeof userEvent.setup>) =>
            user.click(
                screen.getAllByRole('button', { name: 'Save product' })[0],
            );

        /** The Denim Jacket with a Black denim line By colour and size holding these cells ([colour, size id]). */
        function jacketWithCells(
            cells: [colour: { id: number }, sizeId: number][],
            overrides: Partial<Product> = {},
        ): Product {
            return {
                ...denimJacket,
                colours: [black, indigo],
                ...overrides,
                components: [
                    ...denimJacket.components,
                    {
                        ...denimBlackByVariantLine,
                        quantities: cells.map(([colour, sizeId]) => ({
                            colour_id: colour.id,
                            size_id: sizeId,
                            quantity: '2.2000',
                        })),
                    },
                ],
            };
        }

        /** Black and Indigo × S, M, L, XL: 8 cells. */
        const eightCells = jacketWithCells(
            [black, indigo].flatMap((colour) =>
                [1, 2, 3, 4].map((sizeId): [{ id: number }, number] => [
                    colour,
                    sizeId,
                ]),
            ),
        );

        /** The cells the submitted line holds: colour id → the size ids with a quantity. */
        function submittedCells(row: number): Record<string, string[]> {
            const { quantities } = submittedRows()[row] as {
                quantities: Record<string, Record<string, string>>;
            };

            return Object.fromEntries(
                Object.entries(quantities).map(([colourId, cells]) => [
                    colourId,
                    Object.keys(cells),
                ]),
            );
        }

        async function chooseCategory(
            user: ReturnType<typeof userEvent.setup>,
            name: string,
        ) {
            await user.click(screen.getByLabelText(/^Category/));
            await user.click(screen.getByRole('option', { name }));
        }

        /** Add product: Apparel, the given colours, and a denim line By colour and size holding 1.8 in every cell. */
        async function addGridLine(
            user: ReturnType<typeof userEvent.setup>,
            colourOptions: string[],
        ) {
            await chooseCategory(user, 'Apparel');

            for (const option of colourOptions) {
                await user.click(
                    screen.getByRole('button', { name: 'Add colour' }),
                );
                await user.click(
                    await screen.findByRole('option', { name: option }),
                );
                await user.keyboard('{Escape}');
            }

            await pickMaterial(user, /Denim 12oz Indigo/);
            await user.type(
                within(rowGroup(1)).getByLabelText(
                    'Quantity per unit for Denim 12oz Indigo',
                ),
                '1.8',
            );
            await user.click(
                within(rowGroup(1)).getByRole('radio', {
                    name: 'By colour and size',
                }),
            );
            await user.click(screen.getByRole('button', { name: 'Done' }));
        }

        /** Add product with S, M, L, XL and two colours: 8 typed cells (XS, XXL and XXXL come off before any is typed). */
        async function addEightTypedCells(
            user: ReturnType<typeof userEvent.setup>,
        ) {
            await chooseCategory(user, 'Apparel');
            await user.click(size('XS'));
            await user.click(size('XXL'));
            await user.click(size('XXXL'));
            await addGridLine(user, ['Indigo, IND', 'Black, BLK']);
        }

        it('asks before a size with typed quantities comes off, and Keep leaves the sizes and the cells', async () => {
            const { user } = setup({ product: eightCells });

            await user.click(size('XL'));

            const dialog = within(question('Take XL off this product?'));

            expect(
                dialog.getByText(
                    'This clears the 2 quantities you set for XL across its materials.',
                ),
            ).toBeInTheDocument();
            expect(
                dialog.getByRole('button', { name: 'Keep XL' }),
            ).toHaveFocus();
            expect(
                dialog.getByRole('button', { name: 'Take XL off' }),
            ).toHaveClass('bg-destructive');

            await user.click(dialog.getByRole('button', { name: 'Keep XL' }));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            pressed('XL');

            await save(user);

            expect(submission().data.sizes).toEqual([1, 2, 3, 4]);
            expect(submittedCells(4)).toEqual({
                [black.id]: ['1', '2', '3', '4'],
                [indigo.id]: ['1', '2', '3', '4'],
            });
        });

        it('takes the size off and clears its cells on Take XL off', async () => {
            const { user } = setup({ product: eightCells });

            await user.click(size('XL'));
            await user.click(
                screen.getByRole('button', { name: 'Take XL off' }),
            );

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            notPressed('XL');

            await save(user);

            expect(submission().data.sizes).toEqual([1, 2, 3]);
            expect(submittedCells(4)).toEqual({
                [black.id]: ['1', '2', '3'],
                [indigo.id]: ['1', '2', '3'],
            });
        });

        it('says "the 1 quantity" for one cell, and "size 36" for a size named with digits', async () => {
            const { user } = setup({
                product: jacketWithCells([[black, 5]], {
                    colours: [black],
                    sizes: [
                        { id: 1, label: 'S' },
                        { id: 5, label: '36' },
                    ],
                }),
            });

            await user.click(size('36'));

            const dialog = within(question('Take size 36 off this product?'));

            expect(
                dialog.getByText(
                    'This clears the 1 quantity you set for size 36 across its materials.',
                ),
            ).toBeInTheDocument();
            expect(
                dialog.getByRole('button', { name: 'Keep size 36' }),
            ).toBeInTheDocument();
            expect(
                dialog.getByRole('button', { name: 'Take size 36 off' }),
            ).toBeInTheDocument();
        });

        it('takes a size with no typed cell off without asking', async () => {
            const { user } = setup({
                product: jacketWithCells([[black, 4]]),
            });

            await user.click(size('S'));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            notPressed('S');
            expect(screen.getByText('3 sizes chosen')).toBeInTheDocument();
        });

        it('asks before "Use the sizes of Footwear" clears 8 cells, and Keep the sizes changes nothing', async () => {
            const { user } = setup();

            await addEightTypedCells(user);
            await chooseCategory(user, 'Footwear');
            await user.click(
                screen.getByRole('button', {
                    name: 'Use the sizes of Footwear',
                }),
            );

            const dialog = within(question('Use the sizes of Footwear?'));

            expect(
                dialog.getByText(
                    'This replaces the sizes and clears the 8 quantities you set across its materials.',
                ),
            ).toBeInTheDocument();

            await user.click(
                dialog.getByRole('button', { name: 'Keep the sizes' }),
            );

            expect(screen.getByText('4 sizes chosen')).toBeInTheDocument();
            expect(screen.getByText(/Sizes kept/)).toBeInTheDocument();

            await save(user);

            expect(submission().data.sizes).toEqual([1, 2, 3, 4]);
            expect(submittedCells(0)).toEqual({
                [indigo.id]: ['1', '2', '3', '4'],
                [black.id]: ['1', '2', '3', '4'],
            });
        });

        it('replaces the sizes and clears the 8 cells on Use Footwear sizes', async () => {
            const { user } = setup();

            await addEightTypedCells(user);
            await chooseCategory(user, 'Footwear');
            await user.click(
                screen.getByRole('button', {
                    name: 'Use the sizes of Footwear',
                }),
            );
            await user.click(
                screen.getByRole('button', { name: 'Use Footwear sizes' }),
            );

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(screen.getByText('11 sizes chosen')).toBeInTheDocument();
            expect(screen.queryByText(/Sizes kept/)).toBeNull();

            await save(user);

            expect(submission().data.sizes).toEqual([
                5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            ]);
            expect(submittedCells(0)).toEqual({});
        });

        it('applies "Use the sizes of Footwear" at once when no cell would be cleared', async () => {
            const { user } = setup();

            await chooseCategory(user, 'Apparel');
            await user.click(size('XS'));
            await chooseCategory(user, 'Footwear');
            await user.click(
                screen.getByRole('button', {
                    name: 'Use the sizes of Footwear',
                }),
            );

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            expect(screen.getByText('11 sizes chosen')).toBeInTheDocument();
        });

        it('keeps the sizes on a category change as soon as a line holds a typed quantity', async () => {
            const { user } = setup();

            await addGridLine(user, ['Indigo, IND']);
            await chooseCategory(user, 'Footwear');

            expect(screen.getByText('7 sizes chosen')).toBeInTheDocument();
            expect(
                screen.getByText(/Sizes kept: you changed them./),
            ).toBeInTheDocument();
        });

        it('asks "Use One size?" over 8 cells, and Keep the sizes changes nothing', async () => {
            const { user } = setup();

            await addEightTypedCells(user);
            await user.click(size('One size'));

            const dialog = within(question('Use One size?'));

            expect(
                dialog.getByText(
                    'This replaces the sizes and clears the 8 quantities you set across its materials.',
                ),
            ).toBeInTheDocument();
            expect(
                dialog.getByRole('button', { name: 'Keep the sizes' }),
            ).toHaveFocus();

            await user.click(
                dialog.getByRole('button', { name: 'Keep the sizes' }),
            );

            expect(screen.getByText('4 sizes chosen')).toBeInTheDocument();
            notPressed('One size');

            await save(user);

            expect(submission().data.sizes).toEqual([1, 2, 3, 4]);
            expect(submittedCells(0)).toEqual({
                [indigo.id]: ['1', '2', '3', '4'],
                [black.id]: ['1', '2', '3', '4'],
            });
        });

        it('sends One size alone and none of the 8 cells on Use One size', async () => {
            const { user } = setup();

            await addEightTypedCells(user);
            await user.click(size('One size'));
            await user.click(
                screen.getByRole('button', { name: 'Use One size' }),
            );

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            pressed('One size');
            expect(screen.getByText('1 size chosen')).toBeInTheDocument();

            await save(user);

            expect(submission().data.sizes).toEqual([16]);
            expect(submittedCells(0)).toEqual({});
        });

        it('says "the 1 quantity" and uses the workshop\'s own name for its One size', async () => {
            const { user } = setup({
                product: jacketWithCells([[black, 4]], { colours: [black] }),
                sizes: fullSizeOptionsFixture.map((option) =>
                    option.one_size
                        ? { ...option, label: 'Free size' }
                        : option,
                ),
            });

            await user.click(size('Free size'));

            expect(
                within(question('Use Free size?')).getByText(
                    'This replaces the sizes and clears the 1 quantity you set across its materials.',
                ),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Use Free size' }),
            ).toBeInTheDocument();
        });

        it('presses One size at once when no typed cell would be cleared', async () => {
            const { user } = setup({ product: denimJacket });

            await user.click(size('One size'));

            expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
            pressed('One size');
            expect(screen.getByText('1 size chosen')).toBeInTheDocument();
        });

        describe('a colour taken off while lines hold quantities for it', () => {
            const note = (colour: string, count: string) =>
                `Taking ${colour} off clears its ${count} across its materials when you save.`;

            it('says so under Colours, once per colour in list order, and stops when the colour is ticked again', async () => {
                const { user } = setup({ product: eightCells });

                expect(screen.queryByText(/^Taking .* off clears/)).toBeNull();

                await user.click(
                    screen.getByRole('button', { name: 'Remove Indigo' }),
                );
                await user.click(
                    screen.getByRole('button', { name: 'Remove Black' }),
                );

                const lines = screen
                    .getAllByText(/^Taking .* off clears/)
                    .map((line) => line.textContent);

                expect(lines).toEqual([
                    note('Black', '4 quantities'),
                    note('Indigo', '4 quantities'),
                ]);

                await user.click(
                    screen.getByRole('button', { name: 'Add colour' }),
                );
                await user.click(
                    await screen.findByRole('option', { name: 'Black, BLK' }),
                );
                await user.keyboard('{Escape}');

                expect(
                    screen.queryByText(note('Black', '4 quantities')),
                ).toBeNull();
                expect(
                    screen.getByText(note('Indigo', '4 quantities')),
                ).toBeInTheDocument();
            });

            it('says "its 1 quantity" for one cell, and says nothing for a colour with none', async () => {
                const { user } = setup({
                    product: jacketWithCells([[black, 2]]),
                });

                await user.click(
                    screen.getByRole('button', { name: 'Remove Indigo' }),
                );

                expect(screen.queryByText(/^Taking .* off clears/)).toBeNull();

                await user.click(
                    screen.getByRole('button', { name: 'Remove Black' }),
                );

                expect(
                    screen.getByText(note('Black', '1 quantity')),
                ).toBeInTheDocument();
            });
        });

        describe('another size pressed while One size is on, with 3 typed cells', () => {
            const oneSizeProduct = jacketWithCells(
                [
                    [white, 16],
                    [black, 16],
                    [indigo, 16],
                ],
                {
                    colours: [white, black, indigo],
                    sizes: [{ id: 16, label: 'One size' }],
                    one_size: true,
                },
            );

            it('asks "Take One size off this product?", and Keep One size leaves it pressed', async () => {
                const { user } = setup({ product: oneSizeProduct });

                await user.click(size('S'));

                const dialog = within(
                    question('Take One size off this product?'),
                );

                expect(
                    dialog.getByText(
                        'This clears the 3 quantities you set for One size across its materials.',
                    ),
                ).toBeInTheDocument();

                await user.click(
                    dialog.getByRole('button', { name: 'Keep One size' }),
                );

                pressed('One size');
                notPressed('S');
            });

            it('presses the size and clears the 3 cells on Take One size off', async () => {
                const { user } = setup({ product: oneSizeProduct });

                await user.click(size('S'));
                await user.click(
                    screen.getByRole('button', { name: 'Take One size off' }),
                );

                notPressed('One size');
                pressed('S');

                await save(user);

                expect(submission().data.sizes).toEqual([1]);
                expect(submittedCells(4)).toEqual({});
            });
        });
    });
});
