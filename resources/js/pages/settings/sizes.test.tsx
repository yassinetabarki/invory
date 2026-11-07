import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Sizes from '@/pages/settings/sizes';
import { resetInertiaMock, setPageProps } from '@/test/inertia';
import type { SizeRow } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const sizes: SizeRow[] = [
    ['XS', 10, 3, false],
    ['XXS', 15, 0, false],
    ['S', 20, 3, false],
    ['36', 80, 1, false],
    ['One size', 190, 2, true],
].map(([label, position, usage_count, one_size], index) => ({
    id: index + 1,
    label: label as string,
    position: position as number,
    usage_count: usage_count as number,
    one_size: one_size as boolean,
}));

describe('settings/sizes page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/settings/sizes' });
    });

    it('shows the title, the line, Add size and the sizes in order with Position, Used by and Edit, in a table and in phone rows', () => {
        render(<Sizes sizes={sizes} />);

        expect(
            screen.getByRole('heading', { name: 'Sizes', level: 2 }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The sizes your products come in. Each product picks the ones it has. Rename any of them, or add your own.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Add size' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'A size is never removed. Positions set the order sizes are listed in, on every screen and in every SKU. "One size" is for products that don\'t come in sizes: a product that has it has no other size.',
            ),
        ).toBeInTheDocument();

        const table = within(screen.getByRole('table', { name: 'Sizes' }));

        expect(
            table
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Size', 'Position', 'Used by', 'Actions']);

        const rows = table.getAllByRole('row').slice(1);

        expect(
            rows.map((row) =>
                within(row)
                    .getAllByRole('cell')
                    .slice(0, 3)
                    .map((cell) => cell.textContent),
            ),
        ).toEqual([
            ['XS', '10', '3 products'],
            ['XXS', '15', 'Not used'],
            ['S', '20', '3 products'],
            ['36', '80', '1 product'],
            ['One sizeNo size', '190', '2 products'],
        ]);
        expect(within(rows[4]).getByText('No size')).toBeInTheDocument();
        expect(table.queryAllByText('No size')).toHaveLength(1);
        expect(
            within(rows[0]).getByRole('button', { name: 'Edit XS' }),
        ).toHaveTextContent('Edit');
        expect(
            within(rows[4]).getByRole('button', { name: 'Edit One size' }),
        ).toBeInTheDocument();

        const phone = within(screen.getByRole('list', { name: 'Sizes' }));
        const items = phone.getAllByRole('listitem');

        expect(items).toHaveLength(5);
        expect(within(items[0]).getByText('Used by 3 products')).toBeVisible();
        expect(within(items[1]).getByText('Not used')).toBeVisible();
        expect(within(items[3]).getByText('Used by 1 product')).toBeVisible();
        expect(within(items[4]).getByText('No size')).toBeInTheDocument();
        expect(
            within(items[2]).getByRole('button', { name: 'Edit S' }),
        ).toHaveClass('h-11');

        expect(
            screen.queryByRole('columnheader', { name: 'Status' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', {
                name: /^(archive|restore|delete)/i,
            }),
        ).not.toBeInTheDocument();
    });
});
