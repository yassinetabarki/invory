import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrderSizeFields } from '@/components/orders/order-size-fields';
import { sizeLists } from '@/test/fixtures';
import { resetInertiaMock } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('<OrderSizeFields>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('wraps small labelled fields onto several rows with "Total" after the last (S-60 AC-6)', () => {
        const sizes = sizeLists.eu_36_to_46.sizes;

        render(
            <OrderSizeFields
                idPrefix="test"
                legend="Sizes"
                sizes={sizes}
                values={{
                    [sizes[0].id]: '4',
                    [sizes[1].id]: 'x',
                    [sizes[2].id]: ' 6 ',
                }}
                onChange={() => {}}
            />,
        );

        const group = screen.getByRole('group', { name: 'Sizes' });
        const fields = within(group).getAllByRole('textbox');

        // jsdom has no layout: the wrapping container and a width that shows "1000000" whole (w-24 = 96 px; two fit a 275 px line) stand in for 375 px.
        expect(fields).toHaveLength(11);
        expect(fields[0].closest('.flex-wrap')).not.toBeNull();
        fields.forEach((field, index) => {
            expect(field).toHaveAccessibleName(sizes[index].label);
            expect(field).toHaveClass('w-24');
        });

        // An empty or non-numeric field counts 0; the total follows the whole numbers.
        const total = within(group).getByText('Total 10 units');

        expect(
            fields[10].compareDocumentPosition(total) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();
    });
});
