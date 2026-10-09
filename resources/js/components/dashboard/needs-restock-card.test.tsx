import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { NeedsRestockCard } from '@/components/dashboard/needs-restock-card';
import { resetInertiaMock } from '@/test/inertia';
import { needsRestock } from '@/test/fixtures/dashboard';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function rows(): HTMLElement[] {
    return within(screen.getByRole('list')).getAllByRole('listitem');
}

describe('<NeedsRestockCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists each material with stock, reorder level and status', () => {
        render(<NeedsRestockCard materials={needsRestock} />);

        expect(
            screen.getByRole('heading', { level: 2, name: 'Needs restock' }),
        ).toBeInTheDocument();
        expect(rows()).toHaveLength(4);

        const cupsole = rows()[0];

        expect(
            within(cupsole).getByRole('link', { name: 'Rubber cupsole 42' }),
        ).toBeVisible();
        expect(within(cupsole).getByText(/reorder at/)).toHaveTextContent(
            '0 pair · reorder at 40 pair',
        );
        expect(within(cupsole).getByText('Out of stock')).toBeInTheDocument();

        expect(within(rows()[1]).getByText(/reorder at/)).toHaveTextContent(
            '64 m · reorder at 80 m',
        );
        expect(within(rows()[1]).getByText('Low stock')).toBeInTheDocument();
        expect(within(rows()[2]).getByText(/reorder at/)).toHaveTextContent(
            '900 pc · reorder at 1,000 pc',
        );
    });

    it('keeps the server order (out of stock first)', () => {
        render(<NeedsRestockCard materials={needsRestock} />);

        expect(
            rows().map((row) => within(row).getByRole('link').textContent),
        ).toEqual([
            'Rubber cupsole 42',
            'Cotton jersey 180gsm White',
            'Metal shank button 17mm',
            'Woven brand label',
        ]);
    });

    it('links each name to the material and View all to the restock list', () => {
        render(<NeedsRestockCard materials={needsRestock} />);

        expect(
            screen.getByRole('link', { name: 'Rubber cupsole 42' }),
        ).toHaveAttribute('href', '/materials/9');
        expect(screen.getByRole('link', { name: 'View all' })).toHaveAttribute(
            'href',
            '/materials?low_stock=1',
        );
    });

    it('prefetches the material and the restock list under the materials tag', () => {
        render(<NeedsRestockCard materials={needsRestock} />);

        for (const name of ['Rubber cupsole 42', 'View all']) {
            const link = screen.getByRole('link', { name });

            expect(link).toHaveAttribute('data-prefetch', 'true');
            expect(link).toHaveAttribute('data-cache-tags', 'materials');
        }
    });

    it('shows stock alone when the material has no reorder level', () => {
        render(
            <NeedsRestockCard
                materials={[{ ...needsRestock[0], reorder_level: null }]}
            />,
        );

        expect(screen.getByText('0 pair')).toBeVisible();
        expect(screen.queryByText(/reorder at/)).not.toBeInTheDocument();
    });

    it('says everything is above its reorder level when nothing needs restock', () => {
        render(<NeedsRestockCard materials={[]} />);

        expect(
            screen.getByText('All materials are above their reorder level.'),
        ).toBeVisible();
        expect(screen.queryByRole('list')).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'View all' })).toBeVisible();
    });
});
