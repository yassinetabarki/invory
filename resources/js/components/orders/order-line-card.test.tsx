import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrderLineCard } from '@/components/orders/order-line-card';
import { sizeIds, sizedJacketLine, withOneSize } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';
import type { OrderLine, OrderLineSize, OrderProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const jacket: OrderProduct = {
    id: 1,
    sku: 'PRD-JKT-001',
    name: 'Denim Jacket',
    status: 'active',
    deleted: false,
};

function jacketLine(overrides: Partial<OrderLine> = {}): OrderLine {
    return withOneSize({
        id: 70,
        product: jacket,
        quantity: 50,
        to_start: 20,
        in_cutting: 20,
        ready_to_ship: 10,
        shipped: 0,
        started: 30,
        status: 'in_progress',
        status_label: 'In progress',
        ...overrides,
    });
}

function renderCard(line: OrderLine) {
    const onMove = vi.fn();

    render(<OrderLineCard line={line} onMove={onMove} />);

    return { onMove, user: userEvent.setup() };
}

describe('<OrderLineCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the status as a word with a hidden icon and opens the right move', async () => {
        const { onMove, user } = renderCard(jacketLine());

        const status = screen.getByText('In progress');

        expect(status.querySelector('svg')).toHaveAttribute(
            'aria-hidden',
            'true',
        );

        await user.click(screen.getByRole('button', { name: 'Ship' }));

        expect(onMove).toHaveBeenCalledExactlyOnceWith(jacketLine(), 'ship');
    });

    it('disables a button whose stage holds nothing', () => {
        renderCard(
            jacketLine({
                to_start: 50,
                in_cutting: 0,
                ready_to_ship: 0,
                started: 0,
                status: 'not_started',
                status_label: 'Not started',
            }),
        );

        expect(
            screen.getByRole('button', { name: 'Start cutting' }),
        ).toBeEnabled();
        expect(
            screen.getByRole('button', { name: 'Mark ready' }),
        ).toBeDisabled();
        expect(screen.getByRole('button', { name: 'Ship' })).toBeDisabled();
        for (const back of screen.getAllByRole('button', {
            name: 'Move back',
        })) {
            expect(back).toBeDisabled();
        }
    });

    it('disables the three forward buttons on a fully shipped line', () => {
        renderCard(
            jacketLine({
                to_start: 0,
                in_cutting: 0,
                ready_to_ship: 0,
                shipped: 50,
                started: 50,
                status: 'shipped',
                status_label: 'Shipped',
            }),
        );

        expect(
            screen.getByText('Shipped', { selector: '[data-status]' }),
        ).toBeVisible();
        for (const name of ['Start cutting', 'Mark ready', 'Ship']) {
            expect(screen.getByRole('button', { name })).toBeDisabled();
        }
        expect(
            screen.getAllByRole('button', { name: 'Move back' }).at(-1),
        ).toBeEnabled();
    });

    it('keeps a deleted product from starting cutting but lets its units move on and back', () => {
        renderCard(jacketLine({ product: { ...jacket, deleted: true } }));

        const startCutting = screen.getByRole('button', {
            name: 'Start cutting',
        });

        expect(startCutting).toBeDisabled();
        expect(startCutting).toHaveAccessibleDescription(
            "Denim Jacket was deleted, so it can't be cut.",
        );
        expect(
            screen.getByRole('button', { name: 'Mark ready' }),
        ).toBeEnabled();
        expect(screen.getByRole('button', { name: 'Ship' })).toBeEnabled();
        expect(
            screen.getAllByRole('button', { name: 'Move back' })[0],
        ).toBeEnabled();
    });

    describe('by size', () => {
        const sizeRow = (
            id: number,
            label: string,
            quantity: number,
            figures: Partial<OrderLineSize> = {},
        ): OrderLineSize => ({
            size: { id, label },
            quantity,
            to_start: quantity,
            in_cutting: 0,
            ready_to_ship: 0,
            shipped: 0,
            started: 0,
            ...figures,
        });

        const sOnlyAndXl: OrderLine = {
            ...sizedJacketLine,
            quantity: 15,
            to_start: 15,
            sizes: [sizeRow(sizeIds.S, 'S', 10), sizeRow(sizeIds.XL, 'XL', 5)],
        };

        // S 10 (4 to start, 4 in cutting, 2 shipped) and M 20 (10 to start, 5 ready, 5 shipped).
        const underway: OrderLine = {
            ...sizedJacketLine,
            quantity: 30,
            to_start: 14,
            in_cutting: 4,
            ready_to_ship: 5,
            shipped: 7,
            started: 16,
            status: 'in_progress',
            status_label: 'In progress',
            sizes: [
                sizeRow(sizeIds.S, 'S', 10, {
                    to_start: 4,
                    in_cutting: 4,
                    shipped: 2,
                }),
                sizeRow(sizeIds.M, 'M', 20, {
                    to_start: 10,
                    ready_to_ship: 5,
                    shipped: 5,
                }),
            ],
        };

        const tote = withOneSize(
            {
                ...jacketLine(),
                id: 71,
                product: {
                    id: 3,
                    sku: 'PRD-TOT-003',
                    name: 'Leather Tote',
                    status: 'active',
                    deleted: false,
                },
                quantity: 12,
                to_start: 12,
                in_cutting: 0,
                ready_to_ship: 0,
                started: 0,
            },
            'one_size',
        );

        it.each([
            ['S 10 · M 20 · L 15 · XL 5', sizedJacketLine],
            ['S 10 · XL 5', sOnlyAndXl],
            ['One size 50', jacketLine()],
            [null, tote],
        ])('writes %j under the line heading', (expected, line) => {
            renderCard(line);

            const heading = screen.getByRole('heading', { level: 3 });

            expect(heading.nextElementSibling?.textContent ?? null).toBe(
                expected,
            );
        });

        it('shows a table with a row per size and the buttons in the Total row only', async () => {
            const { onMove, user } = renderCard(sizedJacketLine);

            // From 1024 px (lg) a table, named after the product; below it the cards (F2, F7).
            const table = screen.getByRole('table', {
                name: 'Denim Jacket: units by size and stage',
            });

            expect(table).toHaveClass('lg:table');
            expect(
                within(table)
                    .getAllByRole('columnheader')
                    .map((header) => header.textContent),
            ).toEqual([
                'Size',
                'To start',
                'In cutting',
                'Ready to ship',
                'Shipped',
            ]);

            const rows = within(table).getAllByRole('row').slice(1);

            expect(
                rows.map((row) =>
                    [
                        ...within(row).queryAllByRole('rowheader'),
                        ...within(row).queryAllByRole('cell'),
                    ].map((cell) => cell.textContent),
                ),
            ).toEqual([
                ['S', '10', '0', '0', '0'],
                ['M', '20', '0', '0', '0'],
                ['L', '15', '0', '0', '0'],
                ['XL', '5', '0', '0', '0'],
                [
                    'Total',
                    '50Start cutting',
                    '0Mark readyMove back',
                    '0ShipMove back',
                    '0Move back',
                ],
            ]);
            for (const row of rows.slice(0, 4)) {
                expect(within(row).queryByRole('button')).toBeNull();
            }

            await user.click(
                within(rows[4]).getByRole('button', { name: 'Start cutting' }),
            );

            expect(onMove).toHaveBeenCalledExactlyOnceWith(
                sizedJacketLine,
                'start_cutting',
            );
        });

        it('leaves a one-size product without a table', () => {
            renderCard(tote);

            expect(screen.queryByRole('table')).toBeNull();
            expect(
                screen.getByRole('button', { name: 'Start cutting' }),
            ).toBeEnabled();
        });

        it('has a row only for the sizes ordered', () => {
            renderCard(sOnlyAndXl);

            expect(
                within(screen.getByRole('table'))
                    .getAllByRole('rowheader')
                    .map((header) => header.textContent),
            ).toEqual(['S', 'XL', 'Total']);
        });

        it("takes the status and the shipped count from the line's own figures", () => {
            renderCard(underway);

            expect(
                screen.getByText('In progress', { selector: '[data-status]' }),
            ).toBeVisible();
            expect(screen.getByText('7 of 30 shipped')).toBeVisible();

            const rows = within(screen.getByRole('table')).getAllByRole('row');

            expect(
                within(rows[rows.length - 1])
                    .getAllByRole('cell')
                    .map((cell) => cell.textContent),
            ).toEqual([
                '14Start cutting',
                '4Mark readyMove back',
                '5ShipMove back',
                '7Move back',
            ]);
        });

        it('stacks on a phone: the Total card with its buttons first, then each size as labelled figures', () => {
            renderCard(underway);

            const total = screen.getByRole('list', { name: 'Total' });
            const sizes = screen.getByRole('list', { name: 'Sizes' });

            expect(total.closest('.lg\\:hidden')).not.toBeNull();
            expect(
                total.compareDocumentPosition(sizes) &
                    Node.DOCUMENT_POSITION_FOLLOWING,
            ).toBeTruthy();
            expect(
                within(total)
                    .getAllByRole('listitem')
                    .map((item) => item.firstElementChild?.textContent),
            ).toEqual([
                'To start 14 units',
                'In cutting 4 units',
                'Ready to ship 5 units',
                'Shipped 7 units',
            ]);
            expect(
                within(total).getByRole('button', { name: 'Start cutting' }),
            ).toBeEnabled();

            const [first, second] = within(sizes).getAllByRole('listitem');

            expect(first).toHaveTextContent(
                'S · To start 4 · In cutting 4 · Ready to ship 0 · Shipped 2',
            );
            expect(second).toHaveTextContent(
                'M · To start 10 · In cutting 0 · Ready to ship 5 · Shipped 5',
            );
        });
    });
});
