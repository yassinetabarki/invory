import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RestocksIndex from '@/pages/restocks/index';
import { paginate } from '@/test/fixtures';
import {
    bl0412,
    jacketRef,
    jeansRef,
    returned,
    tote6,
} from '@/test/fixtures/restocks';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type {
    RestockProductRef,
    RestockSummary,
    RestocksIndexProps,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function props(
    restocks: RestockSummary[],
    {
        search = null,
        product = null,
        hasRestocks = restocks.length > 0,
        total = restocks.length,
    }: {
        search?: string | null;
        product?: RestockProductRef | null;
        hasRestocks?: boolean;
        total?: number;
    } = {},
): RestocksIndexProps {
    return {
        restocks: paginate(restocks, {
            path: '/restocks',
            total,
            to: restocks.length,
            ...(total > restocks.length
                ? {
                      last_page: 2,
                      links: [
                          { url: null, label: 'Previous', active: false },
                          { url: '/restocks?page=1', label: '1', active: true },
                          {
                              url: '/restocks?page=2',
                              label: '2',
                              active: false,
                          },
                          {
                              url: '/restocks?page=2',
                              label: 'Next',
                              active: false,
                          },
                      ],
                  }
                : {}),
        }),
        filters: { search, product: product?.id ?? null, restock: null },
        product_filter: product,
        has_restocks: hasRestocks,
        restock: null,
    };
}

describe('restocks/index page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/restocks', component: 'restocks/index' });
    });

    it('has the title, the description, New restock, the search and the Product filter', () => {
        render(<RestocksIndex {...props([bl0412])} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Restocks' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Finished units that were made or arrived, added to the shelf in their colours and sizes.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'New restock' }),
        ).toHaveAttribute('href', '/restocks/create');
        expect(screen.getByRole('searchbox')).toHaveAttribute(
            'placeholder',
            'Search reference, note or product',
        );
        expect(
            screen.getByRole('combobox', { name: 'Product All products' }),
        ).toBeInTheDocument();
    });

    it('shows each restock: date, reference or —, products, units, who, and Undone', () => {
        render(
            <RestocksIndex
                {...props([bl0412, tote6, returned], { total: 48 })}
            />,
        );

        const table = within(screen.getByRole('table', { name: 'Restocks' }));
        const rows = table
            .getAllByRole('row')
            .slice(1)
            .map((row) =>
                within(row)
                    .getAllByRole('cell')
                    .slice(0, 5)
                    .map((cell) => cell.textContent),
            );

        expect(rows).toEqual([
            [
                '25 Oct 2026',
                'BL-0412',
                'Denim Jacket 24 · Denim Jeans 5-pocket 10',
                '34',
                'Admin',
            ],
            ['22 Oct 2026', '—', 'Leather Tote 6', '6', 'Sami'],
            [
                '18 Oct 2026',
                'Return Boutique LinaUndone',
                'Classic White T-shirt 12',
                '12',
                'Admin',
            ],
        ]);
        expect(screen.getByText('Showing 1–3 of 48')).toBeInTheDocument();
    });

    it('names two products and counts the others, and shows — when the recorder is gone', () => {
        render(
            <RestocksIndex
                {...props([
                    {
                        ...bl0412,
                        products: [
                            { name: 'Denim Jacket', units: 24 },
                            { name: 'Leather Tote', units: 6 },
                        ],
                        more_products: 2,
                        recorded_by: null,
                    },
                ])}
            />,
        );

        const row = within(screen.getByRole('table')).getAllByRole('row')[1];

        expect(row).toHaveTextContent(
            'Denim Jacket 24 · Leather Tote 6 and 2 more',
        );
        expect(within(row).getAllByRole('cell')[4]).toHaveTextContent('—');
    });

    it('opens a restock from its Open button, its row or its card, keeping the filters in the address', async () => {
        const user = userEvent.setup();
        const view = () =>
            render(
                <RestocksIndex {...props([bl0412, tote6], { search: 'BL' })} />,
            );

        let { unmount } = view();
        await user.click(
            within(screen.getByRole('table')).getByRole('button', {
                name: 'Open restock of 25 Oct 2026, BL-0412',
            }),
        );
        expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
            '/restocks',
            { search: 'BL', restock: 3 },
            expect.objectContaining({ only: ['restock', 'filters'] }),
        );
        unmount();

        ({ unmount } = view());
        await user.click(
            within(screen.getByRole('table')).getByText('Leather Tote 6'),
        );
        expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
            '/restocks',
            { search: 'BL', restock: 2 },
            expect.anything(),
        );
        unmount();

        view();
        await user.click(
            within(screen.getByRole('list', { name: 'Restocks' })).getByRole(
                'button',
                { name: 'Open restock of 22 Oct 2026' },
            ),
        );
        expect(inertiaMock.router.get).toHaveBeenCalledTimes(3);
    });

    it('shows each restock as a card on a phone', () => {
        render(<RestocksIndex {...props([returned])} />);

        const card = within(
            screen.getByRole('list', { name: 'Restocks' }),
        ).getByRole('button', {
            name: 'Open restock of 18 Oct 2026, Return Boutique Lina',
        });

        expect(card).toHaveTextContent(
            '18 Oct 2026 · Return Boutique LinaUndone',
        );
        expect(card).toHaveTextContent('Classic White T-shirt 12');
        expect(card).toHaveTextContent('12 units · by Admin');
    });

    it('says "No restocks yet" with New restock when there are none', () => {
        render(<RestocksIndex {...props([], { hasRestocks: false })} />);

        expect(screen.getByText('No restocks yet')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Each time finished units are made or arrive, add them here. They go on the shelf in their colours and sizes.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: 'New restock' }),
        ).toHaveLength(2);
        expect(screen.queryByRole('searchbox')).not.toBeInTheDocument();
    });

    it('says no restock matches, and Clear filters goes back to the whole list', async () => {
        const user = userEvent.setup();

        render(
            <RestocksIndex
                {...props([], { search: 'BL-9', hasRestocks: true })}
            />,
        );

        expect(
            screen.getByText('No restock matches "BL-9".'),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Clear filters' }));

        expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
            '/restocks',
            {},
            expect.objectContaining({
                only: ['restocks', 'filters', 'product_filter'],
            }),
        );
    });

    it('shows the chosen product in the filter, and "Filters · 1" on a phone', () => {
        render(<RestocksIndex {...props([bl0412], { product: jacketRef })} />);

        expect(
            screen.getByRole('combobox', { name: 'Product Denim Jacket' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Filters · 1' }),
        ).toBeInTheDocument();
    });

    it('shows placeholders while loading and "Couldn\'t load the restocks." with Try again on an error', async () => {
        const user = userEvent.setup();

        render(<RestocksIndex {...props([bl0412])} />);
        await user.type(screen.getByRole('searchbox'), 'BL');

        await vi.waitFor(() =>
            expect(inertiaMock.router.get).toHaveBeenCalled(),
        );
        const options = inertiaMock.router.get.mock.lastCall?.[2] as {
            onStart: () => void;
            onHttpException: () => boolean;
            onFinish: () => void;
        };

        options.onStart();
        expect(
            await screen.findByLabelText('Loading restocks'),
        ).toBeInTheDocument();

        options.onHttpException();
        options.onFinish();
        expect(
            await screen.findByText("Couldn't load the restocks."),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Try again' }),
        ).toBeInTheDocument();
    });

    it('chooses a product in the Product filter: the options come from the server, a draft shows its badge', async () => {
        const user = userEvent.setup();

        render(<RestocksIndex {...props([bl0412])} />);
        await user.click(
            screen.getByRole('combobox', { name: 'Product All products' }),
        );

        expect(inertiaMock.http.requests[0]).toMatchObject({
            method: 'get',
            path: '/restocks/products/options',
        });
        await act(async () => {
            inertiaMock.http.requests[0].resolve([jacketRef, jeansRef]);
        });

        expect(
            screen.getByRole('option', { name: /Denim Jeans 5-pocket/ }),
        ).toHaveTextContent('PRD-JNS-005Draft');

        await user.click(screen.getByRole('option', { name: /Denim Jacket/ }));

        expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
            '/restocks',
            { product: 1 },
            expect.objectContaining({
                only: ['restocks', 'filters', 'product_filter'],
            }),
        );
    });
});
