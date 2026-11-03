import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import ClientsShow from '@/pages/clients/show';
import { paginate, withOneSize } from '@/test/fixtures';
import { amira, ines } from '@/test/fixtures/clients';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type {
    ClientShowProps,
    ClientSummary,
    Order,
    OrderStatusFilter,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

/** One of Amira's orders: `units` of one product, open or delivered. */
function order(
    id: number,
    date: string,
    product: string,
    units: number,
    shipped: boolean,
): Order {
    return {
        id,
        client: 'Amira Ben Salah',
        ordered_on: date,
        note: null,
        lines: [
            withOneSize({
                id: id * 10,
                product: {
                    id,
                    sku: `PRD-${id}`,
                    name: product,
                    status: 'active',
                    deleted: false,
                },
                quantity: units,
                to_deliver: shipped ? 0 : units,
                delivered: shipped ? units : 0,
                cancelled: 0,
                status: shipped ? 'delivered' : 'not_started',
                status_label: shipped ? 'Delivered' : 'Not started',
            }),
        ],
        units,
        to_deliver: shipped ? 0 : units,
        delivered: shipped ? units : 0,
        cancelled: 0,
        status: shipped ? 'delivered' : 'not_started',
        status_label: shipped ? 'Delivered' : 'Not started',
        client_id: 1,
        client_details: null,
    };
}

const amirasOrders = [
    order(31, '2026-10-28', 'Denim Jacket', 2, false),
    order(22, '2026-10-14', 'Leather Tote', 1, true),
    order(13, '2026-09-01', 'Classic White T-shirt', 2, true),
];

function props(
    client: ClientSummary,
    orders: Order[],
    status: OrderStatusFilter = 'all',
): ClientShowProps {
    return {
        client,
        orders: paginate(orders, { path: `/clients/${client.id}` }),
        filters: { status },
        statuses: [
            { value: 'open', label: 'Open orders' },
            { value: 'delivered', label: 'Delivered' },
            { value: 'cancelled', label: 'Cancelled' },
            { value: 'all', label: 'All orders' },
        ],
    };
}

describe('clients/show page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/clients/1', component: 'clients/show' });
    });

    it('shows Amira: her source, city and a call link, New order for her, and her three figures', () => {
        render(<ClientsShow {...props(amira, amirasOrders)} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Amira Ben Salah' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', {
                name: 'Call Amira Ben Salah, 22 345 678',
            }),
        ).toHaveAttribute('href', 'tel:22345678');
        expect(
            screen.getByRole('link', { name: 'New order for Amira Ben Salah' }),
        ).toHaveAttribute('href', '/orders/create?client=1');
        expect(screen.getByText('Instagram')).toBeInTheDocument();
        expect(screen.getByText('Sousse')).toBeInTheDocument();

        const figures = Array.from(document.querySelectorAll('dl > div')).map(
            (figure) => figure.textContent,
        );

        expect(figures).toEqual([
            'Open orders1',
            'Orders3',
            'Last order28 Oct 2026',
        ]);
    });

    it('lists her orders newest first without a Client column, each date opening the order', () => {
        render(<ClientsShow {...props(amira, amirasOrders)} />);

        const table = within(screen.getByRole('table', { name: 'Orders' }));

        expect(
            table.queryByRole('columnheader', { name: 'Client' }),
        ).not.toBeInTheDocument();
        expect(
            table.getByRole('link', { name: '28 Oct 2026' }),
        ).toHaveAttribute('href', '/orders/31');
        expect(
            table.queryByRole('button', { name: /Delete/ }),
        ).not.toBeInTheDocument();
        expect(
            screen.getByRole('combobox', { name: 'Status' }),
        ).toHaveTextContent('All orders');
    });

    it('reloads only her orders with the chosen status', async () => {
        const user = userEvent.setup();

        render(<ClientsShow {...props(amira, amirasOrders)} />);
        await user.click(screen.getByRole('combobox', { name: 'Status' }));
        await user.click(screen.getByRole('option', { name: 'Delivered' }));

        expect(inertiaMock.router.get).toHaveBeenCalledWith(
            '/clients/1',
            { status: 'delivered' },
            expect.objectContaining({
                only: ['orders', 'filters'],
                preserveState: true,
            }),
        );
    });

    it('tells a client with no orders, and a status with none', () => {
        const { unmount } = render(<ClientsShow {...props(ines, [])} />);

        expect(
            screen.getByText(
                'Record an order for Ines Mrad to follow it here.',
            ),
        ).toBeInTheDocument();
        expect(
            Array.from(document.querySelectorAll('dl > div')).map(
                (figure) => figure.textContent,
            ),
        ).toEqual(['Open orders0', 'Orders0', 'Last orderNo orders yet']);
        unmount();

        render(<ClientsShow {...props(amira, [], 'delivered')} />);

        expect(
            screen.getByText('Amira Ben Salah has no delivered orders.'),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Show all orders' }),
        ).toBeInTheDocument();
    });
});
