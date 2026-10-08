import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { StatCard } from '@/components/shared/stat-card';
import { Money } from '@/components/shared/money';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<StatCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the label, the figure and the hint', () => {
        render(
            <StatCard
                label="Units sold"
                value="296"
                hint="across 6 products"
            />,
        );

        expect(screen.getByText('Units sold')).toBeInTheDocument();
        expect(screen.getByText('296')).toBeInTheDocument();
        expect(screen.getByText('across 6 products')).toBeInTheDocument();
    });

    it('renders any node as the value (a formatted amount)', () => {
        render(
            <StatCard
                label="Total sales"
                value={<Money value="16850.0000" />}
            />,
        );

        expect(screen.getByText('€16,850.00')).toBeInTheDocument();
    });

    it('is a plain card, not a link, without an href', () => {
        const { container } = render(
            <StatCard label="Inventory value" value="€10,544.30" />,
        );

        expect(screen.queryByRole('link')).not.toBeInTheDocument();
        expect(
            container.querySelector('[data-slot="stat-card"]'),
        ).toBeInTheDocument();
    });

    it('works without a hint', () => {
        render(<StatCard label="Products" value="7" />);

        expect(screen.getByText('Products')).toBeInTheDocument();
        expect(screen.getByText('7')).toBeInTheDocument();
    });

    describe('with an href', () => {
        it('makes the whole card one link whose name carries the label, figure and hint', () => {
            render(
                <StatCard
                    label="Needs restock"
                    value="4"
                    hint="3 low · 1 out of stock"
                    href="/materials?needs_restock=1"
                />,
            );

            const link = screen.getByRole('link', {
                name: 'Needs restock 4 3 low · 1 out of stock',
            });

            expect(link).toHaveAttribute('href', '/materials?needs_restock=1');
        });

        it('accepts a Wayfinder route object', () => {
            render(
                <StatCard
                    label="Can't produce"
                    value="1"
                    href={{ url: '/products?cant_produce=1' }}
                />,
            );

            expect(screen.getByRole('link')).toHaveAttribute(
                'href',
                '/products?cant_produce=1',
            );
        });

        it('adds an arrow that screen readers skip', () => {
            render(
                <StatCard
                    label="Needs restock"
                    value="4"
                    hint="3 low"
                    href="/materials"
                />,
            );

            expect(
                screen.getByRole('link').querySelector('svg'),
            ).toHaveAttribute('aria-hidden', 'true');
        });

        it('shows the arrow even without a hint', () => {
            render(<StatCard label="Products" value="7" href="/products" />);

            expect(
                screen.getByRole('link').querySelector('svg'),
            ).toBeInTheDocument();
        });

        it('has no arrow when it is not a link', () => {
            const { container } = render(
                <StatCard label="Products" value="7" hint="all" />,
            );

            expect(container.querySelector('svg')).not.toBeInTheDocument();
        });
    });

    describe('tone', () => {
        it('uses the muted colour for the default hint', () => {
            render(<StatCard label="Units" value="1" hint="a hint" />);

            expect(screen.getByText('a hint')).toHaveClass(
                'text-muted-foreground',
            );
        });

        it('colours the hint amber for warning', () => {
            render(
                <StatCard
                    label="Needs restock"
                    value="4"
                    hint="3 low"
                    tone="warning"
                />,
            );

            expect(screen.getByText('3 low')).toHaveClass(
                'text-warning-foreground',
                'font-semibold',
            );
        });

        it('colours the hint red for danger', () => {
            render(
                <StatCard
                    label="Can't produce"
                    value="1"
                    hint="of 7 products"
                    tone="danger"
                />,
            );

            expect(screen.getByText('of 7 products')).toHaveClass(
                'text-danger-foreground',
                'font-semibold',
            );
        });
    });

    it('accepts an extra class name', () => {
        const { container } = render(
            <StatCard label="Units" value="1" className="col-span-2" />,
        );

        expect(container.querySelector('[data-slot="stat-card"]')).toHaveClass(
            'col-span-2',
        );
    });
});
