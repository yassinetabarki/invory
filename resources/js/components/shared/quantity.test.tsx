import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { Quantity } from '@/components/shared/quantity';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<Quantity>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the quantity next to its unit', () => {
        render(<Quantity value="1.9800" unit="m" />);

        expect(screen.getByText('1.98 m')).toBeInTheDocument();
    });

    it('shows whole quantities without trailing zeros', () => {
        render(<Quantity value="900.0000" unit="pc" />);

        expect(screen.getByText('900 pc')).toBeInTheDocument();
    });

    it('keeps tiny quantities exact', () => {
        render(<Quantity value="0.0001" unit="m" />);

        expect(screen.getByText('0.0001 m')).toBeInTheDocument();
    });

    it('omits the unit when none is given', () => {
        render(<Quantity value="250.0000" />);

        expect(screen.getByText('250')).toBeInTheDocument();
    });

    it('formats with the locale from the shared props', () => {
        setPageProps({ app: { currency: 'EUR', locale: 'de' } });
        render(<Quantity value="1234.5000" unit="m" />);

        expect(screen.getByText('1.234,5 m')).toBeInTheDocument();
    });

    it('uses tabular figures so quantities line up in columns', () => {
        render(<Quantity value="1.9800" unit="m" className="font-bold" />);

        expect(screen.getByText('1.98 m')).toHaveClass(
            'tabular-nums',
            'font-bold',
        );
    });

    it('renders an em dash when there is no value', () => {
        render(<Quantity value={undefined} unit="m" />);

        expect(screen.getByText('—')).toBeInTheDocument();
    });
});
