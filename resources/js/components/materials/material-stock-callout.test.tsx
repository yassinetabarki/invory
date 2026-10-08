import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { MaterialStockCallout } from '@/components/materials/material-stock-callout';
import { button, denim, thread } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<MaterialStockCallout>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows nothing while the material is in stock', () => {
        const { container } = render(<MaterialStockCallout material={denim} />);

        expect(container).toBeEmptyDOMElement();
    });

    it('warns below the reorder level, with the stock and the level in their unit', () => {
        render(<MaterialStockCallout material={button} />);

        const callout = screen
            .getByText('Below reorder level.')
            .closest('[data-slot="callout"]');

        expect(callout).toHaveAttribute('data-tone', 'warning');
        expect(callout).toHaveTextContent(
            'Below reorder level. 900 pc on hand, reorder at 1,000 pc.',
        );
    });

    it('is a danger alert when the material is out of stock', () => {
        render(
            <MaterialStockCallout
                material={{ ...thread, reorder_level: '40.0000', unit: 'pair' }}
            />,
        );

        const callout = screen.getByRole('alert');

        expect(callout).toHaveAttribute('data-tone', 'danger');
        expect(callout).toHaveTextContent(
            'Out of stock. 0 pair on hand, reorder at 40 pair.',
        );
    });

    it('leaves the reorder level out when none is set', () => {
        render(<MaterialStockCallout material={thread} />);

        expect(screen.getByRole('alert')).toHaveTextContent(
            'Out of stock. 0 m on hand.',
        );
        expect(screen.queryByText(/reorder at/)).not.toBeInTheDocument();
    });
});
