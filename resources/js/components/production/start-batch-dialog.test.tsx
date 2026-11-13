import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { StartBatchDialog } from '@/components/production/start-batch-dialog';
import { jacketLine, jacketRef } from '@/test/fixtures/restocks';
import { colourNamed } from '@/test/fixtures/colours';
import { inertiaMock, resetInertiaMock, respondToHttp } from '@/test/inertia';
import type { StartBatchLine, StartBatchPreview } from '@/types/domain';

vi.mock('@inertiajs/react', async (importOriginal) => {
    const { createInertiaModule } = await import('@/test/inertia');

    return createInertiaModule(
        await importOriginal<typeof import('@inertiajs/react')>(),
    );
});

const startLine: StartBatchLine = {
    ...jacketLine,
    can_produce: 112,
    bottleneck: 'Metal shank button 17mm',
};

const material = (id: number, name: string, unit: string) => ({
    id,
    sku: `MAT-${id}`,
    name,
    unit,
});

const preview16: StartBatchPreview = {
    units: 16,
    rows: [
        {
            material: material(1, 'Denim 12oz Indigo', 'm'),
            quantity: '31.6800',
            in_stock: '250.0000',
            after: '218.3200',
            short_by: null,
            colour: null,
            missing: false,
            not_returned: null,
        },
        {
            material: material(2, 'Metal shank button 17mm', 'pc'),
            quantity: '128.0000',
            in_stock: '900.0000',
            after: '772.0000',
            short_by: null,
            colour: null,
            missing: false,
            not_returned: null,
        },
        {
            material: material(3, 'Polyester thread', 'm'),
            quantity: '2016.0000',
            in_stock: '10000.0000',
            after: '7984.0000',
            short_by: null,
            colour: null,
            missing: false,
            not_returned: null,
        },
        {
            material: material(4, 'Woven brand label', 'pc'),
            quantity: '16.0000',
            in_stock: '300.0000',
            after: '284.0000',
            short_by: null,
            colour: null,
            missing: false,
            not_returned: null,
        },
    ],
    has_composition: true,
    max_cuttable: 112,
    short_message: null,
};

describe('StartBatchDialog (INV-5, AC-2)', () => {
    beforeEach(() => {
        resetInertiaMock();
        respondToHttp(({ path }) =>
            path.endsWith('/materials') ? preview16 : startLine,
        );
    });

    it('shows the grid with the shelf, the total, the materials and starts the batches', async () => {
        const user = userEvent.setup();
        render(<StartBatchDialog open preset={jacketRef} onClose={vi.fn()} />);

        const dialog = within(
            screen.getByRole('dialog', { name: 'Start batch' }),
        );
        expect(
            dialog.getByText(
                'Make products for your shelf. Materials are taken out of stock now.',
            ),
        ).toBeInTheDocument();
        expect(await dialog.findByText(/Can produce now/)).toHaveTextContent(
            'Can produce now 112',
        );

        const indigoM = dialog.getByLabelText('Indigo M');
        expect(indigoM).toHaveAccessibleDescription('Shelf Out');
        expect(dialog.getByLabelText('Indigo S')).toHaveAccessibleDescription(
            'Shelf 2',
        );

        await user.type(indigoM, '6');
        await user.type(dialog.getByLabelText('Indigo L'), '10');

        expect(
            dialog.getByText(
                'Total 16 units · 2 batches (Indigo M 6, Indigo L 10)',
            ),
        ).toBeInTheDocument();

        const table = await dialog.findByRole(
            'table',
            { name: 'Materials for 16 units' },
            { timeout: 2000 },
        );
        expect(within(table).getByText('31.68 m')).toBeInTheDocument();
        expect(within(table).getByText('2,016 m')).toBeInTheDocument();

        await user.click(dialog.getByRole('button', { name: 'Start batch' }));

        expect(inertiaMock.router.post).toHaveBeenCalledWith(
            '/progress/batches',
            expect.objectContaining({ product_id: jacketRef.id }),
            expect.anything(),
        );

        // The batches started come back as a flash.
        const options = inertiaMock.router.post.mock.calls[0][2] as {
            onFlash: (flash: unknown) => void;
        };
        act(() =>
            options.onFlash({
                batches_started: {
                    product: 'Denim Jacket',
                    units: 16,
                    batches: [
                        {
                            id: 21,
                            number: 'B-0153',
                            colour: 'Indigo',
                            size: 'M',
                            units: 6,
                        },
                        {
                            id: 22,
                            number: 'B-0154',
                            colour: 'Indigo',
                            size: 'L',
                            units: 10,
                        },
                    ],
                },
            }),
        );

        expect(dialog.getByText('2 batches started')).toBeInTheDocument();
        expect(
            dialog.getByText(
                '16 units of Denim Jacket started. Materials taken out of stock.',
            ),
        ).toBeInTheDocument();
        expect(
            dialog.getByRole('button', { name: 'Done' }),
        ).toBeInTheDocument();

        // INV-15 (AC-2): the tickets to print, in a new tab.
        expect(
            dialog.getByText(
                '2 tickets to print: B-0153 · Indigo M · 6 units, B-0154 · Indigo L · 10 units',
            ),
        ).toBeInTheDocument();
        const open = vi.spyOn(window, 'open').mockReturnValue({} as Window);
        await user.click(
            dialog.getByRole('button', { name: 'Print 2 tickets' }),
        );
        expect(decodeURIComponent(String(open.mock.calls[0][0]))).toBe(
            '/progress/tickets?batches[]=21&batches[]=22',
        );
        expect(open.mock.calls[0][1]).toBe('_blank');
        open.mockRestore();
    });

    it("shows the server's refusal and keeps what was typed", async () => {
        const user = userEvent.setup();
        render(<StartBatchDialog open preset={jacketRef} onClose={vi.fn()} />);

        const dialog = within(
            screen.getByRole('dialog', { name: 'Start batch' }),
        );
        const field = await dialog.findByLabelText('Indigo M');
        await user.type(field, '20');
        await user.click(dialog.getByRole('button', { name: 'Start batch' }));

        const options = inertiaMock.router.post.mock.calls[0][2] as {
            onError: (errors: Record<string, string>) => void;
        };
        const refusal =
            'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.';
        act(() => options.onError({ units: refusal }));

        expect(dialog.getByRole('alert')).toHaveTextContent(refusal);
        expect(field).toHaveValue('20');
    });

    // INV-21-q (AC-14): one row per material and colour; a colour the material lacks reads "No stock" with its Add link.
    it('reads the materials per colour and adds a missing colour without losing what was typed', async () => {
        const denimRef = material(5, 'Denim 12oz', 'm');
        const perColour: StartBatchPreview = {
            ...preview16,
            units: 21,
            rows: [
                {
                    material: denimRef,
                    colour: colourNamed('Indigo'),
                    missing: false,
                    quantity: '33.8800',
                    in_stock: '250.0000',
                    after: '216.1200',
                    short_by: null,
                    not_returned: null,
                },
                {
                    material: denimRef,
                    colour: colourNamed('Black'),
                    missing: false,
                    quantity: '8.8000',
                    in_stock: '40.0000',
                    after: '31.2000',
                    short_by: null,
                    not_returned: null,
                },
                {
                    material: denimRef,
                    colour: colourNamed('Light wash'),
                    missing: true,
                    quantity: '8.8000',
                    in_stock: '0.0000',
                    after: null,
                    short_by: '8.8000',
                    not_returned: null,
                },
            ],
            short_message:
                'Short on Denim 12oz in Light wash. Restock it first, or lower the units.',
        };
        // Once Light wash is added, the materials come back with that row no longer reading "No stock".
        const added: StartBatchPreview = {
            ...perColour,
            rows: perColour.rows.map((row) =>
                row.missing ? { ...row, missing: false } : row,
            ),
        };
        let answer = perColour;
        respondToHttp(({ path }) =>
            path.endsWith('/materials') ? answer : startLine,
        );
        const user = userEvent.setup();
        render(<StartBatchDialog open preset={jacketRef} onClose={vi.fn()} />);

        const dialog = within(
            screen.getByRole('dialog', { name: 'Start batch' }),
        );
        const field = await dialog.findByLabelText('Indigo M');
        await user.type(field, '6');
        const table = await dialog.findByRole(
            'table',
            { name: 'Materials for 21 units' },
            { timeout: 2000 },
        );

        const rowOf = (name: string) =>
            within(
                within(table).getByRole('rowheader', { name }).closest('tr')!,
            );
        const indigo = rowOf('Denim 12oz · Indigo');
        expect(indigo.getByText('33.88 m')).toBeInTheDocument();
        expect(indigo.getByText('250 m')).toBeInTheDocument();
        const black = rowOf('Denim 12oz · Black');
        expect(black.getByText('8.8 m')).toBeInTheDocument();
        expect(black.getByText('40 m')).toBeInTheDocument();

        const lightWash = rowOf(
            'Denim 12oz · Light wash Add Light wash to Denim 12oz',
        );
        expect(lightWash.getByText('No stock')).toBeInTheDocument();
        expect(lightWash.getByText('8.8 m')).toBeInTheDocument();
        expect(lightWash.getByText('Short by 8.8 m')).toBeInTheDocument();

        // The window opens over this one; closing it asks for the materials again and the grid stays as typed.
        const asked = () =>
            inertiaMock.http.requests.filter((request) =>
                request.path.endsWith('/materials'),
            ).length;
        const before = asked();
        const link = lightWash.getByRole('button', {
            name: 'Add Light wash to Denim 12oz',
        });
        await user.click(link);
        const adding = await screen.findByRole('alertdialog', {
            name: 'Add Light wash to Denim 12oz?',
        });
        await user.click(
            within(adding).getByRole('button', { name: 'Cancel' }),
        );

        await waitFor(() => expect(asked()).toBeGreaterThan(before));
        expect(field).toHaveValue('6');
        // The link is still there (the row still reads No stock): focus returns to it.
        await waitFor(() => expect(link).toHaveFocus());

        // The colour was added: the row no longer reads No stock and its link is gone. Focus stays inside this window.
        answer = added;
        await user.click(link);
        await user.click(
            within(
                await screen.findByRole('alertdialog', {
                    name: 'Add Light wash to Denim 12oz?',
                }),
            ).getByRole('button', { name: 'Cancel' }),
        );

        await waitFor(() => expect(link).not.toBeInTheDocument());
        await waitFor(() =>
            expect(
                within(table).getByText('Materials for 21 units'),
            ).toHaveFocus(),
        );
        expect(
            screen.getByRole('dialog', { name: 'Start batch' }),
        ).toContainElement(document.activeElement as HTMLElement);
    });

    // INV-16-k (AC-6): the preview is asked for the grid, one field per colour and size above 0.
    it('asks for the materials of the grid typed, leaving the empty fields out', async () => {
        const user = userEvent.setup();
        render(<StartBatchDialog open preset={jacketRef} onClose={vi.fn()} />);

        const dialog = within(
            screen.getByRole('dialog', { name: 'Start batch' }),
        );
        const colour = (name: string) =>
            jacketLine.stock.colours.find(
                (entry) => entry.colour.name === name,
            )!.colour.id;
        const size = (label: string) =>
            jacketLine.sizes.find((entry) => entry.label === label)!.id;

        await user.type(await dialog.findByLabelText('Indigo M'), '6');
        await user.type(dialog.getByLabelText('Black S'), '5');
        await user.type(dialog.getByLabelText('Indigo L'), '0');

        await waitFor(
            () => {
                const requests = inertiaMock.http.requests.filter((request) =>
                    request.path.endsWith('/materials'),
                );
                expect(requests.at(-1)?.params).toEqual({
                    [`units[${colour('Indigo')}][${size('M')}]`]: '6',
                    [`units[${colour('Black')}][${size('S')}]`]: '5',
                });
            },
            { timeout: 2000 },
        );
        expect(
            await dialog.findByRole(
                'table',
                { name: 'Materials for 16 units' },
                { timeout: 2000 },
            ),
        ).toBeInTheDocument();
    });

    // INV-16-k (AC-6): a short message (mixed grid, or today's single-variant one) disables Start batch without removing it from the tab order.
    it.each([
        [
            'mixed grid',
            'Short on Denim 12oz Black. Restock it first, or lower the units.',
        ],
        [
            'single variant',
            'Not enough stock to cut 20 units. You can cut at most 12 now: Metal shank button 17mm runs short. Restock it first, or cut 12 or fewer.',
        ],
    ])(
        'keeps Start batch focusable but inert while the %s message shows',
        async (_case, message) => {
            respondToHttp(({ path }) =>
                path.endsWith('/materials')
                    ? {
                          ...preview16,
                          rows: [
                              {
                                  ...preview16.rows[0],
                                  after: '0.0000',
                                  short_by: '1.8000',
                              },
                          ],
                          max_cuttable: null,
                          short_message: message,
                      }
                    : startLine,
            );
            const user = userEvent.setup();
            render(
                <StartBatchDialog open preset={jacketRef} onClose={vi.fn()} />,
            );

            const dialog = within(
                screen.getByRole('dialog', { name: 'Start batch' }),
            );
            await user.type(await dialog.findByLabelText('Indigo M'), '20');

            expect(
                await dialog.findByText(message, undefined, { timeout: 2000 }),
            ).toBeInTheDocument();
            expect(dialog.getByText('Short by 1.8 m')).toBeInTheDocument();

            const button = dialog.getByRole('button', { name: 'Start batch' });
            expect(button).toHaveAttribute('aria-disabled', 'true');
            expect(button).toBeEnabled();
            expect(button).toHaveAccessibleDescription(message);

            button.focus();
            expect(button).toHaveFocus();

            await user.click(button);
            await user.keyboard('{Enter}');
            expect(inertiaMock.router.post).not.toHaveBeenCalled();
        },
    );
});
