import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { CapacityBadge } from '@/components/shared/capacity-badge';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<CapacityBadge>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    describe('no composition (null)', () => {
        it('says so, in a neutral tone', () => {
            render(<CapacityBadge value={null} />);

            const badge = screen.getByText('No composition');

            expect(badge).toHaveAttribute('data-capacity', 'none');
            expect(badge).toHaveClass('bg-neutral', 'text-neutral-foreground');
        });
    });

    describe("can't produce (0)", () => {
        it('shows "Can\'t produce" in the danger tone', () => {
            render(<CapacityBadge value={0} />);

            const badge = screen.getByText("Can't produce");

            expect(badge).toHaveAttribute('data-capacity', 'zero');
            expect(badge).toHaveClass('bg-danger', 'text-danger-foreground');
        });

        it('no longer says "Out of stock" (that belongs to materials)', () => {
            render(<CapacityBadge value={0} />);

            expect(screen.queryByText(/out of stock/i)).not.toBeInTheDocument();
        });
    });

    describe('units available', () => {
        it('shows the number and "units" in the success tone', () => {
            render(<CapacityBadge value={112} />);

            const badge = screen.getByText('112 units');

            expect(badge).toHaveAttribute('data-capacity', 'available');
            expect(badge).toHaveClass('bg-success', 'text-success-foreground');
        });

        it('uses the singular for one unit', () => {
            render(<CapacityBadge value={1} />);

            expect(screen.getByText('1 unit')).toBeInTheDocument();
        });

        it('groups large numbers with the locale', () => {
            render(<CapacityBadge value={1250} />);

            expect(screen.getByText('1,250 units')).toBeInTheDocument();
        });

        it('uses the locale from the shared props', () => {
            setPageProps({ app: { currency: 'EUR', locale: 'de' } });
            render(<CapacityBadge value={1250} />);

            expect(screen.getByText('1.250 units')).toBeInTheDocument();
        });

        it('handles a very large capacity (1 000 000)', () => {
            render(<CapacityBadge value={1_000_000} />);

            expect(screen.getByText('1,000,000 units')).toBeInTheDocument();
        });
    });

    it('always pairs the colour with an icon hidden from assistive tech', () => {
        const { container } = render(
            <>
                <CapacityBadge value={null} />
                <CapacityBadge value={0} />
                <CapacityBadge value={5} />
            </>,
        );

        const icons = Array.from(container.querySelectorAll('svg'));

        expect(icons).toHaveLength(3);
        icons.forEach((icon) =>
            expect(icon).toHaveAttribute('aria-hidden', 'true'),
        );
        expect(new Set(icons.map((icon) => icon.innerHTML)).size).toBe(3);
    });

    it.each([
        [
            'guaranteed below best',
            16,
            112,
            '16–112 units',
            'range',
            'bg-success',
        ],
        [
            'guaranteed 0, best above',
            0,
            112,
            '0–112 units',
            'short',
            'bg-warning',
        ],
        ['best 0', 0, 0, "Can't produce", 'zero', 'bg-danger'],
        ['equal ends', 112, 112, '112 units', 'available', 'bg-success'],
        [
            'no best given',
            112,
            undefined,
            '112 units',
            'available',
            'bg-success',
        ],
    ])('reads %s as "%s"', (_, value, best, label, state, tone) => {
        render(<CapacityBadge value={value} best={best} />);

        const badge = screen.getByText(label);

        expect(badge).toHaveAttribute('data-capacity', state);
        expect(badge).toHaveClass(tone);
    });

    it('accepts an extra class name', () => {
        render(<CapacityBadge value={3} className="text-base" />);

        expect(screen.getByText('3 units')).toHaveClass('text-base');
    });
});
