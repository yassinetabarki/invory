import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { Money } from '@/components/shared/money';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<Money>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('formats the worked-example unit cost with the shared currency', () => {
        render(<Money value="35.7540" />);

        expect(screen.getByText('€35.75')).toBeInTheDocument();
    });

    it('uses the currency and locale from the shared props', () => {
        setPageProps({ app: { currency: 'USD', locale: 'en' } });
        render(<Money value="1234.5000" />);

        expect(screen.getByText('$1,234.50')).toBeInTheDocument();
    });

    it('rounds tiny costs to 2 decimals unless precise', () => {
        render(<Money value="0.0040" />);

        expect(screen.getByText('€0.00')).toBeInTheDocument();
    });

    it('shows 4 decimals for tiny per-unit costs when precise', () => {
        render(<Money value="0.0040" precise />);

        expect(screen.getByText('€0.0040')).toBeInTheDocument();
    });

    it('shows TND with 3 decimals, and 4 below one millime when precise', () => {
        setPageProps({ app: { currency: 'TND', locale: 'en' } });
        const { rerender } = render(<Money value="35.7540" />);

        expect(screen.getByText(/TND/).textContent).toBe('TND 35.754');

        rerender(<Money value="0.0004" precise />);
        expect(screen.getByText(/TND/).textContent).toBe('TND 0.0004');

        rerender(<Money value="0.0040" precise />);
        expect(screen.getByText(/TND/).textContent).toBe('TND 0.004');
    });

    it('renders an em dash when there is no value', () => {
        render(<Money value={null} />);

        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('falls back to TND / en when the server sends no app settings', () => {
        setPageProps({ app: undefined });
        render(<Money value="12.0000" />);

        // Intl puts a no-break space (U+00A0) between "TND" and the number.
        // Testing Library and jest-dom normalise whitespace to a plain space,
        // so assert the raw textContent to pin the exact character.
        expect(screen.getByText(/TND/).textContent).toBe('TND 12.000');
    });
});
