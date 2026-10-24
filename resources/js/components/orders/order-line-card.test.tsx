import { act, render, screen, within } from '@testing-library/react';
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

        // The jacket after cutting S 4 · M 10: Total 36 · 14 · 0 · 0.
        const cutJacket: OrderLine = {
            ...sizedJacketLine,
            to_start: 36,
            in_cutting: 14,
            started: 14,
            status: 'in_progress',
            status_label: 'In progress',
            sizes: [
                sizeRow(sizeIds.S, 'S', 10, { to_start: 6, in_cutting: 4 }),
                sizeRow(sizeIds.M, 'M', 20, { to_start: 10, in_cutting: 10 }),
                sizeRow(sizeIds.L, 'L', 15),
                sizeRow(sizeIds.XL, 'XL', 5),
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
            [
                'four chips and the sizes spoken once',
                sizedJacketLine,
                [
                    ['S', '10'],
                    ['M', '20'],
                    ['L', '15'],
                    ['XL', '5'],
                ],
                'S 10, M 20, L 15, XL 5',
            ],
            [
                'no chip for a size at 0',
                sOnlyAndXl,
                [
                    ['S', '10'],
                    ['XL', '5'],
                ],
                'S 10, XL 5',
            ],
            [
                'one chip on a line recorded before sizes',
                jacketLine(),
                [['One size', '50']],
                'One size 50',
            ],
            ['no chip for a "One size" product', tote, [], null],
        ])('AC-1: %s', (_name, line, chips, spoken) => {
            renderCard(line);

            const heading = screen.getByRole('heading', { level: 3 });

            expect(heading).toHaveTextContent(
                `${line.product.name} · ${line.product.sku} · ${line.quantity} units`,
            );
            // The SKU is in the number font.
            expect(screen.getByText(line.product.sku)).toHaveClass('font-mono');

            const row = heading.parentElement?.querySelector(
                '[aria-hidden="true"]',
            );

            expect(
                Array.from(row?.children ?? []).map((chip) => [
                    chip.children[0].textContent,
                    chip.children[1].textContent,
                ]),
            ).toEqual(chips);

            if (spoken === null) {
                expect(row).toBeNull();
                return;
            }

            // Heard once, as one line, not as one chip per size.
            expect(screen.getAllByText(spoken)).toHaveLength(1);
            expect(screen.getByText(spoken)).toHaveClass('sr-only');
        });

        // The text of each cell of one line of buttons, in order.
        const lineOf = (scope: HTMLElement, line: 'forward' | 'back') =>
            Array.from(scope.querySelectorAll(`[data-line="${line}"]`)).map(
                (cell) => cell.textContent,
            );

        it('the table holds only figures; the buttons sit in two rows under the Total', async () => {
            const { onMove, user } = renderCard(sizedJacketLine);

            // From 1024 px (lg) a table, named after the product; below it the cards (F2, F7).
            const table = screen.getByRole('table', {
                name: 'Denim Jacket: units by size and stage',
            });
            const wide = table.parentElement as HTMLElement;

            expect(wide).toHaveClass('lg:block');
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
                ['Total', '50', '0', '0', '0'],
            ]);
            expect(within(table).queryByRole('button')).toBeNull();

            // The first row: forward moves; the second: the Move backs (nothing under To start).
            expect(lineOf(wide, 'forward')).toEqual([
                '',
                'Start cutting',
                'Mark ready',
                'Ship',
                '',
            ]);
            expect(lineOf(wide, 'back')).toEqual([
                '',
                '',
                'Move back',
                'Move back',
                'Move back',
            ]);

            const backs = within(wide).getAllByRole('button', {
                name: 'Move back',
            });

            // Nothing shipped yet: the Shipped one can't be clicked, and no text says so.
            expect(backs[2]).toBeDisabled();
            expect(screen.queryByText('Nothing to move')).toBeNull();
            // Each Move back is described by its column's heading.
            expect(backs).toHaveLength(3);
            ['In cutting', 'Ready to ship', 'Shipped'].forEach(
                (name, index) => {
                    expect(backs[index]).toHaveAccessibleDescription(name);
                },
            );

            await user.click(
                within(wide).getByRole('button', { name: 'Start cutting' }),
            );

            expect(onMove).toHaveBeenCalledExactlyOnceWith(
                sizedJacketLine,
                'start_cutting',
            );
        });

        it('AC-2: Tab goes through the first row, then the second, skipping buttons that cannot be clicked', async () => {
            const { user } = renderCard(cutJacket);

            // The table's buttons come first in the page; the boxes for a phone follow.
            const wide = within(
                screen.getByRole('table').parentElement as HTMLElement,
            );

            act(() =>
                screen.getByRole('link', { name: 'Denim Jacket' }).focus(),
            );
            await user.tab();
            expect(
                wide.getByRole('button', { name: 'Start cutting' }),
            ).toHaveFocus();
            await user.tab();
            expect(
                wide.getByRole('button', { name: 'Mark ready' }),
            ).toHaveFocus();
            // Ship (0 ready to ship) is skipped; the second row follows with In cutting's Move back.
            await user.tab();
            expect(
                wide.getAllByRole('button', { name: 'Move back' })[0],
            ).toHaveFocus();
        });

        it('AC-4: the table sits in a frame, with a grey band, figures in the number font and grey zeros', () => {
            renderCard(cutJacket);

            const table = screen.getByRole('table');
            const rows = within(table).getAllByRole('row');
            const total = rows[rows.length - 1];
            const figureCells = rows
                .slice(1)
                .flatMap((row) => within(row).getAllByRole('cell'));

            // Frame and band (jsdom has no CSS: the classes are what is checked).
            expect(table.parentElement).toHaveClass('rounded-lg', 'border');
            expect(rows[0]).toHaveClass('bg-muted/60');
            // Every figure, the Total's included, in the number font.
            expect(figureCells).toHaveLength(20);
            for (const cell of figureCells) {
                expect(cell).toHaveClass('font-mono');
            }
            // Exactly the zeros are grey, in the size rows and in the Total row.
            for (const cell of figureCells) {
                expect(cell.classList.contains('text-muted-foreground')).toBe(
                    cell.textContent === '0',
                );
            }
            expect(
                within(total)
                    .getAllByRole('cell')
                    .map((cell) => [
                        cell.textContent,
                        cell.classList.contains('text-muted-foreground'),
                    ]),
            ).toEqual([
                ['36', false],
                ['14', false],
                ['0', true],
                ['0', true],
            ]);
            // The Total row is shaded with a heavier line above it; the labels stay in the body font.
            expect(total).toHaveClass('bg-muted/60');
            for (const cell of [
                ...within(total).getAllByRole('cell'),
                within(total).getByRole('rowheader'),
            ]) {
                expect(cell).toHaveClass('border-t-2');
            }
            const rowHeaders = within(table).getAllByRole('rowheader');
            const totalHeader = rowHeaders[rowHeaders.length - 1];
            for (const header of rowHeaders) {
                expect(header).not.toHaveClass('font-mono');
            }
            // Size labels are semibold like the size chips; the Total stays bold through its row.
            for (const header of rowHeaders.slice(0, -1)) {
                expect(header).toHaveClass('font-semibold');
                expect(header).not.toHaveClass('font-bold');
            }
            expect(totalHeader).toHaveTextContent('Total');
            expect(total).toHaveClass('font-bold');
            expect(totalHeader).not.toHaveClass('font-semibold');
            // The Size column's cells carry the figure cells' horizontal padding, so none touches the frame.
            const sizeCells = [
                within(rows[0]).getAllByRole('columnheader')[0],
                ...rowHeaders,
            ];
            for (const cell of [...sizeCells, ...figureCells]) {
                expect(cell).toHaveClass('px-3');
            }
        });

        it('AC-3: across the boxes of a one-size line, the forward buttons share one line and the Move backs the line below', () => {
            renderCard({
                ...tote,
                quantity: 12,
                to_start: 8,
                in_cutting: 4,
                started: 4,
            });

            const boxes = screen.getByRole('list');

            expect(lineOf(boxes, 'forward')).toEqual([
                'Start cutting',
                'Mark ready',
                'Ship',
                '',
            ]);
            expect(lineOf(boxes, 'back')).toEqual([
                '',
                'Move back',
                'Move back',
                'Move back',
            ]);
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
            ).toEqual(['14', '4', '5', '7']);
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
            // The same two lines as the table's rows: forward moves, then the Move backs.
            expect(lineOf(total, 'forward')).toEqual([
                'Start cutting',
                'Mark ready',
                'Ship',
                '',
            ]);
            expect(lineOf(total, 'back')).toEqual([
                '',
                'Move back',
                'Move back',
                'Move back',
            ]);

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
