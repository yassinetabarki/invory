import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { CompositionCard } from '@/components/products/composition-card';
import {
    denimJacket,
    denimJacketVarying,
    jacketWithoutComposition,
    sizeOptionsFixture,
} from '@/test/fixtures/products';
import { resetInertiaMock } from '@/test/inertia';
import type { MaterialRef, Product } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

function renderCard(product: Product) {
    return render(
        <CompositionCard
            lines={product.components}
            materialsTotal={product.cost.material_cost}
            capacity={product.capacity.max_producible}
            best={product.capacity.best}
            materialHref={(material: MaterialRef) =>
                `/materials/${material.id}`
            }
            colours={product.colours}
            sizes={sizeOptionsFixture.slice(0, 4)}
            editHref={`/products/${product.id}/edit`}
        />,
    );
}

describe('<CompositionCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('has a Composition heading, its description and an edit link', () => {
        renderCard(denimJacket);

        const section = screen.getByRole('region', { name: 'Composition' });

        expect(
            within(section).getByRole('heading', {
                level: 2,
                name: 'Composition',
            }),
        ).toBeInTheDocument();
        expect(
            within(section).getByText('Materials needed to make one unit.'),
        ).toBeInTheDocument();
        expect(
            within(section).getByRole('link', { name: 'Edit composition' }),
        ).toHaveAttribute('href', '/products/1/edit');
        expect(
            within(section).getByRole('table', { name: 'Composition' }),
        ).toBeInTheDocument();
    });

    it.each([
        {
            name: 'the guaranteed to the best figure',
            capacity: { max_producible: 16, best: 112 },
            label: '16–112 units',
            state: 'range',
        },
        {
            name: 'a short range when a colour and size is at 0',
            capacity: { max_producible: 0, best: 112 },
            label: '0–112 units',
            state: 'short',
        },
        {
            name: "can't produce only when the best case is 0",
            capacity: { max_producible: 0, best: 0 },
            label: "Can't produce",
            state: 'zero',
        },
    ])(
        'badges "Can produce" in the footer as $name',
        ({ capacity, label, state }) => {
            renderCard({
                ...denimJacketVarying,
                capacity: { ...denimJacketVarying.capacity, ...capacity },
            });

            const table = screen.getByRole('table', { name: 'Composition' });
            const desktop = within(within(table).getAllByRole('rowgroup')[2]);
            const cards = screen.getByRole('list', { name: 'Composition' });
            const phone = within(cards.nextElementSibling as HTMLElement);

            expect(desktop.getByText(label)).toHaveAttribute(
                'data-capacity',
                state,
            );
            expect(phone.getByText(label)).toHaveAttribute(
                'data-capacity',
                state,
            );
        },
    );

    it('invites the user to add materials when there are none', () => {
        renderCard(jacketWithoutComposition);

        expect(
            screen.getByRole('heading', { name: 'No materials yet' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Add materials' }),
        ).toHaveAttribute('href', '/products/6/edit');
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
        expect(
            screen.queryByRole('link', { name: 'Edit composition' }),
        ).not.toBeInTheDocument();
    });
});
