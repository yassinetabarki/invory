import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import {
    CapacityByColourCard,
    CapacityByColourCardLoading,
} from '@/components/products/capacity-by-colour-card';
import { denimJacketVarying } from '@/test/fixtures/products';
import { resetInertiaMock } from '@/test/inertia';
import type { ColourCapacity, MaterialRef } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const rows = denimJacketVarying.capacity_by_colour as ColourCapacity[];
const [black, indigo] = rows;

function renderCard(
    capacityRows: ColourCapacity[] = rows,
    guaranteed: number | null = 16,
    best: number | null = 112,
) {
    return render(
        <CapacityByColourCard
            rows={capacityRows}
            guaranteed={guaranteed}
            best={best}
            materialHref={(material: MaterialRef) =>
                `/materials/${material.id}`
            }
        />,
    );
}

const table = () => within(screen.getByRole('table'));
const cells = (row: HTMLElement) =>
    Array.from(row.querySelectorAll('th, td')).map((cell) => cell.textContent);

describe('<CapacityByColourCard>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('lists one row per colour with its figures, the limiting material and its size, then the all-colours row', () => {
        renderCard();

        expect(
            screen.getByRole('heading', {
                level: 2,
                name: 'Capacity by colour',
            }),
        ).toBeInTheDocument();
        expect(
            table()
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Colour', 'Guaranteed', 'Best', 'Limited by']);

        const [, blackRow, indigoRow, totalRow] = table().getAllByRole('row');

        expect(cells(blackRow)).toEqual([
            'Black',
            '16',
            '22',
            'Denim 12oz Black (XL)',
        ]);
        expect(cells(indigoRow)).toEqual([
            'Indigo',
            '103',
            '112',
            'Denim 12oz Indigo (XL)',
        ]);
        expect(
            within(indigoRow).getByRole('link', { name: 'Denim 12oz Indigo' }),
        ).toHaveAttribute('href', `/materials/${indigo.bottleneck?.id}`);
        expect(cells(totalRow)).toEqual([
            'All colours (any mix)',
            '16',
            '112',
            '',
        ]);
    });

    it('reads "No composition" for a colour that uses no material, and leaves it out of the figures', () => {
        renderCard([
            black,
            {
                colour: indigo.colour,
                guaranteed: null,
                best: null,
                bottleneck: null,
                bottleneck_colour: null,
                bottleneck_missing: false,
                binding_size: null,
            },
        ]);

        const [, , indigoRow] = table().getAllByRole('row');

        expect(cells(indigoRow)).toEqual(['Indigo', 'No composition']);
        expect(within(indigoRow).queryByRole('link')).not.toBeInTheDocument();
    });

    it('stacks one card per colour on a phone, each with Guaranteed, Best and Limited by', () => {
        renderCard();

        const items = within(
            screen.getByRole('list', { name: 'Capacity by colour' }),
        ).getAllByRole('listitem');

        expect(items).toHaveLength(2);

        const card = within(items[0]);

        expect(card.getByText('Black')).toBeInTheDocument();
        expect(card.getByText('Guaranteed').nextSibling).toHaveTextContent(
            '16',
        );
        expect(card.getByText('Best').nextSibling).toHaveTextContent('22');
        expect(card.getByText('Limited by').nextSibling).toHaveTextContent(
            'Denim 12oz Black (XL)',
        );
        expect(
            within(
                screen.getByRole('group', { name: 'All colours (any mix)' }),
            ).getByText('Guaranteed').nextSibling,
        ).toHaveTextContent('16');
    });

    it('shows a placeholder while the product loads', () => {
        render(<CapacityByColourCardLoading />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Loading capacity by colour…',
        );
        expect(
            screen.getByRole('heading', { name: 'Capacity by colour' }),
        ).toBeInTheDocument();
        expect(screen.queryByRole('table')).not.toBeInTheDocument();
    });
});
