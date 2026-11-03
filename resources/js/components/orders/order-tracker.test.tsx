import { render, screen, within } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import { OrderProductCard } from '@/components/orders/order-product-card';
import { OrderTracker } from '@/components/orders/order-tracker';
import { linaTracker, sizeIds } from '@/test/fixtures';
import type {
    OrderTracker as OrderTrackerData,
    TrackerLine,
} from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function bar(): HTMLElement {
    return document.querySelector('[data-slot="delivery-bar"]') as HTMLElement;
}

describe('<OrderTracker> (INV-14, AC-1)', () => {
    it('reads "12 of 12 units delivered" with the bar all delivered', () => {
        const delivered: OrderTrackerData = {
            to_deliver: 0,
            delivered: 12,
            cancelled: 0,
            total: 12,
            status: 'delivered',
            status_label: 'Delivered',
            lines: [],
        };

        render(<OrderTracker tracker={delivered} />);

        expect(screen.getByText('12 of 12 units delivered')).toBeVisible();
        expect(
            screen.getByText('Delivered', { selector: '[data-status]' }),
        ).toBeInTheDocument();
        expect(
            Array.from(bar().children).map(
                (part) => (part as HTMLElement).style.width,
            ),
        ).toEqual(['100%', '0%', '0%']);
        expect(bar()).toHaveAttribute('aria-hidden', 'true');
    });

    it('reads "0 of 2 units delivered · 2 cancelled" with the bar all hatched', () => {
        const cancelled: OrderTrackerData = {
            to_deliver: 0,
            delivered: 0,
            cancelled: 2,
            total: 2,
            status: 'cancelled',
            status_label: 'Cancelled',
            lines: [],
        };

        render(<OrderTracker tracker={cancelled} />);

        expect(
            screen.getByText('0 of 2 units delivered · 2 cancelled'),
        ).toBeVisible();
        expect(
            Array.from(bar().children).map(
                (part) => (part as HTMLElement).style.width,
            ),
        ).toEqual(['0%', '0%', '100%']);
        expect(
            within(screen.getByRole('list', { name: 'Units of this order' }))
                .getAllByRole('listitem')
                .map((tile) => tile.getAttribute('aria-label')),
        ).toEqual([
            'To deliver, 0 units',
            'Delivered, 0 units',
            'Cancelled, 2 units',
            'Total, 2 units',
        ]);
    });
});

describe('<OrderProductCard> (INV-14, AC-2)', () => {
    it('gives a "One size" product colour rows only', () => {
        const tote: TrackerLine = {
            id: 9,
            product: {
                id: 4,
                sku: 'PRD-TOT-004',
                name: 'Leather Tote',
                status: 'active',
                deleted: false,
            },
            one_size: true,
            to_deliver: 12,
            delivered: 0,
            cancelled: 0,
            total: 12,
            status: 'not_started',
            status_label: 'Not started',
            colours: [
                {
                    colour: {
                        id: 4,
                        code: 'CAM',
                        name: 'Camel',
                        swatch: '#B98A57',
                    },
                    to_deliver: 12,
                    delivered: 0,
                    cancelled: 0,
                    total: 12,
                    rows: [
                        {
                            size: { id: sizeIds.oneSize, label: 'One size' },
                            to_deliver: 12,
                            delivered: 0,
                            cancelled: 0,
                            total: 12,
                            on_shelf: 4,
                            can_deliver: 4,
                        },
                    ],
                },
            ],
        };

        render(<OrderProductCard line={tote} onOpen={vi.fn()} />);

        const rows = within(
            screen.getByRole('table', {
                name: 'Leather Tote by colour and size',
            }),
        )
            .getAllByRole('row')
            .slice(1)
            .map((row) => [
                within(row).getByRole('rowheader').textContent,
                ...within(row)
                    .getAllByRole('cell')
                    .map((cell) => cell.textContent),
            ]);

        expect(rows).toEqual([
            ['Camel', '12', '0', '0', '12', '', '4'],
            ['Total', '12', '0', '0', '12', '', ''],
        ]);
    });

    it('greys zeros and writes a shelf of 0 as the amber word Out', () => {
        render(
            <OrderProductCard line={linaTracker.lines[0]} onOpen={vi.fn()} />,
        );

        const out = screen.getByText('Out');

        expect(out).toHaveClass('text-amber-700');
        expect(screen.getAllByText('0')[0]).toHaveClass(
            'text-muted-foreground',
        );
        expect(
            screen.getByRole('region', {
                name: 'Denim Jacket by colour and size',
            }),
        ).toHaveAttribute('tabindex', '0');
    });
});
