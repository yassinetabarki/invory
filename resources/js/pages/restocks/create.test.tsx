import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RestocksCreate from '@/pages/restocks/create';
import { colourNamed } from '@/test/fixtures/colours';
import {
    jacketLine,
    jacketRef,
    jeansLine,
    jeansRef,
    toteLine,
} from '@/test/fixtures/restocks';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { RestockLine } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const BLACK = colourNamed('Black').id;
const INDIGO = colourNamed('Indigo').id;

function card(name: string) {
    return within(screen.getByRole('region', { name }));
}

async function typeInto(name: string, field: string, value: string) {
    const user = userEvent.setup();
    const input = card(name).getByLabelText(field);

    await user.clear(input);
    await user.type(input, value);
}

/** The card's restock: Denim Jacket 24 (Indigo 4 · 8 · 6 · 2, Black M 4) and, when given, Denim Jeans 5-pocket 10. */
async function typeCardRestock() {
    for (const [field, value] of [
        ['Indigo S', '4'],
        ['Indigo M', '8'],
        ['Indigo L', '6'],
        ['Indigo XL', '2'],
        ['Black M', '4'],
    ]) {
        await typeInto('Denim Jacket', field, value);
    }
}

/** Add product → the picker's options → the product's line, answered in turn. */
async function addProduct(name: string, line: RestockLine) {
    const user = userEvent.setup();

    await user.click(screen.getByRole('button', { name: 'Add product' }));
    const options = inertiaMock.http.requests.at(-1);
    expect(options).toMatchObject({ path: '/restocks/products/options' });
    await act(async () => options?.resolve([jacketRef, jeansRef]));
    await user.click(screen.getByRole('option', { name: new RegExp(name) }));

    const request = inertiaMock.http.requests.at(-1);
    expect(request).toMatchObject({
        path: `/restocks/products/${line.product.id}`,
    });
    await act(async () => request?.resolve(line));
}

describe('restocks/create page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps(
            {},
            { url: '/restocks/create', component: 'restocks/create' },
        );
    });

    it('opens with today, the empty products and the total at 0 (AC-3)', () => {
        render(<RestocksCreate preset={null} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'New restock' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Add finished units that were made or arrived. They are added to what is on the shelf.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Date')).toHaveValue('2026-10-08');
        expect(screen.getByLabelText('Date')).toHaveAttribute(
            'max',
            '2026-10-08',
        );
        expect(
            screen.getByText('Delivery note, batch or who made them'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Add the products that were made or arrived, or scan their labels.',
            ),
        ).toBeInTheDocument();
        expect(screen.getByText('Total on this restock')).toBeInTheDocument();
        expect(screen.getByText('0 units')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Restock' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Adds to what is on the shelf. Doesn\'t change materials, "Can produce now" or sales. To set a count after a stock check, use Update stock on the product\'s page.',
            ),
        ).toBeInTheDocument();
    });

    it('shows a product with every colour and size, "now 2" under each field, and focuses its first field from its page (AC-3, AC-7)', () => {
        render(<RestocksCreate preset={jacketLine} />);

        const jacket = card('Denim Jacket');

        expect(jacket.getByText('PRD-JKT-001')).toBeInTheDocument();
        expect(jacket.getByText(/Sizes S, M, L, XL/)).toBeInTheDocument();
        expect(
            jacket.getByRole('table', { name: 'Units to add · Denim Jacket' }),
        ).toBeInTheDocument();
        expect(
            jacket.getByRole('columnheader', { name: 'Adding' }),
        ).toBeInTheDocument();
        expect(jacket.getByLabelText('Indigo S')).toHaveAccessibleDescription(
            'now 2',
        );
        expect(
            jacket.getByLabelText('Light wash XL'),
        ).toHaveAccessibleDescription('now 0');
        expect(jacket.getByLabelText('Black S')).toHaveFocus();
        expect(
            jacket.getByRole('button', {
                name: 'Remove Denim Jacket from this restock',
            }),
        ).toBeInTheDocument();
    });

    it('gives a "One size" product one field per colour (AC-3)', () => {
        render(<RestocksCreate preset={toteLine} />);

        const tote = card('Leather Tote');

        expect(
            tote.getByRole('columnheader', { name: 'Units to add' }),
        ).toBeInTheDocument();
        expect(
            tote.queryByRole('columnheader', { name: 'Adding' }),
        ).not.toBeInTheDocument();
        expect(tote.getByLabelText('Camel')).toHaveAccessibleDescription(
            'now 4',
        );
        expect(
            tote.getByText('One size', { exact: false }),
        ).toBeInTheDocument();
    });

    it('adds up what is typed: "Adding 24 units", the band and the button follow (AC-3)', async () => {
        render(<RestocksCreate preset={jacketLine} />);

        await typeCardRestock();
        expect(
            card('Denim Jacket').getByText(
                (_, element) =>
                    element?.tagName === 'P' &&
                    element.textContent === 'Adding 24 units',
            ),
        ).toBeInTheDocument();

        await addProduct('Denim Jeans 5-pocket', jeansLine);
        expect(
            card('Denim Jeans 5-pocket').getByLabelText('Indigo S'),
        ).toHaveFocus();
        await typeInto('Denim Jeans 5-pocket', 'Indigo M', '6');
        await typeInto('Denim Jeans 5-pocket', 'Indigo L', '4');

        expect(screen.getByText('34 units')).toBeInTheDocument();
        expect(screen.getByText(/2 products/)).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Restock 34 units' }),
        ).toBeInTheDocument();
    });

    it('saves everything at once: every field of every product, the details, and where it came from (AC-3)', async () => {
        const user = userEvent.setup();

        render(<RestocksCreate preset={jacketLine} />);
        await typeCardRestock();
        await user.type(screen.getByLabelText(/Reference/), 'BL-0412');
        await user.click(
            screen.getByRole('button', { name: 'Restock 24 units' }),
        );

        expect(inertiaMock.router.post).toHaveBeenCalledTimes(1);
        const [url, data] = inertiaMock.router.post.mock.lastCall ?? [];

        expect(url).toBe('/restocks');
        expect(data).toMatchObject({
            restocked_on: '2026-10-08',
            reference: 'BL-0412',
            note: '',
            from_product: 1,
        });
        expect(data.products).toHaveLength(1);
        expect(data.products[0].product_id).toBe(1);
        expect(data.products[0].units[INDIGO]).toEqual({
            1: '4',
            2: '8',
            3: '6',
            4: '2',
        });
        expect(data.products[0].units[BLACK]).toEqual({
            1: '',
            2: '4',
            3: '',
            4: '',
        });
    });

    it("places the server's messages: under the field's product, a summary at the top, the focus on the first wrong field (AC-3)", async () => {
        const user = userEvent.setup();

        render(<RestocksCreate preset={jacketLine} />);
        await typeInto('Denim Jacket', 'Indigo M', '2.5');
        await user.click(screen.getByRole('button', { name: /Restock/ }));

        const options = inertiaMock.router.post.mock.lastCall?.[2] as {
            onError: (errors: Record<string, string>) => void;
        };
        await act(async () =>
            options.onError({
                [`products.0.units.${INDIGO}.2`]:
                    'Indigo M: Enter a whole number from 0 to 1,000,000.',
                restocked_on: "The date can't be in the future.",
            }),
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Check Denim Jacket: 1 field can't be saved.",
        );
        expect(
            card('Denim Jacket').getByText(
                'Indigo M: Enter a whole number from 0 to 1,000,000.',
            ),
        ).toBeInTheDocument();
        expect(card('Denim Jacket').getByLabelText('Indigo M')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
        expect(
            card('Denim Jacket').getByLabelText('Indigo M'),
        ).toHaveAccessibleDescription(
            'now 0 Indigo M: Enter a whole number from 0 to 1,000,000.',
        );
        expect(card('Denim Jacket').getByLabelText('Indigo M')).toHaveFocus();
        expect(
            screen.getByText("The date can't be in the future."),
        ).toBeInTheDocument();
        expect(card('Denim Jacket').getByLabelText('Indigo M')).toHaveValue(
            '2.5',
        );
    });

    it('says a product with nothing typed under it, nothing at all under the band, and a deleted product at the top (AC-3)', async () => {
        const user = userEvent.setup();

        render(<RestocksCreate preset={jacketLine} />);
        await user.click(screen.getByRole('button', { name: 'Restock' }));

        const options = inertiaMock.router.post.mock.lastCall?.[2] as {
            onError: (errors: Record<string, string>) => void;
        };
        await act(async () =>
            options.onError({
                'products.0':
                    'Enter at least one unit of Denim Jacket, or remove it from this restock.',
                products: 'Enter at least one unit to restock.',
            }),
        );

        expect(
            card('Denim Jacket').getByText(
                'Enter at least one unit of Denim Jacket, or remove it from this restock.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Enter at least one unit to restock.'),
        ).toBeInTheDocument();

        await act(async () =>
            options.onError({
                'products.0.product':
                    'Denim Jacket was deleted. Remove it from this restock.',
            }),
        );

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Denim Jacket was deleted. Remove it from this restock.',
        );
        expect(inertiaMock.http.requests.at(-1)).toMatchObject({
            path: '/restocks/products/1',
        });
    });

    it('says "Couldn\'t save the restock. Try again." when the save fails, keeping what was typed', async () => {
        const user = userEvent.setup();

        render(<RestocksCreate preset={jacketLine} />);
        await typeInto('Denim Jacket', 'Black M', '3');
        await user.click(
            screen.getByRole('button', { name: 'Restock 3 units' }),
        );

        const options = inertiaMock.router.post.mock.lastCall?.[2] as {
            onNetworkError: () => boolean;
        };
        await act(async () => {
            options.onNetworkError();
        });

        expect(screen.getByRole('alert')).toHaveTextContent(
            "Couldn't save the restock. Try again.",
        );
        expect(card('Denim Jacket').getByLabelText('Black M')).toHaveValue('3');
    });

    it('removes a product with ✕, and Add product moves the focus to a product already there', async () => {
        const user = userEvent.setup();

        render(<RestocksCreate preset={jacketLine} />);
        await user.click(
            screen.getByRole('button', {
                name: 'Remove Denim Jacket from this restock',
            }),
        );

        expect(
            screen.queryByRole('region', { name: 'Denim Jacket' }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText(
                'Add the products that were made or arrived, or scan their labels.',
            ),
        ).toBeInTheDocument();
    });

    it('leaves at once when nothing is typed, and asks first otherwise (AC-3)', async () => {
        const user = userEvent.setup();

        const { unmount } = render(<RestocksCreate preset={null} />);
        await user.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(inertiaMock.router.visit).toHaveBeenLastCalledWith('/restocks');
        unmount();

        render(<RestocksCreate preset={jacketLine} />);
        await typeCardRestock();
        await user.click(screen.getByRole('button', { name: 'Cancel' }));

        const confirm = within(screen.getByRole('alertdialog'));
        expect(
            confirm.getByRole('heading', { name: 'Discard this restock?' }),
        ).toBeInTheDocument();
        expect(
            confirm.getByText(
                "The 24 units you entered aren't added to the shelf.",
            ),
        ).toBeInTheDocument();
        expect(
            confirm.getByRole('button', { name: 'Keep editing' }),
        ).toBeInTheDocument();

        await user.click(
            confirm.getByRole('button', { name: 'Discard restock' }),
        );
        // Started from the Denim Jacket's page: back to it.
        expect(inertiaMock.router.visit).toHaveBeenLastCalledWith(
            '/products/1',
        );
    });
});
