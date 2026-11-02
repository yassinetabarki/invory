import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import RestocksCreate from '@/pages/restocks/create';
import { colourNamed } from '@/test/fixtures/colours';
import { jacketLine, toteLine } from '@/test/fixtures/restocks';
import { inertiaMock, resetInertiaMock, setPageProps } from '@/test/inertia';
import type { RestockBarcodeLookup, RestockLine } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const INDIGO = colourNamed('Indigo').id;
const CAMEL = colourNamed('Camel').id;
const M = 2;
const ONE_SIZE = 16;

/** A second product of the same name (ADR-0020: they share the barcode). */
const jacket2: RestockLine = {
    ...jacketLine,
    product: { ...jacketLine.product, id: 9, sku: 'PRD-JKT-002' },
};

/** Types a barcode, presses Enter, and answers the lookup. */
async function scan(number: string, answer: RestockBarcodeLookup | 'fail') {
    const user = userEvent.setup();

    await user.type(
        screen.getByRole('textbox', { name: 'Scan or type a barcode' }),
        `${number}{Enter}`,
    );

    const request = inertiaMock.http.requests.at(-1);
    expect(request).toMatchObject({
        path: '/restocks/barcode',
        params: { number },
    });

    await act(async () => {
        if (answer === 'fail') {
            request?.reject();
        } else {
            request?.resolve(answer);
        }
    });
}

function found(
    matches: RestockLine[],
    colourId: number,
    sizeId: number,
): RestockBarcodeLookup {
    return { result: 'found', colour_id: colourId, size_id: sizeId, matches };
}

describe('New restock: Scan or type a barcode (AC-4)', () => {
    beforeEach(() => {
        resetInertiaMock();
        setPageProps(
            {},
            { url: '/restocks/create', component: 'restocks/create' },
        );
    });

    it('has the field, Add 1 and the hint', () => {
        render(<RestocksCreate preset={null} />);

        expect(
            screen.getByRole('textbox', { name: 'Scan or type a barcode' }),
        ).toHaveAccessibleDescription(
            /^Each scan adds one unit to its colour and size\. A barcode scanner works like typing\./,
        );
        expect(
            screen.getByRole('button', { name: 'Add 1' }),
        ).toBeInTheDocument();
    });

    it('adds one unit to the scanned colour and size, says so in green, and keeps the focus in the field', async () => {
        const user = userEvent.setup();

        render(<RestocksCreate preset={jacketLine} />);
        const jacket = within(
            screen.getByRole('region', { name: 'Denim Jacket' }),
        );
        await user.type(jacket.getByLabelText('Indigo M'), '7');

        await scan('2000000000015', found([jacketLine], INDIGO, M));

        expect(
            screen.getByText(
                'Added 1 · Denim Jacket · Indigo · M · 8 on this restock',
            ),
        ).toBeInTheDocument();
        expect(jacket.getByLabelText('Indigo M')).toHaveValue('8');
        expect(
            screen.getByRole('textbox', { name: 'Scan or type a barcode' }),
        ).toHaveValue('');
        expect(
            screen.getByRole('textbox', { name: 'Scan or type a barcode' }),
        ).toHaveFocus();
        expect(inertiaMock.router.post).not.toHaveBeenCalled();
    });

    it('adds a product not yet on the restock at the end, with that one unit', async () => {
        render(<RestocksCreate preset={jacketLine} />);

        await scan('2000000000053', found([toteLine], CAMEL, ONE_SIZE));

        expect(
            screen.getByText(
                'Added 1 · Leather Tote · Camel · 1 on this restock',
            ),
        ).toBeInTheDocument();
        const regions = screen
            .getAllByRole('region')
            .map((region) => region.getAttribute('aria-label'));
        expect(regions.slice(-2)).toEqual(['Denim Jacket', 'Leather Tote']);
        expect(
            within(
                screen.getByRole('region', { name: 'Leather Tote' }),
            ).getByLabelText('Camel'),
        ).toHaveValue('1');
    });

    it('asks which product a shared barcode is for, by SKU, unless one of them is already on the restock', async () => {
        const user = userEvent.setup();
        const { unmount } = render(<RestocksCreate preset={null} />);

        await scan('2000000000022', found([jacketLine, jacket2], INDIGO, M));

        expect(
            screen.getByText(
                '2000000000022 is Indigo M on 2 products named Denim Jacket. Which one?',
            ),
        ).toBeInTheDocument();
        await user.click(screen.getByRole('button', { name: 'PRD-JKT-002' }));
        expect(
            screen.getByText(
                'Added 1 · Denim Jacket · Indigo · M · 1 on this restock',
            ),
        ).toBeInTheDocument();
        unmount();

        render(<RestocksCreate preset={jacketLine} />);
        await scan('2000000000022', found([jacketLine, jacket2], INDIGO, M));

        expect(screen.queryByText(/Which one\?/)).not.toBeInTheDocument();
        expect(
            screen.getByText(
                'Added 1 · Denim Jacket · Indigo · M · 1 on this restock',
            ),
        ).toBeInTheDocument();
    });

    it("shows the server's red line for a barcode it can't use, and \"Couldn't look up the barcode. Try again.\" when the lookup fails", async () => {
        render(<RestocksCreate preset={jacketLine} />);

        await scan('2000000000992', {
            result: 'unknown',
            message:
                'No product has the barcode 2000000000992. Check the label, or enter the units below.',
        });
        expect(
            screen.getByText(
                'No product has the barcode 2000000000992. Check the label, or enter the units below.',
            ),
        ).toBeInTheDocument();

        await scan('12345', {
            result: 'invalid',
            message: 'A barcode has 13 digits. Check the number.',
        });
        expect(
            screen.getByText('A barcode has 13 digits. Check the number.'),
        ).toBeInTheDocument();

        await scan('2000000000015', 'fail');
        expect(
            screen.getByText("Couldn't look up the barcode. Try again."),
        ).toBeInTheDocument();
    });
});
