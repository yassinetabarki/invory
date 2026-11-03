import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { TooltipProvider } from '@/components/ui/tooltip';
import Progress from '@/pages/progress/index';
import { colourNamed } from '@/test/fixtures/colours';
import {
    b0148,
    cuttingView,
    finishingView,
    progressProps,
    sewing,
    sewingView,
} from '@/test/fixtures/progress';
import { jacketRef } from '@/test/fixtures/restocks';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { ProgressIndexProps } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const device = vi.hoisted(() => ({ mobile: false }));
vi.mock('@/hooks/use-mobile', () => ({ useIsMobile: () => device.mobile }));

function renderPage(overrides: Partial<ProgressIndexProps> = {}) {
    const props = { ...progressProps, ...overrides };
    setPageProps({ ...props }, { url: '/progress' });

    return {
        user: userEvent.setup(),
        ...render(
            <TooltipProvider>
                <Progress {...props} />
            </TooltipProvider>,
        ),
    };
}

function cellsOf(table: HTMLElement): string[][] {
    return within(table)
        .getAllByRole('row')
        .slice(1)
        .map((tr) =>
            Array.from(tr.querySelectorAll('th,td')).map(
                (cell) => cell.textContent ?? '',
            ),
        );
}

describe('progress/index, all phases (INV-5, AC-3)', () => {
    beforeEach(() => {
        resetInertiaMock();
        device.mobile = false;
    });

    it('shows the header, the tiles and a card per product with its table', () => {
        renderPage();

        expect(
            screen.getByRole('heading', { name: 'Progress', level: 1 }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The batches you are making for your shelf, phase by phase, in their colour and size.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Production phases' }),
        ).toHaveAttribute('href', '/settings/production-phases');
        // INV-15 (AC-3): the Scan page.
        expect(
            screen.getByRole('link', { name: 'Scan a ticket' }),
        ).toHaveAttribute('href', '/scan');

        const tiles = within(screen.getByRole('group', { name: 'Phases' }));
        expect(
            tiles.getAllByRole('link').map((tile) => tile.textContent),
        ).toEqual([
            'All phases26 units',
            'In cutting7 units',
            'Sewing15 units',
            'Finishing4 units',
        ]);
        expect(tiles.getByRole('link', { current: 'page' })).toHaveTextContent(
            'All phases',
        );
        expect(tiles.getByRole('link', { name: /^Sewing/ })).toHaveAttribute(
            'href',
            '/progress?phase=11',
        );

        const jacket = within(
            screen.getByRole('region', { name: 'Denim Jacket' }),
        );
        expect(jacket.getByText(/21 units in 6 batches/)).toBeInTheDocument();
        expect(
            jacket.getByRole('button', {
                name: 'Start a batch of Denim Jacket',
            }),
        ).toBeInTheDocument();

        const table = jacket.getByRole('table', {
            name: 'Denim Jacket by phase',
        });
        expect(
            within(table)
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual([
            'Colour · size',
            'In cutting',
            'Sewing',
            'Finishing',
            'In production',
            'On the shelf now',
        ]);
        expect(cellsOf(table)).toEqual([
            ['Indigo', '4', '10', '4', '18', ''],
            ['S', '0', '4', '0', '4', '2'],
            ['M', '4', '6', '4', '14', 'Out'],
            ['Light wash', '3', '0', '0', '3', ''],
            ['L', '2', '0', '0', '2', 'Out'],
            ['XL', '1', '0', '0', '1', 'Out'],
            ['Total', '7', '10', '4', '21', ''],
        ]);
    });

    it('shows the filtered line with Clear filters, and filters through the address', async () => {
        const { user } = renderPage({
            filters: {
                phase: null,
                product: null,
                colour: colourNamed('Indigo').id,
            },
            all_units: 23,
        });

        expect(
            screen.getByText('Showing Indigo · 23 of 26 units'),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Clear filters' }));

        expect(inertiaMock.router.get).toHaveBeenCalledWith(
            '/progress',
            {},
            expect.objectContaining({ preserveState: true }),
        );
    });

    it('says when nothing is in production, and when no batch matches', () => {
        const { unmount } = renderPage({
            has_batches: false,
            products: { ...progressProps.products!, data: [] },
        });

        expect(screen.getByText('Nothing in production')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Start a batch to make products for your shelf. They are added to your finished stock when you put them in stock.',
            ),
        ).toBeInTheDocument();

        unmount();
        renderPage({
            filters: {
                phase: null,
                product: null,
                colour: colourNamed('Black').id,
            },
            products: { ...progressProps.products!, data: [] },
        });

        expect(
            screen.getByText('No batch matches these filters.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Try another product or colour, or clear the filters.',
            ),
        ).toBeInTheDocument();
    });

    it('shows placeholders while the figures load', () => {
        const props = { ...progressProps };
        setPageProps(
            { path: props.path, filters: props.filters },
            { url: '/progress' },
        );

        render(
            <TooltipProvider>
                <Progress
                    {...props}
                    tiles={undefined as never}
                    all_units={undefined as never}
                />
            </TooltipProvider>,
        );

        expect(screen.getByLabelText('Loading progress')).toBeInTheDocument();
    });

    it('reads a line per size on a phone, the Total first', () => {
        device.mobile = true;
        renderPage();

        const jacket = within(
            screen.getByRole('region', { name: 'Denim Jacket' }),
        );
        const lines = jacket
            .getAllByRole('listitem')
            .map((li) => li.textContent);

        expect(lines[0]).toBe('Total · In cutting 7 · Sewing 10 · Finishing 4');
        expect(lines).toContain(
            'M · In cutting 4 · Sewing 6 · Finishing 4 · On the shelf Out',
        );
        expect(
            screen.getByRole('button', { name: 'Filters' }),
        ).toBeInTheDocument();
    });
});

describe('progress/index, one phase (INV-5, AC-4)', () => {
    beforeEach(() => {
        resetInertiaMock();
        device.mobile = false;
    });

    it('shows the heading, the path and a row per batch with its buttons', async () => {
        const { user } = renderPage({
            filters: { phase: sewing.phase, product: null, colour: null },
            products: null,
            phase_view: sewingView,
        });

        expect(
            screen.getByRole('heading', { name: 'Sewing', level: 2 }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                '15 units · 3 batches · 2 products · oldest since 26 Oct 2026',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByText('From In cutting. Next: Finishing.'),
        ).toBeInTheDocument();

        const table = screen.getByRole('table', { name: 'In Sewing' });
        expect(cellsOf(table).map((cells) => cells.slice(0, 5))).toEqual([
            [
                '26 Oct 20264 days',
                'B-0146',
                'Denim Jeans 5-pocketPRD-JNS-005',
                'Indigo· S',
                '5',
            ],
            [
                '27 Oct 20263 days',
                'B-0147',
                'Denim JacketPRD-JKT-001',
                'Indigo· S',
                '4',
            ],
            [
                '28 Oct 20262 days',
                'B-0149',
                'Denim JacketPRD-JKT-001',
                'Indigo· M',
                '6',
            ],
        ]);

        await user.click(
            screen.getByRole('button', {
                name: 'Move batch B-0146 Denim Jeans 5-pocket Indigo S to Finishing',
            }),
        );

        const dialog = within(
            screen.getByRole('dialog', {
                name: 'Move to Finishing · Denim Jeans 5-pocket',
            }),
        );
        expect(
            dialog.getByText('B-0146 · Indigo S · in Sewing: 5 units'),
        ).toBeInTheDocument();
        expect(
            dialog.getByLabelText('How many units?'),
        ).toHaveAccessibleDescription('Up to 5');

        await user.type(dialog.getByLabelText('How many units?'), '4');
        await user.click(dialog.getByRole('button', { name: 'Move units' }));

        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/progress/batches/146/moves',
            method: 'post',
            data: { from_phase: 11, to_phase: 12, units: '4' },
        });
    });

    it('offers Move back and, In cutting, Undo batch in the "⋯" menu', async () => {
        const { user } = renderPage({
            filters: { phase: 'cutting', product: null, colour: null },
            products: null,
            phase_view: cuttingView,
        });

        expect(screen.getByText('Next: Sewing.')).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: 'Move batch B-0150 Denim Jacket Indigo M to Sewing',
            }),
        ).toBeInTheDocument();

        await user.click(
            screen.getByRole('button', { name: 'More moves for batch B-0150' }),
        );
        await user.click(screen.getByRole('menuitem', { name: 'Undo batch' }));

        const confirm = within(
            screen.getByRole('alertdialog', { name: 'Undo batch B-0150?' }),
        );
        expect(
            confirm.getByRole('button', { name: 'Keep batch' }),
        ).toBeInTheDocument();
        expect(inertiaMock.http.requests[0].path).toBe(
            '/progress/batches/150/undo',
        );
    });

    it('prints a batch\'s ticket from the "⋯" menu in every phase (INV-15, AC-2)', async () => {
        const open = vi.spyOn(window, 'open').mockReturnValue({} as Window);

        for (const [view, phase, number, id] of [
            [cuttingView, 'cutting', 'B-0150', 150],
            [finishingView, 12, 'B-0148', 148],
        ] as const) {
            const { user, unmount } = renderPage({
                filters: { phase, product: null, colour: null },
                products: null,
                phase_view: view,
            });

            await user.click(
                screen.getByRole('button', {
                    name: `More moves for batch ${number}`,
                }),
            );
            await user.click(
                screen.getByRole('menuitem', {
                    name: `Print ticket ${number}`,
                }),
            );
            expect(decodeURIComponent(String(open.mock.lastCall?.[0]))).toBe(
                `/progress/tickets?batches[]=${id}`,
            );
            unmount();
        }

        open.mockRestore();
    });

    it('puts a batch in stock from the last phase, saying where the shelf goes', async () => {
        const { user } = renderPage({
            filters: { phase: 12, product: null, colour: null },
            products: null,
            phase_view: finishingView,
        });

        await user.click(
            screen.getByRole('button', { name: /^Put batch B-0148/ }),
        );

        const dialog = within(
            screen.getByRole('dialog', { name: 'Put in stock · Denim Jacket' }),
        );
        expect(
            dialog.getByText(
                'Adds to your finished stock: Indigo M goes from 0 to 4. Recorded as a restock "From production".',
            ),
        ).toBeInTheDocument();

        await user.type(dialog.getByLabelText('How many units?'), '3');
        expect(dialog.getByText(/goes from 0 to 3/)).toBeInTheDocument();

        await user.click(dialog.getByRole('button', { name: 'Put in stock' }));
        expect(inertiaMock.submissions[0]).toMatchObject({
            action: '/progress/batches/148/stock',
            data: { from_phase: 12, units: '3' },
        });
    });

    it("can't put a deleted product's batch in stock, and says why", () => {
        renderPage({
            filters: { phase: 12, product: null, colour: null },
            products: null,
            phase_view: {
                ...finishingView,
                batches: {
                    ...finishingView.batches,
                    data: [
                        { ...b0148, product: { ...jacketRef, deleted: true } },
                    ],
                },
            },
        });

        expect(screen.getByText('Denim Jacket (deleted)')).toBeInTheDocument();
        const button = screen.getByRole('button', {
            name: /^Put batch B-0148/,
        });
        expect(button).toHaveAttribute('aria-disabled', 'true');
        expect(button).toHaveAccessibleDescription(
            "Denim Jacket was deleted, so it can't be put in stock. Move the batch back to In cutting and undo it to return its materials.",
        );
    });

    it('says when a phase is empty, or nothing in it matches', () => {
        const empty = {
            ...sewingView,
            units: 0,
            batch_count: 0,
            product_count: 0,
            oldest_since: null,
            batches: { ...sewingView.batches, data: [] },
        };
        const { unmount } = renderPage({
            filters: { phase: sewing.phase, product: null, colour: null },
            products: null,
            phase_view: empty,
        });

        expect(screen.getByText('Nothing in Sewing')).toBeInTheDocument();
        expect(
            screen.getByText(
                'Batches come here from In cutting with Move to Sewing.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Show all phases' }),
        ).toBeInTheDocument();

        unmount();
        renderPage({
            filters: {
                phase: sewing.phase,
                product: null,
                colour: colourNamed('Black').id,
            },
            products: null,
            phase_view: empty,
        });

        expect(
            screen.getByText('Nothing in Sewing matches these filters.'),
        ).toBeInTheDocument();
    });
});
