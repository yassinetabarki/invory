import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { SkeletonCard, SkeletonRow, SkeletonStat } from './skeletons';

const rowsOf = (container: HTMLElement) =>
    container.querySelectorAll('[data-slot="skeleton-row"]');

describe('SkeletonCard', () => {
    it('announces the loading state once and keeps its rows decorative', () => {
        const { container, rerender } = render(
            <SkeletonCard label="top sellers" title="Top sellers" />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'Loading top sellers…',
        );
        expect(
            screen.getByRole('heading', { name: 'Top sellers' }),
        ).toBeInTheDocument();
        expect(rowsOf(container)).toHaveLength(3);
        expect(container.querySelector('.animate-pulse')).not.toBeNull();

        rerender(<SkeletonCard label="top sellers" rows={5} />);

        expect(screen.getAllByRole('status')).toHaveLength(1);
        expect(screen.queryByRole('heading')).not.toBeInTheDocument();
        const rows = rowsOf(container);
        expect(rows).toHaveLength(5);
        rows.forEach((row) =>
            expect(row).toHaveAttribute('aria-hidden', 'true'),
        );
    });
});

describe('SkeletonStat', () => {
    it('shows its label and announces the loading state', () => {
        render(<SkeletonStat label="Inventory value" />);

        expect(screen.getByText('Inventory value')).toBeInTheDocument();
        expect(screen.getByRole('status')).toHaveTextContent(
            'Loading Inventory value…',
        );
    });
});

describe('SkeletonRow', () => {
    it('is decorative and drops the thumb on request', () => {
        const { container, rerender } = render(<SkeletonRow />);
        const blocks = () =>
            container.querySelectorAll('[data-slot="skeleton"]').length;

        expect(rowsOf(container)[0]).toHaveAttribute('aria-hidden', 'true');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        const withThumb = blocks();

        rerender(<SkeletonRow thumb={false} />);

        expect(blocks()).toBe(withThumb - 1);
    });
});
