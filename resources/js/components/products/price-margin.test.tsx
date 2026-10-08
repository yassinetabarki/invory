import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { PriceMargin } from '@/components/products/price-margin';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<PriceMargin>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the price with the margin percentage under it', () => {
        render(
            <PriceMargin
                sellingPrice="89.0000"
                margin={{ amount: '53.2460', percent: '59.83' }}
            />,
        );

        expect(screen.getByText('€89.00')).toBeInTheDocument();
        expect(screen.getByText('59.83%')).toBeInTheDocument();
        expect(screen.getByText('margin', { exact: false })).toHaveClass(
            'sr-only',
        );
    });

    it('uses the installation currency', () => {
        setPageProps({
            app: { currency: 'TND', locale: 'en', today: '2026-10-08' },
        });
        render(
            <PriceMargin
                sellingPrice="89.0000"
                margin={{ amount: '53.2460', percent: '59.83' }}
            />,
        );

        expect(screen.getByText('TND 89.000')).toBeInTheDocument();
    });

    it('shows an em dash, announced as "No selling price", without a price', () => {
        render(<PriceMargin sellingPrice={null} margin={null} />);

        expect(screen.getByText('—')).toHaveAttribute('aria-hidden', 'true');
        expect(screen.getByText('No selling price')).toHaveClass('sr-only');
    });

    it('shows a negative margin in red with its minus sign and a screen-reader note', () => {
        render(
            <PriceMargin
                sellingPrice="30.0000"
                margin={{ amount: '-5.7540', percent: '-19.18' }}
            />,
        );

        expect(screen.getByText('-19.18%', { exact: false })).toHaveClass(
            'text-danger-foreground',
        );
        expect(
            screen.getByText('margin, below cost', { exact: false }),
        ).toHaveClass('sr-only');
    });

    it('shows the margin amount when there is no percentage (a zero price)', () => {
        render(
            <PriceMargin
                sellingPrice="0.0000"
                margin={{ amount: '-35.7540', percent: null }}
            />,
        );

        expect(screen.getByText('-€35.75')).toBeInTheDocument();
        expect(
            screen.queryByText('%', { exact: false }),
        ).not.toBeInTheDocument();
    });
});
