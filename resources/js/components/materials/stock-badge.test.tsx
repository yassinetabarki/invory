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

    it.each<[StockStatus, string]>([
        ['in_stock', 'success'],
        ['low', 'warning'],
        ['out', 'danger'],
    ])('uses the %s status tokens (%s) for its colours', (status, tone) => {
        render(<StockBadge status={status} />);

        const badge = screen.getByText(/stock/i);

        expect(badge).toHaveClass(`bg-${tone}`, `text-${tone}-foreground`);
    });

    it('uses a distinct style and icon per status (not text alone)', () => {
        const { container } = render(
            <>
                <StockBadge status="in_stock" />
                <StockBadge status="low" />
                <StockBadge status="out" />
            </>,
        );

        const badges = Array.from(container.querySelectorAll('[data-status]'));

        expect(new Set(badges.map((el) => el.className)).size).toBe(3);
        expect(
            new Set(badges.map((el) => el.querySelector('svg')?.outerHTML))
                .size,
        ).toBe(3);
    });

    it('accepts an extra class name', () => {
        render(<StockBadge status="low" className="ml-2" />);

        expect(screen.getByText('Low stock')).toHaveClass('ml-2');
    });
});
