import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { HistoryList } from '@/components/materials/history-list';
import { buttonHistory } from '@/test/fixtures';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { StockMovement } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const [priceChange, restock, correction, created] = buttonHistory;

function entry(overrides: Partial<StockMovement>): StockMovement {
    return { ...restock, ...overrides };
}

/** The `<li>` of the entry at `index`. */
function row(index: number): HTMLElement {
    return screen.getAllByRole('listitem')[index];
}

describe('<HistoryList>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists the entries in the order the server sent them (newest first)', () => {
        render(<HistoryList entries={buttonHistory} unit="pc" />);

        expect(screen.getAllByRole('listitem')).toHaveLength(4);
        expect(
            screen
                .getAllByRole('listitem')
                .map(
                    (item) =>
                        within(item).getByText(/^\d+ \w{3} \d{4}$/).textContent,
                ),
        ).toEqual(['2 Oct 2026', '2 Oct 2026', '14 Sep 2026', '1 Sep 2026']);
    });

    it('describes a restock: the quantity received, the price and the reference', () => {
        render(<HistoryList entries={[restock]} unit="pc" />);

        expect(within(row(0)).getByText('Restock')).toBeInTheDocument();
        expect(
            within(row(0)).getByText('+500 pc at €0.35 · PO-2291'),
        ).toBeInTheDocument();
        expect(within(row(0)).getByText('by Admin')).toBeInTheDocument();
        expect(within(row(0)).getByText('2 Oct 2026')).toBeInTheDocument();
    });

    it('leaves the reference out of a restock that has none', () => {
        render(
            <HistoryList
                entries={[entry({ reference: null, unit_price: '0.9500' })]}
                unit="pc"
            />,
        );

        expect(screen.getByText('+500 pc at €0.95')).toBeInTheDocument();
    });

    it('describes the opening stock of a created entry', () => {
        render(<HistoryList entries={[created]} unit="pc" />);

        expect(within(row(0)).getByText('Created')).toBeInTheDocument();
        expect(within(row(0)).getByText('600 pc at €0.32')).toBeInTheDocument();
    });

    it('describes a correction from before to after, with the reason', () => {
        render(<HistoryList entries={[correction]} unit="pc" />);

        expect(within(row(0)).getByText('Correction')).toBeInTheDocument();
        expect(
            within(row(0)).getByText('600 pc → 400 pc (Stock count)'),
        ).toBeInTheDocument();
    });

    it('leaves the parentheses out of a correction without a reason', () => {
        render(
            <HistoryList entries={[{ ...correction, note: null }]} unit="pc" />,
        );

        expect(screen.getByText('600 pc → 400 pc')).toBeInTheDocument();
    });

    it('describes a price change per unit', () => {
        render(<HistoryList entries={[priceChange]} unit="pc" />);

        expect(within(row(0)).getByText('Price change')).toBeInTheDocument();
        expect(
            within(row(0)).getByText('€0.32 → €0.35 per pc'),
        ).toBeInTheDocument();
    });

    it('keeps 4 decimals for prices below one cent', () => {
        render(
            <HistoryList
                entries={[
                    entry({
                        quantity: '10000.0000',
                        unit_price: '0.0040',
                        reference: null,
                    }),
                ]}
                unit="m"
            />,
        );

        expect(screen.getByText('+10,000 m at €0.0040')).toBeInTheDocument();
    });

    it('always shows the type as text, never by colour alone', () => {
        render(<HistoryList entries={buttonHistory} unit="pc" />);

        for (const label of [
            'Price change',
            'Restock',
            'Correction',
            'Created',
        ]) {
            expect(screen.getByText(label)).toBeVisible();
        }
    });

    it('colours each type with its own status token', () => {
        render(<HistoryList entries={buttonHistory} unit="pc" />);

        expect(screen.getByText('Restock')).toHaveClass('bg-success');
        expect(screen.getByText('Price change')).toHaveClass('bg-info');
        expect(screen.getByText('Correction')).toHaveClass('bg-warning');
        expect(screen.getByText('Created')).toHaveClass('bg-neutral');
    });

    it('uses the server label for the badge', () => {
        render(
            <HistoryList
                entries={[entry({ type_label: 'Réassort' })]}
                unit="pc"
            />,
        );

        expect(screen.getByText('Réassort')).toBeInTheDocument();
    });

    it('marks the date up as a machine-readable time', () => {
        render(<HistoryList entries={[restock]} unit="pc" />);

        expect(screen.getByText('2 Oct 2026')).toHaveAttribute(
            'datetime',
            '2026-10-02',
        );
    });

    it('omits "by" when nobody is recorded', () => {
        render(
            <HistoryList entries={[entry({ user_name: null })]} unit="pc" />,
        );

        expect(screen.queryByText(/^by /)).not.toBeInTheDocument();
    });

    it('formats money in the installation currency and numbers in its locale', () => {
        // Intl puts a no-break space between "TND" and the number; Testing
        // Library's default normalizer reads it as a plain space.
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        render(<HistoryList entries={[priceChange, restock]} unit="pc" />);

        expect(
            screen.getByText('TND 0.320 → TND 0.350 per pc'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('+500 pc at TND 0.350 · PO-2291'),
        ).toBeInTheDocument();
    });

    it('says so when there is no history', () => {
        render(<HistoryList entries={[]} unit="pc" />);

        expect(screen.getByText('No history yet.')).toBeInTheDocument();
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
    });
});
