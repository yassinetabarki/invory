import { render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import Colours from '@/pages/settings/colours';
import { defaultColours } from '@/test/fixtures/colours';
import { resetInertiaMock, setPageProps } from '@/test/inertia';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

describe('settings/colours page', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps({}, { url: '/settings/colours' });
    });

    it('shows the title, the line, Add colour and the colours with Code, Used by and Edit, in a table and in cards', () => {
        render(<Colours colours={defaultColours} />);

        expect(
            screen.getByRole('heading', { name: 'Colours', level: 2 }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'The colours your products come in. Each one shows as its swatch and its name. Rename any of them, or add your own.',
            ),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Add colour' }),
        ).toBeInTheDocument();

        const table = within(screen.getByRole('table', { name: 'Colours' }));

        expect(
            table
                .getAllByRole('columnheader')
                .map((header) => header.textContent),
        ).toEqual(['Colour', 'Code', 'Used by', 'Actions']);

        const rows = table.getAllByRole('row').slice(1);

        expect(rows).toHaveLength(20);
        expect(
            rows.map((row) => within(row).getAllByRole('cell')[0].textContent),
        ).toEqual(defaultColours.map((colour) => colour.name));

        const rowOf = (name: string) =>
            within(
                rows.find((row) =>
                    within(row).queryByText(name, { exact: true }),
                )!,
            );

        expect(rowOf('Indigo').getByText('IND')).toBeInTheDocument();
        expect(rowOf('Black').getByText('4 products')).toBeInTheDocument();
        expect(rowOf('Light wash').getByText('1 product')).toBeInTheDocument();
        expect(rowOf('Ecru').getByText('Not used')).toBeInTheDocument();
        expect(
            rowOf('Indigo').getByRole('button', { name: 'Edit Indigo' }),
        ).toBeInTheDocument();

        const cards = within(screen.getByRole('list', { name: 'Colours' }));
        const card = within(
            cards
                .getAllByRole('listitem')
                .find((item) => within(item).queryByText('Black'))!,
        );

        expect(cards.getAllByRole('listitem')).toHaveLength(20);
        expect(card.getByText('BLK')).toBeInTheDocument();
        expect(card.getByText('4 products')).toBeInTheDocument();
        expect(
            card.getByRole('button', { name: 'Edit Black' }),
        ).toBeInTheDocument();

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
