import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { StartBatchDialog } from '@/components/production/start-batch-dialog';
import { jacketLine, jacketRef } from '@/test/fixtures/restocks';
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
            not_returned: null,
        },
        {
            material: material(2, 'Metal shank button 17mm', 'pc'),
            quantity: '128.0000',
            in_stock: '900.0000',
            after: '772.0000',
            short_by: null,
            not_returned: null,
        },
        {
            material: material(3, 'Polyester thread', 'm'),
            quantity: '2016.0000',
            in_stock: '10000.0000',
            after: '7984.0000',
            short_by: null,
            not_returned: null,
        },
        {
            material: material(4, 'Woven brand label', 'pc'),
            quantity: '16.0000',
            in_stock: '300.0000',
            after: '284.0000',
            short_by: null,
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
});
