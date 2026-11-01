import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { toast } from 'sonner';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { BarcodesTab } from '@/components/products/barcodes-tab';
import { colourNamed } from '@/test/fixtures/colours';
import {
    barcodesByColour,
    denimBarcodes,
    denimJacket,
    denimStockByColour,
    sizeListEu36To46,
    sizeListOneSize,
    sizeListSToXl,
    toteBarcodes,
} from '@/test/fixtures/products';
import { inertiaMock, resetInertiaMock } from '@/test/inertia';
import type { ProductBarcodes } from '@/types/domain';

vi.mock('sonner', () => ({ toast: { info: vi.fn() } }));

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const denim = { id: 1, name: denimJacket.name, sku: denimJacket.sku };
const tote = { id: 7, name: 'Leather Tote', sku: 'PRD-TOT-001' };
const sneaker = { id: 9, name: 'Denim Sneaker', sku: 'PRD-SNK-001' };
const NEVER_CHANGES =
    'A barcode never changes, and its number is never given to anything else.';

/** The callbacks Inertia would call on the visit the window made. */
type Visit = {
    only: string[];
    preserveScroll: boolean;
    preserveState: boolean;
    onStart: () => void;
    onSuccess: () => void;
    onError: (errors: Record<string, string>) => void;
    onFinish: () => void;
};

function lastPost(): { url: string; data: unknown; options: Visit } {
    const [url, data, options] =
        inertiaMock.router.post.mock.calls.at(-1) ?? [];

    return { url: String(url), data, options: options as Visit };
}

const ids = (colour: string, sizeIds: number[]) =>
    sizeIds.map((size_id) => ({
        colour_id: colourNamed(colour).id,
        size_id,
    }));

function renderTab(
    barcodes: ProductBarcodes,
    { product = denim, oneSize = false } = {},
) {
    return render(
        <BarcodesTab
            product={product}
            barcodes={barcodes}
            finishedStock={oneSize ? null : denimStockByColour}
            oneSize={oneSize}
        />,
    );
}

const dialog = (name: string) => screen.getByRole('dialog', { name });

describe('<GenerateBarcodesDialog>', () => {
    beforeEach(() => {
        resetInertiaMock();
        vi.mocked(toast.info).mockClear();
    });

    it('asks for every missing variant, shows a spinner with the label kept, then closes and focuses the table of barcodes (AC-3)', async () => {
        const user = userEvent.setup();

        renderTab(denimBarcodes);
        await user.click(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (4)',
            }),
        );

        const window = dialog('Generate 4 barcodes?');

        expect(window).toHaveTextContent(
            `Denim Jacket gets a barcode for Light wash S, M, L and XL, from your in-store numbers. ${NEVER_CHANGES}`,
        );
        expect(
            within(window).getByRole('button', { name: 'Not now' }),
        ).toBeInTheDocument();

        await user.click(
            within(window).getByRole('button', { name: 'Generate 4 barcodes' }),
        );

        const { url, data, options } = lastPost();

        expect(url).toBe('/products/1/barcodes');
        expect(data).toEqual({ variants: ids('Light wash', [1, 2, 3, 4]) });
        expect(options).toMatchObject({
            only: ['barcodes'],
            preserveScroll: true,
            preserveState: true,
        });

        // The button waits with a spinner and keeps its label.
        act(() => options.onStart());

        const confirm = within(window).getByRole('button', {
            name: /generate 4 barcodes/i,
        });

        expect(confirm).toBeDisabled();
        expect(within(confirm).getByRole('status')).toBeInTheDocument();
        expect(confirm).toHaveTextContent('Generate 4 barcodes');

        act(() => {
            options.onSuccess();
            options.onFinish();
        });

        await waitFor(
            () =>
                expect(
                    document.querySelector('[data-slot="barcodes-table"]'),
                ).toHaveFocus(),
            { timeout: 1000 },
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('lists several colours in the server order and sends all six; a "One size" product names its colours (AC-3)', async () => {
        const user = userEvent.setup();
        const { unmount } = renderTab(
            barcodesByColour(
                'PRD-JKT-001',
                sizeListSToXl,
                [
                    [
                        'Black',
                        ['2000000000053', null, null, '2000000000084'],
                        2,
                    ],
                    ['Light wash', [], 4],
                ],
                {
                    missing: 6,
                    missing_names:
                        'Black M and L, and Light wash S, M, L and XL',
                    with_barcode: 6,
                },
            ),
        );

        await user.click(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (6)',
            }),
        );

        expect(dialog('Generate 6 barcodes?')).toHaveTextContent(
            `Denim Jacket gets a barcode for Black M and L, and Light wash S, M, L and XL, from your in-store numbers. ${NEVER_CHANGES}`,
        );

        await user.click(
            screen.getByRole('button', { name: 'Generate 6 barcodes' }),
        );

        expect(lastPost().data).toEqual({
            variants: [
                ...ids('Black', [2, 3]),
                ...ids('Light wash', [1, 2, 3, 4]),
            ],
        });

        unmount();
        renderTab(
            barcodesByColour(
                'PRD-TOT-001',
                sizeListOneSize,
                [
                    ['Camel', [], 1],
                    ['Rust', [], 1],
                ],
                {
                    missing: 2,
                    missing_names: 'Camel and Rust',
                    with_barcode: 0,
                },
            ),
            { product: tote, oneSize: true },
        );
        await user.click(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (2)',
            }),
        );

        expect(dialog('Generate 2 barcodes?')).toHaveTextContent(
            `Leather Tote gets a barcode for Camel and Rust, from your in-store numbers. ${NEVER_CHANGES}`,
        );
    });

    it.each([
        {
            name: 'a letter size',
            product: denim,
            barcodes: denimBarcodes,
            oneSize: false,
            pill: 'Light wash · 4 missing',
            open: 'Generate barcode for PRD-JKT-001-LWH-S',
            text: 'Denim Jacket in Light wash, size S, gets a barcode',
        },
        {
            name: 'a shoe size',
            product: sneaker,
            barcodes: barcodesByColour(
                'PRD-SNK-001',
                sizeListEu36To46,
                [['Light wash', [], 11]],
                { missing: 11, missing_names: null, with_barcode: 0 },
            ),
            oneSize: false,
            pill: null,
            open: 'Generate barcode for PRD-SNK-001-LWH-36',
            text: 'Denim Sneaker in Light wash, size 36, gets a barcode',
        },
        {
            name: 'One size',
            product: tote,
            barcodes: toteBarcodes,
            oneSize: true,
            pill: null,
            open: 'Generate barcode for PRD-TOT-001-RST',
            text: 'Leather Tote in Rust gets a barcode',
        },
        {
            name: 'the main button with exactly one missing',
            product: tote,
            barcodes: toteBarcodes,
            oneSize: true,
            pill: null,
            open: 'Generate missing barcodes (1)',
            text: 'Leather Tote in Rust gets a barcode',
        },
    ])('words the single-barcode window for $name (AC-3)', async (row) => {
        const user = userEvent.setup();

        renderTab(row.barcodes, { product: row.product, oneSize: row.oneSize });

        if (row.pill) {
            await user.click(screen.getByRole('button', { name: row.pill }));
        }

        await user.click(screen.getAllByRole('button', { name: row.open })[0]);

        expect(dialog('Generate a barcode?')).toHaveTextContent(
            `${row.text} from your in-store numbers. ${NEVER_CHANGES}`,
        );

        await user.click(
            screen.getByRole('button', { name: 'Generate barcode' }),
        );

        expect(
            (lastPost().data as { variants: unknown[] }).variants,
        ).toHaveLength(1);
    });

    it('keeps the window with its message when the server refuses, and reloads the barcodes (AC-3)', async () => {
        const user = userEvent.setup();

        renderTab(denimBarcodes);
        await user.click(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (4)',
            }),
        );
        await user.click(
            screen.getByRole('button', { name: 'Generate 4 barcodes' }),
        );

        const { options } = lastPost();

        act(() => {
            options.onStart();
            options.onError({ variants: 'The variants are not valid.' });
            options.onFinish();
        });

        expect(
            within(dialog('Generate 4 barcodes?')).getByRole('alert'),
        ).toHaveTextContent("Couldn't generate the barcodes. Try again.");
        expect(
            screen.getByRole('button', { name: 'Generate 4 barcodes' }),
        ).toBeEnabled();
        expect(inertiaMock.router.reload).toHaveBeenCalledWith(
            expect.objectContaining({ only: ['barcodes'] }),
        );
    });

    it('after a refusal, closes with the toast "Barcodes changed. Check the list." when the reload shows other barcodes missing, and keeps the sentence when the same ones are (AC-3)', async () => {
        const user = userEvent.setup();
        const { rerender } = renderTab(denimBarcodes);
        const refuse = async () => {
            await user.click(
                screen.getByRole('button', { name: 'Generate 4 barcodes' }),
            );

            const { options } = lastPost();

            act(() => {
                options.onStart();
                options.onError({ variants: 'The variants are not valid.' });
                options.onFinish();
            });

            return inertiaMock.router.reload.mock.calls.at(-1)?.[0] as {
                onSuccess: () => void;
            };
        };

        await user.click(
            screen.getByRole('button', {
                name: 'Generate missing barcodes (4)',
            }),
        );

        // The reload lands and the same four are still missing: the sentence stays.
        const same = await refuse();

        act(() => same.onSuccess());

        expect(
            within(dialog('Generate 4 barcodes?')).getByRole('alert'),
        ).toHaveTextContent("Couldn't generate the barcodes. Try again.");
        expect(toast.info).not.toHaveBeenCalled();

        // Someone made them a moment ago: nothing left, so the window closes with the toast.
        const changed = await refuse();

        rerender(
            <BarcodesTab
                product={denim}
                barcodes={{
                    ...denimBarcodes,
                    missing: 0,
                    missing_names: null,
                    colours: denimBarcodes.colours.map((entry) => ({
                        ...entry,
                        missing: 0,
                        variants: entry.variants.map((variant) => ({
                            ...variant,
                            barcode: '2000000000015',
                        })),
                    })),
                }}
                finishedStock={denimStockByColour}
                oneSize={false}
            />,
        );
        act(() => changed.onSuccess());

        await waitFor(
            () => expect(screen.queryByRole('dialog')).not.toBeInTheDocument(),
            { timeout: 1000 },
        );
        expect(toast.info).toHaveBeenCalledExactlyOnceWith(
            'Barcodes changed. Check the list.',
        );
    });
});
