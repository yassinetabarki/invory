import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersIndex from '@/pages/orders/index';
import { paginate, withOneSize } from '@/test/fixtures';
import { act } from '@testing-library/react';
import { amira, ref } from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type {
    OpenOrderTotals,
    Order,
    OrderLine,
    OrderProduct,
    OrderStatusFilter,
    OrdersIndexProps,
} from '@/types/domain';

const device = vi.hoisted(() => ({ mobile: false }));

vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function product(id: number, name: string) {
    return {
        id,
        sku: `PRD-${id}`,
        name,
        status: 'active' as const,
        deleted: false,
    };
}

function notStarted(
    id: number,
    product: OrderProduct,
    quantity: number,
): OrderLine {
    return withOneSize({
        id,
        product,
        quantity,
        to_deliver: quantity,
        delivered: 0,
        cancelled: 0,
        status: 'not_started',
        status_label: 'Not started',
    });
}

const lina: Order = {
    id: 7,
    client: 'Boutique Lina',
    ordered_on: '2026-10-15',
    note: 'Deliver before the spring show',
    lines: [
        notStarted(1, product(1, 'Denim Jacket'), 50),
        notStarted(2, product(5, 'Denim Jeans 5-pocket'), 30),
    ],
    units: 80,
    to_deliver: 80,
    delivered: 0,
    cancelled: 0,
    status: 'not_started',
    status_label: 'Not started',
    client_id: 3,
    client_details: null,
};

const zeroTotals: OpenOrderTotals = {
    open_orders: 0,
    to_deliver: 0,
};

type Options = {
    search?: string | null;
    status?: OrderStatusFilter;
    hasOrders?: boolean;
};

function withOrders(
    orders: Order[],
    {
        search = null,
        status = 'open',
        hasOrders = orders.length > 0,
    }: Options = {},
): OrdersIndexProps {
    return {
        orders: paginate(orders, { path: '/orders' }),
        filters: { search, status, client: null },
        statuses: [
            { value: 'open', label: 'Open orders' },
            { value: 'delivered', label: 'Delivered' },
            { value: 'cancelled', label: 'Cancelled' },
            { value: 'all', label: 'All orders' },
        ],
        totals: zeroTotals,
        has_orders: hasOrders,
        client_filter: null,
    };
}

function renderPage(props: OrdersIndexProps) {
    const user = userEvent.setup();

    render(<OrdersIndex {...props} />);

    return { user };
}

const LIST_VISIT_OPTIONS = {
    only: ['orders', 'filters', 'client_filter'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

describe('orders/index page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/orders', component: 'orders/index' });
    });

    it('has the title, the description and a New order button', () => {
        renderPage(withOrders([lina]));

        expect(
            screen.getByRole('heading', { level: 1, name: 'Orders' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Every order your clients place with you.'),
        ).toBeVisible();
        expect(screen.getByRole('link', { name: 'New order' })).toHaveAttribute(
            'href',
            '/orders/create',
        );
        expect(screen.getByRole('searchbox')).toHaveAttribute(
            'placeholder',
            'Search client, product or note',
        );
    });

    it('lists the orders in a table', () => {
        renderPage(withOrders([lina]));

        expect(
            within(screen.getByRole('table', { name: 'Orders' })).getByText(
                'Boutique Lina',
            ),
        ).toBeVisible();
    });

    it('offers the three statuses and reloads only the list with the chosen one, keeping the search', async () => {
        const { user } = renderPage(withOrders([lina], { search: 'lina' }));

        const status = screen.getByRole('combobox', { name: 'Status' });

        expect(status).toHaveTextContent('Open orders');

        await user.click(status);

        expect(
            screen.getAllByRole('option').map((option) => option.textContent),
        ).toEqual(['Open orders', 'Delivered', 'Cancelled', 'All orders']);

        await user.click(screen.getByRole('option', { name: 'All orders' }));

        expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
            '/orders',
            { search: 'lina', status: 'all' },
            LIST_VISIT_OPTIONS,
        );
    });

    it.each([
        {
            name: 'nothing matches',
            options: { search: 'zzz', hasOrders: true },
            title: 'No orders match your filters',
            description:
                'Try a different client, product or note, or clear the filters to see your open orders.',
            button: 'Clear filters',
            query: {},
        },
        {
            name: 'every order has been delivered or cancelled',
            options: { hasOrders: true },
            title: 'No open orders',
            description: 'Every order has been delivered or cancelled.',
            button: 'Show all orders',
            query: { status: 'all' },
        },
    ])(
        'says what is empty when $name',
        async ({ options, title, description, button, query }) => {
            const { user } = renderPage(withOrders([], options));

            expect(screen.getByText(title)).toBeVisible();
            expect(screen.getByText(description)).toBeVisible();

            await user.click(screen.getByRole('button', { name: button }));

            expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
                '/orders',
                query,
                LIST_VISIT_OPTIONS,
            );
            expect(screen.getByRole('searchbox')).toHaveValue('');
        },
    );

    it('invites the first order when there are none, with the totals at zero', () => {
        renderPage(withOrders([]));

        expect(screen.getByText('No orders yet')).toBeVisible();
        expect(
            screen.getByText(
                "Record a client's order to keep track of what you've promised to make.",
            ),
        ).toBeVisible();
        expect(screen.getAllByRole('link', { name: 'New order' })).toHaveLength(
            2,
        );
        expect(screen.queryByRole('searchbox')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('combobox', { name: 'Status' }),
        ).not.toBeInTheDocument();

        expect(
            within(screen.getByRole('region', { name: 'Open orders totals' }))
                .getAllByRole('definition')
                .map((item) => item.textContent),
        ).toEqual(['0', '0 units']);
    });

    it('filters by client, kept with the search and status in the address, and Clear filters drops it (INV-4 AC-7)', async () => {
        const { user } = renderPage(withOrders([lina], { search: 'lina' }));

        await user.click(
            screen.getByRole('combobox', { name: 'Client All clients' }),
        );
        await act(async () => {
            inertiaMock.http.requests.at(-1)?.resolve([ref(amira)]);
        });

        expect(
            screen.getByRole('option', { name: 'All clients' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: /Amira Ben Salah/ }),
        ).toHaveTextContent('Instagram · Sousse · 22 345 678');

        await user.click(
            screen.getByRole('option', { name: /Amira Ben Salah/ }),
        );

        expect(inertiaMock.router.get).toHaveBeenLastCalledWith(
            '/orders',
            { search: 'lina', client: 1 },
            LIST_VISIT_OPTIONS,
        );
        expect(
            screen.getByRole('combobox', { name: 'Client Amira Ben Salah' }),
        ).toBeInTheDocument();
    });

    it('puts Status and Client behind Filters on a phone, "Filters · 1" when one is set (INV-14 AC-5)', async () => {
        device.mobile = true;
        const { user } = renderPage(
            withOrders([lina], { status: 'delivered' }),
        );

        expect(
            screen.queryByRole('combobox', { name: /Client/ }),
        ).not.toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Filters · 1' }));

        const panel = within(screen.getByRole('dialog', { name: 'Filters' }));

        expect(
            panel.getByRole('combobox', { name: 'Status' }),
        ).toHaveTextContent('Delivered');
        expect(
            panel.getByRole('combobox', { name: 'Client All clients' }),
        ).toBeInTheDocument();
        device.mobile = false;
    });
});
