import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { BarcodesTab } from '@/components/products/barcodes-tab';
import {
    barcodesByColour,
    denimBarcodes,
    denimJacket,
    denimStockByColour,
    sizeListSToXl,
    toteBarcodes,
    toteCamelRustStock,
} from '@/test/fixtures/products';
import { resetInertiaMock } from '@/test/inertia';
import type { FinishedStock, ProductBarcodes } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const denim = { id: 1, name: denimJacket.name, sku: denimJacket.sku };
const tote = { id: 7, name: 'Leather Tote', sku: 'PRD-TOT-001' };

function renderTab({
    barcodes = denimBarcodes,
    stock = denimStockByColour,
    oneSize = false,
    product = denim,
}: {
    barcodes?: ProductBarcodes;
    stock?: FinishedStock | null;
    oneSize?: boolean;
    product?: typeof denim;
} = {}) {
    const view = render(
        <BarcodesTab
            product={product}
            barcodes={barcodes}
            finishedStock={stock}
            oneSize={oneSize}
        />,
    );

    return view;
}

/** The laptop table; jsdom renders it next to the phone cards. */
const table = (name: string) => within(screen.getByRole('table', { name }));

describe('<BarcodesTab>', () => {
    beforeEach(() => {
        resetInertiaMock();
    });

    it('shows the barcodes of the chosen colour: Indigo reads S to XL with its numbers, stock and no Generate button', async () => {
        const user = userEvent.setup();

        renderTab();
        await user.click(screen.getByRole('button', { name: 'Indigo' }));

        const indigo = table('Barcodes for Indigo');

        expect(
            indigo.getAllByRole('columnheader').map((cell) => cell.textContent),
        ).toEqual(['Size', 'SKU', 'Barcode (EAN-13)', 'In stock', 'Action']);

        const rows = indigo.getAllByRole('row').slice(1);
        const read = (row: HTMLElement) =>
            within(row)
                .getAllByRole('cell')
                .map((cell) => cell.textContent);

        expect(rows).toHaveLength(4);
        expect(within(rows[0]).getByRole('rowheader')).toHaveTextContent('S');
        expect(read(rows[0])).toEqual([
            'PRD-JKT-001-IND-S',
            '2000000000015',
            '2',
            '',
        ]);
        expect(read(rows[1]).slice(0, 3)).toEqual([
            'PRD-JKT-001-IND-M',
            '2000000000022',
            '0',
        ]);
        expect(read(rows[2]).slice(0, 3)).toEqual([
            'PRD-JKT-001-IND-L',
            '2000000000039',
            '3',
        ]);
        expect(read(rows[3]).slice(0, 3)).toEqual([
            'PRD-JKT-001-IND-XL',
            '2000000000046',
            '1',
        ]);
        expect(indigo.queryByRole('button')).not.toBeInTheDocument();
        expect(
            screen.getByText(
                'One barcode for each colour and size. Every product named Denim Jacket shares it, and a barcode never changes.',
            ),
        ).toBeInTheDocument();
    });

    it('asks for the missing barcodes: pills with their counts, the first colour chosen, a named Generate per row, the main button only while any is missing', async () => {
        const user = userEvent.setup();
        const { rerender } = renderTab();

        const colours = screen.getByRole('group', { name: 'Colour' });

        expect(
            within(colours)
                .getAllByRole('button')
                .map((pill) => pill.textContent?.trim()),
        ).toEqual(['Black', 'Indigo', 'Light wash · 4 missing']);
        expect(
            within(colours).getByRole('button', { name: 'Black' }),
        ).toHaveAttribute('aria-pressed', 'true');

        await user.click(
            within(colours).getByRole('button', {
                name: 'Light wash · 4 missing',
            }),
        );

        expect(
            within(colours).getByRole('button', {
                name: 'Light wash · 4 missing',
            }),
        ).toHaveAttribute('aria-pressed', 'true');
        expect(
            within(colours).getByRole('button', { name: 'Black' }),
        ).toHaveAttribute('aria-pressed', 'false');

        const lightWash = table('Barcodes for Light wash');

        expect(lightWash.getAllByText('No barcode yet')).toHaveLength(4);

        await user.click(
            lightWash.getByRole('button', {
                name: 'Generate barcode for PRD-JKT-001-LWH-S',
            }),
        );

        // A row's Generate asks for that one variant.
        expect(
            screen.getByRole('dialog', { name: 'Generate a barcode?' }),
        ).toHaveTextContent('Denim Jacket in Light wash, size S, gets');
        await user.click(screen.getByRole('button', { name: 'Not now' }));
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

        // The main button is the page-wide one, in the pills' row (not in the table).
        await user.click(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (4)',
            }),
        );

        expect(
            screen.getByRole('dialog', { name: 'Generate 4 barcodes?' }),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'Not now' }));

        rerender(
            <BarcodesTab
                product={denim}
                barcodes={{
                    ...denimBarcodes,
                    missing: 0,
                    missing_names: null,
                    with_barcode: 12,
                }}
                finishedStock={denimStockByColour}
                oneSize={false}
            />,
        );

        expect(
            screen.queryByRole('button', { name: /generate missing/i }),
        ).not.toBeInTheDocument();
    });

    it('names who shares a barcode: one, three, or six others, where "4 more" opens the small list of links', async () => {
        const user = userEvent.setup();
        const sku = (n: number) => ({ id: 100 + n, sku: `PRD-JKT-00${n}` });
        const barcodes = barcodesByColour(
            'PRD-JKT-001',
            sizeListSToXl,
            [
                [
                    'Indigo',
                    [
                        '2000000000015',
                        '2000000000022',
                        '2000000000039',
                        '2000000000046',
                    ],
                    0,
                ],
            ],
            { missing: 0, missing_names: null, with_barcode: 4 },
        );
        const variants = barcodes.colours[0].variants;

        variants[0].shared_with = [sku(2)];
        variants[1].shared_with = [sku(2), sku(7), sku(9)];
        variants[2].shared_with = [
            sku(2),
            sku(3),
            sku(4),
            sku(5),
            sku(6),
            sku(7),
        ];

        renderTab({ barcodes });

        const rows = table('Barcodes for Indigo').getAllByRole('row').slice(1);

        expect(rows[0]).toHaveTextContent('Shared with PRD-JKT-002');
        expect(
            within(rows[0]).getByRole('link', { name: 'PRD-JKT-002' }),
        ).toHaveAttribute('href', '/products/102');
        expect(rows[1]).toHaveTextContent(
            'Shared with PRD-JKT-002, PRD-JKT-007 and PRD-JKT-009',
        );
        expect(rows[2]).toHaveTextContent(
            'Shared with PRD-JKT-002, PRD-JKT-003 and 4 more',
        );
        expect(rows[3]).not.toHaveTextContent('Shared with');

        await user.click(
            within(rows[2]).getByRole('button', { name: '4 more' }),
        );

        const list = within(
            screen.getByRole('dialog', { name: 'Shared with' }),
        );

        expect(
            list.getAllByRole('link').map((link) => link.textContent),
        ).toEqual(['PRD-JKT-004', 'PRD-JKT-005', 'PRD-JKT-006', 'PRD-JKT-007']);
        expect(list.getByRole('link', { name: 'PRD-JKT-007' })).toHaveAttribute(
            'href',
            '/products/107',
        );
    });

    it('lists a "One size" product by colour: no pills, a Colour column and the colour SKUs', () => {
        renderTab({
            product: tote,
            barcodes: toteBarcodes,
            stock: toteCamelRustStock,
            oneSize: true,
        });

        expect(
            screen.queryByRole('group', { name: 'Colour' }),
        ).not.toBeInTheDocument();

        const rows = within(screen.getByRole('table', { name: 'Barcodes' }));

        expect(
            rows.getAllByRole('columnheader').map((cell) => cell.textContent),
        ).toEqual(['Colour', 'SKU', 'Barcode (EAN-13)', 'In stock', 'Action']);

        const [camel, rust] = rows.getAllByRole('row').slice(1);

        expect(camel).toHaveTextContent('Camel');
        expect(camel).toHaveTextContent('PRD-TOT-001-CAM');
        expect(camel).toHaveTextContent('2000000000091');
        expect(rust).toHaveTextContent('PRD-TOT-001-RST');
        expect(within(rust).getByText('No barcode yet')).toBeInTheDocument();
        expect(
            within(rust).getByRole('button', {
                name: 'Generate barcode for PRD-TOT-001-RST',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (1)',
            }),
        ).toBeInTheDocument();
    });

    it('shows one card per size on a phone, with a full-width 44 px Generate on a variant without a barcode', async () => {
        const user = userEvent.setup();
        renderTab();

        await user.click(
            screen.getByRole('button', { name: 'Light wash · 4 missing' }),
        );

        const cards = within(screen.getByRole('list')).getAllByRole('listitem');

        expect(cards).toHaveLength(4);
        expect(cards[0]).toHaveTextContent('S');
        expect(cards[0]).toHaveTextContent('PRD-JKT-001-LWH-S');
        expect(cards[0]).toHaveTextContent('In stock 0');
        expect(
            within(cards[0]).getByText('No barcode yet'),
        ).toBeInTheDocument();

        const generate = within(cards[0]).getByRole('button', {
            name: 'Generate barcode for PRD-JKT-001-LWH-S',
        });

        expect(generate).toHaveClass('w-full', 'h-11');

        // The main button is full width under the pills.
        expect(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (4)',
            }),
        ).toHaveClass('w-full', 'h-11');
    });
});
