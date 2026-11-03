import { act, fireEvent, render, screen, within } from '@testing-library/react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { OrderForm } from '@/components/orders/order-form';
import { ORDER_SEARCH_DEBOUNCE_MS } from '@/components/orders/use-product-search';
import {
    black,
    indigo,
    jacketStock,
    jeansStock,
    lightWash,
    sizeIds,
    toteStock,
} from '@/test/fixtures';
import { inertiaMock, resetInertiaMock, setFormState } from '@/test/inertia';
import type { OrderStockProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const device = vi.hoisted(() => ({ mobile: false }));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

function renderForm() {
    render(
        <OrderForm
            title="New order"
            action={{ url: '/orders', method: 'post' }}
            cancelHref="/orders"
        />,
    );
}

/** Searches, answers with these products, and presses Add to order on `name`. */
async function addProduct(name: string, found: OrderStockProduct[]) {
    fireEvent.change(
        screen.getByRole('searchbox', { name: 'Search products' }),
        { target: { value: name } },
    );
    await act(async () => {
        await vi.advanceTimersByTimeAsync(ORDER_SEARCH_DEBOUNCE_MS);
    });
    await act(async () =>
        inertiaMock.http.requests
            .at(-1)
            ?.resolve({ total: found.length, products: found }),
    );
    fireEvent.click(
        screen.getByRole('button', { name: `Add ${name} to this order` }),
    );
}

function block(name: string) {
    return within(screen.getByRole('region', { name }));
}

function type(field: HTMLElement, value: string) {
    fireEvent.change(field, { target: { value } });
}

describe('<OrderForm> Products (INV-13)', () => {
    beforeEach(() => {
        resetInertiaMock();
        device.mobile = false;
        vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('adds the Jacket and the Jeans, takes quantities per colour and size with the shelf under each, and adds them up (AC-2)', async () => {
        renderForm();

        expect(screen.getByText('No products on this order yet')).toBeVisible();

        await addProduct('Denim Jacket', [jacketStock, jeansStock]);

        // The focus goes to the first quantity field of the product added.
        expect(block('Denim Jacket').getByLabelText('Black S')).toHaveFocus();
        expect(
            screen.getByRole('heading', {
                level: 3,
                name: 'On this order · 1 product',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'On this order' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('table', {
                name: 'Quantity per colour and size · Denim Jacket',
            }),
        ).toBeInTheDocument();

        const jacket = block('Denim Jacket');

        expect(jacket.getByLabelText('Indigo S')).toHaveAccessibleDescription(
            'In stock 2',
        );
        expect(jacket.getByLabelText('Indigo M')).toHaveAccessibleDescription(
            'Out of stock',
        );
        expect(
            jacket.getByText('Out of stock', {
                selector: `#order-p1-c${indigo.id}-s${sizeIds.M}-stock`,
            }),
        ).toHaveClass('font-bold');
        expect(
            jacket.getByLabelText('Light wash XL'),
        ).toHaveAccessibleDescription('Out of stock');

        type(jacket.getByLabelText('Indigo S'), '4');
        type(jacket.getByLabelText('Indigo M'), '8');

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Add Denim Jeans 5-pocket to this order',
            }),
        );
        type(block('Denim Jeans 5-pocket').getByLabelText('Indigo M'), '10');

        expect(jacket.getByText('Total 12 units')).toBeVisible();
        expect(
            block('Denim Jeans 5-pocket').getByText('Total 10 units'),
        ).toBeVisible();
        expect(
            screen.getByText('Total on this order · 22 units · 2 products'),
        ).toBeVisible();
        // Beside Save order and Cancel.
        expect(screen.getByText('22 units · 2 products')).toBeVisible();
        expect(
            screen.getByRole('heading', {
                level: 3,
                name: 'On this order · 2 products',
            }),
        ).toBeInTheDocument();
        // No warning when a number is above the stock: Out of stock never blocks.
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: 'Save order' }));

        const sent = inertiaMock.submissions.at(-1)?.data as {
            lines: {
                product_id: number;
                units: Record<number, Record<number, string>>;
            }[];
        };

        expect(inertiaMock.submissions.at(-1)).toMatchObject({
            action: '/orders',
            method: 'post',
        });
        expect(sent.lines.map((line) => line.product_id)).toEqual([1, 5]);
        // Every cell of the grid goes, an untouched one as ''.
        expect(sent.lines[0].units[indigo.id]).toEqual({
            [sizeIds.S]: '4',
            [sizeIds.M]: '8',
            [sizeIds.L]: '',
            [sizeIds.XL]: '',
        });
        expect(Object.keys(sent.lines[0].units).map(Number)).toEqual([
            black.id,
            indigo.id,
            lightWash.id,
        ]);
        expect(sent.lines[1].units[indigo.id][sizeIds.M]).toBe('10');
    });

    it('removes a product and goes back to the search; an empty cell or 0 counts nothing', async () => {
        renderForm();
        await addProduct('Denim Jacket', [jacketStock]);

        type(block('Denim Jacket').getByLabelText('Black S'), '0');
        type(block('Denim Jacket').getByLabelText('Black M'), 'abc');
        expect(block('Denim Jacket').getByText('Total 0 units')).toBeVisible();

        fireEvent.click(
            screen.getByRole('button', {
                name: 'Remove Denim Jacket from this order',
            }),
        );

        expect(
            screen.queryByRole('region', { name: 'Denim Jacket' }),
        ).not.toBeInTheDocument();
        expect(screen.getByText('No products on this order yet')).toBeVisible();
        expect(
            screen.getByRole('searchbox', { name: 'Search products' }),
        ).toHaveFocus();
        expect(
            screen.getByRole('button', {
                name: 'Add Denim Jacket to this order',
            }),
        ).toBeEnabled();
    });

    it('gives a "One size" product the columns Colour · Quantity', async () => {
        renderForm();
        await addProduct('Leather Tote', [toteStock]);

        const table = screen.getByRole('table', {
            name: 'Quantity per colour and size · Leather Tote',
        });

        expect(
            within(table)
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Colour', 'Quantity']);
        expect(
            block('Leather Tote').getByLabelText('Camel'),
        ).toHaveAccessibleDescription('In stock 4');
    });

    it("shows the server's messages under their field and the line, the focus on the first (AC-3)", async () => {
        renderForm();
        await addProduct('Denim Jacket', [jacketStock, jeansStock]);
        fireEvent.click(
            screen.getByRole('button', {
                name: 'Add Denim Jeans 5-pocket to this order',
            }),
        );
        type(block('Denim Jacket').getByLabelText('Indigo M'), '2.5');

        setFormState({
            errors: {
                [`lines.0.units.${indigo.id}.${sizeIds.M}`]:
                    'Indigo M: Enter a whole number from 0 to 1,000,000.',
                'lines.1.units':
                    'Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order.',
            },
        });

        const field = block('Denim Jacket').getByLabelText('Indigo M');

        expect(field).toBeInvalid();
        expect(field).toHaveFocus();
        expect(field).toHaveAccessibleDescription(
            'Out of stock Indigo M: Enter a whole number from 0 to 1,000,000.',
        );
        expect(field).toHaveValue('2.5');
        expect(
            block('Denim Jeans 5-pocket').getByText(
                'Enter at least one unit of Denim Jeans 5-pocket, or remove it from this order.',
            ),
        ).toBeVisible();
        expect(
            block('Denim Jeans 5-pocket').getByLabelText('Indigo M'),
        ).toBeInvalid();
    });

    it('says "Add at least one product to this order." with no product', () => {
        renderForm();

        setFormState({
            errors: { lines: 'Add at least one product to this order.' },
        });

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Add at least one product to this order.',
        );
    });

    it('keeps everything typed and says so when the save fails (AC-3)', async () => {
        renderForm();
        await addProduct('Denim Jacket', [jacketStock]);
        type(block('Denim Jacket').getByLabelText('Indigo S'), '4');

        fireEvent.click(screen.getByRole('button', { name: 'Save order' }));
        const options = inertiaMock.submissions.at(-1)?.options as {
            onNetworkError: () => boolean;
        };

        act(() => {
            options.onNetworkError();
        });

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't save the order. Try again.",
        );
        expect(block('Denim Jacket').getByLabelText('Indigo S')).toHaveValue(
            '4',
        );
    });

    it('shows a spinner on Save order while saving, keeping its label', () => {
        renderForm();
        setFormState({ processing: true });

        expect(
            screen.getByRole('button', { name: /Save order/ }),
        ).toBeDisabled();
        expect(
            screen.getByRole('button', { name: /Save order/ }),
        ).toHaveTextContent('Save order');
    });

    it('reads a colour band with three sizes to a row on a phone, and Save order in a bar fixed at the bottom (AC-7)', async () => {
        device.mobile = true;
        renderForm();
        await addProduct('Denim Jacket', [jacketStock]);

        const indigoBand = screen.getByRole('group', { name: /Indigo/ });

        expect(
            within(indigoBand).getByLabelText('S'),
        ).toHaveAccessibleDescription('In stock 2');
        expect(
            within(indigoBand).getByLabelText('S').closest('.grid-cols-3'),
        ).not.toBeNull();
        expect(
            screen.getByRole('button', {
                name: 'Remove Denim Jacket from this order',
            }),
        ).toHaveClass('max-sm:size-11');
        expect(
            screen.getByRole('button', { name: 'Save order' }).parentElement,
        ).toHaveClass('max-md:fixed');
    });
});
