import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { OrderSizeFields } from '@/components/orders/order-size-fields';
import { sizeLists } from '@/test/fixtures';
import type { Size } from '@/types/domain';
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

        expect(fields).toHaveLength(11);
        fields.forEach((field, index) => {
            expect(field).toHaveAccessibleName(sizes[index].label);
        });

        // An empty or non-numeric field counts 0; the total follows the whole numbers.
        const total = within(group).getByText('Total 10 units');

        expect(
            fields[10].compareDocumentPosition(total) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        ).toBeTruthy();
    });

    it('shapes the fields as one wrapping grid, the figure centred (S-70 AC-4)', () => {
        const sizes = sizeLists.eu_36_to_46.sizes;

        render(
            <OrderSizeFields
                idPrefix="test"
                legend="Sizes"
                sizes={sizes}
                values={{ [sizes[0].id]: '1000000' }}
                onChange={() => {}}
            />,
        );

        const fields = screen.getAllByRole('textbox');
        const grid = fields[0].closest('[data-slot="size-fields"]');

        // jsdom has no layout: the grid, the classes and the browser check stand in for 375 px.
        expect(grid).toHaveClass(
            'grid',
            'grid-cols-[repeat(auto-fill,minmax(4rem,1fr))]',
        );
        fields.forEach((field) => {
            expect(field.closest('[data-slot="size-fields"]')).toBe(grid);
            expect(field).toHaveClass('h-11', 'text-center', 'tabular-nums');
        });
        // The size sits above its field, small and bold.
        expect(screen.getByText(sizes[0].label)).toHaveClass('font-bold');
        expect(fields[0]).toHaveValue('1000000');
    });

    describe('messages', () => {
        const letters = sizeLists.s_to_xl.sizes;
        const at = (label: string): Size => {
            const size = letters.find((entry) => entry.label === label);

            if (!size) {
                throw new Error(`No size ${label}`);
            }

            return size;
        };
        const follows = (earlier: Element, later: Element) =>
            Boolean(
                earlier.compareDocumentPosition(later) &
                Node.DOCUMENT_POSITION_FOLLOWING,
            );

        it('keeps the row when a size is wrong: the message names it and shows after the Total (S-70 AC-5)', () => {
            const message = 'M: Enter a whole number from 0 to 1,000,000.';

            render(
                <OrderSizeFields
                    idPrefix="test"
                    legend="Sizes"
                    sizes={letters}
                    values={{
                        [at('S').id]: '10',
                        [at('M').id]: '2.5',
                        [at('L').id]: '15',
                        [at('XL').id]: '25',
                    }}
                    errors={{ [at('M').id]: message }}
                    onChange={() => {}}
                />,
            );

            const group = within(screen.getByRole('group', { name: 'Sizes' }));
            const fields = group.getAllByRole('textbox');

            // Still S, M, L, XL in one grid, in size order.
            expect(fields.map((field) => field.id)).toEqual(
                letters.map((size) => `test-size-${size.id}`),
            );
            expect(
                new Set(
                    fields.map((field) =>
                        field.closest('[data-slot="size-fields"]'),
                    ),
                ).size,
            ).toBe(1);
            expect(group.getByLabelText('M')).toBeInvalid();
            expect(group.getByLabelText('M')).toHaveAccessibleDescription(
                message,
            );

            for (const label of ['S', 'L', 'XL']) {
                expect(group.getByLabelText(label)).not.toBeInvalid();
            }

            // Nothing under the field itself; the message comes after "Total 50 units".
            const total = group.getByText('Total 50 units');

            expect(follows(total, group.getByText(message))).toBe(true);
            expect(group.getByText(message)).not.toBe(
                group.getByLabelText('M').nextElementSibling,
            );
        });

        it("puts the line's message first, then one line per size in size order, as sent (S-70 AC-5)", () => {
            const lineMessage = 'A line can hold up to 1,000,000 units.';
            const sMessage = 'S: Enter a whole number from 0 to 1,000,000.';
            const mMessage = 'You can move at most 20 units of M.';

            render(
                <OrderSizeFields
                    idPrefix="test"
                    legend="Sizes"
                    sizes={letters}
                    values={{}}
                    // Given out of size order on purpose.
                    errors={{ [at('M').id]: mMessage, [at('S').id]: sMessage }}
                    totalError={lineMessage}
                    onChange={() => {}}
                />,
            );

            const group = within(screen.getByRole('group', { name: 'Sizes' }));
            const lines = [lineMessage, sMessage, mMessage].map((text) =>
                group.getByText(text),
            );

            expect(follows(lines[0], lines[1])).toBe(true);
            expect(follows(lines[1], lines[2])).toBe(true);
            // A message that names its size shows unchanged, with no second name.
            expect(lines[2]).toHaveTextContent(
                /^You can move at most 20 units of M\.$/,
            );
            // Each field hears its own message, then the line's.
            expect(group.getByLabelText('M')).toHaveAccessibleDescription(
                `${mMessage} ${lineMessage}`,
            );
            expect(group.getByLabelText('S')).toHaveAccessibleDescription(
                `${sMessage} ${lineMessage}`,
            );
            expect(group.getByLabelText('L')).toHaveAccessibleDescription(
                lineMessage,
            );
        });
    });
});
