import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { SalesTotals } from '@/components/sales/sales-totals';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import { noSalesTotals, salesTotals } from '@/test/fixtures/sales';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

/** The value shown under `label` in the totals. */
function valueOf(label: string): string {
    const region = screen.getByRole('region', {
        name: 'Totals for the selected period',
    });
    const term = within(region).getByText(label);

    return term.nextElementSibling?.textContent ?? '';
}

describe('<SalesTotals>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('is a labelled region with the four totals', () => {
        render(<SalesTotals totals={salesTotals} />);

        const region = screen.getByRole('region', {
            name: 'Totals for the selected period',
        });

        expect(
            within(region)
                .getAllByRole('term')
                .map((term) => term.textContent),
        ).toEqual([
            'Total sales',
            'Units sold',
            'Sales recorded',
            'Average sale',
        ]);
    });

    it('formats the server totals (mockup values, EUR)', () => {
        render(<SalesTotals totals={salesTotals} />);

        expect(valueOf('Total sales')).toBe('€16,850.00');
        expect(valueOf('Units sold')).toBe('296');
        expect(valueOf('Sales recorded')).toBe('48');
        expect(valueOf('Average sale')).toBe('€351.04');
    });

    it('shows an em dash for the average when nothing was sold', () => {
        render(<SalesTotals totals={noSalesTotals} />);

        expect(valueOf('Total sales')).toBe('€0.00');
        expect(valueOf('Units sold')).toBe('0');
        expect(valueOf('Sales recorded')).toBe('0');
        expect(valueOf('Average sale')).toBe('—');
    });

    it('uses the configured currency and locale', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });

        render(<SalesTotals totals={salesTotals} />);

        expect(valueOf('Total sales')).toMatch(/^TND\s16,850\.000$/);
        expect(valueOf('Average sale')).toMatch(/^TND\s351\.042$/);
    });

    it('groups thousands in the counts', () => {
        render(
            <SalesTotals
                totals={{
                    ...salesTotals,
                    units_sold: 12345,
                    sales_count: 1200,
                }}
            />,
        );

        expect(valueOf('Units sold')).toBe('12,345');
        expect(valueOf('Sales recorded')).toBe('1,200');
    });
});
