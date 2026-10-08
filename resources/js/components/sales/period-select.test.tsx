import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import { DEFAULT_PERIOD, PeriodSelect } from '@/components/sales/period-select';
import { salesPeriods } from '@/test/fixtures/sales';
import type { Option, SalesPeriod } from '@/types/domain';

function setup(
    value: SalesPeriod = 'last_30_days',
    periods: Option<SalesPeriod>[] = salesPeriods,
) {
    const onChange = vi.fn();
    const user = userEvent.setup();

    render(
        <PeriodSelect
            id="period"
            value={value}
            periods={periods}
            onChange={onChange}
        />,
    );

    return { onChange, user };
}

describe('<PeriodSelect>', () => {
    it('uses the same default period as the server', () => {
        expect(DEFAULT_PERIOD).toBe('last_30_days');
    });

    it('is a combobox named Period that shows the selected label', () => {
        setup('this_month');

        expect(
            screen.getByRole('combobox', { name: 'Period' }),
        ).toHaveTextContent('This month');
    });

    it('lists the periods the server sent, in its order, with its labels', async () => {
        const { user } = setup('last_7_days', [
            { value: 'last_7_days', label: 'Past week' },
            { value: 'this_year', label: 'Year to date' },
        ]);

        await user.click(screen.getByRole('combobox', { name: 'Period' }));

        expect(
            screen.getAllByRole('option').map((option) => option.textContent),
        ).toEqual(['Past week', 'Year to date']);
    });

    it('reports the chosen period value', async () => {
        const { user, onChange } = setup();

        await user.click(screen.getByRole('combobox', { name: 'Period' }));
        await user.click(screen.getByRole('option', { name: 'This year' }));

        expect(onChange).toHaveBeenCalledExactlyOnceWith('this_year');
    });

    it('reports nothing when the selection does not change', async () => {
        const { user, onChange } = setup('last_30_days');

        await user.click(screen.getByRole('combobox', { name: 'Period' }));
        await user.click(screen.getByRole('option', { name: 'Last 30 days' }));

        expect(onChange).not.toHaveBeenCalled();
    });
});
