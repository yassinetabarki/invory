import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import OrdersIndex from '@/pages/orders/index';
import { paginate } from '@/test/fixtures';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type {
    Order,
    OrderLine,
    OrderProduct,
    OrdersIndexProps,
} from '@/types/domain';

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
    return {
        id,
        product,
        quantity,
        to_start: quantity,
        in_cutting: 0,
        ready_to_ship: 0,
        shipped: 0,
        started: 0,
        status: 'not_started',
        status_label: 'Not started',
    };
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
    to_start: 80,
    in_cutting: 0,
    ready_to_ship: 0,
    shipped: 0,
    status: 'not_started',
    status_label: 'Not started',
    delete_refusal: null,
};

function withOrders(
    orders: Order[],
    search: string | null = null,
): OrdersIndexProps {
    return {
        orders: paginate(orders, { path: '/orders' }),
        filters: { search, status: 'open' },
        statuses: [
            { value: 'open', label: 'Open orders' },
            { value: 'shipped', label: 'Shipped' },
            { value: 'all', label: 'All orders' },
        ],
        totals: {
            open_orders: 0,
            to_start: 0,
            in_cutting: 0,
            ready_to_ship: 0,
        },
        has_orders: orders.length > 0,
    };
}

function renderPage(props: OrdersIndexProps) {
    const user = userEvent.setup();

    render(<OrdersIndex {...props} />);

    return { user };
}

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

    it('lists Boutique Lina with her date, products, units and note', () => {
        renderPage(withOrders([lina]));

        const row = within(
            screen.getByRole('table', { name: 'Orders' }),
        ).getAllByRole('row')[1];

        expect(within(row).getByText('15 Oct 2026')).toBeVisible();
        expect(within(row).getByText('Boutique Lina')).toBeVisible();
        expect(
            within(row)
                .getAllByRole('listitem')
                .map((item) => item.textContent),
        ).toEqual(['50 × Denim Jacket', '30 × Denim Jeans 5-pocket']);
        expect(within(row).getByText('80')).toBeVisible();
        expect(
            within(row).getByText('Deliver before the spring show'),
        ).toBeVisible();
    });

    it('invites the first order when there are none', () => {
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
    });

    it('says nothing matches and clears the search', async () => {
        const { user } = renderPage(withOrders([], 'zzz'));

        expect(screen.getByText('No orders match your search')).toBeVisible();
        expect(screen.getByRole('searchbox')).toHaveValue('zzz');

        await user.click(screen.getByRole('button', { name: 'Clear search' }));

        expect(inertiaMock.router.get).toHaveBeenCalledExactlyOnceWith(
            '/orders',
            {},
            {
                only: ['orders', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
        expect(screen.getByRole('searchbox')).toHaveValue('');
    });
});
