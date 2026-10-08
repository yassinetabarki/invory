import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { TopProductsCard } from '@/components/dashboard/top-products-card';
import { resetInertiaMock } from '@/test/inertia';
import { topByRevenue, topByUnits } from '@/test/fixtures/dashboard';
import type { TopProduct } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderCard(
    overrides: {
        byUnits?: TopProduct[];
        byRevenue?: TopProduct[];
        periodLabel?: string;
    } = {},
) {
    return render(
        <TopProductsCard
            byUnits={overrides.byUnits ?? topByUnits}
            byRevenue={overrides.byRevenue ?? topByRevenue}
            periodLabel={overrides.periodLabel ?? 'Last 30 days'}
        />,
    );
}

function rows(): HTMLElement[] {
    return within(
        screen.getByRole('list', { name: 'Top selling products' }),
    ).getAllByRole('listitem');
}

function names(): (string | null)[] {
    return rows().map(
        (row) => within(row).getByRole('link').textContent ?? null,
    );
}

/**
 * The CSS width of the filled part of each bar, as the DOM holds it. jsdom
 * normalises the number ("100.00%" reads back as "100%", "59.10%" as "59.1%");
 * a browser reads the same CSS value.
 */
function barWidths(): string[] {
    return rows().map(
        (row) =>
            row.querySelector<HTMLElement>('[data-slot="bar"] > span')?.style
                .width ?? '',
    );
}

describe('<TopProductsCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('ranked by units (default)', () => {
        it('lists the server ranking in order with rank, SKU and values', () => {
            renderCard();

            expect(
                screen.getByRole('heading', {
                    level: 2,
                    name: 'Top selling products',
                }),
            ).toBeInTheDocument();
            expect(
                screen.getByText('Last 30 days, ranked by units sold'),
            ).toBeVisible();
            expect(names()).toEqual([
                'Classic White T-shirt',
                'Denim Jeans 5-pocket',
                'Denim Jacket',
                'Leather Belt',
                'Leather Tote',
            ]);

            const first = rows()[0];

            expect(within(first).getByText('1')).toBeInTheDocument();
            expect(within(first).getByText('PRD-TSH-002')).toHaveClass(
                'font-mono',
            );
            expect(within(first).getByText('142 units')).toBeInTheDocument();
            expect(within(first).getByText('€3,408.00')).toBeInTheDocument();
            expect(within(rows()[4]).getByText('5')).toBeInTheDocument();
        });

        it('links each product to its page', () => {
            renderCard();

            expect(
                screen.getByRole('link', { name: 'Denim Jacket' }),
            ).toHaveAttribute('href', '/products/1');
        });

        it('sets each bar to the percent_of_top string from the server', () => {
            renderCard();

            expect(barWidths()).toEqual([
                '100%',
                '45.07%',
                '26.76%',
                '21.83%',
                '8.45%',
            ]);
        });

        it('hides the bars from assistive technology and fills them with the primary colour', () => {
            renderCard();

            const bar = rows()[0].querySelector('[data-slot="bar"]');

            expect(bar).toHaveAttribute('aria-hidden', 'true');
            expect(bar).toHaveClass('bg-bar-track');
            expect(bar?.firstElementChild).toHaveClass('bg-primary');
        });

        it('says "1 unit" for a single unit', () => {
            renderCard({
                byUnits: [{ ...topByUnits[0], units_sold: 1 }],
            });

            expect(screen.getByText('1 unit')).toBeInTheDocument();
        });
    });

    describe('Units / Revenue switch', () => {
        it('starts on Units, pressed, with Revenue not pressed', () => {
            renderCard();

            expect(
                screen.getByRole('button', { name: 'Units', pressed: true }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Revenue', pressed: false }),
            ).toBeInTheDocument();
        });

        it('switches to the revenue ranking: order, values, bars and subtitle', async () => {
            const user = userEvent.setup();

            renderCard();

            await user.click(screen.getByRole('button', { name: 'Revenue' }));

            expect(
                screen.getByRole('button', { name: 'Revenue', pressed: true }),
            ).toBeInTheDocument();
            expect(
                screen.getByRole('button', { name: 'Units', pressed: false }),
            ).toBeInTheDocument();
            expect(
                screen.getByText('Last 30 days, ranked by revenue'),
            ).toBeVisible();
            expect(names()).toEqual([
                'Denim Jeans 5-pocket',
                'Classic White T-shirt',
                'Denim Jacket',
                'Leather Tote',
                'Leather Belt',
            ]);
            expect(barWidths()).toEqual([
                '100%',
                '67.41%',
                '66.89%',
                '59.1%',
                '27.59%',
            ]);

            // Main and sub values swap: revenue first, units under it.
            const [main, sub] = rows()[0].querySelectorAll(
                'span.flex-col:last-child > span',
            );

            expect(main).toHaveTextContent('€5,056.00');
            expect(sub).toHaveTextContent('64 units');
        });

        it('switches back to units', async () => {
            const user = userEvent.setup();

            renderCard();

            await user.click(screen.getByRole('button', { name: 'Revenue' }));
            await user.click(screen.getByRole('button', { name: 'Units' }));

            expect(names()[0]).toBe('Classic White T-shirt');
            expect(barWidths()[1]).toBe('45.07%');
        });

        it('is a labelled group of buttons', () => {
            renderCard();

            expect(
                within(
                    screen.getByRole('group', { name: 'Rank by' }),
                ).getAllByRole('button'),
            ).toHaveLength(2);
        });
    });

    describe('period and empty state', () => {
        it('names the period it was given', () => {
            renderCard({ periodLabel: 'This year' });

            expect(
                screen.getByText('This year, ranked by units sold'),
            ).toBeVisible();
        });

        it('explains an empty period and offers no switch', () => {
            renderCard({ byUnits: [], byRevenue: [] });

            expect(screen.getByText(/No sales in this period/)).toBeVisible();
            expect(screen.queryByRole('list')).not.toBeInTheDocument();
            expect(
                screen.queryByRole('button', { name: 'Revenue' }),
            ).not.toBeInTheDocument();
        });
    });
});
