import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { StockBadge } from '@/components/materials/stock-badge';
import type { StockStatus } from '@/types/domain';

describe('<StockBadge>', () => {
    it.each<[StockStatus, string]>([
        ['in_stock', 'In stock'],
        ['low', 'Low stock'],
        ['out', 'Out of stock'],
    ])('shows the text label for %s', (status, label) => {
        render(<StockBadge status={status} />);

        expect(screen.getByText(label)).toBeInTheDocument();
    });

    it.each<StockStatus>(['in_stock', 'low', 'out'])(
        'adds an icon that is hidden from assistive tech for %s',
        (status) => {
            const { container } = render(<StockBadge status={status} />);

            const icon = container.querySelector('svg');

            expect(icon).toBeInTheDocument();
            expect(icon).toHaveAttribute('aria-hidden', 'true');
        },
    );

    it('uses a distinct style per status (not text alone)', () => {
        const { container } = render(
            <>
                <StockBadge status="in_stock" />
                <StockBadge status="low" />
                <StockBadge status="out" />
            </>,
        );

        const classes = Array.from(
            container.querySelectorAll('[data-status]'),
        ).map((el) => el.className);

        expect(new Set(classes).size).toBe(3);
    });
});
