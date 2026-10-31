import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    FinishedStockFigure,
    StockLoadError,
} from '@/components/products/finished-stock-figure';
import {
    denimStockByColour,
    sizeListSToXl,
    stockByColour,
} from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<FinishedStockFigure>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the total, then every colour in list order with its count in bold, a 0 in grey, and the link to the card', () => {
        render(<FinishedStockFigure stock={denimStockByColour} />);

        const figure = screen.getByRole('region', { name: 'Finished stock' });
        const big = within(figure).getByText(/units?$/).parentElement;

        // A real space between the count and its word.
        expect(big?.textContent).toBe('10 units');

        // Screen readers hear one sentence; the visual line is hidden from them.
        expect(
            within(figure).getByText(/^In stock per colour:/),
        ).toHaveTextContent(
            'In stock per colour: Black 4, Indigo 6, Light wash 0',
        );
        expect(within(figure).getByText(/^In stock per colour:/)).toHaveClass(
            'sr-only',
        );

        const line = figure.querySelector('p[aria-hidden="true"]');
        const counts = Array.from(line?.querySelectorAll('b') ?? []);

        expect(line).toHaveTextContent('Black 4 · Indigo 6 · Light wash 0');
        expect(counts.map((count) => count.textContent)).toEqual([
            '4',
            '6',
            '0',
        ]);
        // A colour at 0 is in the grey text colour, its "0" still shown; the others are bold.
        expect(
            counts.map((count) => count.className.includes('font-semibold')),
        ).toEqual([true, true, false]);
        expect(
            counts.map((count) =>
                count.parentElement?.classList.contains(
                    'text-muted-foreground',
                ),
            ),
        ).toEqual([false, false, true]);
        expect(
            Array.from(
                line?.querySelectorAll<HTMLElement>(
                    '[data-slot="colour-swatch"]',
                ) ?? [],
            ).map((swatch) => swatch.style.backgroundColor),
        ).toEqual([
            'rgb(31, 31, 35)',
            'rgb(62, 76, 126)',
            'rgb(157, 179, 209)',
        ]);

        expect(
            within(figure).getByRole('link', { name: 'By size and colour' }),
        ).toHaveAttribute('href', '#sizes-colours');
        // Stock is changed in the Sizes & colours card now.
        expect(
            screen.queryByRole('button', { name: 'Update stock' }),
        ).toBeNull();
    });

    it('shows "0 units" with no colour line, and keeps the link', () => {
        render(
            <FinishedStockFigure
                stock={stockByColour(sizeListSToXl, [
                    ['Black', []],
                    ['Indigo', []],
                ])}
            />,
        );

        const figure = screen.getByRole('region', { name: 'Finished stock' });

        expect(
            within(figure).getByText(/units?$/).parentElement,
        ).toHaveTextContent('0 units');
        expect(within(figure).queryByText(/^In stock per colour:/)).toBeNull();
        expect(figure.querySelectorAll('[aria-hidden="true"]')).toHaveLength(0);
        expect(
            within(figure).getByRole('link', { name: 'By size and colour' }),
        ).toBeInTheDocument();
    });

    it('keeps the focus on Try again while the stock reloads, and a second click does not reload again', async () => {
        const user = userEvent.setup();
        const { rerender } = render(<StockLoadError reloading={false} />);
        const retry = screen.getByRole('button', { name: 'Try again' });

        await user.click(retry);
        expect(inertiaMock.router.reload).toHaveBeenCalledTimes(1);

        rerender(<StockLoadError reloading />);

        expect(retry).toHaveAttribute('aria-disabled', 'true');
        expect(retry).not.toBeDisabled();
        expect(retry).toHaveFocus();

        await user.click(retry);
        expect(inertiaMock.router.reload).toHaveBeenCalledTimes(1);
    });
});
